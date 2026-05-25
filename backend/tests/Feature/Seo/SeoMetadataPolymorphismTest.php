<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\QueryException;

/**
 * Phase 4.5a — polymorphic SEO relationship tests.
 *
 * Verifies the morphOne / morphTo wiring on the HasSeoMetadata
 * trait and that the unique index on (seoable_type, seoable_id)
 * actually enforces "one SEO record per resource".
 */

it('SeoMetadata can be attached to a Service via morphOne', function () {
    $service = Service::factory()->create();

    $seo = $service->seoMetadata()->create([
        'meta_title'       => 'Test Service',
        'meta_description' => 'Test description',
    ]);

    expect($service->fresh()->seoMetadata)->not->toBeNull();
    expect($service->seoMetadata->meta_title)->toBe('Test Service');
    expect($seo->seoable)->toBeInstanceOf(Service::class);
    expect($seo->seoable->id)->toBe($service->id);
});

it('SeoMetadata can be attached to a ServiceCategory', function () {
    $category = ServiceCategory::factory()->create();

    $category->seoMetadata()->create([
        'meta_title' => 'Category SEO',
    ]);

    expect($category->fresh()->seoMetadata->meta_title)->toBe('Category SEO');
});

it('Each resource can have only one SeoMetadata (unique constraint)', function () {
    $service = Service::factory()->create();
    $service->seoMetadata()->create(['meta_title' => 'First']);

    expect(fn () =>
        $service->seoMetadata()->create(['meta_title' => 'Second'])
    )->toThrow(QueryException::class);
});
