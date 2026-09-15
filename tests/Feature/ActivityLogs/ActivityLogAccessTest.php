<?php

namespace Tests\Feature\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\Society;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Activity logs are the audit trail, and had no coverage. Routes are restricted to
 * admins by middleware; the tests below pin both that restriction and the fact that
 * the pages actually render with rows present.
 */
class ActivityLogAccessTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

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

        [$this->society] = $this->createSocietyContext('log');
    }

    public function test_residents_and_staff_are_blocked_from_the_activity_log(): void
    {
        foreach (['resident', 'staff'] as $role) {
            $user = $this->createUserWithRole($role, $this->society);

            $this->actingAs($user)
                ->get(route('activity-logs.index'))
                ->assertForbidden();
        }
    }

    public function test_super_admin_sees_the_activity_log(): void
    {
        $this->makeLog('complaints', 1);

        $this->actingAs($this->systemUser)
            ->get(route('activity-logs.index'))
            ->assertOk();
    }

    public function test_society_admin_sees_the_activity_log(): void
    {
        $admin = $this->createUserWithRole('society-admin', $this->society);
        $this->makeLog('complaints', 1);

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk();
    }

    public function test_entity_history_page_renders(): void
    {
        $this->makeLog('complaints', 7);

        $this->actingAs($this->systemUser)
            ->get(route('activity-logs.show', ['module' => 'complaints', 'entity' => 7]))
            ->assertOk();
    }

    public function test_the_service_records_a_log_row(): void
    {
        $this->actingAs($this->systemUser);

        app(ActivityLogService::class)->log('create', 'complaints', 42);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'complaints',
            'entity_id' => 42,
            'action' => 'create',
        ]);
    }

    private function makeLog(string $module, int $entityId): ActivityLog
    {
        $this->actingAs($this->systemUser);

        app(ActivityLogService::class)->log('update', $module, $entityId);

        return ActivityLog::where('module', $module)
            ->where('entity_id', $entityId)
            ->latest('id')
            ->firstOrFail();
    }
}
