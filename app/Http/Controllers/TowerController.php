<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Tower;
use App\Models\Society;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TowerController
 * 
 * Manages tower/building records within societies
 */
class TowerController extends Controller
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
        $query = Tower::with(['society', 'creator']);

        if ($request->filled('society_id')) {
            $query->where('society_id', $request->society_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $towers = $query->latest()->paginate(20);
        $societies = Society::active()->get();

        return view('towers.index', compact('towers', 'societies'));
    }

    public function create()
    {
        $societies = Society::active()->get();
        return view('towers.create', compact('societies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'society_id' => 'required|exists:societies,id',
            'name' => 'required|string|max:255',
            'total_floors' => 'required|integer|min:1|max:100',
        ]);

        DB::beginTransaction();
        try {
            $tower = Tower::create([
                'society_id' => $request->society_id,
                'name' => $request->name,
                'total_floors' => $request->total_floors,
                'created_by' => Auth::id(),
            ]);

            $this->activityLog->logCreate('tower', $tower->id, $tower->toArray());

            DB::commit();

            return redirect()
                ->route('towers.show', $tower)
                ->with('success', 'Tower created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create tower.']);
        }
    }

    public function show(Tower $tower)
    {
        $tower->load(['society', 'flats']);
        
        $stats = [
            'total_flats' => $tower->flats()->count(),
            'occupied_flats' => $tower->getOccupiedFlatsCount(),
            'vacant_flats' => $tower->getVacantFlatsCount(),
        ];

        return view('towers.show', compact('tower', 'stats'));
    }

    public function edit(Tower $tower)
    {
        $societies = Society::active()->get();
        return view('towers.edit', compact('tower', 'societies'));
    }

    public function update(Request $request, Tower $tower)
    {
        $request->validate([
            'society_id' => 'required|exists:societies,id',
            'name' => 'required|string|max:255',
            'total_floors' => 'required|integer|min:1|max:100',
        ]);

        DB::beginTransaction();
        try {
            $oldData = $tower->toArray();

            $tower->update([
                'society_id' => $request->society_id,
                'name' => $request->name,
                'total_floors' => $request->total_floors,
                'updated_by' => Auth::id(),
            ]);

            $this->activityLog->logUpdate('tower', $tower->id, $oldData, $tower->fresh()->toArray());

            DB::commit();

            return redirect()
                ->route('towers.show', $tower)
                ->with('success', 'Tower updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update tower.']);
        }
    }

    public function destroy(Tower $tower)
    {
        if ($tower->flats()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete tower with existing flats.']);
        }

        try {
            $this->activityLog->logDelete('tower', $tower->id, $tower->toArray());
            $tower->delete();

            return redirect()
                ->route('towers.index')
                ->with('success', 'Tower deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete tower.']);
        }
    }
}