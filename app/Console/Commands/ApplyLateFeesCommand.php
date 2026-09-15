<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MaintenanceLateFeeService;

class ApplyLateFeesCommand extends Command
{
    protected $signature = 'maintenance:apply-late-fees';
    protected $description = 'Apply late fees to overdue maintenance records';

    public function handle(): int
    {
        $this->info('Applying late fees...');

        try {
            $count = app(MaintenanceLateFeeService::class)->applyLateFees();

            $this->info("✅ Late fees applied to {$count} record(s).");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}