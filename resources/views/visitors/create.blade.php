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
                <label for="flat_id" class="block text-sm font-medium text-gray-700 mb-2">Select Flat</label>
                <select name="flat_id" id="flat_id" required class="w-full border-gray-300 rounded-lg">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visitor Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border-gray-300 rounded-lg">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full border-gray-300 rounded-lg">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose') }}" required class="w-full border-gray-300 rounded-lg">
                @error('purpose')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Entry Time</label>
                <input type="datetime-local" name="entry_time" value="{{ old('entry_time', now()->format('Y-m-d\TH:i')) }}" required class="w-full border-gray-300 rounded-lg">
                @error('entry_time')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <textarea name="remarks" rows="3" class="w-full border-gray-300 rounded-lg">{{ old('remarks') }}</textarea>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('visitors.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Register Visitor
                </button>
            </div>
        </form>
    </div>
</div>
@endsection