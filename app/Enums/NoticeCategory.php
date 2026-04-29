<?php

/**
 * app/Enums/NoticeCategory.php
 */

namespace App\Enums;

enum NoticeCategory: string
{
    case GENERAL = 'general';
    case MAINTENANCE = 'maintenance';
    case MEETING = 'meeting';
    case EVENT = 'event';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::MAINTENANCE => 'Maintenance',
            self::MEETING => 'Meeting',
            self::EVENT => 'Event',
            self::EMERGENCY => 'Emergency',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GENERAL => 'zinc',
            self::MAINTENANCE => 'amber',
            self::MEETING => 'sky',
            self::EVENT => 'emerald',
            self::EMERGENCY => 'rose',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GENERAL => 'megaphone',
            self::MAINTENANCE => 'wrench-screwdriver',
            self::MEETING => 'users',
            self::EVENT => 'calendar-days',
            self::EMERGENCY => 'exclamation-triangle',
        };
    }
}
