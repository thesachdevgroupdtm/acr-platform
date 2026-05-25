<?php

/**
 * Phase 4.5d — POST /api/v1/seo/validate contract tests.
 *
 * 9 cases: 5 happy paths (one per schema type) + 4 fault paths
 * (missing @context, wrong @context, missing required field,
 * invalid JSON syntax).
 *
 * Endpoint always responds with HTTP 200 — the per-payload
 * `valid` boolean is the contract, not the HTTP status. That
 * lets the Filament admin modal render errors / warnings inline
 * without a try/catch.
 */

it('validates a well-formed Service JSON-LD', function () {
    $jsonld = json_encode([
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => 'Car Battery Replacement',
        'description' => 'Replace your car battery with a fresh, warranty-backed unit.',
        'provider'    => ['@type' => 'Organization', 'name' => 'ACR Mechanics'],
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => true]);
    expect($response->json('errors'))->toBe([]);
});

it('validates a well-formed LocalBusiness JSON-LD', function () {
    $jsonld = json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'LocalBusiness',
        'name'      => 'ACR Moti Nagar',
        'telephone' => '+91-9870400861',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => '63, Rama Rd, Block B, Najafgarh Rd Industrial Area',
            'addressLocality' => 'New Delhi',
            'postalCode'      => '110015',
        ],
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => true]);
});

it('validates a well-formed AutoRepair JSON-LD (extends LocalBusiness rules)', function () {
    $jsonld = json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'AutoRepair',
        'name'      => 'ACR Mechanics',
        'telephone' => '+91-9870400861',
        'url'       => 'https://acr-mechanics.in',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Moti Nagar',
            'addressLocality' => 'Delhi',
        ],
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => true]);
});

it('validates a well-formed FAQPage JSON-LD', function () {
    $jsonld = json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => 'Do you offer pickup?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes — across Delhi NCR.'],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Are prices transparent?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Itemised estimates before any work begins.'],
            ],
        ],
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => true]);
});

it('validates a well-formed Organization JSON-LD', function () {
    $jsonld = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => 'ACR Mechanics',
        'url'      => 'https://acr-mechanics.in',
        'logo'     => 'https://acr-mechanics.in/logo.png',
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => true]);
});

it('rejects JSON-LD with a wrong @context', function () {
    $jsonld = json_encode([
        '@context' => 'https://example.com/schema',
        '@type'    => 'Service',
        'name'     => 'x',
        'description' => 'x',
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => false]);
    $errors = $response->json('errors');
    expect(implode(' ', $errors))->toContain('Unexpected @context');
});

it('rejects JSON-LD missing @context', function () {
    $jsonld = json_encode([
        '@type' => 'Service',
        'name'  => 'x',
        'description' => 'x',
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => false]);
    expect($response->json('errors'))->toContain('Missing @context.');
});

it('reports specific missing-field errors per schema type', function () {
    // LocalBusiness without telephone or address.
    $jsonld = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'LocalBusiness',
        'name'     => 'No Address Co.',
    ]);

    $response = $this->postJson('/api/v1/seo/validate', ['jsonld' => $jsonld]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => false]);
    $errorsConcat = implode(' ', $response->json('errors'));
    expect($errorsConcat)->toContain('address');
    expect($errorsConcat)->toContain('telephone');
});

it('reports parse error for invalid JSON syntax', function () {
    $response = $this->postJson('/api/v1/seo/validate', [
        'jsonld' => '{ "@context": "https://schema.org", "@type": "Service", ',  // truncated
    ]);

    $response->assertSuccessful();
    $response->assertJson(['valid' => false]);
    expect(implode(' ', $response->json('errors')))->toContain('Invalid JSON');
});
