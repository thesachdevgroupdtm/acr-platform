# Sitemap 404 — Diagnosis (PART A)

## 1. Current route registration

```
php artisan route:list --path=sitemap
GET|HEAD  api/v1/sitemap.xml ........... Api\V1\Public\SitemapController@index
```

Registered in `routes/api.php:150` under the `/api/v1` group, so
production URL is `https://acr-mechanics.in/api/v1/sitemap.xml` —
**not** the search-engine convention (`/sitemap.xml` at root). This
is the **404 root cause**.

## 2. Probes

```
curl -i http://127.0.0.1:8000/sitemap.xml      → HTTP 404
curl -i http://127.0.0.1:8000/api/v1/sitemap.xml → HTTP 200 application/xml
```

The controller works; the URL is wrong.

## 3. Route cache state

`backend/bootstrap/cache/routes-v7.php` — **does not exist**. So
route cache is not stale; `route:clear` is unnecessary. Only
`packages.php`, `services.php`, and `filament/` are present in the
cache dir.

## 4. Controller current behaviour

`backend/app/Http/Controllers/Api/V1/Public/SitemapController.php`
(140 lines) already emits valid sitemap XML covering:

- ✅ Static routes — `/`, `/services`, `/service-centers`,
  `/coupons`, `/explore`
- ✅ SeoPage rows where `is_published=true` and
  `published_at IS NOT NULL`
- ✅ ServiceCategory rows where `is_active=true`
- ✅ Service rows where `is_active=true` (URL pattern
  `/services/{cat}/{svc}`)
- ❌ **Missing**: ServiceCenter rows. The 4 seeded centers
  (Motinagar / Gurugram / Noida / Okhla) are not in the sitemap.

The controller honours `seoMetadata.include_in_sitemap` per
resource, has a 1-hour cache via the `sitemap_xml` cache key, and
both `SeoPage` and `SeoMetadata` model events bust the key on
save / delete (verified by existing test
`Sitemap is cached and invalidated on SeoPage save`).

## 5. Existing tests

`backend/tests/Feature/Api/V1/SitemapTest.php` (3 tests, all
calling `/api/v1/sitemap.xml`):

1. `GET /api/v1/sitemap.xml returns well-formed XML`
2. `Sitemap respects include_in_sitemap=false on a SeoPage`
3. `Sitemap is cached and invalidated on SeoPage save`

These will need their request URL updated from `/api/v1/sitemap.xml`
→ `/sitemap.xml` when the route moves. Behavior assertions stay
identical.

## 6. Fix plan

This is **SCENARIO 1** in the task brief: route in `api.php`,
move to `web.php` at root.

### PART B
- Add `routes/web.php`:
  `Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');`
- Remove the duplicate registration from `routes/api.php` (line 150).
- No route-cache clear needed (none exists).

### PART C
- Extend `SitemapController::generate()` to append ServiceCenter
  rows: `ServiceCenter::where('is_active', true)->with('seoMetadata')->chunk(...)`,
  URL pattern `{base}/service-centers/{slug}`.
- Keep `include_in_sitemap` honouring.
- Default priority 0.7, changefreq 'monthly' for centers (matches
  the existing tier — categories 0.8, services 0.7).

### PART D
- Update the 3 existing test URLs to `/sitemap.xml`.
- Add ~5 new tests for: response Content-Type, ServiceCenter
  inclusion, services inclusion, ServiceCategory inclusion,
  inactive records excluded, lastmod presence.

### PART E
- Production-ready Content-Type, URL count, ISO 8601 lastmod
  format, valid XML.

No schema changes, no new packages, no controller moves (the
controller stays at `app/Http/Controllers/Api/V1/Public/SitemapController.php`
— only the **route binding** moves files).
