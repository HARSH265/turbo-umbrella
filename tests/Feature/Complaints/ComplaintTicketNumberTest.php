<?php

namespace Tests\Feature\Complaints;

use App\Models\Complaint;
use App\Models\Flat;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for duplicate ticket numbers.
 *
 * The original generator derived the daily sequence from created_at:
 *
 *     $last = self::whereDate('created_at', now())->latest('id')->first();
 *     $seq  = $last ? (int) substr($last->ticket_number, -4) + 1 : 1;
 *
 * That broke in three ways, and produced a real failure in storage/logs/laravel.log:
 * "Duplicate entry 'CMP-20260913-0001' for key 'complaints_ticket_number_unique'".
 */
class ComplaintTicketNumberTest extends TestCase
{
    use RefreshDatabase;

    private Flat $flat;

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
            'created_by' => null,
            'updated_by' => null,
            'society_id' => null,
        ]);
        $this->assignRole($this->systemUser, 'super-admin');

        $society = Society::create([
            'name' => 'Ticket Society',
            'code' => 'TKT-' . fake()->unique()->numerify('###'),
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
            'name' => 'TKT-T1',
            'total_floors' => 5,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        $this->flat = Flat::create([
            'tower_id' => $tower->id,
            'flat_number' => 'T-101',
            'floor_number' => 1,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        $this->resident = User::factory()->create([
            'society_id' => $society->id,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);
        $this->assignRole($this->resident, 'resident');
    }

    public function test_sequence_increments_within_the_same_day(): void
    {
        $first = $this->createComplaint();
        $second = $this->createComplaint();

        $prefix = 'CMP-' . now()->format('Ymd') . '-';

        $this->assertSame($prefix . '0001', $first->ticket_number);
        $this->assertSame($prefix . '0002', $second->ticket_number);
    }

    /**
     * The exact shape that produced the logged duplicate: a row whose ticket_number
     * carries today's date while its created_at sits on an earlier day, which is how
     * DemoDataSeeder writes history. A created_at-based lookup found nothing and
     * restarted the sequence at 0001, colliding with the existing row.
     */
    public function test_backdated_created_at_does_not_restart_the_sequence(): void
    {
        $prefix = 'CMP-' . now()->format('Ymd') . '-';

        $existing = $this->createComplaint();
        $this->assertSame($prefix . '0001', $existing->ticket_number);

        $existing->forceFill(['created_at' => now()->subDays(4)])->saveQuietly();

        $next = $this->createComplaint();

        $this->assertSame($prefix . '0002', $next->ticket_number);
    }

    /**
     * Soft-deleted complaints keep occupying the unique index, so they still have to
     * be counted when choosing the next sequence.
     */
    public function test_soft_deleted_complaints_do_not_free_their_ticket_number(): void
    {
        $prefix = 'CMP-' . now()->format('Ymd') . '-';

        $first = $this->createComplaint();
        $first->delete();

        $this->assertSoftDeleted('complaints', ['id' => $first->id]);

        $next = $this->createComplaint();

        $this->assertSame($prefix . '0002', $next->ticket_number);
        $this->assertNotSame($first->ticket_number, $next->ticket_number);
    }

    /**
     * substr($ticket, -4) capped the sequence at 9999 and then read the wrong digits.
     */
    public function test_sequence_continues_past_four_digits(): void
    {
        $prefix = 'CMP-' . now()->format('Ymd') . '-';

        $this->createComplaint(['ticket_number' => $prefix . '9999']);

        $next = $this->createComplaint();

        $this->assertSame($prefix . '10000', $next->ticket_number);
    }

    public function test_ticket_numbers_stay_unique_across_many_complaints(): void
    {
        $tickets = collect(range(1, 25))->map(fn () => $this->createComplaint()->ticket_number);

        $this->assertCount(25, $tickets->unique(), 'Ticket numbers must be unique.');
    }

    private function createComplaint(array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'user_id' => $this->resident->id,
            'flat_id' => $this->flat->id,
            'category' => 'plumbing',
            'subject' => 'Water leakage',
            'description' => 'Water is leaking from the kitchen pipe.',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => $this->resident->id,
        ], $overrides));
    }

}
