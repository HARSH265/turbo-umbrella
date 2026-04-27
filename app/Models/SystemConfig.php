<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SystemConfig Model
 * 
 * Stores dynamic system configuration
 * Provides cached access to settings
 */
class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_editable',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_editable' => 'boolean',
        ];
    }

    // Cache key prefix
    const CACHE_PREFIX = 'system_config_';

    // Relationships

    /**
     * Get updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper Methods

    /**
     * Get typed value based on type
     * 
     * @return mixed
     */
    public function getTypedValueAttribute()
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get config value by key (cached)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . $key, 3600, function () use ($key, $default) {
            $config = self::where('key', $key)->first();
            return $config ? $config->typed_value : $default;
        });
    }

    /**
     * Set config value
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public static function setValue(string $key, $value, string $type = 'string'): bool
    {
        $config = self::firstOrCreate(['key' => $key]);
        
        $result = $config->update([
            'value' => is_array($value) ? json_encode($value) : $value,
            'type' => $type,
            'updated_by' => Auth::id(),
        ]);

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $result;
    }

    /**
     * Clear all config cache
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    // Scopes

    /**
     * Scope to get configs by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to get editable configs only
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }
}
