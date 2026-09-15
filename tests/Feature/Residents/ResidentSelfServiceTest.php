<?php

namespace Tests\Feature\Residents;

use App\Models\Flat;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Residents were granted visitors.create and vehicles.create so they can pre-register
 * guests and add their own vehicles.
 *
 * The permission alone is not enough: the flat-scoping checks previously only verified
 * society membership, which would have let a resident log a visitor or register a
 * vehicle against a NEIGHBOUR's flat. These tests pin both halves — the capability and
 * its limit.
 */
class ResidentSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

    private Flat $ownFlat;

    private Flat $neighbourFlat;

    private User $resident;

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

        $this->society = Society::create([
            'name' => 'Self Service Society',
            'code' => 'SSS-' . fake()->unique()->numerify('###'),
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
            'society_id' => $this->society->id,
            'name' => 'SSS-T1',
            'total_floors' => 5,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        $this->ownFlat = $this->makeFlat($tower, 'S-101');
        $this->neighbourFlat = $this->makeFlat($tower, 'S-102');

        $this->resident = User::factory()->create([
            'society_id' => $this->society->id,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);
        $this->assignRole($this->resident, 'resident');

        $this->ownFlat->residents()->attach($this->resident->id, [
            'relation_type' => 'owner',
            'start_date' => now()->subYear(),
            'is_primary' => true,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }

    public function test_resident_has_the_new_self_service_permissions(): void
    {
        $this->assertTrue($this->resident->hasPermission('visitors.create'));
        $this->assertTrue($this->resident->hasPermission('vehicles.create'));
    }

    public function test_resident_can_pre_register_a_visitor_for_their_own_flat(): void
    {
        $this->actingAs($this->resident)
            ->post(route('visitors.store'), $this->visitorPayload($this->ownFlat))
            ->assertRedirect(route('visitors.index'));

        $this->assertDatabaseHas('visitors', [
            'flat_id' => $this->ownFlat->id,
            'name' => 'Expected Guest',
            'created_by' => $this->resident->id,
        ]);
    }

    public function test_resident_cannot_register_a_visitor_for_a_neighbours_flat(): void
    {
        $this->actingAs($this->resident)
            ->post(route('visitors.store'), $this->visitorPayload($this->neighbourFlat))
            ->assertForbidden();

        $this->assertDatabaseMissing('visitors', ['flat_id' => $this->neighbourFlat->id]);
    }

    public function test_resident_can_register_a_vehicle_to_their_own_flat(): void
    {
        $this->actingAs($this->resident)
            ->post(route('vehicles.store'), [
                'flat_id' => $this->ownFlat->id,
                'registration_number' => 'GJ01ZZ0001',
                'vehicle_type' => 'car',
                'make' => 'Tata',
                'model' => 'Nexon',
                'color' => 'Blue',
            ])->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'flat_id' => $this->ownFlat->id,
            'registration_number' => 'GJ01ZZ0001',
        ]);
    }

    public function test_resident_cannot_register_a_vehicle_to_a_neighbours_flat(): void
    {
        $this->actingAs($this->resident)
            ->post(route('vehicles.store'), [
                'flat_id' => $this->neighbourFlat->id,
                'registration_number' => 'GJ01ZZ0002',
                'vehicle_type' => 'car',
            ])->assertNotFound();

        $this->assertDatabaseMissing('vehicles', ['registration_number' => 'GJ01ZZ0002']);
    }

    public function test_vehicle_flat_dropdown_only_offers_the_residents_own_flats(): void
    {
        $response = $this->actingAs($this->resident)->get(route('vehicles.create'));

        $response->assertOk();
        $response->assertSee('S-101');
        $response->assertDontSee('S-102');
    }

    private function makeFlat(Tower $tower, string $number): Flat
    {
        return Flat::create([
            'tower_id' => $tower->id,
            'flat_number' => $number,
            'floor_number' => 1,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }

    private function visitorPayload(Flat $flat): array
    {
        return [
            'flat_id' => $flat->id,
            'name' => 'Expected Guest',
            'phone' => '9876543210',
            'purpose' => 'Family visit',
            'entry_time' => now()->format('Y-m-d H:i:s'),
        ];
    }

}
