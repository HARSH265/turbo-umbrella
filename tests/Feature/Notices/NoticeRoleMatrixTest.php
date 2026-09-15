<?php

namespace Tests\Feature\Notices;

use App\Models\Notice;
use App\Models\Role;
use App\Models\Society;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeRoleMatrixTest extends TestCase
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

    public function test_super_admin_can_access_notice_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');

        $response = $this->actingAs($this->systemUser)->get(route('notices.create'));

        $response->assertOk();
    }

    public function test_super_admin_can_view_notices_list(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $this->createNotice($society, 'published');

        $response = $this->actingAs($this->systemUser)->get(route('notices.index'));

        $response->assertOk();
    }

    public function test_society_admin_can_access_notice_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $admin = $this->createUserWithRole('society-admin', $society);

        $response = $this->actingAs($admin)->get(route('notices.create'));

        $response->assertOk();
    }

    public function test_staff_cannot_access_notice_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);

        $response = $this->actingAs($staff)->get(route('notices.create'));

        $response->assertForbidden();
    }

    public function test_staff_can_view_notices_list(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $staff = $this->createUserWithRole('staff', $society);
        $this->createNotice($society, 'published');

        $response = $this->actingAs($staff)->get(route('notices.index'));

        $response->assertOk();
    }

    public function test_resident_can_view_noticeboard(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);
        $this->createNotice($society, 'published');

        $response = $this->actingAs($resident)->get(route('notices.noticeboard'));

        $response->assertOk();
    }

    public function test_resident_cannot_access_notice_create_page(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $resident = $this->createUserWithRole('resident', $society);

        $response = $this->actingAs($resident)->get(route('notices.create'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_noticeboard(): void
    {
        [$society] = $this->createSocietyContext('alpha');
        $this->createNotice($society, 'published');

        $response = $this->get(route('notices.noticeboard'));

        $response->assertRedirect();
    }

    public function test_unauthenticated_user_cannot_access_notices_list(): void
    {
        $response = $this->get(route('notices.index'));

        $response->assertRedirect();
    }

    public function test_cross_society_notice_list_access_blocked(): void
    {
        [$societyA] = $this->createSocietyContext('alpha');
        [$societyB] = $this->createSocietyContext('beta');

        $adminA = $this->createUserWithRole('society-admin', $societyA);

        $response = $this->actingAs($adminA)->get(route('notices.index'));

        $response->assertOk();
    }

    protected function createNotice(Society $society, string $status = 'draft'): Notice
    {
        return Notice::create([
            'society_id' => $society->id,
            'title' => 'Test Notice',
            'content' => 'Test content',
            'category' => 'general',
            'priority' => 'normal',
            'status' => $status,
            'visibility' => 'public',
            'created_by' => $this->systemUser->id,
        ]);
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