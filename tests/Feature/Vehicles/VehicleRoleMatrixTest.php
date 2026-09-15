<?php

namespace Tests\Feature\Vehicles;

use App\Models\Flat;
use App\Models\Society;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vehicles had no test coverage at all, which is why the wherePivot()-inside-whereHas()
 * crash on the resident listing shipped unnoticed.
 */
class VehicleRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

    private Society $otherSociety;

    private Flat $flat;

    private Flat $otherFlat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);

        $this->systemUser = User::factory()->create([
            'created_by' => null, 'updated_by' => null, 'society_id' => null,
        ]);
        $this->assignRole($this->systemUser, 'super-admin');

        [$this->society] = $this->createSocietyContext('veh');
        [$this->otherSociety] = $this->createSocietyContext('oth');

        $this->flat = $this->createFlatForSociety($this->society, 'V-101');
        $this->otherFlat = $this->createFlatForSociety($this->otherSociety, 'X-101');
    }

    public function test_resident_listing_renders_and_shows_only_their_own_flats_vehicles(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);

        $mine = $this->makeVehicle($this->flat, $this->society, 'GJ01MY0001');
        $neighbours = $this->makeVehicle(
            $this->createFlatForSociety($this->society, 'V-102'),
            $this->society,
            'GJ01NB0001'
        );

        $response = $this->actingAs($resident)->get(route('vehicles.index'));

        $response->assertOk();
        $response->assertSee($mine->registration_number);
        $response->assertDontSee($neighbours->registration_number);
    }

    public function test_society_admin_sees_every_vehicle_in_their_society_only(): void
    {
        $admin = $this->createUserWithRole('society-admin', $this->society);

        $inSociety = $this->makeVehicle($this->flat, $this->society, 'GJ01IN0001');
        $outside = $this->makeVehicle($this->otherFlat, $this->otherSociety, 'GJ01OUT001');

        $response = $this->actingAs($admin)->get(route('vehicles.index'));

        $response->assertOk();
        $response->assertSee($inSociety->registration_number);
        $response->assertDontSee($outside->registration_number);
    }

    public function test_society_admin_cannot_view_a_vehicle_from_another_society(): void
    {
        $admin = $this->createUserWithRole('society-admin', $this->society);
        $outside = $this->makeVehicle($this->otherFlat, $this->otherSociety, 'GJ01FRN001');

        $this->actingAs($admin)->get(route('vehicles.show', $outside))->assertForbidden();
    }

    public function test_super_admin_sees_vehicles_across_societies(): void
    {
        $a = $this->makeVehicle($this->flat, $this->society, 'GJ01AA0001');
        $b = $this->makeVehicle($this->otherFlat, $this->otherSociety, 'GJ01BB0001');

        $response = $this->actingAs($this->systemUser)->get(route('vehicles.index'));

        $response->assertOk();
        $response->assertSee($a->registration_number);
        $response->assertSee($b->registration_number);
    }

    public function test_staff_can_register_a_vehicle_but_residents_cannot_delete_one(): void
    {
        $staff = $this->createUserWithRole('staff', $this->society);
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);

        $this->assertTrue($staff->hasPermission('vehicles.create'));
        $this->assertFalse($resident->hasPermission('vehicles.delete'));

        $vehicle = $this->makeVehicle($this->flat, $this->society, 'GJ01DL0001');

        $this->actingAs($resident)->delete(route('vehicles.destroy', $vehicle))->assertForbidden();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }

    public function test_registration_numbers_must_be_unique(): void
    {
        $admin = $this->createUserWithRole('society-admin', $this->society);
        $this->makeVehicle($this->flat, $this->society, 'GJ01UQ0001');

        $this->actingAs($admin)
            ->post(route('vehicles.store'), [
                'flat_id' => $this->flat->id,
                'registration_number' => 'GJ01UQ0001',
                'vehicle_type' => 'car',
            ])
            ->assertSessionHasErrors('registration_number');
    }

    private function makeVehicle(Flat $flat, Society $society, string $registration): Vehicle
    {
        return Vehicle::create([
            'flat_id' => $flat->id,
            'society_id' => $society->id,
            'registration_number' => $registration,
            'vehicle_type' => 'car',
            'make' => 'Tata',
            'model' => 'Nexon',
            'color' => 'Blue',
            'created_by' => $this->systemUser->id,
        ]);
    }
}
