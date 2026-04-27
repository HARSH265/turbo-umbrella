<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'entity_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logActivity(
        string $action,
        string $module,
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?int $performedBy = null
    ): self {
        return self::create([
            'user_id' => $performedBy ?? Auth::id(),
            'action' => $action,
            'module' => $module,
            'entity_id' => $entityId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getChangesAttribute(): array
    {
        if (!$this->old_data || !$this->new_data) {
            return [];
        }

        $changes = [];
        foreach ($this->new_data as $key => $newValue) {
            $oldValue = $this->old_data[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForEntity($query, string $module, int $entityId)
    {
        return $query->where('module', $module)->where('entity_id', $entityId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
