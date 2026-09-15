@extends('layouts.app')

@section('title', 'Add Flat')
@section('page-title', 'Add Flat')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Add New Flat</h1>

        <form method="POST" action="{{ route('flats.store') }}">
            @csrf

            <div class="mb-6">
                <label class="form-label">Tower <span class="text-red-500">*</span></label>
                <select name="tower_id" required class="form-select">
                    <option value="">Select tower</option>
                    @foreach($towers as $tower)
                        <option value="{{ $tower->id }}" {{ old('tower_id') == $tower->id ? 'selected' : '' }}>
                            {{ $tower->society->name }} - {{ $tower->name }}
                        </option>
                    @endforeach
                </select>
                @error('tower_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Flat Number <span class="text-red-500">*</span></label>
                    <input type="text" name="flat_number" value="{{ old('flat_number') }}" required class="form-input" placeholder="e.g., 101">
                    @error('flat_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Floor Number <span class="text-red-500">*</span></label>
                    <input type="number" name="floor_number" value="{{ old('floor_number') }}" required min="0" class="form-input">
                    @error('floor_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="form-select">
                        <option value="">Select type</option>
                        <option value="1BHK" {{ old('type') === '1BHK' ? 'selected' : '' }}>1BHK</option>
                        <option value="2BHK" {{ old('type') === '2BHK' ? 'selected' : '' }}>2BHK</option>
                        <option value="3BHK" {{ old('type') === '3BHK' ? 'selected' : '' }}>3BHK</option>
                        <option value="4BHK" {{ old('type') === '4BHK' ? 'selected' : '' }}>4BHK</option>
                        <option value="Penthouse" {{ old('type') === 'Penthouse' ? 'selected' : '' }}>Penthouse</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Carpet Area (sq.ft)</label>
                    <input type="number" name="carpet_area" value="{{ old('carpet_area') }}" step="0.01" class="form-input">
                    @error('carpet_area')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Occupancy Status <span class="text-red-500">*</span></label>
                    <select name="occupancy_status" required class="form-select">
                        <option value="vacant" {{ old('occupancy_status') === 'vacant' ? 'selected' : '' }}>Vacant</option>
                        <option value="occupied" {{ old('occupancy_status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    </select>
                    @error('occupancy_status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('flats.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Create Flat
                </button>
            </div>
        </form>
    </div>
</div>
@endsection