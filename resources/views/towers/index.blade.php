@extends('layouts.app')

@section('title', 'Towers')
@section('page-title', 'Towers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Towers</h1>
            <p class="mt-1 text-sm text-gray-600">Manage society towers</p>
        </div>
        @can('societies.create')
        <a href="{{ route('towers.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
            Add Tower
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('towers.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Society</label>
                <select name="society_id" class="w-full border-gray-300 rounded-lg">
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

    <!-- Towers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($towers->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">No towers found</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tower Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Society</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Floors</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($towers as $tower)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $tower->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $tower->society->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $tower->total_floors }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tower->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $tower->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('towers.show', $tower) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                                View
                            </a>
                            @can('societies.update')
                            <a href="{{ route('towers.edit', $tower) }}" class="text-gray-600 hover:text-gray-800">
                                Edit
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t">
                {{ $towers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection