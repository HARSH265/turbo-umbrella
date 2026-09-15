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

class ComplaintRoleMatrixTest extends TestCase
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

    public function test_society_admin_can_view_same_society_complaint(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $admin = $this->createUserWithRole('society-admin', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-301'));

        $response = $this->actingAs($admin)->get(route('complaints.show', $complaint));

        $response->assertOk();
        $response->assertSee($complaint->ticket_number);
    }

    public function test_society_admin_cannot_view_cross_society_complaint(): void
    {
        [$adminSociety] = $this->createSocietyContext('alpha');
        [$otherSociety] = $this->createSocietyContext('beta');
        $admin = $this->createUserWithRole('society-admin', $adminSociety);
        $resident = $this->createUserWithRole('resident', $otherSociety);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($otherSociety, 'B-301'));

        $response = $this->actingAs($admin)->get(route('complaints.show', $complaint));

        $response->assertForbidden();
    }

    public function test_society_admin_can_assign_same_society_complaint_to_same_society_staff(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $admin = $this->createUserWithRole('society-admin', $society);
        $resident = $this->createUserWithRole('resident', $society);
        $staff = $this->createUserWithRole('staff', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-302'));

        $response = $this->actingAs($admin)->post(route('complaints.assign', $complaint), [
            'assigned_to' => $staff->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'assigned_to' => $staff->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_society_admin_cannot_assign_cross_society_complaint(): void
    {
        [$adminSociety] = $this->createSocietyContext('alpha');
        [$otherSociety] = $this->createSocietyContext('beta');
        $admin = $this->createUserWithRole('society-admin', $adminSociety);
        $resident = $this->createUserWithRole('resident', $otherSociety);
        $staff = $this->createUserWithRole('staff', $otherSociety);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($otherSociety, 'B-302'));

        $response = $this->actingAs($admin)->post(route('complaints.assign', $complaint), [
            'assigned_to' => $staff->id,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'assigned_to' => null,
            'status' => 'open',
        ]);
    }

    public function test_staff_can_resolve_assigned_complaint(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $staff = $this->createUserWithRole('staff', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-303'), [
            'assigned_to' => $staff->id,
            'assigned_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($staff)->post(route('complaints.resolve', $complaint), [
            'resolution_note' => 'Leak repaired and pipeline replaced.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolution_note' => 'Leak repaired and pipeline replaced.',
        ]);
    }

    public function test_unassigned_staff_cannot_resolve_complaint(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $assignedStaff = $this->createUserWithRole('staff', $society);
        $otherStaff = $this->createUserWithRole('staff', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-304'), [
            'assigned_to' => $assignedStaff->id,
            'assigned_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($otherStaff)->post(route('complaints.resolve', $complaint), [
            'resolution_note' => 'Attempted unauthorized resolve.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'in_progress',
            'resolution_note' => null,
        ]);
    }

    public function test_society_admin_can_close_resolved_same_society_complaint(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $admin = $this->createUserWithRole('society-admin', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-305'), [
            'status' => 'resolved',
            'resolved_at' => now()->subHour(),
            'resolution_note' => 'Resolved already',
        ]);

        $response = $this->actingAs($admin)->post(route('complaints.status', $complaint), [
            'status' => 'closed',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'closed',
        ]);
    }

    public function test_resident_can_dispute_own_resolved_complaint(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $complaint = $this->createComplaint($resident, $this->createFlatForSociety($society, 'A-306'), [
            'status' => 'resolved',
            'resolved_at' => now()->subHour(),
            'resolution_note' => 'Marked fixed',
        ]);

        $response = $this->actingAs($resident)->post(route('complaints.dispute', $complaint), [
            'reason' => 'The leak is still there.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'disputed',
        ]);
    }

    private function createSocietyContext(string $prefix): array
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

        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => strtoupper($prefix) . '-T1',
            'total_floors' => 10,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        return [$society, $tower];
    }

    private function createFlatForSociety(Society $society, string $flatNumber): Flat
    {
        return Flat::create([
            'tower_id' => $society->towers()->first()->id,
            'flat_number' => $flatNumber,
            'floor_number' => 3,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }

    private function createUserWithRole(string $roleSlug, ?Society $society): User
    {
        $user = User::factory()->create([
            'society_id' => $society?->id,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $this->assignRole($user, $roleSlug);

        return $user;
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

    private function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
