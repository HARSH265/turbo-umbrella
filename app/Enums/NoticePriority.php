<?php

/**
 * app/Enums/NoticePriority.php
 */

namespace App\Enums;

enum NoticePriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'zinc',
            self::NORMAL => 'sky',
            self::HIGH => 'orange',
            self::URGENT => 'rose',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-brand-100 text-brand-700 border-brand-200',
            self::NORMAL => 'bg-sky-100 text-sky-700 border-sky-200',
            self::HIGH => 'bg-orange-100 text-orange-700 border-orange-200',
            self::URGENT => 'bg-rose-100 text-rose-700 border-rose-200',
        };
    }
}
