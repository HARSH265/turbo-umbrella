<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

/**
 * app/Policies/FilePolicy.php
 * 
 * Authorization policy for file access and deletion
 * 
 * Purpose  : Centralize who can download/delete files
 * Used By  : FileController via $this->authorize()
 */
class FilePolicy
{
    /**
     * Super admin bypasses all checks
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Who can download/view a file
     */
    public function download(User $user, File $file): bool
    {
        // Module-specific access rules
        return match ($file->module) {

            'notices' => $this->canAccessNoticeFile($user, $file),

            'complaints' => $this->canAccessComplaintFile($user, $file),

            'users' => $file->entity_id === $user->id,

            'maintenance' => $this->canAccessMaintenanceFile($user, $file),

            default => false,
        };
    }

    /**
     * Who can delete a file
     */
    public function delete(User $user, File $file): bool
    {
        if ($user->isSocietyAdmin() && $this->belongsToUsersSociety($user, $file)) {
            return true;
        }

        // Uploader can delete their own files
        return $file->uploaded_by === $user->id;
    }

    /*
    |--------------------------------------------------------------------------
    | Module-Specific Private Helpers
    |--------------------------------------------------------------------------
    */

    private function canAccessNoticeFile(User $user, File $file): bool
    {
        $notice = \App\Models\Notice::find($file->entity_id);

        if (!$notice) {
            return false;
        }

        if ($user->isSocietyAdmin()) {
            return $notice->society_id === $user->society_id;
        }

        if ($user->isResident() || $user->isStaff()) {
            if ($notice->isArchived()) {
                return false;
            }

            if ($notice->isGlobal()) {
                return $this->canAccessGlobalNoticeFile($user, $notice);
            }

            if ($notice->society_id !== $user->society_id) {
                return false;
            }

            if ($notice->visibility === \App\Enums\NoticeVisibility::PUBLIC) {
                return true;
            }

            if ($notice->visibility === \App\Enums\NoticeVisibility::PERSONAL) {
                return $notice->target_user_id === $user->id;
            }

            if ($notice->visibility === \App\Enums\NoticeVisibility::ROLE_BASED) {
                return $notice->target_role !== null && $user->hasRole($notice->target_role);
            }
        }

        return false;
    }

    private function canAccessGlobalNoticeFile(User $user, \App\Models\Notice $notice): bool
    {
        if ($notice->visibility === \App\Enums\NoticeVisibility::PUBLIC) {
            return true;
        }

        if ($notice->visibility === \App\Enums\NoticeVisibility::PERSONAL) {
            return $notice->target_user_id === $user->id;
        }

        if ($notice->visibility === \App\Enums\NoticeVisibility::ROLE_BASED) {
            return $notice->target_role !== null && $user->hasRole($notice->target_role);
        }

        return false;
    }

    private function canAccessComplaintFile(User $user, File $file): bool
    {
        $complaint = \App\Models\Complaint::find($file->entity_id);

        if (!$complaint) {
            return false;
        }

        if ($user->isSocietyAdmin()) {
            return $complaint->flat?->tower?->society_id === $user->society_id;
        }

        return $complaint->user_id === $user->id
            || $complaint->assigned_to === $user->id;
    }

    private function canAccessMaintenanceFile(User $user, File $file): bool
    {
        $maintenance = \App\Models\Maintenance::find($file->entity_id);

        if (!$maintenance) {
            return false;
        }

        if ($user->isSocietyAdmin()) {
            return $maintenance->flat?->tower?->society_id === $user->society_id;
        }

        if (!$user->isResident()) {
            return false;
        }

        return $user->activeFlats()
            ->where('flats.id', $maintenance->flat_id)
            ->exists();
    }

    private function belongsToUsersSociety(User $user, File $file): bool
    {
        return match ($file->module) {
            'notices' => \App\Models\Notice::whereKey($file->entity_id)
                ->where('society_id', $user->society_id)
                ->exists(),
            'complaints' => \App\Models\Complaint::whereKey($file->entity_id)
                ->whereHas('flat.tower', function ($query) use ($user) {
                    $query->where('society_id', $user->society_id);
                })
                ->exists(),
            'maintenance' => \App\Models\Maintenance::whereKey($file->entity_id)
                ->whereHas('flat.tower', function ($query) use ($user) {
                    $query->where('society_id', $user->society_id);
                })
                ->exists(),
            'users' => \App\Models\User::whereKey($file->entity_id)
                ->where('society_id', $user->society_id)
                ->exists(),
            default => false,
        };
    }
}
