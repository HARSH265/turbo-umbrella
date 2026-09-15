@extends('layouts.app')

@section('title', 'Maintenance Policies')
@section('page-title', 'Maintenance Policies')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-900">Maintenance Policies</h1>

        <div class="flex items-center gap-3">
            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('maintenance.policies.generate') }}" class="flex items-center gap-2">
                    @csrf
                    <select name="society_id" required class="rounded-md border-gray-300 text-sm">
                        <option value="">Select Society</option>
                        @foreach($policies->pluck('society')->filter()->unique('id') as $society)
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

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @if(auth()->user()->isSuperAdmin())
                        <th class="px-4 py-3 text-left">Society</th>
                    @endif
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Billing Cycle</th>
                    <th class="px-4 py-3 text-left">Calculation</th>
                    <th class="px-4 py-3 text-left">Grace Days</th>
                    <th class="px-4 py-3 text-left">Effective From</th>
                    <th class="px-4 py-3 text-left">Active</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse($policies as $policy)
                    <tr>
                        @if(auth()->user()->isSuperAdmin())
                            <td class="px-4 py-3">{{ $policy->society?->name ?? 'N/A' }}</td>
                        @endif
                        <td class="px-4 py-3">{{ $policy->template->name }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $policy->template->billing_cycle->value)) }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $policy->template->calculation_type->value)) }}</td>
                        <td class="px-4 py-3">{{ $policy->template->grace_days }}</td>
                        <td class="px-4 py-3">{{ $policy->effective_from?->format('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($policy->is_active)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(
                                !$policy->is_active
                                && (
                                    auth()->user()->hasPermission('maintenance.create')
                                    || auth()->user()->hasPermission('maintenance.policy.create')
                                    || auth()->user()->isSuperAdmin()
                                    || auth()->user()->isSocietyAdmin()
                                )
                            )
                                <form method="POST" action="{{ route('maintenance.policies.activate', $policy) }}">
                                    @csrf
                                    <button class="text-accent-600 hover:underline text-xs">
                                        Activate
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="px-4 py-6 text-center text-gray-500">
                            No maintenance policies found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
