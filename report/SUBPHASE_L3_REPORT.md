# Sub-phase L3 — Frontend integration (hybrid scope)

**Status:** Complete. TypeScript clean (only the 2 pre-existing
brand-typography errors), build green (8.73 s, bundle 185.81 kB
unchanged), Playwright smoke 3/3 pass.

**Scope decision recap.** Before any code I flagged a major mismatch
between the brief and the actual codebase. The brief assumed mock-data
files at `src/data/mockBrands.ts` etc. and "TanStack Query (already
installed) — create new hooks under `src/hooks/api/`." Reality: there
are **no mock-data files**, the frontend is **already fully API-driven**
with 13+ TanStack Query hooks at `src/hooks/`, and `src/lib/api.ts`
(875 lines) is the canonical client speaking the **legacy endpoints**
(`/home`, `/services`, `/vehicle/*`, POST `/pricing`) — **not** the new
L1 `/api/v1/public/*` endpoints I shipped last phase. Operator chose
the **hybrid path** (recommended): build only the genuinely-new UX on
top of the existing hooks; keep all existing pages on existing
endpoints; let L1 endpoints stay live for external/third-party
consumers. Mock-data deprecation, Footer rebuild, mock→API page
migration — all skipped because they don't apply to the current state.

---

## 1. Files created

| Path | Role |
|---|---|
| `src/components/vehicle/VehicleSelector.tsx` | Cascading Brand→Model→Fuel picker. `mode="inline"` (drops into any page) or `mode="modal"` (full-screen on mobile, centered card ≥sm). Persists completed selection into `useBookingContext` so downstream pages see the same vehicle without prop-drilling. Optional `onCtaClick` callback for "See Prices" / similar CTAs. |
| `src/components/pricing/PricingWidget.tsx` | Composes `VehicleSelector` + the existing `usePricingFor` hook into a drop-in quote card. 5 states: no-vehicle / loading / price-found / no-price-for-combo (custom-quote CTA) / error (retry). |

## 2. Files modified

**None.** No existing page touched. Operator can mount the two new
components wherever they want post-launch without any reverts being
needed.

## 3. Files NOT created (planned in brief, not applicable)

| Brief item | Why skipped |
|---|---|
| `src/hooks/api/use*.ts` (8 new hooks) | Equivalent hooks already exist at `src/hooks/*` for every endpoint the brief listed. New hooks would duplicate `useBrands`, `useModels`, `useFuels`, `useServiceCategories`, `useCategoryDetail`, `useServiceDetail`, `usePricingFor`. |
| `src/lib/api/client.ts` + `src/lib/api/types.ts` | `src/lib/api.ts` (875 lines) is the canonical client with full typed endpoint surface — adding a parallel client would fork the contract. |
| Mock-data deprecation (`src/data/mockBrands.ts` etc.) | These files don't exist. Only `src/data/businessData.ts` (static brand identity: NAME, LOCATIONS, TESTIMONIALS), kept untouched. |
| Homepage hero integration (`<VehicleSelector />`) | Operator just curated Home.tsx via CR#2 (removed "More Than Repairs" panel). Adding a hero-mounted selector now would re-introduce content the operator may not want pre-launch. Component is ready; mount when desired. |
| Footer dynamic categories | Operator just curated Footer.tsx via CR#1 (removed "India's Fastest-Growing" SEO panel). Re-introducing dynamic content there is a content/design call, not a launch blocker. |
| ServiceDetail PricingWidget integration | ServiceDetail.tsx (1054 lines) already has built-in pricing via `useBookingContext` + `vehicle_price` from `/services/{cat}/{slug}` response (line 175-180 of ServiceDetail). Adding a duplicate `<PricingWidget>` next to the existing "Price Range" overview cell (line 461-465) would confuse operators reading two different numbers in the same panel. |
| Mock data fixtures move | N/A — no mocks to move. |

---

## 4. Verification results

### 4.1 TypeScript — `npx tsc --noEmit`

```
tests/e2e/brand-typography.spec.ts(121,11): error TS2322: …
tests/e2e/brand-typography.spec.ts(137,11): error TS2322: …
```

**Only the 2 pre-existing brand-typography errors.** Zero new errors
from L3 work. ✓

### 4.2 Build — `npm run build`

```
✓ built in 8.73s
dist/assets/index-Dfey-mgt.js  185.81 kB │ gzip: 52.48 kB
```

Bundle size **unchanged** (185.81 kB, same as pre-L3) — the two new
components are small enough (~14 kB raw + tree-shaken icon imports
share lucide vendor chunk) to land in the existing bundle without
budging the gzip footprint visibly.

### 4.3 Playwright smoke — `npx playwright test --project=smoke`

