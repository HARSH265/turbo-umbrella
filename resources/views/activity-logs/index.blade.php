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
                <label class="form-label">Module</label>
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                    <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>
                        {{ ucfirst($module) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                        {{ ucfirst($action) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary btn-block">
                    Filter
                </button>
            </div>
        </form>
    </div>


    {{-- x-data-table: each log entry becomes a labelled card on phones rather than a
         six-column row that has to be scrolled sideways. --}}
    <x-data-table :headers="['User', 'Action', 'Module', 'Entity ID', 'IP Address', 'Date']"
                  :data="$logs"
                  emptyMessage="No activity logs found">
        @forelse($logs as $log)
            <tr>
                <td class="font-medium">{{ $log->user?->name ?? 'System' }}</td>
                <td>
                    <span class="badge {{ $log->action === 'create' ? 'badge-success' : ($log->action === 'update' ? 'badge-info' : ($log->action === 'delete' ? 'badge-danger' : 'badge-gray')) }}">
                        {{ ucfirst($log->action) }}
                    </span>
                </td>
                <td>{{ ucfirst($log->module) }}</td>
                <td>{{ $log->entity_id ?? '—' }}</td>
                <td>{{ $log->ip_address ?? '—' }}</td>
                <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
            </tr>
        @empty
        @endforelse
    </x-data-table>
</div>
@endsection
