<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreFlatRequest;
use App\Http\Requests\UpdateFlatRequest;
use App\Models\Flat;
use App\Models\Tower;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FlatController
 * 
 * Manages flat records and resident assignments
 */
class FlatController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;

        $this->middleware('permission:flats.view')->only(['index', 'show']);
        $this->middleware('permission:flats.create')->only(['create', 'store']);
        $this->middleware('permission:flats.update')->only(['edit', 'update']);
        $this->middleware('permission:flats.delete')->only('destroy');
    }

    /**
     * Display list of flats
     */
    public function index(Request $request)
    {
        $query = Flat::with(['tower.society']);
        $user = Auth::user();

        if ($user->isSocietyAdmin()) {
            $query->whereHas('tower', function ($towerQuery) use ($user) {
                $towerQuery->where('society_id', $user->society_id);
            });
        }

        // Filter by tower
        if ($request->filled('tower_id')) {
            $query->where('tower_id', $request->tower_id);
        }

        // Filter by occupancy status
        if ($request->filled('occupancy_status')) {
            $query->where('occupancy_status', $request->occupancy_status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by flat number
        if ($request->filled('search')) {
            $query->where('flat_number', 'LIKE', '%' . $request->search . '%');
        }

        $flats = $query->paginate(50)->withQueryString();
        $towers = $this->availableTowersFor($user)->get();

        // Load primary residents separately
        $flats->load(['activeResidents' => function ($query) {
            $query->wherePivot('is_primary', true);
        }]);

        return view('flats.index', compact('flats', 'towers'));
    }

    /**
     * Show form to create flat
     */
    public function create()
    {
        $towers = $this->availableTowersFor(Auth::user())->get();

        return view('flats.create', compact('towers'));
    }

    /**
     * Store new flat
     */
    public function store(StoreFlatRequest $request)
    {
        $this->authorizeTowerSelection((int) $request->input('tower_id'));

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();

            $flat = Flat::create($data);

            $this->activityLog->logCreate('flat', $flat->id, $flat->toArray());

            DB::commit();

            return redirect()
                ->route('flats.show', $flat)
                ->with('success', 'Flat created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create flat.']);
        }
    }

    /**
     * Display flat details
     */
    public function show(Flat $flat)
{
    $this->authorizeFlatAccess($flat);

    $flat->load([
        'tower.society',
        'activeResidents',
        'pendingMaintenances',
        'complaints' => fn($q) => $q->latest()->take(10),
        'visitors' => fn($q) => $q->latest()->take(5)
    ]);

    return view('flats.show', compact('flat'));
}

    

    /**
     * Show form to edit flat
     */
    public function edit(Flat $flat)
    {
        $this->authorizeFlatAccess($flat);
        $towers = $this->availableTowersFor(Auth::user())->get();

        return view('flats.edit', compact('flat', 'towers'));
    }

    /**
     * Update flat
     */
    public function update(UpdateFlatRequest $request, Flat $flat)
    {
        $this->authorizeFlatAccess($flat);
        $this->authorizeTowerSelection((int) $request->input('tower_id'));

        DB::beginTransaction();
        try {
            $oldData = $flat->toArray();
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $flat->update($data);

            $this->activityLog->logUpdate('flat', $flat->id, $oldData, $flat->fresh()->toArray());

            DB::commit();

            return redirect()
                ->route('flats.show', $flat)
                ->with('success', 'Flat updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update flat.']);
        }
    }

    /**
     * Show form to assign residents
     */
    public function assignResidents(Flat $flat)
    {
        $this->authorizeFlatAccess($flat);
        $flat->load('activeResidents');
        $availableUsers = User::withRole('resident')
            ->active()
            ->when(Auth::user()->isSocietyAdmin(), function ($query) {
                $query->where('society_id', Auth::user()->society_id);
            })
            ->get();

        return view('flats.assign-residents', compact('flat', 'availableUsers'));
    }

    /**
     * Store resident assignment
     */
    public function storeResidentAssignment(Request $request, Flat $flat)
    {
        $this->authorizeFlatAccess($flat);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'relation_type' => 'required|in:owner,tenant,family_member',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_primary' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $resident = User::withRole('resident')->active()->findOrFail($request->user_id);

            if (
                Auth::user()->isSocietyAdmin()
                && $resident->society_id !== Auth::user()->society_id
            ) {
                abort(403, 'Unauthorized resident selection.');
            }

            $alreadyAssigned = $flat->residents()
                ->where('users.id', $resident->id)
                ->wherePivot('is_active', true)
                ->exists();

            if ($alreadyAssigned) {
                return back()
                    ->withInput()
                    ->withErrors(['user_id' => 'This resident is already assigned to the flat.']);
            }

            // If setting as primary, remove primary flag from others
            if ($request->boolean('is_primary')) {
                $flat->residents()->updateExistingPivot(
                    $flat->residents->pluck('id'),
                    ['is_primary' => false]
                );
            }

            // Attach resident
            $flat->residents()->attach($request->user_id, [
                'relation_type' => $request->relation_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_primary' => $request->boolean('is_primary'),
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            // Update flat occupancy status
            $flat->update(['occupancy_status' => 'occupied']);

            DB::commit();

            return redirect()
                ->route('flats.show', $flat)
                ->with('success', 'Resident assigned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to assign resident.']);
        }
    }

    /**
     * Remove resident assignment
     */
    public function removeResident(Request $request, Flat $flat, User $user)
    {
        $this->authorizeFlatAccess($flat);

        DB::beginTransaction();
        try {
            $flat->residents()->updateExistingPivot($user->id, [
                'is_active' => false,
                'end_date' => now(),
            ]);

            // If no active residents, mark flat as vacant
            if ($flat->activeResidents()->count() === 0) {
                $flat->update(['occupancy_status' => 'vacant']);
            }

            DB::commit();

            return back()->with('success', 'Resident removed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to remove resident.']);
        }
    }

    /**
     * Delete flat (soft delete)
     */
    public function destroy(Flat $flat)
    {
        $this->authorizeFlatAccess($flat);

        // Check if flat has active residents
        if ($flat->activeResidents()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete flat with active residents.']);
        }

        // Check if flat has pending maintenance
        if ($flat->pendingMaintenances()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete flat with pending maintenance.']);
        }

        try {
            $this->activityLog->logDelete('flat', $flat->id, $flat->toArray());
            $flat->delete();

            return redirect()
                ->route('flats.index')
                ->with('success', 'Flat deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete flat.']);
        }
    }

    private function authorizeFlatAccess(Flat $flat): void
    {
        $user = Auth::user();

        if ($user->isSocietyAdmin() && $flat->tower?->society_id !== $user->society_id) {
            abort(403, 'Unauthorized access to this flat.');
        }
    }

    private function authorizeTowerSelection(int $towerId): void
    {
        $user = Auth::user();

        if (!$user->isSocietyAdmin()) {
            return;
        }

        $allowed = Tower::whereKey($towerId)
            ->where('society_id', $user->society_id)
            ->exists();

        if (!$allowed) {
            abort(403, 'Unauthorized tower selection.');
        }
    }

    private function availableTowersFor(User $user)
    {
        $query = Tower::with('society')->active();

        if ($user->isSocietyAdmin()) {
            $query->where('society_id', $user->society_id);
        }

        return $query;
    }
}
