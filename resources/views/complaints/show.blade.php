@extends('layouts.app')

@section('title', 'Complaint Details')
@section('page-title', 'Complaint Details')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $complaint->ticket_number }}</h1>
                <p class="mt-1 text-sm text-gray-600">Created {{ $complaint->created_at->format('d M Y, h:i A') }}</p>
            </div>
            <a href="{{ route('complaints.index') }}"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Back to List
            </a>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column - Details & Discussion -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Main Complaint Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $complaint->subject }}</h2>
                            <div class="mt-2 flex items-center space-x-3">
                                <!-- Priority Badge -->
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if ($complaint->priority->value === 'urgent') bg-red-100 text-red-800 
                                    @elseif($complaint->priority->value === 'high') bg-orange-100 text-orange-800 
                                    @elseif($complaint->priority->value === 'medium') bg-yellow-100 text-yellow-800 
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($complaint->priority->value) }} Priority
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $complaint->category }}
                                </span>
                            </div>
                        </div>
                        <!-- Status Badge using Enum Color Method -->
                        <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $complaint->status->color() }}">
                            {{ ucfirst($complaint->status->value) }}
                        </span>
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Description</h3>
                        <p class="text-gray-700 whitespace-pre-line">{{ $complaint->description }}</p>
                    </div>

                    @if ($complaint->resolution_note)
                        <div class="border-t mt-4 pt-4 bg-green-50 p-4 rounded-lg">
                            <h3 class="text-sm font-bold text-green-900 mb-2 uppercase">Official Resolution</h3>
                            <p class="text-green-800 whitespace-pre-line">{{ $complaint->resolution_note }}</p>
                            @if ($complaint->resolved_at)
                                <p class="mt-2 text-xs text-green-600">Resolved on
                                    {{ $complaint->resolved_at->format('d M Y, h:i A') }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Attached Files -->
                @if ($complaint->files->isNotEmpty())
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Attached Evidence</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($complaint->files as $file)
                                <div class="border rounded-lg p-3 flex items-center justify-between hover:bg-gray-50">
                                    <div class="flex items-center space-x-3 overflow-hidden">
                                        <svg class="w-6 h-6 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <div class="truncate">
                                            <p class="text-xs font-medium text-gray-900 truncate">
                                                {{ $file->original_name }}</p>
                                            <p class="text-xxs text-gray-500 uppercase">{{ $file->human_size }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('files.download', $file) }}"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-full">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Comments Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversation Thread</h3>

                    <div class="space-y-4 mb-6">
                        @forelse($complaint->comments as $comment)
                            @if (!$comment->is_internal || auth()->user()->isStaff() || auth()->user()->isSocietyAdmin())
                                <div
                                    class="border-l-4 {{ $comment->is_internal ? 'border-yellow-400 bg-yellow-50' : 'border-blue-400 bg-blue-50' }} p-4 rounded">
                                    <div class="flex items-start justify-between mb-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-bold text-gray-900">{{ $comment->user->name }}</span>
                                            @if ($comment->is_internal)
                                                <span
                                                    class="px-2 py-0.5 text-xxs bg-yellow-200 text-yellow-800 rounded uppercase font-bold">Internal
                                                    Note</span>
                                            @endif
                                        </div>
                                        <span
                                            class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-gray-700 text-sm leading-relaxed">{{ $comment->comment }}</p>
                                </div>
                            @endif
                        @empty
                            <div class="text-center py-6 text-gray-500 italic">No communication history yet.</div>
                        @endforelse
                    </div>

                    <!-- Add Comment Form -->
                    @if ($complaint->status->value !== 'closed' || auth()->user()->isSuperAdmin())
                        <form method="POST" action="{{ route('complaints.comment', $complaint) }}" class="border-t pt-4">
                            @csrf
                            <textarea name="comment" rows="3" required placeholder="Write your message here..."
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 mb-3"></textarea>

                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center space-x-4">
                                    @if (!auth()->user()->isResident())
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_internal" value="1"
                                                class="rounded border-gray-300">
                                            <span class="ml-2 text-sm text-gray-600">Internal Note</span>
                                        </label>
                                    @endif
                                </div>
                                <button type="submit"
                                    class="px-6 py-2 bg-gray-900 text-white text-sm font-bold rounded-lg hover:bg-gray-800 transition">
                                    Post Update
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="bg-gray-100 p-4 rounded-lg border text-center">
                            <p class="text-sm text-gray-500 font-medium italic">This ticket is Closed. New comments are
                                disabled.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="space-y-6">
                <!-- Status Timeline Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Tracking</h3>
                    <dl class="space-y-4">
                        <div class="pb-3 border-b">
                            <dt class="text-xs font-medium text-gray-500 uppercase">Current Status</dt>
                            <dd class="mt-2">
                                <span class="px-3 py-1 text-sm font-bold rounded-full {{ $complaint->status->color() }}">
                                    {{ ucfirst($complaint->status->value) }}
                                </span>
                            </dd>
                        </div>
                        <div class="text-sm">
                            <dt class="text-gray-500">Raised By</dt>
                            <dd class="font-medium text-gray-900">{{ $complaint->user->name }}</dd>
                            <dd class="text-xs text-gray-500">{{ $complaint->flat->full_name }}</dd>
                        </div>
                        @if ($complaint->assigned_to)
                            <div class="text-sm">
                                <dt class="text-gray-500">Assigned To</dt>
                                <dd class="font-medium text-blue-600">{{ $complaint->assignedStaff->name }}</dd>
                                <dd class="text-xs text-gray-400">on {{ $complaint->assigned_at->format('d M, Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Admin Quick Actions -->
                @if (auth()->user()->hasPermission('complaints.assign') || auth()->user()->hasPermission('complaints.update'))
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-bold text-gray-900 uppercase mb-4">Management</h3>

                        @if ($complaint->status->value !== 'resolved' && $complaint->status->value !== 'closed')
                            <!-- Assign Form -->
                            <form method="POST" action="{{ route('complaints.assign', $complaint) }}" class="mb-4">
                                @csrf
                                <select name="assigned_to" required class="w-full border-gray-300 rounded-lg text-sm mb-2">
                                    <option value="">Choose Staff...</option>
                                    @foreach (\App\Models\User::withRole('staff')->active()->get() as $staff)
                                        <option value="{{ $staff->id }}"
                                            {{ $complaint->assigned_to == $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                    class="w-full py-2 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 uppercase">
                                    Update Assignee
                                </button>
                            </form>

                            <!-- Manual Status Update -->
                            <form method="POST" action="{{ route('complaints.status', $complaint) }}" class="mb-4">
                                @csrf
                                <select name="status" class="w-full border-gray-300 rounded-lg text-sm mb-2">
                                    @foreach (\App\Enums\ComplaintStatus::cases() as $status)
                                        <option value="{{ $status->value }}"
                                            {{ $complaint->status == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                    class="w-full py-2 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-900 uppercase">
                                    Change Status
                                </button>
                            </form>
                        @endif

                        <!-- Resolve Button -->
                        @if ($complaint->status->value === 'in_progress' || $complaint->status->value === 'disputed')
                            <button onclick="document.getElementById('resolveModal').classList.remove('hidden')"
                                class="w-full py-3 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 shadow-lg transition">
                                Mark as Resolved
                            </button>
                        @endif

                        <!-- Close Button (Admin Only) -->
                        @if ($complaint->status->value === 'resolved' && (auth()->user()->isSuperAdmin() || auth()->user()->isSocietyAdmin()))
                            <form method="POST" action="{{ route('complaints.status', $complaint) }}">
                                @csrf
                                <input type="hidden" name="status" value="closed">
                                <button type="submit"
                                    class="w-full py-3 bg-gray-900 text-white text-sm font-bold rounded-lg hover:bg-black shadow-lg">
                                    Final Close Ticket
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                <!-- Resident Dispute Action -->
                @if (auth()->id() === $complaint->user_id && in_array($complaint->status->value, ['resolved', 'closed']))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-5 text-center shadow-sm">
                        <p class="text-xs text-red-600 font-bold uppercase mb-3">Issue still exists?</p>
                        <button onclick="document.getElementById('disputeModal').classList.remove('hidden')"
                            class="w-full py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700 transition">
                            Reopen & Dispute
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Resolve Modal -->
    <div id="resolveModal"
        class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Provide Resolution Note</h3>
            <form method="POST" action="{{ route('complaints.resolve', $complaint) }}">
                @csrf
                <textarea name="resolution_note" rows="4" required placeholder="Explain what was done to fix this..."
                    class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 mb-4"></textarea>
                <div class="flex space-x-3">
                    <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-bold">Cancel</button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-bold transition">Complete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dispute Modal -->
    <div id="disputeModal"
        class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white border-red-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-red-600 uppercase italic">Escalate Dispute</h3>
                <button onclick="document.getElementById('disputeModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('complaints.dispute', $complaint) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic underline">Reason for
                        Disputing:</label>
                    <textarea name="reason" rows="4" required placeholder="Describe why you are reopening this ticket..."
                        class="w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Upload Visual Proof (Photo/Video):</label>
                    <input type="file" name="files[]" multiple accept="image/*" class="text-sm">
                </div>
                <button type="submit"
                    class="w-full py-3 bg-red-600 text-white rounded-lg font-black uppercase tracking-widest hover:bg-red-700 shadow-xl transition">
                    Confirm Escalation
                </button>
            </form>
        </div>
    </div>
@endsection
