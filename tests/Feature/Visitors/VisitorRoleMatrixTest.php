<?php

namespace Tests\Feature\Visitors;

use App\Models\Flat;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VisitorRoleMatrixTest extends TestCase
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

    public function test_staff_can_register_visitor_for_same_society_flat(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $flat = $this->createFlatForSociety($society, 'A-101');

        $response = $this->actingAs($staff)->post(route('visitors.store'), [
            'flat_id' => $flat->id,
            'name' => 'Rohan Guest',
            'phone' => '9876543210',
            'purpose' => 'Delivery',
            'entry_time' => now()->toDateTimeString(),
            'remarks' => 'Gate 1',
        ]);

        $response->assertRedirect(route('visitors.index'));

        $this->assertDatabaseHas('visitors', [
            'flat_id' => $flat->id,
            'name' => 'Rohan Guest',
            'created_by' => $staff->id,
            'approval_status' => 'pending',
        ]);
    }

    public function test_staff_index_only_lists_same_society_visitors(): void
    {
        [$staffSociety] = $this->createSocietyContext('alpha');
        [$otherSociety] = $this->createSocietyContext('beta');

        $staff = $this->createUserWithRole('staff', $staffSociety);
        $visibleVisitor = $this->createVisitor($this->createFlatForSociety($staffSociety, 'A-110'));
        $hiddenVisitor = $this->createVisitor($this->createFlatForSociety($otherSociety, 'B-210'));

        $response = $this->actingAs($staff)->get(route('visitors.index', ['show_all' => 1]));

        $response->assertOk();
        $response->assertSee($visibleVisitor->name);
        $response->assertDontSee($hiddenVisitor->name);
    }

    public function test_inside_filter_only_lists_visitors_without_exit_time(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $insideVisitor = $this->createVisitor($this->createFlatForSociety($society, 'A-112'), [
            'approval_status' => 'approved',
            'approved_by' => $staff->id,
            'approved_at' => now()->subMinute(),
            'exit_time' => null,
        ]);
        $exitedVisitor = $this->createVisitor($this->createFlatForSociety($society, 'A-113'), [
            'approval_status' => 'approved',
            'approved_by' => $staff->id,
            'approved_at' => now()->subMinutes(2),
            'exit_time' => now()->subMinute(),
        ]);

        $response = $this->actingAs($staff)->get(route('visitors.index', [
            'show_all' => 1,
            'inside' => 1,
        ]));

        $response->assertOk();
        $response->assertSee($insideVisitor->name);
        $response->assertDontSee($exitedVisitor->name);
    }

    public function test_staff_cannot_register_visitor_for_other_society_flat(): void
    {
        [$staffSociety] = $this->createSocietyContext('alpha');
        [$otherSociety] = $this->createSocietyContext('beta');

        $staff = $this->createUserWithRole('staff', $staffSociety);
        $otherFlat = $this->createFlatForSociety($otherSociety, 'B-201');

        $response = $this->actingAs($staff)->post(route('visitors.store'), [
            'flat_id' => $otherFlat->id,
            'name' => 'Blocked Guest',
            'phone' => '9876543211',
            'purpose' => 'Visit',
            'entry_time' => now()->toDateTimeString(),
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('visitors', [
            'flat_id' => $otherFlat->id,
            'name' => 'Blocked Guest',
        ]);
    }

    public function test_resident_can_approve_pending_visitor_for_their_own_flat(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-102');
        $resident = $this->createUserWithRole('resident', $society);
        $this->assignResidentToFlat($resident, $flat);

        $visitor = $this->createVisitor($flat);

        $response = $this->actingAs($resident)->post(route('visitors.approve', $visitor));

        $response->assertRedirect();

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'approval_status' => 'approved',
            'approved_by' => $resident->id,
        ]);
    }

    public function test_resident_cannot_approve_visitor_for_another_flat_in_same_society(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $residentFlat = $this->createFlatForSociety($society, 'A-103');
        $otherFlat = $this->createFlatForSociety($society, 'A-104');

        $resident = $this->createUserWithRole('resident', $society);
        $this->assignResidentToFlat($resident, $residentFlat);

        $visitor = $this->createVisitor($otherFlat);

        $response = $this->actingAs($resident)->post(route('visitors.approve', $visitor));

        $response->assertForbidden();

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'approval_status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_society_admin_cannot_view_cross_society_visitor_details(): void
    {
        [$adminSociety] = $this->createSocietyContext('alpha');
        [$otherSociety] = $this->createSocietyContext('beta');

        $admin = $this->createUserWithRole('society-admin', $adminSociety);
        $visitor = $this->createVisitor($this->createFlatForSociety($otherSociety, 'B-301'));

        $response = $this->actingAs($admin)->get(route('visitors.show', $visitor));

        $response->assertForbidden();
    }

    public function test_super_admin_can_view_cross_society_visitor_details(): void
    {
        [$otherSociety] = $this->createSocietyContext('beta');
        $visitor = $this->createVisitor($this->createFlatForSociety($otherSociety, 'B-302'));

        $response = $this->actingAs($this->systemUser)->get(route('visitors.show', $visitor));

        $response->assertOk();
        $response->assertSee($visitor->name);
    }

    public function test_resident_show_page_only_displays_approve_and_reject_for_pending_own_flat_visitor(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-114');
        $resident = $this->createUserWithRole('resident', $society);
        $this->assignResidentToFlat($resident, $flat);
        $visitor = $this->createVisitor($flat, [
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($resident)->get(route('visitors.show', $visitor));

        $response->assertOk();
        $response->assertSee('Approve');
        $response->assertSee('Reject');
        $response->assertDontSee('Record Exit');
    }

    public function test_staff_show_page_displays_exit_only_for_approved_visitor(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $visitor = $this->createVisitor($this->createFlatForSociety($society, 'A-115'), [
            'approval_status' => 'approved',
            'approved_by' => $this->systemUser->id,
            'approved_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($staff)->get(route('visitors.show', $visitor));

        $response->assertOk();
        $response->assertSee('Record Exit');
        $response->assertDontSeeHtml('>Approve<');
        $response->assertDontSeeHtml('>Reject<');
    }

    public function test_visitor_cannot_be_approved_twice(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $flat = $this->createFlatForSociety($society, 'A-105');
        $this->assignResidentToFlat($resident, $flat);

        $visitor = $this->createVisitor($flat, [
            'approval_status' => 'approved',
            'approved_by' => $resident->id,
            'approved_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($resident)->post(route('visitors.approve', $visitor));

        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'approval_status' => 'approved',
        ]);
    }

    public function test_visitor_cannot_be_rejected_after_approval(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $flat = $this->createFlatForSociety($society, 'A-107');
        $this->assignResidentToFlat($resident, $flat);

        $visitor = $this->createVisitor($flat, [
            'approval_status' => 'approved',
            'approved_by' => $resident->id,
            'approved_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($resident)->post(route('visitors.reject', $visitor), [
            'remarks' => 'Too late',
        ]);

        $response->assertSessionHasErrors([
            'error' => 'Only pending visitors can be rejected.',
        ]);

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'approval_status' => 'approved',
        ]);
    }

    public function test_resident_can_reject_pending_visitor_with_remarks(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-116');
        $resident = $this->createUserWithRole('resident', $society);
        $this->assignResidentToFlat($resident, $flat);
        $visitor = $this->createVisitor($flat, [
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($resident)->post(route('visitors.reject', $visitor), [
            'remarks' => 'Resident not available to receive guest.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'approval_status' => 'rejected',
            'approved_by' => $resident->id,
            'remarks' => 'Resident not available to receive guest.',
        ]);
    }

    public function test_exit_cannot_be_recorded_before_approval(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $flat = $this->createFlatForSociety($society, 'A-108');

        $visitor = $this->createVisitor($flat, [
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($staff)->post(route('visitors.exit', $visitor));

        $response->assertSessionHasErrors([
            'error' => 'Exit can only be recorded for an approved visitor.',
        ]);

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'exit_time' => null,
            'approval_status' => 'pending',
        ]);
    }

    public function test_exit_can_only_be_recorded_once_for_approved_visitor(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $flat = $this->createFlatForSociety($society, 'A-106');

        $visitor = $this->createVisitor($flat, [
            'approval_status' => 'approved',
            'approved_by' => $staff->id,
            'approved_at' => now()->subMinutes(5),
        ]);

        $firstResponse = $this->actingAs($staff)->post(route('visitors.exit', $visitor));
        $firstResponse->assertRedirect();

        $visitor->refresh();
        $this->assertNotNull($visitor->exit_time);

        $secondResponse = $this->actingAs($staff)->post(route('visitors.exit', $visitor));
        $secondResponse->assertSessionHasErrors('error');
    }

    public function test_database_blocks_pending_visitor_with_approval_metadata(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-109');

        $this->expectException(QueryException::class);

        DB::table('visitors')->insert([
            'flat_id' => $flat->id,
            'name' => 'Invalid Pending',
            'phone' => '9876543220',
            'purpose' => 'Bypass',
            'entry_time' => now(),
            'approval_status' => 'pending',
            'approved_by' => $this->systemUser->id,
            'approved_at' => now(),
            'remarks' => null,
            'created_by' => $this->systemUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_blocks_exit_time_for_non_approved_visitor(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-111');

        $this->expectException(QueryException::class);

        DB::table('visitors')->insert([
            'flat_id' => $flat->id,
            'name' => 'Invalid Exit',
            'phone' => '9876543221',
            'purpose' => 'Bypass',
            'entry_time' => now(),
            'exit_time' => now(),
            'approval_status' => 'rejected',
            'approved_by' => $this->systemUser->id,
            'approved_at' => now(),
            'remarks' => null,
            'created_by' => $this->systemUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        $tower = $society->towers()->first()
            ?? Tower::create([
                'society_id' => $society->id,
                'name' => strtoupper(substr($flatNumber, 0, 1)) . '-T1',
                'total_floors' => 10,
                'is_active' => true,
                'created_by' => $this->systemUser->id,
            ]);

        return Flat::create([
            'tower_id' => $tower->id,
            'flat_number' => $flatNumber,
            'floor_number' => 1,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
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

    private function createVisitor(Flat $flat, array $overrides = []): Visitor
    {
        return Visitor::create(array_merge([
            'flat_id' => $flat->id,
            'name' => 'Visitor ' . fake()->firstName(),
            'phone' => fake()->numerify('9#########'),
            'purpose' => 'Guest Visit',
            'entry_time' => now()->subHour(),
            'remarks' => null,
            'created_by' => $this->systemUser->id,
        ], $overrides));
    }

    private function assignResidentToFlat(User $resident, Flat $flat): void
    {
        $resident->flats()->attach($flat->id, [
            'relation_type' => 'owner',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => true,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
