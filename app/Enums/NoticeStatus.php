<?php

/**
 * app/Enums/NoticeStatus.php
 */

namespace App\Enums;

enum NoticeStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'amber',
            self::PUBLISHED => 'emerald',
            self::ARCHIVED => 'zinc',
        };
    }
}
