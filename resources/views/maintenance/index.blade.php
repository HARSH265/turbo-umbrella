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

            @if (auth()->user()->hasPermission('maintenance.create'))
                <a href="{{ route('maintenance.create') }}"
                    class="px-4 py-2 bg-brand-900 text-white rounded-md hover:bg-brand-800 text-sm font-medium">
                    Generate Maintenance
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow rounded-lg p-4">
                <p class="text-xs text-gray-500">Total Billed</p>
                <p class="text-xl font-bold text-gray-900">₹{{ number_format($summary['total_billed']) }}</p>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <p class="text-xs text-gray-500">Collected</p>
                <p class="text-xl font-bold text-green-600">₹{{ number_format($summary['total_collected']) }}</p>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <p class="text-xs text-gray-500">Outstanding</p>
                <p class="text-xl font-bold text-yellow-600">₹{{ number_format($summary['total_outstanding']) }}</p>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <p class="text-xs text-gray-500">Overdue Bills</p>
                <p class="text-xl font-bold text-red-600">{{ $summary['overdue_count'] }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>
                    <label class="text-xs text-gray-500">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">All</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="partially_paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Partial</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-4 py-2 bg-brand-900 text-white rounded-md hover:bg-brand-800 text-sm">
                        Filter
                    </button>
                </div>

            </form>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Flat</th>
                        <th class="px-4 py-3 text-left">Month</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Late Fee</th>
                        <th class="px-4 py-3 text-left">Total Due</th>
                        <th class="px-4 py-3 text-left">Paid</th>
                        <th class="px-4 py-3 text-left">Balance</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse($maintenances as $maintenance)
                        <tr class="{{ $maintenance->status->value === 'overdue' ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-3">
                                {{ $maintenance->flat->flat_number ?? 'N/A' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $maintenance->month)->format('M Y') }}
                            </td>

                            <td class="px-4 py-3">₹{{ number_format($maintenance->amount, 2) }}</td>
                            <td class="px-4 py-3">₹{{ number_format($maintenance->late_fee, 2) }}</td>
                            <td class="px-4 py-3 font-semibold">₹{{ number_format($maintenance->total_due, 2) }}</td>
                            <td class="px-4 py-3 text-green-600">₹{{ number_format($maintenance->amount_paid, 2) }}</td>
                            <td class="px-4 py-3 text-yellow-600">₹{{ number_format($maintenance->balance_due, 2) }}</td>

                            <td class="px-4 py-3">
                                @switch($maintenance->status->value)
                                    @case('paid')
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Paid</span>
                                        @break
                                    @case('overdue')
                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">Overdue</span>
                                        @break
                                    @case('partially_paid')
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Partial</span>
                                        @break
                                    @default
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">Unpaid</span>
                                @endswitch
                            </td>

                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('maintenance.show', $maintenance) }}"
                                    class="text-brand-900 hover:underline text-xs">
                                    View
                                </a>

                                @if (
                                    $maintenance->status->value !== 'paid'
                                    && (
                                        auth()->user()->isResident()
                                        || auth()->user()->hasRole('society-admin')
                                        || auth()->user()->isSuperAdmin()
                                        || auth()->user()->hasPermission('maintenance.update')
                                    )
                                )
                                    <a href="{{ route('maintenance.payment', $maintenance) }}"
                                        class="text-accent-600 hover:underline text-xs">
                                        Payment
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                No maintenance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4">
                {{ $maintenances->links() }}
            </div>
        </div>

    </div>
@endsection
