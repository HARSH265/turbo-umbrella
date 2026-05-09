<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectVisitorRequest;
use App\Http\Requests\StoreVisitorRequest;
use App\Models\Flat;
use App\Models\Visitor;
use App\Services\VisitorAccessService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/**
 * VisitorController
 * 
 * Manages visitor entry/exit logs and approvals
 */
class VisitorController extends Controller
{
    public function __construct(private VisitorAccessService $accessService)
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
        $user = Auth::user();
        $query = Visitor::with(['flat.tower', 'approver', 'creator']);

        if ($user->isResident()) {
            $query->forFlatIds($this->activeFlatIdsFor($user));
        } elseif (($user->isSocietyAdmin() || $user->isStaff()) && $user->society_id) {
            $query->forSociety($user->society_id);
        }

        // Filter by approval status
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('entry_time', $request->date);
        }

        // Filter by inside/exited state
        if ($request->filled('inside')) {
            if ($request->boolean('inside')) {
                $query->currentlyInside();
            } else {
                $query->whereNotNull('exit_time');
            }
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
        $user = Auth::user();

        $flats = Flat::with('tower.society')
            ->active()
            ->occupied()
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->forSociety($user->society_id);
            })
            ->get();

        return view('visitors.create', compact('flats'));
    }

    /**
     * Register new visitor entry
     */
    public function store(StoreVisitorRequest $request)
    {
        $validated = $request->validated();

        $flat = Flat::with('tower')->findOrFail((int) $validated['flat_id']);

        if (!$this->accessService->canAccessFlat(Auth::user(), $flat)) {
            abort(403);
        }

        try {
            $visitor = Visitor::create([
                'flat_id' => $flat->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'purpose' => $validated['purpose'],
                'entry_time' => $validated['entry_time'],
                'remarks' => $validated['remarks'] ?? null,
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
        $user = Auth::user();

        if (!$this->accessService->canView($user, $visitor)) {
            abort(403);
        }

        $visitor->load(['flat.tower', 'approver', 'creator']);

        return view('visitors.show', array_merge(
            ['visitor' => $visitor],
            $this->accessService->actionFlags($user, $visitor)
        ));
    }

    /**
     * Approve visitor entry
     */
    public function approve(Visitor $visitor)
    {
        // Check if user can approve (resident of the flat)
        if (!$this->accessService->canApprove(Auth::user(), $visitor)) {
            abort(403);
        }

        try {
            $visitor->approve(Auth::id());
            return back()->with('success', 'Visitor approved successfully.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to approve visitor.']);
        }
    }

    /**
     * Reject visitor entry
     */
    public function reject(RejectVisitorRequest $request, Visitor $visitor)
    {
        $validated = $request->validated();

        // Check if user can reject
        if (!$this->accessService->canReject(Auth::user(), $visitor)) {
            abort(403);
        }

        try {
            $visitor->reject(Auth::id(), $validated['remarks'] ?? null);
            return back()->with('success', 'Visitor rejected.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reject visitor.']);
        }
    }

    /**
     * Record visitor exit
     */
    public function recordExit(Visitor $visitor)
    {
        if (!$this->accessService->canManage(Auth::user(), $visitor)) {
            abort(403);
        }

        try {
            $visitor->recordExit();
            return back()->with('success', 'Exit time recorded.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to record exit.']);
        }
    }
}
