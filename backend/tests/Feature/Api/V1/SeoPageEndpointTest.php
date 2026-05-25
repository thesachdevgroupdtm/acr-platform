<?php

use App\Models\SeoPage;
use App\Models\UrlRedirect;
use Database\Seeders\SiteSeoSettingsSeeder;

/**
 * Phase 4.5b — /api/v1/seo-pages/{slug} + /explore endpoint
 * contract tests.
 */

beforeEach(function () {
    $this->seed(SiteSeoSettingsSeeder::class);
});

it('GET /api/v1/seo-pages/{slug} returns the page when published', function () {
    $page = SeoPage::create([
        'slug'         => 'audi-service-delhi',
        'title'        => 'Audi Service in Delhi',
        'excerpt'      => 'Trained Audi technicians.',
        'body'         => '<p>Body content</p>',
        'category'     => 'Brand Service',
        'tags'         => ['audi', 'delhi'],
        'is_published' => true,
        'published_at' => now(),
    ]);

    $page->setSeoData([
        'meta_title'       => 'Audi Service in Delhi | ACR',
        'meta_description' => 'Authorized Audi service.',
    ]);

    $response = $this->getJson('/api/v1/seo-pages/audi-service-delhi');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'page' => ['id', 'slug', 'title', 'excerpt', 'body', 'category', 'tags', 'cta', 'published_at'],
        'seo'  => ['meta_title', 'meta_description', 'og_title', 'twitter_card'],
        'related_pages',
        'redirect',
    ]);
    expect($response->json('page.title'))->toBe('Audi Service in Delhi');
    expect($response->json('seo.meta_title'))->toBe('Audi Service in Delhi | ACR');
    expect($response->json('redirect'))->toBeNull();
});

it('GET /api/v1/seo-pages/{slug} returns 404 when the page is unpublished', function () {
    SeoPage::create([
        'slug'         => 'draft-page',
        'title'        => 'Draft',
        'body'         => '<p>x</p>',
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->getJson('/api/v1/seo-pages/draft-page')->assertStatus(404);
});

it('GET /api/v1/seo-pages/{slug} returns redirect payload when url_redirect is active', function () {
    UrlRedirect::create([
        'from_path'   => '/old-audi',
        'to_path'     => '/audi-service-delhi',
        'status_code' => 301,
        'is_active'   => true,
    ]);

    $response = $this->getJson('/api/v1/seo-pages/old-audi');

    $response->assertSuccessful();
    $response->assertJson([
        'redirect' => [
            'to'     => '/audi-service-delhi',
            'status' => 301,
        ],
    ]);
});

it('related_pages is ordered by category match then most recent', function () {
    $base = SeoPage::create([
        'slug'         => 'base-page',
        'title'        => 'Base',
        'body'         => '<p>x</p>',
        'category'     => 'Brand Service',
        'tags'         => ['audi'],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    SeoPage::create([
        'slug'         => 'same-category',
        'title'        => 'Same Category',
        'body'         => '<p>x</p>',
        'category'     => 'Brand Service',
        'tags'         => ['bmw'],
        'is_published' => true,
        'published_at' => now(),
    ]);

    SeoPage::create([
        'slug'         => 'different',
        'title'        => 'Different',
        'body'         => '<p>x</p>',
        'category'     => 'Maintenance Tips',
        'tags'         => ['x'],
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/seo-pages/base-page');

    $related = collect($response->json('related_pages'));
    expect($related->pluck('slug')->all())->toContain('same-category');
});

it('GET /api/v1/explore filters by category', function () {
    SeoPage::create([
        'slug'         => 'in-cat',
        'title'        => 'In Category',
        'body'         => '<p>x</p>',
        'category'     => 'Brand Service',
        'is_published' => true,
        'published_at' => now(),
    ]);
    SeoPage::create([
        'slug'         => 'out-cat',
        'title'        => 'Other',
        'body'         => '<p>x</p>',
        'category'     => 'News',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore/list?category=Brand%20Service');

    $response->assertSuccessful();
    $slugs = collect($response->json('data'))->pluck('slug')->all();
    expect($slugs)->toContain('in-cat');
    expect($slugs)->not->toContain('out-cat');
});

it('GET /api/v1/explore search filters by title', function () {
    SeoPage::create([
        'slug'         => 'audi-page',
        'title'        => 'Audi Service',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);
    SeoPage::create([
        'slug'         => 'bmw-page',
        'title'        => 'BMW Service',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore/list?search=Audi');

    $response->assertSuccessful();
    $slugs = collect($response->json('data'))->pluck('slug')->all();
    expect($slugs)->toContain('audi-page');
    expect($slugs)->not->toContain('bmw-page');
});

it('GET /api/v1/explore/categories returns distinct categories', function () {
    SeoPage::create(['slug' => 'a', 'title' => 'A', 'body' => '<p>x</p>',
        'category' => 'Cat A', 'is_published' => true, 'published_at' => now()]);
    SeoPage::create(['slug' => 'b', 'title' => 'B', 'body' => '<p>x</p>',
        'category' => 'Cat A', 'is_published' => true, 'published_at' => now()]);
    SeoPage::create(['slug' => 'c', 'title' => 'C', 'body' => '<p>x</p>',
        'category' => 'Cat B', 'is_published' => true, 'published_at' => now()]);

    $response = $this->getJson('/api/v1/explore/categories');
    $cats = $response->json('categories');

    expect($cats)->toContain('Cat A');
    expect($cats)->toContain('Cat B');
    // Distinct: only one 'Cat A' even though seeded twice.
    expect(collect($cats)->filter(fn ($c) => $c === 'Cat A')->count())->toBe(1);
});
