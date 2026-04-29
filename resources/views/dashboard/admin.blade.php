@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Welcome back, {{ Auth::user()->name }}</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Societies -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Total Societies</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_societies'] }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Flats -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Total Flats</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_flats'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $stats['occupied_flats'] }} occupied</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Residents -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Total Residents</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_residents'] }}</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Maintenance -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Pending Maintenance</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            ₹{{ number_format($stats['maintenance']['total_outstanding']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $stats['maintenance']['total_outstanding'] }} pending</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaints Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Complaints Stats -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Complaints Overview</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-red-500 rounded-full mr-3"></span>
                                <span class="text-sm text-gray-600">Open</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ $stats['complaints']['open'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></span>
                                <span class="text-sm text-gray-600">In Progress</span>
                            </div>
                            <span
                                class="text-sm font-semibold text-gray-900">{{ $stats['complaints']['in_progress'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-3"></span>
                                <span class="text-sm text-gray-600">Resolved</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ $stats['complaints']['resolved'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-orange-500 rounded-full mr-3"></span>
                                <span class="text-sm text-gray-600">High Priority</span>
                            </div>
                            <span
                                class="text-sm font-semibold text-gray-900">{{ $stats['complaints']['high_priority'] }}</span>
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('complaints.index') }}"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View all complaints →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Maintenance Summary -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Maintenance Summary</h2>
                    <div class="space-y-4">
                        @php
                            $totalAmount =
                                $stats['maintenance']['total_collected'] + $stats['maintenance']['total_outstanding'];
                            $collectedPercentage =
                                $totalAmount > 0 ? ($stats['maintenance']['total_collected'] / $totalAmount) * 100 : 0;
                            $pendingPercentage =
                                $totalAmount > 0 ? ($stats['maintenance']['total_outstanding'] / $totalAmount) * 100 : 0;
                        @endphp

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">Total Collected</span>
                                <span
                                    class="text-sm font-semibold text-green-600">₹{{ number_format($stats['maintenance']['total_collected']) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $collectedPercentage }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($collectedPercentage, 1) }}% of total
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">Total Pending</span>
                                <span
                                    class="text-sm font-semibold text-yellow-600">₹{{ number_format($stats['maintenance']['total_outstanding']) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $pendingPercentage }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($pendingPercentage, 1) }}% of total</p>
                        </div>

                        <div class="pt-4 border-t">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Overdue Bills</span>
                                <span
                                    class="font-semibold text-red-600">{{ $stats['maintenance']['overdue_count'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('maintenance.index') }}"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View all maintenance →
                        </a>
                    </div>
                </div>
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
                @can('maintenance.create')
                    <a href="{{ route('maintenance.create') }}"
                        class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Generate Maintenance</span>
                    </a>
                @endcan

                @can('notices.create')
                    <a href="{{ route('notices.create') }}"
                        class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Post Notice</span>
                    </a>
                @endcan

                @can('users.create')
                    <a href="{{ route('users.create') }}"
                        class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Add User</span>
                    </a>
                @endcan

                @can('flats.create')
                    <a href="{{ route('flats.create') }}"
                        class="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Add Flat</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection
