@extends('layouts.app')

@section('title', 'Maintenance Bills')
@section('page-title', 'Maintenance Bills')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Maintenance Bills</h1>
            <p class="text-sm text-gray-500">View and manage maintenance records</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500">Total Billed</p>
            <p class="text-xl font-bold text-gray-900">₹{{ number_format($summary['total_billed']) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500">Collected</p>
            <p class="text-xl font-bold text-green-600">₹{{ number_format($summary['total_collected']) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500">Outstanding</p>
            <p class="text-xl font-bold text-yellow-600">₹{{ number_format($summary['total_outstanding']) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500">Overdue Bills</p>
            <p class="text-xl font-bold text-red-600">{{ $summary['overdue_count'] }}</p>
        </div>
    </div>

    <x-card>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Month" class="mb-1" />
                <input type="month" name="month" value="{{ request('month') }}" class="w-full border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <x-input-label value="Status" class="mb-1" />
                <select name="status" class="w-full border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="partially_paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Partial</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Flat', 'Month', 'Amount', 'Paid', 'Balance', 'Due Date', 'Status', 'Actions']" :data="$maintenances" route="{{ auth()->user()->hasPermission('maintenance.create') ? route('maintenance.create') : null }}">
        @forelse($maintenances as $maintenance)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $maintenance->flat->flat_number }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $maintenance->month }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">₹{{ number_format($maintenance->amount) }}</td>
            <td class="px-6 py-4 text-sm text-green-600">₹{{ number_format($maintenance->amount_paid) }}</td>
            <td class="px-6 py-4 text-sm text-yellow-600 font-medium">₹{{ number_format($maintenance->balance_due) }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $maintenance->due_date?->format('d M Y') }}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($maintenance->status->value === 'paid') bg-green-100 text-green-800
                    @elseif($maintenance->status->value === 'overdue') bg-red-100 text-red-800
                    @elseif($maintenance->status->value === 'partially_paid') bg-yellow-100 text-yellow-800
                    @else bg-blue-100 text-blue-800 @endif">
                    {{ str_replace('_', ' ', ucfirst($maintenance->status->value)) }}
                </span>
            </td>
            <td class="px-6 py-4 text-sm">
                <a href="{{ route('maintenance.show', $maintenance) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="px-6 py-10 text-center text-gray-500">No maintenance records found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection