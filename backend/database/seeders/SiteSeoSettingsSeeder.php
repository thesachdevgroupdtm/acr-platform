<?php

namespace Database\Seeders;

use App\Models\SiteSeoSettings;
use Illuminate\Database\Seeder;

/**
 * Phase 4.5a — single-row site SEO defaults.
 *
 * Idempotent (`updateOrCreate({ id: 1 })`) so re-running the
 * seeder is safe. Operators can edit these values via Filament
 * once Phase 4.5d adds the SiteSettingsResource; until then
 * the row exists so the HasSeoMetadata fallback chain has
 * non-null defaults to draw from.
 */
class SiteSeoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSeoSettings::updateOrCreate(
            ['id' => 1],
            [
                'default_meta_title_template' =>
                    '{{page_title}} | ACR Mechanics',
                'default_meta_description' =>
                    'Authorized car service centers in Delhi NCR. '
                    . 'Premium repair, maintenance, and detailing for all car brands.',
                'default_og_image'         => 'https://acr-mechanics.in/og-image.jpg',
                'default_twitter_handle'   => '@acrmechanics',
                'default_twitter_card'     => 'summary_large_image',
                'default_robots_meta'      => 'index,follow',
                'organization_jsonld' => [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'AutoRepair',
                    'name'        => 'ACR Mechanics',
                    'description' => 'Authorized multi-brand car service centers in Delhi NCR',
                    'url'         => 'https://acr-mechanics.in',
                    'logo'        => 'https://acr-mechanics.in/logo.png',
                    'telephone'   => '+91-9560321371',
                ],
                'google_site_verification'     => null,
                'facebook_domain_verification' => null,
            ]
        );
    }
}
