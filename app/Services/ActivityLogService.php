<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function logCreate(string $module, int $entityId, array $data, ?int $performedBy = null): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'create',
            module: $module,
            entityId: $entityId,
            oldData: null,
            newData: $this->sanitizeData($data),
            performedBy: $performedBy
        );
    }

    public function logUpdate(
        string $module,
        int $entityId,
        array $oldData,
        array $newData,
        ?int $performedBy = null
    ): ActivityLog {
        return ActivityLog::logActivity(
            action: 'update',
            module: $module,
            entityId: $entityId,
            oldData: $this->sanitizeData($oldData),
            newData: $this->sanitizeData($newData),
            performedBy: $performedBy
        );
    }

    public function logDelete(string $module, int $entityId, array $data, ?int $performedBy = null): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'delete',
            module: $module,
            entityId: $entityId,
            oldData: $this->sanitizeData($data),
            newData: null,
            performedBy: $performedBy
        );
    }

    public function logLogin(): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'login',
            module: 'auth',
            entityId: Auth::id()
        );
    }

    public function logLogout(): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'logout',
            module: 'auth',
            entityId: Auth::id()
        );
    }

    public function log(
        string $action,
        string $module,
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?int $performedBy = null
    ): ActivityLog {
        return ActivityLog::logActivity(
            action: $action,
            module: $module,
            entityId: $entityId,
            oldData: $oldData ? $this->sanitizeData($oldData) : null,
            newData: $newData ? $this->sanitizeData($newData) : null,
            performedBy: $performedBy
        );
    }

    private function sanitizeData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_token', 'remember_token'];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    public function getEntityLogs(string $module, int $entityId, int $limit = 20)
    {
        return ActivityLog::forEntity($module, $entityId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    public function getUserRecentLogs(int $days = 7, int $limit = 50)
    {
        return ActivityLog::byUser(Auth::id())
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    public function getModuleLogs(string $module, int $limit = 50)
    {
        return ActivityLog::byModule($module)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
