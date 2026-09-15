@extends('layouts.app')

@section('title', 'Create Maintenance Policy')
@section('page-title', 'Create Maintenance Policy')

@section('content')
<div class="space-y-6 max-w-4xl">

    <h1 class="text-xl font-bold text-gray-900">Create Policy</h1>

    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('maintenance.policies.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(auth()->user()->isSuperAdmin())
                    <div class="md:col-span-2">
                        <label class="text-sm">Society</label>
                        <select name="society_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select Society</option>
                            @foreach($societies as $society)
                                <option value="{{ $society->id }}" {{ old('society_id') == $society->id ? 'selected' : '' }}>
                                    {{ $society->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="text-sm">Policy Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-sm">Billing Cycle</label>
                    <select name="billing_cycle" required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('billing_cycle') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="half_yearly" {{ old('billing_cycle') === 'half_yearly' ? 'selected' : '' }}>Half Yearly</option>
                        <option value="yearly" {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm">Calculation Type</label>
                    <select name="calculation_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="fixed" {{ old('calculation_type') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                        <option value="flat_type" {{ old('calculation_type') === 'flat_type' ? 'selected' : '' }}>Flat Type</option>
                        <option value="area_based" {{ old('calculation_type') === 'area_based' ? 'selected' : '' }}>Area Based</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm">Base Amount</label>
                    <input type="number" step="0.01" name="base_amount" value="{{ old('base_amount') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-sm">Late Fee Type</label>
                    <select name="late_fee_type"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">None</option>
                        <option value="fixed" {{ old('late_fee_type') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                        <option value="percentage" {{ old('late_fee_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm">Late Fee Value</label>
                    <input type="number" step="0.01" name="late_fee_value" value="{{ old('late_fee_value') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-sm">Grace Days</label>
                    <input type="number" name="grace_days" value="{{ old('grace_days', 0) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div class="flex items-center mt-6">
                    <input type="hidden" name="allow_partial_payment" value="0">
                    <input type="checkbox" name="allow_partial_payment" value="1"
                           {{ old('allow_partial_payment') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-900">
                    <span class="ml-2 text-sm">Allow Partial Payment</span>
                </div>

                <div>
                    <label class="text-sm">Effective From</label>
                    <input type="date" name="effective_from" value="{{ old('effective_from') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div class="md:col-span-2 border-t pt-6">
                    <h2 class="text-sm font-semibold text-gray-900">Flat Type Amounts</h2>
                    <p class="mt-1 text-xs text-gray-500">Required only when Calculation Type is set to Flat Type.</p>
                </div>

                @foreach(['1BHK', '2BHK', '3BHK', '4BHK', 'Penthouse'] as $flatType)
                    <div>
                        <label class="text-sm">{{ $flatType }} Amount</label>
                        <input type="number" step="0.01" name="type_amounts[{{ $flatType }}]"
                               value="{{ old('type_amounts.' . $flatType) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                <button type="submit"
                        class="px-6 py-2 bg-brand-900 text-white rounded-md hover:bg-brand-800 text-sm">
                    Create & Activate
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
