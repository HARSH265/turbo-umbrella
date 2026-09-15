<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Self-registration is disabled by design: users are created by an admin so that
 * society, role and flat assignment always happen. RegisteredUserController aborts
 * with 403 and the /register routes are not registered in routes/auth.php at all.
 *
 * These tests lock that decision in — if someone restores the Breeze register
 * routes, they should have to update this file deliberately.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_not_reachable(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_endpoint_is_not_reachable(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '9876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_register_route_is_not_registered(): void
    {
        $this->assertNull(
            app('router')->getRoutes()->getByName('register'),
            'A named "register" route exists — self-registration is supposed to be disabled.'
        );
    }

    public function test_registered_user_controller_refuses_self_registration(): void
    {
        $this->assertSame(0, User::query()->count());

        foreach (['create', 'store'] as $method) {
            $this->assertStringContainsString(
                'abort(403',
                (string) file_get_contents(app_path('Http/Controllers/Auth/RegisteredUserController.php')),
                "RegisteredUserController::{$method}() should refuse self-registration."
            );
        }
    }
}
