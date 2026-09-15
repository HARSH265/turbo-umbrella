@extends('layouts.app')

@section('title', 'Notices')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Notices</h1>
            <p class="page-header-subtitle">Society announcements and updates</p>
        </div>
        @can('notices.create')
        <a href="{{ route('notices.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Post Notice
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('notices.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if(Auth::user()->isSuperAdmin())
            <div>
                <x-input-label value="Society" class="mb-1" />
                <select name="society_id" class="form-select">
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
                <x-input-label value="Category" class="mb-1" />
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->value }}" {{ request('category') === $category->value ? 'selected' : '' }}>
                            {{ $category->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Status" class="mb-1" />
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Title', 'Category', 'Priority', 'Status', 'Published', 'Actions']" :data="$notices">
        @forelse($notices as $notice)
        <tr>
            <td>
                <div class="font-medium">{{ $notice->title }}</div>
                <div class="text-xs text-gray-500">{{ $notice->society->name }}</div>
            </td>
            <td>{{ $notice->category->label() }}</td>
            <td>
                <span class="badge
                    @if($notice->priority->value === 'urgent') badge-danger
                    @elseif($notice->priority->value === 'high') badge-warning
                    @else badge-gray @endif">
                    {{ $notice->priority->label() }}
                </span>
            </td>
            <td>
                <span class="badge
                    @if($notice->status->value === 'published') badge-success
                    @elseif($notice->status->value === 'archived') badge-gray
                    @else badge-warning @endif">
                    {{ $notice->status->label() }}
                </span>
            </td>
            <td>{{ $notice->published_at?->format('d M Y') ?? '—' }}</td>
            <td>
                <a href="{{ route('notices.show', $notice) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6">No notices found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection