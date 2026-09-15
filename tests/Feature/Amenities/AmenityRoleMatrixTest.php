<?php

namespace Tests\Feature\Amenities;

use App\Models\Amenity;
use App\Models\Flat;
use App\Models\Society;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Amenities had no test coverage, which is how a raw DB::table()->insertGetId() in the
 * store path shipped — it bypassed Eloquent and left created_at/updated_at NULL on
 * every amenity created through the UI.
 */
class AmenityRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

    private Society $otherSociety;

    private Flat $flat;

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

        [$this->society] = $this->createSocietyContext('amn');
        [$this->otherSociety] = $this->createSocietyContext('alt');

        $this->flat = $this->createFlatForSociety($this->society, 'A-101');
    }

    public function test_society_admin_creating_an_amenity_persists_timestamps(): void
    {
        $admin = $this->createUserWithRole('society-admin', $this->society);

        $this->actingAs($admin)
            ->post(route('amenities.store'), $this->payload())
            ->assertRedirect(route('amenities.index'));

        $amenity = Amenity::where('name', 'Test Clubhouse')->firstOrFail();

        $this->assertSame($this->society->id, $amenity->society_id);
        $this->assertNotNull($amenity->created_at, 'created_at must be set — the store path must go through Eloquent.');
        $this->assertNotNull($amenity->updated_at);
    }

    public function test_residents_cannot_create_amenities(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);

        $this->actingAs($resident)
            ->post(route('amenities.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('amenities', ['name' => 'Test Clubhouse']);
    }

    public function test_residents_can_browse_amenities_in_their_society(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);

        $mine = $this->makeAmenity($this->society, 'Society Pool');
        $theirs = $this->makeAmenity($this->otherSociety, 'Foreign Pool');

        $response = $this->actingAs($resident)->get(route('amenities.index'));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    public function test_resident_cannot_open_an_amenity_from_another_society(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);
        $foreign = $this->makeAmenity($this->otherSociety, 'Foreign Gym');

        $this->actingAs($resident)->get(route('amenities.show', $foreign))->assertForbidden();
    }

    public function test_resident_can_open_the_booking_form_for_their_own_flat(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);
        $amenity = $this->makeAmenity($this->society, 'Bookable Hall');

        $response = $this->actingAs($resident)->get(route('amenities.book', $amenity));

        $response->assertOk();
        $response->assertSee('A-101');
    }

    public function test_my_bookings_page_renders_for_a_resident(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);

        $this->actingAs($resident)->get(route('amenities.my-bookings'))->assertOk();
    }

    private function makeAmenity(Society $society, string $name): Amenity
    {
        return Amenity::create(array_merge($this->payload(), [
            'society_id' => $society->id,
            'name' => $name,
        ]));
    }

    private function payload(): array
    {
        return [
            'name' => 'Test Clubhouse',
            'description' => 'A hall for events',
            'type' => 'clubhouse',
            'capacity' => 50,
            'opening_time' => '09:00',
            'closing_time' => '21:00',
            'booking_duration' => 60,
            'advance_booking_days' => 7,
            'cancellation_hours' => 24,
            'charge_per_hour' => 250,
            'is_active' => true,
        ];
    }
}
