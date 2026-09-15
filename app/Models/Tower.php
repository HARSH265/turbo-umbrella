<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tower Model
 * 
 * Represents building/tower within a society
 * Contains multiple flats organized by floors
 */


class Tower extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'society_id',
        'name',
        'total_floors',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_floors' => 'integer',
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
     * Get all flats in tower
     */
    public function flats()
    {
        return $this->hasMany(Flat::class);
    }

    /**
     * Get active flats
     */
    public function activeFlats()
    {
        return $this->flats()->where('is_active', true);
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
     * Get occupied flats count
     * 
     * @return int
     */
    public function getOccupiedFlatsCount(): int
    {
        return $this->flats()->where('occupancy_status', 'occupied')->count();
    }

    /**
     * Get vacant flats count
     * 
     * @return int
     */
    public function getVacantFlatsCount(): int
    {
        return $this->flats()->where('occupancy_status', 'vacant')->count();
    }

    // Scopes

    /**
     * Scope to get only active towers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
