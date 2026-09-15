<?php

namespace Tests\Feature\Complaints;

use App\Models\Complaint;
use App\Models\Flat;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintWorkflowGuardTest extends TestCase
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

    public function test_staff_cannot_mark_complaint_resolved_through_manual_status_route(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $staff = $this->createUserWithRole('staff', $society);
        $flat = $this->createFlatForSociety($society, 'A-201');

        $complaint = $this->createComplaint($resident, $flat, [
            'assigned_to' => $staff->id,
            'assigned_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($staff)->post(route('complaints.status', $complaint), [
            'status' => 'resolved',
        ]);

        $response->assertSessionHasErrors('status');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'in_progress',
            'resolved_at' => null,
            'resolution_note' => null,
        ]);
    }

    public function test_society_admin_cannot_reopen_resolved_complaint_through_manual_status_route(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $admin = $this->createUserWithRole('society-admin', $society);
        $flat = $this->createFlatForSociety($society, 'A-202');

        $complaint = $this->createComplaint($resident, $flat, [
            'status' => 'resolved',
            'resolved_at' => now()->subHour(),
            'resolution_note' => 'Issue fixed',
        ]);

        $response = $this->actingAs($admin)->post(route('complaints.status', $complaint), [
            'status' => 'open',
        ]);

        $response->assertSessionHasErrors([
            'status' => 'Cannot reopen resolved complaints.',
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolution_note' => 'Issue fixed',
        ]);
    }

    public function test_society_admin_cannot_reassign_resolved_complaint_back_to_in_progress(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $admin = $this->createUserWithRole('society-admin', $society);
        $staff = $this->createUserWithRole('staff', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-205'), [
            'status' => 'resolved',
            'resolved_at' => now()->subHour(),
            'resolution_note' => 'Issue fixed',
        ]);

        $response = $this->actingAs($admin)->post(route('complaints.assign', $complaint), [
            'assigned_to' => $staff->id,
        ]);

        $response->assertSessionHasErrors([
            'error' => 'Cannot assign resolved or closed complaints.',
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'assigned_to' => null,
            'resolution_note' => 'Issue fixed',
        ]);
    }

    public function test_staff_detail_page_does_not_show_assignment_controls_without_assign_permission(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $staff = $this->createUserWithRole('staff', $society);
        $flat = $this->createFlatForSociety($society, 'A-203');

        $complaint = $this->createComplaint($resident, $flat, [
            'assigned_to' => $staff->id,
            'assigned_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($staff)->get(route('complaints.show', $complaint));

        $response->assertOk();
        $response->assertDontSee('Update Assignee');
        $response->assertDontSee('Choose Staff...');
        $response->assertSee('Change Status');
        $response->assertSee('Mark as Resolved');
    }

    public function test_resident_does_not_see_dispute_action_for_closed_complaint(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $flat = $this->createFlatForSociety($society, 'A-204');

        $complaint = $this->createComplaint($resident, $flat, [
            'status' => 'closed',
            'resolved_at' => now()->subHours(2),
            'resolution_note' => 'Issue fixed and closed',
        ]);

        $response = $this->actingAs($resident)->get(route('complaints.show', $complaint));

        $response->assertOk();
        $response->assertDontSee('Reopen & Dispute');
    }

    private function createComplaint(User $resident, Flat $flat, array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'user_id' => $resident->id,
            'flat_id' => $flat->id,
            'category' => 'plumbing',
            'subject' => 'Water leakage',
            'description' => 'Water is leaking from the kitchen pipe.',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => $resident->id,
        ], $overrides));
    }

}
