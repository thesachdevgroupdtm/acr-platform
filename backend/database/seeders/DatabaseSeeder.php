<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServiceCategorySeeder::class,
            ServiceSeeder::class,
            VehicleSeeder::class,
            ServicePriceSeeder::class,
            PageSeeder::class,
            AdminUserSeeder::class,
            SiteSeoSettingsSeeder::class,
            SeoPageCategorySeeder::class,
            SeoPageSeeder::class,
            SeoPageMockSeeder::class,
        ]);
    }
}
