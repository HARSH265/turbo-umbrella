<?php

namespace App\Console\Commands;

use App\Services\FileService;
use App\Models\Complaint;
use App\Models\Notice;
use Illuminate\Console\Command;

/**
 * CleanupOrphanFiles Command
 *
 * Removes files whose parent entity no longer exists.
 * Run weekly via cron.
 *
 * Deletion here is permanent, so the valid-ID sets below MUST include soft-deleted
 * records: a soft-deleted complaint is still restorable, and erasing its attachments
 * would make that restore useless.
 */
class CleanupOrphanFiles extends Command
{
    protected $signature = 'files:cleanup-orphans {--dry-run : List what would be deleted without removing anything}';

    protected $description = 'Remove orphan files from storage';

    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        parent::__construct();
        $this->fileService = $fileService;
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Scanning for orphan files (dry run — nothing will be deleted)...'
            : 'Cleaning up orphan files...');

        $totalCleaned = 0;

        try {
            foreach ($this->modules() as $module => $validIds) {
                $count = $dryRun
                    ? $this->fileService->findOrphans($module, $validIds)->count()
                    : $this->fileService->cleanupOrphans($module, $validIds);

                $this->info($dryRun
                    ? "Would clean {$count} orphan {$module} file(s)."
                    : "Cleaned {$count} orphan {$module} file(s).");

                $totalCleaned += $count;
            }

            $this->info($dryRun
                ? "Total orphan files that would be cleaned: {$totalCleaned}"
                : "Total orphan files cleaned: {$totalCleaned}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to cleanup orphan files: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Module => IDs whose files must be kept.
     *
     * withTrashed() is essential: pluck('id') alone applies the SoftDeletes global
     * scope, so every attachment belonging to a soft-deleted complaint or notice
     * would be classed as an orphan and permanently erased.
     *
     * @return array<string, int[]>
     */
    private function modules(): array
    {
        return [
            'complaints' => Complaint::withTrashed()->pluck('id')->all(),
            'notices' => Notice::withTrashed()->pluck('id')->all(),
        ];
    }
}
