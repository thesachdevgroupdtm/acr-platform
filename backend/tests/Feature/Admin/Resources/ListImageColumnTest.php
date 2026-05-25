<?php

use App\Filament\Resources\CarBrandResource;
use App\Filament\Resources\CarModelResource;
use App\Filament\Resources\FuelTypeResource;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\ServiceResource;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

/**
 * IMAGE-SYSTEM-FIXES (PART C) — each resource list table renders with the
 * new image column + inline "upload image" row action. Seeding one row each
 * exercises both the ImageColumn and the per-row action config at render.
 */
it('renders every resource list with the image column + upload action', function () {
    $admin = User::factory()->admin()->create();

    $brand = CarBrand::factory()->create(['image' => 'entity-images/brands/audi.png']);
    CarModel::factory()->create(['brand_id' => $brand->id, 'image' => 'entity-images/models/audi-q5.png']);
    FuelType::factory()->create(['image' => 'entity-images/fuel-types/petrol.png']);
    Service::factory()->create(['image' => 'entity-images/services/oil.png']);
    ServiceCategory::factory()->create(['image' => 'entity-images/categories/battery.png']);

    foreach ([
        CarBrandResource::class,
        CarModelResource::class,
        FuelTypeResource::class,
        ServiceResource::class,
        ServiceCategoryResource::class,
    ] as $resource) {
        $this->actingAs($admin)
            ->get($resource::getUrl('index'))
            ->assertSuccessful();
    }
});
