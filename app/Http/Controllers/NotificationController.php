<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * app/Http/Controllers/NotificationController.php
 *
 * Purpose  : Handle notification read/delete actions
 * Used By  : Web routes
 */
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List all notifications for authenticated user
     */
    public function index()
    {
        $user          = Auth::user();
        $notifications = $this->notificationService->getForUser($user);
        $unreadCount   = $this->notificationService->unreadCount($user);

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(string $id)
    {
        $this->notificationService->markAsRead(Auth::user(), $id);

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Open a notification target and mark it as read first.
     */
    public function open(string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return redirect()
                ->route('notifications.index')
                ->withErrors(['error' => 'Notification not found.']);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $url = data_get($notification->data, 'url');

        if ($this->isSafeNotificationUrl($url)) {
            return redirect($url);
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(Auth::user());

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete single notification
     */
    public function destroy(string $id)
    {
        $this->notificationService->deleteNotification(Auth::user(), $id);

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Clear all read notifications
     */
    public function clearRead()
    {
        $count = $this->notificationService->clearRead(Auth::user());

        return back()->with('success', "{$count} read notifications cleared.");
    }

    /**
     * Get unread count (for AJAX navbar badge)
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => $this->notificationService->unreadCount(Auth::user()),
        ]);
    }

    private function isSafeNotificationUrl(mixed $url): bool
    {
        if (!is_string($url) || blank($url)) {
            return false;
        }

        if (Str::startsWith($url, '/')) {
            return true;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        return $appUrl !== '' && Str::startsWith($url, $appUrl . '/');
    }
}
