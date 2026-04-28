<?php

namespace App\Enums;

/**
 * app/Enums/NotificationType.php
 *
 * Purpose  : Define all notification types across modules
 * Used By  : NotificationService, module services
 */
enum NotificationType: string
{
    // Notice Module
    case NOTICE_PUBLISHED = 'notice_published';

    // Maintenance Module
    case MAINTENANCE_DUE      = 'maintenance_due';
    case MAINTENANCE_OVERDUE  = 'maintenance_overdue';
    case MAINTENANCE_UPDATED  = 'maintenance_updated';
    case PAYMENT_RECEIVED     = 'payment_received';

    // Complaint Module
    case COMPLAINT_RAISED  = 'complaint_raised';
    case COMPLAINT_UPDATED = 'complaint_updated';

    // System
    case SYSTEM_ALERT = 'system_alert';

    /**
     * Human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::NOTICE_PUBLISHED  => 'Notice Published',
            self::MAINTENANCE_DUE   => 'Maintenance Due',
            self::MAINTENANCE_OVERDUE => 'Maintenance Overdue',
            self::MAINTENANCE_UPDATED => 'Maintenance Updated',
            self::PAYMENT_RECEIVED  => 'Payment Received',
            self::COMPLAINT_RAISED  => 'Complaint Raised',
            self::COMPLAINT_UPDATED => 'Complaint Updated',
            self::SYSTEM_ALERT      => 'System Alert',
        };
    }

    /**
     * Icon for UI (TailwindCSS / HeroIcons)
     */
    public function icon(): string
    {
        return match($this) {
            self::NOTICE_PUBLISHED    => 'megaphone',
            self::MAINTENANCE_DUE     => 'clock',
            self::MAINTENANCE_OVERDUE => 'exclamation-triangle',
            self::MAINTENANCE_UPDATED => 'arrow-path',
            self::PAYMENT_RECEIVED    => 'check-circle',
            self::COMPLAINT_RAISED    => 'chat-bubble-left',
            self::COMPLAINT_UPDATED   => 'arrow-path',
            self::SYSTEM_ALERT        => 'bell-alert',
        };
    }

    /**
     * Color for UI badge
     */
    public function color(): string
    {
        return match($this) {
            self::NOTICE_PUBLISHED    => 'blue',
            self::MAINTENANCE_DUE     => 'yellow',
            self::MAINTENANCE_OVERDUE => 'red',
            self::MAINTENANCE_UPDATED => 'blue',
            self::PAYMENT_RECEIVED    => 'green',
            self::COMPLAINT_RAISED    => 'orange',
            self::COMPLAINT_UPDATED   => 'purple',
            self::SYSTEM_ALERT        => 'red',
        };
    }
}
