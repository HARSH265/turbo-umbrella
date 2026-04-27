<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notice Model
 * 
 * Manages society-wide announcements and notices
 * Supports targeted visibility and file attachments
 */

class Notice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'society_id',
        'title',
        'content',
        'priority',
        'visibility',
        'publish_date',
        'expiry_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'publish_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    // Relationships

    /**
     * Get parent society
     */
    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    /**
     * Get specific recipients (for targeted notices)
     */
    public function recipients()
    {
        return $this->belongsToMany(User::class, 'notice_recipients')
            ->withPivot(['is_read', 'read_at'])
            ->withTimestamps();
    }

    /**
     * Get attached files
     */
    public function files()
    {
        return $this->hasMany(File::class, 'entity_id')
            ->where('module', 'notices');
    }

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper Methods

    /**
     * Check if notice is currently active
     * 
     * @return bool
     */
    public function isCurrentlyActive(): bool
    {
        $now = now()->startOfDay();
        
        return $this->is_active 
            && $this->publish_date <= $now
            && ($this->expiry_date === null || $this->expiry_date >= $now);
    }

    /**
     * Check if notice has expired
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && now()->greaterThan($this->expiry_date);
    }

    /**
     * Mark as read by user
     * 
     * @param int $userId
     * @return void
     */
    public function markAsReadBy(int $userId): void
    {
        $this->recipients()->updateExistingPivot($userId, [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    // Scopes

    /**
     * Scope to get active notices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('publish_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            });
    }

    /**
     * Scope to get urgent notices
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope to get notices for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('visibility', 'all')
              ->orWhereHas('recipients', function ($subQ) use ($userId) {
                  $subQ->where('user_id', $userId);
              });
        });
    }
}