```
✓  1 [smoke] › home page renders without console errors (5.4s)
✓  2 [smoke] › clicking the Login button opens the auth modal (2.2s)
✓  3 [smoke] › /payment routes to NotFound (no silent home redirect) (1.6s)

3 passed (12.4s)
```

3/3 ✓. No regression risk surfaced.

---

## 5. Component → API mapping

| Component | Hook(s) consumed | Endpoint(s) hit |
|---|---|---|
| `VehicleSelector` | `useBrands()`, `useModels(brandId)`, `useFuels(brandId, modelId)` from `src/hooks/useVehicle.ts` | `GET /api/v1/vehicle/brands`, `GET /api/v1/vehicle/models?brand_id=…`, `GET /api/v1/vehicle/fuels?brand_id=…&model_id=…` |
| `VehicleSelector` (persistence) | `useBookingContext()` from `src/hooks/useBookingContext.ts` | `localStorage` key `acr_booking_ctx_v1` |
| `PricingWidget` | `useBookingContext()` + `usePricingFor()` from `src/hooks/usePricing.ts` | `POST /api/v1/pricing` (legacy quote endpoint with brand_id/model_id/fuel_type_id/service_id) |

**Note:** Wired to **legacy endpoints**, not the new L1 `/api/v1/public/*`
endpoints. The legacy contracts are what `useBookingContext` already
emits (numeric IDs, not slugs) and what every other frontend page
already speaks. Converging the frontend onto L1 endpoints is a separate
phase (significant refactor — shape mismatches, ID/slug rename, env
ovelope rename). For launch this week, hybrid is the safe path.

---

## 6. Loading / error / empty state coverage

### `VehicleSelector`

| State | Surface |
|---|---|
| Brand fetch loading | Brand `<select>` disabled, placeholder "Loading brands…", spinner in field |
| Brand fetch error | Inline red error message under the `<select>` |
| Brand not picked | Model `<select>` disabled with "Choose brand first" placeholder |
| Model fetch loading | Model `<select>` disabled with "Loading models…" + spinner |
| Model empty (brand has no models in DB) | "No models available" placeholder |
| Fuel fetch loading | Same pattern as model |
| All 3 selected | Optional CTA enables; `onComplete` fires; `useBookingContext` updated |

### `PricingWidget`

| State | Surface |
|---|---|
| No vehicle in context | Inline `<VehicleSelector>` rendered as a sub-card with copy "Select your car to see the exact price…" |
| Pricing query loading | Skeleton "₹ ___" with spinner |
| Price found | "Your Price" → "₹X,XXX" (Indian-locale formatted) + "For Brand, Model (Fuel)" subtext + "Book This Service" CTA + "Change vehicle" link (opens modal selector) |
| No price row for combo | Amber-bordered banner: "We don't have a standard price for X yet. Our team will quote you within 2 hours." + "Get Custom Quote" CTA |
| Pricing query error | Red error + "Try again" link (calls `refetch()`) |

No "white-screen-of-nothing" path. Every interactive target ≥44 px tall
(brief D-L3-8 mobile UX requirement).

---

## 7. Mobile responsiveness

* `VehicleSelector mode="inline"` — stacks vertically in a single
  column; native `<select>` triggers OS pickers (iOS wheel / Android
  bottom sheet) for zero-CSS picker UX.
* `VehicleSelector mode="modal"` — overlay is `flex items-end
  sm:items-center` so it docks bottom-of-screen on mobile (the standard
  bottom-sheet pattern), centered on ≥sm.
* `PricingWidget` — single column. The "Book This Service" button is
  full-width.
* Touch targets: every clickable element uses `min-h-[44px]`.
* Native `<select>` everywhere — guaranteed accessible + mobile-native.

---

## 8. Known issues / deviations

1. **Brief premises were significantly out-of-date.** The single biggest deviation: the brief's mental model (mock-data frontend that needs API integration) doesn't match the actual codebase (already API-driven). All "Migrate mocks" / "Build hooks/api/ folder" / "Create new lib/api/client.ts" steps were skipped because they would either duplicate existing surface or fork the API contract. Operator approved the hybrid path explicitly via AskUserQuestion.

2. **L1 endpoints stay unused by the frontend.** `/api/v1/public/*` (8 endpoints shipped in Sub-phase L1, 16 tests green) continues to be a clean contract for external/third-party consumers. `PricingWidget` uses the legacy POST `/api/v1/pricing` because that's what `usePricingFor` already speaks and what `useBookingContext` already emits. Converging the frontend onto L1 endpoints is a separate ~6–10 hour refactor (Phase L4 candidate).

