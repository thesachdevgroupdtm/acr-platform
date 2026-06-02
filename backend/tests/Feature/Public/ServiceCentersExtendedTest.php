<?php

use App\Models\ServiceCenter;
use Illuminate\Support\Facades\Cache;

it('GET /api/v1/service-centers returns rows with the 5 new frontend-parity fields', function () {
    ServiceCenter::create([
        'slug'            => 'test-centre',
        'name'            => 'Test Centre',
        'address'         => '1 Test Road',
        'phone'           => '9000000000',
        'city'            => 'Delhi',
        'state'           => 'Delhi NCR',
        'pincode'         => '110001',
        'is_active'       => true,
        'sort_order'      => 1,
        'rating'          => 4.7,
        'reviews_count'   => '1,200',
        'features'        => ['One', 'Two', 'Three'],
        'image'           => 'https://example.test/img.png',
        'google_maps_url' => 'https://maps.example/test',
    ]);

    $resp = $this->getJson('/api/v1/service-centers');

    $resp->assertOk();
    $resp->assertJsonStructure([
        'service_centers' => [
            '*' => [
                'id', 'slug', 'name', 'address', 'phone', 'email', 'city', 'state', 'pincode',
                'latitude', 'longitude',
                'rating', 'reviews_count', 'features', 'image', 'google_maps_url',
            ],
        ],
        'seo',
    ]);

    $row = collect($resp->json('service_centers'))->firstWhere('slug', 'test-centre');
    expect((float) $row['rating'])->toBe(4.7);
    expect($row['reviews_count'])->toBe('1,200');
    expect($row['features'])->toBe(['One', 'Two', 'Three']);
    expect($row['image'])->toBe('https://example.test/img.png');
    expect($row['google_maps_url'])->toBe('https://maps.example/test');
});

it('features is always an array even when stored NULL', function () {
    ServiceCenter::create([
        'slug'       => 'no-features',
        'name'       => 'No Features',
        'address'    => '1 Road',
        'phone'      => '9000000000',
        'city'       => 'Delhi',
        'state'      => 'Delhi NCR',
        'pincode'    => '110001',
        'is_active'  => true,
        'sort_order' => 1,
    ]);

    $resp = $this->getJson('/api/v1/service-centers');
    $row = collect($resp->json('service_centers'))->firstWhere('slug', 'no-features');

    expect($row['features'])->toBe([]);
    expect($row['rating'])->toBeNull();
    expect($row['reviews_count'])->toBeNull();
    expect($row['image'])->toBeNull();
    expect($row['google_maps_url'])->toBeNull();
});

it('list cache invalidates when a center is saved or deleted', function () {
    $center = ServiceCenter::create([
        'slug'       => 'cache-test',
        'name'       => 'Cache Test',
        'address'    => '1 Road',
        'phone'      => '9000000000',
        'city'       => 'Delhi',
        'state'      => 'Delhi NCR',
        'pincode'    => '110001',
        'is_active'  => true,
        'sort_order' => 1,
        'rating'     => 4.0,
    ]);

    // Prime the cache. Cast to float — JSON round-trip turns 4.0 → 4 (int).
    $first = $this->getJson('/api/v1/service-centers')->json('service_centers');
    $primed = collect($first)->firstWhere('slug', 'cache-test');
    expect((float) $primed['rating'])->toBe(4.0);

    // Update via Eloquent — the saved() hook should flush the cache.
    $center->update(['rating' => 5.0]);

    $second = $this->getJson('/api/v1/service-centers')->json('service_centers');
    $reread = collect($second)->firstWhere('slug', 'cache-test');
    expect((float) $reread['rating'])->toBe(5.0);

    // Delete — re-prime to ensure no stale row remains.
    $center->delete();
    $third = $this->getJson('/api/v1/service-centers')->json('service_centers');
    expect(collect($third)->firstWhere('slug', 'cache-test'))->toBeNull();
});

it('inactive centers are excluded from the public list', function () {
    ServiceCenter::create([
        'slug'       => 'archived',
        'name'       => 'Archived',
        'address'    => '1 Road',
        'phone'      => '9000000000',
        'city'       => 'Delhi',
        'state'      => 'Delhi NCR',
        'pincode'    => '110001',
        'is_active'  => false,
        'sort_order' => 99,
    ]);

    $resp = $this->getJson('/api/v1/service-centers');
    expect(collect($resp->json('service_centers'))->firstWhere('slug', 'archived'))->toBeNull();
});

it('migration adds the 5 new columns to the service_centers table', function () {
    foreach (['rating', 'reviews_count', 'features', 'image', 'google_maps_url'] as $col) {
        expect(\Illuminate\Support\Facades\Schema::hasColumn('service_centers', $col))
            ->toBeTrue("Column {$col} should exist on service_centers");
    }
});
