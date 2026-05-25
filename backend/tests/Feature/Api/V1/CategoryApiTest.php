<?php

use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * Sub-phase L1 — public read-only category endpoints.
 */

it('lists all active public categories ordered by position', function () {
    ServiceCategory::create(['name' => 'First',  'slug' => 'first-l1',  'position' => 1, 'is_active' => true]);
    ServiceCategory::create(['name' => 'Second', 'slug' => 'second-l1', 'position' => 2, 'is_active' => true]);
    ServiceCategory::create([
        'name' => 'Auto Cat', 'slug' => 'auto-cat-l1',
        'position' => 99, 'is_active' => true,
        'is_auto_created' => true,
        'include_in_sitemap' => false,
    ]);

    $response = $this->getJson('/api/v1/public/categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'hero_image_url', 'position']],
            'meta' => ['count'],
        ]);

    $slugs = collect($response->json('data'))->pluck('slug');
    expect($slugs)->toContain('first-l1');
    expect($slugs)->toContain('second-l1');
    expect($slugs)->not->toContain('auto-cat-l1');
});

it('lists services within a category at /categories/{slug}/services', function () {
    $cat = ServiceCategory::create(['name' => 'CatWithSvcs', 'slug' => 'cwsvc-l1-' . uniqid(), 'position' => 1, 'is_active' => true]);
    Service::create(['category_id' => $cat->id, 'name' => 'Svc A', 'slug' => 'cwsa-' . uniqid(), 'is_active' => true]);
    Service::create(['category_id' => $cat->id, 'name' => 'Svc B', 'slug' => 'cwsb-' . uniqid(), 'is_active' => true]);

    $response = $this->getJson("/api/v1/public/categories/{$cat->slug}/services");

    $response->assertOk();
    expect(count($response->json('data')))->toBe(2);
});

it('returns 404 when listing services for a non-existent category slug', function () {
    $response = $this->getJson('/api/v1/public/categories/no-such-category/services');

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'category_not_found');
});
