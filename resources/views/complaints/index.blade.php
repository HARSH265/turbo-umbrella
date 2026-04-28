@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header: Bold but Clean -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-brand-900 tracking-tight">Complaints</h1>
        <x-primary-button onclick="window.location='{{ route('complaints.create') }}'">
            + New Complaint
        </x-primary-button>
    </div>

    <!-- Filter Card: Better Spacing -->
    <x-card>
        <form method="GET" action="{{ route('complaints.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label value="Status" class="mb-1" />
                <select name="status" class="w-full border-brand-200 rounded-lg text-sm focus:ring-brand-900/10">
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
                <x-secondary-button type="submit" class="w-full justify-center">
                    Filter Results
                </x-secondary-button>
            </div>
        </form>
    </x-card>

    <!-- Table: The "Gold" Standard Layout -->
    <div class="bg-white rounded-xl border border-brand-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-brand-100">
                <thead class="bg-brand-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-brand-400 uppercase tracking-widest">Ticket</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-brand-400 uppercase tracking-widest">Subject</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-brand-400 uppercase tracking-widest">Resident</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-brand-400 uppercase tracking-widest">Priority</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-brand-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-brand-400 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @forelse($complaints as $complaint)
                    <tr class="hover:bg-brand-50/30 transition">
                        <td class="px-6 py-5 text-sm font-bold text-brand-900">{{ $complaint->ticket_number }}</td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-semibold text-brand-800">{{ Str::limit($complaint->subject, 35) }}</div>
                            <div class="text-xs text-brand-400">{{ $complaint->category }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm text-brand-600">{{ $complaint->user->name }}</div>
                            <div class="text-[11px] text-brand-400">{{ $complaint->flat->full_name }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <x-badge :color="$complaint->priority->color()">
                                {{ ucfirst($complaint->priority->value) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-5">
                            <x-badge :color="$complaint->status->color()">
                                {{ str_replace('_', ' ', strtoupper($complaint->status->value)) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <a href="{{ route('complaints.show', $complaint) }}" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-brand-400">
                            No complaints found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complaints->hasPages())
            <div class="border-t border-brand-100 px-6 py-4">
                {{ $complaints->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
