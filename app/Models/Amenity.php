<?php

namespace App\Models;

use App\Models\Traits\BelongsToSociety;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use BelongsToSociety;

    protected $fillable = [
        'society_id',
        'name',
        'description',
        'type',
        'capacity',
        'opening_time',
        'closing_time',
        'booking_duration',
        'advance_booking_days',
        'cancellation_hours',
        'charge_per_hour',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'charge_per_hour' => 'decimal:2',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];

    public function bookings()
    {
        return $this->hasMany(AmenityBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAvailable($date, $startTime, $endTime, ?int $excludeBookingId = null)
    {
        $query = $this->bookings()
            ->where('booking_date', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return !$query->exists();
    }
}