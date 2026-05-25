<?php

use App\Models\SeoPage;

/**
 * Phase 4.5b-fix — searchable_text + relevance search tests.
 *
 * Confirms:
 *   - SeoPage::saving event populates searchable_text from
 *     title + excerpt + category + tags + body (HTML stripped).
 *   - The /api/v1/explore search query finds matches in body
 *     content (not just title).
 *   - Title matches outrank body matches by the relevance score.
 */

it('SeoPage saving event populates searchable_text from body content', function () {
    $page = SeoPage::create([
        'slug'         => 'searchable-test-' . uniqid(),
        'title'        => 'Audi Service',
        'excerpt'      => 'Premium Audi care.',
        'body'         => '<p>Premium <strong>Audi service</strong> in Delhi NCR with battery testing</p>',
        'category'     => 'Brand Service',
        'tags'         => ['audi', 'delhi'],
        'is_published' => true,
        'published_at' => now(),
    ]);

    $st = $page->fresh()->searchable_text;

    expect($st)->toContain('Audi service');
    expect($st)->toContain('battery testing');
    expect($st)->toContain('Brand Service');
    expect($st)->toContain('audi');
    // HTML tags must be stripped.
    expect($st)->not->toContain('<p>');
    expect($st)->not->toContain('<strong>');
});

it('Explore search finds keywords from body content (not just title)', function () {
    SeoPage::create([
        'slug'         => 'bmw-search-test-' . uniqid(),
        'title'        => 'BMW Service Special',
        'body'         => '<p>Specialized in carbon ceramic brake replacement and developer-grade diagnostics</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore/list?search=ceramic');

    $response->assertSuccessful();
    $slugs = collect($response->json('data'))->pluck('slug')->all();
    expect($slugs)->toContain(SeoPage::query()->latest()->first()->slug);
});

it('Search relevance: title match outranks body match', function () {
    // Older page: keyword in body only.
    SeoPage::create([
        'slug'         => 'body-only-match',
        'title'        => 'Generic Service Page',
        'body'         => '<p>This page mentions Audi service many times in body</p>',
        'is_published' => true,
        'published_at' => now()->subMinutes(10),
    ]);

    // Newer page: keyword in title.
    SeoPage::create([
        'slug'         => 'title-match',
        'title'        => 'Audi Service Special',
        'body'         => '<p>A different topic entirely</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/explore/list?search=audi');

    $response->assertSuccessful();
    $first = $response->json('data.0.slug');
    expect($first)->toBe('title-match');
});
