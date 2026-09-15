@extends('layouts.app')

@section('title', 'Tower Details')
@section('page-title', 'Tower Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('towers.index') }}"
            class="btn btn-secondary">
            ← Back to Towers
        </a>
        @can('societies.update')
        <a href="{{ route('towers.edit', $tower) }}" class="btn btn-primary">
            Edit Tower
        </a>
        @endcan
    </div>

    <!-- Tower Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $tower->name }}</h1>
                <p class="mt-1 text-gray-600">{{ $tower->society->name }}</p>
            </div>
            <span class="badge {{ $tower->is_active ? 'badge-success' : 'badge-danger' }}">
                {{ $tower->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600">Total Floors</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $tower->total_floors }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Flats</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['total_flats'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Occupied Flats</p>
                <p class="mt-1 text-2xl font-bold text-green-600">{{ $stats['occupied_flats'] }}</p>
            </div>
        </div>
    </div>

    <!-- Flats List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Flats in this Tower</h2>
            @can('flats.create')
            <a href="{{ route('flats.create') }}?tower_id={{ $tower->id }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                + Add Flat
            </a>
            @endcan
        </div>
        
        <x-data-table :headers="['Flat Number', 'Floor', 'Type', 'Status', 'Actions']"
                      :data="$tower->flats"
                      emptyMessage="No flats in this tower yet">
            @forelse($tower->flats as $flat)
                <tr>
                    <td class="font-medium">{{ $flat->flat_number }}</td>
                    <td>{{ $flat->floor_number }}</td>
                    <td>{{ $flat->type }}</td>
                    <td>
                        <span class="badge {{ $flat->occupancy_status === 'occupied' ? 'badge-success' : 'badge-gray' }}">
                            {{ ucfirst($flat->occupancy_status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="{{ route('flats.show', $flat) }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
            @empty
            @endforelse
        </x-data-table>
    </div>
</div>
@endsection
