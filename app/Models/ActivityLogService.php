<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ActivityLogService
 * 
 * Centralized activity logging service
 * Tracks user actions across all modules
 * 
 * Purpose: Provide audit trail for compliance and debugging
 * Input: Action type, module, entity details, data changes
 * Output: ActivityLog model instance
 * Side Effects: Creates log entries in database
 */

class ActivityLogService extends Model
{
    /**
     * Log a create action
     * 
     * @param string $module
     * @param int $entityId
     * @param array $data
     * @return ActivityLog
     */
    public function logCreate(string $module, int $entityId, array $data): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'create',
            module: $module,
            entityId: $entityId,
            oldData: null,
            newData: $this->sanitizeData($data)
        );
    }

    /**
     * Log an update action
     * 
     * @param string $module
     * @param int $entityId
     * @param array $oldData
     * @param array $newData
     * @return ActivityLog
     */
    public function logUpdate(string $module, int $entityId, array $oldData, array $newData): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'update',
            module: $module,
            entityId: $entityId,
            oldData: $this->sanitizeData($oldData),
            newData: $this->sanitizeData($newData)
        );
    }

    /**
     * Log a delete action
     * 
     * @param string $module
     * @param int $entityId
     * @param array $data
     * @return ActivityLog
     */
    public function logDelete(string $module, int $entityId, array $data): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'delete',
            module: $module,
            entityId: $entityId,
            oldData: $this->sanitizeData($data),
            newData: null
        );
    }

    /**
     * Log a login action
     * 
     * @return ActivityLog
     */
    public function logLogin(): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'login',
            module: 'auth',
            entityId: Auth::id()
        );
    }

    /**
     * Log a logout action
     * 
     * @return ActivityLog
     */
    public function logLogout(): ActivityLog
    {
        return ActivityLog::logActivity(
            action: 'logout',
            module: 'auth',
            entityId: Auth::id()
        );
    }

    /**
     * Log a custom action
     * 
     * @param string $action
     * @param string $module
     * @param int|null $entityId
     * @param array|null $oldData
     * @param array|null $newData
     * @return ActivityLog
     */
    public function log(
        string $action,
        string $module,
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): ActivityLog {
        return ActivityLog::logActivity(
            action: $action,
            module: $module,
            entityId: $entityId,
            oldData: $oldData ? $this->sanitizeData($oldData) : null,
            newData: $newData ? $this->sanitizeData($newData) : null
        );
    }

    /**
     * Sanitize sensitive data before logging
     * Removes passwords, tokens, etc.
     * 
     * @param array $data
     * @return array
     */
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

    /**
     * Get logs for specific entity
     * 
     * @param string $module
     * @param int $entityId
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getEntityLogs(string $module, int $entityId, int $limit = 20)
    {
        return ActivityLog::forEntity($module, $entityId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get recent logs for current user
     * 
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserRecentLogs(int $days = 7, int $limit = 50)
    {
        return ActivityLog::byUser(Auth::id())
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get logs by module
     * 
     * @param string $module
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getModuleLogs(string $module, int $limit = 50)
    {
        return ActivityLog::byModule($module)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
