<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Society Model
 * 
 * Represents residential society/complex
 * Central entity containing towers, flats, and residents
 */

class Society extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'pincode',
        'contact_number',
        'email',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    /**
     * Get all towers in society
     */
    public function towers()
    {
        return $this->hasMany(Tower::class);
    }

    /**
     * Get all active towers
     */
    public function activeTowers()
    {
        return $this->towers()->where('is_active', true);
    }

    /**
     * Get all notices for society
     */
    public function notices()
    {
        return $this->hasMany(Notice::class);
    }

    /**
     * Get creator (user who created society)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get last updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper Methods

    /**
     * Get total number of flats across all towers
     * 
     * @return int
     */
    public function getTotalFlatsCount(): int
    {
        return $this->towers()->withCount('flats')->get()->sum('flats_count');
    }

    /**
     * Get total occupied flats
     * 
     * @return int
     */
    public function getOccupiedFlatsCount(): int
    {
        return Flat::whereHas('tower', function ($query) {
            $query->where('society_id', $this->id);
        })->where('occupancy_status', 'occupied')->count();
    }

    // Scopes

    /**
     * Scope to get only active societies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
