@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit User</h1>

        <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                </div>

                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
                </div>

                <div>
                    <label class="form-label">Phone <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required class="form-input">
                </div>

                <div>
                    <label class="form-label">Role <span class="text-red-500">*</span></label>
                    <select name="role_id" required class="form-select">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', optional($user->roles->first())->id) == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Society</label>
                    <select name="society_id" class="form-select" @if(auth()->user()->isSocietyAdmin()) disabled @endif>
                        <option value="">No society</option>
                        @foreach($societies as $society)
                            <option value="{{ $society->id }}" {{ old('society_id', auth()->user()->isSocietyAdmin() ? auth()->user()->society_id : $user->society_id) == $society->id ? 'selected' : '' }}>
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
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-input">
                </div>

                <div>
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-input">
                </div>
            </div>

            <div class="mt-6">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="profile_photo" class="form-input">
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
