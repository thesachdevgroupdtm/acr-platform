<?php

use App\Models\SeoPage;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCenter;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5b — sitemap.xml contract tests.
 * Phase 4.5c sitemap-fix — route moved from /api/v1/sitemap.xml to
 * /sitemap.xml (root), plus the sitemap now includes ServiceCenter
 * rows alongside the existing seo_pages / categories / services.
 */

beforeEach(function () {
    Cache::forget('sitemap_xml');
});

it('GET /sitemap.xml returns well-formed XML', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    $xml = $response->getContent();
    expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($xml)->toContain('<urlset');
    expect($xml)->toContain('</urlset>');

    // Always-on static landing routes — base URL is whatever the
    // test env's APP_URL resolves to (localhost or localhost:8000),
    // so anchor on the path suffix.
    expect($xml)->toMatch('#<loc>https?://[^<]+/</loc>#');
    expect($xml)->toContain('/services</loc>');
    expect($xml)->toContain('/explore</loc>');
});

it('Sitemap respects include_in_sitemap=false on a SeoPage', function () {
    Cache::forget('sitemap_xml');

    $shown = SeoPage::create([
        'slug'  => 'shown-in-sitemap', 'title' => 'Shown',
        'body'  => '<p>x</p>',
        'is_published' => true, 'published_at' => now(),
    ]);
    $shown->setSeoData(['include_in_sitemap' => true]);

    $hidden = SeoPage::create([
        'slug'  => 'hidden-from-sitemap', 'title' => 'Hidden',
        'body'  => '<p>x</p>',
        'is_published' => true, 'published_at' => now(),
    ]);
    $hidden->setSeoData(['include_in_sitemap' => false]);

    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain('/shown-in-sitemap');
    expect($xml)->not->toContain('/hidden-from-sitemap');
});

it('Sitemap is cached and invalidated on SeoPage save', function () {
    Cache::forget('sitemap_xml');

    // Prime the cache (no pages yet).
    $this->get('/sitemap.xml');
    expect(Cache::has('sitemap_xml'))->toBeTrue();

    // Saving a new page must bust the cache via the model event.
    SeoPage::create([
        'slug'  => 'cache-bust-test',
        'title' => 'Cache Buster',
        'body'  => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    expect(Cache::has('sitemap_xml'))->toBeFalse();
});

// Phase 4.5c sitemap-fix — additional coverage for the 4 resource
// types and the route-move regression risk.

it('Sitemap includes active ServiceCenter URLs at /service-centers/{slug}', function () {
    Cache::forget('sitemap_xml');

    $slug = 'sitemap-test-center-' . uniqid();
    ServiceCenter::create([
        'slug'       => $slug,
        'name'       => 'Sitemap Test Center',
        'address'    => '1 Test Rd',
        'phone'      => '9999999999',
        'city'       => 'Delhi',
        'state'      => 'Delhi NCR',
        'pincode'    => '110001',
        'is_active'  => true,
        'sort_order' => 99,
    ]);

    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain("/service-centers/{$slug}</loc>");
});

it('Sitemap excludes inactive ServiceCenter rows', function () {
    Cache::forget('sitemap_xml');

    $slug = 'inactive-center-' . uniqid();
    ServiceCenter::create([
        'slug'       => $slug,
        'name'       => 'Inactive Center',
        'address'    => '1 Test Rd',
        'phone'      => '9999999999',
        'city'       => 'Delhi',
        'state'      => 'Delhi NCR',
        'pincode'    => '110001',
        'is_active'  => false,
        'sort_order' => 99,
    ]);

    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->not->toContain($slug);
});

it('Sitemap includes active ServiceCategory URLs at /category/{slug}', function () {
    Cache::forget('sitemap_xml');

    $slug = 'sitemap-cat-' . uniqid();
    ServiceCategory::create([
        'name'        => 'Sitemap Test Cat',
        'slug'        => $slug,
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain("/category/{$slug}</loc>");
});

it('Sitemap includes active Service URLs at /services/{cat}/{svc}', function () {
    Cache::forget('sitemap_xml');

    $catSlug = 'sitemap-cat-' . uniqid();
    $cat = ServiceCategory::create([
        'name'        => 'Sitemap Cat',
        'slug'        => $catSlug,
        'description' => 'desc',
        'position'    => 99,
        'is_active'   => true,
    ]);

    $svcSlug = 'sitemap-svc-' . uniqid();
    Service::create([
        'name'        => 'Sitemap Test Service',
        'slug'        => $svcSlug,
        'category_id' => $cat->id,
        'base_price'  => 100,
        'is_active'   => true,
    ]);

    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain("/services/{$catSlug}/{$svcSlug}</loc>");
});

it('Sitemap entries with lastmod use ISO 8601 W3C datetime format', function () {
    Cache::forget('sitemap_xml');

    SeoPage::create([
        'slug'         => 'iso-format-page',
        'title'        => 'ISO',
        'body'         => '<p>x</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    // ISO 8601 W3C: YYYY-MM-DDThh:mm:ss followed by Z or ±hh:mm.
    expect($xml)->toMatch('#<lastmod>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)</lastmod>#');
});

it('Sitemap is parseable as well-formed XML by the standard parser', function () {
    Cache::forget('sitemap_xml');
    $xml = $this->get('/sitemap.xml')->getContent();

    // Throws on malformed XML — pass means structure is valid.
    $doc = new \DOMDocument();
    $loaded = $doc->loadXML($xml);
    expect($loaded)->toBeTrue();

    // urlset root with at least the 5 static URLs even on an empty DB.
    $urls = $doc->getElementsByTagName('url');
    expect($urls->length)->toBeGreaterThanOrEqual(5);
});

it('GET /api/v1/sitemap.xml is 404 after the route move', function () {
    // Regression guard: the old URL must no longer serve content.
    $this->get('/api/v1/sitemap.xml')->assertNotFound();
});
