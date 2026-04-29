@extends('layouts.app')

@section('title', 'Resident Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">My Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600">
            @if($stats['flat'])
                {{ $stats['flat']->full_name }} - {{ $stats['flat']->type }}
            @else
                No flat assigned
            @endif
        </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Open Complaints -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Open Complaints</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['complaint_summary']['open'] }}</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">In Progress</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['complaint_summary']['in_progress'] }}</p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Resolved -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">Resolved</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['complaint_summary']['resolved'] }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Maintenance -->
    @if($stats['pending_maintenance']->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Pending Maintenance</h2>
                <span class="text-sm text-red-600 font-medium">{{ $stats['pending_maintenance']->count() }} pending</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($stats['pending_maintenance'] as $maintenance)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($maintenance->month)->format('F Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ₹{{ number_format($maintenance->total_due, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $maintenance->due_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $maintenance->status->value === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $maintenance->status->value)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('maintenance.payment', $maintenance) }}" class="text-blue-600 hover:text-blue-800">
                                    Pay Now
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Complaints -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Complaints</h2>
                <a href="{{ route('complaints.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                    New Complaint
                </a>
            </div>
            
            @if($stats['my_complaints']->isEmpty())
                <p class="text-center text-gray-500 py-8">No complaints yet</p>
            @else
                <div class="space-y-4">
                    @foreach($stats['my_complaints'] as $complaint)
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $complaint->subject }}</h3>
                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($complaint->priority === 'urgent') bg-red-100 text-red-800
                                        @elseif($complaint->priority === 'high') bg-orange-100 text-orange-800
                                        @elseif($complaint->priority === 'medium') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($complaint->priority->value) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">{{ Str::limit($complaint->description, 100) }}</p>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <span class="mr-4">{{ $complaint->ticket_number }}</span>
                                    <span class="mr-4">{{ $complaint->created_at->diffForHumans() }}</span>
                                    <span>Flat: {{ $complaint->flat->full_name }}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full
                                    @if($complaint->status === 'open') bg-red-100 text-red-800
                                    @elseif($complaint->status === 'in_progress') bg-yellow-100 text-yellow-800
                                    @elseif($complaint->status === 'resolved') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $complaint->status->value)) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <a href="{{ route('complaints.show', $complaint) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                View Details →
                            </a>
                        </div>
                    </div>
                    @endforeach
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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('complaints.create') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Raise Complaint</span>
            </a>

            <a href="{{ route('maintenance.index') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">View Maintenance</span>
            </a>

            <a href="{{ route('notices.index') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span class="text-sm font-medium text-gray-700">View Notices</span>
            </a>

            <a href="{{ route('visitors.index') }}" class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Visitor Logs</span>
            </a>
        </div>
    </div>
</div>
@endsection
