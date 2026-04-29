@extends('layouts.app')

@section('title', 'Notices')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-black text-brand-900 tracking-tight">Notices</h1>
            <p class="mt-1 text-sm text-brand-500">Society announcements and updates</p>
        </div>
        @can('create', \App\Models\Notice::class)
            <a href="{{ route('notices.create') }}" class="inline-flex items-center rounded-xl bg-brand-900 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-brand-800">
                Post Notice
            </a>
        @endcan
    </div>

    <x-card header="Notice Filters">
        <form method="GET" action="{{ route('notices.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @if(Auth::user()->isSuperAdmin())
                <div>
                    <x-input-label value="Society" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="society_id" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
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
                <x-input-label value="Category" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                <select name="category" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->value }}" {{ request('category') === $category->value ? 'selected' : '' }}>
                            {{ $category->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label value="Priority" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                <select name="priority" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority->value }}" {{ request('priority') === $priority->value ? 'selected' : '' }}>
                            {{ $priority->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(Auth::user()->isSuperAdmin() || Auth::user()->isSocietyAdmin())
                <div>
                    <x-input-label value="Status" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="status" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label value="Visibility" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="visibility" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                        <option value="">All Visibility Types</option>
                        @foreach($visibilities as $visibility)
                            <option value="{{ $visibility->value }}" {{ request('visibility') === $visibility->value ? 'selected' : '' }}>
                                {{ $visibility->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="md:col-span-5 flex items-end justify-end">
                <button type="submit" class="rounded-xl bg-brand-900 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-brand-800">
                    Filter
                </button>
            </div>
        </form>
    </x-card>

    <div class="space-y-4">
        @forelse($notices as $notice)
            <div class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h2 class="text-xl font-bold text-brand-900">{{ $notice->title }}</h2>
                                @if($notice->is_pinned)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-amber-800">
                                        PINNED
                                    </span>
                                @endif
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $notice->priority->badgeClass() }}">
                                    {{ $notice->priority->label() }}
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full border
                                    @if($notice->status->value === 'published') bg-green-100 text-green-800 border-green-200
                                    @elseif($notice->status->value === 'archived') bg-gray-100 text-gray-800 border-gray-200
                                    @else bg-yellow-100 text-yellow-800 border-yellow-200
                                    @endif">
                                    {{ $notice->status->label() }}
                                </span>
                            </div>

                            <p class="mb-4 text-brand-700 leading-7">{{ \Illuminate\Support\Str::limit($notice->content, 220) }}</p>

                            <div class="flex flex-wrap items-center gap-4 text-sm text-brand-500">
                                <span class="font-medium">{{ $notice->category->label() }}</span>
                                <span>{{ optional($notice->published_at)->format('d M Y') ?? 'Not published yet' }}</span>
                                <span>{{ $notice->creator?->name }}</span>
                                <span>{{ $notice->visibility->label() }}</span>
                                @if($notice->isGlobal())
                                    <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-800">Global</span>
                                @elseif($notice->society)
                                    <span>{{ $notice->society->name }}</span>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('notices.show', $notice) }}" class="flex-shrink-0 text-sm font-bold text-brand-700 hover:text-brand-900">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-brand-200 bg-white py-12 text-center">
                <h3 class="mt-2 text-sm font-semibold text-brand-900">No notices found</h3>
                <p class="mt-1 text-sm text-brand-500">Check back later for updates</p>
            </div>
        @endforelse
    </div>

    @if($notices->hasPages())
        <div class="rounded-2xl border border-brand-100 bg-white p-4 shadow-sm">
            {{ $notices->links() }}
        </div>
    @endif
</div>
@endsection
