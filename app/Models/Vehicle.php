<?php

namespace App\Models;

use App\Models\Traits\BelongsToSociety;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use BelongsToSociety;

    protected $fillable = [
        'society_id',
        'flat_id',
        'registration_number',
        'vehicle_type',
        'make',
        'model',
        'color',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}