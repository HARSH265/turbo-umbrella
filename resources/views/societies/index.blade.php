@extends('layouts.app')

@section('title', 'Societies')
@section('page-title', 'Societies')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Societies</h1>
            <p class="mt-1 text-sm text-gray-600">Manage residential societies</p>
        </div>
        @can('societies.create')
        <a href="{{ route('societies.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
            Add Society
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('societies.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Name, code, or city"
                       class="w-full border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Societies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($societies as $society)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $society->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $society->code }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $society->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $society->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="space-y-2 text-sm text-gray-600 mb-4">
                <p class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ $society->city }}, {{ $society->state }}
                </p>
                <p class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    {{ $society->contact_number }}
                </p>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('societies.show', $society) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Details →
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12">
            <p class="text-gray-500">No societies found</p>
        </div>
        @endforelse
    </div>

    @if($societies->hasPages())
    <div class="bg-white rounded-lg shadow p-4">
        {{ $societies->links() }}
    </div>
    @endif
</div>
@endsection