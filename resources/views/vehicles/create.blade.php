@extends('layouts.app')

@section('title', 'Register Vehicle')
@section('page-title', 'Register Vehicle')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Register Vehicle</h1>
            <p class="page-header-subtitle">Add a new vehicle to the society</p>
        </div>
    </div>

    <x-card>
        <form method="GET" action="{{ route('vehicles.create') }}" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @if(Auth::user()->isSuperAdmin())
            <div>
                <x-input-label for="society_id" value="Select Society" />
                <select name="society_id" id="society_id" class="form-select mt-1" onchange="this.form.submit()">
                    <option value="">Select Society</option>
                    @foreach($societies as $society)
                    <option value="{{ $society->id }}" {{ $selectedSociety == $society->id ? 'selected' : '' }}>
                        {{ $society->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>

        <form method="POST" action="{{ route('vehicles.store') }}" class="space-y-6">
            @csrf
            @if(Auth::user()->isSuperAdmin())
                <input type="hidden" name="society_id" value="{{ $selectedSociety }}">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="flat_id" value="Select Flat" />
                    <select name="flat_id" id="flat_id" class="form-select mt-1">
                        <option value="">Select Flat</option>
                        @forelse($flats as $flat)
                        <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>
                            {{ $flat->flat_number }} - {{ $flat->tower->name }}
                        </option>
                        @empty
                        <option value="">No flats available</option>
                        @endforelse
                    </select>
                    <x-input-error :messages="$errors->get('flat_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="registration_number" value="Registration Number" />
                    <x-text-input id="registration_number" name="registration_number" type="text" 
                        value="{{ old('registration_number') }}" placeholder="e.g. DL 01 AB 1234" class="mt-1" />
                    <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="vehicle_type" value="Vehicle Type" />
                    <select name="vehicle_type" id="vehicle_type" class="form-select mt-1">
                        <option value="">Select Type</option>
                        <option value="car" {{ old('vehicle_type') === 'car' ? 'selected' : '' }}>Car</option>
                        <option value="bike" {{ old('vehicle_type') === 'bike' ? 'selected' : '' }}>Bike</option>
                        <option value="scooter" {{ old('vehicle_type') === 'scooter' ? 'selected' : '' }}>Scooter</option>
                        <option value="bicycle" {{ old('vehicle_type') === 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                        <option value="other" {{ old('vehicle_type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    <x-input-error :messages="$errors->get('vehicle_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="make" value="Make (Brand)" />
                    <x-text-input id="make" name="make" type="text" 
                        value="{{ old('make') }}" placeholder="e.g. Maruti, Honda" class="mt-1" />
                    <x-input-error :messages="$errors->get('make')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="model" value="Model" />
                    <x-text-input id="model" name="model" type="text" 
                        value="{{ old('model') }}" placeholder="e.g. Swift, City" class="mt-1" />
                    <x-input-error :messages="$errors->get('model')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="color" value="Color" />
                    <x-text-input id="color" name="color" type="text" 
                        value="{{ old('color') }}" placeholder="e.g. White, Black" class="mt-1" />
                    <x-input-error :messages="$errors->get('color')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Cancel</a>
                <x-primary-button type="submit">Register Vehicle</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
@endsection