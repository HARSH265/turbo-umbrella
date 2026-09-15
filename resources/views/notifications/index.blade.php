@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Your notifications</h2>
            <p class="text-sm text-gray-500">{{ $unreadCount }} unread</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Mark all read
                </button>
            </form>

            <form method="POST" action="{{ route('notifications.clear-read') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Clear read
                </button>
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @forelse ($notifications as $notification)
            @php($data = $notification->data)
            <div class="border-b border-gray-100 p-4 last:border-b-0 {{ is_null($notification->read_at) ? 'bg-blue-50/40' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <x-badge :color="match($data['color'] ?? 'zinc') {
                                'blue' => 'zinc',
                                'yellow' => 'amber',
                                'green' => 'emerald',
                                'red' => 'rose',
                                'orange' => 'orange',
                                'purple' => 'purple',
                                default => 'zinc',
                            }">
                                {{ str_replace('_', ' ', $data['type'] ?? 'notification') }}
                            </x-badge>
                            @if (is_null($notification->read_at))
                                <span class="text-xs font-medium text-blue-700">Unread</span>
                            @endif
                        </div>

                        <p class="text-sm font-semibold text-gray-900">{{ $data['title'] ?? 'Notification' }}</p>
                        <p class="text-sm text-gray-600">{{ $data['message'] ?? '' }}</p>
                        <p class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if (!empty($data['url']))
                            <a href="{{ route('notifications.open', $notification->id) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Open
                            </a>
                        @endif

                        @if (is_null($notification->read_at))
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    Mark read
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-sm text-gray-500">
                No notifications yet.
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
@endsection
