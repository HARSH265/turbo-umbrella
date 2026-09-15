<?php

namespace Tests\Feature\Maintenance;

use App\Models\Flat;
use App\Models\Maintenance;
use App\Models\MaintenancePolicy;
use App\Models\MaintenancePolicyTemplate;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use App\Services\MaintenancePaymentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MaintenancePaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $systemUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->systemUser = User::factory()->create([
            'created_by' => null,
            'updated_by' => null,
            'society_id' => null,
        ]);

        $this->assignRole($this->systemUser, 'super-admin');
    }

    public function test_stale_maintenance_instance_cannot_overwrite_payment_aggregate(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-501')
        );
        $staleMaintenance = Maintenance::findOrFail($maintenance->id);

        $paymentService = app(MaintenancePaymentService::class);

        $paymentService->recordPayment(
            maintenance: $maintenance,
            amount: 600,
            performedBy: $this->systemUser->id,
            paymentMode: 'cash'
        );

        try {
            $paymentService->recordPayment(
                maintenance: $staleMaintenance,
                amount: 500,
                performedBy: $this->systemUser->id,
                paymentMode: 'cash'
            );

            $this->fail('Expected stale payment attempt to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->assertDatabaseHas('maintenances', [
            'id' => $maintenance->id,
            'amount_paid' => 600,
            'status' => 'partially_paid',
            'payment_mode' => 'cash',
        ]);

        $this->assertDatabaseCount('maintenance_payments', 1);
        $this->assertDatabaseHas('maintenance_payments', [
            'maintenance_id' => $maintenance->id,
            'amount_paid' => 600,
            'payment_mode' => 'cash',
        ]);
    }

    public function test_partial_payment_is_allowed_when_policy_permits_it(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-502')
        );
        $this->createActivePolicy($society, true);

        $maintenance = app(MaintenancePaymentService::class)->recordPayment(
            maintenance: $maintenance,
            amount: 400,
            performedBy: $this->systemUser->id,
            paymentMode: 'upi',
            transactionId: 'TXN-400'
        );

        $this->assertSame('partially_paid', $maintenance->status->value);
        $this->assertSame(400.0, (float) $maintenance->amount_paid);
        $this->assertNull($maintenance->paid_date);

        $this->assertDatabaseHas('maintenance_payments', [
            'maintenance_id' => $maintenance->id,
            'amount_paid' => 400,
            'payment_mode' => 'upi',
            'transaction_id' => 'TXN-400',
        ]);
    }

    public function test_partial_payment_is_blocked_when_policy_disallows_it(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-503')
        );
        $this->createActivePolicy($society, false);

        try {
            app(MaintenancePaymentService::class)->recordPayment(
                maintenance: $maintenance,
                amount: 400,
                performedBy: $this->systemUser->id,
                paymentMode: 'cash'
            );

            $this->fail('Expected partial payment to be rejected by policy.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Partial payment is not allowed as per society policy. Please pay full amount.',
                $e->errors()['amount'][0]
            );
        }

        $this->assertDatabaseHas('maintenances', [
            'id' => $maintenance->id,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseCount('maintenance_payments', 0);
    }

    public function test_exact_final_payment_marks_maintenance_paid(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-504'),
            ['amount_paid' => 400, 'status' => 'partially_paid']
        );
        $this->createActivePolicy($society, true);

        $maintenance = app(MaintenancePaymentService::class)->recordPayment(
            maintenance: $maintenance,
            amount: 600,
            performedBy: $this->systemUser->id,
            paymentMode: 'online'
        );

        $this->assertSame('paid', $maintenance->status->value);
        $this->assertSame(1000.0, (float) $maintenance->amount_paid);
        $this->assertNotNull($maintenance->paid_date);
    }

    public function test_overpayment_is_blocked(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-505')
        );

        try {
            app(MaintenancePaymentService::class)->recordPayment(
                maintenance: $maintenance,
                amount: 1200,
                performedBy: $this->systemUser->id,
                paymentMode: 'cash'
            );

            $this->fail('Expected overpayment to be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Payment exceeds balance due.', $e->errors()['amount'][0]);
        }

        $this->assertDatabaseHas('maintenances', [
            'id' => $maintenance->id,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseCount('maintenance_payments', 0);
    }

    public function test_second_payment_after_full_payment_is_blocked(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-506')
        );

        $paymentService = app(MaintenancePaymentService::class);
        $paymentService->recordPayment(
            maintenance: $maintenance,
            amount: 1000,
            performedBy: $this->systemUser->id,
            paymentMode: 'cash'
        );

        try {
            $paymentService->recordPayment(
                maintenance: $maintenance->fresh(),
                amount: 1,
                performedBy: $this->systemUser->id,
                paymentMode: 'cash'
            );

            $this->fail('Expected payment after full payment to be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame('This maintenance is already fully paid.', $e->errors()['amount'][0]);
        }

        $this->assertDatabaseCount('maintenance_payments', 1);
    }

    public function test_resident_can_pay_only_own_flat_maintenance(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $ownFlat = $this->createFlatForSociety($society, 'A-507');
        $otherFlat = $this->createFlatForSociety($society, 'A-508');
        $ownMaintenance = $this->createMaintenance($ownFlat);
        $otherMaintenance = $this->createMaintenance($otherFlat, ['month' => '2026-06']);

        $resident->flats()->attach($ownFlat->id, [
            'relation_type' => 'owner',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => true,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $response = $this->actingAs($resident)->post(route('maintenance.process-payment', $ownMaintenance), [
            'amount' => 1000,
            'payment_mode' => 'cash',
        ]);
        $response->assertRedirect();

        $forbidden = $this->actingAs($resident)->post(route('maintenance.process-payment', $otherMaintenance), [
            'amount' => 1000,
            'payment_mode' => 'cash',
        ]);
        $forbidden->assertForbidden();

        $this->assertDatabaseHas('maintenances', [
            'id' => $ownMaintenance->id,
            'amount_paid' => 1000,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('maintenances', [
            'id' => $otherMaintenance->id,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);
    }

    public function test_payment_page_shows_policy_hint_and_hides_form_for_paid_maintenance(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $flat = $this->createFlatForSociety($society, 'A-509');
        $maintenance = $this->createMaintenance($flat, [
            'amount_paid' => 1000,
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
        ]);
        $this->createActivePolicy($society, false);

        $resident->flats()->attach($flat->id, [
            'relation_type' => 'owner',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => true,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $response = $this->actingAs($resident)->get(route('maintenance.payment', $maintenance));

        $response->assertOk();
        $response->assertSee('Payment Rules');
        $response->assertSee('This maintenance record is already fully paid.');
        $response->assertDontSee('name="amount"', false);
    }

    private function createSocietyContext(string $prefix): array
    {
        $society = Society::create([
            'name' => strtoupper($prefix) . ' Society',
            'code' => strtoupper($prefix) . '-' . fake()->unique()->numerify('###'),
            'address' => fake()->address(),
            'city' => 'Indore',
            'state' => 'MP',
            'pincode' => '452001',
            'contact_number' => '900000' . fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => strtoupper($prefix) . '-T1',
            'total_floors' => 10,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        return [$society, $tower];
    }

    private function createFlatForSociety(Society $society, string $flatNumber): Flat
    {
        return Flat::create([
            'tower_id' => $society->towers()->first()->id,
            'flat_number' => $flatNumber,
            'floor_number' => 5,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }

    private function createMaintenance(Flat $flat, array $overrides = []): Maintenance
    {
        return Maintenance::create(array_merge([
            'flat_id' => $flat->id,
            'month' => '2026-05',
            'amount' => 1000,
            'amount_paid' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
            'late_fee' => 0,
            'status' => 'unpaid',
            'created_by' => $this->systemUser->id,
        ], $overrides));
    }

    private function createActivePolicy(Society $society, bool $allowPartialPayment): MaintenancePolicy
    {
        $template = MaintenancePolicyTemplate::create([
            'name' => 'Test Policy',
            'billing_cycle' => 'monthly',
            'calculation_type' => 'fixed',
            'base_amount' => 1000,
            'late_fee_type' => 'fixed',
            'late_fee_value' => 0,
            'grace_days' => 0,
            'allow_partial_payment' => $allowPartialPayment,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        return MaintenancePolicy::create([
            'society_id' => $society->id,
            'template_id' => $template->id,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);
    }

    private function createUserWithRole(string $roleSlug, ?Society $society): User
    {
        $user = User::factory()->create([
            'society_id' => $society?->id,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $this->assignRole($user, $roleSlug);

        return $user;
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
