@extends('layouts.app')

@section('title', 'Vehicles')
@section('page-title', 'Vehicle Management')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Vehicles</h1>
            <p class="page-header-subtitle">Manage registered vehicles in society</p>
        </div>
        @can('vehicles.create')
        <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Register Vehicle
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('vehicles.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Flat</label>
                <select name="flat_id" class="form-select">
                    <option value="">All Flats</option>
                    @foreach($flats as $flat)
                    <option value="{{ $flat->id }}" {{ request('flat_id') == $flat->id ? 'selected' : '' }}>
                        {{ $flat->flat_number }} - {{ $flat->tower->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                <select name="vehicle_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="car" {{ request('vehicle_type') === 'car' ? 'selected' : '' }}>Car</option>
                    <option value="bike" {{ request('vehicle_type') === 'bike' ? 'selected' : '' }}>Bike</option>
                    <option value="scooter" {{ request('vehicle_type') === 'scooter' ? 'selected' : '' }}>Scooter</option>
                    <option value="bicycle" {{ request('vehicle_type') === 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                    <option value="other" {{ request('vehicle_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Registration number"
                       class="form-input">
            </div>

            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Registration No.', 'Type', 'Make/Model', 'Color', 'Flat', 'Status', 'Actions']" :data="$vehicles">
        @forelse($vehicles as $vehicle)
        <tr>
            <td class="font-bold">{{ $vehicle->registration_number }}</td>
            <td>
                <span class="badge badge-info">
                    {{ ucfirst($vehicle->vehicle_type) }}
                </span>
            </td>
            <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
            <td>{{ $vehicle->color ?? '-' }}</td>
            <td>{{ $vehicle->flat->flat_number }}</td>
            <td>
                <span class="badge {{ $vehicle->is_active ? 'badge-success' : 'badge-gray' }}">
                    {{ $vehicle->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td>
                <a href="{{ route('vehicles.show', $vehicle) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">No vehicles found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection