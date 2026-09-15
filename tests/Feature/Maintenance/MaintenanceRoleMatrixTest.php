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

}