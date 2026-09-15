<?php

namespace Tests\Feature\Smoke;

use App\Models\Amenity;
use App\Models\Complaint;
use App\Models\Flat;
use App\Models\Society;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/**
 * The POST/PUT/PATCH/DELETE counterpart to RouteSweepTest.
 *
 * Requests are sent with an empty payload, so most are stopped at validation. That is
 * the point: what this exercises is everything that runs *before* validation —
 * constructor middleware, route-model binding, policy resolution, and any controller
 * code that touches the request early. That is exactly where the wherePivot() crashes
 * lived, and none of it was covered on the write side.
 *
 * 422, 403, 404 and redirects are all expected outcomes. Only a 5xx is a bug.
 *
 * This is deliberately shallow. Asserting that a write actually does the right thing
 * belongs in the module's own role-matrix test, with a real payload.
 */
class WriteRouteSweepTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

    private Flat $flat;

    /**
     * Routes that would sabotage the sweep itself rather than reveal anything.
     */
    private const SKIP = [
        'logout',            // ends the acting session, so every later request is a guest
        'profile.destroy',   // deletes the acting user out from under the sweep
        'storage.local.upload', // framework's signed upload endpoint, not an app route
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ssms');

        $this->seed([
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);

        $this->systemUser = User::factory()->create([
            'created_by' => null, 'updated_by' => null, 'society_id' => null,
        ]);
        $this->assignRole($this->systemUser, 'super-admin');

        [$this->society] = $this->createSocietyContext('wsweep');
        $this->flat = $this->createFlatForSociety($this->society, 'W-101');
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
    public function test_no_write_route_returns_a_server_error(string $role): void
    {
        $user = $this->createUserWithRole($role, $role === 'super-admin' ? null : $this->society);
        $this->attachResidentToFlat($user, $this->flat);
        $this->seedContentFor($user);

        $this->withoutExceptionHandling();

        $failures = [];
        $checked = 0;

        foreach ($this->writeRoutes() as [$method, $uri]) {
            $checked++;

            // Re-authenticate each time: a route may have altered the session.
            $this->actingAs($user);

            try {
                $status = $this->call($method, $uri, [], [], [], [])->getStatusCode();

                if ($status >= 500) {
                    $failures[] = "{$method} {$uri} returned {$status}";
                }
            } catch (ValidationException) {
                // An empty payload failing validation is the expected outcome.
            } catch (HttpExceptionInterface $e) {
                if ($e->getStatusCode() >= 500) {
                    $failures[] = "{$method} {$uri} -> {$e->getStatusCode()}: {$e->getMessage()}";
                }
            } catch (ModelNotFoundException|AuthorizationException) {
                // 404 / 403 in production. Not a server error.
            } catch (\Throwable $e) {
                $failures[] = "{$method} {$uri} -> " . class_basename($e) . ': ' . $e->getMessage();
            }
        }

        $this->assertGreaterThan(30, $checked, 'Write sweep collected suspiciously few routes.');
        $this->assertSame([], $failures, "Server errors as {$role}:\n" . implode("\n", $failures));
    }

    /**
     * Concrete [method, uri] pairs for every write route, destructive verbs last so a
     * DELETE does not remove a record a later POST still needs.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function writeRoutes(): array
    {
        $samples = [
            'complaint' => 1, 'maintenance' => 1, 'notice' => 1, 'visitor' => 1,
            'flat' => $this->flat->id, 'user' => $this->systemUser->id,
            'society' => $this->society->id, 'tower' => $this->society->towers()->first()->id,
            'vehicle' => 1, 'amenity' => 1, 'policy' => 1, 'booking' => 1,
            'file' => 1, 'id' => 1, 'module' => 'complaints', 'entity' => 1, 'path' => 'x.txt',
        ];

        $safe = [];
        $destructive = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name && in_array($name, self::SKIP, true)) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                    continue;
                }

                $resolved = preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($samples) {
                    return array_key_exists($m[1], $samples) ? (string) $samples[$m[1]] : $m[0];
                }, $route->uri());

                if (str_contains($resolved, '{')) {
                    continue;
                }

                $pair = [$method, '/' . ltrim($resolved, '/')];

                $method === 'DELETE' ? $destructive[] = $pair : $safe[] = $pair;
            }
        }

        return array_merge($safe, $destructive);
    }

    /**
     * One record per module so route-model binding resolves and the controller runs,
     * rather than bailing out at a 404 before reaching anything interesting.
     */
    private function seedContentFor(User $user): void
    {
        Complaint::create([
            'user_id' => $user->id,
            'flat_id' => $this->flat->id,
            'category' => 'plumbing',
            'subject' => 'Write sweep complaint',
            'description' => 'Water is leaking from the kitchen pipe.',
            'priority' => 'high',
            'status' => 'in_progress',
            'assigned_to' => $user->id,
            'assigned_at' => now(),
            'created_by' => $user->id,
        ]);

        Vehicle::create([
            'flat_id' => $this->flat->id,
            'society_id' => $this->society->id,
            'registration_number' => 'GJ01WS0001',
            'vehicle_type' => 'car',
            'created_by' => $this->systemUser->id,
        ]);

        Amenity::create([
            'society_id' => $this->society->id,
            'name' => 'Write Sweep Hall',
            'description' => 'Test amenity',
            'type' => 'clubhouse',
            'capacity' => 40,
            'opening_time' => '09:00',
            'closing_time' => '21:00',
            'booking_duration' => 60,
            'advance_booking_days' => 7,
            'cancellation_hours' => 24,
            'charge_per_hour' => 100,
            'is_active' => true,
        ]);
    }
}
