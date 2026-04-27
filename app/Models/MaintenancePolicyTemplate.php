<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\BillingCycle;
use App\Enums\CalculationType;
use App\Enums\LateFeeType;

class MaintenancePolicyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'billing_cycle',
        'calculation_type',
        'base_amount',
        'type_amounts',
        'late_fee_type',
        'late_fee_value',
        'grace_days',
        'allow_partial_payment',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_cycle'         => BillingCycle::class,
            'calculation_type'      => CalculationType::class,
            'late_fee_type'         => LateFeeType::class,
            'base_amount'           => 'decimal:2',
            'late_fee_value'        => 'decimal:2',
            'grace_days'            => 'integer',
            'type_amounts'          => 'array',
            'allow_partial_payment' => 'boolean',
            'is_active'             => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function policies()
    {
        return $this->hasMany(MaintenancePolicy::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getAmountForFlatType(string $flatType): float
    {
        if ($this->calculation_type !== CalculationType::FLAT_TYPE) {
            return (float) $this->base_amount;
        }

        $amounts = $this->type_amounts ?? [];

        if (!isset($amounts[$flatType]) || !is_numeric($amounts[$flatType])) {
            return (float) $this->base_amount; // fallback
        }

        return (float) $amounts[$flatType];
    }

    public function calculateLateFee(float $baseAmount): float
    {
        if (!$this->late_fee_type || !$this->late_fee_value) {
            return 0;
        }

        return match ($this->late_fee_type) {
            LateFeeType::FIXED      => (float) $this->late_fee_value,
            LateFeeType::PERCENTAGE => round(($baseAmount * (float) $this->late_fee_value) / 100, 2),
            default                 => 0,
        };
    }

    public function isPartialPaymentAllowed(): bool
    {
        return (bool) $this->allow_partial_payment;
    }
}