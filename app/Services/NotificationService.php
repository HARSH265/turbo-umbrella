<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\GeneralNotification;

/**
 * app/Services/NotificationService.php
 *
 * Purpose  : Centralized service to send notifications across all modules
 * Used By  : NoticeService, MaintenanceService, ComplaintService, etc.
 *
 * Recipient Modes:
 *   - Single user
 *   - All residents of a society
 *   - All users of a specific role
 *   - All users globally (Super Admin use)
 */
class NotificationService
{
    /*
    |--------------------------------------------------------------------------
    | CORE SEND METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Send notification to a single user
     *
     * @param  User               $user
     * @param  NotificationType   $type
     * @param  string             $title
     * @param  string             $message
     * @param  string             $module
     * @param  int                $entityId
     * @param  string             $url
     * @return void
     */
    public function sendToUser(
        User             $user,
        NotificationType $type,
        string           $title,
        string           $message,
        string           $module,
        int              $entityId,
        string           $url,
    ): void {
        $user->notify(
            new GeneralNotification($type, $title, $message, $module, $entityId, $url)
        );
    }

    /**
     * Send notification to all residents of a society
     *
     * @param  int                $societyId
     * @param  NotificationType   $type
     * @param  string             $title
     * @param  string             $message
     * @param  string             $module
     * @param  int                $entityId
     * @param  string             $url
     * @return void
     */
    public function sendToSociety(
        int              $societyId,
        NotificationType $type,
        string           $title,
        string           $message,
        string           $module,
        int              $entityId,
        string           $url,
    ): void {
        $residents = User::where('society_id', $societyId)
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'resident');
            })
            ->where('is_active', true);

        $this->sendToQuery($residents, $type, $title, $message, $module, $entityId, $url);
    }

    /**
     * Send notification to all users of a specific role
     * Optionally scoped to a society
     *
     * @param  string             $roleName   e.g. 'staff', 'society_admin'
     * @param  NotificationType   $type
     * @param  string             $title
     * @param  string             $message
     * @param  string             $module
     * @param  int                $entityId
     * @param  string             $url
     * @param  int|null           $societyId  null = all societies
     * @return void
     */
    public function sendToRole(
        string           $roleName,
        NotificationType $type,
        string           $title,
        string           $message,
        string           $module,
        int              $entityId,
        string           $url,
        ?int             $societyId = null,
    ): void {
        $query = User::whereHas('roles', function ($q) use ($roleName) {
            $q->where('slug', $roleName);
        });

        $query->where('is_active', true);

        if ($societyId) {
            $query->where('society_id', $societyId);
        }

        $this->sendToQuery($query, $type, $title, $message, $module, $entityId, $url);
    }

    /**
     * Send notification to all active users (global - Super Admin use)
     *
     * @param  NotificationType   $type
     * @param  string             $title
     * @param  string             $message
     * @param  string             $module
     * @param  int                $entityId
     * @param  string             $url
     * @return void
     */
    public function sendToAll(
        NotificationType $type,
        string           $title,
        string           $message,
        string           $module,
        int              $entityId,
        string           $url,
    ): void {
        $this->sendToQuery(
            User::where('is_active', true),
            $type, $title, $message, $module, $entityId, $url
        );
    }

    /*
    |--------------------------------------------------------------------------
    | READ / UNREAD MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Mark a single notification as read
     *
     * @param  User   $user
     * @param  string $notificationId  UUID from notifications table
     * @return bool
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param  User $user
     * @return void
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    /**
     * Get unread notification count for a user
     *
     * @param  User $user
     * @return int
     */
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Get paginated notifications for a user
     *
     * @param  User $user
     * @param  int  $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getForUser(User $user, int $perPage = 15)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete a single notification
     *
     * @param  User   $user
     * @param  string $notificationId
     * @return bool
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        return (bool) $user->notifications()
            ->where('id', $notificationId)
            ->delete();
    }

    /**
     * Delete all read notifications for a user (cleanup)
     *
     * @param  User $user
     * @return int  Number deleted
     */
    public function clearRead(User $user): int
    {
        return $user->readNotifications()->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Send notification to a collection of users
     *
     * @param  \Illuminate\Database\Eloquent\Collection $users
     * @param  NotificationType                         $type
     * @param  string                                   $title
     * @param  string                                   $message
     * @param  string                                   $module
     * @param  int                                      $entityId
     * @param  string                                   $url
     * @return void
     */
    private function sendToCollection(
        $users,
        NotificationType $type,
        string           $title,
        string           $message,
        string           $module,
        int              $entityId,
        string           $url,
    ): void {
        $notification = new GeneralNotification(
            $type, $title, $message, $module, $entityId, $url
        );

        foreach ($users->unique('id') as $user) {
            $user->notify($notification);
        }
    }

    /**
     * Notify every user matching a query, in batches.
     *
     * Recipients used to be loaded with ->get() and notified one at a time, so a
     * society-wide notice held the whole recipient set in memory at once. chunkById()
     * keeps memory flat regardless of society size.
     *
     * Delivery is still sequential within a chunk. With QUEUE_CONNECTION=sync that
     * means the request performs one insert per recipient; switch the queue to
     * 'database' and run `php artisan queue:work` to move it off the request.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    private function sendToQuery(
        $query,
        NotificationType $type,
        string           $title,
        string           $message,
        string           $module,
        int              $entityId,
        string           $url,
    ): void {
        $notification = new GeneralNotification(
            $type, $title, $message, $module, $entityId, $url
        );

        $query->chunkById(200, function ($users) use ($notification) {
            foreach ($users as $user) {
                $user->notify($notification);
            }
        });
    }
}
