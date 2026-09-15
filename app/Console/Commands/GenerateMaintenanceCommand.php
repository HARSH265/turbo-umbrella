<?php

namespace App\Console\Commands;

use App\Services\MaintenanceGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMaintenanceCommand extends Command
{
    protected $signature = 'maintenance:generate {--society= : Specific society ID}
                                                   {--month= : Month in Y-m format (default: current month)}
                                                   {--dry-run : Preview what would be generated without creating}';

    protected $description = 'Generate maintenance bills for societies';

    public function __construct(
        protected MaintenanceGenerationService $generationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $societyId = $this->option('society');
        $monthStr = $this->option('month');
        $dryRun = $this->option('dry-run');

        $month = $monthStr ? Carbon::parse($monthStr . '-01') : Carbon::now();

        if ($dryRun) {
            return $this->dryRun($societyId, $month);
        }

        return $this->generate($societyId, $month);
    }

    protected function generate(?int $societyId, Carbon $month): int
    {
        if ($societyId) {
            $this->info("Generating maintenance for society {$societyId}, month {$month->format('Y-m')}...");
            $count = $this->generationService->generateForSociety($societyId, $month);
            $this->info("Generated {$count} maintenance bills.");
            return Command::SUCCESS;
        }

        $societies = \App\Models\Society::active()->get();
        $totalGenerated = 0;

        foreach ($societies as $society) {
            try {
                $count = $this->generationService->generateForSociety($society->id, $month);
                $totalGenerated += $count;
                $this->line("Society {$society->id}: {$society->name} -> {$count} bills");
            } catch (\Exception $e) {
                $this->warn("Society {$society->id}: {$society->name} -> Skipped: {$e->getMessage()}");
            }
        }

        $this->info("Total generated: {$totalGenerated} maintenance bills.");
        return Command::SUCCESS;
    }

    protected function dryRun(?int $societyId, Carbon $month): int
    {
        $this->info("DRY RUN - No bills will be created");
        $this->info("Month: {$month->format('Y-m')}");
        $this->info("Society: " . ($societyId ?? 'ALL'));

        $this->line("");
        $this->info("Preview (showing first 5 societies with active policies):");

        $query = \App\Models\Society::active()->with('maintenancePolicy');

        if ($societyId) {
            $query->where('id', $societyId);
        }

        foreach ($query->get() as $society) {
            if (!$society->maintenancePolicy || !$society->maintenancePolicy->is_active) {
                $this->warn("  {$society->name}: No active policy");
                continue;
            }

            $flatCount = \App\Models\Flat::whereHas('tower', fn($q) => $q->where('society_id', $society->id))
                ->where('is_active', true)
                ->count();

            $existingCount = \App\Models\Maintenance::whereHas('flat.tower', fn($q) => $q->where('society_id', $society->id))
                ->where('month', $month->format('Y-m'))
                ->count();

            $toGenerate = $flatCount - $existingCount;
            $this->line("  {$society->name}: {$toGenerate} bills to generate ({$flatCount} flats, {$existingCount} existing)");
        }

        return Command::SUCCESS;
    }
}