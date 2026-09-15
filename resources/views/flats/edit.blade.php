@extends('layouts.app')

@section('title', 'Edit Flat')
@section('page-title', 'Edit Flat')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Flat</h1>

        <form method="POST" action="{{ route('flats.update', $flat) }}">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tower <span class="text-red-500">*</span></label>
                <select name="tower_id" required class="w-full border-gray-300 rounded-lg">
                    @foreach($towers as $tower)
                        <option value="{{ $tower->id }}" {{ old('tower_id', $flat->tower_id) == $tower->id ? 'selected' : '' }}>
                            {{ $tower->society->name }} - {{ $tower->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Flat Number <span class="text-red-500">*</span></label>
                    <input type="text" name="flat_number" value="{{ old('flat_number', $flat->flat_number) }}" required class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Floor Number <span class="text-red-500">*</span></label>
                    <input type="number" name="floor_number" value="{{ old('floor_number', $flat->floor_number) }}" min="0" required class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full border-gray-300 rounded-lg">
                        @foreach(['1BHK', '2BHK', '3BHK', '4BHK', 'Penthouse'] as $type)
                            <option value="{{ $type }}" {{ old('type', $flat->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Carpet Area (sq.ft)</label>
                    <input type="number" name="carpet_area" value="{{ old('carpet_area', $flat->carpet_area) }}" step="0.01" class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Occupancy Status <span class="text-red-500">*</span></label>
                    <select name="occupancy_status" required class="w-full border-gray-300 rounded-lg">
                        <option value="vacant" {{ old('occupancy_status', $flat->occupancy_status) === 'vacant' ? 'selected' : '' }}>Vacant</option>
                        <option value="occupied" {{ old('occupancy_status', $flat->occupancy_status) === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    </select>
                </div>

                <div class="flex items-center pt-8">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $flat->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Flat is active</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('flats.show', $flat) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Update Flat
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
