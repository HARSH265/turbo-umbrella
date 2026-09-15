@extends('layouts.app')

@section('title', 'Create Amenity')
@section('page-title', 'Create Amenity')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Create Amenity</h1>
            <p class="page-header-subtitle">Add a new amenity to the society</p>
        </div>
    </div>

    <x-card>
        <form method="POST" action="{{ route('amenities.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(Auth::user()->isSuperAdmin())
                <div>
                    <x-input-label for="society_id" value="Select Society" />
                    <select name="society_id" id="society_id" class="form-select mt-1">
                        <option value="">Select Society</option>
                        @foreach($societies as $society)
                        <option value="{{ $society->id }}" {{ old('society_id') == $society->id ? 'selected' : '' }}>
                            {{ $society->name }}
                        </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('society_id')" class="mt-2" />
                </div>
                @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="type" value="Type" />
                    <select name="type" id="type" class="form-select mt-1">
                        <option value="">Select Type</option>
                        <option value="clubhouse" {{ old('type') === 'clubhouse' ? 'selected' : '' }}>Clubhouse</option>
                        <option value="pool" {{ old('type') === 'pool' ? 'selected' : '' }}>Swimming Pool</option>
                        <option value="gym" {{ old('type') === 'gym' ? 'selected' : '' }}>Gym</option>
                        <option value="tennis" {{ old('type') === 'tennis' ? 'selected' : '' }}>Tennis Court</option>
                        <option value="badminton" {{ old('type') === 'badminton' ? 'selected' : '' }}>Badminton Court</option>
                        <option value="party_hall" {{ old('type') === 'party_hall' ? 'selected' : '' }}>Party Hall</option>
                        <option value="garden" {{ old('type') === 'garden' ? 'selected' : '' }}>Garden</option>
                        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="description" value="Description" />
                    <textarea name="description" id="description" rows="3" class="form-input mt-1">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="capacity" value="Capacity (persons)" />
                    <x-text-input id="capacity" name="capacity" type="number" value="{{ old('capacity') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="charge_per_hour" value="Charge per Hour (₹)" />
                    <x-text-input id="charge_per_hour" name="charge_per_hour" type="number" step="0.01" value="{{ old('charge_per_hour') }}" class="mt-1" placeholder="0 for free" />
                    <x-input-error :messages="$errors->get('charge_per_hour')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="opening_time" value="Opening Time" />
                    <x-text-input id="opening_time" name="opening_time" type="time" value="{{ old('opening_time') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('opening_time')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="closing_time" value="Closing Time" />
                    <x-text-input id="closing_time" name="closing_time" type="time" value="{{ old('closing_time') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('closing_time')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="booking_duration" value="Default Booking Duration (minutes)" />
                    <x-text-input id="booking_duration" name="booking_duration" type="number" value="{{ old('booking_duration', 60) }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('booking_duration')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="advance_booking_days" value="Advance Booking (days)" />
                    <x-text-input id="advance_booking_days" name="advance_booking_days" type="number" value="{{ old('advance_booking_days', 7) }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('advance_booking_days')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="is_active" value="Status" />
                    <select name="is_active" id="is_active" class="form-select mt-1">
                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('amenities.index') }}" class="btn btn-secondary">Cancel</a>
                <x-primary-button type="submit">Create Amenity</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
@endsection