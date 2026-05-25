<?php

use App\Models\SeoPage;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5 — view-count tracking endpoint.
 *
 * POST /api/v1/seo-pages/{slug}/track-view increments
 * view_count once per (IP+slug) per 10 minutes. Subsequent
 * calls within the window return ok=true, counted=false.
 */

it('rate-limits view_count increments per IP+slug', function () {
    $page = SeoPage::create([
        'slug'         => 'view-track-' . uniqid(),
        'title'        => 'Track Me',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
        'view_count'   => 0,
    ]);

    // Bust any leftover fingerprint from previous test runs.
    Cache::forget("view-track:127.0.0.1:{$page->slug}");

    // First call counts.
    $first = $this->postJson("/api/v1/seo-pages/{$page->slug}/track-view");
    $first->assertSuccessful();
    expect($first->json('counted'))->toBeTrue();
    expect($first->json('view_count'))->toBe(1);

    // Second call within the rate-limit window does NOT count.
    $second = $this->postJson("/api/v1/seo-pages/{$page->slug}/track-view");
    $second->assertSuccessful();
    expect($second->json('counted'))->toBeFalse();

    // DB reflects only the first increment.
    expect((int) $page->fresh()->view_count)->toBe(1);
});

it('returns 404 for unpublished pages', function () {
    SeoPage::create([
        'slug'         => 'draft-page',
        'title'        => 'Draft',
        'body'         => '<p>x</p>',
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->postJson('/api/v1/seo-pages/draft-page/track-view')
        ->assertStatus(404);
});
