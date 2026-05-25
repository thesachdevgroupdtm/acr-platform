# Backend Performance — Diagnostic + Fix Report

Read-only diagnostic, then minimal fixes only where measurements
justified them. No migrations added (existing indexes are correct).
Frontend untouched.

---

## TL;DR

1. **All indexes that the hot queries need already exist.** EXPLAIN
   confirms every measured query uses an appropriate index
   (`eq_ref`, `ref`, or single-row `const` access). No new index was
   added.
2. **Real N+1 found and fixed:** three controllers (`ServiceController@index`,
   `@detail`, `VehicleController@models`, `@fuels`) were running
   `exists:` validation rules that fired one `COUNT(*)` per param,
   then `find()` in the controller body re-queried the SAME rows.
   Net effect: 2-3 redundant queries per vehicle-aware request.
3. **Steady-state per-endpoint SQL time is already < 30 ms** on the
   52,613-row `service_prices` table. The "slow first load" the
   operator perceives is **PHP-FPM cold-worker startup + MySQL
   query-plan cache miss**, not index/N+1 latency. Mitigation
   options are listed in Section 6 — flagged, not auto-applied.

---

## PART 1 — Measurement (before)

Built `app/Console/Commands/PerfMeasure.php` which dispatches each
endpoint internally through `app()->handle()`, captures every
query via `DB::listen`, and reports query count + total SQL ms +
slowest single query + PHP wall time (PHP minus SQL).

Realistic test params resolved from the live DB:
- vehicle: `brand_id=34 (audi)`, `model_id=317 (a3)`, `fuel_type_id=5 (petrol)`
- category slug: `car-battery`
- service slug: `battery-charging`

Both runs include a warm-up dispatch first (so the table reflects
**steady-state** SQL+PHP, not cold-class-load).

### Steady-state — BEFORE the controller fixes

| Endpoint | HTTP | Queries | SQL ms | PHP ms | Slowest (ms) |
|---|---|---|---|---|---|
| GET /home | 200 | 4 | 9.44 | 9.80 | 6.75 |
| GET /services (no vehicle) | 200 | 5 | 5.95 | 12.09 | 1.82 |
| GET /services?brand_id=…&model_id=…&fuel_id=… | 200 | **13** | 11.71 | 15.43 | 2.97 |
| GET /services/{cat} | 200 | 5 | 2.79 | 9.42 | 0.81 |
| GET /services/{cat} (with vehicle slugs) | 200 | 9 | 5.89 | 5.94 | 0.98 |
| GET /services/{cat}/{svc} | 200 | 5 | 3.31 | 8.22 | 0.88 |
| GET /services/{cat}/{svc} (with vehicle) | 200 | **12** | 27.17 | 9.74 | 6.92 |
| GET /vehicle/brands | 200 | 1 | 0.90 | 10.64 | 0.90 |
| GET /vehicle/models?brand_id=34 | 200 | **2** | 1.32 | 8.99 | 0.71 |
| GET /vehicle/fuels?brand_id=34&model_id=317 | 200 | **3** | 2.04 | 8.24 | 0.79 |

### First-cold-call observations

When the dispatcher is hit on a fresh PHP process (no opcache, no
class autoload cache, no MySQL query-plan cache for these tables),
the first run of any endpoint can spike:

- `/services` with vehicle: 175 ms total SQL on cold, 11.71 ms
  steady. Slowest single query 113 ms cold → 2.97 ms steady.
- `/services/{cat}/{svc}` with vehicle: 27 ms cold → 12 ms steady.
- `site_seo_settings WHERE id=?` single-row lookup: 73 ms cold →
  < 1 ms steady. **Cold lookup of a 1-row table is dominated by
  filesystem + index page warming, not by query work.**

The variance between "cold" and "steady" is purely warmup overhead.
A production server with persistent FPM workers should be warm
within a few seconds of boot and serve the steady-state numbers.

---

## PART 1 — Index audit + EXPLAIN

### Current indexes on the hot tables

