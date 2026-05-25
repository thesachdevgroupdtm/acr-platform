<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Seed the service categories using the EXACT slugs that were
     * already in production (extracted from the legacy frontend
     * DB_SERVICE_CATEGORIES static data). Slugs are SEO-critical and
     * MUST NOT be changed without redirects.
     */
    public function run(): void
    {
        $rows = [
            [
                'slug'        => 'car-battery',
                'name'        => 'Car Battery',
                'description' => 'Auto Car Repair provides high-quality car battery replacement and maintenance services.',
                'position'    => 1,
            ],
            [
                'slug'        => 'car-emergency-services',
                'name'        => 'Car Emergency Services',
                'description' => 'Need urgent car help? We offer 24/7 emergency car services.',
                'position'    => 2,
            ],
            [
                'slug'        => 'car-insurance-claim',
                'name'        => 'Car Insurance Claim',
                'description' => 'Quick & Easy Car Insurance Claims at Auto Car Repair.',
                'position'    => 3,
            ],
            [
                'slug'        => 'car-repairs-inspection',
                'name'        => 'Car Repairs & Inspection',
                'description' => 'Searching for a car repair shop near me? Our garage offers top-notch car repair services.',
                'position'    => 4,
            ],
            [
                'slug'        => 'car-suspension-work',
                'name'        => 'Car Suspension Work',
                'description' => 'Expert Car Suspension Repair & Replacement Services.',
                'position'    => 5,
            ],
            [
                'slug'        => 'car-clutch-work',
                'name'        => 'Car Clutch Work',
                'description' => 'Looking for expert clutch repair and replacement?',
                'position'    => 6,
            ],
            [
                'slug'        => 'car-lights-and-glass-work',
                'name'        => 'Car Lights and Glass Work',
                'description' => 'Get professional car lights and glass repair or replacement services.',
                'position'    => 7,
            ],
            [
                'slug'        => 'car-care-detailing',
                'name'        => 'Car Care & Detailing',
                'description' => "Transform your car with Auto Car Repair's expert detailing services in Delhi NCR.",
                'position'    => 8,
            ],
            [
                'slug'        => 'car-denting-painting',
                'name'        => 'Car Denting & Painting',
                'description' => 'Looking for quality car denting & painting? Get expert dent repair & painting.',
                'position'    => 9,
            ],
            [
                'slug'        => 'car-brake-wheel-maintenance',
                'name'        => 'Car Brake & Wheel Maintenance',
                'description' => 'Get expert car brake and wheel maintenance at Auto Car Repair.',
                'position'    => 10,
            ],
            [
                'slug'        => 'car-ac-service-repair',
                'name'        => 'Car AC Service & Repair',
                'description' => 'Stay cool with expert car AC repair near you.',
                'position'    => 11,
            ],
            [
                'slug'        => 'regular-car-service',
                'name'        => 'Regular Car Service',
                'description' => 'Get expert regular car service at Auto Car Repair in Delhi.',
                'position'    => 12,
            ],
        ];

        foreach ($rows as $row) {
            ServiceCategory::updateOrCreate(
                ['slug' => $row['slug']],
                $row + ['is_active' => true]
            );
        }
    }
}
