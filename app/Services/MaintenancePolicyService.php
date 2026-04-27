<?php

namespace App\Services;

use App\Models\MaintenancePolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenancePolicyService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Create and activate a new maintenance policy for a society
     */
    public function createAndActivatePolicy(int $societyId, array $data, int $performedBy): MaintenancePolicy
    {
        return $this->activatePolicy(
            societyId: $societyId,
            templateId: $data['template_id'],
            effectiveFrom: Carbon::parse($data['effective_from']),
            performedBy: $performedBy
        );
    }

    /**
     * Activate a maintenance policy for a society
     * Deactivates any existing active policy first
     */
    public function activatePolicy(
        int $societyId,
        int $templateId,
        Carbon $effectiveFrom,
        int $performedBy
    ): MaintenancePolicy {

        return DB::transaction(function () use ($societyId, $templateId, $effectiveFrom, $performedBy) {

            // ✅ Deactivate existing active policy
            $existingActive = MaintenancePolicy::forSociety($societyId)
                ->active()
                ->first();

            if ($existingActive) {
                $oldData = $existingActive->toArray();

                $existingActive->update([
                    'is_active'  => false,
                    'updated_by' => $performedBy,
                ]);

                $this->activityLogService->logUpdate(
                    module: 'maintenance_policy',
                    entityId: $existingActive->id,
                    oldData: $oldData,
                    newData: $existingActive->fresh()->toArray(),
                    performedBy: $performedBy
                );
            }

            // ✅ Create new active policy
            $policy = MaintenancePolicy::create([
                'society_id'     => $societyId,
                'template_id'   => $templateId,
                'effective_from' => $effectiveFrom,
                'is_active'      => true,
                'created_by'     => $performedBy,
            ]);

            $this->activityLogService->logCreate(
                module: 'maintenance_policy',
                entityId: $policy->id,
                data: $policy->toArray(),
                performedBy: $performedBy
            );

            return $policy;
        });
    }
}