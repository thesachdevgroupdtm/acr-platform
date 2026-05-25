<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    /**
     * Brand/model data extracted from frontend CAR_DATA.
     * Fuel types: petrol, diesel, cng, electric (matches FUEL_TYPES const
     * in src/pages/ServiceCategory.tsx).
     */
    public function run(): void
    {
        $carData = [
            'Maruti Suzuki' => ['Swift', 'Baleno', 'Dzire', 'Brezza', 'Ertiga', 'WagonR', 'Alto', 'Grand Vitara', 'Fronx'],
            'Hyundai'       => ['Creta', 'Venue', 'i20', 'Verna', 'Grand i10 Nios', 'Alcazar', 'Tucson', 'Exter'],
            'Honda'         => ['City', 'Amaze', 'Elevate', 'Civic', 'CR-V'],
            'Toyota'        => ['Fortuner', 'Innova Hycross', 'Innova Crysta', 'Glanza', 'Urban Cruiser Hyryder', 'Camry', 'Hilux'],
            'Tata'          => ['Nexon', 'Punch', 'Harrier', 'Safari', 'Tiago', 'Tigor', 'Altroz', 'Tiago EV'],
            'Mahindra'      => ['XUV700', 'Scorpio-N', 'Thar', 'XUV300', 'Bolero', 'Scorpio Classic', 'XUV400'],
            'Kia'           => ['Seltos', 'Sonet', 'Carens', 'Carnival', 'EV6'],
            'BMW'           => ['3 Series', '5 Series', 'X1', 'X3', 'X5', '7 Series', 'M3', 'M5'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'GLC', 'GLE', 'S-Class', 'GLA', 'GLS'],
            'Audi'          => ['A4', 'A6', 'Q3', 'Q5', 'Q7', 'A8', 'e-tron'],
            'Skoda'         => ['Slavia', 'Kushaq', 'Octavia', 'Superb', 'Kodiaq'],
            'Volkswagen'    => ['Virtus', 'Taigun', 'Tiguan', 'Polo', 'Vento'],
        ];

        foreach ($carData as $brandName => $models) {
            $brand = CarBrand::updateOrCreate(
                ['slug' => Str::slug($brandName)],
                ['name' => $brandName, 'is_active' => true]
            );
            foreach ($models as $modelName) {
                CarModel::updateOrCreate(
                    ['brand_id' => $brand->id, 'slug' => Str::slug($modelName)],
                    ['name' => $modelName, 'is_active' => true]
                );
            }
        }

        $fuels = [
            ['name' => 'Petrol',   'slug' => 'petrol'],
            ['name' => 'Diesel',   'slug' => 'diesel'],
            ['name' => 'CNG',      'slug' => 'cng'],
            ['name' => 'Electric', 'slug' => 'electric'],
        ];
        foreach ($fuels as $f) {
            FuelType::updateOrCreate(
                ['slug' => $f['slug']],
                ['name' => $f['name'], 'is_active' => true]
            );
        }
    }
}
