<?php

namespace Tests\Feature\Maintenance;

use App\Models\Flat;
use App\Models\Maintenance;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceRoleMatrixTest extends TestCase
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

    public function test_super_admin_can_view_any_maintenance(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-101');
        $maintenance = $this->createMaintenance($flat);

        $response = $this->actingAs($this->systemUser)->get(route('maintenance.show', $maintenance));

        $response->assertOk();
    }

    public function test_super_admin_can_access_maintenance_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');

        $response = $this->actingAs($this->systemUser)->get(route('maintenance.create'));

        $response->assertOk();
    }

    public function test_super_admin_can_view_maintenance_list(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-101');
        $maintenance = $this->createMaintenance($flat);

        $response = $this->actingAs($this->systemUser)->get(route('maintenance.index'));

        $response->assertOk();
    }

    public function test_society_admin_can_view_maintenance_within_society(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $admin = $this->createUserWithRole('society-admin', $society);
        $flat = $this->createFlatForSociety($society, 'A-101');
        $maintenance = $this->createMaintenance($flat);

        $response = $this->actingAs($admin)->get(route('maintenance.show', $maintenance));

        $response->assertOk();
    }

    public function test_society_admin_cannot_view_maintenance_of_other_society(): void
    {
        [$societyA] = $this->createSocietyContext('alpha');
        [$societyB] = $this->createSocietyContext('beta');

        $admin = $this->createUserWithRole('society-admin', $societyA);
        $flatB = $this->createFlatForSociety($societyB, 'B-101');
        $maintenanceB = $this->createMaintenance($flatB);

        $response = $this->actingAs($admin)->get(route('maintenance.show', $maintenanceB));

        $response->assertForbidden();
    }

    public function test_staff_cannot_view_maintenance(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $flat = $this->createFlatForSociety($society, 'A-101');
        $maintenance = $this->createMaintenance($flat);

        $response = $this->actingAs($staff)->get(route('maintenance.show', $maintenance));

        $response->assertForbidden();
    }

    public function test_staff_cannot_access_maintenance_list(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);

        $response = $this->actingAs($staff)->get(route('maintenance.index'));

        $response->assertForbidden();
    }

    public function test_resident_cannot_view_maintenance(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-101');
        $resident = $this->createUserWithRole('resident', $society);
        $maintenance = $this->createMaintenance($flat);

        $response = $this->actingAs($resident)->get(route('maintenance.show', $maintenance));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_maintenance(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $flat = $this->createFlatForSociety($society, 'A-101');
        $maintenance = $this->createMaintenance($flat);

        $response = $this->get(route('maintenance.show', $maintenance));

        $response->assertRedirect();
    }

    public function test_cross_society_access_blocked_for_maintenance_list(): void
    {
        [$societyA] = $this->createSocietyContext('alpha');
        [$societyB] = $this->createSocietyContext('beta');

        $adminA = $this->createUserWithRole('society-admin', $societyA);

        $response = $this->actingAs($adminA)->get(route('maintenance.index'));

        $response->assertOk();
    }

    protected function createMaintenance(Flat $flat): Maintenance
    {
        return Maintenance::create([
            'flat_id' => $flat->id,
            'month' => '2026-05',
            'amount' => 5000,
            'amount_paid' => 0,
            'due_date' => '2026-06-01',
            'status' => 'unpaid',
            'created_by' => $this->systemUser->id,
        ]);
    }

    protected function createUserWithRole(string $role, Society $society, ?Flat $flat = null): User
    {
        $user = User::factory()->create([
            'society_id' => $society->id,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $this->assignRole($user, $role);

        return $user;
    }

    protected function createFlatForSociety(Society $society, string $flatNumber): Flat
    {
        $tower = $society->towers()->first()
            ?? Tower::create([
                'society_id' => $society->id,
                'name' => 'A',
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

    protected function createSocietyContext(string $prefix): array
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

        return [$society];
    }

    protected function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}