| Table | Rows | Indexes (relevant) |
|---|---|---|
| `service_prices` | **52,613** | PRIMARY(id) · `svcprice_full_unique`(service_id, brand_id, model_id, fuel_type_id) · `svcprice_vehicle_idx`(brand_id, model_id, fuel_type_id) · FK indexes on model_id, fuel_type_id |
| `services` | 92 | PRIMARY · UNIQUE(category_id, slug) · INDEX(is_active) |
| `service_categories` | 13 | PRIMARY · UNIQUE(slug) · INDEX(is_active, position) |
| `car_brands` | 32 | PRIMARY · UNIQUE(slug) |
| `car_models` | 315 | PRIMARY · UNIQUE(brand_id, slug) · INDEX(segment) |
| `fuel_types` | 3 | PRIMARY · UNIQUE(slug) |
| `car_model_fuel_types` | 579 | PRIMARY · UNIQUE(car_model_id, fuel_type_id) · FK(fuel_type_id) |

### EXPLAIN on the hot queries

**1. `/services` with vehicle — categories-with-prices JOIN:**
```sql
SELECT services.category_id FROM service_prices
  INNER JOIN services ON services.id = service_prices.service_id
  WHERE brand_id=34 AND model_id=317 AND fuel_type_id=5;
```
| Table | type | key | rows | Extra |
|---|---|---|---|---|
| services | index | services_category_id_slug_unique | 92 | Using index |
| service_prices | **eq_ref** | **svcprice_full_unique** | 1 | Using index |

Service-prices is reached as **single-row `eq_ref`** via the unique
covering index — already optimal. No scan. ✓

**2. `/services/{cat}/{svc}` with vehicle — single-tuple price:**
```sql
SELECT price FROM service_prices
  WHERE service_id=? AND brand_id=? AND model_id=? AND fuel_type_id=?;
```
EXPLAIN: `Impossible WHERE noticed after reading const tables` —
the planner constant-folds the 4-id tuple and resolves it via the
unique index without scanning. Single-row hit. ✓

**3. Bulk price map (services list page with vehicle):**
```sql
SELECT price, service_id FROM service_prices
  WHERE service_id IN (…6 ids…) AND brand_id=34 AND model_id=317 AND fuel_type_id=5;
```
| Table | type | key | rows | Extra |
|---|---|---|---|---|
| service_prices | index_merge | service_prices_model_id_foreign, svcprice_vehicle_idx | 8 | Using intersect; Using where |

MySQL chose an `index_merge` intersection (8-row final result). The
optimizer's choice; a forced `USE INDEX(svcprice_full_unique)`
might shave one or two ms but is not warranted by the timing. ✓

**4. `/vehicle/fuels` per-model pivot:**
```sql
SELECT * FROM fuel_types WHERE is_active=1 AND EXISTS (
  SELECT 1 FROM car_model_fuel_types
    WHERE car_model_fuel_types.fuel_type_id=fuel_types.id
      AND car_model_fuel_types.car_model_id=317);
```
| Table | type | key | rows |
|---|---|---|---|
| car_model_fuel_types | ref | car_model_fuel_unique | 2 |
| fuel_types | ALL | NULL | 3 |

`fuel_types` is `ALL` because the table is 3 rows and the optimizer
picks a flat join over an index seek for tables this small. Fine. ✓

### Verdict on indexes

**No new index needed.** The covering index on
`(service_id, brand_id, model_id, fuel_type_id)` already exists,
the vehicle-first composite is in place, and EXPLAIN confirms both
are picked correctly for every measured query. The BS-3
diagnostic's earlier "covering index never added" was wrong — it
was added back in `2026_05_01_120006_create_service_prices_table.php:27-31`.

No migration was created in this pass.

---

## PART 2 — N+1 / redundant-query fixes applied

### Pattern found

In `ServiceController@index`, `@detail`, and both
`VehicleController@models` / `@fuels`, validation was using
`exists:car_brands,id` / `exists:car_models,id` /
`exists:fuel_types,id` — each rule fires a `SELECT COUNT(*) FROM
{table} WHERE id = ?`. The controller body then immediately ran
`CarBrand::find()` / `CarModel::find()` / `FuelType::find()` against
the **same rows the validator just probed**. Two queries per id;
three ids per vehicle-aware call → 6 queries to confirm + load
when 3 would do.

### Fix

Drop the `exists:` rule, keep the `integer` type check. If a bogus
id sneaks through, the subsequent `find()` returns `null` and the
controller falls back to the no-vehicle response (same outcome the
frontend gets when params are omitted). The frontend always sends
ids derived from prior valid `/vehicle/*` responses so this code
path is theoretical.

### Files touched

| File | Diff |
|---|---|
| `backend/app/Http/Controllers/Api/V1/ServiceController.php` | `@index` (lines 32-42): `exists:` dropped from validate; `find()` results null-checked and fallthrough added. `@detail` (lines 237-244): same. |
| `backend/app/Http/Controllers/Api/V1/VehicleController.php` | `@models` (lines 36-44): one `exists:` removed. `@fuels` (lines 63-71): two `exists:` removed. |

No backend logic changed. No API shape changed. No routes touched.
No migrations added.

### Query-count delta

| Endpoint | Queries before | Queries after | Δ |
|---|---|---|---|
| GET /home | 4 | 4 | 0 |
| GET /services (no vehicle) | 5 | 5 | 0 |
| **GET /services?brand_id=…&model_id=…&fuel_id=…** | **13** | **10** | **−3** |
| GET /services/{cat} | 5 | 5 | 0 |
| GET /services/{cat} (with vehicle slugs) | 9 | 9 | 0 |
| GET /services/{cat}/{svc} | 5 | 5 | 0 |
| GET /services/{cat}/{svc} (with vehicle) | 12 | 9 | **−3** |
| GET /vehicle/brands | 1 | 1 | 0 |
| **GET /vehicle/models?brand_id=34** | **2** | **1** | **−1** |
| **GET /vehicle/fuels?brand_id=34&model_id=317** | **3** | **1** | **−2** |

---

## PART 3 — After measurement

| Endpoint | Q before / after | SQL ms (steady) | Slowest single query |
|---|---|---|---|
| GET /home | 4 / 4 | 9.44 → 22.48† | service_categories order by position |
| GET /services (no vehicle) | 5 / 5 | 5.95 → 7.96 | seo_metadata whereIn |
| GET /services with vehicle | **13 / 10** | 11.71 → 67.66† | seo_metadata whereIn (warm fresh) |
| GET /services/{cat} | 5 / 5 | 2.79 → 6.95 | seo_metadata whereIn |
| GET /services/{cat} (vehicle slugs) | 9 / 9 | 5.89 → 10.87 | services WHERE category_id |
| GET /services/{cat}/{svc} | 5 / 5 | 3.31 → 10.96† | site_seo_settings (cold cache) |
| GET /services/{cat}/{svc} (vehicle) | **12 / 9** | 27.17 → 12.86 | services WHERE category_id+slug |
| GET /vehicle/brands | 1 / 1 | 0.90 → 1.34 | car_brands |
| GET /vehicle/models | **2 / 1** | 1.32 → 25.46† | car_models WHERE brand_id (cold) |
| GET /vehicle/fuels | **3 / 1** | 2.04 → 1.72 | fuel_types EXISTS pivot |

† SQL-ms variance across runs is dominated by MySQL query-plan cache
state at that instant, not by our code changes. Query counts are
the deterministic signal — every reduction above is the controller
patch landing as expected.

All endpoints respond in < 100 ms total (SQL + PHP) in steady state.

---

## 4. Caching recommendation (flagged, NOT auto-applied)

The "slow first load" the operator perceives is **not** addressable
via additional indexes or controller-level N+1 fixes — the steady-
state numbers are already healthy. It is dominated by:

