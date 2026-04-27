<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'flat_id',
        'month',
        'amount',
        'amount_paid',
        'due_date',
        'late_fee',
        'status',
        'paid_date',
        'payment_mode',
        'transaction_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status'      => MaintenanceStatus::class,
            'due_date'    => 'date',
            'paid_date'   => 'date',
            'amount'      => 'decimal:2',
            'late_fee'    => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payments()
    {
        return $this->hasMany(MaintenancePayment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTotalDueAttribute(): float
    {
        return round((float) $this->amount + (float) ($this->late_fee ?? 0), 2);
    }

    public function getBalanceDueAttribute(): float
    {
        return round($this->total_due - (float) ($this->amount_paid ?? 0), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */

    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        if ($this->status === MaintenanceStatus::PAID) {
            return false;
        }

        return now()->startOfDay()->greaterThan($this->due_date);
    }

    public function isFullyPaid(): bool
    {
        return (float) ($this->amount_paid ?? 0) >= $this->total_due;
    }

    public function recalculateStatus(): void
    {
        $paid    = (float) ($this->amount_paid ?? 0);
        $totalDue = $this->total_due;

        if ($paid <= 0) {
            $this->status = $this->isOverdue()
                ? MaintenanceStatus::OVERDUE
                : MaintenanceStatus::UNPAID;
        } elseif ($paid < $totalDue) {
            $this->status = $this->isOverdue()
                ? MaintenanceStatus::OVERDUE
                : MaintenanceStatus::PARTIALLY_PAID;
        } else {
            $this->status = MaintenanceStatus::PAID;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', MaintenanceStatus::UNPAID);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', MaintenanceStatus::PAID);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', MaintenanceStatus::OVERDUE)
                ->orWhere(function ($sub) {
                    $sub->whereNot('status', MaintenanceStatus::PAID)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', now()->startOfDay());
                });
        });
    }

    public function scopeNotPaid(Builder $query): Builder
    {
        return $query->whereNot('status', MaintenanceStatus::PAID);
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('month', $month);
    }

    public function scopeForFlat(Builder $query, int $flatId): Builder
    {
        return $query->where('flat_id', $flatId);
    }

    public function scopeForSociety(Builder $query, int $societyId): Builder
    {
        return $query->whereHas('flat.tower', function ($q) use ($societyId) {
            $q->where('society_id', $societyId);
        });
    }
}