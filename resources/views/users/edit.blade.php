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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                    <select name="role_id" required class="w-full border-gray-300 rounded-lg">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', optional($user->roles->first())->id) == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password" class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                <input type="file" name="profile_photo" class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('users.show', $user) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
