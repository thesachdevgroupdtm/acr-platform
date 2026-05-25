<?php

use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\FuelResource;
use App\Models\CarBrand;
use App\Models\FuelType;
use App\Support\ImageUrl;

/**
 * IMAGE-URL-FIX — API image serialization returns fully-qualified storage
 * URLs (not raw relative paths). Host-agnostic assertions (startsWith http
 * + contains /storage/…) so they hold whatever APP_URL is set to.
 */

it('ImageUrl::resolve returns null for null or empty', function () {
    expect(ImageUrl::resolve(null))->toBeNull()
        ->and(ImageUrl::resolve(''))->toBeNull();
});

it('ImageUrl::resolve wraps a relative path into a full /storage URL', function () {
    $url = ImageUrl::resolve('entity-images/brands/audi.webp');

    expect($url)->toStartWith('http')
        ->and($url)->toContain('/storage/entity-images/brands/audi.webp');
});

it('ImageUrl::resolve passes an absolute URL through unchanged (idempotent)', function () {
    expect(ImageUrl::resolve('http://cdn.example.com/x.png'))->toBe('http://cdn.example.com/x.png')
        ->and(ImageUrl::resolve('https://cdn.example.com/x.png'))->toBe('https://cdn.example.com/x.png');

    // Resolving an already-resolved value is stable.
    $once = ImageUrl::resolve('entity-images/brands/audi.webp');
    expect(ImageUrl::resolve($once))->toBe($once);
});

it('BrandResource hero_image_url is a full URL when image is set', function () {
    $brand = CarBrand::factory()->create(['image' => 'entity-images/brands/audi.webp']);

    $data = (new BrandResource($brand))->toArray(request());

    expect($data['hero_image_url'])->toStartWith('http')
        ->and($data['hero_image_url'])->toContain('/storage/entity-images/brands/audi.webp');
});

it('BrandResource hero_image_url is null when image is null', function () {
    $brand = CarBrand::factory()->create(['image' => null]);

    expect((new BrandResource($brand))->toArray(request())['hero_image_url'])->toBeNull();
});

it('FuelResource exposes hero_image_url as a full URL or null', function () {
    $withImage = FuelType::factory()->create(['image' => 'entity-images/fuel-types/petrol.webp']);
    $without   = FuelType::factory()->create(['image' => null]);

    expect((new FuelResource($withImage))->toArray(request())['hero_image_url'])
        ->toContain('/storage/entity-images/fuel-types/petrol.webp');
    expect((new FuelResource($without))->toArray(request())['hero_image_url'])->toBeNull();
});
