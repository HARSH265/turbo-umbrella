<?php

/**
 * app/Services/NoticeService.php
 */

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\NoticeStatus;
use App\Enums\NoticeVisibility;
use App\Exceptions\FileException;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class NoticeService
{
    public function __construct(
        protected FileService $fileService,
        protected NotificationService $notificationService,
        protected ActivityLogService $activityLogService,
    ) {
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     * @throws FileException
     */
    public function createNotice(array $data, array $attachments, User $creator): Notice
    {
        return DB::transaction(function () use ($data, $attachments, $creator): Notice {
            $data = $this->normalizeNoticePayload($data, $creator, true);
            $notice = Notice::create($data);

            if ($attachments !== []) {
                $this->fileService->uploadMultiple($attachments, 'notices', $notice->id);
            }

            if ($notice->status === NoticeStatus::PUBLISHED) {
                $notice = $this->publishNotice($notice->fresh(['targetUser']), false);
            }

            $this->activityLogService->log(
                'notice.created',
                'notices',
                $notice->id,
                null,
                Arr::only($notice->fresh()->toArray(), ['id', 'title', 'society_id']),
                $creator->id
            );

            return $notice->fresh(['society', 'creator', 'targetUser']);
        });
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $newAttachments
     * @throws FileException
     */
    public function updateNotice(Notice $notice, array $data, array $newAttachments): Notice
    {
        return DB::transaction(function () use ($notice, $data, $newAttachments): Notice {
            $oldData = $notice->toArray();
            $wasPublished = $notice->isPublished();
            $data = $this->normalizeNoticePayload($data, $notice->creator ?? User::findOrFail($notice->created_by), false);

            $notice->update($data);

            if ($newAttachments !== []) {
                $this->fileService->uploadMultiple($newAttachments, 'notices', $notice->id);
            }

            $notice = $notice->fresh(['targetUser']);

            if (!$wasPublished && $notice->status === NoticeStatus::PUBLISHED) {
                $notice = $this->publishNotice($notice, false);
            }

            $this->activityLogService->log(
                'notice.updated',
                'notices',
                $notice->id,
                $oldData,
                $notice->toArray(),
                auth()->id()
            );

            return $notice->fresh(['society', 'creator', 'targetUser']);
        });
    }

    public function publishNotice(Notice $notice, bool $logActivity = true): Notice
    {
        return DB::transaction(function () use ($notice, $logActivity): Notice {
            $alreadyPublished = $notice->isPublished() && $notice->published_at !== null;

            if (!$alreadyPublished) {
                $publishedAt = $notice->published_at ?? now();

                $notice->forceFill([
                    'status' => NoticeStatus::PUBLISHED,
                    'published_at' => $publishedAt,
                    'publish_date' => $publishedAt->toDateString(),
                    'is_active' => true,
                ])->save();

                $this->dispatchPublishedNotifications($notice->fresh(['targetUser']));
            }

            if ($logActivity) {
                $this->activityLogService->log(
                    'notice.published',
                    'notices',
                    $notice->id,
                    null,
                    [
                        'visibility' => $notice->fresh()->visibility?->value,
                        'target_user_id' => $notice->fresh()->target_user_id,
                        'target_role' => $notice->fresh()->target_role,
                    ],
                    auth()->id()
                );
            }

            return $notice->fresh(['society', 'creator', 'targetUser']);
        });
    }

    public function archiveNotice(Notice $notice): Notice
    {
        return DB::transaction(function () use ($notice): Notice {
            $notice->forceFill(['status' => NoticeStatus::ARCHIVED])->save();

            $this->activityLogService->log(
                'notice.archived',
                'notices',
                $notice->id,
                null,
                ['id' => $notice->id],
                auth()->id()
            );

            return $notice->fresh(['society', 'creator', 'targetUser']);
        });
    }

    public function pinNotice(Notice $notice): Notice
    {
        return DB::transaction(function () use ($notice): Notice {
            $notice->forceFill(['is_pinned' => !$notice->is_pinned])->save();

            $this->activityLogService->log(
                $notice->is_pinned ? 'notice.pinned' : 'notice.unpinned',
                'notices',
                $notice->id,
                null,
                ['id' => $notice->id],
                auth()->id()
            );

            return $notice->fresh(['society', 'creator', 'targetUser']);
        });
    }

    /**
     * @throws FileException
     */
    public function deleteNotice(Notice $notice): bool
    {
        return DB::transaction(function () use ($notice): bool {
            $this->fileService->deleteForEntity('notices', $notice->id);
            $deleted = $notice->delete();

            $this->activityLogService->log(
                'notice.deleted',
                'notices',
                $notice->id,
                $notice->toArray(),
                ['id' => $notice->id, 'title' => $notice->title],
                auth()->id()
            );

            return $deleted;
        });
    }

    public function getNoticesForUser(User $user): Collection
    {
        return Notice::query()
            ->with(['society', 'creator', 'attachments'])
            ->forUser($user)
            ->active()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get();
    }

    public function getNoticeBoardData(User $user): array
    {
        $baseQuery = Notice::query()
            ->forUser($user)
            ->active();

        $pinned = (clone $baseQuery)
            ->with(['society', 'creator'])
            ->pinned()
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $notices = (clone $baseQuery)
            ->with(['society', 'creator'])
            ->where('is_pinned', false)
            ->orderByDesc('published_at')
            ->limit(max(0, 5 - $pinned->count()))
            ->get();

        return [
            'pinned' => $pinned,
            'notices' => $notices,
            'total' => (clone $baseQuery)->count(),
        ];
    }

    public function getAdminNotices(User $admin): LengthAwarePaginator
    {
        $query = Notice::query()->with(['society', 'creator', 'targetUser']);

        if ($admin->isSocietyAdmin()) {
            $query->where('society_id', $admin->society_id);
        }

        if (request()->filled('category')) {
            $query->byCategory(request('category'));
        }

        if (request()->filled('priority')) {
            $query->byPriority(request('priority'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        if (request()->filled('visibility')) {
            $query->where('visibility', request('visibility'));
        }

        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    private function normalizeNoticePayload(array $data, User $creator, bool $isCreate): array
    {
        if ($creator->isSocietyAdmin()) {
            $data['society_id'] = $creator->society_id;
        }

        if (($data['society_id'] ?? null) === null && !$creator->isSuperAdmin()) {
            $data['society_id'] = $creator->society_id;
        }

        $data['is_pinned'] = (bool) ($data['is_pinned'] ?? false);

        if (($data['status'] ?? null) instanceof NoticeStatus) {
            $status = $data['status'];
        } else {
            $status = isset($data['status']) ? NoticeStatus::from((string) $data['status']) : NoticeStatus::DRAFT;
        }

        $data['status'] = $status;

        if ($status !== NoticeStatus::PUBLISHED) {
            $data['published_at'] = null;
        }

        $expiresAt = $data['expires_at'] ?? null;

        if ($status === NoticeStatus::PUBLISHED) {
            $publishedAt = $data['published_at'] ?? now();

            $data['published_at'] = $publishedAt instanceof \Carbon\CarbonInterface
                ? $publishedAt
                : \Illuminate\Support\Carbon::parse($publishedAt);

            $data['publish_date'] = $data['published_at']->toDateString();
        } else {
            $data['publish_date'] = null;
        }

        $data['expiry_date'] = $expiresAt
            ? (\Illuminate\Support\Carbon::parse($expiresAt))->toDateString()
            : null;

        $data['is_active'] = $status === NoticeStatus::PUBLISHED;

        if (($data['visibility'] ?? null) instanceof NoticeVisibility) {
            $visibility = $data['visibility'];
        } else {
            $visibility = isset($data['visibility']) ? NoticeVisibility::from((string) $data['visibility']) : NoticeVisibility::PUBLIC;
        }

        if ($visibility !== NoticeVisibility::PERSONAL) {
            $data['target_user_id'] = null;
        }

        if ($visibility !== NoticeVisibility::ROLE_BASED) {
            $data['target_role'] = null;
        }

        if ($isCreate) {
            $data['created_by'] = $creator->id;
        } else {
            $data['updated_by'] = auth()->id() ?? $creator->id;
        }

        return $data;
    }

    private function dispatchPublishedNotifications(Notice $notice): void
    {
        $url = route('notices.show', ['notice' => $notice], absolute: false);

        if ($notice->isGlobal()) {
            $this->notificationService->sendToAll(
                NotificationType::NOTICE_PUBLISHED,
                'Global Notice: ' . $notice->title,
                $notice->category->label() . ' notice published',
                'notices',
                $notice->id,
                $url
            );

            return;
        }

        if ($notice->visibility === NoticeVisibility::PUBLIC) {
            $this->notificationService->sendToSociety(
                (int) $notice->society_id,
                NotificationType::NOTICE_PUBLISHED,
                'New Notice: ' . $notice->title,
                $notice->category->label() . ' notice published',
                'notices',
                $notice->id,
                $url
            );

            return;
        }

        if ($notice->visibility === NoticeVisibility::PERSONAL && $notice->targetUser !== null) {
            $this->notificationService->sendToUser(
                $notice->targetUser,
                NotificationType::NOTICE_PUBLISHED,
                'You have a new notice',
                $notice->title,
                'notices',
                $notice->id,
                $url
            );

            return;
        }

        if ($notice->visibility === NoticeVisibility::ROLE_BASED && $notice->target_role !== null) {
            $this->notificationService->sendToRole(
                $notice->target_role,
                NotificationType::NOTICE_PUBLISHED,
                'New Notice for ' . $notice->target_role . ': ' . $notice->title,
                $notice->title,
                'notices',
                $notice->id,
                $url,
                $notice->society_id
            );
        }
    }
}
