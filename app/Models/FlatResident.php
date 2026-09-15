<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FlatResident Model
 * 
 * Pivot model managing relationship between flats and users
 * Tracks residency history, ownership, and tenancy details
 */

class FlatResident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flat_id',
        'user_id',
        'relation_type',
        'start_date',
        'end_date',
        'is_primary',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
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
     * Get associated user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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

    // Scopes

    /**
     * Scope to get only active residencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get owners
     */
    public function scopeOwners($query)
    {
        return $query->where('relation_type', 'owner');
    }

    /**
     * Scope to get tenants
     */
    public function scopeTenants($query)
    {
        return $query->where('relation_type', 'tenant');
    }

    /**
     * Scope to get primary residents
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
