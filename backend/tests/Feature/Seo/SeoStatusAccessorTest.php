<?php

use App\Models\ServiceCategory;

/**
 * Phase 4.5d Feature 5b — `seo_status` accessor on HasSeoMetadata.
 *
 * Three states: 'none' (no metadata row), 'partial' (row missing
 * meta_title or meta_description), 'complete' (both present).
 *
 * Drives the Filament IconColumn in the four resource list pages.
 */

it('seo_status returns none when no seoMetadata row exists', function () {
    $cat = ServiceCategory::create([
        'name'        => 'No SEO Cat',
        'slug'        => 'no-seo-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    expect($cat->seo_status)->toBe('none');
});

it('seo_status returns partial when only meta_title is set', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Partial Cat',
        'slug'        => 'partial-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);
    $cat->setSeoData(['meta_title' => 'Only Title']);
    $cat->refresh();

    expect($cat->seo_status)->toBe('partial');
});

it('seo_status returns partial when only meta_description is set', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Partial Desc Cat',
        'slug'        => 'partial-desc-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);
    $cat->setSeoData(['meta_description' => 'Only Description']);
    $cat->refresh();

    expect($cat->seo_status)->toBe('partial');
});

it('seo_status returns complete when both meta_title and meta_description are set', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Complete Cat',
        'slug'        => 'complete-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);
    $cat->setSeoData([
        'meta_title'       => 'Title',
        'meta_description' => 'Description',
    ]);
    $cat->refresh();

    expect($cat->seo_status)->toBe('complete');
});
