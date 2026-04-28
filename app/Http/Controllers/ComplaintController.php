<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\AssignComplaintRequest;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\Request;


/**
 * ComplaintController
 * 
 * Manages complaint CRUD operations
 * Handles assignment, status updates, comments
 */
class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;

        // Apply middleware
        $this->middleware('permission:complaints.view')->only(['index', 'show']);
        $this->middleware('permission:complaints.create')->only(['create', 'store']);
        $this->middleware('permission:complaints.update')->only(['edit', 'update']);
        $this->middleware('permission:complaints.assign')->only('assign');
    }

    /**
     * Display list of complaints
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Complaint::with(['user', 'flat', 'assignedStaff']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search by ticket number or subject
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%$search%")
                    ->orWhere('subject', 'LIKE', "%$search%");
            });
        }

        // For residents, show only their complaints
        if ($user->isResident()) {
            $query->where('user_id', $user->id);
        }

        // For staff, show assigned complaints
        if ($user->isStaff()) {
            $query->where('assigned_to', $user->id);
        }

        if ($user->isSocietyAdmin() && $user->society_id) {
            $query->whereHas('flat.tower', function ($q) use ($user) {
                $q->where('society_id', $user->society_id);
            });
        }

        $complaints = $query->latest()->paginate(20)->withQueryString();

        return view('complaints.index', compact('complaints'));
    }

    /**
     * Show form to create new complaint
     */
    public function create()
    {
        $user = Auth::user();
        $flats = $user->activeFlats()->with('tower')->get();

        // If user has only one flat, pre-select it
        $selectedFlat = $flats->count() === 1 ? $flats->first() : null;

        return view('complaints.create', compact('flats', 'selectedFlat'));
    }

    /**
     * Store new complaint
     */

    public function store(StoreComplaintRequest $request)
    {
        try {
            // Check for duplicates
            if ($this->complaintService->checkDuplicate(
                auth()->id(),
                $request->subject,
                7
            )) {
                return back()
                    ->withInput()
                    ->withErrors(['subject' => 'You have already submitted a similar complaint recently.']);
            }

            // Get files if uploaded
            $files = $request->hasFile('files') ? $request->file('files') : null;

            // Add user_id to validated data
            $data = $request->validated();
            $data['user_id'] = auth()->id();

            // Create complaint
            $complaint = $this->complaintService->create($data, $files);

            return redirect()
                ->route('complaints.show', $complaint)
                ->with('success', 'Complaint submitted successfully. Ticket: ' . $complaint->ticket_number);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show field errors
            throw $e;
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Complaint creation failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to submit complaint: ' . $e->getMessage()]);
        }
    }

    /**
     * Display single complaint with details
     */
    public function show(Complaint $complaint)
    {
        // Authorization check
        if (!$this->canViewComplaint($complaint)) {
            abort(403);
        }

        $complaint->load(['user', 'flat.tower', 'assignedStaff', 'comments.user', 'files']);

        return view('complaints.show', compact('complaint'));
    }

    /**
     * Assign complaint to staff
     */
    public function assign(AssignComplaintRequest $request, Complaint $complaint)
    {
        try {
            $this->complaintService->assign($complaint->id, $request->assigned_to);

            return back()->with('success', 'Complaint assigned successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to assign complaint.']);
        }
    }

    /**
     * Add comment to complaint
     */
    public function addComment(Request $request, Complaint $complaint)
    {
        if (!$this->canViewComplaint($complaint)) {
            abort(403);
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
            'is_internal' => 'boolean',
            'assign_to' => 'nullable|exists:users,id',
        ]);

        if (
            $request->boolean('is_internal') &&
            !Auth::user()->isSuperAdmin() &&
            !Auth::user()->isSocietyAdmin() &&
            !Auth::user()->isStaff()
        ) {
            return back()->withErrors(['comment' => 'You are not allowed to add internal notes.']);
        }

        try {
            DB::beginTransaction();

            // Add comment
            $this->complaintService->addComment(
                $complaint->id,
                $request->comment,
                $request->boolean('is_internal')
            );

            // Handle assignment if provided
            if ($request->filled('assign_to')) {
                // Validate assignee is staff or admin
                $assignee = User::findOrFail($request->assign_to);

                if (!$assignee->isStaff() && !$assignee->isSocietyAdmin()) {
                    throw new \Exception('Can only assign to staff or admins.');
                }

                // Assign complaint
                $this->complaintService->assign($complaint->id, $request->assign_to);
            }

            DB::commit();

            return back()->with('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to add comment: ' . $e->getMessage()]);
        }
    }

    /**
     * Resolve complaint
     */
    public function resolve(Request $request, Complaint $complaint)
    {
        if (!$this->canManageComplaint($complaint)) {
            abort(403);
        }

        $request->validate([
            'resolution_note' => 'required|string|max:2000',
        ]);

        try {
            $this->complaintService->resolve($complaint->id, $request->resolution_note);

            return back()->with('success', 'Complaint marked as resolved.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to resolve complaint.']);
        }
    }

    /**
     * Update complaint status manually
     * Admin override with validation
     */
    public function updateStatus(Request $request, Complaint $complaint)
    {
        if (!$this->canManageComplaint($complaint)) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        try {
            $newStatus = $request->status;
            $oldStatus = $complaint->status;
            $user = auth()->user();

            // Validation: Cannot close unless resolved
            if ($newStatus === 'closed' && $oldStatus !== 'resolved') {
                return back()->withErrors(['status' => 'Can only close resolved complaints.']);
            }

            // Only society admin or super admin can close a resolved complaint
            if (
                $newStatus === 'closed' &&
                !$user->isSuperAdmin() &&
                !$user->isSocietyAdmin()
            ) {
                return back()->withErrors(['status' => 'Only admin or super admin can close a resolved complaint.']);
            }

            // Validation: Cannot reopen resolved/closed complaints (unless super admin)
            if (in_array($oldStatus, ['resolved', 'closed']) && $newStatus !== 'closed' && !$user->isSuperAdmin()) {
                return back()->withErrors(['status' => 'Cannot reopen resolved complaints.']);
            }

            // Update status
            $result = $this->complaintService->updateStatus($complaint->id, $newStatus);

            if ($result) {
                return back()->with('success', 'Status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
            }

            return back()->withErrors(['error' => 'Failed to update status.']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update status: ' . $e->getMessage()]);
        }
    }

    /**
     * Resident dispute flow for unresolved fixes.
     */
    public function dispute(Request $request, Complaint $complaint)
    {
        if (!$this->canDisputeComplaint($complaint)) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:2000',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        try {
            $files = $request->hasFile('files') ? $request->file('files') : null;

            $this->complaintService->dispute(
                $complaint->id,
                $request->reason,
                $files
            );

            return back()->with('success', 'Complaint disputed successfully. The team has been notified.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'error' => 'Failed to dispute complaint: ' . $e->getMessage(),
            ]);
        }
    }


    /**
     * Check if user can view specific complaint
     */
    private function canViewComplaint(Complaint $complaint): bool
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSocietyAdmin()) {
            return $complaint->flat
                && $complaint->flat->tower
                && $complaint->flat->tower->society_id === $user->society_id;
        }

        // Residents can view their own
        if ($user->isResident() && $complaint->user_id === $user->id) {
            return true;
        }

        // Staff can view assigned
        if ($user->isStaff() && $complaint->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update or resolve a complaint
     */
    private function canManageComplaint(Complaint $complaint): bool
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSocietyAdmin()) {
            return $complaint->flat
                && $complaint->flat->tower
                && $complaint->flat->tower->society_id === $user->society_id;
        }

        if ($user->isStaff()) {
            return $complaint->assigned_to === $user->id;
        }

        return false;
    }

    private function canDisputeComplaint(Complaint $complaint): bool
    {
        $user = Auth::user();

        return $user->isResident()
            && $complaint->user_id === $user->id
            && in_array($complaint->status->value, ['resolved', 'closed'], true);
    }
}
