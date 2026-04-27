<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

/**
 * FileService
 * 
 * Centralized file handling service
 * Manages upload, storage, validation, and deletion
 * 
 * Purpose: Provide secure, consistent file operations across modules
 * Input: Files from forms, module identifiers, entity IDs
 * Output: File model instances or boolean success status
 * Side Effects: Creates files in storage, DB records, activity logs
 */

class FileService 
{
    /**
     * Allowed file extensions (whitelist)
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'];

    /**
     * Allowed MIME types (whitelist)
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];

    /**
     * Upload single file
     * 
     * @param UploadedFile $file
     * @param string $module (e.g., 'complaints', 'notices')
     * @param int $entityId
     * @return File
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $module, int $entityId): File
    {
        // Validate file
        $this->validateFile($file);

        DB::beginTransaction();
        try {
            // Generate storage path
            $path = $this->generateStoragePath($module, $entityId);
            
            // Generate unique filename
            $storedName = $this->generateFileName($file);
            
            // Store file
            $fullPath = Storage::putFileAs($path, $file, $storedName);

            // Create DB record
            $fileRecord = File::create([
                'module' => $module,
                'entity_id' => $entityId,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'path' => $fullPath,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            DB::commit();
            return $fileRecord;

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up file if DB insert failed
            if (isset($fullPath) && Storage::exists($fullPath)) {
                Storage::delete($fullPath);
            }

            throw $e;
        }
    }

    /**
     * Upload multiple files
     * 
     * @param array $files
     * @param string $module
     * @param int $entityId
     * @return array Array of File models
     */
    public function uploadMultiple(array $files, string $module, int $entityId): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->upload($file, $module, $entityId);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Delete file (soft delete DB + optionally remove from disk)
     * 
     * @param int $fileId
     * @param bool $removeFromDisk
     * @return bool
     */
    public function delete(int $fileId, bool $removeFromDisk = false): bool
    {
        $file = File::findOrFail($fileId);

        if ($removeFromDisk && Storage::exists($file->path)) {
            Storage::delete($file->path);
        }

        return $file->delete(); // Soft delete
    }

    /**
     * Delete files for entity
     * 
     * @param string $module
     * @param int $entityId
     * @param bool $removeFromDisk
     * @return int Number of files deleted
     */
    public function deleteForEntity(string $module, int $entityId, bool $removeFromDisk = false): int
    {
        $files = File::where('module', $module)
            ->where('entity_id', $entityId)
            ->get();

        $count = 0;
        foreach ($files as $file) {
            if ($this->delete($file->id, $removeFromDisk)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cleanup orphan files (files where entity no longer exists)
     * 
     * @param string $module
     * @param array $validEntityIds
     * @return int
     */
    public function cleanupOrphans(string $module, array $validEntityIds): int
    {
        $orphans = File::where('module', $module)
            ->whereNotIn('entity_id', $validEntityIds)
            ->get();

        $count = 0;
        foreach ($orphans as $file) {
            if ($this->delete($file->id, true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validate uploaded file
     * 
     * @param UploadedFile $file
     * @return void
     * @throws \Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \Exception("File type '{$extension}' is not allowed.");
        }

        // Check MIME type (prevent spoofing)
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception("File MIME type is not allowed.");
        }

        // Check size (get from config)
        $maxSize = SystemConfigService::get('max_file_size', 5) * 1024 * 1024; // Convert MB to bytes
        if ($file->getSize() > $maxSize) {
            throw new \Exception("File size exceeds maximum allowed size.");
        }
    }

    /**
     * Generate storage path
     * Format: module/entity_id/YYYY/MM/
     * 
     * @param string $module
     * @param int $entityId
     * @return string
     */
    private function generateStoragePath(string $module, int $entityId): string
    {
        return sprintf(
            'public/%s/%d/%s/%s',
            $module,
            $entityId,
            now()->format('Y'),
            now()->format('m')
        );
    }

    /**
     * Generate unique filename
     * Format: timestamp_randomHash.extension
     * 
     * @param UploadedFile $file
     * @return string
     */
    private function generateFileName(UploadedFile $file): string
    {
        return sprintf(
            '%s_%s.%s',
            now()->timestamp,
            Str::random(12),
            $file->getClientOriginalExtension()
        );
    }

    /**
     * Get files for entity
     * 
     * @param string $module
     * @param int $entityId
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
}