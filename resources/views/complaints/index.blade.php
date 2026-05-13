@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Complaints</h1>
    </div>

    <x-card>
        <form method="GET" action="{{ route('complaints.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label value="Status" class="mb-1" />
                <select name="status" class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
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

    <x-data-table :headers="['Ticket', 'Subject', 'Resident', 'Priority', 'Status', 'Action']" :data="$complaints" route="{{ route('complaints.create') }}" createText="New Complaint">
        @forelse($complaints as $complaint)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $complaint->ticket_number }}</td>
            <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-800">{{ Str::limit($complaint->subject, 35) }}</div>
                <div class="text-xs text-gray-500">{{ $complaint->category }}</div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-700">{{ $complaint->user->name }}</div>
                <div class="text-xs text-gray-500">{{ $complaint->flat->full_name }}</div>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($complaint->priority->value === 'urgent') bg-red-100 text-red-800
                    @elseif($complaint->priority->value === 'high') bg-orange-100 text-orange-800
                    @elseif($complaint->priority->value === 'medium') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst($complaint->priority->value) }}
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($complaint->status->value === 'open') bg-blue-100 text-blue-800
                    @elseif($complaint->status->value === 'in_progress') bg-yellow-100 text-yellow-800
                    @elseif($complaint->status->value === 'resolved') bg-green-100 text-green-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ str_replace('_', ' ', ucfirst($complaint->status->value)) }}
                </span>
            </td>
            <td class="px-6 py-4 text-right">
                <a href="{{ route('complaints.show', $complaint) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-800">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No complaints found for the selected filters.</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection