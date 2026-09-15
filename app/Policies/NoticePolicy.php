<?php

/**
 * app/Policies/NoticePolicy.php
 */

namespace App\Policies;

use App\Enums\NoticeStatus;
use App\Enums\NoticeVisibility;
use App\Models\Notice;
use App\Models\User;

class NoticePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('notices.view');
    }

    public function view(User $user, Notice $notice): bool
    {
        if ($user->isSocietyAdmin()) {
            return $notice->isGlobal() || $this->belongsToUsersSociety($user, $notice);
        }

        if ($notice->isArchived()) {
            return false;
        }

        if ($notice->isDraft()) {
            return $notice->created_by === $user->id;
        }

        if ($notice->visibility === NoticeVisibility::PERSONAL) {
            return $notice->target_user_id === $user->id;
        }

        if ($notice->visibility === NoticeVisibility::ROLE_BASED) {
            return $user->hasRole((string) $notice->target_role)
                && $this->isVisibleWithinUsersScope($user, $notice);
        }

        return $this->isVisibleWithinUsersScope($user, $notice);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('notices.create')
            && ($user->isSocietyAdmin() || $user->isSuperAdmin());
    }

    public function update(User $user, Notice $notice): bool
    {
        if ($user->isSocietyAdmin()) {
            return $this->belongsToUsersSociety($user, $notice);
        }

        return $notice->created_by === $user->id
            && $notice->status === NoticeStatus::DRAFT;
    }

    public function delete(User $user, Notice $notice): bool
    {
        return $user->isSocietyAdmin()
            && $this->belongsToUsersSociety($user, $notice);
    }

    public function publish(User $user, Notice $notice): bool
    {
        return $user->hasPermission('notices.publish')
            && $user->isSocietyAdmin()
            && $this->belongsToUsersSociety($user, $notice);
    }

    public function archive(User $user, Notice $notice): bool
    {
        return $user->hasPermission('notices.archive')
            && $user->isSocietyAdmin()
            && $this->belongsToUsersSociety($user, $notice);
    }

    public function pin(User $user, Notice $notice): bool
    {
        return $user->hasPermission('notices.pin')
            && $user->isSocietyAdmin()
            && $this->belongsToUsersSociety($user, $notice);
    }

    private function belongsToUsersSociety(User $user, Notice $notice): bool
    {
        if ($notice->society_id === null) {
            return false;
        }

        return $notice->society_id === $user->society_id;
    }

    private function isVisibleWithinUsersScope(User $user, Notice $notice): bool
    {
        if ($notice->isGlobal()) {
            return true;
        }

        return $notice->society_id === $user->society_id;
    }
}
