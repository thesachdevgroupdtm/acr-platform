<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class ServicePriceSeeder extends Seeder
{
    /**
     * Seeds a price grid for every service that has a non-null base_price,
     * across every brand × model × fuel combination. Pricing is multiplied
     * by a brand tier (luxury brands get a premium).
     *
     * This is sample data — production should overwrite via the admin
     * CSV import (Phase 4.1) with real per-vehicle pricing.
     */
    public function run(): void
    {
        $services = Service::whereNotNull('base_price')->get();
        if ($services->isEmpty()) {
            return;
        }

        $brands = CarBrand::with('models')->get();
        $fuels  = FuelType::all();

        $tier = [
            'maruti-suzuki' => 1.00,
            'hyundai'       => 1.05,
            'honda'         => 1.10,
            'toyota'        => 1.10,
            'tata'          => 1.00,
            'mahindra'      => 1.05,
            'kia'           => 1.10,
            'skoda'         => 1.20,
            'volkswagen'    => 1.20,
            'bmw'           => 1.60,
            'mercedes-benz' => 1.65,
            'audi'          => 1.55,
        ];

        $fuelAdj = [
            'petrol'   => 1.00,
            'diesel'   => 1.05,
            'cng'      => 0.95,
            'electric' => 1.10,
        ];

        // Bulk insert in chunks for speed.
        $rows = [];
        $now = now();

        foreach ($services as $service) {
            foreach ($brands as $brand) {
                $brandFactor = $tier[$brand->slug] ?? 1.00;
                foreach ($brand->models as $model) {
                    foreach ($fuels as $fuel) {
                        $fuelFactor = $fuelAdj[$fuel->slug] ?? 1.00;
                        $price = round(((float) $service->base_price) * $brandFactor * $fuelFactor, 2);
                        $rows[] = [
                            'service_id'   => $service->id,
                            'brand_id'     => $brand->id,
                            'model_id'     => $model->id,
                            'fuel_type_id' => $fuel->id,
                            'price'        => $price,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                }
            }
        }

        // Truncate & re-insert (idempotent re-run).
        ServicePrice::truncate();
        foreach (array_chunk($rows, 1000) as $chunk) {
            ServicePrice::insert($chunk);
        }
    }
}
