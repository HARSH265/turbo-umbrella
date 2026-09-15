@extends('layouts.app')

@section('title', 'Entity Activity')
@section('page-title', 'Entity Activity')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ ucfirst($module) }} #{{ $entityId }}</h1>
            <p class="mt-1 text-sm text-gray-600">Change history for this record</p>
        </div>
        <a href="{{ route('activity-logs.index') }}"
            class="btn btn-secondary">
            Back to Logs
        </a>
    </div>

    <div class="space-y-4">
        @forelse($logs as $log)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="badge {{ $log->action === 'create' ? 'badge-success' : ($log->action === 'update' ? 'badge-info' : ($log->action === 'delete' ? 'badge-danger' : 'badge-gray')) }}">
                            {{ ucfirst($log->action) }}
                        </span>
                        <span class="ml-3 text-sm text-gray-600">{{ $log->user?->name ?? 'System' }}</span>
                    </div>
                    <span class="text-sm text-gray-500">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                </div>

                @if(!empty($log->changes))
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Old</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">New</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($log->changes as $field => $change)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $field }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ is_array($change['old']) ? json_encode($change['old']) : ($change['old'] ?? '-') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ is_array($change['new']) ? json_encode($change['new']) : ($change['new'] ?? '-') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-4 text-sm text-gray-600">
                        No field-level diff was recorded for this action.
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
                No activity recorded for this entity.
            </div>
        @endforelse
    </div>

    @if($logs->total() > 0)
        <div class="card overflow-hidden">
            <x-pagination :data="$logs" />
        </div>
    @endif
</div>
@endsection
