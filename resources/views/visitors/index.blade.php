@extends('layouts.app')

@section('title', 'Visitors')
@section('page-title', 'Visitor Logs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Visitor Logs</h1>
            <p class="mt-1 text-sm text-gray-600">Track visitor entry and exit</p>
        </div>
        @can('visitors.create')
        <a href="{{ route('visitors.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
            Register Visitor
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('visitors.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="approval_status" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="w-full border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Inside</label>
                <select name="inside" class="w-full border-gray-300 rounded-lg">
                    <option value="">All</option>
                    <option value="1" {{ request('inside') === '1' ? 'selected' : '' }}>Currently Inside</option>
                    <option value="0" {{ request('inside') === '0' ? 'selected' : '' }}>Exited</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Visitors Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($visitors->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">No visitor records found</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visitor Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exit Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($visitors as $visitor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $visitor->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $visitor->phone }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $visitor->flat->full_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $visitor->purpose }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $visitor->entry_time->format('d M, h:i A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $visitor->exit_time ? $visitor->exit_time->format('d M, h:i A') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($visitor->approval_status === 'approved') bg-green-100 text-green-800
                                @elseif($visitor->approval_status === 'rejected') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($visitor->approval_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('visitors.show', $visitor) }}" class="text-blue-600 hover:text-blue-800">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t">
                {{ $visitors->links() }}
            </div>
        @endif
    </div>
</div>
@endsection