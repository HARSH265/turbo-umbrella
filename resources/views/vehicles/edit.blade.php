@extends('layouts.app')

@section('title', 'Edit Vehicle')
@section('page-title', 'Edit Vehicle')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Edit Vehicle</h1>
            <p class="page-header-subtitle">Update vehicle details</p>
        </div>
    </div>

    <x-card>
        <form method="POST" action="{{ route('vehicles.update', $vehicle) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="flat_id" value="Select Flat" />
                    <select name="flat_id" id="flat_id" class="form-select mt-1">
                        <option value="">Select Flat</option>
                        @foreach($flats as $flat)
                        <option value="{{ $flat->id }}" {{ old('flat_id', $vehicle->flat_id) == $flat->id ? 'selected' : '' }}>
                            {{ $flat->flat_number }} - {{ $flat->tower->name }}
                        </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('flat_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="registration_number" value="Registration Number" />
                    <x-text-input id="registration_number" name="registration_number" type="text" 
                        value="{{ old('registration_number', $vehicle->registration_number) }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="vehicle_type" value="Vehicle Type" />
                    <select name="vehicle_type" id="vehicle_type" class="form-select mt-1">
                        <option value="car" {{ old('vehicle_type', $vehicle->vehicle_type) === 'car' ? 'selected' : '' }}>Car</option>
                        <option value="bike" {{ old('vehicle_type', $vehicle->vehicle_type) === 'bike' ? 'selected' : '' }}>Bike</option>
                        <option value="scooter" {{ old('vehicle_type', $vehicle->vehicle_type) === 'scooter' ? 'selected' : '' }}>Scooter</option>
                        <option value="bicycle" {{ old('vehicle_type', $vehicle->vehicle_type) === 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                        <option value="other" {{ old('vehicle_type', $vehicle->vehicle_type) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    <x-input-error :messages="$errors->get('vehicle_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="make" value="Make (Brand)" />
                    <x-text-input id="make" name="make" type="text" 
                        value="{{ old('make', $vehicle->make) }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('make')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="model" value="Model" />
                    <x-text-input id="model" name="model" type="text" 
                        value="{{ old('model', $vehicle->model) }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('model')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="color" value="Color" />
                    <x-text-input id="color" name="color" type="text" 
                        value="{{ old('color', $vehicle->color) }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('color')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="is_active" value="Status" />
                    <select name="is_active" id="is_active" class="form-select mt-1">
                        <option value="1" {{ old('is_active', $vehicle->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $vehicle->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-secondary">Cancel</a>
                <x-primary-button type="submit">Update Vehicle</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
@endsection