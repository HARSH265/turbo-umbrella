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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/**
 * Walks every authenticated GET route as every role and fails on any 5xx.
 *
 * Four resident-facing pages (vehicles list, vehicle detail, amenity booking form,
 * flats list) returned 500 because wherePivot() was called inside whereHas() closures,
 * emitting an invalid `pivot.is_active` column. Nothing in the suite requested those
 * pages as a resident, so the crashes were invisible.
 *
 * 403 and 404 are fine here — this asserts "no page explodes", not "everyone sees
 * everything". Authorisation is covered by the role-matrix tests.
 */
class RouteSweepTest extends TestCase
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

        [$this->society] = $this->createSocietyContext('sweep');
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
    public function test_no_get_route_returns_a_server_error(string $role): void
    {
        $user = $this->createUserWithRole($role, $role === 'super-admin' ? null : $this->society);
        $this->attachResidentToFlat($user, $this->flat);
        $this->seedContentFor($user);

        $this->actingAs($user);

        // Surface the underlying exception instead of the rendered 500 page, so a
        // failure names the cause rather than just the status code.
        $this->withoutExceptionHandling();

        $failures = [];
        $checked = 0;

        foreach ($this->sweepableUris() as $uri) {
            $checked++;

            try {
                $status = $this->get($uri)->getStatusCode();

                if ($status >= 500) {
                    $failures[] = "{$uri} returned {$status}";
                }
            } catch (HttpExceptionInterface $e) {
                // 403/404 are legitimate outcomes for a role sweep; only 5xx is a bug.
                if ($e->getStatusCode() >= 500) {
                    $failures[] = "{$uri} -> {$e->getStatusCode()}: {$e->getMessage()}";
                }
            } catch (ModelNotFoundException|AuthorizationException) {
                // Route-model binding missing a sampled id, or a policy denial — both
                // render as 404/403 in production, so neither is a server error.
            } catch (\Throwable $e) {
                $failures[] = "{$uri} -> " . class_basename($e) . ': ' . $e->getMessage();
            }
        }

        $this->assertGreaterThan(20, $checked, 'Route sweep collected suspiciously few routes.');
        $this->assertSame([], $failures, "Server errors as {$role}:\n" . implode("\n", $failures));
    }

    /**
     * Concrete URIs for every authenticated GET route, with route parameters filled in
     * from the records seeded above. Routes whose parameters cannot be resolved are
     * skipped rather than guessed at.
     *
     * @return string[]
     */
    private function sweepableUris(): array
    {
        $samples = [
            'complaint' => 1, 'maintenance' => 1, 'notice' => 1, 'visitor' => 1,
            'flat' => $this->flat->id, 'user' => $this->systemUser->id,
            'society' => $this->society->id, 'tower' => $this->society->towers()->first()->id,
            'vehicle' => 1, 'amenity' => 1, 'policy' => 1, 'booking' => 1,
            'file' => 1, 'id' => 1, 'module' => 'complaints', 'entity' => 1,
        ];

        $uris = [];

        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // Framework internals and the storage symlink are not app pages.
            if (str_starts_with($uri, '_') || $uri === 'up' || str_contains($uri, 'storage/')) {
                continue;
            }

            $resolved = preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($samples) {
                return array_key_exists($m[1], $samples) ? (string) $samples[$m[1]] : $m[0];
            }, $uri);

            if (str_contains($resolved, '{')) {
                continue;
            }

            $uris['/' . ltrim($resolved, '/')] = true;
        }

        return array_keys($uris);
    }

    /**
     * One record per module, so list and detail pages render real rows instead of
     * empty states — an empty table cannot crash on a bad column reference.
     */
    private function seedContentFor(User $user): void
    {
        Complaint::create([
            'user_id' => $user->id,
            'flat_id' => $this->flat->id,
            'category' => 'plumbing',
            'subject' => 'Sweep complaint',
            'description' => 'Water is leaking from the kitchen pipe.',
            'priority' => 'high',
            'status' => 'in_progress',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);

        Vehicle::create([
            'flat_id' => $this->flat->id,
            'society_id' => $this->society->id,
            'registration_number' => 'GJ01SW0001',
            'vehicle_type' => 'car',
            'make' => 'Tata',
            'model' => 'Nexon',
            'color' => 'Blue',
            'created_by' => $this->systemUser->id,
        ]);

        Amenity::create([
            'society_id' => $this->society->id,
            'name' => 'Sweep Clubhouse',
            'description' => 'Test amenity',
            'type' => 'clubhouse',
            'capacity' => 50,
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
