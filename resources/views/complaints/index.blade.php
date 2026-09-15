@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Complaints</h1>
            <p class="page-header-subtitle">Manage resident complaints and requests</p>
        </div>
        @can('complaints.create')
        <a href="{{ route('complaints.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Complaint
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('complaints.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label value="Status" class="mb-1" />
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\ComplaintStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ ucfirst($status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Search" class="mb-1" />
                <x-text-input name="search" value="{{ request('search') }}" placeholder="Search ticket # or subject..." class="w-full" />
            </div>
            <div>
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Ticket', 'Subject', 'Resident', 'Priority', 'Status', 'Action']" :data="$complaints">
        @forelse($complaints as $complaint)
        <tr>
            <td class="font-bold">{{ $complaint->ticket_number }}</td>
            <td>
                <div class="font-medium">{{ Str::limit($complaint->subject, 35) }}</div>
                <div class="text-xs text-gray-500">{{ $complaint->category }}</div>
            </td>
            <td>
                <div>{{ $complaint->user->name }}</div>
                <div class="text-xs text-gray-500">{{ $complaint->flat->full_name }}</div>
            </td>
            <td>
                <span class="badge
                    @if($complaint->priority->value === 'urgent') badge-danger
                    @elseif($complaint->priority->value === 'high') badge-warning
                    @elseif($complaint->priority->value === 'medium') badge-info
                    @else badge-gray @endif">
                    {{ ucfirst($complaint->priority->value) }}
                </span>
            </td>
            <td>
                <span class="badge
                    @if($complaint->status->value === 'open') badge-info
                    @elseif($complaint->status->value === 'in_progress') badge-warning
                    @elseif($complaint->status->value === 'resolved') badge-success
                    @else badge-gray @endif">
                    {{ str_replace('_', ' ', ucfirst($complaint->status->value)) }}
                </span>
            </td>
            <td>
                <a href="{{ route('complaints.show', $complaint) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6">No complaints found for the selected filters.</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection