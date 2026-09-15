@extends('layouts.app')

@section('title', 'Maintenance Detail')
@section('page-title', 'Maintenance Detail')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                {{ $maintenance->flat->flat_number }} - {{ $maintenance->month }}
            </h1>
            <p class="text-sm text-gray-500">Maintenance record details</p>
        </div>

        <a href="{{ route('maintenance.index') }}"
           class="btn btn-secondary">
            ← Back to Maintenance
        </a>
    </div>

    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <p class="text-xs text-gray-500">Base Amount</p>
            <p class="text-lg font-semibold">Rs. {{ number_format($maintenance->amount, 2) }}</p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Late Fee</p>
            <p class="text-lg font-semibold">Rs. {{ number_format($maintenance->late_fee, 2) }}</p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Total Due</p>
            <p class="text-lg font-bold">Rs. {{ number_format($maintenance->total_due, 2) }}</p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Paid</p>
            <p class="text-lg font-semibold text-green-600">
                Rs. {{ number_format($maintenance->amount_paid, 2) }}
            </p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Balance</p>
            <p class="text-lg font-semibold text-yellow-600">
                Rs. {{ number_format($maintenance->balance_due, 2) }}
            </p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Status</p>
            <p class="text-lg font-semibold capitalize">
                {{ str_replace('_', ' ', $maintenance->status->value) }}
            </p>
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="text-lg font-semibold">Payment History</h2>

        {{-- x-data-table rather than a bespoke table, so each payment becomes a labelled
             card on phones instead of a row that has to be scrolled sideways. --}}
        <x-data-table :headers="['Date', 'Amount', 'Mode', 'Transaction ID', 'Remarks']"
                      :data="$maintenance->payments"
                      emptyMessage="No payments recorded yet.">
            @forelse($maintenance->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                    <td class="font-medium text-emerald-600">Rs. {{ number_format($payment->amount_paid, 2) }}</td>
                    <td>{{ $payment->payment_mode ? ucfirst(str_replace('_', ' ', $payment->payment_mode)) : '—' }}</td>
                    <td>{{ $payment->transaction_id ?: '—' }}</td>
                    <td>{{ $payment->remarks ?: '—' }}</td>
                </tr>
            @empty
            @endforelse
        </x-data-table>
    </div>

</div>
@endsection
