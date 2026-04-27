<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SystemConfigService
 * 
 * Manages dynamic system configuration
 * Provides cached access to settings
 * 
 * Purpose: Centralize configuration management
 * Input: Config keys and values
 * Output: Config values or update status
 * Side Effects: Updates database and cache
 */
class SystemConfigService extends Model
{
    /**
     * Get configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return SystemConfig::getValue($key, $default);
    }

    /**
     * Set configuration value
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public static function set(string $key, $value, string $type = 'string'): bool
    {
        return SystemConfig::setValue($key, $value, $type);
    }

    /**
     * Get multiple configs by group
     * 
     * @param string $group
     * @return array
     */
    public static function getByGroup(string $group): array
    {
        return SystemConfig::byGroup($group)
            ->get()
            ->mapWithKeys(function ($config) {
                return [$config->key => $config->typed_value];
            })
            ->toArray();
    }

    /**
     * Initialize default configurations
     * Run this in seeder
     * 
     * @return void
     */
    public static function initializeDefaults(): void
    {
        $defaults = [
            // File settings
            ['key' => 'max_file_size', 'value' => '5', 'type' => 'integer', 'group' => 'file', 'description' => 'Maximum file upload size in MB'],
            ['key' => 'allowed_file_types', 'value' => json_encode(['jpg', 'png', 'pdf', 'docx']), 'type' => 'json', 'group' => 'file', 'description' => 'Allowed file extensions'],
            
            // Pagination
            ['key' => 'pagination_limit', 'value' => '20', 'type' => 'integer', 'group' => 'general', 'description' => 'Default pagination limit'],
            
            // Maintenance
            ['key' => 'maintenance_due_day', 'value' => '5', 'type' => 'integer', 'group' => 'maintenance', 'description' => 'Day of month for maintenance due date'],
            ['key' => 'late_fee_percentage', 'value' => '2', 'type' => 'integer', 'group' => 'maintenance', 'description' => 'Late fee percentage'],
            
            // Notifications
            ['key' => 'enable_email_notifications', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Enable email notifications'],
            ['key' => 'enable_sms_notifications', 'value' => 'false', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Enable SMS notifications'],
        ];

        foreach ($defaults as $config) {
            SystemConfig::firstOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }

    /**
     * Clear configuration cache
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        SystemConfig::clearCache();
    }
}
