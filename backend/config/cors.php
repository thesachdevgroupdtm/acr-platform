<?php

/**
 * CORS for the React frontend dev server (Vite, port 3000, --host=0.0.0.0).
 *
 * The Vite dev server is reachable on multiple hostnames simultaneously:
 * localhost, 127.0.0.1, and any LAN IP the dev box happens to have.
 * The browser sends Origin matching whichever hostname the developer
 * typed, so we must accept all of them. We use allowed_origins for the
 * common loopback hosts and allowed_origins_patterns (regex) for any
 * RFC1918 LAN IP on port 3000.
 *
 * In production, set FRONTEND_URL to the public origin and the regex
 * patterns are harmless (they only match private IP ranges).
 */
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Phase 2.6b-fix — env-driven allowlist (D-2.6b-fix-3).
     *
     * Reads CORS_ALLOWED_ORIGINS as a comma-separated list. The
     * default covers BOTH the Vite dev server (:3000) and the Vite
     * preview server (:4173) on both localhost and 127.0.0.1, so
     * the production-build E2E tests (Phase 2.6b) can hit the
     * real API instead of swallowing CORS errors in a noise filter.
     *
     * FRONTEND_URL still gets appended for backward compatibility
     * with deployments that set only that variable. array_filter
     * drops empty strings if the env value has trailing commas.
     */
    'allowed_origins' => array_values(array_filter(array_unique(array_merge(
        array_map('trim', explode(',', (string) env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:3000,http://127.0.0.1:3000,http://localhost:4173,http://127.0.0.1:4173'
        ))),
        [env('FRONTEND_URL', 'http://localhost:3000')],
    )))),

    'allowed_origins_patterns' => [
        // RFC1918 LAN IPs on either dev (:3000) or preview (:4173).
        '#^http://192\.168\.\d+\.\d+:(3000|4173)$#',
        '#^http://10\.\d+\.\d+\.\d+:(3000|4173)$#',
        '#^http://172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+:(3000|4173)$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
