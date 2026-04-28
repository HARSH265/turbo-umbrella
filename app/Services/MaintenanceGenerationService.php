<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Flat;
use App\Models\Maintenance;
use App\Models\MaintenancePolicy;
use App\Enums\MaintenanceStatus;
use App\Enums\BillingCycle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenanceGenerationService
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
     * Generate maintenance for all active flats in a society
     */
    public function generateForSociety(int $societyId, Carbon $month, ?int $generatedBy = null): int
    {
        return DB::transaction(function () use ($societyId, $month, $generatedBy) {

            $policy = MaintenancePolicy::getActiveForSociety($societyId);

            if (!$policy) {
                throw new \Exception("No active maintenance policy found for this society.");
            }

            $template = $policy->template;

            if (!$template) {
                throw new \Exception("Policy template missing.");
            }

            // ✅ Check if policy is effective for the given month
            if ($policy->effective_from && $month->lt($policy->effective_from->copy()->startOfMonth())) {
                throw new \Exception("Policy not effective for selected month.");
            }

            // ✅ Check billing cycle eligibility
            if (!$this->isEligibleForBillingCycle($month, $template->billing_cycle)) {
                throw new \Exception("Month {$month->format('F Y')} is not eligible for {$template->billing_cycle->value} billing cycle.");
            }

            $flats = Flat::whereHas('tower', function ($q) use ($societyId) {
                    $q->where('society_id', $societyId);
                })
                ->where('is_active', true)
                ->get();

            $generatedCount = 0;

            foreach ($flats as $flat) {

                // ✅ Skip if already generated for this month
                $exists = Maintenance::where('flat_id', $flat->id)
                    ->where('month', $month->format('Y-m'))
                    ->exists();

                if ($exists) {
                    continue;
                }

                $amount  = $this->calculateAmount($flat, $template);
                $dueDate = $month->copy()->addDays($template->grace_days ?? 0);

                $maintenance = Maintenance::create([
                    'flat_id'     => $flat->id,
                    'month'       => $month->format('Y-m'),
                    'amount'      => $amount,
                    'amount_paid' => 0,
                    'late_fee'    => 0,
                    'status'      => MaintenanceStatus::UNPAID,
                    'due_date'    => $dueDate,
                    'created_by'  => $generatedBy,
                ]);

                $generatedCount++;

                $this->activityLogService->logCreate(
                    module: 'maintenance',
                    entityId: $maintenance->id,
                    data: $maintenance->toArray(),
                    performedBy: $generatedBy
                );

                $this->notifyMaintenanceDue($maintenance->fresh('flat.residents'));
            }

            // ✅ Log generation summary
            $this->activityLogService->log(
                action: 'maintenance_generated',
                module: 'maintenance',
                entityId: null,
                oldData: null,
                newData: [
                    'society_id' => $societyId,
                    'month'      => $month->format('Y-m'),
                    'generated'  => $generatedCount,
                ],
                performedBy: $generatedBy
            );

            return $generatedCount;
        });
    }

    /**
     * Calculate amount based on calculation type
     */
    private function calculateAmount(Flat $flat, $template): float
    {
        return match ($template->calculation_type->value) {

            'fixed' => (float) $template->base_amount,

            'area_based' => round((float) ($flat->carpet_area ?? 0) * (float) $template->base_amount, 2),

            'flat_type' => $template->getAmountForFlatType($flat->type ?? ''),

            default => (float) $template->base_amount,
        };
    }

    /**
     * Check if given month is eligible for billing cycle
     */
    private function isEligibleForBillingCycle(Carbon $month, BillingCycle $cycle): bool
    {
        return match ($cycle) {
            BillingCycle::MONTHLY     => true,
            BillingCycle::QUARTERLY   => in_array($month->month, [1, 4, 7, 10]),
            BillingCycle::HALF_YEARLY => in_array($month->month, [1, 7]),
            BillingCycle::YEARLY      => $month->month === 4, // April (financial year)
            default                   => true,
        };
    }

    private function notifyMaintenanceDue(Maintenance $maintenance): void
    {
        $maintenance->loadMissing('flat.residents');

        foreach ($maintenance->flat?->activeResidents ?? collect() as $resident) {
            $this->notificationService->sendToUser(
                $resident,
                NotificationType::MAINTENANCE_DUE,
                'Maintenance generated',
                "Maintenance for {$maintenance->month} has been generated for your flat.",
                'maintenance',
                $maintenance->id,
                route('maintenance.show', $maintenance)
            );
        }
    }
}
