<?php

/**
 * app/Models/Notice.php
 */

namespace App\Models;

use App\Enums\NoticeCategory;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Enums\NoticeVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'society_id',
        'title',
        'content',
        'category',
        'priority',
        'status',
        'visibility',
        'target_user_id',
        'target_role',
        'is_pinned',
        'published_at',
        'expires_at',
        'publish_date',
        'expiry_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => NoticeCategory::class,
            'priority' => NoticePriority::class,
            'status' => NoticeStatus::class,
            'visibility' => NoticeVisibility::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'publish_date' => 'date',
            'expiry_date' => 'date',
            'is_pinned' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function attachments()
    {
        return $this->hasMany(File::class, 'entity_id')
            ->where('module', 'notices');
    }

    // Compatibility alias for current controller/views.
    public function files()
    {
        return $this->attachments();
    }

    // Legacy relation kept temporarily until controller/views are refactored.
    public function recipients()
    {
        return $this->belongsToMany(User::class, 'notice_recipients')
            ->withPivot(['is_read', 'read_at']);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', NoticeStatus::PUBLISHED->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->published()
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * @param  User|int  $user
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $user = $user instanceof User ? $user : User::with('roles')->findOrFail($user);
        $roleSlugs = $user->roles->pluck('slug')->all();

        return $query->where(function (Builder $builder) use ($user, $roleSlugs): void {
            $builder->where(function (Builder $publicQuery) use ($user): void {
                $publicQuery->where('visibility', NoticeVisibility::PUBLIC->value)
                    ->where(function (Builder $scopeQuery) use ($user): void {
                        $scopeQuery->whereNull('society_id')
                            ->orWhere('society_id', $user->society_id);
                    });
            })->orWhere(function (Builder $personalQuery) use ($user): void {
                $personalQuery->where('visibility', NoticeVisibility::PERSONAL->value)
                    ->where('target_user_id', $user->id);
            })->orWhere(function (Builder $roleQuery) use ($user, $roleSlugs): void {
                $roleQuery->where('visibility', NoticeVisibility::ROLE_BASED->value)
                    ->whereIn('target_role', $roleSlugs)
                    ->where(function (Builder $scopeQuery) use ($user): void {
                        $scopeQuery->whereNull('society_id')
                            ->orWhere('society_id', $user->society_id);
                    });
            });
        });
    }

    public function scopeByCategory(Builder $query, NoticeCategory|string|null $category): Builder
    {
        if ($category === null || $category === '') {
            return $query;
        }

        $value = $category instanceof NoticeCategory ? $category->value : $category;

        return $query->where('category', $value);
    }

    public function scopeByPriority(Builder $query, NoticePriority|string|null $priority): Builder
    {
        if ($priority === null || $priority === '') {
            return $query;
        }

        $value = $priority instanceof NoticePriority ? $priority->value : $priority;

        return $query->where('priority', $value);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isPublished(): bool
    {
        return $this->status === NoticeStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === NoticeStatus::DRAFT;
    }

    public function isArchived(): bool
    {
        return $this->status === NoticeStatus::ARCHIVED;
    }

    public function isGlobal(): bool
    {
        return $this->society_id === null;
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSocietyAdmin()) {
            return $this->society_id === $user->society_id;
        }

        return $this->created_by === $user->id && $this->isDraft();
    }

    // Legacy helper kept temporarily until controller/views are refactored.
    public function isCurrentlyActive(): bool
    {
        return $this->isPublished() && !$this->isExpired();
    }

    // Legacy helper kept temporarily until recipient workflow is removed.
    public function markAsReadBy(int $userId): void
    {
        $this->recipients()->updateExistingPivot($userId, [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
