<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenancePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'amount_paid',
        'payment_date',
        'payment_mode',
        'transaction_id',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount_paid'  => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}