<?php

use App\Filament\Resources\SeoPageResource;
use App\Models\SeoPage;

/**
 * Phase 4.5b-fix — Filament Preview URL builder.
 *
 * The Preview action on SeoPageResource must open the customer
 * frontend host (driven by `config('app.frontend_url')`), NOT
 * the Filament /admin host that `url()` would default to.
 *
 * Tests the pure builder rather than the Filament action button —
 * the action's URL closure calls into this helper, so verifying
 * the helper covers the action's contract too.
 */

it('Preview URL helper uses config(app.frontend_url)', function () {
    config(['app.frontend_url' => 'http://localhost:3001']);
    $page = new SeoPage(['slug' => 'preview-test']);

    expect(SeoPageResource::previewUrl($page))->toBe('http://localhost:3001/preview-test');
});

it('Preview URL helper trims trailing slash from frontend_url', function () {
    config(['app.frontend_url' => 'https://acr-mechanics.in/']);
    $page = new SeoPage(['slug' => 'audi-service-delhi']);

    expect(SeoPageResource::previewUrl($page))->toBe('https://acr-mechanics.in/audi-service-delhi');
});

it('Preview URL helper falls back to default when config is empty', function () {
    config(['app.frontend_url' => null]);
    $page = new SeoPage(['slug' => 'fallback-test']);

    // Default is http://localhost:3000 per config/app.php.
    expect(SeoPageResource::previewUrl($page))->toBe('http://localhost:3000/fallback-test');
});
