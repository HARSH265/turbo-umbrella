<?php

/**
 * config/files.php
 * 
 * Centralized File System Configuration
 * 
 * Purpose  : Define per-module file rules, global defaults, and storage settings
 * Used By  : FileService, FileUploadRequest, FilePolicy
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    | Which Laravel disk to use for storing files.
    | Configurable via .env for different environments.
    | Options: local, public, s3
    */
    'default_disk' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Global Max File Size (MB)
    |--------------------------------------------------------------------------
    | Used as fallback if module does not define its own max_size.
    */
    'max_size' => (int) env('MAX_FILE_SIZE_MB', 5),

    /*
    |--------------------------------------------------------------------------
    | Module-Specific File Rules
    |--------------------------------------------------------------------------
    | Each module can define:
    |   - allowed_extensions : whitelist of file extensions
    |   - allowed_mimes      : whitelist of MIME types (prevents spoofing)
    |   - max_size           : max file size in MB (overrides global)
    |   - max_files          : max number of files per entity
    */
    'modules' => [

        'notices' => [
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'],
            'allowed_mimes'      => [
                'image/jpeg',
                'image/png',
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ],
            'max_size'  => 5,  // MB
            'max_files' => 5,
        ],

        'complaints' => [
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'allowed_mimes'      => [
                'image/jpeg',
                'image/png',
                'application/pdf',
            ],
            'max_size'  => 10, // MB
            'max_files' => 10,
        ],

        'users' => [
            'allowed_extensions' => ['jpg', 'jpeg', 'png'],
            'allowed_mimes'      => [
                'image/jpeg',
                'image/png',
            ],
            'max_size'  => 2,  // MB
            'max_files' => 1,
        ],

        'maintenance' => [
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'allowed_mimes'      => [
                'image/jpeg',
                'image/png',
                'application/pdf',
            ],
            'max_size'  => 5,  // MB
            'max_files' => 5,
        ],

    ],

];