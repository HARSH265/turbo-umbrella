<?php

namespace Tests\Feature\Notifications;

use App\Models\Role;
use App\Models\Society;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRoleMatrixTest extends TestCase
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

    public function test_super_admin_can_view_notifications(): void
    {
        $response = $this->actingAs($this->systemUser)->get(route('notifications.index'));

        $response->assertOk();
    }

    public function test_society_admin_can_view_notifications(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $admin = $this->createUserWithRole('society-admin', $society);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_notifications(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);

        $response = $this->actingAs($staff)->get(route('notifications.index'));

        $response->assertOk();
    }

    public function test_resident_can_view_notifications(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);

        $response = $this->actingAs($resident)->get(route('notifications.index'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect();
    }

    protected function createUserWithRole(string $role, Society $society): User
    {
        $user = User::factory()->create([
            'society_id' => $society->id,
            'created_by' => $this->systemUser->id,
            'updated_by' => $this->systemUser->id,
        ]);

        $this->assignRole($user, $role);

        return $user;
    }

    protected function createSocietyContext(string $prefix): array
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

        return [$society];
    }

    protected function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}