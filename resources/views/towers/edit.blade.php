@extends('layouts.app')

@section('title', 'Edit Tower')
@section('page-title', 'Edit Tower')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Tower</h1>

        <form method="POST" action="{{ route('towers.update', $tower) }}">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label class="form-label">Society <span class="text-red-500">*</span></label>
                <select name="society_id" required class="form-select">
                    @foreach($societies as $society)
                        <option value="{{ $society->id }}" {{ old('society_id', $tower->society_id) == $society->id ? 'selected' : '' }}>
                            {{ $society->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Tower Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $tower->name) }}" required class="form-input">
                </div>

                <div>
                    <label class="form-label">Total Floors <span class="text-red-500">*</span></label>
                    <input type="number" name="total_floors" value="{{ old('total_floors', $tower->total_floors) }}" min="1" max="100" required class="form-input">
                </div>
            </div>

            <div class="mt-6 flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $tower->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Tower is active</label>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('towers.show', $tower) }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Update Tower
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
