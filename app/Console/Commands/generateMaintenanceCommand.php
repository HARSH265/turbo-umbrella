<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Society;
use App\Models\User;
use App\Services\MaintenanceGenerationService;

class GenerateMaintenanceCommand extends Command
{
    protected $signature = 'maintenance:generate {--month= : Month in Y-m format} {--society= : Specific society ID}';
    protected $description = 'Generate maintenance bills for societies';

    public function handle(): int
    {
        $monthOption   = $this->option('month');
        $societyOption = $this->option('society');

        $month = $monthOption
            ? Carbon::parse($monthOption)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $this->info("Generating maintenance for: " . $month->format('F Y'));

        $query = Society::where('is_active', true);

        if ($societyOption) {
            $query->where('id', $societyOption);
        }

        $societies = $query->get();
        $systemUserId = User::query()->orderBy('id')->value('id');

        if ($societies->isEmpty()) {
            $this->warn('No active societies found.');
            return Command::SUCCESS;
        }

        $totalGenerated = 0;

        foreach ($societies as $society) {
            try {
                $count = app(MaintenanceGenerationService::class)
                    ->generateForSociety($society->id, $month, $systemUserId);

                $totalGenerated += $count;

                $this->info("✅ {$society->name}: {$count} flat(s) generated");

            } catch (\Exception $e) {
                $this->error("❌ {$society->name}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Total generated: {$totalGenerated}");

        return Command::SUCCESS;
    }
}
