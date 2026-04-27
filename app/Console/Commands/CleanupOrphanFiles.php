<?php

namespace App\Console\Commands;

use App\Services\FileService;
use App\Models\Complaint;
use App\Models\Notice;
use Illuminate\Console\Command;

/**
 * CleanupOrphanFiles Command
 * 
 * Removes files where parent entity no longer exists
 * Run weekly via cron
 */
class CleanupOrphanFiles extends Command
{
    protected $signature = 'files:cleanup-orphans';
    protected $description = 'Remove orphan files from storage';

    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        parent::__construct();
        $this->fileService = $fileService;
    }

    public function handle(): int
    {
        $this->info('Cleaning up orphan files...');

        $totalCleaned = 0;

        try {
            // Cleanup complaint files
            $validComplaintIds = Complaint::pluck('id')->toArray();
            $count = $this->fileService->cleanupOrphans('complaints', $validComplaintIds);
            $this->info("Cleaned {$count} orphan complaint files.");
            $totalCleaned += $count;

            // Cleanup notice files
            $validNoticeIds = Notice::pluck('id')->toArray();
            $count = $this->fileService->cleanupOrphans('notices', $validNoticeIds);
            $this->info("Cleaned {$count} orphan notice files.");
            $totalCleaned += $count;

            $this->info("Total orphan files cleaned: {$totalCleaned}");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to cleanup orphan files: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}