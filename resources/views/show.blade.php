@extends('layouts.app')

@section('title', 'Complaint Details')

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
        <!-- Left Column - Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Complaint Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $complaint->subject }}</h2>
                        <div class="mt-2 flex items-center space-x-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if($complaint->priority === 'urgent') bg-red-100 text-red-800
                                @elseif($complaint->priority === 'high') bg-orange-100 text-orange-800
                                @elseif($complaint->priority === 'medium') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($complaint->priority) }} Priority
                            </span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $complaint->category }}
                            </span>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full
                        @if($complaint->status === 'open') bg-red-100 text-red-800
                        @elseif($complaint->status === 'in_progress') bg-yellow-100 text-yellow-800
                        @elseif($complaint->status === 'resolved') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                    </span>
                </div>

                <div class="border-t pt-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ $complaint->description }}</p>
                </div>

                @if($complaint->resolution_note)
                <div class="border-t mt-4 pt-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Resolution</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ $complaint->resolution_note }}</p>
                    <p class="mt-2 text-xs text-gray-500">Resolved on {{ $complaint->resolved_at->format('d M Y, h:i A') }}</p>
                </div>
                @endif
            </div>

            <!-- Attached Files -->
            @if($complaint->files->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Attached Files</h3>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($complaint->files as $file)
                    <div class="border rounded-lg p-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center space-x-3">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ Str::limit($file->original_name, 20) }}</p>
                                <p class="text-xs text-gray-500">{{ $file->human_size }}</p>
                            </div>
                        </div>
                        <a href="{{ route('files.download', $file) }}" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Comments Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Comments</h3>
                
                <!-- Comments List -->
                <div class="space-y-4 mb-6">
                    @forelse($complaint->comments as $comment)
                    <div class="border-l-4 {{ $comment->is_internal ? 'border-yellow-400 bg-yellow-50' : 'border-blue-400 bg-blue-50' }} p-4 rounded">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-gray-900">{{ $comment->user->name }}</span>
                                @if($comment->is_internal)
                                <span class="px-2 py-0.5 text-xs bg-yellow-200 text-yellow-800 rounded">Internal</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-700 text-sm">{{ $comment->comment }}</p>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 py-4">No comments yet</p>
                    @endforelse
                </div>

                <!-- Add Comment Form -->
                <form method="POST" action="{{ route('complaints.comment', $complaint) }}" class="border-t pt-4">
                    @csrf
                    <textarea name="comment" rows="3" required
                              placeholder="Add a comment..."
                              class="w-full border-gray-300 rounded-lg mb-2"></textarea>
                    
                    @if(Auth::user()->isSuperAdmin() || Auth::user()->isSocietyAdmin() || Auth::user()->isStaff())
                    <div class="flex items-center mb-3">
                        <input type="checkbox" name="is_internal" id="is_internal" value="1" class="rounded border-gray-300">
                        <label for="is_internal" class="ml-2 text-sm text-gray-700">Internal note (not visible to resident)</label>
                    </div>
                    @endif

                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-800">
                        Add Comment
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-6">
            <!-- Quick Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Resident</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $complaint->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Flat</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $complaint->flat->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Tower</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $complaint->flat->tower->name }}</dd>
                    </div>
                    @if($complaint->assignedStaff)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Assigned To</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $complaint->assignedStaff->name }}</dd>
                        <dd class="text-xs text-gray-500">Assigned {{ $complaint->assigned_at->diffForHumans() }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Actions -->
            @if(Auth::user()->hasPermission('complaints.assign') || Auth::user()->hasPermission('complaints.update'))
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                
                <!-- Assign to Staff -->
                @can('complaints.assign')
                @if(!$complaint->isResolved())
                <form method="POST" action="{{ route('complaints.assign', $complaint) }}" class="mb-4">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Staff</label>
                    <select name="assigned_to" class="w-full border-gray-300 rounded-lg mb-2">
                        <option value="">Select staff member</option>
                        @foreach(\App\Models\User::withRole('staff')->active()->get() as $staff)
                        <option value="{{ $staff->id }}" {{ $complaint->assigned_to == $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                        Assign
                    </button>
                </form>
                @endif
                @endcan

                <!-- Update Status -->
                @can('complaints.update')
                @if(!$complaint->isResolved())
                <form method="POST" action="{{ route('complaints.status', $complaint) }}" class="mb-4">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 mb-2">Change Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-lg mb-2">
                        <option value="open" {{ $complaint->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ $complaint->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $complaint->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $complaint->status === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-800">
                        Update Status
                    </button>
                </form>
                @endif

                <!-- Resolve Complaint -->
                @if($complaint->status === 'in_progress' && !$complaint->isResolved())
                <button onclick="document.getElementById('resolveModal').classList.remove('hidden')" 
                        class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    Mark as Resolved
                </button>
                @endif
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Resolve Modal -->
<div id="resolveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resolve Complaint</h3>
            <form method="POST" action="{{ route('complaints.resolve', $complaint) }}">
                @csrf
                <textarea name="resolution_note" rows="4" required
                          placeholder="Enter resolution details..."
                          class="w-full border-gray-300 rounded-lg mb-4"></textarea>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Resolve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
