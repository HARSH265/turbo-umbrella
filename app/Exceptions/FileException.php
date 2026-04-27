<?php

namespace App\Exceptions;

use Exception;

/**
 * app/Exceptions/FileException.php
 * 
 * Custom exception for all file-related errors
 * 
 * Purpose  : Provide clear, specific error messages for file operations
 * Used By  : FileService
 */
class FileException extends Exception
{
    /**
     * File type/extension not allowed
     */
    public static function invalidType(string $extension): self
    {
        return new self(
            "File type '.{$extension}' is not allowed for this module.",
            422
        );
    }

    /**
     * MIME type not allowed (spoofing attempt)
     */
    public static function invalidMime(string $mime): self
    {
        return new self(
            "File MIME type '{$mime}' is not permitted.",
            422
        );
    }

    /**
     * File exceeds max size
     */
    public static function fileTooLarge(int $maxMb): self
    {
        return new self(
            "File size exceeds the maximum allowed size of {$maxMb}MB.",
            422
        );
    }

    /**
     * File not found on disk
     */
    public static function notFound(): self
    {
        return new self(
            "File not found on server.",
            404
        );
    }

    /**
     * File upload/storage failed
     */
    public static function uploadFailed(string $reason = ''): self
    {
        $message = "File upload failed.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        return new self($message, 500);
    }

    /**
     * Module not configured in config/files.php
     */
    public static function moduleNotConfigured(string $module): self
    {
        return new self(
            "Module '{$module}' is not configured in config/files.php.",
            500
        );
    }

    /**
     * Max file count exceeded
     */
    public static function maxFilesExceeded(int $max): self
    {
        return new self(
            "Maximum number of files ({$max}) exceeded for this entity.",
            422
        );
    }

    /**
     * Deletion failed
     */
    public static function deletionFailed(string $reason = ''): self
    {
        $message = "File deletion failed.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        return new self($message, 500);
    }
}