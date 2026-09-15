@extends('layouts.app')

@section('title', 'Add Tower')
@section('page-title', 'Add Tower')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Add New Tower</h1>

        <form method="POST" action="{{ route('towers.store') }}">
            @csrf

            <div class="mb-6">
                <label class="form-label">Society <span class="text-red-500">*</span></label>
                <select name="society_id" required class="form-select">
                    <option value="">Select society</option>
                    @foreach($societies as $society)
                        <option value="{{ $society->id }}" {{ old('society_id') == $society->id ? 'selected' : '' }}>
                            {{ $society->name }}
                        </option>
                    @endforeach
                </select>
                @error('society_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Tower Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g., Tower A">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Total Floors <span class="text-red-500">*</span></label>
                    <input type="number" name="total_floors" value="{{ old('total_floors') }}" required min="1" max="100" class="form-input">
                    @error('total_floors')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('towers.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Create Tower
                </button>
            </div>
        </form>
    </div>
</div>
@endsection