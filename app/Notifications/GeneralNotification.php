<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * app/Notifications/GeneralNotification.php
 *
 * Purpose  : Single reusable Laravel notification class for all types
 * Used By  : NotificationService
 * Channel  : Database only (for now)
 *
 * Data Structure stored in DB:
 * {
 *   "type"       : "notice_published",
 *   "title"      : "New Notice: Water Supply Shutdown",
 *   "message"    : "A new notice has been published...",
 *   "module"     : "notices",
 *   "entity_id"  : 12,
 *   "url"        : "/notices/12",
 *   "icon"       : "megaphone",
 *   "color"      : "blue"
 * }
 */
class GeneralNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected NotificationType $type,
        protected string           $title,
        protected string           $message,
        protected string           $module,
        protected int              $entityId,
        protected string           $url,
    ) {}

    /**
     * Delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Data stored in notifications table (data column)
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => $this->type->value,
            'title'     => $this->title,
            'message'   => $this->message,
            'module'    => $this->module,
            'entity_id' => $this->entityId,
            'url'       => $this->url,
            'icon'      => $this->type->icon(),
            'color'     => $this->type->color(),
        ];
    }
}
