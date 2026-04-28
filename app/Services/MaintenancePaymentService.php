<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Maintenance;
use App\Models\MaintenancePayment;
use App\Models\MaintenancePolicy;
use App\Models\User;
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
            $oldStatus = $maintenance->status;

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

            $maintenance = $maintenance->fresh('flat.residents');

            $this->notifyPaymentRecorded($maintenance, $amount, $performedBy);
            $this->notifyStatusChange($maintenance, $oldStatus);

            return $maintenance;
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

    private function notifyPaymentRecorded(Maintenance $maintenance, float $amount, int $performedBy): void
    {
        $performedByUser = User::find($performedBy);

        foreach ($maintenance->flat?->activeResidents ?? collect() as $resident) {
            $message = 'A payment of Rs. ' . number_format($amount, 2) . ' has been recorded.';

            if ($performedByUser && $performedByUser->id !== $resident->id) {
                $message = 'Your maintenance has been paid by ' . $performedByUser->name
                    . ' (Rs. ' . number_format($amount, 2) . ').';
            }

            $this->notificationService->sendToUser(
                $resident,
                NotificationType::PAYMENT_RECEIVED,
                'Maintenance payment recorded',
                $message,
                'maintenance',
                $maintenance->id,
                route('maintenance.show', $maintenance)
            );
        }

        $societyId = $maintenance->flat?->tower?->society_id;

        if ($societyId) {
            $flatLabel = $maintenance->flat->flat_number ?? 'N/A';

            $this->notificationService->sendToRole(
                'society-admin',
                NotificationType::PAYMENT_RECEIVED,
                'Maintenance payment received',
                'Payment of Rs. ' . number_format($amount, 2) . " received for flat {$flatLabel}.",
                'maintenance',
                $maintenance->id,
                route('maintenance.show', $maintenance),
                $societyId
            );
        }
    }

    private function notifyStatusChange(Maintenance $maintenance, $oldStatus): void
    {
        if ($oldStatus === $maintenance->status) {
            return;
        }

        foreach ($maintenance->flat?->activeResidents ?? collect() as $resident) {
            $this->notificationService->sendToUser(
                $resident,
                NotificationType::MAINTENANCE_UPDATED,
                'Maintenance status updated',
                'Maintenance status is now ' . ucfirst(str_replace('_', ' ', $maintenance->status->value)) . '.',
                'maintenance',
                $maintenance->id,
                route('maintenance.show', $maintenance)
            );
        }
    }
}
