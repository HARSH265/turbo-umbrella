<?php

namespace App\Services;

use App\Models\Flat;
use App\Models\User;
use App\Models\Visitor;

class VisitorAccessService
{
    public function canView(User $user, Visitor $visitor): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$visitor->belongsToSociety($user->society_id)) {
            return false;
        }

        if ($user->hasPermission('visitors.view')) {
            return true;
        }

        if ($user->isResident()) {
            return $visitor->belongsToFlatIds($this->activeFlatIdsFor($user));
        }

        return false;
    }

    public function canApprove(User $user, Visitor $visitor): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$visitor->belongsToSociety($user->society_id)) {
            return false;
        }

        if ($user->isSocietyAdmin()) {
            return true;
        }

        if ($user->isResident()) {
            return $visitor->belongsToFlatIds($this->activeFlatIdsFor($user));
        }

        return false;
    }

    public function canReject(User $user, Visitor $visitor): bool
    {
        return $this->canApprove($user, $visitor);
    }

    public function canManage(User $user, Visitor $visitor): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$visitor->belongsToSociety($user->society_id)) {
            return false;
        }

        if ($user->hasPermission('visitors.update') && !$user->isResident()) {
            return true;
        }

        if ($user->isResident()) {
            return $visitor->belongsToFlatIds($this->activeFlatIdsFor($user));
        }

        return false;
    }

    public function canRecordExit(User $user, Visitor $visitor): bool
    {
        return $visitor->approval_status === 'approved'
            && $visitor->exit_time === null
            && $this->canManage($user, $visitor);
    }

    public function canAccessFlat(User $user, Flat $flat): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $flat->tower?->society_id === $user->society_id;
    }

    public function actionFlags(User $user, Visitor $visitor): array
    {
        $canApproveAction = $visitor->approval_status === 'pending' && $this->canApprove($user, $visitor);

        return [
            'canApproveAction' => $canApproveAction,
            'canRejectAction' => $visitor->approval_status === 'pending' && $this->canReject($user, $visitor),
            'canExitAction' => $this->canRecordExit($user, $visitor),
        ];
    }

    private function activeFlatIdsFor(User $user): array
    {
        return $user->activeFlats()->pluck('flats.id')->map(fn ($id) => (int) $id)->all();
    }
}
