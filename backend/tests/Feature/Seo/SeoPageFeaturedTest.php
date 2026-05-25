<?php

use App\Models\SeoPage;

/**
 * Phase 4.5b-polish — featured flag + sort param tests.
 *
 * Confirms:
 *   - is_featured persists round-trip (fillable + boolean cast)
 *   - GET /api/v1/explore?featured=true returns only featured pages
 *   - GET /api/v1/explore?sort=newest orders by published_at desc
 */

it('is_featured persists round-trip with boolean cast', function () {
    $page = SeoPage::create([
        'slug'         => 'featured-roundtrip-' . uniqid(),
        'title'        => 'Featured Roundtrip',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'is_featured'  => false,
        'published_at' => now(),
    ]);

    expect($page->is_featured)->toBeFalse();

    $page->update(['is_featured' => true]);
    expect($page->fresh()->is_featured)->toBeTrue();
    expect($page->fresh()->is_featured)->toBeBool();
});

it('explore?featured=true returns only featured pages', function () {
    SeoPage::create([
        'slug'         => 'feat-yes',
        'title'        => 'Featured One',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'is_featured'  => true,
        'published_at' => now(),
    ]);
    SeoPage::create([
        'slug'         => 'feat-no',
        'title'        => 'Standard One',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'is_featured'  => false,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore/list?featured=true');

    $response->assertSuccessful();
    $slugs = collect($response->json('data'))->pluck('slug')->all();
    expect($slugs)->toContain('feat-yes');
    expect($slugs)->not->toContain('feat-no');
});

it('explore?sort=newest orders by published_at desc', function () {
    $older = SeoPage::create([
        'slug'         => 'older-page',
        'title'        => 'Older',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now()->subDays(5),
    ]);
    $newer = SeoPage::create([
        'slug'         => 'newer-page',
        'title'        => 'Newer',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore/list?sort=newest');
    $response->assertSuccessful();

    // Newer page must appear before older page in the result.
    $slugs    = collect($response->json('data'))->pluck('slug')->all();
    $newerIdx = array_search('newer-page', $slugs, true);
    $olderIdx = array_search('older-page', $slugs, true);

    expect($newerIdx)->not->toBeFalse();
    expect($olderIdx)->not->toBeFalse();
    expect($newerIdx)->toBeLessThan($olderIdx);
});
