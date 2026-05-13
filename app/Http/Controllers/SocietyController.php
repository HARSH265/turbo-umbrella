<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreSocietyRequest;
use App\Http\Requests\UpdateSocietyRequest;
use App\Models\Society;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SocietyController
 * 
 * Manages society/complex records
 */
class SocietyController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
        
        $this->middleware('permission:societies.view')->only(['index', 'show']);
        $this->middleware('permission:societies.create')->only(['create', 'store']);
        $this->middleware('permission:societies.update')->only(['edit', 'update']);
        $this->middleware('permission:societies.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Society::with('creator');
        $user = Auth::user();

        if ($user->isSocietyAdmin()) {
            $query->whereKey($user->society_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('code', 'LIKE', "%$search%")
                  ->orWhere('city', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $societies = $query->latest()->paginate(5)->withQueryString();

        return view('societies.index', compact('societies'));
    }

    public function create()
    {
        return view('societies.create');
    }

    public function store(StoreSocietyRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();

            $society = Society::create($data);

            $this->activityLog->logCreate('society', $society->id, $society->toArray());

            DB::commit();

            return redirect()
                ->route('societies.show', $society)
                ->with('success', 'Society created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create society.']);
        }
    }

    public function show(Society $society)
    {
        $this->authorizeSocietyAccess($society);

        $society->load(['towers', 'notices' => fn($q) => $q->latest()->take(5)]);
        
        $stats = [
            'total_towers' => $society->towers()->count(),
            'total_flats' => $society->getTotalFlatsCount(),
            'occupied_flats' => $society->getOccupiedFlatsCount(),
        ];

        return view('societies.show', compact('society', 'stats'));
    }

    public function edit(Society $society)
    {
        $this->authorizeSocietyAccess($society);

        return view('societies.edit', compact('society'));
    }

    public function update(UpdateSocietyRequest $request, Society $society)
    {
        $this->authorizeSocietyAccess($society);

        DB::beginTransaction();
        try {
            $oldData = $society->toArray();
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $society->update($data);

            $this->activityLog->logUpdate('society', $society->id, $oldData, $society->fresh()->toArray());

            DB::commit();

            return redirect()
                ->route('societies.show', $society)
                ->with('success', 'Society updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update society.']);
        }
    }

    public function destroy(Society $society)
    {
        $this->authorizeSocietyAccess($society);

        // Check if society has towers
        if ($society->towers()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete society with existing towers.']);
        }

        try {
            $this->activityLog->logDelete('society', $society->id, $society->toArray());
            $society->delete();

            return redirect()
                ->route('societies.index')
                ->with('success', 'Society deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete society.']);
        }
    }

    private function authorizeSocietyAccess(Society $society): void
    {
        $user = Auth::user();

        if ($user->isSocietyAdmin() && $society->id !== $user->society_id) {
            abort(403, 'Unauthorized access to this society.');
        }
    }
}
