<?php

namespace App\Enums;

enum ComplaintPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function color(): string
    {
        return match($this) {
            self::LOW => 'zinc',      // Soft Grey
            self::MEDIUM => 'amber',  // Soft Yellow
            self::HIGH => 'orange',   // Soft Orange
            self::URGENT => 'rose',   // Soft Red
        };
    }
}