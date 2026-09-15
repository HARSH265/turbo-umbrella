@extends('layouts.app')

@section('title', 'Tower Details')
@section('page-title', 'Tower Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('towers.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Back to Towers
        </a>
        @can('societies.update')
        <a href="{{ route('towers.edit', $tower) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
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
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $tower->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
        
        @if($tower->flats->isEmpty())
            <div class="p-12 text-center text-gray-500">
                No flats in this tower yet
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flat Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Floor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($tower->flats as $flat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $flat->flat_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $flat->floor_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $flat->type }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $flat->occupancy_status === 'occupied' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($flat->occupancy_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('flats.show', $flat) }}" class="text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
