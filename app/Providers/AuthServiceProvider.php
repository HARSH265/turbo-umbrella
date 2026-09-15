<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\File::class => \App\Policies\FilePolicy::class,
        \App\Models\Notice::class => \App\Policies\NoticePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
   public function boot(): void
{
    $this->registerPolicies(); // ✅ IMPORTANT

    Gate::before(function ($user, string $ability) {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
            return true;
        }

        return null;
    });
}
}
