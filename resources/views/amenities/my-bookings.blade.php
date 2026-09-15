@extends('layouts.app')

@section('title', 'My Amenity Bookings')
@section('page-title', 'My Amenity Bookings')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">My Bookings</h1>
            <p class="page-header-subtitle">Track your upcoming amenity reservations</p>
        </div>
        <a href="{{ route('amenities.index') }}" class="btn btn-secondary">Back to Amenities</a>
    </div>

    <x-data-table :headers="['Amenity', 'Flat', 'Date', 'Time', 'Charge', 'Status', 'Actions']" :data="$bookings">
        @forelse($bookings as $booking)
        <tr>
            <td>
                <div class="font-medium text-gray-900">{{ $booking->amenity->name }}</div>
                <div class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $booking->amenity->type)) }}</div>
            </td>
            <td>{{ $booking->flat->flat_number }}</td>
            <td>{{ $booking->booking_date->format('d M Y') }}</td>
            <td>{{ $booking->start_time }} - {{ $booking->end_time }}</td>
            <td>{{ $booking->total_charge ? 'Rs ' . number_format($booking->total_charge) : 'Free' }}</td>
            <td>
                <span class="badge {{ $booking->status === 'confirmed' ? 'badge-success' : ($booking->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                    {{ ucfirst($booking->status) }}
                </span>
            </td>
            <td>
                <div class="flex items-center gap-3">
                    <a href="{{ route('amenities.show', $booking->amenity) }}" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">View Amenity</a>
                    @if($booking->status === 'confirmed' && $booking->booking_date >= now()->toDateString())
                    <form method="POST" action="{{ route('amenities.cancel-booking', $booking) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Cancel this booking?')">Cancel</button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">No upcoming bookings found.</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
