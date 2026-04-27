@extends('layouts.app')

@section('title', 'Create Notice')
@section('page-title', 'Create Notice')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Create Notice</h1>

        <form method="POST" action="{{ route('notices.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Society <span class="text-red-500">*</span></label>
                    <select name="society_id" required class="w-full border-gray-300 rounded-lg">
                        <option value="">Select society</option>
                        @foreach($societies as $society)
                            <option value="{{ $society->id }}" {{ old('society_id') == $society->id ? 'selected' : '' }}>
                                {{ $society->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('society_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required class="w-full border-gray-300 rounded-lg">
                        <option value="normal" {{ old('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="important" {{ old('priority') === 'important' ? 'selected' : '' }}>Important</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full border-gray-300 rounded-lg">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Content <span class="text-red-500">*</span></label>
                <textarea name="content" rows="6" required class="w-full border-gray-300 rounded-lg">{{ old('content') }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visibility <span class="text-red-500">*</span></label>
                    <select name="visibility" required class="w-full border-gray-300 rounded-lg">
                        <option value="all" {{ old('visibility', 'all') === 'all' ? 'selected' : '' }}>All Residents</option>
                        <option value="specific" {{ old('visibility') === 'specific' ? 'selected' : '' }}>Specific Residents</option>
                    </select>
                    @error('visibility')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Publish Date <span class="text-red-500">*</span></label>
                    <input type="date" name="publish_date" value="{{ old('publish_date', now()->toDateString()) }}" required class="w-full border-gray-300 rounded-lg">
                    @error('publish_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="w-full border-gray-300 rounded-lg">
                    @error('expiry_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>
                    <input type="file" name="files[]" multiple class="w-full border-gray-300 rounded-lg">
                    @error('files.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
                <select name="recipients[]" multiple class="w-full border-gray-300 rounded-lg min-h-40">
                    @foreach($residents as $resident)
                        <option value="{{ $resident->id }}" {{ collect(old('recipients', []))->contains($resident->id) ? 'selected' : '' }}>
                            {{ $resident->name }} ({{ $resident->email }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Used only when visibility is set to specific.</p>
                @error('recipients')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('notices.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Publish Notice
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
