<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceService
{
    public function __construct(
        protected MaintenanceLateFeeService $lateFeeService
    ) {
    }

    public function getSummary(?int $societyId = null): array
    {
        $query = Maintenance::query();

        if ($societyId) {
            $query->forSociety($societyId);
        }

        return $this->getSummaryFromQuery($query);
    }

    public function getSummaryFromQuery(Builder $query): array
    {
        $totalDue = (clone $query)->sum('amount');
        $totalLateFee = (clone $query)->sum('late_fee');
        $totalPaid = (clone $query)->sum('amount_paid');

        return [
            'total_billed' => round($totalDue, 2),
            'total_late_fee' => round($totalLateFee, 2),
            'total_collected' => round($totalPaid, 2),
            'total_outstanding' => round(($totalDue + $totalLateFee) - $totalPaid, 2),
            'paid_count' => (clone $query)->where('status', MaintenanceStatus::PAID)->count(),
            'partial_count' => (clone $query)->where('status', MaintenanceStatus::PARTIALLY_PAID)->count(),
            'unpaid_count' => (clone $query)->where('status', MaintenanceStatus::UNPAID)->count(),
            'overdue_count' => (clone $query)->where('status', MaintenanceStatus::OVERDUE)->count(),
        ];
    }

    public function getPendingMaintenance(int $flatId)
    {
        return Maintenance::forFlat($flatId)
            ->notPaid()
            ->orderBy('month')
            ->get();
    }

    public function getMonthlySummary(int $societyId, string $month): array
    {
        $query = Maintenance::forSociety($societyId)->forMonth($month);

        return [
            'month' => $month,
            'total_billed' => round((clone $query)->sum('amount'), 2),
            'total_late_fee' => round((clone $query)->sum('late_fee'), 2),
            'total_collected' => round((clone $query)->sum('amount_paid'), 2),
            'total_outstanding' => round(
                ((clone $query)->sum('amount') + (clone $query)->sum('late_fee')) - (clone $query)->sum('amount_paid'),
                2
            ),
            'total_flats' => (clone $query)->count(),
            'paid_count' => (clone $query)->where('status', MaintenanceStatus::PAID)->count(),
            'unpaid_count' => (clone $query)->where('status', MaintenanceStatus::UNPAID)->count(),
            'overdue_count' => (clone $query)->where('status', MaintenanceStatus::OVERDUE)->count(),
            'partial_count' => (clone $query)->where('status', MaintenanceStatus::PARTIALLY_PAID)->count(),
        ];
    }

    public function calculateLateFees(): int
    {
        return $this->lateFeeService->applyLateFees();
    }
}
