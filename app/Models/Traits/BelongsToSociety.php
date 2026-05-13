<?php

namespace App\Models\Traits;

use App\Models\Society;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSociety
{
    public function scopeForSociety(Builder $query, int $societyId): Builder
    {
        return $query->where('society_id', $societyId);
    }

    public function scopeOwnedByUser(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function belongsToCurrentSociety(?int $societyId): bool
    {
        if (!$societyId) {
            return false;
        }

        return $this->society_id === $societyId;
    }

    public function isInSociety(int $societyId): bool
    {
        return $this->society_id === $societyId;
    }

    public function getSocietyIdAttribute($value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }
}
