<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ComplaintAccessService
{
    public function scopeIndexQuery(Builder $query, User $user): Builder
    {
        if ($user->isResident()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            return $query->where('assigned_to', $user->id);
        }

        if ($user->isSocietyAdmin() && $user->society_id) {
            return $query->whereHas('flat.tower', function ($towerQuery) use ($user) {
                $towerQuery->where('society_id', $user->society_id);
            });
        }

        return $query;
    }

    public function canView(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSocietyAdmin()) {
            return $this->belongsToUsersSociety($user, $complaint);
        }

        if ($user->isResident()) {
            return $complaint->user_id === $user->id;
        }

        if ($user->isStaff()) {
            return $complaint->assigned_to === $user->id;
        }

        return false;
    }

    public function canManage(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSocietyAdmin()) {
            return $this->belongsToUsersSociety($user, $complaint);
        }

        if ($user->isStaff()) {
            return $complaint->assigned_to === $user->id;
        }

        return false;
    }

    public function canDispute(User $user, Complaint $complaint): bool
    {
        return $user->isResident()
            && $complaint->user_id === $user->id
            && $complaint->status->value === 'resolved';
    }

    public function assignableStaff(Complaint $complaint)
    {
        $societyId = $complaint->flat?->tower?->society_id;

        return User::withRole('staff')
            ->active()
            ->when($societyId, function ($query) use ($societyId) {
                $query->where('society_id', $societyId);
            })
            ->get();
    }

    private function belongsToUsersSociety(User $user, Complaint $complaint): bool
    {
        return (bool) ($complaint->flat
            && $complaint->flat->tower
            && $complaint->flat->tower->society_id === $user->society_id);
    }
}
