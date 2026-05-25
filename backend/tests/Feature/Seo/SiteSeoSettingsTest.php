<?php

use App\Models\Service;
use App\Models\SiteSeoSettings;
use Database\Seeders\SiteSeoSettingsSeeder;

/**
 * Phase 4.5a — site SEO settings + fallback chain tests.
 *
 * Confirms the singleton accessor and that getSeoData() on a
 * resource WITHOUT a SEO record falls back to the seeded site
 * defaults (rendering the {{page_title}} template).
 */

it('SiteSeoSettings::current() returns the seeded single row', function () {
    // Pest's RefreshDatabase wipes the DB per test, so seed
    // explicitly inside the test rather than relying on the
    // dev-environment seed.
    $this->seed(SiteSeoSettingsSeeder::class);

    $settings = SiteSeoSettings::current();

    expect($settings->id)->toBe(1);
    expect($settings->default_meta_title_template)->toContain('ACR Mechanics');
});

it('Resource without SEO falls back to site defaults via the cascade', function () {
    $this->seed(SiteSeoSettingsSeeder::class);

    $service = Service::factory()->create(['name' => 'Battery Charging']);

    // Sanity: no SEO record attached.
    expect($service->seoMetadata)->toBeNull();

    $seoData = $service->getSeoData();

    expect($seoData['meta_title'])->toBe('Battery Charging | ACR Mechanics');
    expect($seoData['robots_meta'])->toBe('index,follow');
    expect($seoData['og_type'])->toBe('website');
    expect($seoData['og_title'])->toBe('Battery Charging');
    expect($seoData['include_in_sitemap'])->toBeTrue();
});
