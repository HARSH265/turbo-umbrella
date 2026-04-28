<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Complaint Model
 * 
 * Manages resident complaints and service requests
 * Tracks lifecycle from creation to resolution
 */

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'flat_id',
        'category',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_to',
        'assigned_at',
        'resolved_at',
        'resolution_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'status' => ComplaintStatus::class,
        'priority' => \App\Enums\ComplaintPriority::class,
        ];
    }

    // Boot method to auto-generate ticket number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($complaint) {
            if (empty($complaint->ticket_number)) {
                $complaint->ticket_number = self::generateTicketNumber();
            }
        });
    }

    // Relationships

    /**
     * Get complaint creator (resident)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get associated flat
     */
    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    /**
     * Get assigned staff member
     */
    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all comments
     */
    public function comments()
    {
        return $this->hasMany(ComplaintComment::class);
    }

    /**
     * Get attached files
     */
    public function files()
    {
        return $this->hasMany(File::class, 'entity_id')
            ->where('module', 'complaints');
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
     * Generate unique ticket number
     * Format: CMP-YYYYMMDD-XXXX
     * 
     * @return string
     */
    private static function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $lastTicket = self::whereDate('created_at', now())->latest('id')->first();
        $sequence = $lastTicket ? (int) substr($lastTicket->ticket_number, -4) + 1 : 1;

        return 'CMP-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Assign complaint to staff
     * 
     * @param int $staffId
     * @return bool
     */
    public function assignTo(int $staffId): bool
    {
        return $this->update([
            'assigned_to' => $staffId,
            'assigned_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Mark complaint as resolved
     * 
     * @param string $resolutionNote
     * @return bool
     */
    public function markAsResolved(string $resolutionNote): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_note' => $resolutionNote,
        ]);
    }

    /**
     * Close complaint (admin only)
     * 
     * @return bool
     */
    public function close(): bool
    {
        if ($this->status !== ComplaintStatus::RESOLVED) {
            return false; // Can only close resolved complaints
        }

        return $this->update([
            'status' => ComplaintStatus::CLOSED,
        ]);
    }

    /**
     * Check if complaint is open
     * 
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === ComplaintStatus::OPEN;
    }

    /**
     * Check if complaint is in progress
     * 
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->status === ComplaintStatus::IN_PROGRESS;
    }

    /**
     * Check if complaint is resolved
     * 
     * @return bool
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED], true);
    }

    /**
     * Check if complaint can be edited
     * 
     * @return bool
     */
    public function canBeEdited(): bool
    {
        return !$this->isResolved();
    }
    

    // Scopes

    /**
     * Scope to get open complaints
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to get in-progress complaints
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get resolved complaints
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    /**
     * Scope to get high priority complaints
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope to get complaints by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
