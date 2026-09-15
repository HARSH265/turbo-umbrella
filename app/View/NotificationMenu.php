<?php

namespace App\View;

use ArrayAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Lazy backing for the navbar notification bell.
 *
 * The View composer used to run an unread count and a recent-notifications query on
 * every page render — for both layouts.app and layouts.sidebar, so four queries per
 * page, including pages that never show the bell.
 *
 * Implementing ArrayAccess keeps the existing Blade syntax working unchanged
 * ($notificationMenu['unreadCount']) while deferring each query until something
 * actually reads it. Registered as a scoped binding, so both layouts share one
 * instance and the queries run at most once per request.
 */
final class NotificationMenu implements ArrayAccess
{
    private ?int $unreadCount = null;

    private ?Collection $recent = null;

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['unreadCount', 'recent'], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'unreadCount' => $this->unreadCount ??= $this->resolveUnreadCount(),
            'recent' => $this->recent ??= $this->resolveRecent(),
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('The notification menu is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('The notification menu is read-only.');
    }

    private function resolveUnreadCount(): int
    {
        return Auth::check() ? Auth::user()->unreadNotifications()->count() : 0;
    }

    private function resolveRecent(): Collection
    {
        return Auth::check()
            ? Auth::user()->notifications()->latest()->limit(5)->get()
            : collect();
    }
}
