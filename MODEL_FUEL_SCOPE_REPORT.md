# MODEL_FUEL_SCOPE — Filter fuel options by selected model

The Fuel step (step 3 of the vehicle selector) used to show **all** fuels for
every model. It now shows **only the fuels that have a valid pricing combination
for the chosen model** — derived from `service_prices`, with a safe fallback so
the booking flow never dead-ends.

**Backend 285 tests pass (280 prior + 5 new) · tsc clean (2 pre-existing only) ·
vite build clean · smoke 3/3.** Additive only — the global `/vehicles/fuels`
endpoint, pricing logic, booking-context shape, and brand/model selection are
untouched.

---

## PART A — Audit (run against the live `acr_v3` MySQL DB, before coding)

| Question | Finding |
|---|---|
| Linking table | `service_prices(service_id, brand_id, model_id, fuel_type_id, price)` — **no `is_active` column**. A fuel is valid for a model when ≥1 row exists with that `model_id` + `fuel_type_id`. |
| Fuel catalog | 3 active: **Petrol (5), Diesel (6), CNG (7)** |
| **Audi A3 (#317)** | pricing fuels = **[Petrol, Diesel]**, **no CNG** ✓ (task hypothesis confirmed) |
| Does filtering matter? | Yes, a lot: of 314 models — 119 Petrol+Diesel · 52 Diesel-only · 50 Petrol-only · 52 all-three · 41 Petrol+CNG. Showing all 3 was **wrong for 262/314 models**. |
| Models with **no** pricing rows (would hit fallback) | **0 / 314** — the D-FUEL-4 fallback is a pure safety net, never triggered on current data. |
| `service_prices` (DISTINCT) vs `car_model_fuel_types` pivot | **0 disagreements across all 314 models** — perfectly consistent. |

**Why `service_prices` and not the pivot:** the global `FuelController::index`
already supports `?model_id=N`, but it narrows via the `car_model_fuel_types`
pivot (seeded from the car_list import). Per D-FUEL-2 the new endpoint derives
fuels from **pricing data** instead — a fuel only belongs in the booking flow if
we can actually *quote/book* it. Since the two sources agree on every model, this
is also pivot-consistent; pricing is the semantically-correct authority for a
booking step. The global endpoint and its pivot path are left exactly as-is.

---

## PART B — Backend (additive)

**Route** (`backend/routes/api.php`, inside the existing `public.vehicles.*` group):
```php
Route::get('models/{slug}/fuels', [PublicFuelController::class, 'forModel'])
    ->name('public.vehicles.models.fuels');
```

**Controller** — new `FuelController::forModel(string $slug)`:
- Resolves the model by **slug** (`firstOrFail()` → **404** on unknown slug, mirroring `brands/{slug}/models`).
- Selects publicly-visible fuels (same `is_active` + sitemap rule as `index()`, via a shared `visibleFuels()` helper) that have a `whereExists` pricing row in `service_prices` for `model_id`, ordered by name.
- **Fallback (D-FUEL-4):** if that set is empty, returns the full active-fuel catalog and sets `meta.fallback = true`.
- Returns the **same `{data, meta}` envelope and `FuelResource`** (full `hero_image_url`) as the global list, so the frontend adapter is reused unchanged. `meta` adds `model_id`, `model_slug`, `fallback`.

Live-DB verification (via `app()->handle(Request::create(...))`):
```
GET /api/v1/public/vehicles/models/a3/fuels    → 200  [Diesel, Petrol]      meta.count=2, fallback=false   (no CNG ✓)
GET /api/v1/public/vehicles/models/aveo/fuels   → 200  [CNG, Diesel, Petrol] meta.count=3, fallback=false
GET /api/v1/public/vehicles/models/no-such/fuels→ 404
```
`hero_image_url` came back fully-qualified (`http://localhost:8000/storage/entity-images/fuel-types/<slug>.webp`).

---

## PART C — Frontend (selector read-path only)

| File | Change |
|---|---|
| `src/lib/publicVehicles.ts` | + `fetchFuelsForModel(modelSlug, signal)` → `GET /public/vehicles/models/{slug}/fuels` (slug `encodeURIComponent`-ed), unwraps `{data}`. Reuses `apiGet` (same base URL / token / error handling). |
| `src/hooks/useVehicle.ts` | `useFuels(brandId, modelId)` → **`useFuels(modelSlug)`**. Calls `fetchFuelsForModel`; `queryKey: ["public-vehicles","fuels", modelSlug]` (per-model cache, no brand-bleed); `enabled` only once a model slug is present. Same `FuelsResponse` `{success, fuels}` shape via the existing `toFuel` adapter — no shape change for consumers. |
| `src/components/vehicle-selector/FuelGrid.tsx` | Props `{brandId, modelId}` → **`{modelSlug}`**; calls `useFuels(modelSlug)`. Rendering/density untouched (`hero_image_url` image vs lucide-icon fallback preserved). |
| `src/components/vehicle-selector/VehicleSelector.tsx` | Caller now passes `<FuelGrid modelSlug={model.slug} … />` (`model.slug` is a non-optional `string` on `CarModel`, already used for `model_slug` in the booking write). |

`useFuels` is consumed **only** by `FuelGrid`, which is consumed **only** by
`VehicleSelector` — so the signature change is fully contained. Booking-context
write, pricing (`usePricingFor` → `POST /api/v1/pricing` on numeric ids), brand
and model steps are unchanged.

---

## PART D — Tests (`backend/tests/Feature/Api/V1/ModelFuelScopeTest.php`, 5 new)

1. **Scoped** — model priced for Petrol+Diesel only → returns exactly those two, not CNG; `meta.count=2`, `fallback=false`, correct `model_id`/`model_slug`.
2. **Fallback (D-FUEL-4)** — model with zero pricing rows → returns all active fuels, excludes an inactive one, `meta.fallback=true`.
3. **Shape** — asserts `{data:[{id,name,slug,hero_image_url}], meta:{count,model_id,model_slug,fallback}}` and a full `/storage/...` `hero_image_url`.
4. **Visibility** — a *priced but inactive* fuel is excluded (visibility rule wins over pricing); `fallback=false`.
5. **404** — unknown model slug returns 404.

---

## PART E — Verification

| Check | Result |
|---|---|
| `php vendor/bin/pest` (full) | **285 passed** (1152 assertions) — 280 prior + 5 new, **0 regressions** |
| `npx tsc --noEmit` | only the **2 pre-existing** `brand-typography.spec.ts` SVG-cast errors |
| `npx vite build` | **clean** (exit 0) |
| `npx playwright test --project=smoke` | **3/3 passed** |
| Live endpoint (a3 / aveo / unknown) | 200 [2 fuels] / 200 [3 fuels] / 404 ✓ |

---

## Decisions honoured

- **D-FUEL-1** New L1 endpoint `GET /api/v1/public/vehicles/models/{slug}/fuels`, same `{data, meta}` shape as global fuels.
- **D-FUEL-2** Valid fuels = DISTINCT fuels with a `service_prices` row for the model.
- **D-FUEL-3** `useFuels(modelSlug)` hits the new endpoint; no fetch until a model is picked; booking context already carries `model_slug`.
- **D-FUEL-4** No pricing rows → fall back to all active fuels; flagged via `meta.fallback` (documented: 0/314 models hit it today).
- **D-FUEL-5** No git actions taken — operator manages git manually.

## Constraints honoured

Global `/vehicles/fuels` endpoint kept (+ its `?model_id=` pivot path); pricing
logic, booking-context shape, and brand/model selection untouched; no packages
installed; additive migration-free change (no schema edits); tsc still only the 2
pre-existing errors; smoke 3/3.
