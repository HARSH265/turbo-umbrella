@extends('layouts.app')

@section('title', 'Flats')
@section('page-title', 'Flats')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Flats</h1>
            <p class="page-header-subtitle">Manage residential units</p>
        </div>
        @can('flats.create')
        <a href="{{ route('flats.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Flat
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('flats.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tower</label>
                <select name="tower_id" class="form-select">
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
                <select name="type" class="form-select">
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
                <select name="occupancy_status" class="form-select">
                    <option value="">All</option>
                    <option value="occupied" {{ request('occupancy_status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    <option value="vacant" {{ request('occupancy_status') === 'vacant' ? 'selected' : '' }}>Vacant</option>
                </select>
            </div>

            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Flat', 'Tower', 'Type', 'Floor', 'Status', 'Resident', 'Actions']" :data="$flats">
        @forelse($flats as $flat)
        <tr>
            <td class="font-medium">{{ $flat->flat_number }}</td>
            <td>{{ $flat->tower->name }}</td>
            <td>{{ $flat->type }}</td>
            <td>{{ $flat->floor_number }}</td>
            <td>
                <span class="badge {{ $flat->occupancy_status === 'occupied' ? 'badge-success' : 'badge-gray' }}">
                    {{ ucfirst($flat->occupancy_status) }}
                </span>
            </td>
            <td>{{ optional($flat->activeResidents->where('pivot.is_primary', true)->first())->name ?? '-' }}</td>
            <td>
                <a href="{{ route('flats.show', $flat) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">No flats found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection