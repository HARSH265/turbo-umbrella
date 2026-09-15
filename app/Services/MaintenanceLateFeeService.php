<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Maintenance;
use App\Models\MaintenancePolicy;
use App\Enums\MaintenanceStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceLateFeeService
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
     * Apply late fees to all overdue maintenance records
     */
    public function applyLateFees(): int
    {
        $updatedCount = 0;

        DB::transaction(function () use (&$updatedCount) {

            $records = Maintenance::with('flat.tower')
                ->notPaid()
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->startOfDay())
                ->get();

            foreach ($records as $maintenance) {
                $oldStatus = $maintenance->status;

                $flat = $maintenance->flat;

                if (!$flat || !$flat->tower) {
                    continue;
                }

                $policy = MaintenancePolicy::getActiveForSociety($flat->tower->society_id);

                if (!$policy || !$policy->template) {
                    continue;
                }

                $template = $policy->template;

                // ✅ Check if late fee is configured
                if (!$template->late_fee_type || !$template->late_fee_value) {
                    // Still mark overdue even without late fee
                    $this->markOverdue($maintenance);
                    $updatedCount++;
                    continue;
                }

                // ✅ Calculate days overdue (after grace period)
                $daysOverdue = now()->startOfDay()->diffInDays($maintenance->due_date);

                if ($daysOverdue <= 0) {
                    continue;
                }

                // ✅ Calculate late fee using template method
                $calculatedLateFee = $template->calculateLateFee((float) $maintenance->amount);

                // ✅ Skip if no change
                if ((float) $maintenance->late_fee === $calculatedLateFee) {
                    continue;
                }

                $oldData = $maintenance->toArray();

                $maintenance->late_fee = $calculatedLateFee;

                // ✅ Recalculate status (handles overdue automatically)
                $maintenance->recalculateStatus();

                $maintenance->save();

                // ✅ Log with system as performer (scheduled task)
                $this->activityLogService->logUpdate(
                    module: 'maintenance',
                    entityId: $maintenance->id,
                    oldData: $oldData,
                    newData: $maintenance->fresh()->toArray(),
                    performedBy: null // system action
                );

                $this->notifyOverdueOrLateFeeUpdate($maintenance->fresh('flat.residents'), $oldStatus, true);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    /**
     * Mark maintenance as overdue without applying late fee
     */
    private function markOverdue(Maintenance $maintenance): void
    {
        if ($maintenance->status === MaintenanceStatus::OVERDUE) {
            return; // Already overdue
        }

        $oldStatus = $maintenance->status;
        $oldData = $maintenance->toArray();

        $maintenance->recalculateStatus();
        $maintenance->save();

        if ($maintenance->wasChanged()) {
            $this->activityLogService->logUpdate(
                module: 'maintenance',
                entityId: $maintenance->id,
                oldData: $oldData,
                newData: $maintenance->fresh()->toArray(),
                performedBy: null
            );

            $this->notifyOverdueOrLateFeeUpdate($maintenance->fresh('flat.residents'), $oldStatus, false);
        }
    }

    private function notifyOverdueOrLateFeeUpdate(Maintenance $maintenance, $oldStatus, bool $lateFeeChanged): void
    {
        foreach ($maintenance->flat?->activeResidents ?? collect() as $resident) {
            if ($oldStatus !== $maintenance->status && $maintenance->status === MaintenanceStatus::OVERDUE) {
                $this->notificationService->sendToUser(
                    $resident,
                    NotificationType::MAINTENANCE_OVERDUE,
                    'Maintenance is overdue',
                    "Your maintenance bill for {$maintenance->month} is now overdue.",
                    'maintenance',
                    $maintenance->id,
                    route('maintenance.show', $maintenance)
                );
            }

            if ($lateFeeChanged && (float) $maintenance->late_fee > 0) {
                $this->notificationService->sendToUser(
                    $resident,
                    NotificationType::MAINTENANCE_UPDATED,
                    'Late fee applied',
                    'A late fee of Rs. ' . number_format((float) $maintenance->late_fee, 2) . ' has been applied to your maintenance bill.',
                    'maintenance',
                    $maintenance->id,
                    route('maintenance.show', $maintenance)
                );
            }
        }
    }
}
