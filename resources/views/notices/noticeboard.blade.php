@extends('layouts.app')

@section('title', 'Notice Board')
@section('page-title', 'Notice Board')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-black text-brand-900 tracking-tight">Notice Board</h1>
            <p class="mt-1 text-sm text-brand-500">Latest announcements and important updates</p>
        </div>
        <a href="{{ route('notices.index') }}" class="inline-flex items-center rounded-xl border border-brand-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-widest text-brand-700 hover:bg-brand-50">
            View All Notices
        </a>
    </div>

    @if($pinned->isNotEmpty())
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-brand-900">Pinned Notices</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($pinned as $notice)
                    <a href="{{ route('notices.show', $notice) }}" class="block rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-bold text-brand-900">{{ $notice->title }}</h3>
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-full bg-amber-200 text-amber-800">
                                        Pinned
                                    </span>
                                </div>
                                <p class="mt-2 text-sm leading-7 text-brand-700">{{ \Illuminate\Support\Str::limit($notice->content, 140) }}</p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $notice->priority->badgeClass() }}">
                                {{ $notice->priority->label() }}
                            </span>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs text-brand-500">
                            <span>{{ $notice->category->label() }}</span>
                            <span>{{ optional($notice->published_at)->diffForHumans() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-brand-900">Recent Notices</h2>
            <span class="text-sm text-brand-500">{{ $total }} total</span>
        </div>

        @if($notices->isEmpty() && $pinned->isEmpty())
            <div class="rounded-2xl border border-dashed border-brand-200 bg-white py-12 text-center shadow-sm">
                <h3 class="text-sm font-semibold text-brand-900">No notices available</h3>
                <p class="mt-1 text-sm text-brand-500">There are no active notices for you right now.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($notices as $notice)
                    <a href="{{ route('notices.show', $notice) }}" class="block rounded-2xl border border-brand-100 bg-white p-5 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-bold text-brand-900">{{ $notice->title }}</h3>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $notice->priority->badgeClass() }}">
                                        {{ $notice->priority->label() }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm leading-7 text-brand-700">{{ \Illuminate\Support\Str::limit($notice->content, 160) }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-brand-500">
                                {{ optional($notice->published_at)->format('d M Y') }}
                            </span>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs text-brand-500">
                            <span>{{ $notice->category->label() }}</span>
                            <span>{{ $notice->visibility->label() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
