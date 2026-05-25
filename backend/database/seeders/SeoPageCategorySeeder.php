<?php

namespace Database\Seeders;

use App\Models\SeoPageCategory;
use Illuminate\Database\Seeder;

/**
 * Phase 4.5 — seo_page_categories defaults.
 *
 * 9 automotive-relevant categories per spec #9. Idempotent
 * (`updateOrCreate` keyed by slug). The Phase 4.5
 * `enhance_seo_pages_for_explore_editorial` migration runs this
 * seeder before backfilling `seo_pages.category_id`, so each
 * existing page can be linked to its matching category by
 * name.
 */
class SeoPageCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Names align with existing seo_pages.category strings
        // ("Brand Service" singular, etc.) so the backfill in
        // enhance_seo_pages_for_explore_editorial.up() can link
        // category_id without manual reconciliation.
        $categories = [
            ['slug' => 'brand-service',      'name' => 'Brand Service',      'icon_name' => 'tag'],
            ['slug' => 'city-service',       'name' => 'City Service',       'icon_name' => 'map-pin'],
            ['slug' => 'service-guide',      'name' => 'Service Guide',      'icon_name' => 'wrench-screwdriver'],
            ['slug' => 'maintenance-tips',   'name' => 'Maintenance Tips',   'icon_name' => 'shield-check'],
            ['slug' => 'cost-guide',         'name' => 'Cost Guide',         'icon_name' => 'currency-rupee'],
            ['slug' => 'comparison',         'name' => 'Comparison',         'icon_name' => 'arrows-right-left'],
            ['slug' => 'news',               'name' => 'News',               'icon_name' => 'newspaper'],
            ['slug' => 'denting-painting',   'name' => 'Denting & Painting', 'icon_name' => 'paint-brush'],
            ['slug' => 'luxury-cars',        'name' => 'Luxury Cars',        'icon_name' => 'sparkles'],
        ];

        foreach ($categories as $idx => $row) {
            SeoPageCategory::updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'position'    => $idx,
                    'is_active'   => true,
                ]),
            );
        }
    }
}
