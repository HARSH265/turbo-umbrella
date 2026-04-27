@extends('layouts.app')

@section('title', 'Maintenance Detail')
@section('page-title', 'Maintenance Detail')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                {{ $maintenance->flat->flat_number }} - {{ $maintenance->month }}
            </h1>
            <p class="text-sm text-gray-500">Maintenance record details</p>
        </div>

        <a href="{{ route('maintenance.index') }}"
           class="text-sm text-brand-900 hover:underline">
            Back
        </a>
    </div>

    {{-- Summary Card --}}
    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

        <div>
            <p class="text-xs text-gray-500">Base Amount</p>
            <p class="text-lg font-semibold">₹{{ number_format($maintenance->amount,2) }}</p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Late Fee</p>
            <p class="text-lg font-semibold">₹{{ number_format($maintenance->late_fee,2) }}</p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Total Due</p>
            <p class="text-lg font-bold">₹{{ number_format($maintenance->total_due,2) }}</p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Paid</p>
            <p class="text-lg font-semibold text-green-600">
                ₹{{ number_format($maintenance->amount_paid,2) }}
            </p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Balance</p>
            <p class="text-lg font-semibold text-yellow-600">
                ₹{{ number_format($maintenance->balance_due,2) }}
            </p>
        </div>

        <div>
            <p class="text-xs text-gray-500">Status</p>
            <p class="text-lg font-semibold capitalize">
                {{ $maintenance->status->value }}
            </p>
        </div>

    </div>

    {{-- Payment History --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Payment History</h2>

        <table class="min-w-full text-sm divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Amount</th>
                    <th class="px-4 py-2 text-left">Mode</th>
                    <th class="px-4 py-2 text-left">Transaction ID</th>
                    <th class="px-4 py-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($maintenance->payments as $payment)
                <tr>
                    <td class="px-4 py-2">{{ $payment->payment_date?->format('d M Y') }}</td>
                    <td class="px-4 py-2 text-green-600">₹{{ number_format($payment->amount_paid,2) }}</td>
                    <td class="px-4 py-2">{{ $payment->payment_mode }}</td>
                    <td class="px-4 py-2">{{ $payment->transaction_id }}</td>
                    <td class="px-4 py-2">{{ $payment->remarks }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">
                        No payments recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection