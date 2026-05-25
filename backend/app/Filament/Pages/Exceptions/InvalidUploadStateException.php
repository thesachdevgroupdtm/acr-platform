<?php

namespace App\Filament\Pages\Exceptions;

/**
 * Phase 4.3.4 — thrown when Filament's FileUpload state can't be
 * normalized to an existing absolute file path. The factory below
 * embeds the raw shape into the exception message so the operator
 * notification (and the laravel.log error line) tell you exactly
 * what the FileUpload component handed us.
 */
class InvalidUploadStateException extends \RuntimeException
{
    public static function fromShape(mixed $shape, string $reason): self
    {
        $dump = is_scalar($shape)
            ? var_export($shape, true)
            : json_encode($shape, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return new self("Invalid upload state: {$reason}. Got: {$dump}");
    }
}
