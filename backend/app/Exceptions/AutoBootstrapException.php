<?php

namespace App\Exceptions;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — typed exception for the auto-bootstrap
 * pipeline. Factories embed the failure reason directly into the
 * message so the operator notification + log line tell you exactly
 * what tripped the rollback.
 */
class AutoBootstrapException extends \RuntimeException
{
    public static function parseError(string $reason): self
    {
        return new self("Bootstrap parse error: {$reason}");
    }

    public static function persistenceError(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Bootstrap persistence error: {$reason}", 0, $previous);
    }

    public static function invalidShape(mixed $shape, string $reason): self
    {
        $dump = is_scalar($shape)
            ? var_export($shape, true)
            : json_encode($shape, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return new self("Bootstrap invalid shape: {$reason}. Got: {$dump}");
    }
}
