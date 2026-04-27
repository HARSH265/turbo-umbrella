<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\MaintenancePolicy;
use App\Enums\MaintenanceStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceLateFeeService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
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
        }
    }
}