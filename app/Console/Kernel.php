// app/Console/Kernel.php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Calculate late fees daily at 1 AM
        $schedule->command('maintenance:calculate-late-fees')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Cleanup orphan files weekly on Sunday at 2 AM
        $schedule->command('files:cleanup-orphans')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Backup database daily at 3 AM
        // Uncomment and configure when using backup package
        // $schedule->command('backup:run')
        //     ->dailyAt('03:00')
        //     ->withoutOverlapping()
        //     ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}