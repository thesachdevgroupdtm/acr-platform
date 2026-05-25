<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * L2 — thrown by the bulk image matcher for operator-facing failures
 * (bad/unreadable ZIP, etc.). The Filament page catches it and surfaces
 * the message in a notification.
 */
class BulkImageException extends RuntimeException
{
    public static function cannotOpenZip(string $path): self
    {
        return new self("Couldn't open the ZIP file. Make sure it's a valid .zip archive. (path: {$path})");
    }

    public static function zipNotFound(string $path): self
    {
        return new self("ZIP file not found on disk: {$path}");
    }
}
