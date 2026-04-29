<?php

/**
 * app/Http/Controllers/NoticeController.php
 */

namespace App\Http\Controllers;

use App\Enums\NoticeCategory;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Enums\NoticeVisibility;
use App\Http\Requests\StoreNoticeRequest;
use App\Http\Requests\UpdateNoticeRequest;
use App\Models\Notice;
use App\Models\Society;
use App\Models\User;
use App\Services\NoticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function __construct(
        protected NoticeService $noticeService,
    ) {
        $this->middleware('permission:notices.view')->only(['index', 'show', 'noticeboard']);
        $this->middleware('permission:notices.create')->only(['create', 'store']);
        $this->middleware('permission:notices.update')->only(['edit', 'update']);
        $this->middleware('permission:notices.delete')->only('destroy');
        $this->middleware('permission:notices.publish')->only('publish');
        $this->middleware('permission:notices.archive')->only('archive');
        $this->middleware('permission:notices.pin')->only('pin');
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        if ($user->isSocietyAdmin() || $user->isSuperAdmin()) {
            $notices = $this->noticeService->getAdminNotices($user);
        } else {
            $query = Notice::query()
                ->with(['society', 'creator', 'targetUser', 'attachments'])
                ->forUser($user)
                ->active();

            if ($request->filled('category')) {
                $query->byCategory($request->string('category')->toString());
            }

            if ($request->filled('priority')) {
                $query->byPriority($request->string('priority')->toString());
            }

            $notices = $query
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->paginate(15)
                ->withQueryString();
        }

        $societies = $this->availableSocietiesFor($user);
        $users = $this->availableUsersFor($user);

        return view('notices.index', [
            'notices' => $notices,
            'societies' => $societies,
            'users' => $users,
            'categories' => NoticeCategory::cases(),
            'priorities' => NoticePriority::cases(),
            'statuses' => NoticeStatus::cases(),
            'visibilities' => NoticeVisibility::cases(),
        ]);
    }

    public function show(Notice $notice): View
    {
        $this->authorize('view', $notice);

        $notice->load(['society', 'creator', 'targetUser', 'attachments']);

        return view('notices.show', compact('notice'));
    }

    public function create(): View
    {
        $this->authorize('create', Notice::class);

        $user = Auth::user();

        return view('notices.create', [
            'societies' => $this->availableSocietiesFor($user),
            'users' => $this->availableUsersFor($user),
            'categories' => NoticeCategory::cases(),
            'priorities' => NoticePriority::cases(),
            'statuses' => NoticeStatus::cases(),
            'visibilities' => NoticeVisibility::cases(),
        ]);
    }

    public function store(StoreNoticeRequest $request): RedirectResponse
    {
        try {
            $this->noticeService->createNotice(
                $request->validated(),
                $request->file('attachments', []),
                Auth::user()
            );

            return redirect()
                ->to(route('notices.index', absolute: false))
                ->with('success', 'Notice created successfully.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create notice: ' . $e->getMessage()]);
        }
    }

    public function edit(Notice $notice): View
    {
        $this->authorize('update', $notice);

        $user = Auth::user();
        $notice->load(['targetUser', 'attachments']);

        return view('notices.edit', [
            'notice' => $notice,
            'societies' => $this->availableSocietiesFor($user),
            'users' => $this->availableUsersFor($user),
            'categories' => NoticeCategory::cases(),
            'priorities' => NoticePriority::cases(),
            'statuses' => NoticeStatus::cases(),
            'visibilities' => NoticeVisibility::cases(),
        ]);
    }

    public function update(UpdateNoticeRequest $request, Notice $notice): RedirectResponse
    {
        $this->authorize('update', $notice);

        try {
            $notice = $this->noticeService->updateNotice(
                $notice,
                $request->validated(),
                $request->file('attachments', [])
            );

            return redirect()
                ->to(route('notices.show', ['notice' => $notice], absolute: false))
                ->with('success', 'Notice updated successfully.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update notice: ' . $e->getMessage()]);
        }
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $this->authorize('delete', $notice);

        try {
            $this->noticeService->deleteNotice($notice);

            return redirect()
                ->to(route('notices.index', absolute: false))
                ->with('success', 'Notice deleted successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to delete notice: ' . $e->getMessage()]);
        }
    }

    public function publish(Notice $notice): RedirectResponse
    {
        $this->authorize('publish', $notice);

        try {
            $this->noticeService->publishNotice($notice);

            return back()->with('success', 'Notice published successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to publish notice: ' . $e->getMessage()]);
        }
    }

    public function archive(Notice $notice): RedirectResponse
    {
        $this->authorize('archive', $notice);

        try {
            $this->noticeService->archiveNotice($notice);

            return back()->with('success', 'Notice archived successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to archive notice: ' . $e->getMessage()]);
        }
    }

    public function pin(Notice $notice): RedirectResponse
    {
        $this->authorize('pin', $notice);

        try {
            $this->noticeService->pinNotice($notice);

            return back()->with('success', 'Notice pin status updated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to update pin status: ' . $e->getMessage()]);
        }
    }

    public function noticeboard(): View
    {
        $data = $this->noticeService->getNoticeBoardData(Auth::user());

        return view('notices.noticeboard', $data);
    }

    private function availableSocietiesFor(User $user)
    {
        return $user->isSuperAdmin()
            ? Society::active()->get()
            : Society::active()->whereKey($user->society_id)->get();
    }

    private function availableUsersFor(User $user)
    {
        return User::query()
            ->with('roles')
            ->withRole('resident')
            ->active()
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->where('society_id', $user->society_id);
            })
            ->get();
    }
}
