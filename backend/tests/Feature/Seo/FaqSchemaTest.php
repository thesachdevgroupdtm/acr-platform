<?php

use App\Models\Faq;
use App\Models\SeoPage;
use App\Services\SchemaTemplateEngine;

/**
 * Phase 4.5d — FAQ schema wiring (Path B from PHASE4_5D_AUDIT.md).
 *
 * Three tests cover:
 *   1. Faq model basic save round-trip.
 *   2. /api/v1/faqs returns active rows in sort_order, excludes inactive.
 *   3. SchemaTemplateEngine emits a valid FAQPage JSON-LD from the
 *      faqs table when a SeoMetadata row has schema_type=FAQPage.
 */

it('Faq model persists question + answer with sort_order and is_active', function () {
    $faq = Faq::create([
        'question'   => 'Test question?',
        'answer'     => 'Test answer.',
        'sort_order' => 5,
        'is_active'  => true,
    ]);

    $fresh = $faq->fresh();
    expect($fresh->question)->toBe('Test question?');
    expect($fresh->answer)->toBe('Test answer.');
    expect($fresh->sort_order)->toBe(5);
    expect($fresh->is_active)->toBeTrue();
});

it('GET /api/v1/faqs returns active rows in sort_order, hides inactive', function () {
    // Inactive row must NOT appear.
    Faq::create([
        'question'   => 'Inactive Q?',
        'answer'     => 'A',
        'sort_order' => 0,
        'is_active'  => false,
    ]);
    Faq::create([
        'question'   => 'Visible later',
        'answer'     => 'A',
        'sort_order' => 20,
        'is_active'  => true,
    ]);
    Faq::create([
        'question'   => 'Visible first',
        'answer'     => 'A',
        'sort_order' => 10,
        'is_active'  => true,
    ]);

    $response = $this->getJson('/api/v1/faqs');

    $response->assertSuccessful();
    $faqs = $response->json('faqs');

    expect($faqs)->toHaveCount(2);
    expect($faqs[0]['question'])->toBe('Visible first');
    expect($faqs[1]['question'])->toBe('Visible later');
});

it('SchemaTemplateEngine emits FAQPage JSON-LD from active Faq rows', function () {
    Faq::create([
        'question'   => 'Do you offer pickup?',
        'answer'     => 'Yes — across Delhi NCR.',
        'sort_order' => 0,
        'is_active'  => true,
    ]);
    Faq::create([
        'question'   => 'Are prices transparent?',
        'answer'     => 'Itemised estimates always.',
        'sort_order' => 1,
        'is_active'  => true,
    ]);

    $page = SeoPage::create([
        'slug'         => 'faq-page-test',
        'title'        => 'FAQ',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);
    $page->setSeoData(['schema_type' => 'FAQPage']);
    $page->refresh();

    $engine = app(SchemaTemplateEngine::class);
    $rendered = $engine->generate($page->seoMetadata);

    expect($rendered)->not->toBeNull();
    $decoded = json_decode($rendered, true);

    expect($decoded['@context'])->toBe('https://schema.org');
    expect($decoded['@type'])->toBe('FAQPage');
    expect($decoded['mainEntity'])->toBeArray();
    expect(count($decoded['mainEntity']))->toBeGreaterThanOrEqual(2);

    $first = $decoded['mainEntity'][0];
    expect($first['@type'])->toBe('Question');
    expect($first['name'])->toBe('Do you offer pickup?');
    expect($first['acceptedAnswer']['@type'])->toBe('Answer');
    expect($first['acceptedAnswer']['text'])->toBe('Yes — across Delhi NCR.');
});
