@extends('layouts.app')

@section('title', 'Visitor Details')
@section('page-title', 'Visitor Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('visitors.index') }}" class="text-gray-600 hover:text-gray-900">← Back to Visitors</a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $visitor->name }}</h1>
                <p class="text-gray-600">{{ $visitor->phone }}</p>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full
                @if($visitor->approval_status === 'approved') bg-green-100 text-green-800
                @elseif($visitor->approval_status === 'rejected') bg-red-100 text-red-800
                @else bg-yellow-100 text-yellow-800
                @endif">
                {{ ucfirst($visitor->approval_status) }}
            </span>
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dt class="text-sm text-gray-600">Flat</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $visitor->flat->full_name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600">Purpose</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $visitor->purpose }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600">Entry Time</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $visitor->entry_time->format('d M Y, h:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600">Exit Time</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $visitor->exit_time ? $visitor->exit_time->format('d M Y, h:i A') : 'Still Inside' }}</dd>
            </div>
            @if($visitor->approver)
            <div>
                <dt class="text-sm text-gray-600">Approved By</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $visitor->approver->name }}</dd>
            </div>
            @endif
        </dl>

        @if($visitor->approval_status === 'pending')
        <div class="mt-6 pt-6 border-t flex space-x-3">
            <form method="POST" action="{{ route('visitors.approve', $visitor) }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Approve
                </button>
            </form>
            <form method="POST" action="{{ route('visitors.reject', $visitor) }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Reject
                </button>
            </form>
        </div>
        @endif

        @if(!$visitor->exit_time)
        <div class="mt-6 pt-6 border-t">
            <form method="POST" action="{{ route('visitors.exit', $visitor) }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Record Exit
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection