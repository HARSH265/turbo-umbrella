@extends('layouts.app')

@section('title', 'Edit Notice')
@section('page-title', 'Edit Notice')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Notice</h1>

        <form method="POST" action="{{ route('notices.update', $notice) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Society <span class="text-red-500">*</span></label>
                    <select name="society_id" required class="w-full border-gray-300 rounded-lg">
                        @foreach($societies as $society)
                            <option value="{{ $society->id }}" {{ old('society_id', $notice->society_id) == $society->id ? 'selected' : '' }}>
                                {{ $society->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required class="w-full border-gray-300 rounded-lg">
                        @foreach(['normal', 'important', 'urgent'] as $priority)
                            <option value="{{ $priority }}" {{ old('priority', $notice->priority) === $priority ? 'selected' : '' }}>
                                {{ ucfirst($priority) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $notice->title) }}" required class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Content <span class="text-red-500">*</span></label>
                <textarea name="content" rows="6" required class="w-full border-gray-300 rounded-lg">{{ old('content', $notice->content) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visibility <span class="text-red-500">*</span></label>
                    <select name="visibility" required class="w-full border-gray-300 rounded-lg">
                        <option value="all" {{ old('visibility', $notice->visibility) === 'all' ? 'selected' : '' }}>All Residents</option>
                        <option value="specific" {{ old('visibility', $notice->visibility) === 'specific' ? 'selected' : '' }}>Specific Residents</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Publish Date <span class="text-red-500">*</span></label>
                    <input type="date" name="publish_date" value="{{ old('publish_date', $notice->publish_date->toDateString()) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date', optional($notice->expiry_date)->toDateString()) }}" class="w-full border-gray-300 rounded-lg">
                </div>

                <div class="flex items-center pt-8">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $notice->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Notice is active</label>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
                <select name="recipients[]" multiple class="w-full border-gray-300 rounded-lg min-h-40">
                    @php($selectedRecipients = collect(old('recipients', $notice->recipients->pluck('id')->all())))
                    @foreach($residents as $resident)
                        <option value="{{ $resident->id }}" {{ $selectedRecipients->contains($resident->id) ? 'selected' : '' }}>
                            {{ $resident->name }} ({{ $resident->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Add Attachments</label>
                <input type="file" name="files[]" multiple class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('notices.show', $notice) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Update Notice
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
