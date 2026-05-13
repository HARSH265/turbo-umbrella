@extends('layouts.app')

@section('title', 'Book Amenity')
@section('page-title', 'Book ' . $amenity->name)

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Book {{ $amenity->name }}</h1>
            <p class="page-header-subtitle">{{ ucfirst(str_replace('_', ' ', $amenity->type)) }}</p>
        </div>
    </div>

    <x-card>
        <form method="POST" action="{{ route('amenities.store-booking', $amenity) }}" class="space-y-6">
            @csrf

            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-emerald-700">Charge per hour</p>
                        <p class="text-xl font-bold text-emerald-800">{{ $amenity->charge_per_hour ? '₹' . number_format($amenity->charge_per_hour) : 'Free' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-emerald-700">Timing</p>
                        <p class="text-sm font-medium text-emerald-800">{{ $amenity->opening_time ?? 'N/A' }} - {{ $amenity->closing_time ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="flat_id" value="Select Flat" />
                    <select name="flat_id" id="flat_id" class="form-select mt-1">
                        <option value="">Select Flat</option>
                        @foreach($flats as $flat)
                        <option value="{{ $flat->id }}">{{ $flat->flat_number }} - {{ $flat->tower->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('flat_id')" class="mt-2" />
                    @if(Auth::user()->isResident())
                    <p class="mt-2 text-sm text-gray-500">Only your assigned flat is available for booking.</p>
                    @endif
                </div>

                <div>
                    <x-input-label for="booking_date" value="Booking Date" />
                    <input type="date" name="booking_date" id="booking_date" class="form-input mt-1" min="{{ now()->toDateString() }}" max="{{ now()->addDays($amenity->advance_booking_days ?? 7)->toDateString() }}" />
                    <x-input-error :messages="$errors->get('booking_date')" class="mt-2" />
                    <p class="mt-2 text-sm text-gray-500">Advance booking allowed for up to {{ $amenity->advance_booking_days ?? 7 }} days.</p>
                </div>

                <div>
                    <x-input-label for="start_time" value="Start Time" />
                    <input type="time" name="start_time" id="start_time" class="form-input mt-1" />
                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                    <p class="mt-2 text-sm text-gray-500">Default slot length is {{ $amenity->booking_duration ?? 60 }} minutes.</p>
                </div>

                <div>
                    <x-input-label for="end_time" value="End Time" />
                    <input type="time" name="end_time" id="end_time" class="form-input mt-1" />
                    <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                    <p class="mt-2 text-sm text-gray-500">Choose a time within {{ $amenity->opening_time ?? 'opening hours' }} and {{ $amenity->closing_time ?? 'closing hours' }}.</p>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="purpose" value="Purpose" />
                    <x-text-input id="purpose" name="purpose" type="text" value="{{ old('purpose') }}" class="mt-1" placeholder="e.g. Birthday party, meeting" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="notes" value="Notes (Optional)" />
                    <textarea name="notes" id="notes" rows="2" class="form-input mt-1">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('amenities.show', $amenity) }}" class="btn btn-secondary">Cancel</a>
                <x-primary-button type="submit">Confirm Booking</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
