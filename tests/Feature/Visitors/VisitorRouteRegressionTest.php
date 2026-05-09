<?php

namespace Tests\Feature\Visitors;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class VisitorRouteRegressionTest extends TestCase
{
    public function test_visitor_route_names_resolve_to_expected_uris(): void
    {
        $this->assertSame('http://localhost/visitors', route('visitors.index', absolute: true));
        $this->assertSame('http://localhost/visitors/create', route('visitors.create', absolute: true));
        $this->assertSame('http://localhost/visitors', route('visitors.store', absolute: true));
        $this->assertSame('http://localhost/visitors/5', route('visitors.show', 5, absolute: true));
        $this->assertSame('http://localhost/visitors/5/approve', route('visitors.approve', 5, absolute: true));
        $this->assertSame('http://localhost/visitors/5/reject', route('visitors.reject', 5, absolute: true));
        $this->assertSame('http://localhost/visitors/5/exit', route('visitors.exit', 5, absolute: true));
    }

    public function test_visitor_routes_keep_expected_http_methods(): void
    {
        $this->assertSame(['GET', 'HEAD'], $this->methodsFor('visitors.index'));
        $this->assertSame(['GET', 'HEAD'], $this->methodsFor('visitors.create'));
        $this->assertSame(['POST'], $this->methodsFor('visitors.store'));
        $this->assertSame(['GET', 'HEAD'], $this->methodsFor('visitors.show'));
        $this->assertSame(['POST'], $this->methodsFor('visitors.approve'));
        $this->assertSame(['POST'], $this->methodsFor('visitors.reject'));
        $this->assertSame(['POST'], $this->methodsFor('visitors.exit'));
    }

    public function test_visitor_routes_keep_expected_controller_actions(): void
    {
        $this->assertSame('App\Http\Controllers\VisitorController@index', $this->actionFor('visitors.index'));
        $this->assertSame('App\Http\Controllers\VisitorController@create', $this->actionFor('visitors.create'));
        $this->assertSame('App\Http\Controllers\VisitorController@store', $this->actionFor('visitors.store'));
        $this->assertSame('App\Http\Controllers\VisitorController@show', $this->actionFor('visitors.show'));
        $this->assertSame('App\Http\Controllers\VisitorController@approve', $this->actionFor('visitors.approve'));
        $this->assertSame('App\Http\Controllers\VisitorController@reject', $this->actionFor('visitors.reject'));
        $this->assertSame('App\Http\Controllers\VisitorController@recordExit', $this->actionFor('visitors.exit'));
    }

    public function test_visitor_routes_keep_auth_and_user_state_middleware(): void
    {
        foreach ([
            'visitors.index',
            'visitors.create',
            'visitors.store',
            'visitors.show',
            'visitors.approve',
            'visitors.reject',
            'visitors.exit',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertContains('auth', $middleware, $routeName);
            $this->assertContains('verified', $middleware, $routeName);
            $this->assertContains('user.active', $middleware, $routeName);
        }
    }

    public function test_visitor_action_routes_remain_post_only_for_guests(): void
    {
        $this->get('/visitors/1/approve')->assertStatus(405);
        $this->get('/visitors/1/reject')->assertStatus(405);
        $this->get('/visitors/1/exit')->assertStatus(405);
    }

    public function test_visitor_routes_redirect_guests_to_login(): void
    {
        $this->get(route('visitors.index'))->assertRedirect(route('login'));
        $this->get(route('visitors.create'))->assertRedirect(route('login'));
        $this->post(route('visitors.store'))->assertRedirect(route('login'));
        $this->get(route('visitors.show', 1))->assertRedirect(route('login'));
        $this->post(route('visitors.approve', 1))->assertRedirect(route('login'));
        $this->post(route('visitors.reject', 1))->assertRedirect(route('login'));
        $this->post(route('visitors.exit', 1))->assertRedirect(route('login'));
    }

    private function methodsFor(string $routeName): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getByName($routeName)->methods(),
            fn (string $method) => $method !== 'HEAD' || in_array('GET', Route::getRoutes()->getByName($routeName)->methods(), true)
        ));
    }

    private function actionFor(string $routeName): string
    {
        return Route::getRoutes()->getByName($routeName)->getActionName();
    }

    private function middlewareFor(string $routeName): array
    {
        return Route::getRoutes()->getByName($routeName)->gatherMiddleware();
    }
}
