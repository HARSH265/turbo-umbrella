<?php

/**
 * app/Enums/NoticeVisibility.php
 */

namespace App\Enums;

enum NoticeVisibility: string
{
    case PUBLIC = 'public';
    case PERSONAL = 'personal';
    case ROLE_BASED = 'role_based';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PERSONAL => 'Personal',
            self::ROLE_BASED => 'Role Based',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PUBLIC => 'Visible to all eligible users in the target society.',
            self::PERSONAL => 'Visible only to one specific user and administrators.',
            self::ROLE_BASED => 'Visible to users of the selected role and administrators.',
        };
    }
}
