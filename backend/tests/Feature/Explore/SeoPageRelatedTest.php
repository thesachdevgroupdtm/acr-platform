<?php

use App\Models\SeoPage;
use App\Models\SeoPageCategory;
use Database\Seeders\SeoPageCategorySeeder;

/**
 * Phase 4.5 — related-pages resolution.
 *
 * Curated pivot wins when populated, else falls back to the
 * heuristic in SeoPage::getRelatedPages (same category +
 * shared tags). This test pins both branches.
 */

beforeEach(function () {
    $this->seed(SeoPageCategorySeeder::class);
});

it('auto-suggests related pages from same category when pivot is empty', function () {
    $cat = SeoPageCategory::first();

    $page = SeoPage::create([
        'slug'         => 'main-page',
        'title'        => 'Main',
        'body'         => '<p>x</p>',
        'category'     => $cat->name,
        'category_id'  => $cat->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    SeoPage::create([
        'slug'         => 'sibling-1',
        'title'        => 'Sibling 1',
        'body'         => '<p>x</p>',
        'category'     => $cat->name,
        'category_id'  => $cat->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    SeoPage::create([
        'slug'         => 'sibling-2',
        'title'        => 'Sibling 2',
        'body'         => '<p>x</p>',
        'category'     => $cat->name,
        'category_id'  => $cat->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $related = $page->relatedPages(4);
    $slugs   = $related->pluck('slug')->all();

    expect(count($slugs))->toBeGreaterThanOrEqual(2);
    expect($slugs)->toContain('sibling-1');
    expect($slugs)->toContain('sibling-2');
});

it('curated pivot rows beat the heuristic', function () {
    $cat = SeoPageCategory::first();

    $page = SeoPage::create([
        'slug'         => 'curated-main',
        'title'        => 'Curated Main',
        'body'         => '<p>x</p>',
        'category_id'  => $cat->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $curated = SeoPage::create([
        'slug'         => 'curated-pick',
        'title'        => 'Curated Pick',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now()->subWeek(),
    ]);

    // Sibling that the heuristic would otherwise prefer.
    SeoPage::create([
        'slug'         => 'heuristic-sibling',
        'title'        => 'Heuristic Sibling',
        'body'         => '<p>x</p>',
        'category_id'  => $cat->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    // Attach curated row.
    $page->curatedRelated()->attach($curated->id, ['display_order' => 1]);

    $related = $page->relatedPages(4);
    $slugs   = $related->pluck('slug')->all();

    expect($slugs)->toContain('curated-pick');
    expect($slugs)->not->toContain('heuristic-sibling');
});
