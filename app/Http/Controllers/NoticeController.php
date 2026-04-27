<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreNoticeRequest;
use App\Http\Requests\UpdateNoticeRequest;
use App\Models\Notice;
use App\Models\Society;
use App\Models\User;
use App\Services\FileService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * NoticeController
 * 
 * Manages society notices and announcements
 * Handles creation, publishing, and recipient management
 */
class NoticeController extends Controller
{
    protected FileService $fileService;
    protected ActivityLogService $activityLog;

    public function __construct(FileService $fileService, ActivityLogService $activityLog)
    {
        $this->fileService = $fileService;
        $this->activityLog = $activityLog;
        
        $this->middleware('permission:notices.view')->only(['index', 'show']);
        $this->middleware('permission:notices.create')->only(['create', 'store']);
        $this->middleware('permission:notices.update')->only(['edit', 'update']);
        $this->middleware('permission:notices.delete')->only('destroy');
    }

    /**
     * Display list of notices
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Notice::with(['society', 'creator']);

        // For residents, show only notices visible to them
        if ($user->isResident()) {
            $query->forUser($user->id);
        }

        // Filter by society (for admins)
        if ($request->filled('society_id')) {
            $query->where('society_id', $request->society_id);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Show active by default
        if (!$request->has('show_all')) {
            $query->active();
        }

        $notices = $query->latest('publish_date')->paginate(20);
        $societies = Society::active()->get();

        return view('notices.index', compact('notices', 'societies'));
    }

    /**
     * Show form to create notice
     */
    public function create()
    {
        $societies = Society::active()->get();
        $residents = User::withRole('resident')->active()->get();

        return view('notices.create', compact('societies', 'residents'));
    }

    /**
     * Store new notice
     */
    public function store(StoreNoticeRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();

            $notice = Notice::create($data);

            // Attach recipients for specific visibility
            if ($request->visibility === 'specific' && $request->has('recipients')) {
                $notice->recipients()->attach($request->recipients);
            }

            // Upload files if provided
            if ($request->hasFile('files')) {
                $this->fileService->uploadMultiple(
                    $request->file('files'),
                    'notices',
                    $notice->id
                );
            }

            $this->activityLog->logCreate('notice', $notice->id, $notice->toArray());

            DB::commit();

            return redirect()
                ->route('notices.index')
                ->with('success', 'Notice created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create notice.']);
        }
    }

    /**
     * Display single notice
     */
    public function show(Notice $notice)
    {
        if (!$this->canAccessNotice($notice)) {
            abort(403);
        }

        $notice->load(['society', 'creator', 'files', 'recipients']);

        // Mark as read for specific recipients
        if ($notice->visibility === 'specific' && Auth::user()->isResident()) {
            $notice->markAsReadBy(Auth::id());
        }

        return view('notices.show', compact('notice'));
    }

    /**
     * Show form to edit notice
     */
    public function edit(Notice $notice)
    {
        $notice->load('recipients');
        $societies = Society::active()->get();
        $residents = User::withRole('resident')->active()->get();

        return view('notices.edit', compact('notice', 'societies', 'residents'));
    }

    /**
     * Update notice
     */
    public function update(UpdateNoticeRequest $request, Notice $notice)
    {
        DB::beginTransaction();
        try {
            $oldData = $notice->toArray();
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $notice->update($data);

            // Update recipients for specific visibility
            if ($request->visibility === 'specific') {
                $notice->recipients()->sync($request->recipients ?? []);
            } else {
                $notice->recipients()->detach();
            }

            // Upload new files if provided
            if ($request->hasFile('files')) {
                $this->fileService->uploadMultiple(
                    $request->file('files'),
                    'notices',
                    $notice->id
                );
            }

            $this->activityLog->logUpdate('notice', $notice->id, $oldData, $notice->fresh()->toArray());

            DB::commit();

            return redirect()
                ->route('notices.show', $notice)
                ->with('success', 'Notice updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update notice.']);
        }
    }

    /**
     * Delete notice (soft delete)
     */
    public function destroy(Notice $notice)
    {
        try {
            $this->activityLog->logDelete('notice', $notice->id, $notice->toArray());
            $notice->delete();

            return redirect()
                ->route('notices.index')
                ->with('success', 'Notice deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete notice.']);
        }
    }

    /**
     * Check if current user can access a notice
     */
    private function canAccessNotice(Notice $notice): bool
    {
        $user = Auth::user();

        if ($user->isSuperAdmin() || $user->isSocietyAdmin()) {
            return true;
        }

        if (!$user->isResident()) {
            return false;
        }

        if ($notice->visibility === 'all') {
            return true;
        }

        return $notice->recipients()->where('user_id', $user->id)->exists();
    }
}
