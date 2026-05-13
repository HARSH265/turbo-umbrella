@extends('layouts.app')

@section('title', 'Maintenance Bills')
@section('page-title', 'Maintenance Bills')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Maintenance Bills</h1>
            <p class="page-header-subtitle">View and manage maintenance records</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <p class="stat-card-label">Total Billed</p>
            <p class="stat-card-value">₹{{ number_format($summary['total_billed']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Collected</p>
            <p class="stat-card-value stat-card-value-success">₹{{ number_format($summary['total_collected']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Outstanding</p>
            <p class="stat-card-value stat-card-value-warning">₹{{ number_format($summary['total_outstanding']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Overdue Bills</p>
            <p class="stat-card-value stat-card-value-danger">{{ $summary['overdue_count'] }}</p>
        </div>
    </div>

    <x-card>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Month" class="mb-1" />
                <input type="month" name="month" value="{{ request('month') }}" class="form-input">
            </div>
            <div>
                <x-input-label value="Status" class="mb-1" />
                <select name="status" class="form-select">
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

    <x-data-table :headers="['Flat', 'Month', 'Amount', 'Paid', 'Balance', 'Due Date', 'Status', 'Actions']" :data="$maintenances">
        @forelse($maintenances as $maintenance)
        <tr>
            <td class="font-medium">{{ $maintenance->flat->flat_number }}</td>
            <td>{{ $maintenance->month }}</td>
            <td class="font-medium">₹{{ number_format($maintenance->amount) }}</td>
            <td class="text-emerald-600">₹{{ number_format($maintenance->amount_paid) }}</td>
            <td class="font-medium">₹{{ number_format($maintenance->balance_due) }}</td>
            <td>{{ $maintenance->due_date?->format('d M Y') }}</td>
            <td>
                <span class="badge
                    @if($maintenance->status->value === 'paid') badge-success
                    @elseif($maintenance->status->value === 'overdue') badge-danger
                    @elseif($maintenance->status->value === 'partially_paid') badge-warning
                    @else badge-info @endif">
                    {{ str_replace('_', ' ', ucfirst($maintenance->status->value)) }}
                </span>
            </td>
            <td>
                <a href="{{ route('maintenance.show', $maintenance) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8">No maintenance records found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection