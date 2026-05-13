@extends('layouts.app')

@section('title', 'Vehicle Details')
@section('page-title', 'Vehicle Details')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">{{ $vehicle->registration_number }}</h1>
            <p class="page-header-subtitle">Vehicle Details</p>
        </div>
        <div class="flex gap-3">
            @can('vehicles.update')
            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-secondary">Edit</a>
            @endcan
            @can('flats.view')
            <a href="{{ route('flats.show', $vehicle->flat) }}" class="btn btn-secondary">View Flat</a>
            @endcan
            <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </div>
                <p class="text-sm text-gray-500">Vehicle Type</p>
                <p class="text-lg font-semibold text-gray-900">{{ ucfirst($vehicle->vehicle_type) }}</p>
            </div>
        </x-card>

        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p class="text-sm text-gray-500">Flat</p>
                <p class="text-lg font-semibold text-gray-900">{{ $vehicle->flat->flat_number }}</p>
                <p class="text-sm text-gray-500">{{ $vehicle->flat->tower->name }}</p>
            </div>
        </x-card>

        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4
                    @if($vehicle->is_active) bg-green-100 @else bg-gray-100 @endif">
                    <svg class="w-8 h-8 @if($vehicle->is_active) text-green-600 @else text-gray-600 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-lg font-semibold @if($vehicle->is_active) text-green-600 @else text-gray-600 @endif">
                    {{ $vehicle->is_active ? 'Active' : 'Inactive' }}
                </p>
            </div>
        </x-card>
    </div>

    <x-card>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Vehicle Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500">Registration Number</p>
                <p class="font-medium text-gray-900">{{ $vehicle->registration_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Make / Model</p>
                <p class="font-medium text-gray-900">{{ $vehicle->make }} {{ $vehicle->model }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Color</p>
                <p class="font-medium text-gray-900">{{ $vehicle->color ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Registered On</p>
                <p class="font-medium text-gray-900">{{ $vehicle->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </x-card>

    @if($vehicle->flat->residents->count() > 0)
    <x-card>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registered Owner(s)</h3>
        <div class="space-y-3">
            @foreach($vehicle->flat->residents as $resident)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">{{ substr($resident->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $resident->name }}</p>
                        <p class="text-sm text-gray-500">{{ $resident->phone }}</p>
                    </div>
                </div>
                <span class="badge badge-info">{{ $resident->pivot->is_primary ? 'Primary' : 'Secondary' }}</span>
            </div>
            @endforeach
        </div>
    </x-card>
    @endif

    @can('vehicles.delete')
    <div class="border-t border-gray-200 pt-6">
        <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
            @csrf
            @method('DELETE')
            <x-danger-button type="submit">Delete Vehicle</x-danger-button>
        </form>
    </div>
    @endcan
</div>
@endsection
