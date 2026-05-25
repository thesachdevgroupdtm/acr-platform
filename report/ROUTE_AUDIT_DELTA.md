# Route surface delta — reconciliation against AUDIT_REPORT.md §4

Read-only audit. No code changes.

## a) Total route count today vs original audit

```
$ cd backend && php artisan route:list --path=api --json | jq length
16
```

Original audit (`AUDIT_REPORT.md §4`) listed: **16 routes**.
Today, on this branch (`HEAD = 5049181`): **16 routes**.

**Delta: zero. The route surface has not changed.** Every URI/method
pair matches the original audit table verbatim:

```
GET     api/v1/home                                       HomeController@index
GET     api/v1/services                                   ServiceController@index
GET     api/v1/services/{slug}                            ServiceController@show
GET     api/v1/services/{categorySlug}/{serviceSlug}      ServiceController@detail
GET     api/v1/vehicle/brands                             VehicleController@brands
GET     api/v1/vehicle/models                             VehicleController@models
GET     api/v1/vehicle/fuels                              VehicleController@fuels
GET     api/v1/search/brands                              VehicleController@brands
GET     api/v1/search/models                              VehicleController@models
GET     api/v1/search/fuels                               VehicleController@fuels
POST    api/v1/pricing                                    PricingController@quote
GET     api/v1/pages/{slug}                               PageController@show
POST    api/v1/import/car-brands                          ImportController@carBrands
POST    api/v1/import/car-models                          ImportController@carModels
POST    api/v1/import/fuel-types                          ImportController@fuelTypes
POST    api/v1/import/service-prices                      ImportController@servicePrices
```

## b) Routes added/missed since original audit

**None.** Verified two ways:

1. **Git log on `backend/routes/api.php`:**
   ```
   $ git log --oneline -- backend/routes/api.php
   ad6bc8d chore: baseline + add API layer to existing Front controllers
   ```
   The file has been touched by exactly **one** commit, the original
   baseline that pre-dates the audit. Phase 1.1 through Phase 1.6
   commits did not modify the route file.

2. **Route registration mechanism:** `RouteServiceProvider::boot()` at
   `backend/app/Providers/RouteServiceProvider.php:31–38` registers
   exactly two route files — `routes/api.php` (under `api` middleware
   + `api` prefix) and `routes/web.php`. `routes/web.php` contains a
   single `Route::get('/', view('welcome'))`. There are no package
   service providers contributing API routes (`find vendor -path
   "*/routes" -type d` returns no results within the application's
   namespace surface). No custom macros, no dynamic registration,
   no model-binding magic.

## c) `/primary-service` — explained

**There is no `/api/v1/primary-service` route.** The 16-route listing
above is exhaustive and `php artisan route:list --path=api` confirms
no match.

`primary-service` is a **service slug**, not a route name. Source:

```
backend/database/seeders/ServiceSeeder.php:36

['cat' => 'regular-car-service', 'slug' => 'primary-service',
 'name' => 'Primary Service', 'base_price' => null,
 'time_takes' => '3', 'time_unit' => 'Hour',
 'warrenty_info' => 'Warranty 1000 kms or 1 month',
 'recommended_info' => 'After every 5,000 kms or 3 Months']
```

It is one of three sub-services seeded under the `regular-car-service`
category (the other two are `comprehensive-service` and
`standard-service`). Project-wide grep of `src/` for the literal
`primary-service` returns **zero matches** — nothing in the frontend
uses it as a route or hardcodes that path.

### What the user almost certainly observed

The succeeding browser request was `GET /api/v1/services/regular-car-service/primary-service?brand_id=...&model_id=...&fuel_id=...`,
which is **route #4 in the original audit**: `services/{categorySlug}/{serviceSlug}` → `ServiceController@detail`. Reading the URL with
the leading `services/regular-car-service/` portion truncated in
the network-tab view explains the misreport as `/primary-service`.

**Controller method, in full** (`backend/app/Http/Controllers/Api/V1/ServiceController.php:166–229`):

```php
public function detail(Request $request, string $categorySlug, string $serviceSlug): JsonResponse
{
    $validated = $request->validate([
        'brand_id'  => ['nullable', 'integer', 'exists:car_brands,id'],
        'model_id'  => ['nullable', 'integer', 'exists:car_models,id'],
        'fuel_id'   => ['nullable', 'integer', 'exists:fuel_types,id'],
    ]);

    $category = ServiceCategory::where('slug', $categorySlug)
        ->where('is_active', true)->first();
    if (!$category) return response()->json([...], 404);

    $service = Service::query()
        ->where('category_id', $category->id)
        ->where('slug', $serviceSlug)
        ->where('is_active', true)
        ->first();
    if (!$service) return response()->json([...], 404);

    $vehiclePrice = null; $brand = $model = $fuel = null; $priceShow = 0;

    if (!empty($validated['brand_id']) && !empty($validated['model_id'])
        && !empty($validated['fuel_id'])) {
        $brand = CarBrand::find($validated['brand_id']);
        $model = CarModel::find($validated['model_id']);
        $fuel  = FuelType::find($validated['fuel_id']);

        $price = ServicePrice::query()
            ->where('service_id',   $service->id)
            ->where('brand_id',     $brand->id)
            ->where('model_id',     $model->id)
            ->where('fuel_type_id', $fuel->id)
            ->first();
        if ($price) { $vehiclePrice = (float) $price->price; $priceShow = 1; }
    }

    $serviceResource = new ServiceResource($service);
    if ($vehiclePrice !== null) {
        $serviceResource->withVehiclePrice(['price' => $vehiclePrice]);
    }

    $related = Service::query()
        ->where('category_id', $category->id)
        ->where('id', '!=', $service->id)
        ->where('is_active', true)
        ->limit(6)->get();

    return response()->json([
        'success'           => true,
        'service'           => $serviceResource,
        'category'          => new ServiceCategoryResource($category),
        'related'           => ServiceResource::collection($related),
        'price_show'        => $priceShow,
        'vehicle_price'     => $vehiclePrice,
        'vehicle_package_id'=> null,
        'brand'             => $brand ? new CarBrandResource($brand) : null,
        'model'             => $model ? new CarModelResource($model) : null,
        'fuel'              => $fuel  ? new FuelTypeResource($fuel)   : null,
        'seo'               => [...],
    ]);
}
```

