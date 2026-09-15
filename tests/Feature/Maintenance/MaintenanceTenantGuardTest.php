<?php

namespace Tests\Feature\Maintenance;

use App\Models\Flat;
use App\Models\Maintenance;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTenantGuardTest extends TestCase
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

    public function test_non_tenant_user_with_maintenance_view_permission_cannot_view_maintenance_detail(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-401')
        );

        $auditor = $this->createPermissionOnlyUser('maintenance-auditor', 'maintenance.view');

        $response = $this->actingAs($auditor)->get(route('maintenance.show', $maintenance));

        $response->assertForbidden();
    }

    public function test_non_tenant_user_with_maintenance_update_permission_cannot_record_payment(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $maintenance = $this->createMaintenance(
            $this->createFlatForSociety($society, 'A-402')
        );

        $cashier = $this->createPermissionOnlyUser('maintenance-cashier', 'maintenance.update');

        $response = $this->actingAs($cashier)->post(route('maintenance.process-payment', $maintenance), [
            'amount' => 500,
            'payment_mode' => 'cash',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('maintenances', [
            'id' => $maintenance->id,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseCount('maintenance_payments', 0);
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
            'floor_number' => 4,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }

    private function createMaintenance(Flat $flat): Maintenance
    {
        return Maintenance::create([
            'flat_id' => $flat->id,
            'month' => '2026-05',
            'amount' => 2000,
            'amount_paid' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
            'late_fee' => 0,
            'status' => 'unpaid',
            'created_by' => $this->systemUser->id,
        ]);
    }

    private function createPermissionOnlyUser(string $roleSlug, string $permissionSlug): User
    {
        $role = Role::create([
            'name' => ucwords(str_replace('-', ' ', $roleSlug)),
            'slug' => $roleSlug,
            'description' => 'Test role',
        ]);

        $permission = Permission::where('slug', $permissionSlug)->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create([
            'society_id' => null,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
