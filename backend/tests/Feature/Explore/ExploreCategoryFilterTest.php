<?php

use App\Models\SeoPage;
use App\Models\SeoPageCategory;
use Database\Seeders\SeoPageCategorySeeder;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5.1 — `?category={slug}` filter on /api/v1/explore.
 */

beforeEach(function () {
    Cache::flush();
    $this->seed(SeoPageCategorySeeder::class);
});

it('filters payload by category slug', function () {
    $brand = SeoPageCategory::where('slug', 'brand-service')->first();
    $city  = SeoPageCategory::where('slug', 'city-service')->first();

    SeoPage::create([
        'slug'         => 'in-brand',
        'title'        => 'In Brand',
        'body'         => '<p>x</p>',
        'category_id'  => $brand->id,
        'is_published' => true,
        'is_trending'  => true,
        'published_at' => now(),
    ]);
    SeoPage::create([
        'slug'         => 'in-city',
        'title'        => 'In City',
        'body'         => '<p>x</p>',
        'category_id'  => $city->id,
        'is_published' => true,
        'is_trending'  => true,
        'published_at' => now(),
    ]);

    Cache::flush();
    $response = $this->getJson('/api/v1/explore?category=brand-service');

    $response->assertSuccessful();
    $trendingSlugs = collect($response->json('trending_grid'))->pluck('slug')->all();
    expect($trendingSlugs)->toContain('in-brand');
    expect($trendingSlugs)->not->toContain('in-city');

    // Categories block — filtered to just the brand-service block.
    $catSlugs = collect($response->json('categories'))->pluck('slug')->all();
    expect($catSlugs)->toBe(['brand-service']);
});

it('returns full payload when no category param is present', function () {
    $brand = SeoPageCategory::where('slug', 'brand-service')->first();
    $city  = SeoPageCategory::where('slug', 'city-service')->first();

    SeoPage::create([
        'slug' => 'b-page', 'title' => 'B', 'body' => '<p>x</p>',
        'category_id' => $brand->id, 'is_published' => true,
        'is_trending' => true, 'published_at' => now(),
    ]);
    SeoPage::create([
        'slug' => 'c-page', 'title' => 'C', 'body' => '<p>x</p>',
        'category_id' => $city->id, 'is_published' => true,
        'is_trending' => true, 'published_at' => now(),
    ]);

    Cache::flush();
    $response = $this->getJson('/api/v1/explore');
    $response->assertSuccessful();

    $trendingSlugs = collect($response->json('trending_grid'))->pluck('slug')->all();
    expect($trendingSlugs)->toContain('b-page');
    expect($trendingSlugs)->toContain('c-page');
});
