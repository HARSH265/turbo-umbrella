<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService
{
    const DEFAULT_TTL = 300; // 5 minutes
    const HOURLY_TTL = 3600;
    const DAILY_TTL = 86400;

    public static function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public static function rememberForever(string $key, callable $callback)
    {
        return Cache::rememberForever($key, $callback);
    }

    public static function flush(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Forget a known set of keys.
     *
     * The database cache store has no pattern/tag support, so callers must name the
     * keys they want gone. This previously took a $pattern it ignored and called
     * Cache::flush(), wiping every cached value in the application.
     */
    public static function flushKeys(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public static function getDashboardStats(?int $societyId = null): array
    {
        $key = $societyId ? "dashboard.stats.{$societyId}" : 'dashboard.stats.global';

        return self::remember($key, self::DEFAULT_TTL, function () use ($societyId) {
            $flatsQuery = DB::table('flats')->where('is_active', true);

            // Pivot table is 'user_role' (singular) — see User::roles().
            $residentsQuery = DB::table('users')
                ->whereNull('users.deleted_at')
                ->where('users.is_active', true)
                ->join('user_role', 'users.id', '=', 'user_role.user_id')
                ->join('roles', 'user_role.role_id', '=', 'roles.id')
                ->where('roles.slug', 'resident');

            if ($societyId) {
                $flatsQuery->whereIn('tower_id', function ($q) use ($societyId) {
                    $q->select('id')->from('towers')->where('society_id', $societyId);
                });
                $residentsQuery->where('users.society_id', $societyId);
            }

            return [
                'total_flats' => (clone $flatsQuery)->count(),
                'occupied_flats' => (clone $flatsQuery)->where('occupancy_status', 'occupied')->count(),
                'total_residents' => (clone $residentsQuery)->distinct('users.id')->count('users.id'),
            ];
        });
    }

    public static function flushDashboardCache(?int $societyId = null): void
    {
        self::flushKeys([
            $societyId ? "dashboard.stats.{$societyId}" : 'dashboard.stats.global',
            $societyId ? "maintenance.stats.{$societyId}" : 'maintenance.stats.global',
            $societyId ? "complaint.stats.{$societyId}" : 'complaint.stats.global',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTICEBOARD VERSIONING
    |--------------------------------------------------------------------------
    | The noticeboard is cached per user ("noticeboard.user.<id>.<society>"), so
    | there is no single key to forget when a notice changes — and the database
    | cache store supports neither tags nor key patterns. Instead the version
    | below is part of every noticeboard cache key: bumping it makes all existing
    | entries unreachable at once, and they expire on their own TTL.
    |
    | Deliberately global rather than per-society, because a notice with a null
    | society_id is visible to every user.
    */

    private const NOTICEBOARD_VERSION_KEY = 'noticeboard.version';

    public static function noticeboardVersion(): int
    {
        return (int) Cache::get(self::NOTICEBOARD_VERSION_KEY, 1);
    }

    public static function bumpNoticeboardVersion(): void
    {
        Cache::forever(self::NOTICEBOARD_VERSION_KEY, self::noticeboardVersion() + 1);
    }

    public static function getMaintenanceStats(?int $societyId = null): array
    {
        $key = $societyId ? "maintenance.stats.{$societyId}" : 'maintenance.stats.global';

        return self::remember($key, self::DEFAULT_TTL, function () use ($societyId) {
            $query = DB::table('maintenances')
                ->join('flats', 'maintenances.flat_id', '=', 'flats.id')
                ->join('towers', 'flats.tower_id', '=', 'towers.id')
                ->whereNull('maintenances.deleted_at');

            if ($societyId) {
                $query->where('towers.society_id', $societyId);
            }

            return [
                'pending' => (clone $query)->where('maintenances.status', 'unpaid')->count(),
                'paid' => (clone $query)->where('maintenances.status', 'paid')->count(),
                'overdue' => (clone $query)->where('maintenances.status', 'overdue')->count(),
                'total_due' => (clone $query)->sum(DB::raw('maintenances.amount - maintenances.amount_paid')),
            ];
        });
    }

    public static function getComplaintStats(?int $societyId = null): array
    {
        $key = $societyId ? "complaint.stats.{$societyId}" : 'complaint.stats.global';

        return self::remember($key, self::DEFAULT_TTL, function () use ($societyId) {
            $query = DB::table('complaints')->whereNull('complaints.deleted_at');

            // complaints has no society_id column; society is reached via flat -> tower.
            if ($societyId) {
                $query->join('flats', 'complaints.flat_id', '=', 'flats.id')
                    ->join('towers', 'flats.tower_id', '=', 'towers.id')
                    ->where('towers.society_id', $societyId);
            }

            return [
                'open' => (clone $query)->where('complaints.status', 'open')->count(),
                'in_progress' => (clone $query)->where('complaints.status', 'in_progress')->count(),
                'resolved' => (clone $query)->where('complaints.status', 'resolved')->count(),
                'disputed' => (clone $query)->where('complaints.status', 'disputed')->count(),
                'closed' => (clone $query)->where('complaints.status', 'closed')->count(),
            ];
        });
    }
}