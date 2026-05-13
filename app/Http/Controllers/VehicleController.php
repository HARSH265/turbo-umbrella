<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Flat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:vehicles.view')->only(['index', 'show']);
        $this->middleware('permission:vehicles.create')->only(['create', 'store']);
        $this->middleware('permission:vehicles.update')->only(['edit', 'update']);
        $this->middleware('permission:vehicles.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Vehicle::with(['flat.tower', 'flat.residents']);

        if (!$user->isSuperAdmin()) {
            $query->where('society_id', $user->society_id);
        }

        if ($user->isResident()) {
            $query->whereHas('flat.residents', function ($residentQuery) use ($user) {
                $residentQuery->where('users.id', $user->id)
                    ->wherePivot('is_active', true);
            });
        }

        if ($request->filled('flat_id')) {
            $query->where('flat_id', $request->flat_id);
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        if ($request->filled('search')) {
            $query->where('registration_number', 'LIKE', '%' . $request->search . '%');
        }

        $vehicles = $query->latest()->paginate(20)->withQueryString();

        $flats = $this->vehicleFlatsQuery($user->isSuperAdmin() ? null : $user->society_id)->get();

        return view('vehicles.index', compact('vehicles', 'flats'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $societies = \App\Models\Society::active()->orderBy('name')->get();
        $flats = collect();
        $selectedSociety = $request->filled('society_id') ? $request->society_id : null;

        // Get flats based on society
        if (!$user->isSuperAdmin()) {
            $selectedSociety = $user->society_id;
        }

        $flats = $this->vehicleFlatsQuery($selectedSociety)->get();

        return view('vehicles.create', compact('societies', 'flats', 'selectedSociety'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'flat_id' => 'required|exists:flats,id',
            'registration_number' => 'required|string|max:20|unique:vehicles,registration_number',
            'vehicle_type' => 'required|in:car,bike,scooter,bicycle,other',
            'make' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
        ];

        // Super admin needs to select society
        if ($user->isSuperAdmin()) {
            $rules['society_id'] = 'required|exists:societies,id';
        }

        $validated = $request->validate($rules);

        $expectedSocietyId = $user->isSuperAdmin()
            ? (int) $validated['society_id']
            : (int) $user->society_id;

        $flat = $this->findManagedFlat((int) $validated['flat_id'], $expectedSocietyId);
        
        $vehicle = Vehicle::create([
            'flat_id' => $validated['flat_id'],
            'registration_number' => $validated['registration_number'],
            'vehicle_type' => $validated['vehicle_type'],
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
            'color' => $validated['color'] ?? null,
            'society_id' => $flat->tower->society_id,
            'created_by' => $user->id,
        ]);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle registered successfully.');
    }

    private function isSuperAdmin(): bool
    {
        return Auth::user()->isSuperAdmin();
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorizeManagedVehicle($vehicle);
        $vehicle->load(['flat.tower', 'flat.residents', 'creator']);

        return view('vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $user = Auth::user();
        $this->authorizeManagedVehicle($vehicle);

        $flats = $this->vehicleFlatsQuery($user->isSuperAdmin() ? $vehicle->society_id : $user->society_id)->get();

        return view('vehicles.edit', compact('vehicle', 'flats'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $user = Auth::user();
        $this->authorizeManagedVehicle($vehicle);

        $validated = $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'registration_number' => 'required|string|max:20|unique:vehicles,registration_number,' . $vehicle->id,
            'vehicle_type' => 'required|in:car,bike,scooter,bicycle,other',
            'make' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        $flat = $this->findManagedFlat(
            (int) $validated['flat_id'],
            $user->isSuperAdmin() ? null : (int) $user->society_id
        );

        $validated['society_id'] = $flat->tower->society_id;
        $vehicle->update($validated);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeManagedVehicle($vehicle);

        $vehicle->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    private function authorizeManagedVehicle(Vehicle $vehicle): void
    {
        $user = Auth::user();
        
        // Super admin can access all vehicles
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isResident()) {
            $hasAccess = $vehicle->flat()
                ->whereHas('residents', function ($residentQuery) use ($user) {
                    $residentQuery->where('users.id', $user->id)
                        ->wherePivot('is_active', true);
                })
                ->exists();

            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this vehicle.');
            }

            return;
        }

        if ($vehicle->society_id !== $user->society_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }
    }

    private function vehicleFlatsQuery(?int $societyId)
    {
        $query = Flat::query()
            ->with('tower')
            ->where('is_active', true);

        if ($societyId !== null) {
            $query->whereHas('tower', fn($q) => $q->where('society_id', $societyId));
        }

        return $query->orderBy('flat_number');
    }

    private function findManagedFlat(int $flatId, ?int $societyId): Flat
    {
        $query = Flat::query()
            ->with('tower')
            ->whereKey($flatId);

        if ($societyId !== null) {
            $query->whereHas('tower', fn($q) => $q->where('society_id', $societyId));
        }

        return $query->firstOrFail();
    }
}
