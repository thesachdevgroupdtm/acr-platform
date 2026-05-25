# SELECTOR-CONVERGENCE — vehicle selector reads L1 public endpoints

The vehicle selector's brand/model/fuel reads now hit the **L1 public** endpoints
(`/api/v1/public/vehicles/*`, full `hero_image_url`s) instead of legacy
`/api/v1/vehicle/*` (relative paths that 404'd on the frontend origin). Targeted to
selector reads only — pricing, cart, booking, services data untouched. Frontend only;
backend unchanged.

**tsc clean (2 pre-existing errors only) · build clean · smoke 3/3.**

---

## 1. Audit findings (PART A)

**Legacy (before):** `src/hooks/useVehicle.ts` → `src/lib/api.ts`:
| Hook | Legacy URL | Shape |
|---|---|---|
| `useBrands()` | `GET /vehicle/brands` | `{success, brands: CarBrand[]}` |
| `useModels(brandId)` | `GET /vehicle/models?brand_id=<num>` | `{success, models: CarModel[]}` |
| `useFuels(brandId, modelId)` | `GET /vehicle/fuels?brand_id=&model_id=` | `{success, fuels: FuelType[]}` (scoped) |

`CarBrand{id,slug,title,name?,image?}` — `image` was a **relative** path
(`entity-images/brands/audi.webp`).

**Consumers of these (shared) hooks — important:** not just the selector.
- `src/components/vehicle-selector/{BrandGrid,ModelGrid,FuelGrid}.tsx` — the selector.
  BrandGrid renders `b.image` (← the broken images); ModelGrid passes `brand.id` to
  `useModels`; FuelGrid passes `brand.id, model.id`.
- `src/components/EstimateProcess.tsx` — `useBrands()` + `useModels(brandId)` (numeric);
  reads `b.title`, `b.id`, `m.title`.
- `src/pages/ServiceCategory.tsx` — `useBrands()` only; reads `b.title` for copy.

**L1 (target):** `{data:[…], meta:{count}}` envelope; brand `{id,name,slug,hero_image_url}`;
models **by slug** `/brands/{slug}/models` `{…,brand_id,segment,hero_image_url}`; fuels
**global** `{id,name,slug}`.

**Mapping bridged:** `name → title` + `name`; `hero_image_url → image` (full URL);
`segment → segment`. Models: caller passes numeric `brandId` but L1 needs the slug →
resolve from the cached brand list. Fuels: global, args kept only as the enable-gate.

**Decision:** repoint the shared hooks to L1 **while keeping their exact signatures and
`{success, brands|models|fuels}` return shape** (a shape-preserving adapter). This fixes
the selector images with **zero component edits** and keeps EstimateProcess +
ServiceCategory working unchanged — honoring "selector works identically" + "don't
refactor unrelated hooks".

## 2. L1 API client (PART B)

New `src/lib/publicVehicles.ts` (`fetchBrands` / `fetchModelsForBrand(slug)` /
`fetchFuels`) — reuses the existing **`apiGet`** (so base-URL resolution, bearer token,
errors are identical) and unwraps the `{data}` envelope. Typed `PublicBrand` /
`PublicModel` / `PublicFuel`.

> Path note: placed at `src/lib/publicVehicles.ts`, **not** `src/lib/api/publicVehicles.ts`
> as the brief suggested — creating a `src/lib/api/` directory next to the existing
> `src/lib/api.ts` file would make every `from "../lib/api"` import ambiguous. (The brief
> allowed "or add to existing api layer".)

## 3. Hooks updated (PART C)

`src/hooks/useVehicle.ts` rewritten:
- `useBrands()` → `fetchL1Brands()` → `toBrand()` → `{success, brands}`. Query key
  `["public-vehicles","brands"]`; `keepPreviousData` kept.
- `useModels(brandId)` → resolves `brandSlug` from `useBrands()` cache, then
  `fetchModelsForBrand(slug)` → `toModel()`. `enabled` once the slug resolves. **Signature
  unchanged** (still numeric `brandId`), so EstimateProcess + ModelGrid need no edits.
- `useFuels(brandId, modelId)` → global `fetchL1Fuels()` → `toFuel()`. The two args are
  kept purely as the enable-gate (fetch only after a model is picked); query key is global.
- staleTime 5 min preserved; no `keepPreviousData` on models/fuels (FORMS-1 D-5 fix kept).

## 4. Components updated (PART D)

**None.** The adapter maps L1 `hero_image_url → image`, so `BrandGrid`'s
`<img src={brand.image}>` now receives the full URL with no change; `ModelGrid` keeps
passing `brand.id` (slug resolved inside the hook); `FuelGrid` keeps passing the gate
args. No frontend URL manipulation (D-CONV-6) — the L1 URL is used verbatim.

## 5. Pricing verified unaffected (PART E)

`usePricingFor` (legacy `POST /api/v1/pricing`) is untouched. The selector's
`VehicleSelector.finish()` writes `brand_id/model_id/fuel_id` (+ slugs + names) to
`useBookingContext` from the picked objects' `id`s — which are the **same DB ids** L1
returns (L1 `BrandResource` exposes `$this->id`). So pricing reads the same numeric ids
and calls the same endpoint. `useBookingContext` shape unchanged.

## 6. Before / after — image URL source

```diff
- GET /api/v1/vehicle/brands  → "image":"entity-images/brands/audi.webp"   (relative → 404 vs localhost:3000)
+ GET /api/v1/public/vehicles/brands → "hero_image_url":"http://127.0.0.1:8000/storage/entity-images/brands/audi.webp"
                                       → adapted to image (full URL, rendered as-is)
```

## 7. Network confirmation

Hooks now call (via `apiGet`, base = `VITE_API_BASE_URL` = `http://127.0.0.1:8000/api/v1`,
host-rewritten to the page host in dev):
- `…/api/v1/public/vehicles/brands`
- `…/api/v1/public/vehicles/brands/{slug}/models`
- `…/api/v1/public/vehicles/fuels`

No remaining selector calls to `/api/v1/vehicle/*`. (The legacy `fetchBrands/fetchModels/
fetchFuels` in `src/lib/api.ts` are now unused by the selector but left in place — still
used by nothing else; harmless. `usePricingFor` keeps using legacy `/pricing`.)

## 8. tsc / build / smoke

- `npx tsc --noEmit` → only the 2 pre-existing `brand-typography.spec.ts` errors.
- `npx vite build` → clean (exit 0).
- `npx playwright test --project=smoke` → **3/3 passed** (live dev server).

## 9. Deviations

- **Shared hooks repointed (not just selector-private ones).** `useBrands/useModels/
  useFuels` are also used by EstimateProcess + ServiceCategory; the shape-preserving
  adapter keeps both working unchanged, so the convergence is effectively selector-scoped
  in behavior while fixing images everywhere those hooks feed.
- **Fuels are now GLOBAL, not vehicle-scoped.** L1 has no per-brand/model fuel endpoint,
  so the fuel step lists all fuel types (Petrol/Diesel/CNG/Electric…) regardless of the
  chosen model (D-CONV-4 accepts this — fuel types are universal). If a chosen
  model+fuel combo has no configured price, pricing already falls back to
  "quote on inspection". This is the only behavior change.
- **No component edits / no `hero_image_url` rename in components** — achieved via the
  adapter (`hero_image_url → image`) instead, which is lower-risk and keeps all three
  consumers working. (PART D's suggested field rename was conditional/"if needed".)
- **`publicVehicles.ts` path** — `src/lib/` not `src/lib/api/` (see §2).
- **Live curl / browser network check** left to the operator (needs the running Laravel
  backend); the L1 contract + full-URL output are already covered by backend tests, and
  the call paths are confirmed by code.

Operator: hard-refresh — selector brand images should load on both the homepage hero and
the service-page sidebar (both mount the same `VehicleSelector`); confirm in the Network
tab the calls go to `/api/v1/public/vehicles/*`.
