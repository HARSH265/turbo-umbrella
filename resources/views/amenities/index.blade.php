@extends('layouts.app')

@section('title', 'Amenities')
@section('page-title', 'Amenities')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Amenities</h1>
            <p class="page-header-subtitle">Manage society amenities and facilities</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('amenities.my-bookings') }}" class="btn btn-secondary">My Bookings</a>
            @can('amenities.create')
            <a href="{{ route('amenities.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Amenity
            </a>
            @endcan
        </div>
    </div>

    <x-card>
        <form method="GET" action="{{ route('amenities.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="clubhouse" {{ request('type') === 'clubhouse' ? 'selected' : '' }}>Clubhouse</option>
                    <option value="pool" {{ request('type') === 'pool' ? 'selected' : '' }}>Swimming Pool</option>
                    <option value="gym" {{ request('type') === 'gym' ? 'selected' : '' }}>Gym</option>
                    <option value="tennis" {{ request('type') === 'tennis' ? 'selected' : '' }}>Tennis Court</option>
                    <option value="badminton" {{ request('type') === 'badminton' ? 'selected' : '' }}>Badminton Court</option>
                    <option value="party_hall" {{ request('type') === 'party_hall' ? 'selected' : '' }}>Party Hall</option>
                    <option value="garden" {{ request('type') === 'garden' ? 'selected' : '' }}>Garden</option>
                    <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($amenities as $amenity)
        <div class="card hover:shadow-md transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $amenity->name }}</h3>
                    <p class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $amenity->type)) }}</p>
                </div>
                <span class="badge {{ $amenity->is_active ? 'badge-success' : 'badge-gray' }}">
                    {{ $amenity->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <p class="text-sm text-gray-600 mb-4">{{ $amenity->description }}</p>

            <div class="space-y-2 text-sm text-gray-600 mb-4">
                @if($amenity->capacity)
                <p class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Capacity: {{ $amenity->capacity }} persons
                </p>
                @endif

                @if($amenity->charge_per_hour)
                <p class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ₹{{ number_format($amenity->charge_per_hour) }}/hour
                </p>
                @endif

                @if($amenity->opening_time && $amenity->closing_time)
                <p class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $amenity->opening_time }} - {{ $amenity->closing_time }}
                </p>
                @endif
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="{{ route('amenities.show', $amenity) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">
                    View Details →
                </a>
                @can('amenities.update')
                <a href="{{ route('amenities.edit', $amenity) }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </a>
                @endcan
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12">
            <p class="text-gray-500">No amenities found</p>
        </div>
        @endforelse
    </div>

    @if($amenities->hasPages())
    <div class="card p-4">
        {{ $amenities->links() }}
    </div>
    @endif
</div>
@endsection
