@extends('layouts.app')

@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">My Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600">Welcome back, {{ auth()->user()->name }}</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Pending Complaints -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Pending Complaints</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $stats['pending_count'] }}</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Resolved Today -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Resolved Today</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $stats['resolved_today'] }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Assigned -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Total Assigned</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['assigned_complaints']->total() }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Complaints -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">My Assigned Complaints</h2>
            
            @if($stats['assigned_complaints']->isEmpty())
                <p class="text-center text-gray-500 py-8">No complaints assigned yet</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stats['assigned_complaints'] as $complaint)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $complaint->ticket_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ Str::limit($complaint->subject, 40) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $complaint->flat->full_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($complaint->priority === 'urgent') bg-red-100 text-red-800
                                        @elseif($complaint->priority === 'high') bg-orange-100 text-orange-800
                                        @elseif($complaint->priority === 'medium') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($complaint->priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($complaint->status === 'open') bg-red-100 text-red-800
                                        @elseif($complaint->status === 'in_progress') bg-yellow-100 text-yellow-800
                                        @else bg-green-100 text-green-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $complaint->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('complaints.show', $complaint) }}" class="text-blue-600 hover:text-blue-800">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $stats['assigned_complaints']->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Notice Board</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $stats['notice_board']['total'] }} active notices</p>
                </div>
                <a href="{{ route('notices.noticeboard') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    View all
                </a>
            </div>

            @if($stats['notice_board']['pinned']->isEmpty() && $stats['notice_board']['notices']->isEmpty())
                <p class="text-center text-gray-500 py-8">No active notices right now</p>
            @else
                <div class="space-y-3">
                    @foreach($stats['notice_board']['pinned'] as $notice)
                        <a href="{{ route('notices.show', $notice) }}" class="block rounded-lg border border-amber-200 bg-amber-50 p-4 hover:shadow-sm transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ $notice->title }}</h3>
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-amber-200 text-amber-800">Pinned</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($notice->content, 90) }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $notice->priority->badgeClass() }}">
                                    {{ $notice->priority->label() }}
                                </span>
                            </div>
                        </a>
                    @endforeach

                    @foreach($stats['notice_board']['notices'] as $notice)
                        <a href="{{ route('notices.show', $notice) }}" class="block rounded-lg border border-gray-200 p-4 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $notice->title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($notice->content, 90) }}</p>
                                </div>
                                <span class="text-xs text-gray-500">{{ optional($notice->published_at)->format('d M') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('complaints.index') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="text-sm font-medium text-gray-700">All Complaints</span>
            </a>

            <a href="{{ route('visitors.index') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Visitor Logs</span>
            </a>

            <a href="{{ route('visitors.create') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Register Visitor</span>
            </a>
        </div>
    </div>
</div>
@endsection
