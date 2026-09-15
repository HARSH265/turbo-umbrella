@extends('layouts.app')

@section('title', 'Amenity Bookings')
@section('page-title', 'Bookings - ' . $amenity->name)

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Bookings</h1>
            <p class="page-header-subtitle">{{ $amenity->name }} - All bookings</p>
        </div>
        <a href="{{ route('amenities.show', $amenity) }}" class="btn btn-secondary">Back to Amenity</a>
    </div>

    <x-card>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>
            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Date', 'Time', 'Flat', 'User', 'Purpose', 'Charge', 'Status', 'Actions']" :data="$bookings">
        @forelse($bookings as $booking)
        <tr>
            <td>{{ $booking->booking_date->format('d M Y') }}</td>
            <td>{{ $booking->start_time }} - {{ $booking->end_time }}</td>
            <td>{{ $booking->flat->flat_number }}</td>
            <td>{{ $booking->user->name }}</td>
            <td>{{ $booking->purpose ?? '-' }}</td>
            <td>{{ $booking->total_charge ? '₹' . number_format($booking->total_charge) : '-' }}</td>
            <td>
                <span class="badge 
                    @if($booking->status === 'confirmed') badge-success
                    @elseif($booking->status === 'pending') badge-warning
                    @elseif($booking->status === 'cancelled') badge-danger
                    @else badge-gray @endif">
                    {{ ucfirst($booking->status) }}
                </span>
            </td>
            <td>
                @if($booking->status === 'confirmed' && $booking->booking_date >= now()->toDateString())
                <form method="POST" action="{{ route('amenities.cancel-booking', $booking) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Cancel this booking?')">Cancel</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8">No bookings found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection