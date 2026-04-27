@extends('layouts.app')

@section('title', 'Flat Details')
@section('page-title', 'Flat Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('flats.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Back to Flats
        </a>
        <div class="flex items-center space-x-3">
            @can('flats.update')
            <a href="{{ route('flats.assign-residents', $flat) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Assign Resident
            </a>
            <a href="{{ route('flats.edit', $flat) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
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
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $flat->occupancy_status === 'occupied' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
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
                <div class="p-6 flex items-center justify-between hover:bg-gray-50">
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
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Primary Contact
                                </span>
                                @endif
                                <span class="text-xs text-gray-500">
                                    Since {{ \Carbon\Carbon::parse($resident->pivot->start_date)->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('users.show', $resident) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            View Profile
                        </a>
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

    <!-- Pending Maintenance -->
    @if($flat->pendingMaintenances->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Pending Maintenance</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Late Fee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($flat->pendingMaintenances as $maintenance)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($maintenance->month)->format('F Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ₹{{ number_format($maintenance->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            ₹{{ number_format($maintenance->late_fee, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            ₹{{ number_format($maintenance->total_due, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $maintenance->due_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $maintenance->status->value === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $maintenance->status->value)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('maintenance.show', $maintenance) }}" class="text-blue-600 hover:text-blue-800">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
                                @if($complaint->priority === 'urgent') bg-red-100 text-red-800
                                @elseif($complaint->priority === 'high') bg-orange-100 text-orange-800
                                @elseif($complaint->priority === 'medium') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($complaint->priority) }}
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
                            @if($complaint->status === 'open') bg-red-100 text-red-800
                            @elseif($complaint->status === 'in_progress') bg-yellow-100 text-yellow-800
                            @elseif($complaint->status === 'resolved') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($flat->visitors->take(5) as $visitor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $visitor->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $visitor->phone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $visitor->purpose }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $visitor->entry_time->format('d M, h:i A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($visitor->exit_time) bg-gray-100 text-gray-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ $visitor->exit_time ? 'Exited' : 'Inside' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Delete Flat
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
