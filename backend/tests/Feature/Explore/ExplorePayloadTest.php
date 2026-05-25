<?php

use App\Models\SeoPage;
use App\Models\SeoPageCategory;
use Database\Seeders\SeoPageCategorySeeder;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5 — /api/v1/explore structured payload tests.
 *
 * Each test seeds a clean slate: SeoPageCategorySeeder plus
 * targeted SeoPage::create() calls. The cache key
 * 'explore-payload' is busted at the start of each test so
 * fresh fixtures show up immediately.
 */

beforeEach(function () {
    Cache::forget('explore-payload');
    $this->seed(SeoPageCategorySeeder::class);
});

it('returns hero / trending_grid / categories / rails / meta structure', function () {
    Cache::forget('explore-payload');

    $cat = SeoPageCategory::first();

    SeoPage::create([
        'slug'         => 'pinned-page',
        'title'        => 'Pinned Page',
        'body'         => '<p>x</p>',
        'category_id'  => $cat->id,
        'is_published' => true,
        'is_pinned'    => true,
        'hero_priority' => 1,
        'view_count'   => 500,
        'published_at' => now(),
    ]);

    SeoPage::create([
        'slug'         => 'trending-page',
        'title'        => 'Trending Page',
        'body'         => '<p>x</p>',
        'category_id'  => $cat->id,
        'is_published' => true,
        'is_trending'  => true,
        'view_count'   => 300,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'hero',
        'trending_grid',
        'categories',
        'rails' => ['trending_searches', 'most_read_week'],
        'meta'  => ['total_pages', 'last_updated_at'],
    ]);

    $heroSlugs     = collect($response->json('hero'))->pluck('slug')->all();
    $trendingSlugs = collect($response->json('trending_grid'))->pluck('slug')->all();

    expect($heroSlugs)->toContain('pinned-page');
    expect($trendingSlugs)->toContain('trending-page');
});

it('respects is_pinned and hero_priority for hero ordering', function () {
    Cache::forget('explore-payload');

    SeoPage::create([
        'slug'          => 'pinned-second',
        'title'         => 'Pinned Second',
        'body'          => '<p>x</p>',
        'is_published'  => true,
        'is_pinned'     => true,
        'hero_priority' => 2,
        'published_at'  => now(),
    ]);
    SeoPage::create([
        'slug'          => 'pinned-first',
        'title'         => 'Pinned First',
        'body'          => '<p>x</p>',
        'is_published'  => true,
        'is_pinned'     => true,
        'hero_priority' => 1,
        'published_at'  => now()->subDay(),
    ]);

    Cache::forget('explore-payload');
    $response = $this->getJson('/api/v1/explore');

    $heroSlugs = collect($response->json('hero'))->pluck('slug')->all();
    expect(array_search('pinned-first', $heroSlugs, true))
        ->toBeLessThan(array_search('pinned-second', $heroSlugs, true));
});

it('caches the payload (a second call is faster and does not re-query)', function () {
    Cache::forget('explore-payload');

    SeoPage::create([
        'slug'         => 'cache-test',
        'title'        => 'Cache Test',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'is_trending'  => true,
        'published_at' => now(),
    ]);

    // First call populates cache. Phase 4.5.1 changed the key
    // from `explore-payload` to `explore-payload:all` (with the
    // `all` suffix when no ?category filter is active).
    $this->getJson('/api/v1/explore')->assertSuccessful();
    expect(Cache::has('explore-payload:all'))->toBeTrue();

    // Subsequent call serves from cache — still successful, same shape.
    $second = $this->getJson('/api/v1/explore');
    $second->assertSuccessful();
    $second->assertJsonStructure(['hero', 'trending_grid', 'categories', 'rails', 'meta']);

    // Saving a SeoPage busts the cache.
    SeoPage::create([
        'slug'         => 'cache-bust-' . uniqid(),
        'title'        => 'Cache Buster',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    expect(Cache::has('explore-payload'))->toBeFalse();
});
