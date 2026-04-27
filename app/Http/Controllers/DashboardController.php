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
        $stats = [
            'total_societies' => Society::active()->count(),
            'total_flats' => Flat::active()->count(),
            'occupied_flats' => Flat::occupied()->count(),
            'total_residents' => User::withRole('resident')->active()->count(),
            'complaints' => $this->complaintService->getDashboardSummary(),
            'maintenance' => $this->maintenanceService->getSummary(),
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