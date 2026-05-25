# Sitemap-Fix Report — Phase 4.5c follow-up

> Closes the only blocker from Phase 4.5c manual verification:
> `/sitemap.xml` returned 404. Also adds the ServiceCenter rows
> that Phase 4.5c §16 noted as deferred.

---

## 1. PART A — Diagnosis

`php artisan route:list --path=sitemap` (before fix):

```
GET|HEAD  api/v1/sitemap.xml ........... Api\V1\Public\SitemapController@index
```

- Route was registered in `routes/api.php:150` under the `/api/v1`
  prefix → resolved URL `/api/v1/sitemap.xml`.
- Root path `/sitemap.xml` had no binding → 404.
- `backend/bootstrap/cache/routes-v7.php` did not exist, so route
  cache was **not** the issue (no `php artisan route:clear`
  required).

Existing `SitemapController` already covered seo_pages, service
categories, services, and 5 static URLs. **Missing: ServiceCenter
rows** — the 4 seeded centers (Moti Nagar / Gurugram / Noida /
Okhla) plus any new ones added through the Phase 4.5c admin form.

Probes:

```
GET /sitemap.xml          → 404  (before fix)
GET /api/v1/sitemap.xml   → 200 application/xml  (before fix)
```

Full diagnosis recorded in `SITEMAP_DIAGNOSIS.md`.

---

## 2. PART B — Route fix

This was **SCENARIO 1** in the task brief: route in `api.php`,
move to `web.php` at the root.

### `routes/web.php`

```php
use App\Http\Controllers\Api\V1\Public\SitemapController;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('sitemap');
```

### `routes/api.php`

Removed the duplicate registration (was at line 150) and left a
breadcrumb comment so future maintainers know where the binding
lives now. Controller class location untouched — only the route
file moved.

### Verification

```
php artisan route:list --path=sitemap

GET|HEAD  sitemap.xml ........... sitemap › Api\V1\Public\SitemapController@index
```

```
GET /sitemap.xml          → 200 application/xml  ✓
GET /api/v1/sitemap.xml   → 404 (intentional)    ✓
```

---

## 3. PART C — Controller content expansion

Single new section appended after the Services block:

```php
ServiceCenter::query()
    ->where('is_active', true)
    ->with('seoMetadata')
    ->chunk(100, function ($centers) use (&$urls, $base) {
        foreach ($centers as $center) {
            $seo = $center->seoMetadata;
            if ($seo && ! $seo->include_in_sitemap) {
                continue;
            }
            $urls[] = $this->urlEntry(
                $base . '/service-centers/' . $center->slug,
                (string) ($seo?->priority ?? 0.7),
                $seo?->changefreq ?? 'monthly',
                $center->updated_at?->toAtomString()
            );
        }
    });
```

Bonus: added `$updated_at?->toAtomString()` to the ServiceCategory
and Service URL entries too — they previously emitted no `lastmod`.
Now every dynamic URL carries an ISO 8601 W3C timestamp.

### URL count — before vs after

```
=== Before fix ===
Total URLs: 73
  static       :  5
  seo_pages    : 17
  categories   : 12
  services     : 39
  centers      :  0   ← missing

=== After fix ===
Total URLs: 77
  static       :  5
  seo_pages    : 17
  categories   : 12
  services     : 39
  centers      :  4   ← Moti Nagar, Gurugram, Noida, Okhla
```

Cache key `sitemap_xml` was busted (`php artisan cache:clear`)
before re-probing.

---

## 4. PART D — Tests

```
PASS  Tests\Feature\Api\V1\SitemapTest
  ✓ GET /sitemap.xml returns well-formed XML                                        1.31s
  ✓ Sitemap respects include_in_sitemap=false on a SeoPage                          0.31s
  ✓ Sitemap is cached and invalidated on SeoPage save                               0.24s
  ✓ Sitemap includes active ServiceCenter URLs at /service-centers/{slug}           0.22s
  ✓ Sitemap excludes inactive ServiceCenter rows                                    0.19s
  ✓ Sitemap includes active ServiceCategory URLs at /category/{slug}                0.20s
  ✓ Sitemap includes active Service URLs at /services/{cat}/{svc}                   0.31s
  ✓ Sitemap entries with lastmod use ISO 8601 W3C datetime format                   0.20s
  ✓ Sitemap is parseable as well-formed XML by the standard parser                  0.22s
  ✓ GET /api/v1/sitemap.xml is 404 after the route move                             0.20s

  Tests:    10 passed (22 assertions)
```

- 3 existing tests had their URL path updated `/api/v1/sitemap.xml`
  → `/sitemap.xml`. Behaviour assertions unchanged.
- 7 new tests added — coverage for the 4 resource types, the
  inactive-row exclusion rule, the ISO 8601 lastmod format, the
  full DOMDocument parseability, and a regression guard locking
  in the old URL's 404.

### Full backend Pest suite

```
Tests:    137 passed (622 assertions)
Duration: 37.06s
```

Phase 4.5c baseline was 130. Delta: **+7** sitemap tests. Zero
regressions on the 130 pre-existing tests.

---

## 5. PART E — Production-ready verification

### 5.1 Response headers

```
HTTP/1.1 200 OK
Content-Type: application/xml; charset=utf-8
Cache-Control: max-age=3600, public
```

### 5.2 URL count

```
curl -s http://127.0.0.1:8000/sitemap.xml | grep -c "<url>"
77
```

By section: 5 static + 17 seo_pages + 12 categories + 39 services
+ 4 centers = 77.

### 5.3 lastmod format

```xml
<lastmod>2026-05-09T11:46:11+00:00</lastmod>
<lastmod>2026-05-09T06:08:45+00:00</lastmod>
<lastmod>2026-05-09T06:08:45+00:00</lastmod>
```

All conform to W3C ISO 8601 datetime
(`YYYY-MM-DDThh:mm:ss±hh:mm`). Validated by regex assertion in
the test suite.

### 5.4 XML validity

Parsed end-to-end via Python's `ElementTree` and PHP's
`DOMDocument::loadXML()` — both return success, 77 `<url>` nodes
under one `<urlset>` root. No malformed entities, no unescaped
characters.

### 5.5 Spot-check 3 URLs

```
/                                     → 200  (root)
/api/v1/seo-pages/audi-service-delhi  → 200  (sample seo page)
/api/v1/service-centers/moti-nagar    → 200  (sample center)
```

### 5.6 robots.txt

`backend/public/robots.txt` exists with `Disallow:` allow-all
content but does **not** reference the sitemap. Out of scope for
this fix per task brief — flagged for Phase 4.5d cleanup along
with the legacy `src/lib/SeoHead.tsx` removal.

Production deployment can add a one-line `Sitemap:` directive
when the canonical APP_URL is known:

```
User-agent: *
Disallow:
Sitemap: https://acr-mechanics.in/sitemap.xml
```

---

## 6. Files touched

```
Modified:
  backend/routes/web.php
    + import + Route::get('/sitemap.xml', …)
  backend/routes/api.php
    - removed Route::get('sitemap.xml', …) under /api/v1
    + breadcrumb comment pointing to web.php
  backend/app/Http/Controllers/Api/V1/Public/SitemapController.php
    + use App\Models\ServiceCenter
    + ServiceCenter::query()->where('is_active', true)->chunk(...) block
    + lastmod on Service + ServiceCategory entries
    + class-level docblock updated for Phase 4.5c sitemap-fix
  backend/tests/Feature/Api/V1/SitemapTest.php
    + 7 new tests
    + 3 existing tests retargeted from /api/v1/sitemap.xml → /sitemap.xml

Added:
  SITEMAP_DIAGNOSIS.md  (PART A audit record)
  SITEMAP_FIX_REPORT.md  (this report)
```

No schema migrations. No frontend changes. No package installs.
No controller class moves (only the route binding moved files).

---

## 7. Hard-constraint honesty pass

| Constraint                                | Status |
|-------------------------------------------|--------|
| Don't touch CmsPage/SeoPageView/PageBanner | ✅ |
| Don't modify Phase 4.5c trait / resources  | ✅ |
| Don't touch /explore editorial work        | ✅ |
| Don't change /api/v1/seo-pages or others   | ✅ |
| No new packages                            | ✅ |
| No migrations                              | ✅ |
| All 130 prior tests pass                   | ✅ |
| Existing seo_pages sitemap behavior preserved | ✅ (3 original tests still pass verbatim, just with the new path) |
| No frontend route breaks                   | ✅ |

---

## 8. Phase 4.5d preview — unchanged from Phase 4.5c report

The post-Phase-4.5c roadmap items remain:

1. **Delete legacy `src/lib/SeoHead.tsx`** — confirmed zero
   importers; ready for a tiny one-PR cleanup.
2. **Schema preview UI** — Filament action that renders resolved
   JSON-LD inline on each SEO-bearing Edit page.
3. **FAQ data source wiring** — bind `schema_type=FAQPage` to an
   FAQ resource for canonical FAQPage JSON-LD output.
4. **JSON-LD validator endpoint** — `POST /api/v1/seo/validate`
   returning Google Rich Results-style hints.

Sitemap now contains the 4 resource types Phase 4.5c §16 listed
as deferred. That item moves from "deferred" → "done" without
opening up the rest of 4.5d's scope.

— Sitemap-fix complete · backend 137 / 137 · `/sitemap.xml` 200 application/xml · 77 URLs
