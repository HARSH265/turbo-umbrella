<?php
namespace App\Enums;

enum ComplaintStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case DISPUTED = 'disputed';
    case CLOSED = 'closed';

    // Badge colors ke liye helper method (UI clean rahega)
    public function color(): string
{
    return match($this) {
        self::OPEN => 'rose',
        self::IN_PROGRESS => 'amber',
        self::RESOLVED => 'emerald',
        self::DISPUTED => 'purple',
        self::CLOSED => 'zinc',
    };
}
}