@extends('layouts.app')

@section('title', 'Record Payment')
@section('page-title', 'Record Payment')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                Payment - {{ $maintenance->flat->flat_number }} ({{ $maintenance->month }})
            </h1>
            <p class="text-sm text-gray-500">Record maintenance payment</p>
        </div>

        <a href="{{ route('maintenance.show', $maintenance) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <- Back to Maintenance
        </a>
    </div>

    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
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

    <div class="bg-white shadow rounded-lg p-6 space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-900">Payment Rules</h2>
        <p class="text-sm text-gray-600">
            @if ($canPartiallyPay)
                Partial payment is allowed for this society's active maintenance policy.
            @else
                Full balance payment is required for this society's active maintenance policy.
            @endif
        </p>
        <p class="text-sm text-gray-600">
            Current payable balance:
            <span class="font-semibold text-gray-900">Rs. {{ number_format($maintenance->balance_due, 2) }}</span>
        </p>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        @if ($isFullyPaid)
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-5">
                <p class="text-sm font-semibold text-green-800">This maintenance record is already fully paid.</p>
                <p class="mt-1 text-sm text-green-700">No further payment can be recorded from this page.</p>
            </div>
        @else
            <form method="POST" action="{{ route('maintenance.process-payment', $maintenance) }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm text-gray-600">Amount</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            max="{{ number_format($maintenance->balance_due, 2, '.', '') }}"
                            name="amount"
                            value="{{ old('amount', number_format($recommendedAmount, 2, '.', '')) }}"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <p class="mt-1 text-xs text-gray-500">
                            @if ($canPartiallyPay)
                                Enter any amount up to the outstanding balance.
                            @else
                                Enter the full outstanding balance only.
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Payment Mode</label>
                        <select name="payment_mode" required
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select</option>
                            <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="cheque" {{ old('payment_mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="online" {{ old('payment_mode') === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="upi" {{ old('payment_mode') === 'upi' ? 'selected' : '' }}>UPI</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Transaction ID</label>
                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Remarks</label>
                        <input type="text" name="remarks" value="{{ old('remarks') }}"
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
        @endif
    </div>

</div>
@endsection
