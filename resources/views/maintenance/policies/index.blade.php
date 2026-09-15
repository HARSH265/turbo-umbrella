@extends('layouts.app')

@section('title', 'Maintenance Policies')
@section('page-title', 'Maintenance Policies')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-header-title">Maintenance Policies</h1>

        <div class="page-header-actions">
            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('maintenance.policies.generate') }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select name="society_id" required class="rounded-md border-gray-300 text-sm">
                        <option value="">Select Society</option>
                        @foreach($societies as $society)
                            <option value="{{ $society->id }}">{{ $society->name }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Generate Current Month
                    </button>
                </form>
            @elseif(auth()->user()->hasPermission('maintenance.create') || auth()->user()->isSocietyAdmin())
                <form method="POST" action="{{ route('maintenance.policies.generate') }}">
                    @csrf
                    <button class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Generate Current Month
                    </button>
                </form>
            @endif

            @if(auth()->user()->hasPermission('maintenance.create') || auth()->user()->hasPermission('maintenance.policy.create') || auth()->user()->isSuperAdmin() || auth()->user()->isSocietyAdmin())
                <a href="{{ route('maintenance.policies.create') }}"
                   class="px-4 py-2 bg-brand-900 text-white rounded-md hover:bg-brand-800 text-sm">
                    Create Policy
                </a>
            @endif
        </div>
    </div>

    @php
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $canActivate = auth()->user()->hasPermission('maintenance.create')
            || auth()->user()->hasPermission('maintenance.policy.create')
            || $isSuperAdmin
            || auth()->user()->isSocietyAdmin();

        $headers = $isSuperAdmin
            ? ['Society', 'Name', 'Billing Cycle', 'Calculation', 'Grace Days', 'Effective From', 'Active', 'Action']
            : ['Name', 'Billing Cycle', 'Calculation', 'Grace Days', 'Effective From', 'Active', 'Action'];
    @endphp

    {{-- x-data-table rather than a bespoke table: it collapses each policy into a card
         on phones and keeps the pagination styling consistent with every other list. --}}
    <x-data-table :headers="$headers" :data="$policies" emptyMessage="No maintenance policies found.">
        @forelse($policies as $policy)
            <tr>
                @if($isSuperAdmin)
                    <td>{{ $policy->society?->name ?? 'N/A' }}</td>
                @endif
                <td class="font-medium">{{ $policy->template->name }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $policy->template->billing_cycle->value)) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $policy->template->calculation_type->value)) }}</td>
                <td>{{ $policy->template->grace_days }}</td>
                <td>{{ $policy->effective_from?->format('d M Y') ?? '—' }}</td>
                <td>
                    @if($policy->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-gray">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex items-center gap-3">
                        <a href="{{ route('maintenance.policies.show', $policy) }}"
                           class="text-accent-600 hover:underline">View</a>

                        @if(!$policy->is_active && $canActivate)
                            <form method="POST" action="{{ route('maintenance.policies.activate', $policy) }}">
                                @csrf
                                <button class="text-brand-700 hover:underline">Activate</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
        @endforelse
    </x-data-table>

</div>
@endsection
