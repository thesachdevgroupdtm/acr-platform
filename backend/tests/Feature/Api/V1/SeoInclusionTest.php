<?php

use App\Models\ServiceCategory;
use App\Models\ServiceCenter;

/**
 * Phase 4.5c — API endpoint additivity: each customer-facing
 * endpoint exposes a flat `seo` key matching SeoFlatData.
 *
 * We don't assert content (the cascade pulls dynamic defaults that
 * may differ across envs) — we assert STRUCTURE so the frontend
 * SeoHead can render unconditionally.
 */

it('GET /api/v1/home includes the flat seo key', function () {
    $response = $this->getJson('/api/v1/home');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'seo' => [
            'meta_title',
            'meta_description',
            'meta_keywords',
            'canonical_url',
            'robots_meta',
            'og_title',
            'og_description',
            'og_image',
            'og_type',
            'twitter_card',
            'twitter_title',
            'twitter_description',
            'twitter_image',
            'schema_jsonld',
        ],
    ]);
});

it('GET /api/v1/services includes the flat seo key', function () {
    $response = $this->getJson('/api/v1/services');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'seo' => ['meta_title', 'meta_description', 'og_image', 'twitter_card'],
    ]);
});

it('GET /api/v1/services/{slug} includes the cascade-resolved category seo', function () {
    $cat = ServiceCategory::create([
        'name'        => 'Test SeoCat',
        'slug'        => 'test-seocat-' . uniqid(),
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    $response = $this->getJson('/api/v1/services/' . $cat->slug);
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'category' => ['id', 'slug', 'title'],
        'seo'      => ['meta_title', 'meta_description', 'og_image'],
    ]);
});

it('GET /api/v1/service-centers includes the flat seo key', function () {
    $response = $this->getJson('/api/v1/service-centers');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'service_centers',
        'seo' => ['meta_title', 'meta_description', 'og_image'],
    ]);
});

it('GET /api/v1/service-centers/{slug} returns single-center cascade seo', function () {
    $center = ServiceCenter::create([
        'slug'       => 'test-detail-center-' . uniqid(),
        'name'       => 'Test Detail Center',
        'address'    => '123 Test',
        'phone'      => '9999999999',
        'city'       => 'Delhi',
        'state'      => 'Delhi NCR',
        'pincode'    => '110001',
        'is_active'  => true,
        'sort_order' => 99,
    ]);

    $response = $this->getJson('/api/v1/service-centers/' . $center->slug);
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'service_center' => ['id', 'slug', 'name'],
        'seo'            => ['meta_title', 'meta_description', 'og_image', 'schema_jsonld'],
    ]);
});

it('GET /api/v1/service-centers/{slug} returns 404 for unknown slug', function () {
    $response = $this->getJson('/api/v1/service-centers/nope-' . uniqid());
    $response->assertNotFound();
    $response->assertJson(['success' => false]);
});
