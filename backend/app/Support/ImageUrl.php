<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * IMAGE-URL-FIX — single source of truth for turning a stored relative
 * image path (e.g. "entity-images/brands/audi.webp") into a fully-qualified
 * public storage URL the frontend can load from any origin.
 *
 * Uses Storage::disk('public')->url(), whose host comes from the public
 * disk's `url` config = env('APP_URL').'/storage' — so it is correct in
 * every environment (no hardcoded host/domain). Set APP_URL accordingly:
 *   local:      http://127.0.0.1:8000  (or http://localhost:8000)
 *   production: https://acr-mechanics.in
 *
 * Behaviour (D-URL-1):
 *   - null/empty   → null   (frontend renders its own fallback)
 *   - http(s):// … → as-is  (idempotent; safe to call twice)
 *   - relative …   → Storage::disk('public')->url($path)
 */
class ImageUrl
{
    public static function resolve(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
