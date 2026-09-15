<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MaintenancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'society_id',
        'template_id',
        'effective_from',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'is_active'      => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function template()
    {
        return $this->belongsTo(MaintenancePolicyTemplate::class, 'template_id');
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSociety(Builder $query, int $societyId): Builder
    {
        return $query->where('society_id', $societyId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function getActiveForSociety(int $societyId): ?self
    {
        return static::forSociety($societyId)
            ->active()
            ->with('template')
            ->first();
    }
}