@extends('layouts.app')

@section('title', 'Flat Details')
@section('page-title', 'Flat Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="page-header">
        <a href="{{ route('flats.index') }}"
            class="btn btn-secondary">
            ← Back to Flats
        </a>
        <div class="page-header-actions">
            @can('flats.update')
            <a href="{{ route('flats.assign-residents', $flat) }}" class="btn btn-primary">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Assign Resident
            </a>
            <a href="{{ route('flats.edit', $flat) }}" class="btn btn-primary">
                Edit Flat
            </a>
            @endcan
        </div>
    </div>

    <!-- Flat Info Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $flat->full_name }}</h1>
                <p class="mt-1 text-gray-600">{{ $flat->tower->society->name }}</p>
            </div>
            <span class="badge {{ $flat->occupancy_status === 'occupied' ? 'badge-success' : 'badge-gray' }}">
                {{ ucfirst($flat->occupancy_status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-gray-600">Tower</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ $flat->tower->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Floor</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ $flat->floor_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Type</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ $flat->type }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Carpet Area</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ $flat->carpet_area ? $flat->carpet_area . ' sq.ft' : '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Residents -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Residents</h2>
        </div>

        @if($flat->activeResidents->isEmpty())
            <div class="p-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-gray-900 font-medium">No residents assigned</p>
                <p class="text-sm text-gray-500 mt-1">Click "Assign Resident" to add residents to this flat</p>
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($flat->activeResidents as $resident)
                <div class="p-6 flex flex-wrap items-center justify-between gap-4 hover:bg-gray-50">
                    <div class="flex items-center space-x-4">
                        <div class="h-12 w-12 rounded-full bg-gray-900 flex items-center justify-center text-white font-semibold">
                            {{ substr($resident->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $resident->name }}</p>
                            <p class="text-sm text-gray-600">{{ $resident->email }}</p>
                            <p class="text-sm text-gray-500">{{ $resident->phone }}</p>
                            <div class="mt-1 flex items-center space-x-2">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($resident->pivot->relation_type) }}
                                </span>
                                @if($resident->pivot->is_primary)
                                <span class="badge badge-success">
                                    Primary Contact
                                </span>
                                @endif
                                <span class="text-xs text-gray-500">
                                    Since {{ \Carbon\Carbon::parse($resident->pivot->start_date)->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @can('users.view')
                        <a href="{{ route('users.show', $resident) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            View Profile
                        </a>
                        @endcan
                        @can('flats.update')
                        <form method="POST" action="{{ route('flats.remove-resident', [$flat, $resident]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('Remove this resident from the flat?')"
                                    class="text-red-600 hover:text-red-800 text-sm">
                                Remove
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Vehicles -->
    @if($flat->vehicles->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Vehicles</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($flat->vehicles as $vehicle)
            <div class="p-6 flex flex-wrap items-center justify-between gap-4 hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $vehicle->registration_number }}</p>
                        <p class="text-sm text-gray-600">{{ ucfirst($vehicle->vehicle_type) }} - {{ $vehicle->make }} {{ $vehicle->model }}</p>
                        <p class="text-sm text-gray-500">{{ $vehicle->color }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @can('vehicles.view')
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                        View Vehicle
                    </a>
                    @endcan
                    <span class="badge {{ $vehicle->is_active ? 'badge-success' : 'badge-gray' }}">
                        {{ $vehicle->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Pending Maintenance -->
    @if($flat->pendingMaintenances->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Pending Maintenance</h2>
        </div>
        <div class="px-6 pb-6">
            <x-data-table :headers="['Month', 'Amount', 'Late Fee', 'Total', 'Due Date', 'Status', 'Action']"
                          :data="$flat->pendingMaintenances"
                          emptyMessage="Nothing outstanding">
                @forelse($flat->pendingMaintenances as $maintenance)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($maintenance->month)->format('F Y') }}</td>
                        <td>₹{{ number_format($maintenance->amount, 2) }}</td>
                        <td class="text-red-600">₹{{ number_format($maintenance->late_fee, 2) }}</td>
                        <td class="font-semibold">₹{{ number_format($maintenance->total_due, 2) }}</td>
                        <td>{{ $maintenance->due_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge {{ $maintenance->status->value === 'overdue' ? 'badge-danger' : 'badge-warning' }}">
                                {{ ucfirst(str_replace('_', ' ', $maintenance->status->value)) }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('maintenance.show', $maintenance) }}" class="btn btn-ghost btn-sm">View</a>
                        </td>
                    </tr>
                @empty
                @endforelse
            </x-data-table>
        </div>
    </div>
    @endif

    <!-- Recent Complaints -->
    @if($flat->complaints->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Recent Complaints</h2>
            <a href="{{ route('complaints.index') }}?flat_id={{ $flat->id }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All →
            </a>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($flat->complaints as $complaint)
            <div class="p-6 hover:bg-gray-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $complaint->subject }}</h3>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                @if($complaint->priority->value === 'urgent') bg-red-100 text-red-800
                                @elseif($complaint->priority->value === 'high') bg-orange-100 text-orange-800
                                @elseif($complaint->priority->value === 'medium') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($complaint->priority->value) }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ Str::limit($complaint->description, 100) }}</p>
                        <div class="mt-2 flex items-center text-xs text-gray-500">
                            <span class="mr-4">{{ $complaint->ticket_number }}</span>
                            <span>{{ $complaint->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="ml-4 flex items-center space-x-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($complaint->status->value === 'open') bg-red-100 text-red-800
                            @elseif($complaint->status->value === 'in_progress') bg-yellow-100 text-yellow-800
                            @elseif($complaint->status->value === 'resolved') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $complaint->status->value)) }}
                        </span>
                        <a href="{{ route('complaints.show', $complaint) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            View
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Visitors -->
    @if($flat->visitors->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Recent Visitors</h2>
            <a href="{{ route('visitors.index') }}?flat_id={{ $flat->id }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All →
            </a>
        </div>
        <div class="px-6 pb-6">
            <x-data-table :headers="['Name', 'Phone', 'Purpose', 'Entry Time', 'Status']"
                          :data="$flat->visitors->take(5)"
                          emptyMessage="No recent visitors">
                @forelse($flat->visitors->take(5) as $visitor)
                    <tr>
                        <td class="font-medium">{{ $visitor->name }}</td>
                        <td>{{ $visitor->phone }}</td>
                        <td>{{ $visitor->purpose }}</td>
                        <td>{{ $visitor->entry_time->format('d M, h:i A') }}</td>
                        <td>
                            <span class="badge {{ $visitor->exit_time ? 'badge-gray' : 'badge-success' }}">
                                {{ $visitor->exit_time ? 'Exited' : 'Inside' }}
                            </span>
                        </td>
                    </tr>
                @empty
                @endforelse
            </x-data-table>
        </div>
    </div>
    @endif

    <!-- Delete Flat -->
    @can('flats.delete')
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Danger Zone</h2>
        <p class="text-sm text-gray-600 mb-4">Once you delete this flat, there is no going back. Please be certain.</p>
        <form method="POST" action="{{ route('flats.destroy', $flat) }}" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" 
                    onclick="return confirm('Are you sure you want to delete this flat? This action cannot be undone.')"
                    class="btn btn-danger">
                Delete Flat
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
