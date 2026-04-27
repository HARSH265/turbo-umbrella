@extends('layouts.app')

@section('title', 'Flats')
@section('page-title', 'Flats')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Flats</h1>
            <p class="mt-1 text-sm text-gray-600">Manage residential units</p>
        </div>
        @can('flats.create')
        <a href="{{ route('flats.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
            Add Flat
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('flats.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tower</label>
                <select name="tower_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Towers</option>
                    @foreach($towers as $tower)
                    <option value="{{ $tower->id }}" {{ request('tower_id') == $tower->id ? 'selected' : '' }}>
                        {{ $tower->society->name }} - {{ $tower->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Types</option>
                    <option value="1BHK" {{ request('type') === '1BHK' ? 'selected' : '' }}>1BHK</option>
                    <option value="2BHK" {{ request('type') === '2BHK' ? 'selected' : '' }}>2BHK</option>
                    <option value="3BHK" {{ request('type') === '3BHK' ? 'selected' : '' }}>3BHK</option>
                    <option value="4BHK" {{ request('type') === '4BHK' ? 'selected' : '' }}>4BHK</option>
                    <option value="Penthouse" {{ request('type') === 'Penthouse' ? 'selected' : '' }}>Penthouse</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Occupancy</label>
                <select name="occupancy_status" class="w-full border-gray-300 rounded-lg">
                    <option value="">All</option>
                    <option value="occupied" {{ request('occupancy_status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    <option value="vacant" {{ request('occupancy_status') === 'vacant' ? 'selected' : '' }}>Vacant</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Flats Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($flats->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">No flats found</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tower</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Floor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resident</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($flats as $flat)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $flat->flat_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $flat->tower->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $flat->type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $flat->floor_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $flat->occupancy_status === 'occupied' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($flat->occupancy_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ optional($flat->activeResidents->where('pivot.is_primary', true)->first())->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('flats.show', $flat) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                                View
                            </a>
                            @can('flats.update')
                            <a href="{{ route('flats.edit', $flat) }}" class="text-gray-600 hover:text-gray-800">
                                Edit
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t">
                {{ $flats->links() }}
            </div>
        @endif
    </div>
</div>
@endsection