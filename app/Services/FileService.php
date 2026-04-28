<?php

namespace App\Services;

use App\Exceptions\FileException;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * app/Services/FileService.php
 * 
 * Centralized File Handling Service
 * 
 * Purpose     : Manage upload, retrieval, deletion of files across all modules
 * Used By     : Any module controller/service that handles file uploads
 * Disk        : Configurable via config/files.php (default: public)
 * Validation  : Per-module rules from config/files.php
 * Exceptions  : Throws FileException for all error cases
 */
class FileService
{
    /**
     * Get disk name from config
     */
    private function disk(): string
    {
        return config('files.default_disk', 'public');
    }

    /**
     * Get module config
     * 
     * @throws FileException
     */
    private function moduleConfig(string $module): array
    {
        $config = config("files.modules.{$module}");

        if (!$config) {
            throw FileException::moduleNotConfigured($module);
        }

        return $config;
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD
    |--------------------------------------------------------------------------
    */

    /**
     * Upload a single file
     * 
     * @param  UploadedFile $file
     * @param  string       $module   e.g. 'notices', 'complaints'
     * @param  int          $entityId ID of the related record
     * @return File
     * @throws FileException
     */
    public function upload(UploadedFile $file, string $module, int $entityId): File
    {
        $config = $this->moduleConfig($module);

        // Validate file against module rules
        $this->validate($file, $config);

        // Check max files limit
        $this->checkMaxFiles($module, $entityId, $config);

        DB::beginTransaction();

        try {
            // Build storage directory path
            $directory = $this->buildDirectory($module, $entityId);

            // Generate unique filename
            $storedName = $this->generateFileName($file);

            // Store on disk
            $fullPath = Storage::disk($this->disk())
                ->putFileAs($directory, $file, $storedName);

            if (!$fullPath) {
                throw FileException::uploadFailed('Storage write failed.');
            }

            // Persist metadata to DB
            $fileRecord = File::create([
                'module'        => $module,
                'entity_id'     => $entityId,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'path'          => $fullPath,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'uploaded_by'   => Auth::id(),
                'is_active'     => true,
            ]);

            DB::commit();

            return $fileRecord;

        } catch (FileException $e) {
            DB::rollBack();
            $this->cleanupFile($fullPath ?? null);
            throw $e;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->cleanupFile($fullPath ?? null);
            throw FileException::uploadFailed($e->getMessage());
        }
    }

    /**
     * Upload multiple files
     * 
     * @param  UploadedFile[] $files
     * @param  string         $module
     * @param  int            $entityId
     * @return File[]
     * @throws FileException
     */
    public function uploadMultiple(array $files, string $module, int $entityId): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploaded[] = $this->upload($file, $module, $entityId);
            }
        }

        return $uploaded;
    }

    /*
    |--------------------------------------------------------------------------
    | RETRIEVAL
    |--------------------------------------------------------------------------
    */

    /**
     * Get all active files for an entity
     * 
     * @param  string $module
     * @param  int    $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFiles(string $module, int $entityId)
    {
        return File::where('module', $module)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get download response for a file
     * 
     * @param  File $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws FileException
     */
    public function download(File $file)
    {
        if (!Storage::disk($this->disk())->exists($file->path)) {
            throw FileException::notFound();
        }

        return Storage::disk($this->disk())
            ->download($file->path, $file->original_name);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETION
    |--------------------------------------------------------------------------
    */

    /**
     * Delete a single file
     * 
     * @param  int  $fileId
     * @param  bool $removeFromDisk
     * @return bool
     * @throws FileException
     */
    public function delete(int $fileId, bool $removeFromDisk = true): bool
    {
        $file = File::findOrFail($fileId);

        DB::beginTransaction();

        try {
            // Remove from disk
            if ($removeFromDisk && Storage::disk($this->disk())->exists($file->path)) {
                Storage::disk($this->disk())->delete($file->path);
            }

            // Soft delete DB record
            $file->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw FileException::deletionFailed($e->getMessage());
        }
    }

    /**
     * Delete all files for an entity
     * 
     * @param  string $module
     * @param  int    $entityId
     * @return int    Number of files deleted
     */
    public function deleteForEntity(string $module, int $entityId): int
    {
        $files = File::where('module', $module)
            ->where('entity_id', $entityId)
            ->get();

        $count = 0;

        foreach ($files as $file) {
            try {
                $this->delete($file->id);
                $count++;
            } catch (FileException $e) {
                // Log and continue — don't stop bulk deletion on single failure
                logger()->warning("FileService: Failed to delete file ID {$file->id}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Deactivate a file (soft disable without deleting)
     * 
     * @param  int $fileId
     * @return bool
     */
    public function deactivate(int $fileId): bool
    {
        return (bool) File::where('id', $fileId)
            ->update(['is_active' => false]);
    }

    /**
     * Reactivate a file
     * 
     * @param  int $fileId
     * @return bool
     */
    public function reactivate(int $fileId): bool
    {
        return (bool) File::where('id', $fileId)
            ->update(['is_active' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | ORPHAN CLEANUP
    |--------------------------------------------------------------------------
    */

    /**
     * Delete orphan files (entity no longer exists)
     * 
     * @param  string $module
     * @param  int[]  $validEntityIds
     * @return int    Number of orphans deleted
     */
    public function cleanupOrphans(string $module, array $validEntityIds): int
    {
        $orphans = File::where('module', $module)
            ->whereNotIn('entity_id', $validEntityIds)
            ->get();

        $count = 0;

        foreach ($orphans as $file) {
            try {
                $this->delete($file->id);
                $count++;
            } catch (FileException $e) {
                logger()->warning("FileService: Orphan cleanup failed for file ID {$file->id}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Validate file against module config rules
     * 
     * @throws FileException
     */
    private function validate(UploadedFile $file, array $config): void
    {
        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $config['allowed_extensions'])) {
            throw FileException::invalidType($extension);
        }

        // Check MIME type (prevents extension spoofing)
        if (!in_array($file->getMimeType(), $config['allowed_mimes'])) {
            throw FileException::invalidMime($file->getMimeType());
        }

        // Check file size
        $maxBytes = ($config['max_size'] ?? config('files.max_size', 5)) * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw FileException::fileTooLarge($config['max_size'] ?? config('files.max_size', 5));
        }
    }

    /**
     * Check if max files limit is reached for this entity
     * 
     * @throws FileException
     */
    private function checkMaxFiles(string $module, int $entityId, array $config): void
    {
        if (!isset($config['max_files'])) {
            return;
        }

        $currentCount = File::where('module', $module)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        if ($currentCount >= $config['max_files']) {
            throw FileException::maxFilesExceeded($config['max_files']);
        }
    }

    /**
     * Build storage directory path
     * Format: module/entityId/YYYY/MM
     * 
     * @param  string $module
     * @param  int    $entityId
     * @return string
     */
    private function buildDirectory(string $module, int $entityId): string
    {
        return sprintf(
            '%s/%d/%s/%s',
            $module,
            $entityId,
            now()->format('Y'),
            now()->format('m')
        );
    }

    /**
     * Generate unique stored filename
     * Format: timestamp_randomHash.extension
     * 
     * @param  UploadedFile $file
     * @return string
     */
    private function generateFileName(UploadedFile $file): string
    {
        return sprintf(
            '%s_%s.%s',
            now()->timestamp,
            Str::random(12),
            strtolower($file->getClientOriginalExtension())
        );
    }

    /**
     * Clean up file from disk if DB transaction failed
     * 
     * @param  string|null $path
     */
    private function cleanupFile(?string $path): void
    {
        if ($path && Storage::disk($this->disk())->exists($path)) {
            Storage::disk($this->disk())->delete($path);
        }
    }
}
