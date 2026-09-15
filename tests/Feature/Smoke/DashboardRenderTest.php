<?php

namespace Tests\Feature\Smoke;

use App\Models\Complaint;
use App\Models\Flat;
use App\Models\Society;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The staff dashboard returned 500 for every staff user because staff.blade.php passed
 * a ComplaintPriority enum to ucfirst(). Sixty-one role-matrix tests never caught it —
 * none of them rendered a dashboard.
 *
 * Each role must therefore actually render /dashboard, and with a complaint present so
 * the priority/status badge branches execute rather than being skipped by an empty loop.
 */
class DashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

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

        [$this->society] = $this->createSocietyContext('dash');
        $this->flat = $this->createFlatForSociety($this->society, 'D-101');
    }

    public static function roleProvider(): array
    {
        return [
            'super admin' => ['super-admin'],
            'society admin' => ['society-admin'],
            'resident' => ['resident'],
            'staff' => ['staff'],
        ];
    }

    #[DataProvider('roleProvider')]
    public function test_dashboard_renders_for_role(string $role): void
    {
        $user = $this->createUserWithRole($role, $role === 'super-admin' ? null : $this->society);
        $this->attachResidentToFlat($user, $this->flat);

        // A complaint assigned to this user, so both the resident panel and the staff
        // "assigned complaints" table have a row and render their badges.
        $this->makeComplaint($user, ['assigned_to' => $user->id, 'status' => 'in_progress']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    /**
     * Every priority and status must survive rendering — the original crash only
     * happened once a row existed with an enum-cast value.
     */
    #[DataProvider('roleProvider')]
    public function test_dashboard_renders_every_priority_and_status(string $role): void
    {
        $user = $this->createUserWithRole($role, $role === 'super-admin' ? null : $this->society);
        $this->attachResidentToFlat($user, $this->flat);

        foreach (['low', 'medium', 'high', 'urgent'] as $i => $priority) {
            $this->makeComplaint($user, [
                'priority' => $priority,
                'status' => ['open', 'in_progress', 'resolved', 'disputed'][$i],
                'assigned_to' => $user->id,
            ]);
        }

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    private function makeComplaint(User $user, array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'user_id' => $user->id,
            'flat_id' => $this->flat->id,
            'category' => 'plumbing',
            'subject' => 'Leak ' . fake()->unique()->numerify('####'),
            'description' => 'Water is leaking from the kitchen pipe.',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => $user->id,
        ], $overrides));
    }
}
