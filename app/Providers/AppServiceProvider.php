<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Tailwind pagination
        Paginator::useTailwind();

        // Custom Blade directives
        Blade::directive('money', function ($amount) {
            return "<?php echo 'Rs ' . number_format($amount, 2); ?>";
        });

        // Permission check directive
        Blade::if('hasPermission', function ($permission) {
            return Auth::check() && Auth::user()->hasPermission($permission);
        });

        // Role check directive
        Blade::if('hasRole', function ($role) {
            return Auth::check() && Auth::user()->hasRole($role);
        });

        View::composer(['layouts.app', 'layouts.sidebar'], function ($view) {
            $notificationMenu = [
                'unreadCount' => 0,
                'recent' => collect(),
            ];

            if (Auth::check()) {
                $user = Auth::user();
                $notificationMenu['unreadCount'] = $user->unreadNotifications()->count();
                $notificationMenu['recent'] = $user->notifications()
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            $view->with('notificationMenu', $notificationMenu);
        });
    }
}
