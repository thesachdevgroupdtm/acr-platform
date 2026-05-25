<?php

use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * Phase 4.5c — direct unit-style coverage of the trait. We exercise
 * it through a tiny anonymous-class harness because the real
 * consumers (Filament page classes) are tested via the resource
 * suite below.
 *
 * The harness simulates the two Filament hooks the trait targets:
 *   - $form->getRawState() — return a dict of form values
 *   - $this->record         — the Eloquent model to upsert SEO on
 */

function makeSeoHarness($record, array $formState): object
{
    return new class($record, $formState) {
        use HandlesSeoFormPersistence;
        public $record;
        public $form;
        public function __construct($record, array $formState)
        {
            $this->record = $record;
            $this->form = new class($formState) {
                public function __construct(private array $state) {}
                public function getRawState(): array { return $this->state; }
            };
        }
        public function publicFieldNames(): array { return $this->seoFieldNames(); }
        public function publicSave(): void { $this->saveSeoFromForm(); }
        public function publicLoad(array $data): array { return $this->loadSeoIntoForm($data); }
    };
}

it('returns the 20 canonical SEO field names', function () {
    $harness = makeSeoHarness(null, []);
    $names = $harness->publicFieldNames();

    expect($names)->toHaveCount(20)
        ->and($names)->toContain('meta_title')
        ->and($names)->toContain('meta_description')
        ->and($names)->toContain('og_image')
        ->and($names)->toContain('twitter_card')
        ->and($names)->toContain('schema_type')
        ->and($names)->toContain('schema_data')
        ->and($names)->toContain('custom_jsonld')
        ->and($names)->toContain('include_in_sitemap')
        ->and($names)->toContain('priority')
        ->and($names)->toContain('changefreq');
});

it('saveSeoFromForm upserts the slice of form state matching SEO field names', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Test Category',
        'slug'        => 'test-cat-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    // Form state includes non-SEO keys; the trait must ignore them.
    $harness = makeSeoHarness($cat, [
        'name'             => 'Test Category',  // non-SEO → ignored
        'slug'             => 'test-cat',       // non-SEO → ignored
        'meta_title'       => 'Custom Title',
        'meta_description' => 'Custom Description',
        'og_image'         => 'https://example.com/img.jpg',
        'schema_type'      => 'Service',
        'twitter_card'     => 'summary_large_image',
        'meta_keywords'    => '',               // empty → skipped
        'canonical_url'    => null,             // null → skipped
    ]);
    $harness->publicSave();

    $cat->refresh();
    expect($cat->seoMetadata)->not->toBeNull();
    expect($cat->seoMetadata->meta_title)->toBe('Custom Title');
    expect($cat->seoMetadata->meta_description)->toBe('Custom Description');
    expect($cat->seoMetadata->og_image)->toBe('https://example.com/img.jpg');
    expect($cat->seoMetadata->schema_type)->toBe('Service');
    // Empty / null were skipped — the cascade keeps falling back.
    expect($cat->seoMetadata->meta_keywords)->toBeNull();
});

it('loadSeoIntoForm merges seoMetadata into the form data array', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Loadable Cat',
        'slug'        => 'loadable-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);
    $cat->setSeoData([
        'meta_title'       => 'Loaded Title',
        'meta_description' => 'Loaded Desc',
        'schema_type'      => 'BreadcrumbList',
    ]);
    $cat->refresh();

    $harness = makeSeoHarness($cat, []);
    $merged = $harness->publicLoad([
        'name' => 'Existing Name',  // pre-existing form value preserved
    ]);

    expect($merged['name'])->toBe('Existing Name');
    expect($merged['meta_title'])->toBe('Loaded Title');
    expect($merged['meta_description'])->toBe('Loaded Desc');
    expect($merged['schema_type'])->toBe('BreadcrumbList');
});
