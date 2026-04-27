@extends('layouts.app')

@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('users.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Back to Users
        </a>
        @can('users.update')
        <a href="{{ route('users.edit', $user) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
            Edit User
        </a>
        @endcan
    </div>

    <!-- User Info Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start space-x-4">
            <div class="h-20 w-20 rounded-full bg-gray-900 flex items-center justify-center text-white text-2xl font-bold">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="text-gray-600">{{ $user->email }}</p>
                <div class="mt-2 flex items-center space-x-3">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $user->roles->pluck('name')->implode(', ') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Contact Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-600">Phone</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->phone }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-600">Email</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->email }}</dd>
                </div>
            </dl>
        </div>

        <!-- Account Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-600">Created</dt>
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
            </dl>
        </div>
    </div>

    <!-- Assigned Flats (for residents) -->
    @if($user->activeFlats->isNotEmpty())
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Assigned Flats</h2>
        <div class="space-y-3">
            @foreach($user->activeFlats as $flat)
            <div class="border rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $flat->full_name }} - {{ $flat->type }}</p>
                    <p class="text-sm text-gray-600">{{ $flat->tower->society->name }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ ucfirst($flat->pivot->relation_type) }}
                        @if($flat->pivot->is_primary)
                            <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Primary</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('flats.show', $flat) }}" class="text-blue-600 hover:text-blue-800">
                    View Flat →
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Complaints (for residents) -->
    @if($user->complaints->isNotEmpty())
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Complaints</h2>
        <div class="space-y-3">
            @foreach($user->complaints->take(5) as $complaint)
            <div class="border-l-4 {{ $complaint->status === 'resolved' ? 'border-green-500' : 'border-yellow-500' }} pl-4 py-2">
                <p class="font-medium text-gray-900">{{ $complaint->subject }}</p>
                <p class="text-sm text-gray-600">{{ $complaint->ticket_number }} - {{ ucfirst($complaint->status) }}</p>
                <p class="text-xs text-gray-500">{{ $complaint->created_at->format('d M Y') }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Actions -->
    @can('users.update')
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
        <div class="flex items-center space-x-4">
            <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 {{ $user->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg">
                    {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                </button>
            </form>

            @can('users.delete')
            @if(!$user->isSuperAdmin())
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        onclick="return confirm('Are you sure you want to delete this user?')"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Delete User
                </button>
            </form>
            @endif
            @endcan
        </div>
    </div>
    @endcan
</div>
@endsection
