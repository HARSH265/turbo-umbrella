@extends('layouts.app')

@section('title', 'Society Details')
@section('page-title', 'Society Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('societies.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Back to Societies
        </a>
        @can('societies.update')
        <a href="{{ route('societies.edit', $society) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
            Edit Society
        </a>
        @endcan
    </div>

    <!-- Society Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $society->name }}</h1>
                <p class="mt-1 text-gray-600">Code: {{ $society->code }}</p>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $society->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                {{ $society->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-600 mb-2">Address</h3>
                <p class="text-gray-900">{{ $society->address }}</p>
                <p class="text-gray-900">{{ $society->city }}, {{ $society->state }} - {{ $society->pincode }}</p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-600 mb-2">Contact Information</h3>
                <p class="text-gray-900">Phone: {{ $society->contact_number }}</p>
                <p class="text-gray-900">Email: {{ $society->email }}</p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Towers</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_towers'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Flats</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_flats'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Occupied Flats</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ $stats['occupied_flats'] }}</p>
        </div>
    </div>

    <!-- Towers List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Towers</h2>
            @can('societies.create')
            <a href="{{ route('towers.create') }}?society_id={{ $society->id }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                + Add Tower
            </a>
            @endcan
        </div>

        @if($society->towers->isEmpty())
            <div class="p-12 text-center text-gray-500">
                No towers in this society yet
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
                @foreach($society->towers as $tower)
                <div class="border rounded-lg p-4 hover:shadow-md transition">
                    <h3 class="font-semibold text-gray-900">{{ $tower->name }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $tower->total_floors }} floors</p>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-xs {{ $tower->is_active ? 'text-green-600' : 'text-red-600' }}">
                            {{ $tower->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <a href="{{ route('towers.show', $tower) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            View →
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recent Notices -->
    @if($society->notices->isNotEmpty())
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Notices</h2>
        <div class="space-y-3">
            @foreach($society->notices as $notice)
            <div class="border-l-4 border-blue-500 pl-4 py-2">
                <p class="font-medium text-gray-900">{{ $notice->title }}</p>
                <p class="text-sm text-gray-600">{{ $notice->publish_date->format('d M Y') }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
