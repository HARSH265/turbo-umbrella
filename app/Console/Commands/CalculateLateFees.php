<?php

namespace App\Console\Commands;

use App\Services\MaintenanceService;
use Illuminate\Console\Command;

/** * CalculateLateFees Command * * Scheduled command to calculate and apply late fees * Run daily via cron */ class CalculateLateFees extends Command
{
    protected $signature = 'maintenance:calculate-late-fees';
    protected $description = 'Calculate and apply late fees for overdue maintenance';
    protected MaintenanceService $maintenanceService;
    public function __construct(MaintenanceService $maintenanceService)
    {
        parent::__construct();
        $this->maintenanceService = $maintenanceService;
    }
    public function handle(): int
    {
        $this->info('Calculating late fees...');
        try {
            $count = $this->maintenanceService->calculateLateFees();
            $this->info("Late fees applied to {$count} maintenance record(s).");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to calculate late fees: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
