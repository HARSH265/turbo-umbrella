<?php

namespace App\Models;

use App\Models\Traits\BelongsToSociety;
use Illuminate\Database\Eloquent\Model;

class AmenityBooking extends Model
{
    use BelongsToSociety;

    protected $fillable = [
        'amenity_id',
        'flat_id',
        'user_id',
        'society_id',
        'booking_date',
        'start_time',
        'end_time',
        'purpose',
        'status',
        'total_charge',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_charge' => 'decimal:2',
    ];

    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }
}
