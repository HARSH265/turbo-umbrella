@extends('layouts.app')

@section('title', 'Notice Details')
@section('page-title', 'Notice Details')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('notices.index') }}"
            class="inline-flex items-center rounded-xl border border-brand-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-widest text-brand-700 hover:bg-brand-50">
            Back to Notices
        </a>
        @can('update', $notice)
            <a href="{{ route('notices.edit', $notice) }}" class="rounded-xl bg-brand-900 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-brand-800">
                Edit Notice
            </a>
        @endcan
    </div>

    <x-card header="Notice Details">
        <div class="flex items-start justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-black text-brand-900 tracking-tight">{{ $notice->title }}</h1>
                    @if($notice->is_pinned)
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-amber-800">
                            Pinned
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-brand-500">
                    {{ $notice->isGlobal() ? 'Global Notice' : ($notice->society?->name ?? 'Society Notice') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $notice->priority->badgeClass() }}">
                    {{ $notice->priority->label() }}
                </span>
                <span class="px-3 py-1 text-sm font-semibold rounded-full border
                    @if($notice->status->value === 'published') bg-green-100 text-green-800 border-green-200
                    @elseif($notice->status->value === 'archived') bg-gray-100 text-gray-800 border-gray-200
                    @else bg-yellow-100 text-yellow-800 border-yellow-200
                    @endif">
                    {{ $notice->status->label() }}
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 border-t border-brand-100 pt-6 md:grid-cols-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-brand-400">Category</p>
                <p class="mt-1 font-semibold text-brand-900">{{ $notice->category->label() }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-brand-400">Published At</p>
                <p class="mt-1 font-semibold text-brand-900">
                    {{ optional($notice->published_at)->format('d M Y, h:i A') ?? 'Not published yet' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-brand-400">Expires At</p>
                <p class="mt-1 font-semibold text-brand-900">
                    {{ optional($notice->expires_at)->format('d M Y, h:i A') ?? 'No expiry' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-brand-400">Visibility</p>
                <p class="mt-1 font-semibold text-brand-900">{{ $notice->visibility->label() }}</p>
            </div>
        </div>

        @if($notice->visibility->value === 'personal' && $notice->targetUser)
            <div class="mt-6 border-t border-brand-100 pt-6">
                <p class="text-xs font-bold uppercase tracking-widest text-brand-400">Target User</p>
                <p class="mt-1 font-semibold text-brand-900">{{ $notice->targetUser->name }}</p>
                <p class="text-sm text-brand-500">{{ $notice->targetUser->email }}</p>
            </div>
        @endif

        @if($notice->visibility->value === 'role_based' && $notice->target_role)
            <div class="mt-6 border-t border-brand-100 pt-6">
                <p class="text-xs font-bold uppercase tracking-widest text-brand-400">Target Role</p>
                <p class="mt-1 font-semibold text-brand-900">{{ str_replace('-', ' ', ucfirst($notice->target_role)) }}</p>
            </div>
        @endif

        <div class="mt-6 border-t border-brand-100 pt-6">
            <h2 class="mb-3 text-lg font-semibold text-brand-900">Notice Content</h2>
            <p class="whitespace-pre-line leading-7 text-brand-700">{{ $notice->content }}</p>
        </div>

        @if($notice->attachments->isNotEmpty())
            <div class="mt-6 border-t border-brand-100 pt-6">
                <h2 class="mb-3 text-lg font-semibold text-brand-900">Attachments</h2>
                <div class="space-y-3">
                    @foreach($notice->attachments as $file)
                        <div class="flex items-center justify-between rounded-xl border border-brand-100 bg-brand-50/40 p-4">
                            <div>
                                <p class="font-medium text-brand-900">{{ $file->original_name }}</p>
                                <p class="text-sm text-brand-500">{{ $file->human_size }}</p>
                            </div>
                            <a href="{{ route('files.download', $file) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
                                Download
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-card>
</div>
@endsection
