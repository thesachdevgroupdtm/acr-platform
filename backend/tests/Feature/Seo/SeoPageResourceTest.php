<?php

use App\Filament\Resources\SeoPageResource;
use App\Models\SeoPage;
use App\Models\User;

/**
 * Phase 4.5b — SeoPageResource access + model behavior.
 *
 * Filament-form-level slug validation lives in the Filament
 * page object and is exercised by the resource list/create
 * tests. The pure-model concerns (HTML sanitization,
 * reservedSlugs membership) are verified directly here.
 */

it('admin can access SeoPageResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(SeoPageResource::getUrl('index'))
        ->assertSuccessful();
});

it('non-admin user is forbidden from SeoPageResource', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(SeoPageResource::getUrl('index'))
        ->assertForbidden();
});

it('SeoPage::reservedSlugs() includes critical system paths', function () {
    $reserved = SeoPage::reservedSlugs();

    // System / route invariants the smoke + admin specs depend on.
    expect($reserved)->toContain('cart');
    expect($reserved)->toContain('admin');
    expect($reserved)->toContain('payment');
    expect($reserved)->toContain('explore');
});

it('SeoPage sanitizeHtml strips disallowed tags but keeps whitelist', function () {
    $dirty = '<p>Hello <strong>world</strong></p>'
        . '<script>alert(1)</script>'
        . '<iframe src="evil"></iframe>'
        . '<h2>Heading</h2>'
        . '<a href="/somewhere">Link</a>';

    $clean = SeoPage::sanitizeHtml($dirty);

    expect($clean)->toContain('<p>Hello <strong>world</strong></p>');
    expect($clean)->toContain('<h2>Heading</h2>');
    expect($clean)->toContain('<a href="/somewhere">Link</a>');
    expect($clean)->not->toContain('<script>');
    expect($clean)->not->toContain('<iframe');
});

it('SeoPage saving event auto-strips script tags from body', function () {
    $page = SeoPage::create([
        'slug'         => 'sanitize-test-' . uniqid(),
        'title'        => 'Sanitize Test',
        'body'         => '<p>Real content</p><script>alert(1)</script>',
        'is_published' => true,
    ]);

    expect($page->fresh()->body)->toContain('<p>Real content</p>');
    expect($page->fresh()->body)->not->toContain('<script>');
});

it('SeoPage auto-stamps published_at on first publish', function () {
    $page = SeoPage::create([
        'slug'  => 'auto-stamp-' . uniqid(),
        'title' => 'Auto-Stamp Test',
        'body'  => '<p>x</p>',
        // Created unpublished
        'is_published' => false,
    ]);

    expect($page->published_at)->toBeNull();

    $page->update(['is_published' => true]);

    expect($page->fresh()->published_at)->not->toBeNull();
});
