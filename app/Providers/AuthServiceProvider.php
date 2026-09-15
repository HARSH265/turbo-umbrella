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
        $this->registerPolicies();

        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // Permission-slug checks only. Blade calls these argument-less —
            // @can('vehicles.create') — whereas a policy question always carries the
            // model or class it is about: authorize('update', $notice).
            //
            // Previously this granted ANY ability whose name matched a permission slug,
            // arguments included, which short-circuited the policy and skipped its
            // ownership and tenant rules. It held only because no policy ability happened
            // to share a name with a slug; renaming one to 'notices.update' would have
            // let a society admin edit another society's notice.
            if ($arguments !== []) {
                return null;
            }

            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
