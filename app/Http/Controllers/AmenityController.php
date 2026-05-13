<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\AmenityBooking;
use App\Models\Flat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AmenityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:amenities.view')->only(['index', 'show', 'bookings']);
        $this->middleware('permission:amenities.create')->only(['create', 'store']);
        $this->middleware('permission:amenities.view')->only(['createBooking', 'storeBooking', 'myBookings', 'cancelBooking']);
        $this->middleware('permission:amenities.update')->only(['edit', 'update']);
        $this->middleware('permission:amenities.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Amenity::query();

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('society_id', Auth::user()->society_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $amenities = $query->latest()->paginate(20)->withQueryString();

        return view('amenities.index', compact('amenities'));
    }

    public function create()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isSocietyAdmin()) {
            abort(403, 'Only administrators can create amenities.');
        }
        
        $societies = \App\Models\Society::active()->orderBy('name')->get();
        return view('amenities.create', compact('societies'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        $rules = [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => 'required|in:clubhouse,pool,gym,tennis,badminton,party_hall,garden,other',
            'capacity' => 'nullable|integer|min:1',
            'opening_time' => 'nullable',
            'closing_time' => 'nullable',
            'booking_duration' => 'nullable|integer|min:30',
            'advance_booking_days' => 'nullable|integer|min:1',
            'cancellation_hours' => 'nullable|integer|min:1',
            'charge_per_hour' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];

        // Super admin must select society, society admin uses their own
        if ($user->isSuperAdmin()) {
            $rules['society_id'] = 'required|exists:societies,id';
        }

        $validated = $request->validate($rules);

        // Set society_id for society admin
        if ($user->society_id && !$user->isSuperAdmin()) {
            $validated['society_id'] = (int) $user->society_id;
        }
        
        // Use DB insert instead of model create
        $amenityId = \DB::table('amenities')->insertGetId($validated);
        $amenity = Amenity::find($amenityId);
        
        logger('CREATED amenity: id=' . $amenity->id . ', society_id=' . $amenity->society_id . ', toArray: ', $amenity->toArray());

        return redirect()->route('amenities.index')
            ->with('success', 'Amenity created successfully.');
    }

    public function show(Amenity $amenity)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            if ($amenity->society_id != $user->society_id) {
                abort(403, 'Unauthorized access to this amenity.');
            }
        }
        
        $canViewBookingDetails = $user->isSuperAdmin() || $user->isSocietyAdmin();

        $bookings = $amenity->bookings()
            ->when($canViewBookingDetails, fn($query) => $query->with(['user', 'flat']))
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->take(10)
            ->get();

        return view('amenities.show', compact('amenity', 'bookings', 'canViewBookingDetails'));
    }

    public function edit(Amenity $amenity)
    {
        $this->authorizeManagedAmenity($amenity);

        return view('amenities.edit', compact('amenity'));
    }

    public function update(Request $request, Amenity $amenity)
    {
        $this->authorizeManagedAmenity($amenity);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => 'required|in:clubhouse,pool,gym,tennis,badminton,party_hall,garden,other',
            'capacity' => 'nullable|integer|min:1',
            'opening_time' => 'nullable',
            'closing_time' => 'nullable',
            'booking_duration' => 'nullable|integer|min:30',
            'advance_booking_days' => 'nullable|integer|min:1',
            'cancellation_hours' => 'nullable|integer|min:1',
            'charge_per_hour' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $amenity->update($validated);

        return redirect()->route('amenities.show', $amenity)
            ->with('success', 'Amenity updated successfully.');
    }

    public function destroy(Amenity $amenity)
    {
        $this->authorizeManagedAmenity($amenity);

        $amenity->delete();

        return redirect()->route('amenities.index')
            ->with('success', 'Amenity deleted successfully.');
    }

    public function bookings(Request $request, Amenity $amenity)
    {
        $this->authorizeManagedAmenity($amenity);

        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isSocietyAdmin()) {
            abort(403, 'Unauthorized');
        }

        $query = $amenity->bookings()
            ->with(['user', 'flat'])
            ->orderByDesc('booking_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('amenities.bookings', compact('amenity', 'bookings'));
    }

    public function createBooking(Amenity $amenity)
    {
        $user = Auth::user();

        $flats = Flat::query()
            ->whereHas('tower', fn($q) => $q->where('society_id', $user->society_id))
            ->where('is_active', true)
            ->with('tower');

        if ($user->isResident()) {
            $flats->whereHas('residents', function ($query) use ($user) {
                $query->where('users.id', $user->id)
                    ->wherePivot('is_active', true);
            });
        }

        $flats = $flats->orderBy('flat_number')->get();

        return view('amenities.book', compact('amenity', 'flats'));
    }

    public function storeBooking(Request $request, Amenity $amenity)
    {
        $validated = $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $this->authorizeManagedAmenity($amenity);

        $amenityModel = Amenity::findOrFail($amenity->id);

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $date = $validated['booking_date'];

        if (!$amenityModel->isAvailable($date, $startTime, $endTime)) {
            return back()->withErrors(['error' => 'This time slot is already booked.']);
        }

        $hours = (strtotime($endTime) - strtotime($startTime)) / 3600;
        $totalCharge = $amenityModel->charge_per_hour ? $hours * $amenityModel->charge_per_hour : 0;

        $flat = Flat::with('tower')->findOrFail($validated['flat_id']);

        if ($flat->tower->society_id !== $amenity->society_id) {
            abort(403, 'Unauthorized access to this amenity.');
        }

        if (!$user->isSuperAdmin() && $flat->tower->society_id !== $user->society_id) {
            abort(403, 'Unauthorized flat selection.');
        }

        if ($user->isResident()) {
            $hasAccessToFlat = $user->activeFlats()
                ->where('flats.id', $flat->id)
                ->exists();

            if (!$hasAccessToFlat) {
                abort(403, 'Unauthorized flat selection.');
            }
        }

        AmenityBooking::create([
            'amenity_id' => $amenity->id,
            'flat_id' => $validated['flat_id'],
            'user_id' => Auth::id(),
            'booking_date' => $validated['booking_date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'],
            'total_charge' => $totalCharge,
            'status' => 'confirmed',
            'society_id' => $flat->tower->society_id,
        ]);

        return redirect()->route('amenities.my-bookings')
            ->with('success', 'Amenity booked successfully.');
    }

    public function myBookings()
    {
        $bookings = AmenityBooking::where('user_id', Auth::id())
            ->with(['amenity', 'flat'])
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('amenities.my-bookings', compact('bookings'));
    }

    public function cancelBooking(AmenityBooking $booking)
    {
        $user = Auth::user();

        if ($booking->user_id !== $user->id && !$user->isSocietyAdmin()) {
            abort(403, 'Unauthorized');
        }

        if (!$user->isSuperAdmin() && $user->isSocietyAdmin()) {
            $booking->loadMissing('amenity');

            if (!$booking->amenity || $booking->amenity->society_id !== $user->society_id) {
                abort(403, 'Unauthorized');
            }
        }

        if ($booking->status === 'cancelled') {
            return back()->withErrors(['error' => 'Booking is already cancelled.']);
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled successfully.');
    }

    private function authorizeManagedAmenity(Amenity $amenity): void
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            return;
        }
        
        $amenitySocietyId = $amenity->society_id;
        $userSocietyId = $user->society_id;
        
        // Both must have same society_id
        if ($amenitySocietyId != $userSocietyId) {
            abort(403, 'Unauthorized access to this amenity.');
        }
    }
}
