@extends('layouts.app')

@section('title', 'Notices')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Notices</h1>
            <p class="mt-1 text-sm text-gray-500">Society announcements and updates</p>
        </div>
    </div>

    <x-card>
        <form method="GET" action="{{ route('notices.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if(Auth::user()->isSuperAdmin())
            <div>
                <x-input-label value="Society" class="mb-1" />
                <select name="society_id" class="w-full border-gray-300 rounded-lg text-sm">
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
                <select name="category" class="w-full border-gray-300 rounded-lg text-sm">
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
                <select name="status" class="w-full border-gray-300 rounded-lg text-sm">
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

    <x-data-table :headers="['Title', 'Category', 'Priority', 'Status', 'Published', 'Actions']" :data="$notices" route="{{ route('notices.create') }}" createText="Post Notice">
        @forelse($notices as $notice)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ $notice->title }}</div>
                <div class="text-xs text-gray-500">{{ $notice->society->name }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $notice->category->label() }}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($notice->priority->value === 'urgent') bg-red-100 text-red-800
                    @elseif($notice->priority->value === 'high') bg-orange-100 text-orange-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $notice->priority->label() }}
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($notice->status->value === 'published') bg-green-100 text-green-800
                    @elseif($notice->status->value === 'archived') bg-gray-100 text-gray-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    {{ $notice->status->label() }}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $notice->published_at?->format('d M Y') ?? '—' }}</td>
            <td class="px-6 py-4 text-sm">
                <a href="{{ route('notices.show', $notice) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No notices found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection