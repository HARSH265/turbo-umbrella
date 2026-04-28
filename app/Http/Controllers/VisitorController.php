<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Visitor;
use App\Models\Flat;
use Illuminate\Http\Request;

/**
 * VisitorController
 * 
 * Manages visitor entry/exit logs and approvals
 */
class VisitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visitors.view')->only(['index', 'show']);
        $this->middleware('permission:visitors.create')->only(['create', 'store']);
        $this->middleware('permission:visitors.update')->only(['approve', 'reject', 'recordExit']);
    }

    /**
     * Display visitor logs
     */
    public function index(Request $request)
    {
        $query = Visitor::with(['flat.tower', 'approver', 'creator']);

        // Filter by approval status
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('entry_time', $request->date);
        }

        // For residents, show only their flat's visitors
        if (Auth::user()->isResident()) {
            $flatIds = Auth::user()->activeFlats->pluck('id');
            $query->whereIn('flat_id', $flatIds);
        }

        // Show today's visitors by default
        if (!$request->has('date') && !$request->has('show_all')) {
            $query->today();
        }

        $visitors = $query->latest('entry_time')->paginate(50)->withQueryString();

        return view('visitors.index', compact('visitors'));
    }

    /**
     * Show form to register visitor
     */
    public function create()
    {
        $flats = Flat::with('tower')->active()->occupied()->get();

        return view('visitors.create', compact('flats'));
    }

    /**
     * Register new visitor entry
     */
    public function store(Request $request)
    {
        $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{10}$/',
            'purpose' => 'required|string|max:255',
            'entry_time' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $visitor = Visitor::create([
                'flat_id' => $request->flat_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'purpose' => $request->purpose,
                'entry_time' => $request->entry_time,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->route('visitors.index')
                ->with('success', 'Visitor registered successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to register visitor.']);
        }
    }

    /**
     * Display visitor details
     */
    public function show(Visitor $visitor)
    {
        if (!$this->canView($visitor)) {
            abort(403);
        }

        $visitor->load(['flat.tower', 'approver', 'creator']);

        return view('visitors.show', compact('visitor'));
    }

    /**
     * Approve visitor entry
     */
    public function approve(Visitor $visitor)
    {
        // Check if user can approve (resident of the flat)
        if (!$this->canApprove($visitor)) {
            abort(403);
        }

        try {
            $visitor->approve(Auth::id());

            return back()->with('success', 'Visitor approved successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to approve visitor.']);
        }
    }

    /**
     * Reject visitor entry
     */
    public function reject(Request $request, Visitor $visitor)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        // Check if user can reject
        if (!$this->canApprove($visitor)) {
            abort(403);
        }

        try {
            $visitor->reject(Auth::id(), $request->remarks);

            return back()->with('success', 'Visitor rejected.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reject visitor.']);
        }
    }

    /**
     * Record visitor exit
     */
    public function recordExit(Visitor $visitor)
    {
        if (!$this->canManage($visitor)) {
            abort(403);
        }

        try {
            $visitor->recordExit();

            return back()->with('success', 'Exit time recorded.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to record exit.']);
        }
    }

    /**
     * Check if user can approve/reject visitor
     */
    private function canApprove(Visitor $visitor): bool
    {
        $user = Auth::user();

        // Admins can approve all
        if ($user->isSuperAdmin() || $user->isSocietyAdmin()) {
            return true;
        }

        // Residents can approve for their flats
        if ($user->isResident()) {
            return $user->activeFlats->contains($visitor->flat_id);
        }

        return false;
    }

    /**
     * Check if user can view visitor details
     */
    private function canView(Visitor $visitor): bool
    {
        $user = Auth::user();

        if ($user->hasPermission('visitors.view')) {
            return true;
        }

        if ($user->isResident()) {
            return $user->activeFlats->contains($visitor->flat_id);
        }

        return false;
    }

    /**
     * Check if user can update visitor state
     */
    private function canManage(Visitor $visitor): bool
    {
        $user = Auth::user();

        if ($user->hasPermission('visitors.update') && !$user->isResident()) {
            return true;
        }

        if ($user->isResident()) {
            return $user->activeFlats->contains($visitor->flat_id);
        }

        return false;
    }
}
