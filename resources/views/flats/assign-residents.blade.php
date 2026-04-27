@extends('layouts.app')

@section('title', 'Assign Resident')
@section('page-title', 'Assign Resident to Flat')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Assign Resident to {{ $flat->full_name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $flat->tower->society->name }} - {{ $flat->type }}</p>
        </div>

        <!-- Current Residents -->
        @if($flat->activeResidents->isNotEmpty())
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Current Residents:</h3>
            <ul class="space-y-2">
                @foreach($flat->activeResidents as $resident)
                <li class="flex items-center justify-between">
                    <div>
                        <span class="font-medium">{{ $resident->name }}</span>
                        <span class="text-sm text-gray-600">({{ ucfirst($resident->pivot->relation_type) }})</span>
                        @if($resident->pivot->is_primary)
                        <span class="ml-2 px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">Primary</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('flats.remove-resident', [$flat, $resident]) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm"
                                onclick="return confirm('Remove this resident?')">
                            Remove
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Assign Form -->
        <form method="POST" action="{{ route('flats.store-resident', $flat) }}">
            @csrf

            <!-- Select User -->
            <div class="mb-6">
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select User <span class="text-red-500">*</span>
                </label>
                <select name="user_id" id="user_id" required
                        class="w-full border-gray-300 rounded-lg @error('user_id') border-red-500 @enderror">
                    <option value="">Choose a user</option>
                    @foreach($availableUsers as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Don't see the user? <a href="{{ route('users.create') }}" class="text-blue-600 hover:text-blue-800">Create new user</a>
                </p>
            </div>

            <!-- Relation Type -->
            <div class="mb-6">
                <label for="relation_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Relation Type <span class="text-red-500">*</span>
                </label>
                <select name="relation_type" id="relation_type" required
                        class="w-full border-gray-300 rounded-lg @error('relation_type') border-red-500 @enderror">
                    <option value="">Select type</option>
                    <option value="owner" {{ old('relation_type') === 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="tenant" {{ old('relation_type') === 'tenant' ? 'selected' : '' }}>Tenant</option>
                    <option value="family_member" {{ old('relation_type') === 'family_member' ? 'selected' : '' }}>Family Member</option>
                </select>
                @error('relation_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Start Date -->
            <div class="mb-6">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Start Date <span class="text-red-500">*</span>
                </label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required
                       class="w-full border-gray-300 rounded-lg @error('start_date') border-red-500 @enderror">
                @error('start_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Date (for tenants) -->
            <div class="mb-6">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    End Date (Optional - For Tenants)
                </label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                       class="w-full border-gray-300 rounded-lg @error('end_date') border-red-500 @enderror">
                @error('end_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Primary Contact -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_primary" value="1" {{ old('is_primary') ? 'checked' : '' }}
                           class="rounded border-gray-300">
                    <span class="ml-2 text-sm text-gray-700">Set as primary contact for this flat</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Primary contact receives all notifications for this flat</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('flats.show', $flat) }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Assign Resident
                </button>
            </div>
        </form>
    </div>
</div>
@endsection