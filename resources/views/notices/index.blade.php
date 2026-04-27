@extends('layouts.app')

@section('title', 'Notices')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Notices</h1>
            <p class="mt-1 text-sm text-gray-600">Society announcements and updates</p>
        </div>
        @can('notices.create')
        <a href="{{ route('notices.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
            Post Notice
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('notices.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if(Auth::user()->isSuperAdmin() || Auth::user()->isSocietyAdmin())
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
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Priorities</option>
                    <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="important" {{ request('priority') === 'important' ? 'selected' : '' }}>Important</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Notices List -->
    <div class="space-y-4">
        @forelse($notices as $notice)
        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h2 class="text-xl font-semibold text-gray-900">{{ $notice->title }}</h2>
                            @if($notice->priority === 'urgent')
                            <span class="px-2 py-1 text-xs font-bold bg-red-100 text-red-800 rounded-full animate-pulse">
                                URGENT
                            </span>
                            @elseif($notice->priority === 'important')
                            <span class="px-2 py-1 text-xs font-semibold bg-orange-100 text-orange-800 rounded-full">
                                Important
                            </span>
                            @endif
                        </div>
                        
                        <p class="text-gray-700 mb-4">{{ Str::limit($notice->content, 200) }}</p>
                        
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $notice->publish_date->format('d M Y') }}
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ $notice->creator->name }}
                            </span>
                            @if($notice->visibility === 'specific')
                            <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">
                                Targeted
                            </span>
                            @endif
                        </div>
                    </div>
                    
                    <a href="{{ route('notices.show', $notice) }}" class="ml-4 text-blue-600 hover:text-blue-800 flex-shrink-0">
                        View Details →
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No notices found</h3>
            <p class="mt-1 text-sm text-gray-500">Check back later for updates</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notices->hasPages())
    <div class="bg-white rounded-lg shadow p-4">
        {{ $notices->links() }}
    </div>
    @endif
</div>
@endsection