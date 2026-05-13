@extends('layouts.app')

@section('title', $amenity->name)
@section('page-title', 'Amenity Details')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">{{ $amenity->name }}</h1>
            <p class="page-header-subtitle">{{ ucfirst(str_replace('_', ' ', $amenity->type)) }}</p>
        </div>
        <div class="flex gap-3">
            @if(Auth::user()->isSuperAdmin() || Auth::user()->isSocietyAdmin())
            <a href="{{ route('amenities.bookings', $amenity) }}" class="btn btn-secondary">All Bookings</a>
            @endif
            <a href="{{ route('amenities.my-bookings') }}" class="btn btn-secondary">My Bookings</a>
            @can('amenities.view')
            <a href="{{ route('amenities.book', $amenity) }}" class="btn btn-primary">Book Now</a>
            @endcan
            @can('amenities.update')
            <a href="{{ route('amenities.edit', $amenity) }}" class="btn btn-secondary">Edit</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p class="text-sm text-gray-500">Type</p>
                <p class="text-lg font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $amenity->type)) }}</p>
            </div>
        </x-card>

        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-500">Capacity</p>
                <p class="text-lg font-semibold text-gray-900">{{ $amenity->capacity ?? 'N/A' }}</p>
            </div>
        </x-card>

        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4
                    @if($amenity->is_active) bg-green-100 @else bg-gray-100 @endif">
                    <svg class="w-8 h-8 @if($amenity->is_active) text-green-600 @else text-gray-600 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-lg font-semibold @if($amenity->is_active) text-green-600 @else text-gray-600 @endif">
                    {{ $amenity->is_active ? 'Active' : 'Inactive' }}
                </p>
            </div>
        </x-card>
    </div>

    <x-card>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Amenity Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500">Description</p>
                <p class="font-medium text-gray-900">{{ $amenity->description ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Charge per Hour</p>
                <p class="font-medium text-gray-900">{{ $amenity->charge_per_hour ? '₹' . number_format($amenity->charge_per_hour) : 'Free' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Timing</p>
                <p class="font-medium text-gray-900">{{ $amenity->opening_time ?? 'N/A' }} - {{ $amenity->closing_time ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Advance Booking</p>
                <p class="font-medium text-gray-900">{{ $amenity->advance_booking_days ?? 7 }} days</p>
            </div>
        </div>
    </x-card>

    @if($canViewBookingDetails)
    <x-card>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Bookings</h3>
        @forelse($bookings as $booking)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
            <div>
                <p class="font-medium text-gray-900">{{ $booking->user->name }}</p>
                <p class="text-sm text-gray-500">{{ $booking->flat->flat_number }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-900">{{ $booking->booking_date->format('d M Y') }}</p>
                <p class="text-sm text-gray-500">{{ $booking->start_time }} - {{ $booking->end_time }}</p>
            </div>
            <span class="badge {{ $booking->status === 'confirmed' ? 'badge-success' : 'badge-warning' }}">
                {{ ucfirst($booking->status) }}
            </span>
        </div>
        @empty
        <p class="text-gray-500">No upcoming bookings</p>
        @endforelse
    </x-card>
    @endif
</div>
@endsection
