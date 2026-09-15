<?php

namespace Tests\Feature\Authorization;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Gate::before grants permission-slug checks so Blade can ask @can('vehicles.create').
 *
 * It previously did so for ANY ability whose name matched a slug — including calls that
 * carried a model, which short-circuited the policy and skipped its tenant rules. That
 * was safe only by naming coincidence: no policy ability happened to be called
 * 'notices.update'. These tests pin the boundary rather than the coincidence.
 */
class GateBeforeTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_argument_less_slug_checks_still_work(): void
    {
        [$society] = $this->createSocietyContext('gate');
        $resident = $this->createUserWithRole('resident', $society);

        $this->assertTrue(Gate::forUser($resident)->allows('complaints.create'));
        $this->assertFalse(Gate::forUser($resident)->allows('societies.delete'));
    }

    /**
     * The regression this task exists for: a slug-named ability carrying a model must
     * reach the policy, not be waved through by the permission lookup.
     */
    public function test_a_slug_named_ability_with_a_model_does_not_bypass_the_policy(): void
    {
        [$ownSociety] = $this->createSocietyContext('alpha');
        [$otherSociety] = $this->createSocietyContext('beta');

        $admin = $this->createUserWithRole('society-admin', $ownSociety);
        $foreignNotice = $this->makeNotice($otherSociety->id);

        $this->assertTrue(
            $admin->hasPermission('notices.update'),
            'Precondition: the society admin holds the notices.update permission.'
        );

        // Same name as the permission slug, but carrying a model — Gate::before must
        // stand aside so NoticePolicy's society check decides.
        $this->assertFalse(
            Gate::forUser($admin)->allows('notices.update', $foreignNotice),
            'A slug-named ability with a model argument must be decided by the policy.'
        );
    }

    public function test_policy_still_denies_cross_society_updates(): void
    {
        [$ownSociety] = $this->createSocietyContext('gamma');
        [$otherSociety] = $this->createSocietyContext('delta');

        $admin = $this->createUserWithRole('society-admin', $ownSociety);

        $this->assertTrue(Gate::forUser($admin)->allows('update', $this->makeNotice($ownSociety->id)));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $this->makeNotice($otherSociety->id)));
    }

    public function test_super_admin_still_bypasses_everything(): void
    {
        [$society] = $this->createSocietyContext('omega');

        $this->assertTrue(Gate::forUser($this->systemUser)->allows('anything.at.all'));
        $this->assertTrue(Gate::forUser($this->systemUser)->allows('update', $this->makeNotice($society->id)));
    }

    private function makeNotice(int $societyId): Notice
    {
        return Notice::create([
            'society_id' => $societyId,
            'title' => 'Notice ' . fake()->unique()->numerify('####'),
            'content' => 'Body',
            'category' => 'general',
            'priority' => 'normal',
            'status' => 'published',
            'visibility' => 'public',
            'is_pinned' => false,
            'published_at' => now(),
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }
}
