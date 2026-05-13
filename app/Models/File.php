<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * File Model
 * 
 * Centralized file management across all modules
 * Stores metadata and handles file operations securely
 */

class File extends Model
{
    use HasFactory, SoftDeletes;

    private function disk()
    {
        return Storage::disk(config('files.default_disk', 'ssms'));
    }

    protected $fillable = [
        'module',
        'entity_id',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'size' => 'integer',
        ];
    }

    // Relationships

    /**
     * Get uploader (user who uploaded file)
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helper Methods

    /**
     * Get full storage path
     * 
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        return $this->disk()->path($this->path);
    }

    /**
     * Get public URL (if applicable)
     * 
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        return $this->disk()->exists($this->path) ? $this->disk()->url($this->path) : null;
    }

    /**
     * Get human-readable file size
     * 
     * @return string
     */
    public function getHumanSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Delete file from storage
     * 
     * @return bool
     */
    public function deleteFile(): bool
    {
        if ($this->disk()->exists($this->path)) {
            $this->disk()->delete($this->path);
        }
        
        return $this->delete();
    }

    /**
     * Check if file exists on disk
     * 
     * @return bool
     */
    public function existsOnDisk(): bool
    {
        return $this->disk()->exists($this->path);
    }

    // Scopes

    /**
     * Scope to get files by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to get files for specific entity
     */
    public function scopeForEntity($query, string $module, int $entityId)
    {
        return $query->where('module', $module)->where('entity_id', $entityId);
    }

    /**
     * Scope to get active files
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get orphan files (entity no longer exists)
     * This is module-specific and should be customized
     */
    public function scopeOrphans($query)
    {
        // Implementation depends on module validation logic
        // Example: files where complaint_id doesn't exist in complaints table
        return $query;
    }
}
