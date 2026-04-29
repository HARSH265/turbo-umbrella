@extends('layouts.app')

@section('title', 'Edit Notice')
@section('page-title', 'Edit Notice')

@section('content')
<div class="max-w-5xl mx-auto">
    <x-card header="Edit Notice">
        <h1 class="mb-2 text-2xl font-black text-brand-900 tracking-tight">Edit Notice</h1>
        <p class="mb-6 text-sm text-brand-500">Update visibility, status, timing, and attachments for this notice.</p>

        <form method="POST" action="{{ route('notices.update', $notice) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label value="Society" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="society_id" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10" {{ auth()->user()->isSocietyAdmin() ? 'disabled' : '' }}>
                        @unless(auth()->user()->isSocietyAdmin())
                            <option value="">Global Notice</option>
                        @endunless
                        @foreach($societies as $society)
                            <option value="{{ $society->id }}" {{ (string) old('society_id', auth()->user()->isSocietyAdmin() ? auth()->user()->society_id : $notice->society_id) === (string) $society->id ? 'selected' : '' }}>
                                {{ $society->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(auth()->user()->isSocietyAdmin())
                        <input type="hidden" name="society_id" value="{{ auth()->user()->society_id }}">
                    @endif
                    @error('society_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label value="Status" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="status" required class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ old('status', $notice->status->value) === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <x-input-label value="Category" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="category" required class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                        @foreach($categories as $category)
                            <option value="{{ $category->value }}" {{ old('category', $notice->category->value) === $category->value ? 'selected' : '' }}>
                                {{ $category->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label value="Priority" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="priority" required class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10">
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->value }}" {{ old('priority', $notice->priority->value) === $priority->value ? 'selected' : '' }}>
                                {{ $priority->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <x-input-label value="Title" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                <input type="text" name="title" value="{{ old('title', $notice->title) }}" required class="w-full rounded-xl border-brand-200 text-brand-800 shadow-sm focus:border-brand-500 focus:ring-brand-500/10">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label value="Content" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                <textarea name="content" rows="6" required class="w-full rounded-xl border-brand-200 text-brand-800 shadow-sm focus:border-brand-500 focus:ring-brand-500/10">{{ old('content', $notice->content) }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <x-input-label value="Visibility" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="visibility" required class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10" {{ $notice->isPublished() ? 'disabled' : '' }}>
                        @foreach($visibilities as $visibility)
                            <option value="{{ $visibility->value }}" {{ old('visibility', $notice->visibility->value) === $visibility->value ? 'selected' : '' }}>
                                {{ $visibility->label() }}
                            </option>
                        @endforeach
                    </select>
                    @if($notice->isPublished())
                        <input type="hidden" name="visibility" value="{{ $notice->visibility->value }}">
                        <p class="mt-1 text-xs italic text-brand-400">Visibility cannot be changed after publication.</p>
                    @endif
                    @error('visibility')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label value="Expires At" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <input type="datetime-local" name="expires_at"
                        value="{{ old('expires_at', optional($notice->expires_at)->format('Y-m-d\\TH:i')) }}"
                        class="w-full rounded-xl border-brand-200 text-brand-800 shadow-sm focus:border-brand-500 focus:ring-brand-500/10">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <x-input-label value="Target User" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="target_user_id" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10" {{ $notice->isPublished() ? 'disabled' : '' }}>
                        <option value="">Select user</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('target_user_id', $notice->target_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @if($notice->isPublished() && $notice->target_user_id)
                        <input type="hidden" name="target_user_id" value="{{ $notice->target_user_id }}">
                    @endif
                    <p class="mt-1 text-xs italic text-brand-400">Used only for personal notices.</p>
                    @error('target_user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label value="Target Role" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <select name="target_role" class="w-full rounded-xl border-brand-200 text-sm text-brand-800 focus:border-brand-500 focus:ring-brand-500/10" {{ $notice->isPublished() ? 'disabled' : '' }}>
                        <option value="">Select role</option>
                        @foreach(['resident' => 'Resident', 'staff' => 'Staff', 'society-admin' => 'Society Admin'] as $value => $label)
                            <option value="{{ $value }}" {{ old('target_role', $notice->target_role) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @if($notice->isPublished() && $notice->target_role)
                        <input type="hidden" name="target_role" value="{{ $notice->target_role }}">
                    @endif
                    <p class="mt-1 text-xs italic text-brand-400">Used only for role-based notices.</p>
                    @error('target_role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center">
                <input type="checkbox" name="is_pinned" id="is_pinned" value="1" {{ old('is_pinned', $notice->is_pinned) ? 'checked' : '' }} class="rounded border-brand-300 text-amber-600 focus:ring-amber-500/20">
                <label for="is_pinned" class="ml-2 text-sm font-medium text-brand-700">Pin this notice to the top of the notice board</label>
            </div>

            @if($notice->attachments->isNotEmpty())
                <div class="mt-6">
                    <x-input-label value="Current Attachments" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                    <div class="space-y-2">
                        @foreach($notice->attachments as $file)
                            <div class="flex items-center justify-between rounded-xl border border-brand-100 bg-brand-50/40 px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-brand-900">{{ $file->original_name }}</p>
                                    <p class="text-xs text-brand-500">{{ $file->human_size }}</p>
                                </div>
                                <a href="{{ route('files.download', $file) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
                                    Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <x-input-label value="Add Attachments" class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-500" />
                <input type="file" name="attachments[]" multiple class="w-full rounded-xl border-brand-200 text-sm text-brand-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-900 file:px-4 file:py-2 file:text-xs file:font-black file:uppercase file:tracking-widest file:text-white">
                <p class="mt-1 text-xs italic text-brand-400">Up to 5 files. JPG, PNG, PDF, DOCX.</p>
                @error('attachments')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('notices.show', $notice) }}" class="inline-flex items-center rounded-xl border border-brand-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-widest text-brand-700 hover:bg-brand-50">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-brand-800">
                    Update Notice
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection
