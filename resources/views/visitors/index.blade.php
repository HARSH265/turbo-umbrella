@extends('layouts.app')

@section('title', 'Visitors')
@section('page-title', 'Visitor Logs')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Visitor Logs</h1>
            <p class="page-header-subtitle">Track visitor entry and exit</p>
        </div>
        @can('visitors.create')
        <a href="{{ route('visitors.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Register Visitor
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('visitors.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="approval_status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Inside</label>
                <select name="inside" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('inside') === '1' ? 'selected' : '' }}>Currently Inside</option>
                    <option value="0" {{ request('inside') === '0' ? 'selected' : '' }}>Exited</option>
                </select>
            </div>
            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Visitor Name', 'Phone', 'Flat', 'Purpose', 'Entry', 'Exit', 'Status', 'Actions']" :data="$visitors">
        @forelse($visitors as $visitor)
        <tr>
            <td class="font-medium">{{ $visitor->name }}</td>
            <td>{{ $visitor->phone }}</td>
            <td>{{ $visitor->flat->full_name }}</td>
            <td>{{ $visitor->purpose }}</td>
            <td>{{ $visitor->entry_time?->format('d M, h:i A') }}</td>
            <td>{{ $visitor->exit_time?->format('d M, h:i A') ?? '—' }}</td>
            <td>
                <span class="badge
                    @if($visitor->approval_status === 'approved') badge-success
                    @elseif($visitor->approval_status === 'rejected') badge-danger
                    @else badge-warning @endif">
                    {{ ucfirst($visitor->approval_status) }}
                </span>
            </td>
            <td>
                <a href="{{ route('visitors.show', $visitor) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8">No visitor records found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection