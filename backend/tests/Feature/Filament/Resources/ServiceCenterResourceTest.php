<?php

use App\Filament\Resources\ServiceCenterResource;
use App\Models\ServiceCenter;
use App\Models\User;

/**
 * Phase 4.5c — ServiceCenterResource access + SEO persistence smoke.
 *
 * Filament uses its own Livewire test helper for full form
 * round-trip coverage; the suite here verifies the resource's
 * authorization gates and the model-level SEO upsert path that the
 * resource exercises in production.
 */

it('admin can access ServiceCenterResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(ServiceCenterResource::getUrl('index'))
        ->assertSuccessful();
});

it('non-admin user is forbidden from ServiceCenterResource', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(ServiceCenterResource::getUrl('index'))
        ->assertForbidden();
});

it('ServiceCenter setSeoData persists schema_type=LocalBusiness via the trait', function () {
    $center = ServiceCenter::create([
        'slug'      => 'test-center-' . uniqid(),
        'name'      => 'Test Center',
        'address'   => '123 Test Street',
        'phone'     => '9999999999',
        'city'      => 'Delhi',
        'state'     => 'Delhi NCR',
        'pincode'   => '110001',
        'is_active' => true,
        'sort_order' => 99,
    ]);

    $center->setSeoData([
        'meta_title'       => 'Test Center | ACR',
        'meta_description' => 'Visit our Test center.',
        'schema_type'      => 'LocalBusiness',
    ]);
    $center->refresh();

    expect($center->seoMetadata)->not->toBeNull();
    expect($center->seoMetadata->schema_type)->toBe('LocalBusiness');
    expect($center->seoMetadata->meta_title)->toBe('Test Center | ACR');
});
