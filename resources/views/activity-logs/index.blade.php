@extends('layouts.app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Activity Logs</h1>
        <p class="mt-1 text-sm text-gray-600">System audit trail and user actions</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                <select name="module" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                    <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>
                        {{ ucfirst($module) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                <select name="action" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                        {{ ucfirst($action) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($logs->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">No activity logs found</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Module</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entity ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $log->user?->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($log->action === 'create') bg-green-100 text-green-800
                                @elseif($log->action === 'update') bg-blue-100 text-blue-800
                                @elseif($log->action === 'delete') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($log->action) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ ucfirst($log->module) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $log->entity_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $log->created_at->format('d M Y, h:i A') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection