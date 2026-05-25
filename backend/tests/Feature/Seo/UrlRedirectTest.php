<?php

use App\Models\UrlRedirect;

/**
 * Phase 4.5a — URL redirect lookup tests.
 *
 * Phase 4.5a only ships the table + lookup helper; the
 * frontend catch-all middleware lands in 4.5b. Tests here
 * pin the model/table contract so 4.5b can build on top
 * without surprises.
 */

it('URL redirect can be created and found by from_path', function () {
    UrlRedirect::create([
        'from_path'   => '/old-audi-page',
        'to_path'     => '/audi-service-delhi',
        'status_code' => 301,
    ]);

    $redirect = UrlRedirect::findActiveFor('/old-audi-page');

    expect($redirect)->not->toBeNull();
    expect($redirect->to_path)->toBe('/audi-service-delhi');
    expect($redirect->status_code)->toBe(301);
    expect($redirect->is_active)->toBeTrue();
});

it('Inactive redirects are not returned by findActiveFor', function () {
    UrlRedirect::create([
        'from_path' => '/disabled-redirect',
        'to_path'   => '/somewhere',
        'is_active' => false,
    ]);

    expect(UrlRedirect::findActiveFor('/disabled-redirect'))->toBeNull();
});
