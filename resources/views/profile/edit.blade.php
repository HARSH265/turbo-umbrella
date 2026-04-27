@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Profile Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Profile Information</h2>
        
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border-gray-300 rounded-lg @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email (readonly) -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled
                           class="w-full border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500">Email cannot be changed</p>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required
                           class="w-full border-gray-300 rounded-lg @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role (readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <input type="text" value="{{ $user->roles->pluck('name')->implode(', ') }}" disabled
                           class="w-full border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                </div>
            </div>

            <!-- Flats (for residents) -->
            @if($user->isResident() && $user->activeFlats->isNotEmpty())
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">My Flats</label>
                <div class="space-y-2">
                    @foreach($user->activeFlats as $flat)
                    <div class="border rounded-lg p-3 bg-gray-50">
                        <p class="font-medium text-gray-900">{{ $flat->full_name }} - {{ $flat->type }}</p>
                        <p class="text-sm text-gray-600">{{ $flat->tower->society->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($flat->pivot->relation_type) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h2>
        
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="phone" value="{{ $user->phone }}">

            <div class="space-y-4">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password" name="current_password" id="current_password"
                           class="w-full border-gray-300 rounded-lg @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password" id="password"
                           class="w-full border-gray-300 rounded-lg @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    <!-- Account Status -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Status</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-600">Account Created</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->created_at->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600">Email Verified</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">
                    @if($user->email_verified_at)
                        <span class="text-green-600">✓ Verified</span>
                    @else
                        <span class="text-red-600">✗ Not Verified</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600">Account Status</dt>
                <dd class="mt-1 text-sm font-medium">
                    @if($user->is_active)
                        <span class="text-green-600">Active</span>
                    @else
                        <span class="text-red-600">Inactive</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection