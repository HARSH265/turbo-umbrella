<?php

namespace App\Http\Controllers;

use App\Exceptions\FileException;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;

/**
 * app/Http/Controllers/FileController.php
 * 
 * Handles file downloads and deletions
 * Authorization delegated to FilePolicy
 */
class FileController extends Controller
{
    public function __construct(protected FileService $fileService) {}

    /**
     * Download a file
     */
    public function download(File $file)
    {
        $this->authorize('download', $file);

        try {
            return $this->fileService->download($file);
        } catch (FileException $e) {
            abort($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Delete a file
     */
    public function destroy(File $file)
    {
        $this->authorize('delete', $file);

        try {
            $this->fileService->delete($file->id);

            return back()->with('success', 'File deleted successfully.');

        } catch (FileException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}