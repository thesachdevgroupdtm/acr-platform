<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Phase 4.5a — operator-managed 301/302 redirect.
 *
 * Phase 4.5a ships the table + lookup helper only. Phase 4.5b
 * adds the catch-all middleware that consults this table at
 * request time. Phase 6 will increment `hits` (deferred to
 * avoid converting a read into a write per request).
 */
class UrlRedirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'is_active',
        'hits',
        'notes',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'status_code' => 'integer',
        'hits'        => 'integer',
    ];

    /**
     * Lookup an active redirect by exact `from_path` match.
     * Returns null when no row exists OR the row is disabled.
     */
    public static function findActiveFor(string $path): ?self
    {
        return static::where('from_path', $path)
            ->where('is_active', true)
            ->first();
    }
}
