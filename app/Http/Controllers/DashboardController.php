<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Services\ComplaintService;
use App\Services\MaintenanceService;
use App\Models\Society;
use App\Models\Flat;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * DashboardController
 * 
 * Displays role-specific dashboard with key metrics
 */
class DashboardController extends Controller
{
    protected ComplaintService $complaintService;
    protected MaintenanceService $maintenanceService;

    public function __construct(
        ComplaintService $complaintService,
        MaintenanceService $maintenanceService
    ) {
        $this->complaintService = $complaintService;
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Display dashboard based on user role
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin() || $user->isSocietyAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->isResident()) {
            return $this->residentDashboard();
        }

        if ($user->isStaff()) {
            return $this->staffDashboard();
        }

        abort(403);
    }

    /**
     * Admin dashboard with overall statistics
     */
    private function adminDashboard()
    {
        $user = Auth::user();
        $societyId = $user->isSocietyAdmin() ? $user->society_id : null;

        $flatsQuery = Flat::active();
        $residentsQuery = User::withRole('resident')->active();
        $complaintsSummary = $this->complaintService->getDashboardSummary($societyId);
        $maintenanceSummary = $this->maintenanceService->getSummary($societyId);

        if ($societyId) {
            $flatsQuery->whereHas('tower', function ($query) use ($societyId) {
                $query->where('society_id', $societyId);
            });

            $residentsQuery->where('society_id', $societyId);
        }

        $stats = [
            'total_societies' => $societyId ? 1 : Society::active()->count(),
            'total_flats' => (clone $flatsQuery)->count(),
            'occupied_flats' => (clone $flatsQuery)->where('occupancy_status', 'occupied')->count(),
            'total_residents' => $residentsQuery->count(),
            'complaints' => $complaintsSummary,
            'maintenance' => $maintenanceSummary,
        ];

        return view('dashboard.admin', compact('stats'));
    }

    /**
     * Resident dashboard with personal data
     */
    private function residentDashboard()
    {
        $user = Auth::user();
        $primaryFlat = $user->primaryFlat();

        $stats = [
            'flat' => $primaryFlat,
            'pending_maintenance' => $primaryFlat ? 
                $this->maintenanceService->getPendingMaintenance($primaryFlat->id) : 
                collect(),
            'my_complaints' => $user->complaints()
                ->with('flat')
                ->latest()
                ->take(5)
                ->get(),
            'complaint_summary' => [
                'open' => $user->complaints()->where('status', 'open')->count(),
                'in_progress' => $user->complaints()->where('status', 'in_progress')->count(),
                'resolved' => $user->complaints()->where('status', 'resolved')->count(),
            ],
        ];

        return view('dashboard.resident', compact('stats'));
    }

    /**
     * Staff dashboard with assigned tasks
     */
    private function staffDashboard()
    {
        $user = Auth::user();

        $stats = [
            'assigned_complaints' => $user->assignedComplaints()
                ->whereNotIn('status', ['resolved', 'closed'])
                ->with(['user', 'flat'])
                ->latest()
                ->paginate(10),
            'pending_count' => $user->assignedComplaints()
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'resolved_today' => $user->assignedComplaints()
                ->where('status', 'resolved')
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        return view('dashboard.staff', compact('stats'));
    }
}
