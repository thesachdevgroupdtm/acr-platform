<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Concerns\CleansOldImage;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * FILEUPLOAD-RECOVERY — CleansOldImage trait (overwrite cleanup),
 * fetchFileInformation(false) config guard, and the normalize command.
 */

beforeEach(fn () => Storage::fake('public'));

// ── CleansOldImage trait (D-FU-3) ───────────────────────────────────

it('deletes the old image file when the path changes to a different extension', function () {
    $brand = CarBrand::factory()->create(['slug' => 'audi', 'image' => 'entity-images/brands/audi.png']);
    Storage::disk('public')->put('entity-images/brands/audi.png', 'old');
    Storage::disk('public')->put('entity-images/brands/audi.webp', 'new');

    $brand->update(['image' => 'entity-images/brands/audi.webp']);

    Storage::disk('public')->assertMissing('entity-images/brands/audi.png'); // no duplicate-ext orphan
    Storage::disk('public')->assertExists('entity-images/brands/audi.webp');
    expect($brand->fresh()->image)->toBe('entity-images/brands/audi.webp');
});

it('does NOT delete on a same-path overwrite (image not dirty)', function () {
    $brand = CarBrand::factory()->create(['slug' => 'audi', 'image' => 'entity-images/brands/audi.png']);
    Storage::disk('public')->put('entity-images/brands/audi.png', 'data');

    $brand->update(['name' => 'Audi AG']); // image unchanged

    Storage::disk('public')->assertExists('entity-images/brands/audi.png');
});

it('deletes the old file when the image is cleared to null', function () {
    $brand = CarBrand::factory()->create(['slug' => 'audi', 'image' => 'entity-images/brands/audi.png']);
    Storage::disk('public')->put('entity-images/brands/audi.png', 'data');

    $brand->update(['image' => null]);

    Storage::disk('public')->assertMissing('entity-images/brands/audi.png');
    expect($brand->fresh()->image)->toBeNull();
});

it('does NOT delete anything on create with an image', function () {
    Storage::disk('public')->put('entity-images/brands/bmw.png', 'data');

    CarBrand::factory()->create(['slug' => 'bmw', 'image' => 'entity-images/brands/bmw.png']);

    Storage::disk('public')->assertExists('entity-images/brands/bmw.png');
});

it('the CleansOldImage trait is applied to all 5 entity models', function () {
    foreach ([CarBrand::class, CarModel::class, FuelType::class, Service::class, ServiceCategory::class] as $model) {
        expect(in_array(CleansOldImage::class, class_uses_recursive($model), true))->toBeTrue();
    }
});

// ── Config guard (D-FU-2): fetchFileInformation(false) ──────────────

it('every entity resource sets fetchFileInformation(false) on its image upload', function () {
    foreach ([
        'CarBrandResource', 'CarModelResource', 'FuelTypeResource', 'ServiceResource', 'ServiceCategoryResource',
    ] as $resource) {
        $src = file_get_contents(app_path("Filament/Resources/{$resource}.php"));
        // both the edit-form field and the list-action field
        expect(substr_count($src, 'fetchFileInformation(false)'))->toBeGreaterThanOrEqual(2);
    }
});

// ── Normalize command (D-FU-5) ──────────────────────────────────────

it('normalizes a JSON-array image value to the embedded clean path', function () {
    $brand = CarBrand::factory()->create(['slug' => 'audi', 'image' => 'entity-images/brands/audi.png']);
    DB::table('car_brands')->where('id', $brand->id)
        ->update(['image' => json_encode(['entity-images/brands/audi.webp'])]);

    Artisan::call('acr:normalize-image-paths');

    expect($brand->fresh()->image)->toBe('entity-images/brands/audi.webp');
});

it('nulls a stray livewire-tmp image value', function () {
    $brand = CarBrand::factory()->create(['slug' => 'bmw', 'image' => 'entity-images/brands/bmw.png']);
    DB::table('car_brands')->where('id', $brand->id)->update(['image' => 'livewire-tmp/abc123.tmp']);

    Artisan::call('acr:normalize-image-paths');

    expect($brand->fresh()->image)->toBeNull();
});

it('leaves a clean relative path untouched', function () {
    $brand = CarBrand::factory()->create(['slug' => 'kia', 'image' => 'entity-images/brands/kia.png']);

    Artisan::call('acr:normalize-image-paths');

    expect($brand->fresh()->image)->toBe('entity-images/brands/kia.png');
});

it('--dry-run reports without writing', function () {
    $brand = CarBrand::factory()->create(['slug' => 'audi', 'image' => 'entity-images/brands/audi.png']);
    DB::table('car_brands')->where('id', $brand->id)->update(['image' => 'livewire-tmp/x.tmp']);

    Artisan::call('acr:normalize-image-paths', ['--dry-run' => true]);

    expect($brand->fresh()->image)->toBe('livewire-tmp/x.tmp'); // unchanged
});