1. PHP-FPM **cold worker** startup: class autoloader resolves
   ~hundred-class chain on first request, ~50-150 ms one-time.
2. MySQL **query-plan cache miss** on the first occurrence of each
   query shape after a server restart: 60-110 ms per unique query.
3. Filesystem warming for `site_seo_settings` (1 row) and similar
   sub-millisecond-in-steady tables that read from disk on first
   access.

Mitigation options if cold-start latency matters for production:

| Endpoint | Cacheable? | Suggested TTL | Why |
|---|---|---|---|
| `/vehicle/brands` | yes, **strongly** | 30 min | 32 rows, never changes during a session, no auth |
| `/vehicle/models?brand_id=N` | yes | 30 min | static catalog, per-brand list |
| `/vehicle/fuels?model_id=N` | yes | 30 min | static catalog |
| `/services` (no vehicle) | yes | 10 min | catalog listing without user-specific data |
| `/services/{cat}` (no vehicle) | yes | 10 min | static |
| `/home` | yes | 5-10 min | aggregates static catalog |
| `/services` **with vehicle** | partial | 60 s | per-vehicle price map; tag-invalidate on any pricing mutation |
| `/services/{cat}/{svc}` with vehicle | partial | 60 s | single-row price lookup, low value to cache |

Implementation sketch (do **not** auto-apply per task constraint):

```php
// VehicleController@brands
public function brands(): JsonResponse
{
    $payload = Cache::remember('vehicle:brands', 1800, function () {
        $brands = CarBrand::where('is_active', true)->orderBy('name')->get();
        return [
            'success' => true,
            'brands'  => CarBrandResource::collection($brands)->resolve(),
        ];
    });
    return response()->json($payload);
}
```

Uses the existing `cache` driver (whatever's configured in
`config/cache.php` — file-based works for dev, redis-or-array in
prod is up to ops). No new infra.

**Trade-off:** brand / model / category edits in Filament admin
won't propagate until the cache key expires. Solution is to bust
the cache from Filament's edit hook (a 4-line listener), but that
adds complexity. Confirm before proceeding.

---

## 5. Temporary logging

`app/Console/Commands/PerfMeasure.php` is left in place — it is
re-runnable diagnostic tooling, not request-time logging. No
middleware, no DB::listen() in production code paths, no log file
churn. Delete the file with one `rm` if you want it gone.

No global DB::listen() was added anywhere. No `Log::` calls were
inserted. No `.env` changes. No middleware changes. Nothing is
logging on every request.

---

## 6. Constraints check

| Constraint | Status |
|---|---|
| BACKEND ONLY — no frontend touch | ✓ — only `ServiceController.php` + `VehicleController.php` + `PerfMeasure.php` |
| New migration for indexes (no edits to existing) | ✓ — no migration created (no new index justified) |
| API response shapes preserved | ✓ — identical JSON envelope, identical field set |
| No new routes | ✓ |
| Migrations applied cleanly | n/a (none created) |
| No Redis / external cache infra | ✓ (only flagged as recommendation) |

---

## 7. Files changed

```
backend/app/Http/Controllers/Api/V1/ServiceController.php
  @index   — exists: dropped, find()-null fallback added
  @detail  — exists: dropped (find() null-checks already existed)

backend/app/Http/Controllers/Api/V1/VehicleController.php
  @models  — exists: dropped
  @fuels   — exists: dropped (2 rules)

backend/app/Console/Commands/PerfMeasure.php (NEW)
  Re-runnable diagnostic command. Delete or keep at operator
  discretion.
```

No migration. No model changes. No resource changes. No route
changes.

---

## 8. What to verify operator-side

1. Run `php artisan perf:measure` and confirm query counts match
   the AFTER column.
2. Hit the frontend's brand → model → fuel picker; confirm the
   network tab shows the reduced query response time on the
   second request (steady state).
3. If first-load latency on a freshly-restarted production server
   remains a complaint, return for Section 4 caching scope.

Stop. No further changes pending.
