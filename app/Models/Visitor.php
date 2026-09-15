<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Visitor Model
 * 
 * Manages visitor entry/exit logs
 * Supports approval workflow for enhanced security
 */
class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'flat_id',
        'name',
        'phone',
        'purpose',
        'entry_time',
        'exit_time',
        'approval_status',
        'approved_by',
        'approved_at',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships

    /**
     * Get associated flat
     */
    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    /**
     * Get approver (resident who approved)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get creator (security/staff)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper Methods

    /**
     * Approve visitor entry
     * 
     * @param int $userId
     * @return bool
     */
    public function approve(int $userId): bool
    {
        $this->guardCanApprove();

        return $this->update([
            'approval_status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject visitor entry
     * 
     * @param int $userId
     * @param string|null $remarks
     * @return bool
     */
    public function reject(int $userId, ?string $remarks = null): bool
    {
        $this->guardCanReject();

        return $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    /**
     * Record exit time
     * 
     * @return bool
     */
    public function recordExit(): bool
    {
        $this->guardCanRecordExit();

        return $this->update(['exit_time' => now()]);
    }

    /**
     * Check if visitor is currently inside
     * 
     * @return bool
     */
    public function isInside(): bool
    {
        return $this->entry_time && !$this->exit_time;
    }

    /**
     * Check if visitor belongs to a given society
     */
    public function belongsToSociety(?int $societyId): bool
    {
        return $societyId !== null && $this->flat?->tower?->society_id === $societyId;
    }

    /**
     * Check if visitor belongs to one of the given flats
     */
    public function belongsToFlatIds(iterable $flatIds): bool
    {
        $flatIds = is_array($flatIds) ? $flatIds : iterator_to_array($flatIds, false);

        return in_array($this->flat_id, $flatIds, true);
    }

    /**
     * Guard valid approval transition
     */
    public function guardCanApprove(): void
    {
        if ($this->approval_status !== 'pending') {
            throw new DomainException('Only pending visitors can be approved.');
        }
    }

    /**
     * Guard valid rejection transition
     */
    public function guardCanReject(): void
    {
        if ($this->approval_status !== 'pending') {
            throw new DomainException('Only pending visitors can be rejected.');
        }
    }

    /**
     * Guard valid exit transition
     */
    public function guardCanRecordExit(): void
    {
        if ($this->approval_status !== 'approved') {
            throw new DomainException('Exit can only be recorded for an approved visitor.');
        }

        if ($this->exit_time !== null) {
            throw new DomainException('Exit can only be recorded once for an approved visitor.');
        }
    }

    // Scopes

    /**
     * Scope to get pending approvals
     */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope to get approved visitors
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to get visitors currently inside
     */
    public function scopeCurrentlyInside($query)
    {
        return $query->whereNotNull('entry_time')->whereNull('exit_time');
    }

    /**
     * Scope to get visitors for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('entry_time', now());
    }

    /**
     * Scope to get visitors for a society
     */
    public function scopeForSociety(Builder $query, int $societyId): Builder
    {
        return $query->whereHas('flat.tower', function (Builder $towerQuery) use ($societyId) {
            $towerQuery->where('society_id', $societyId);
        });
    }

    /**
     * Scope to get visitors for selected flats
     */
    public function scopeForFlatIds(Builder $query, iterable $flatIds): Builder
    {
        $flatIds = is_array($flatIds) ? $flatIds : iterator_to_array($flatIds, false);

        return $query->whereIn('flat_id', $flatIds);
    }

}
