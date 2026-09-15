<?php

use Illuminate\Foundation\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // ✅ Generate maintenance on 1st of every month at midnight
        $schedule->command('maintenance:generate')
            ->monthlyOn(1, '00:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ✅ Apply late fees daily at 2 AM
        $schedule->command('maintenance:apply-late-fees')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
