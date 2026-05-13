@extends('layouts.app')

@section('title', 'Towers')
@section('page-title', 'Towers')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Towers</h1>
            <p class="page-header-subtitle">Manage society towers</p>
        </div>
        @can('societies.create')
        <a href="{{ route('towers.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Tower
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('towers.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Society</label>
                <select name="society_id" class="form-select">
                    <option value="">All Societies</option>
                    @foreach($societies as $society)
                    <option value="{{ $society->id }}" {{ request('society_id') == $society->id ? 'selected' : '' }}>
                        {{ $society->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Tower Name', 'Society', 'Total Floors', 'Status', 'Actions']" :data="$towers">
        @forelse($towers as $tower)
        <tr>
            <td class="font-medium">{{ $tower->name }}</td>
            <td>{{ $tower->society->name }}</td>
            <td>{{ $tower->total_floors }}</td>
            <td>
                <span class="badge {{ $tower->is_active ? 'badge-success' : 'badge-danger' }}">
                    {{ $tower->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td>
                <a href="{{ route('towers.show', $tower) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5">No towers found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection