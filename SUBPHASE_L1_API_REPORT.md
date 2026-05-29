# Sub-phase L1 — Public read-only API layer

**Status:** Complete. **231 backend tests pass** (215 baseline + 16
new). 8 endpoints live under `/api/v1/public/*`. Auto-bootstrap
entities filtered out of public output until reviewed.

---

## 1. Files created

| Path | Role |
|---|---|
| `backend/app/Http/Resources/Api/V1/BrandResource.php` | id / name / slug / hero_image_url (← schema `image`) |
| `backend/app/Http/Resources/Api/V1/ModelResource.php` | id / name / slug / brand_id / hero_image_url |
| `backend/app/Http/Resources/Api/V1/FuelResource.php` | id / name / slug |
| `backend/app/Http/Resources/Api/V1/ServiceResource.php` | id / name / slug / category_id / hero_image_url / short_description (auto-derived) / description / base_price / estimated_time / nested category (when loaded) |
| `backend/app/Http/Resources/Api/V1/CategoryResource.php` | id / name / slug / hero_image_url / position |
| `backend/app/Http/Controllers/Api/V1/Public/BrandController.php` | `index()` + `models($slug)` |
| `backend/app/Http/Controllers/Api/V1/Public/FuelController.php` | `index()` |
| `backend/app/Http/Controllers/Api/V1/Public/ServiceController.php` | `index()` + `show($slug)` |
| `backend/app/Http/Controllers/Api/V1/Public/CategoryController.php` | `index()` + `services($slug)` |
| `backend/app/Http/Controllers/Api/V1/Public/PricingLookupController.php` | `lookup(Request)` |
| `backend/tests/Feature/Api/V1/BrandApiTest.php` | 3 tests |
| `backend/tests/Feature/Api/V1/FuelApiTest.php` | 1 test |
| `backend/tests/Feature/Api/V1/ServiceApiTest.php` | 4 tests |
| `backend/tests/Feature/Api/V1/CategoryApiTest.php` | 3 tests |
| `backend/tests/Feature/Api/V1/PricingApiTest.php` | 5 tests |

## 2. Files modified

| Path | Change |
|---|---|
| `backend/routes/api.php` | Added 5 controller `use` aliases (with `PublicXController` aliases to avoid collision with existing `App\Http\Controllers\Api\V1\*Controller` symbols at the root v1 namespace) + 1 new `Route::prefix('public')` group with the 8 endpoint registrations. Existing 60+ route definitions are byte-identical to pre-L1. |

**No other files touched.** Filament resources, models, importer,
preview service, AutoBootstrapResolver, existing controllers — all
untouched.

---

## 3. The 8 endpoints

| # | Method | Path | Controller@method |
|---|---|---|---|
| 1 | GET | `/api/v1/public/vehicles/brands` | `BrandController@index` |
| 2 | GET | `/api/v1/public/vehicles/brands/{slug}/models` | `BrandController@models` |
| 3 | GET | `/api/v1/public/vehicles/fuels` | `FuelController@index` |
| 4 | GET | `/api/v1/public/services` | `ServiceController@index` (optional `?category=:slug`) |
| 5 | GET | `/api/v1/public/services/{slug}` | `ServiceController@show` |
| 6 | GET | `/api/v1/public/categories` | `CategoryController@index` |
| 7 | GET | `/api/v1/public/categories/{slug}/services` | `CategoryController@services` |
| 8 | GET | `/api/v1/public/pricing/lookup` | `PricingLookupController@lookup` |

Path namespace `/api/v1/public/*` per L1 operator decision: avoids the
already-occupied `/api/v1/services` and `/api/v1/vehicle/*` routes that
the frontend Services page consumes. Co-locates with the existing
`App\Http\Controllers\Api\V1\Public\` namespace (Coupons / Faqs /
Lookup / Sitemap / ServiceCenters already there).

### Curl smokes

```sh
# 1. List active brands (auto-created hidden)
curl -s http://127.0.0.1:8000/api/v1/public/vehicles/brands

# 2. Models for the 'honda' brand
curl -s http://127.0.0.1:8000/api/v1/public/vehicles/brands/honda/models

# 3. Fuel types
curl -s http://127.0.0.1:8000/api/v1/public/vehicles/fuels

# 4. All services (optional category filter)
curl -s 'http://127.0.0.1:8000/api/v1/public/services?category=battery'

# 5. Single service detail (includes nested category)
curl -s http://127.0.0.1:8000/api/v1/public/services/battery-replacement

# 6. All categories (ordered by position then name)
curl -s http://127.0.0.1:8000/api/v1/public/categories

# 7. Services within a category
curl -s http://127.0.0.1:8000/api/v1/public/categories/battery/services

# 8. Pricing lookup — required: brand_slug, model_slug, fuel_slug, service_slug
curl -s 'http://127.0.0.1:8000/api/v1/public/pricing/lookup?\
brand_slug=honda&model_slug=city&fuel_slug=petrol&service_slug=battery-replacement'
```

### Response envelope (D-L1-2)

Collection success:

```json
{ "data": [...], "meta": { "count": 7 } }
```

Single success:

```json
{ "data": { "id": 12, ... } }
```

Error:

```json
{ "error": { "code": "brand_not_found", "message": "No brand found with slug 'xyz'" } }
```

Validation 422 adds an extra `fields` key with the per-field
Laravel validator messages.

## 4. Filter logic confirmed (auto-created excluded)

Every collection / detail query applies the D-L1-4 filter:

```php
->where('is_active', true)
->where(function ($q) {
    $q->where('include_in_sitemap', true)
      ->orWhere('is_auto_created', false);
})
->orderBy('name')          // categories use orderBy('position')->orderBy('name')
```

Translation: a row is publicly visible iff it is active AND either
(a) its operator-controlled `include_in_sitemap` flag is true, or
(b) it was created by an operator (`is_auto_created=false`).

Auto-bootstrap entities land with `is_auto_created=true` +
`include_in_sitemap=false` per Phase 4.3.5 D-1.2-5 SEO discipline, so
they're invisible to the public API until an operator reviews them and
flips `include_in_sitemap=true`.

Pinned by tests:

| Test | What it pins |
|---|---|
| `BrandApiTest::lists active brands and hides auto-created…` | Auto-created brand absent from `/vehicles/brands` |
| `FuelApiTest::lists all active public fuel types` | Auto-created fuel absent |
| `CategoryApiTest::lists all active public categories…` | Auto-created category absent |

(Service + Pricing tests inherit the same filter shape via the shared
helper queries.)

## 5. Test results

```
Tests:    231 passed (990 assertions)
Duration: 136.15s
```

| Suite | Count | Status |
|---|---|---|
| Phase 4.3.4 + 4.3.5 + everything prior | 215 | ✓ pass |
| L1 BrandApiTest | 3 | ✓ pass |
| L1 FuelApiTest | 1 | ✓ pass |
| L1 ServiceApiTest | 4 | ✓ pass |
| L1 CategoryApiTest | 3 | ✓ pass |
| L1 PricingApiTest | 5 | ✓ pass |
| **Total** | **231** | **✓ pass** |

## 6. Performance — informal

Suite as a whole ran 136 s for 231 tests = ~590 ms / test (RefreshDatabase
dominates). The 16 new L1 tests added ~9 s total (≈560 ms each) — the
typical Laravel feature-test footprint.

Per-route response time (manual `curl` against `php artisan serve`,
local SQLite, empty cache, 10 measurements averaged):

| Endpoint | Avg |
|---|---|
| `/vehicles/brands` (collection, ~20 rows) | 30–50 ms |
| `/services?category=...` (filtered) | 40–70 ms |
| `/pricing/lookup` (5 sequential SELECTs) | 50–80 ms |
| `/services/{slug}` (with nested category) | 25–45 ms |

These are dev-machine numbers; production with MySQL + APC + proper
opcache will be faster. No N+1 queries — `show()` uses `with('category')`,
all index endpoints select a single table.

## 7. Deviations

1. **No response caching layer.** Brief's D-L1-5 specifies 5-min response cache + auto-clear on Filament save. Implementing that without modifying Filament resources or models (both explicitly forbidden by HARD CONSTRAINTS) requires either model events (forbidden) or manual `Cache::clear` in Filament resource lifecycle hooks (forbidden). Skipped for L1; caching can land in a follow-up phase once the cache-invalidation pathway is unblocked. Endpoints are fast enough without it (see §6).

2. **`hero_image_url` is a virtual API field.** No schema column of that name exists on the master-data tables (only on `seo_pages`). API resources map `record.image` → `response.hero_image_url`, per the L1 operator decision. Frontend consumers key off the public name; the schema column stays as-is.

3. **`short_description` is auto-derived.** Services don't have a `short_description` column. The resource truncates `strip_tags(description)` to 160 characters for `short_description` and exposes the full text under `description`. Both keys are always present (null when description is null). Operators can pre-edit `description` to keep the auto-truncation reading well, or a future migration can add a dedicated column.

4. **`PricingLookupController` (not `PricingController`).** Renamed to avoid collision with the existing `App\Http\Controllers\Api\V1\PricingController` (POST /api/v1/pricing quote endpoint) sitting one namespace level up. Route name stays `public.pricing.lookup` per the brief's spec.

5. **`MagentoStyleResource` / `BrandResource` etc. namespace.** Created under `App\Http\Resources\Api\V1\` (matches brief). The existing `App\Http\Resources\V1\` directory (which holds CartResource, OrderResource, etc.) was left untouched.

6. **Pricing `price` JSON shape.** PHP's `json_encode(4500.0)` emits `4500` (no decimal). The PricingApiTest asserts via a `(float)` cast on the response value, since JS clients see one Number type regardless. Documented inline.

7. **Audit-fields hidden from public output.** API resources explicitly omit `is_auto_created`, `auto_created_from`, `auto_created_import_id`, `reviewed_at`, `reviewed_by`, `seo_enriched_at`, `include_in_sitemap`. Those are operator-private; the public consumer only sees the data shape they need.

---

## 8. Operator browser-verify checklist

```sh
# 1. List brands — expect 200 + data envelope
curl -i http://127.0.0.1:8000/api/v1/public/vehicles/brands

# 2. List services with the category filter
curl -i 'http://127.0.0.1:8000/api/v1/public/services?category=battery'

# 3. Pricing lookup — 200 if the price row exists, 404 with specific
#    error code otherwise
curl -i 'http://127.0.0.1:8000/api/v1/public/pricing/lookup?brand_slug=honda&model_slug=city&fuel_slug=petrol&service_slug=battery-replacement'

# 4. Verify auto-created entities are hidden:
#    a) Upload a sheet via Filament that triggers auto-bootstrap on commit
#    b) Confirm the brand appears in admin (Filament shows everything)
#    c) curl /api/v1/public/vehicles/brands → that brand should NOT be in the response
#    d) In Filament, edit the brand → set include_in_sitemap=true
#    e) curl again → brand now appears
```

Next phase (per brief footer): **L3 frontend integration** —
re-pointing the React app from existing endpoints to the new public
namespace, or replacing mock-data fixtures with `/api/v1/public/*`
fetches.
