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
use App\Services\NotificationService;
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
    protected NotificationService $notificationService;

    public function __construct(
        FileService $fileService,
        ActivityLogService $activityLog,
        NotificationService $notificationService
    )
    {
        $this->fileService = $fileService;
        $this->activityLog = $activityLog;
        $this->notificationService = $notificationService;
        
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

        if ($user->isSocietyAdmin() && $user->society_id) {
            $query->where('society_id', $user->society_id);
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

        $notices = $query->latest('publish_date')->paginate(20)->withQueryString();
        $societies = Society::active()->get();

        return view('notices.index', compact('notices', 'societies'));
    }

    /**
     * Show form to create notice
     */
    public function create()
    {
        $user = Auth::user();
        $societies = $user->isSuperAdmin()
            ? Society::active()->get()
            : Society::active()->where('id', $user->society_id)->get();
        $residents = User::withRole('resident')
            ->active()
            ->when($user->isSocietyAdmin(), function ($query) use ($user) {
                $query->where('society_id', $user->society_id);
            })
            ->get();

        return view('notices.create', compact('societies', 'residents'));
    }

    /**
     * Store new notice
     */
    public function store(StoreNoticeRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $data = $request->validated();
            $data['created_by'] = Auth::id();

            if ($user->isSocietyAdmin()) {
                $data['society_id'] = $user->society_id;
            }

            $this->assertRecipientsBelongToSociety($request->input('recipients', []), (int) $data['society_id']);

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
            $this->notifyNoticeRecipients($notice, $request->input('recipients', []));

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
        if (!$this->canAccessNotice($notice)) {
            abort(403);
        }

        $notice->load('recipients');
        $user = Auth::user();
        $societies = $user->isSuperAdmin()
            ? Society::active()->get()
            : Society::active()->where('id', $user->society_id)->get();
        $residents = User::withRole('resident')
            ->active()
            ->when($user->isSocietyAdmin(), function ($query) use ($user) {
                $query->where('society_id', $user->society_id);
            })
            ->get();

        return view('notices.edit', compact('notice', 'societies', 'residents'));
    }

    /**
     * Update notice
     */
    public function update(UpdateNoticeRequest $request, Notice $notice)
    {
        if (!$this->canAccessNotice($notice)) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $oldData = $notice->toArray();
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            if ($user->isSocietyAdmin()) {
                $data['society_id'] = $user->society_id;
            }

            $this->assertRecipientsBelongToSociety($request->input('recipients', []), (int) $data['society_id']);

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
            $this->notifyNoticeRecipients($notice->fresh(), $request->input('recipients', []));

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
        if (!$this->canAccessNotice($notice)) {
            abort(403);
        }

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

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSocietyAdmin()) {
            return $notice->society_id === $user->society_id;
        }

        if (!$user->isResident()) {
            return false;
        }

        if ($notice->visibility === 'all') {
            return true;
        }

        return $notice->recipients()->where('user_id', $user->id)->exists();
    }

    private function assertRecipientsBelongToSociety(array $recipientIds, int $societyId): void
    {
        if (empty($recipientIds)) {
            return;
        }

        $count = User::withRole('resident')
            ->active()
            ->where('society_id', $societyId)
            ->whereIn('id', $recipientIds)
            ->count();

        if ($count !== count($recipientIds)) {
            throw new \InvalidArgumentException('Selected recipients must belong to the selected society.');
        }
    }

    private function notifyNoticeRecipients(Notice $notice, array $recipientIds): void
    {
        $title = 'New notice published';
        $message = $notice->title;
        $url = route('notices.show', $notice);

        if ($notice->visibility === 'all') {
            $this->notificationService->sendToSociety(
                (int) $notice->society_id,
                \App\Enums\NotificationType::NOTICE_PUBLISHED,
                $title,
                $message,
                'notices',
                $notice->id,
                $url
            );

            return;
        }

        if (empty($recipientIds)) {
            return;
        }

        $users = User::withRole('resident')
            ->active()
            ->where('society_id', $notice->society_id)
            ->whereIn('id', $recipientIds)
            ->get();

        foreach ($users as $user) {
            $this->notificationService->sendToUser(
                $user,
                \App\Enums\NotificationType::NOTICE_PUBLISHED,
                $title,
                $message,
                'notices',
                $notice->id,
                $url
            );
        }
    }
}
