<?php

namespace Tests\Feature\Users;

use App\Models\Role;
use App\Models\Society;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleMatrixTest extends TestCase
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

    public function test_super_admin_can_access_user_create_page(): void
    {
        $response = $this->actingAs($this->systemUser)->get(route('users.create'));

        $response->assertOk();
    }

    public function test_super_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->systemUser)->get(route('users.index'));

        $response->assertOk();
    }

    public function test_society_admin_can_access_user_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $admin = $this->createUserWithRole('society-admin', $society);

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
    }

    public function test_society_admin_can_view_users_in_society(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $admin = $this->createUserWithRole('society-admin', $society);
        $user = $this->createUserWithRole('resident', $society);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertOk();
    }

    public function test_society_admin_cannot_view_user_of_other_society(): void
    {
        [$societyA] = $this->createSocietyContext('alpha');
        [$societyB] = $this->createSocietyContext('beta');

        $adminA = $this->createUserWithRole('society-admin', $societyA);
        $userB = $this->createUserWithRole('resident', $societyB);

        $response = $this->actingAs($adminA)->get(route('users.show', $userB));

        $response->assertForbidden();
    }

    public function test_staff_cannot_view_users_list(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);

        $response = $this->actingAs($staff)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_staff_cannot_access_user_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);

        $response = $this->actingAs($staff)->get(route('users.create'));

        $response->assertForbidden();
    }

    public function test_resident_can_access_profile_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);

        $response = $this->actingAs($resident)->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_resident_cannot_access_user_list(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);

        $response = $this->actingAs($resident)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_resident_cannot_access_user_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);

        $response = $this->actingAs($resident)->get(route('users.create'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_users(): void
    {
        $response = $this->get(route('users.index'));

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