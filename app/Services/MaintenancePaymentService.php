<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Maintenance;
use App\Models\MaintenancePayment;
use App\Models\MaintenancePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenancePaymentService
{
    protected ActivityLogService $activityLogService;
    protected NotificationService $notificationService;

    public function __construct(
        ActivityLogService $activityLogService,
        NotificationService $notificationService
    )
    {
        $this->activityLogService = $activityLogService;
        $this->notificationService = $notificationService;
    }

    /**
     * Record a payment against a maintenance record
     */
    public function recordPayment(
        Maintenance $maintenance,
        float $amount,
        int $performedBy,
        ?string $paymentMode = null,
        ?string $transactionId = null,
        ?string $remarks = null
    ): Maintenance {

        return DB::transaction(function () use (
            $maintenance, $amount, $performedBy,
            $paymentMode, $transactionId, $remarks
        ) {

            // ✅ Validate amount > 0
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            // ✅ Check if already fully paid
            if ($maintenance->isFullyPaid()) {
                throw ValidationException::withMessages([
                    'amount' => 'This maintenance is already fully paid.',
                ]);
            }

            // ✅ Overpayment protection
            $maxPayable = $maintenance->balance_due;

            if ($amount > $maxPayable) {
                throw ValidationException::withMessages([
                    'amount' => "Payment exceeds balance due. Maximum payable: ₹{$maxPayable}",
                ]);
            }

            // ✅ Check partial payment policy
            if ($amount < $maxPayable) {
                $this->validatePartialPayment($maintenance);
            }

            $oldData = $maintenance->toArray();

            // ✅ Create payment ledger entry with ALL fields
            MaintenancePayment::create([
                'maintenance_id' => $maintenance->id,
                'amount_paid'    => $amount,
                'payment_date'   => now(),
                'payment_mode'   => $paymentMode,
                'transaction_id' => $transactionId,
                'remarks'        => $remarks,
                'created_by'     => $performedBy,
            ]);

            // ✅ Update aggregate on maintenance
            $maintenance->amount_paid += $amount;
            $maintenance->payment_mode   = $paymentMode;
            $maintenance->transaction_id = $transactionId;
            $maintenance->remarks        = $remarks;

            // ✅ Recalculate status
            $maintenance->recalculateStatus();

            // ✅ Set paid date only when fully paid
            if ($maintenance->status->value === 'paid') {
                $maintenance->paid_date = now();
            }

            $maintenance->updated_by = $performedBy;
            $maintenance->save();

            // ✅ Log payment
            $this->activityLogService->logUpdate(
                module: 'maintenance',
                entityId: $maintenance->id,
                oldData: $oldData,
                newData: $maintenance->fresh()->toArray(),
                performedBy: $performedBy
            );

            $this->notifyPaymentRecorded($maintenance->fresh('flat.residents'), $amount);

            return $maintenance->fresh();
        });
    }

    /**
     * Validate if partial payment is allowed by policy
     */
    private function validatePartialPayment(Maintenance $maintenance): void
    {
        $flat = $maintenance->flat;

        if (!$flat || !$flat->tower) {
            return; // No society context, allow
        }

        $policy = MaintenancePolicy::getActiveForSociety($flat->tower->society_id);

        if (!$policy || !$policy->template) {
            return; // No policy, allow
        }

        if (!$policy->template->isPartialPaymentAllowed()) {
            throw ValidationException::withMessages([
                'amount' => 'Partial payment is not allowed as per society policy. Please pay full amount.',
            ]);
        }
    }

    private function notifyPaymentRecorded(Maintenance $maintenance, float $amount): void
    {
        foreach ($maintenance->flat?->activeResidents ?? collect() as $resident) {
            $this->notificationService->sendToUser(
                $resident,
                NotificationType::PAYMENT_RECEIVED,
                'Maintenance payment recorded',
                'A payment of Rs. ' . number_format($amount, 2) . ' has been recorded.',
                'maintenance',
                $maintenance->id,
                route('maintenance.show', $maintenance)
            );
        }
    }
}