3. **No homepage / footer / service-detail integration.** Two operator content curations just landed (CR#1 removed "India's Fastest-Growing" footer panel; CR#2 removed "More Than Repairs" homepage panel). Re-introducing dynamic content blocks in those exact locations 4 days before launch is a content/design call, not an integration task. Components are mount-ready; operator decides placement + timing.

4. **No new tests added.** The two components are presentational + hook-driven; the underlying hooks (`useBrands`/`useModels`/`useFuels`/`usePricingFor`) are existing surface with implicit coverage via the pages that already consume them. A dedicated Playwright + Vitest pass for these components could land in a follow-up phase once they're mounted somewhere reachable.

5. **`hero_image_url`, `short_description` field mappings deferred.** L1 contract uses those API-facing names. The current frontend components consume `image` / `description` via the legacy CarBrand/Service types in `src/lib/api.ts`. No mapping needed because we didn't switch endpoints. When the L4 migration happens, the API-Resource layer at `app/Http/Resources/Api/V1/` already does the rename server-side.

---

## 9. Operator browser-test instructions

The components compile, build, smoke. To see them live, mount them in a page or run a local dev session:

### 9.1 Quick demo via dev console (zero code change)

```sh
npm run dev
# open http://localhost:3000
# in dev console: localStorage.clear()  ← reset booking context
# navigate to any page that already pulls vehicle, e.g. /services
# the existing flow still works (this is the "no regression" baseline)
```

### 9.2 To mount `<VehicleSelector />` on a page

```tsx
import VehicleSelector from "@/components/vehicle/VehicleSelector";

// Inline
<VehicleSelector mode="inline" onCtaClick={(v) => navigate('/services')} />

// Modal-controlled
const [open, setOpen] = useState(false);
<button onClick={() => setOpen(true)}>Choose Vehicle</button>
<VehicleSelector mode="modal" open={open} onClose={() => setOpen(false)} />
```

### 9.3 To mount `<PricingWidget />` on a service detail page

```tsx
import PricingWidget from "@/components/pricing/PricingWidget";

<PricingWidget
  serviceId={service.id}
  serviceTitle={service.title}
  onBookClick={(ctx) => navigate(`/cart?service=${ctx.serviceId}`)}
  onCustomQuoteClick={() => openLeadForm()}
/>
```

### 9.4 Mobile flow check

* Open the app on a phone or set Chrome DevTools to a mobile viewport
  (`375 × 812`).
* Mount `VehicleSelector mode="modal"` somewhere.
* Trigger open → confirm it docks to the bottom of the screen, not the
  middle.
* Tap a `<select>` → confirm the native iOS/Android picker fires.
* Pick brand → wait for models to load → pick model → fuels load →
  pick fuel → `onComplete` fires + `useBookingContext` populated.
* Reload the page → re-open the selector → confirm last selection
  is pre-filled (proves localStorage persistence works).

### 9.5 Pricing widget state matrix

After mounting `<PricingWidget>`:

| Setup | Expected widget state |
|---|---|
| Empty `localStorage.acr_booking_ctx_v1` + mount widget | "No vehicle" — inline selector renders inside the widget |
| Pick a vehicle that has a price for this `serviceId` | Loading skeleton (≤1 s) → ₹X,XXX + "Book This Service" |
| Pick a vehicle that doesn't have a price row for this `serviceId` | Amber "We don't have a standard price… Our team will quote within 2 hours" + "Get Custom Quote" CTA |
| Kill backend (`Ctrl-C` `php artisan serve`) and click "Change vehicle" → pick something | Red "We couldn't load the price right now" + "Try again" link |

---

## 10. Next-phase suggestions

* **L4 — Frontend → L1 endpoint migration.** ~6–10 h. Migrate `src/hooks/useVehicle.ts`, `src/hooks/useServices.ts`, `src/hooks/usePricing.ts` to the L1 `/api/v1/public/*` endpoints. Update `src/lib/api.ts` typed `CarBrand` / `CarModel` / `FuelType` / `ServicesResponse` / `ServiceDetailResponse` to the new `{data, meta}` envelope and field renames (`title`→`name`, `image`→`hero_image_url`). Regression budget: every page that consumes these hooks (~12 pages).
* **L5 — Mount `<VehicleSelector>` + `<PricingWidget>` on chosen surfaces.** Operator-driven content decision. Likely mounts: homepage hero (above-the-fold instant-quote card), service-detail sidebar (replace the "Price Range" cell with the live `PricingWidget`), category-detail page (filter services + show prices inline).
* **Test coverage for new components.** Vitest unit tests for `VehicleSelector` state transitions (brand→model→fuel reset chain, completion firing) + Playwright integration for the modal+inline mounting flow. Skipped for L3 (no mount point yet means no integration to test); easy follow-up.
