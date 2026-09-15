@extends('layouts.app')

@section('title', 'Policy — ' . $policy->template->name)
@section('page-title', 'Maintenance Policy')

@php
    $template = $policy->template;
    $isFlatType = $template->calculation_type === \App\Enums\CalculationType::FLAT_TYPE;
    $typeAmounts = $template->type_amounts ?? [];
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    <div class="page-header print:hidden">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ $template->name }}</h1>
            <p class="page-header-subtitle">
                {{ $policy->society?->name ?? 'Society policy' }}
            </p>
        </div>
        <div class="page-header-actions">
            <button type="button" onclick="window.print()" class="btn btn-secondary">Print</button>
            <a href="{{ route('maintenance.policies.index') }}" class="btn btn-secondary">Back to Policies</a>
        </div>
    </div>

    {{-- The document itself. Deliberately plain and printable: a society committee
         circulates this, so it has to read like a notice rather than a settings screen. --}}
    <article class="card overflow-hidden">

        <header class="border-b border-gray-200 bg-gray-50/70 px-6 py-6 sm:px-10 sm:py-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                        Maintenance Charge Policy
                    </p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl">
                        {{ $template->name }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $policy->society?->name ?? 'Society' }}
                        @if($policy->society?->code)
                            <span class="text-gray-400">· {{ $policy->society->code }}</span>
                        @endif
                    </p>
                </div>
                <span class="shrink-0 self-start rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider
                    {{ $policy->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                    {{ $policy->is_active ? 'In force' : 'Superseded' }}
                </span>
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-gray-200 pt-5 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Effective from</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $policy->effective_from?->format('d M Y') ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Billing cycle</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                        {{ ucfirst(str_replace('_', ' ', $template->billing_cycle->value)) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Grace period</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $template->grace_days }} {{ Str::plural('day', $template->grace_days) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Part payment</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $template->isPartialPaymentAllowed() ? 'Permitted' : 'Not permitted' }}
                    </dd>
                </div>
            </dl>
        </header>

        <div class="space-y-8 px-6 py-8 sm:px-10">

            @if($template->description)
                <section>
                    <h3 class="doc-heading">1. Purpose</h3>
                    <p class="doc-body">{{ $template->description }}</p>
                </section>
            @endif

            <section>
                <h3 class="doc-heading">{{ $template->description ? '2' : '1' }}. How the charge is calculated</h3>

                @if($isFlatType)
                    <p class="doc-body">
                        The monthly charge is set by flat type. Each flat is billed the rate listed
                        against its type for every billing cycle.
                    </p>

                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-700">Flat type</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-700">Amount per cycle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($typeAmounts as $type => $amount)
                                    <tr @class(['bg-amber-50/40' => in_array($type, $flatTypes, true)])>
                                        <td class="px-4 py-2.5 text-gray-900">
                                            {{ $type }}
                                            @if(in_array($type, $flatTypes, true))
                                                <span class="ml-2 text-[10px] font-bold uppercase tracking-wider text-amber-700">in this society</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-gray-900">
                                            &#8377;{{ number_format((float) $amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-center text-gray-500">
                                            No per-type rates recorded.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="doc-body">
                        Every flat is billed the same flat rate of
                        <strong class="font-semibold text-gray-900">&#8377;{{ number_format((float) $template->base_amount, 2) }}</strong>
                        per {{ strtolower(str_replace('_', ' ', $template->billing_cycle->value)) }} cycle,
                        regardless of flat type or area.
                    </p>
                @endif
            </section>

            <section>
                <h3 class="doc-heading">{{ $template->description ? '3' : '2' }}. Due date and late payment</h3>
                <p class="doc-body">
                    Payment is due within
                    <strong class="font-semibold text-gray-900">{{ $template->grace_days }} {{ Str::plural('day', $template->grace_days) }}</strong>
                    of the bill being raised.

                    @if($template->late_fee_type && $template->late_fee_value)
                        After that the bill is marked overdue and a late fee of
                        @if($template->late_fee_type === \App\Enums\LateFeeType::PERCENTAGE)
                            <strong class="font-semibold text-gray-900">{{ rtrim(rtrim(number_format((float) $template->late_fee_value, 2), '0'), '.') }}%</strong>
                            of the outstanding amount applies.
                        @else
                            a flat <strong class="font-semibold text-gray-900">&#8377;{{ number_format((float) $template->late_fee_value, 2) }}</strong> applies.
                        @endif
                    @else
                        No late fee is levied under this policy.
                    @endif
                </p>
                <p class="doc-body mt-3">
                    {{ $template->isPartialPaymentAllowed()
                        ? 'Part payments are accepted; the balance remains outstanding until cleared.'
                        : 'Part payments are not accepted. Each bill must be settled in full.' }}
                </p>
            </section>

            @if($template->inclusions)
                <section>
                    <h3 class="doc-heading">What the charge covers</h3>
                    <p class="doc-body">{{ $template->inclusions }}</p>
                </section>
            @endif

            @if($template->payment_terms)
                <section>
                    <h3 class="doc-heading">Payment terms</h3>
                    <p class="doc-body">{{ $template->payment_terms }}</p>
                </section>
            @endif

            @if($template->notes)
                <section>
                    <h3 class="doc-heading">Additional notes</h3>
                    <p class="doc-body">{{ $template->notes }}</p>
                </section>
            @endif
        </div>

        <footer class="border-t border-gray-200 bg-gray-50/70 px-6 py-5 text-xs text-gray-600 sm:px-10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p>
                    Recorded by {{ $policy->creator?->name ?? 'system' }}
                    on {{ $policy->created_at?->format('d M Y') }}
                    @if($policy->updater && $policy->updated_at?->ne($policy->created_at))
                        · last amended by {{ $policy->updater->name }} on {{ $policy->updated_at->format('d M Y') }}
                    @endif
                </p>
                <p class="text-gray-400">Policy reference #{{ $policy->id }}</p>
            </div>
        </footer>
    </article>

    @if(!$policy->is_active)
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 print:hidden">
            This policy is not currently in force. Bills are generated using the society's active policy.
        </p>
    @endif
</div>
@endsection
