@extends('layouts.app')

@section('title', 'Edit Amenity')
@section('page-title', 'Edit Amenity')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Edit Amenity</h1>
            <p class="page-header-subtitle">Update amenity details</p>
        </div>
    </div>

    <x-card>
        <form method="POST" action="{{ route('amenities.update', $amenity) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" value="{{ old('name', $amenity->name) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="type" value="Type" />
                    <select name="type" id="type" class="form-select mt-1">
                        <option value="clubhouse" {{ old('type', $amenity->type) === 'clubhouse' ? 'selected' : '' }}>Clubhouse</option>
                        <option value="pool" {{ old('type', $amenity->type) === 'pool' ? 'selected' : '' }}>Swimming Pool</option>
                        <option value="gym" {{ old('type', $amenity->type) === 'gym' ? 'selected' : '' }}>Gym</option>
                        <option value="tennis" {{ old('type', $amenity->type) === 'tennis' ? 'selected' : '' }}>Tennis Court</option>
                        <option value="badminton" {{ old('type', $amenity->type) === 'badminton' ? 'selected' : '' }}>Badminton Court</option>
                        <option value="party_hall" {{ old('type', $amenity->type) === 'party_hall' ? 'selected' : '' }}>Party Hall</option>
                        <option value="garden" {{ old('type', $amenity->type) === 'garden' ? 'selected' : '' }}>Garden</option>
                        <option value="other" {{ old('type', $amenity->type) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="description" value="Description" />
                    <textarea name="description" rows="3" class="form-input mt-1">{{ old('description', $amenity->description) }}</textarea>
                </div>

                <div>
                    <x-input-label for="capacity" value="Capacity" />
                    <x-text-input id="capacity" name="capacity" type="number" value="{{ old('capacity', $amenity->capacity) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="charge_per_hour" value="Charge per Hour (₹)" />
                    <x-text-input id="charge_per_hour" name="charge_per_hour" type="number" step="0.01" value="{{ old('charge_per_hour', $amenity->charge_per_hour) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="opening_time" value="Opening Time" />
                    <x-text-input id="opening_time" name="opening_time" type="time" value="{{ old('opening_time', $amenity->opening_time) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="closing_time" value="Closing Time" />
                    <x-text-input id="closing_time" name="closing_time" type="time" value="{{ old('closing_time', $amenity->closing_time) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="booking_duration" value="Default Booking Duration (minutes)" />
                    <x-text-input id="booking_duration" name="booking_duration" type="number" value="{{ old('booking_duration', $amenity->booking_duration) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="advance_booking_days" value="Advance Booking (days)" />
                    <x-text-input id="advance_booking_days" name="advance_booking_days" type="number" value="{{ old('advance_booking_days', $amenity->advance_booking_days) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="cancellation_hours" value="Cancellation Notice (hours)" />
                    <x-text-input id="cancellation_hours" name="cancellation_hours" type="number" value="{{ old('cancellation_hours', $amenity->cancellation_hours) }}" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="is_active" value="Status" />
                    <select name="is_active" id="is_active" class="form-select mt-1">
                        <option value="1" {{ old('is_active', $amenity->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $amenity->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('amenities.show', $amenity) }}" class="btn btn-secondary">Cancel</a>
                <x-primary-button type="submit">Update Amenity</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
@endsection