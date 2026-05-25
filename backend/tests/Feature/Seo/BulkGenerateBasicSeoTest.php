<?php

use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * Phase 4.5d Feature 5c — bulk SEO generation contract.
 *
 * The Filament BulkAction's closure is hard to invoke directly via
 * Pest. These tests exercise the underlying semantics — that
 * setSeoData() with the starter payload produces the expected SEO
 * row, and that the resource's "skip records with existing SEO"
 * guard works — by running the closure inline against a small set
 * of records.
 */

function bulkGenerateBasicSeoServices(\Illuminate\Database\Eloquent\Collection $records): int
{
    $count = 0;
    foreach ($records as $record) {
        if ($record->seoMetadata) {
            continue;
        }
        $record->setSeoData([
            'meta_title'         => $record->name . ' | ACR Mechanics',
            'meta_description'   => sprintf(
                'Professional %s service at ACR Mechanics. Trusted multi-brand workshop in Delhi NCR with skilled technicians and transparent pricing.',
                $record->name
            ),
            'schema_type'        => 'Service',
            'include_in_sitemap' => true,
            'priority'           => 0.6,
            'changefreq'         => 'weekly',
        ]);
        $count++;
    }
    return $count;
}

it('bulk generation creates SEO records for records without seoMetadata', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Bulk Cat',
        'slug'        => 'bulk-cat-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    $svcA = Service::create([
        'name'        => 'Svc A',
        'slug'        => 'svc-a-' . uniqid(),
        'category_id' => $cat->id,
        'base_price'  => 100,
        'is_active'   => true,
    ]);
    $svcB = Service::create([
        'name'        => 'Svc B',
        'slug'        => 'svc-b-' . uniqid(),
        'category_id' => $cat->id,
        'base_price'  => 100,
        'is_active'   => true,
    ]);

    $collection = new \Illuminate\Database\Eloquent\Collection([$svcA, $svcB]);
    $generated = bulkGenerateBasicSeoServices($collection);

    expect($generated)->toBe(2);
    expect($svcA->fresh()->seoMetadata)->not->toBeNull();
    expect($svcA->fresh()->seoMetadata->meta_title)->toBe('Svc A | ACR Mechanics');
    expect($svcA->fresh()->seoMetadata->schema_type)->toBe('Service');
    expect((float) $svcA->fresh()->seoMetadata->priority)->toBe(0.6);
    expect($svcB->fresh()->seoMetadata->meta_title)->toBe('Svc B | ACR Mechanics');
});

it('bulk generation skips records that already have a seoMetadata row', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Bulk Skip Cat',
        'slug'        => 'bulk-skip-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    $hasSeo = Service::create([
        'name'        => 'Has SEO',
        'slug'        => 'has-seo-' . uniqid(),
        'category_id' => $cat->id,
        'base_price'  => 100,
        'is_active'   => true,
    ]);
    $hasSeo->setSeoData([
        'meta_title' => 'Pre-existing Title',
    ]);

    $needsSeo = Service::create([
        'name'        => 'Needs SEO',
        'slug'        => 'needs-seo-' . uniqid(),
        'category_id' => $cat->id,
        'base_price'  => 100,
        'is_active'   => true,
    ]);

    $collection = new \Illuminate\Database\Eloquent\Collection([$hasSeo->fresh(), $needsSeo]);
    $generated = bulkGenerateBasicSeoServices($collection);

    expect($generated)->toBe(1);
    // Existing meta_title preserved exactly.
    expect($hasSeo->fresh()->seoMetadata->meta_title)->toBe('Pre-existing Title');
    expect($hasSeo->fresh()->seoMetadata->meta_description)->toBeNull();
    // New row created on the other record.
    expect($needsSeo->fresh()->seoMetadata)->not->toBeNull();
    expect($needsSeo->fresh()->seoMetadata->meta_title)->toBe('Needs SEO | ACR Mechanics');
});
