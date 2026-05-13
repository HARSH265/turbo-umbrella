@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Users</h1>
            <p class="page-header-subtitle">Manage system users and roles</p>
        </div>
        @can('users.create')
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </a>
        @endcan
    </div>

    <x-card>
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="form-select">
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
                <select name="is_active" class="form-select">
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

    <x-data-table :headers="['Name', 'Email', 'Phone', 'Role', 'Status', 'Actions']" :data="$users">
        @forelse($users as $user)
        <tr>
            <td class="font-medium">{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone }}</td>
            <td>
                <span class="badge badge-info">
                    {{ $user->roles->first()?->name ?? 'N/A' }}
                </span>
            </td>
            <td>
                <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td>
                <a href="{{ route('users.show', $user) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">View</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6">No users found</td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection