<?php

use App\Models\Service;
use App\Models\ServiceCenter;
use App\Services\SchemaTemplateEngine;

/**
 * Phase 4.5a — JSON-LD generator tests.
 *
 * Three core paths:
 *   1. LocalBusiness template fills name/address/geo from a
 *      ServiceCenter and merges schema_data overrides.
 *   2. Service template fills offers from base_price.
 *   3. Custom JSON-LD bypasses templates entirely.
 */

it('LocalBusiness template generates valid JSON-LD for a ServiceCenter', function () {
    $center = ServiceCenter::factory()->create([
        'name'      => 'ACR Moti Nagar',
        'address'   => 'XYZ Road',
        'city'      => 'Delhi',
        'state'     => 'Delhi',
        'pincode'   => '110015',
        'phone'     => '+91-9560321371',
        'latitude'  => 28.6,
        'longitude' => 77.2,
    ]);

    $seo = $center->seoMetadata()->create([
        'schema_type' => 'LocalBusiness',
        'schema_data' => ['priceRange' => '₹₹₹'],
    ]);

    $jsonld = app(SchemaTemplateEngine::class)->generate($seo);
    $data   = json_decode($jsonld, true);

    expect($data['@type'])->toBe('LocalBusiness');
    expect($data['name'])->toBe('ACR Moti Nagar');
    expect($data['address']['@type'])->toBe('PostalAddress');
    expect($data['address']['streetAddress'])->toBe('XYZ Road');
    expect($data['address']['addressCountry'])->toBe('IN');
    expect($data['priceRange'])->toBe('₹₹₹');
    expect($data['geo']['latitude'])->toBe(28.6);
});

it('Service template generates valid JSON-LD with offers from base_price', function () {
    $service = Service::factory()->create([
        'name'        => 'AC Service',
        'description' => 'Comprehensive AC repair',
        'base_price'  => 1500,
    ]);

    $seo = $service->seoMetadata()->create([
        'schema_type' => 'Service',
    ]);

    $jsonld = app(SchemaTemplateEngine::class)->generate($seo);
    $data   = json_decode($jsonld, true);

    expect($data['@type'])->toBe('Service');
    expect($data['name'])->toBe('AC Service');
    // JSON-decode collapses 1500.0 back to int 1500 — assert
    // numeric value, not type.
    expect((float) $data['offers']['price'])->toBe(1500.0);
    expect($data['offers']['priceCurrency'])->toBe('INR');
    expect($data['provider']['name'])->toBe('ACR Mechanics');
});

it('Custom JSON-LD overrides the template path', function () {
    $service = Service::factory()->create();

    $custom = '{"@context":"https://schema.org","@type":"CustomType","name":"Override"}';
    $seo    = $service->seoMetadata()->create([
        // schema_type would normally render the Service template,
        // but custom_jsonld must win.
        'schema_type'   => 'Service',
        'custom_jsonld' => $custom,
    ]);

    $result = app(SchemaTemplateEngine::class)->generate($seo);

    expect($result)->toBe($custom);
});
