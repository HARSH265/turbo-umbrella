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

    public static function flushPattern(string $pattern): void
    {
        Cache::flush();
    }

    public static function getDashboardStats(?int $societyId = null): array
    {
        $key = $societyId ? "dashboard.stats.{$societyId}" : 'dashboard.stats.global';

        return self::remember($key, self::DEFAULT_TTL, function () use ($societyId) {
            $flatsQuery = DB::table('flats')
                ->where('is_active', true)
                ->where('occupancy_status', 'occupied');

            $residentsQuery = DB::table('users')
                ->where('is_active', true)
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('roles.slug', 'resident');

            if ($societyId) {
                $flatsQuery->whereIn('tower_id', function ($q) use ($societyId) {
                    $q->select('id')->from('towers')->where('society_id', $societyId);
                });
                $residentsQuery->where('users.society_id', $societyId);
            }

            return [
                'total_flats' => (clone $flatsQuery)->count(),
                'occupied_flats' => (clone $flatsQuery)->count(),
                'total_residents' => (clone $residentsQuery)->distinct('users.id')->count('users.id'),
            ];
        });
    }

    public static function flushDashboardCache(?int $societyId = null): void
    {
        self::flush($societyId ? "dashboard.stats.{$societyId}" : 'dashboard.stats.global');
        self::flush('dashboard.noticeboard');
    }

    public static function getMaintenanceStats(?int $societyId = null): array
    {
        $key = $societyId ? "maintenance.stats.{$societyId}" : 'maintenance.stats.global';

        return self::remember($key, self::DEFAULT_TTL, function () use ($societyId) {
            $query = DB::table('maintenances')
                ->join('flats', 'maintenances.flat_id', '=', 'flats.id')
                ->join('towers', 'flats.tower_id', '=', 'towers.id');

            if ($societyId) {
                $query->where('towers.society_id', $societyId);
            }

            return [
                'pending' => (clone $query)->where('status', 'unpaid')->count(),
                'paid' => (clone $query)->where('status', 'paid')->count(),
                'overdue' => (clone $query)->where('status', 'overdue')->count(),
                'total_due' => (clone $query)->sum(DB::raw('amount - amount_paid')),
            ];
        });
    }

    public static function getComplaintStats(?int $societyId = null): array
    {
        $key = $societyId ? "complaint.stats.{$societyId}" : 'complaint.stats.global';

        return self::remember($key, self::DEFAULT_TTL, function () use ($societyId) {
            $query = DB::table('complaints');

            if ($societyId) {
                $query->where('society_id', $societyId);
            }

            return [
                'open' => (clone $query)->where('status', 'open')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'resolved' => (clone $query)->where('status', 'resolved')->count(),
                'closed' => (clone $query)->where('status', 'closed')->count(),
            ];
        });
    }
}