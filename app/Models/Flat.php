<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Flat Model
 * 
 * Represents individual residential unit within a tower
 * Tracks occupancy, residents, and related transactions
 */

class Flat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tower_id',
        'flat_number',
        'floor_number',
        'type',
        'carpet_area',
        'occupancy_status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'floor_number' => 'integer',
            'carpet_area' => 'decimal:2',
        ];
    }

    // Relationships

    /**
     * Get parent tower
     */
    public function tower()
    {
        return $this->belongsTo(Tower::class);
    }

    /**
     * Get all residents associated with flat
     */
    public function residents()
    {
        return $this->belongsToMany(User::class, 'flat_residents')
            ->withPivot(['relation_type', 'start_date', 'end_date', 'is_primary', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get active residents only
     */
    public function activeResidents()
    {
        return $this->residents()->wherePivot('is_active', true);
    }

    /**
     * Get primary resident (owner/main contact)
     */
    public function primaryResident()
    {
        return $this->residents()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get maintenance records
     */
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * Get pending maintenance
     */
    public function pendingMaintenances()
    {
        return $this->maintenances()->whereIn('status', ['unpaid', 'partially_paid', 'overdue']);
    }

    /**
     * Get complaints related to flat
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Get visitors
     */
    public function visitors()
    {
        return $this->hasMany(Visitor::class);
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
     * Get full flat identifier (Tower-Flat)
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->tower->name}-{$this->flat_number}";
    }

    /**
     * Check if flat is occupied
     * 
     * @return bool
     */
    public function isOccupied(): bool
    {
        return $this->occupancy_status === 'occupied';
    }

    /**
     * Get total pending maintenance amount
     * 
     * @return float
     */
    public function getTotalPendingMaintenance(): float
    {
        return $this->pendingMaintenances()->sum('amount');
    }

    // Scopes

    /**
     * Scope to get only active flats
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get occupied flats
     */
    public function scopeOccupied($query)
    {
        return $query->where('occupancy_status', 'occupied');
    }

    /**
     * Scope to get vacant flats
     */
    public function scopeVacant($query)
    {
        return $query->where('occupancy_status', 'vacant');
    }

    /**
     * Scope to get flats for a society
     */
    public function scopeForSociety(Builder $query, int $societyId): Builder
    {
        return $query->whereHas('tower', function (Builder $towerQuery) use ($societyId) {
            $towerQuery->where('society_id', $societyId);
        });
    }
}
