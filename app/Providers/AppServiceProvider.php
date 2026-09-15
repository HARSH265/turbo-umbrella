<?php

namespace App\Providers;

use App\View\NotificationMenu;
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
        $this->app->scoped(NotificationMenu::class);
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

        // NotificationMenu resolves its queries on first read and is scoped to the
        // request, so the bell costs at most two queries per page instead of two per
        // layout render — and none at all on pages that never read it.
        View::composer(['layouts.app', 'layouts.sidebar'], function ($view) {
            $view->with('notificationMenu', app(NotificationMenu::class));
        });
    }
}
