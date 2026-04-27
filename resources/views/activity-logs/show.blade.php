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
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Back to Logs
        </a>
    </div>

    <div class="space-y-4">
        @forelse($logs as $log)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $log->action === 'create' ? 'bg-green-100 text-green-800' : ($log->action === 'update' ? 'bg-blue-100 text-blue-800' : ($log->action === 'delete' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
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

    @if($logs->hasPages())
        <div class="bg-white rounded-lg shadow p-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
