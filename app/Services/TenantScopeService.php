<?php

namespace App\Services;

use App\Models\Society;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TenantScopeService
{
    public function getCurrentUserSocietyId(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        if ($this->isSuperAdmin($user)) {
            return null;
        }

        return $user->society_id;
    }

    public function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('super-admin');
    }

    public function validateAccess(Model $model, ?int $societyId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $userSocietyId = $user->society_id;

        if (!$userSocietyId) {
            return false;
        }

        if ($model->society_id !== $userSocietyId) {
            return false;
        }

        return true;
    }

    public function filterBySociety(Builder $query, string $modelClass): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->isSuperAdmin($user)) {
            return $query;
        }

        $userSocietyId = $user->society_id;

        if (!$userSocietyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('society_id', $userSocietyId);
    }

    public function blockCrossSociety(Model $model, ?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($this->isSuperAdmin($user)) {
            return;
        }

        $userSocietyId = $user->society_id;

        if (!$userSocietyId) {
            abort(403, 'No society assigned');
        }

        if ($model->society_id !== $userSocietyId) {
            abort(403, 'Access denied to this society\'s resources');
        }
    }

    public function scopeForCurrentUser(Builder $query, string $modelClass): Builder
    {
        return $this->filterBySociety($query, $modelClass);
    }

    public function getAllowedSocietyIds(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($this->isSuperAdmin($user)) {
            return Society::pluck('id')->toArray();
        }

        if ($user->society_id) {
            return [$user->society_id];
        }

        return [];
    }

    public function canAccessSociety(?int $societyId): bool
    {
        if (!$societyId) {
            return $this->isSuperAdmin();
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->society_id === $societyId;
    }
}