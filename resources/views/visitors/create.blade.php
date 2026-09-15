@extends('layouts.app')

@section('title', 'Register Visitor')
@section('page-title', 'Register Visitor')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Register New Visitor</h1>
            <p class="mt-1 text-sm text-gray-600">Enter visitor details for security logging</p>
        </div>

        <form method="POST" action="{{ route('visitors.store') }}">
            @csrf

            <div class="mb-6">
                <label for="flat_id" class="form-label">Select Flat</label>
                <select name="flat_id" id="flat_id" required class="form-select">
                    <option value="">Choose flat</option>
                    @foreach($flats as $flat)
                        <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>
                            {{ $flat->full_name }} - {{ $flat->tower->society->name }}
                        </option>
                    @endforeach
                </select>
                @error('flat_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Visitor Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="form-input">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label class="form-label">Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose') }}" required class="form-input">
                @error('purpose')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="form-label">Entry Time</label>
                <input type="datetime-local" name="entry_time" value="{{ old('entry_time', now()->format('Y-m-d\TH:i')) }}" required class="form-input">
                @error('entry_time')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" rows="3" class="form-textarea">{{ old('remarks') }}</textarea>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('visitors.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Register Visitor
                </button>
            </div>
        </form>
    </div>
</div>
@endsection