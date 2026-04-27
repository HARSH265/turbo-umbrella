@extends('layouts.app')

@section('title', 'Notice Details')
@section('page-title', 'Notice Details')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('notices.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Back to Notices
        </a>
        @can('notices.update')
            <a href="{{ route('notices.edit', $notice) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                Edit Notice
            </a>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $notice->title }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ $notice->society->name }}</p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $notice->priority === 'urgent' ? 'bg-red-100 text-red-800' : ($notice->priority === 'important' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                    {{ ucfirst($notice->priority) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t">
            <div>
                <p class="text-sm text-gray-600">Publish Date</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $notice->publish_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Expiry Date</p>
                <p class="mt-1 font-semibold text-gray-900">{{ optional($notice->expiry_date)->format('d M Y') ?? 'No expiry' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Visibility</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $notice->visibility === 'all' ? 'All Residents' : 'Specific Residents' }}</p>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Notice Content</h2>
            <p class="text-gray-700 whitespace-pre-line">{{ $notice->content }}</p>
        </div>

        @if($notice->files->isNotEmpty())
            <div class="mt-6 pt-6 border-t">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Attachments</h2>
                <div class="space-y-3">
                    @foreach($notice->files as $file)
                        <div class="flex items-center justify-between border rounded-lg p-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ $file->original_name }}</p>
                                <p class="text-sm text-gray-500">{{ $file->human_size }}</p>
                            </div>
                            <a href="{{ route('files.download', $file) }}" class="text-blue-600 hover:text-blue-800">
                                Download
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($notice->visibility === 'specific' && (auth()->user()->isSuperAdmin() || auth()->user()->isSocietyAdmin()))
            <div class="mt-6 pt-6 border-t">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Recipients</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($notice->recipients as $recipient)
                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                            {{ $recipient->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
