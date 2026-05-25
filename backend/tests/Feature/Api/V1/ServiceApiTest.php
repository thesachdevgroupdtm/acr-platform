<?php

use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * Sub-phase L1 — public read-only service endpoints (under /public/).
 * Does NOT collide with the existing /api/v1/services endpoint that
 * the frontend already consumes.
 */

function svcL1Cat(string $slug = 'l1-cat'): ServiceCategory
{
    return ServiceCategory::firstOrCreate(
        ['slug' => $slug],
        ['name' => 'L1 Cat', 'position' => 1, 'is_active' => true],
    );
}

it('lists all active public services', function () {
    $cat = svcL1Cat();
    Service::create(['category_id' => $cat->id, 'name' => 'L1 Svc A', 'slug' => 'l1-svc-a-' . uniqid(), 'is_active' => true]);
    Service::create(['category_id' => $cat->id, 'name' => 'L1 Svc B', 'slug' => 'l1-svc-b-' . uniqid(), 'is_active' => true]);

    $response = $this->getJson('/api/v1/public/services');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'category_id', 'hero_image_url', 'short_description', 'description', 'base_price', 'estimated_time']],
            'meta' => ['count'],
        ]);

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('L1 Svc A');
    expect($names)->toContain('L1 Svc B');
});

it('filters services by ?category=:slug', function () {
    $catA = ServiceCategory::create(['name' => 'CatA', 'slug' => 'cat-a-l1-' . uniqid(), 'is_active' => true, 'position' => 1]);
    $catB = ServiceCategory::create(['name' => 'CatB', 'slug' => 'cat-b-l1-' . uniqid(), 'is_active' => true, 'position' => 2]);
    Service::create(['category_id' => $catA->id, 'name' => 'In A', 'slug' => 'in-a-' . uniqid(), 'is_active' => true]);
    Service::create(['category_id' => $catB->id, 'name' => 'In B', 'slug' => 'in-b-' . uniqid(), 'is_active' => true]);

    $response = $this->getJson("/api/v1/public/services?category={$catA->slug}");

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('In A');
    expect($names)->not->toContain('In B');
});

it('shows a single service with nested category', function () {
    $cat = svcL1Cat('l1-cat-show');
    $svc = Service::create([
        'category_id' => $cat->id,
        'name'        => 'Showable',
        'slug'        => 'showable-l1-' . uniqid(),
        'description' => 'A service description for the show endpoint.',
        'is_active'   => true,
    ]);

    $response = $this->getJson("/api/v1/public/services/{$svc->slug}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'category_id', 'category' => ['id', 'name', 'slug']],
        ])
        ->assertJsonPath('data.slug', $svc->slug)
        ->assertJsonPath('data.category.slug', $cat->slug);
});

it('returns 404 for non-existent service slug', function () {
    $response = $this->getJson('/api/v1/public/services/totally-missing-slug');

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'service_not_found');
});
