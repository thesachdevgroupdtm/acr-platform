<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Sub-services seeded with the EXACT slugs from the legacy frontend
     * DB_SUB_SERVICES static data — these slugs are SEO-critical.
     */
    public function run(): void
    {
        $catBySlug = ServiceCategory::pluck('id', 'slug');

        $rows = [
            // Car Battery
            ['cat' => 'car-battery', 'slug' => 'battery-charging',     'name' => 'Battery Charging',     'base_price' => 1500.00, 'time_takes' => '24', 'time_unit' => 'Hour', 'warrenty_info' => 'Car Does Not Starts',     'recommended_info' => 'Electrical System Does Not Work'],
            ['cat' => 'car-battery', 'slug' => 'battery-replacement',  'name' => 'Battery Replacement',  'base_price' => 4500.00, 'time_takes' => '4',  'time_unit' => 'Hour', 'warrenty_info' => 'Car Does Not Starts',     'recommended_info' => 'Electrical System Does Not Work'],

            // Car Emergency
            ['cat' => 'car-emergency-services', 'slug' => 'flat-bed-towing',           'name' => 'Flat Bed Towing',           'base_price' => 800.00, 'time_takes' => '3', 'time_unit' => 'Hour', 'warrenty_info' => 'Door-Step Service Available', 'recommended_info' => 'Upto 10 Km'],
            ['cat' => 'car-emergency-services', 'slug' => 'wheel-lift-towing-10-kms',  'name' => 'Wheel lift towing ( 10 Kms )', 'base_price' => null, 'time_takes' => '3', 'time_unit' => 'Hour', 'warrenty_info' => 'Doorstep Service Available', 'recommended_info' => 'For Upto 10 Kms'],
            ['cat' => 'car-emergency-services', 'slug' => 'battery-jump-start',        'name' => 'Battery jump start',        'base_price' => 500.00, 'time_takes' => '4', 'time_unit' => 'Hour', 'warrenty_info' => 'Doorstep Service Available', 'recommended_info' => 'After Jump-start keep vehicle ON at least 3 hours'],

            // Car AC
            ['cat' => 'car-ac-service-repair', 'slug' => 'full-ac-service',     'name' => 'Full AC service',     'base_price' => null, 'time_takes' => '8', 'time_unit' => 'Hour', 'warrenty_info' => 'Warranty 1000 kms or 1 month', 'recommended_info' => 'After every 10,000 kms or 1 year'],
            ['cat' => 'car-ac-service-repair', 'slug' => 'periodic-ac-service', 'name' => 'Periodic AC Service', 'base_price' => null, 'time_takes' => '4', 'time_unit' => 'Hour', 'warrenty_info' => 'Warranty 500 kms or 1 month',  'recommended_info' => 'After every 5,000 kms or 3 Months'],

            // Regular Car Service
            ['cat' => 'regular-car-service', 'slug' => 'comprehensive-service', 'name' => 'Comprehensive Service', 'base_price' => null, 'time_takes' => '8', 'time_unit' => 'Hour', 'warrenty_info' => 'Warranty 1000 kms or 1 month', 'recommended_info' => 'After every 20,000 kms or 12 Months'],
            ['cat' => 'regular-car-service', 'slug' => 'standard-service',      'name' => 'Standard Service',      'base_price' => null, 'time_takes' => '6', 'time_unit' => 'Hour', 'warrenty_info' => 'Warranty 1000 kms or 1 month', 'recommended_info' => 'After every 10,000 kms or 3 Months'],
            ['cat' => 'regular-car-service', 'slug' => 'primary-service',       'name' => 'Primary Service',       'base_price' => null, 'time_takes' => '3', 'time_unit' => 'Hour', 'warrenty_info' => 'Warranty 1000 kms or 1 month', 'recommended_info' => 'After every 5,000 kms or 3 Months'],

            // Car Brake & Wheel
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'front-brake-disc-replacement', 'name' => 'Front Brake Disc Replacement'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'front-brake-pad-replacement',  'name' => 'Front Brake Pad Replacement'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'rear-brake-shoes-replacement', 'name' => 'Rear Brake Shoes Replacement'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'disc-turning',                 'name' => 'Disc Turning'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'brake-drums-turning',          'name' => 'Brake Drums Turning'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'tyre-rotation',                'name' => 'Tyre Rotation'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'wheel-alignment',              'name' => 'Wheel Alignment'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'wheel-balancing',              'name' => 'Wheel Balancing'],
            ['cat' => 'car-brake-wheel-maintenance', 'slug' => 'complete-wheel-care',          'name' => 'Complete Wheel Care'],

            // Car Denting & Painting
            ['cat' => 'car-denting-painting', 'slug' => 'front-bumper-paint', 'name' => 'Front Bumper Paint', 'time_takes' => '2', 'time_unit' => 'Day'],
            ['cat' => 'car-denting-painting', 'slug' => 'rear-bumper-paint',  'name' => 'Rear Bumper Paint',  'time_takes' => '2', 'time_unit' => 'Day'],
            ['cat' => 'car-denting-painting', 'slug' => 'bonnet-paint',       'name' => 'Bonnet Paint',       'time_takes' => '2', 'time_unit' => 'Day'],
            ['cat' => 'car-denting-painting', 'slug' => 'full-body-paint',    'name' => 'Full Body Paint',    'time_takes' => '7', 'time_unit' => 'Day'],

            // Car Care & Detailing
            ['cat' => 'car-care-detailing', 'slug' => 'car-wash',                    'name' => 'Car Wash',                  'time_takes' => '3', 'time_unit' => 'Hour'],
            ['cat' => 'car-care-detailing', 'slug' => 'interior-dry-cleaning',       'name' => 'Interior Dry Cleaning',     'time_takes' => '4', 'time_unit' => 'Hour'],
            ['cat' => 'car-care-detailing', 'slug' => 'exterior-rubbing-polishing',  'name' => 'Exterior Rubbing & Polishing', 'time_takes' => '4', 'time_unit' => 'Hour'],
            ['cat' => 'car-care-detailing', 'slug' => 'complete-car-detailing',      'name' => 'Complete Car Detailing',    'time_takes' => '5', 'time_unit' => 'Hour'],
            ['cat' => 'car-care-detailing', 'slug' => 'teflon-coating',              'name' => 'Teflon Coating',            'time_takes' => '24','time_unit' => 'Hour'],
            ['cat' => 'car-care-detailing', 'slug' => 'ceramic-coating',             'name' => 'Ceramic Coating',           'time_takes' => '6', 'time_unit' => 'Hour'],

            // Car Repairs & Inspection
            ['cat' => 'car-repairs-inspection', 'slug' => 'alternator-new',           'name' => 'Alternator New'],
            ['cat' => 'car-repairs-inspection', 'slug' => 'cooling-coil-replacement', 'name' => 'Cooling Coil Replacement'],
            ['cat' => 'car-repairs-inspection', 'slug' => 'car-inspection',           'name' => 'Car Inspection'],

            // Car Lights & Glass
            ['cat' => 'car-lights-and-glass-work', 'slug' => 'front-headlight-replacement',  'name' => 'Front Headlight Replacement'],
            ['cat' => 'car-lights-and-glass-work', 'slug' => 'front-windshield-replacement', 'name' => 'Front Windshield Replacement'],

            // Car Clutch
            ['cat' => 'car-clutch-work', 'slug' => 'clutch-assembly',  'name' => 'Clutch Assembly'],
            ['cat' => 'car-clutch-work', 'slug' => 'clutch-overhaul',  'name' => 'Clutch Overhaul'],

            // Car Suspension
            ['cat' => 'car-suspension-work', 'slug' => 'front-shock-absorber-replacement', 'name' => 'Front Shock Absorber Replacement'],
            ['cat' => 'car-suspension-work', 'slug' => 'suspension-overhaul',              'name' => 'Suspension Overhaul'],

            // Insurance Claim
            ['cat' => 'car-insurance-claim', 'slug' => 'windshield-replacement-claim', 'name' => 'Windshield Replacement Claim'],
            ['cat' => 'car-insurance-claim', 'slug' => 'accidental-claim',             'name' => 'Accidental Claim'],
        ];

        foreach ($rows as $row) {
            $categoryId = $catBySlug[$row['cat']] ?? null;
            if (!$categoryId) {
                continue;
            }
            Service::updateOrCreate(
                ['category_id' => $categoryId, 'slug' => $row['slug']],
                [
                    'name'             => $row['name'],
                    'base_price'       => $row['base_price']        ?? null,
                    'time_takes'       => $row['time_takes']        ?? null,
                    'time_unit'        => $row['time_unit']         ?? null,
                    'warrenty_info'    => $row['warrenty_info']     ?? null,
                    'recommended_info' => $row['recommended_info']  ?? null,
                    'is_active'        => true,
                ]
            );
        }
    }
}
