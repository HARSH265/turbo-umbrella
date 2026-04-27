@extends('layouts.app')

@section('title', 'Record Payment')
@section('page-title', 'Record Payment')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                Payment - {{ $maintenance->flat->flat_number }} ({{ $maintenance->month }})
            </h1>
            <p class="text-sm text-gray-500">Record maintenance payment</p>
        </div>

        <a href="{{ route('maintenance.show', $maintenance) }}"
           class="text-sm text-brand-900 hover:underline">
            Back
        </a>
    </div>

    {{-- Summary Card --}}
    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
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

    {{-- Payment Form --}}
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('maintenance.process-payment', $maintenance) }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="text-sm text-gray-600">Amount</label>
                    <input type="number" step="0.01" name="amount" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Payment Mode</label>
                    <select name="payment_mode" required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="online">Online</option>
                        <option value="upi">UPI</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Transaction ID</label>
                    <input type="text" name="transaction_id"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Remarks</label>
                    <input type="text" name="remarks"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

            </div>

            <div class="mt-6">
                <button type="submit"
                        class="px-6 py-2 bg-brand-900 text-white rounded-md hover:bg-brand-800 text-sm">
                    Record Payment
                </button>
            </div>
        </form>
    </div>

</div>
@endsection