**Behaviour:** validates optional vehicle-id query params; resolves
the category by slug → resolves the service by `(category_id, slug)`;
when full vehicle context is supplied, joins `service_prices` for the
4-tuple and returns a `vehicle_price` + `price_show=1`; returns six
related services from the same category. Models touched:
`ServiceCategory`, `Service`, `CarBrand`, `CarModel`, `FuelType`,
`ServicePrice`.

**Frontend consumers:**
| Caller | File:line |
|---|---|
| `fetchServiceDetail(...)` definition | `src/lib/api.ts:436–446` |
| `useServiceDetail(...)` hook (React Query wrapper) | `src/hooks/useServices.ts:50–56` |
| `<ServiceDetail>` page (single-service detail screen) | `src/pages/ServiceDetail.tsx:26, 59` |

`ServiceDetail.tsx` is the only consumer. It fires this endpoint on
mount with the `categorySlug` and `serviceSlug` props from the
pseudo-router, and the vehicle-id triple from `useBookingContext`
when a vehicle has been picked. That matches the user's description
of the request firing with `?brand_id=…&model_id=…&fuel_id=…` query
string.

## d) Frontend reconciliation update — gaps

Re-running the §9.1 check from `AUDIT_REPORT.md` against today's
state. Every frontend call site below is grep'd from `src/` for
`apiGet`/`apiPost`/`apiPut`/`apiDelete` and the typed fetchers.

| Frontend call | Matching backend route | Status |
|---|---|---|
| `fetchHome()` → `GET /home` | route #1 | OK |
| `fetchServices(q)` → `GET /services` | route #2 | OK |
| `fetchCategoryDetail(slug, q)` → `GET /services/{slug}` | route #3 | OK |
| `fetchServiceDetail(cat, svc, q)` → `GET /services/{cat}/{svc}` | route #4 | OK |
| `fetchBrands()` → `GET /vehicle/brands` | route #5 | OK |
| `fetchModels(brandId)` → `GET /vehicle/models` | route #6 | OK |
| `fetchFuels(brandId, modelId)` → `GET /vehicle/fuels` | route #7 | OK |
| `postPricing(req)` → `POST /pricing` | route #11 | OK |
| `fetchPage(slug)` → `GET /pages/{slug}` | route #12 | OK |
| `useAuth.ts:210` → `GET /user/profile` | — | **GAP** (still no route; gated by `FEATURES.auth=false`) |
| `useAuth.ts:251` → `POST /auth/register` | — | **GAP** (gated) |
| `useAuth.ts:282` → `POST /auth/login` | — | **GAP** (gated) |
| `useAuth.ts:306` → `POST /auth/logout` | — | **GAP** (gated) |
| `useAuth.ts:329` → `PUT /auth/profile` | — | **GAP** (gated) |
| `useAuth.ts:369` → `POST /user/addresses` | — | **GAP** (gated) |
| `useAuth.ts:405` → `POST /checkout/offline` | — | **GAP** (gated by `FEATURES.offlineCheckout=false`) |
| `useCart.ts:88` → `POST /cart/sync` | — | **GAP** (gated by `FEATURES.cartSync=false`) |

**No new gaps** since the original audit. The 8 unmatched calls are
the same ones flagged in `AUDIT_REPORT.md §9.1`, all currently
short-circuited by Phase 1.3's feature flags so they never hit the
network. No call site was added or rerouted in Phase 1.1–1.6 commits
that would create a new gap.

## e) Verdict

**The original audit was complete; the route surface did NOT change.**
The user's report of a `/api/v1/primary-service` request is a
misreading of the network-tab URL — the actual request was
`GET /api/v1/services/regular-car-service/primary-service?brand_id=...`,
hitting the long-documented `services/{categorySlug}/{serviceSlug}`
route (#4 in the original §4 table). `primary-service` is a seeded
service slug under the `regular-car-service` category — not a route
name.

Three independent confirmations:
1. `php artisan route:list --path=api` → 16 routes, none containing `primary-service`.
2. `git log -- backend/routes/api.php` → exactly one commit, the baseline `ad6bc8d` (pre-audit).
3. `RouteServiceProvider` registers only the two stock route files; no package or dynamic registration mechanism exists in this codebase.

No code changes recommended. The diagnosis tooling for future smoke
tests should capture the **full URL path** from the Network tab to
prevent slug-vs-route confusion (the per-slug detail endpoint embeds
slugs that read like routes).
