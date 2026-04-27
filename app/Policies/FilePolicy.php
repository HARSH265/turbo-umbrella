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
        // Society admin can access all files in their society scope
        if ($user->isSocietyAdmin()) {
            return true;
        }

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
        // Society admin can delete files in their society
        if ($user->isSocietyAdmin()) {
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

        // Resident can access notice files if notice is published
        return $notice->status->value === 'published';
    }

    private function canAccessComplaintFile(User $user, File $file): bool
    {
        $complaint = \App\Models\Complaint::find($file->entity_id);

        if (!$complaint) {
            return false;
        }

        return $complaint->user_id === $user->id
            || $complaint->assigned_to === $user->id;
    }

    private function canAccessMaintenanceFile(User $user, File $file): bool
    {
        // Resident can access their own maintenance files
        $maintenance = \App\Models\Maintenance::find($file->entity_id);

        if (!$maintenance) {
            return false;
        }

        return $maintenance->flat?->resident_id === $user->id;
    }
}