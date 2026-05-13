@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Users</h1>
            <p class="mt-1 text-sm text-gray-600">Manage system users and roles</p>
        </div>
    </div>

    <x-card>
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <x-text-input name="search" value="{{ request('search') }}" placeholder="Name, email, or phone" class="w-full" />
            </div>
            <div class="md:col-span-4 flex justify-end">
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </div>
        </form>
    </x-card>

    <x-data-table :headers="['Name', 'Email', 'Phone', 'Role', 'Status', 'Actions']" :data="$users" route="{{ route('users.create') }}" createText="Add User">
        @forelse($users as $user)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->phone }}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    {{ $user->roles->first()?->name ?? 'N/A' }}
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td class="px-6 py-4 text-sm">
                <a href="{{ route('users.show', $user) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No users found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection