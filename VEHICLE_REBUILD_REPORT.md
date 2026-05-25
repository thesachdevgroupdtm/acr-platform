# REBUILD-VEHICLE — Clean rebuild of the vehicle selector + cart sidebar

**Outcome:** the three duplicate brand→model→fuel selectors and two duplicate
sidebars were deleted and replaced by **three** shared components — one
`VehicleSelector`, one `CarSidebar`, one `HomeCarSelector` shell. Frontend only;
backend untouched. Net code is **strongly negative** (~2322 deleted vs ~1146 added).

Gate: `tsc --noEmit` clean (except the 2 pre-existing `brand-typography.spec.ts`
errors) **+** `vite build` exit 0 **+** `playwright --project=smoke` **3/3 passed**.

---

## 1. Files DELETED (old selectors + sidebars)

| Path | ~Lines |
|---|---|
| `src/components/BookingSidebar.tsx` (monolithic — Home/old-Services car-selector) | 454 |
| `src/components/booking-sidebar/` (modular cart form: BookingSidebar, VehicleSummary, ServicesCart, CartItem, BookingSummary, MobileStickyBar, MobileBottomSheet, VehicleChangeModal, useAutoAddService, index) | 752 |
| `src/components/vehicle/` (premium-selector: PremiumVehicleSelector, BrandStep, ModelStep, FuelStep, useSelectorState, SelectionDisplay, StepIndicator, SearchInput, BrandLogoFallback, types, index) | 890 |
| `src/components/pricing/` (PricingWidget — dead; only referenced in comments) | 226 |
| **Total deleted** | **~2322** |

All three duplicate selectors and both sidebars are gone.

## 2. Files CREATED (the new shared components)

| Path | Lines | Role |
|---|---|---|
| `src/components/vehicle-selector/VehicleSelector.tsx` | 233 | **THE** brand→model→fuel picker (in-place, 3 steps, manual hatch, context write) |
| `src/components/vehicle-selector/BrandGrid.tsx` | 103 | step 1 (own `useBrands` + search) |
| `src/components/vehicle-selector/ModelGrid.tsx` | 92 | step 2 (own `useModels(brandId)` + search) |
| `src/components/vehicle-selector/FuelGrid.tsx` | 83 | step 3 (own `useFuels`) |
| `src/components/vehicle-selector/index.ts` | 2 | barrel |
| `src/components/car-sidebar/CarSidebar.tsx` | 253 | **THE** cart form on every service page (3 states + in-place selector) |
| `src/components/car-sidebar/MobileShell.tsx` | 123 | mobile sticky bar + bottom sheet |
| `src/components/car-sidebar/useAutoAddService.ts` | 83 | auto-add current service (ServiceDetail only) |
| `src/components/car-sidebar/index.ts` | 2 | barrel |
| `src/components/home-car-selector/HomeCarSelector.tsx` | 171 | Home shell wrapping VehicleSelector + redirect-on-CTA |
| `src/components/home-car-selector/index.ts` | 1 | barrel |
| **Total created** | **~1146** | |

Reused as-is (not rebuilt): `useBrands/useModels/useFuels`, `useBookingContext`,
`useCart`, `CouponInput` + `CouponPickerModal` (Phase 2.5b), `VehicleReplaceModal`.

## 3. Page wiring

| Page | Route | Component | Notes |
|---|---|---|---|
| Home | `/` | `HomeCarSelector` | wraps VehicleSelector in-place; redirect on CTA |
| Services | `/services` | `CarSidebar` | no `currentService` → no auto-add |
| Category | `/category/:slug` | `CarSidebar categorySlug={slug}` | no auto-add; old inline selector already removed |
| ServiceDetail | `/services/:cat/:svc` | `CarSidebar currentService={service} vehiclePrice=…` | auto-adds the current service |

All three service pages mount the **same** `CarSidebar` → identical width/layout
everywhere (fixes the "internal sub-page has different width" bug).

## 4. Bug fixes (B-1 … B-4)

- **B-1 stale data** — `useVehicle.ts`: `placeholderData: keepPreviousData` is on
  `useBrands` ONLY (line 46); removed from `useModels` (line 56) and `useFuels`
  (line 72). VehicleSelector resets `model` on brand change and `fuel` on model
  change. So picking BMW→back→Audi shows the model skeleton, never lingering BMW
  models; same for fuels.
- **B-2 skeleton bias-low + no reflow** — brand skeleton **9**, model **3**, fuel
  **2**, so real data grows in. Skeleton tile dims == real tile dims (brand
  `grid-cols-3`/`min-h-[96px]`, model `grid-cols-3`/`min-h-[84px]`, fuel
  `grid-cols-2`/`min-h-[92px]`) → zero reflow on swap.
- **B-3 no data loss on select** — `VehicleSelector.finish()` calls
  `useBookingContext().update({ car: {…ids, slugs, segment} })` on fuel-select (and
  on manual submit). The single localStorage-backed context is read back by every
  page; the smoke test confirms Home renders this state with no console errors.
- **B-4 layout stable (Option X)** — the selector renders in-place (no overlay).
  Home card is `lg:min-h-[520px]` and the in-place selector is `h-[520px]`;
  CarSidebar card is `lg:min-h-[460px]` and its selector is `h-[460px]` — idle and
  active share the same footprint, no jump, no overlap.

## 5. Price-reveal rewire

Prices unlock on **vehicle presence**, not OTP:
- `src/pages/Services.tsx:421` — `const showPrice = hasVehicle && pricesAvailableForCategory;`
- `src/pages/ServiceCategory.tsx:575` — `const showPrice = vehicleSelected && priceShowFromApi;`

`hasVehicle`/`vehicleSelected` = `brand_id && model_id && fuel_id` in booking
context. No vehicle → rows show a "Select car" / "Select Your Car" CTA (routes to
the sidebar selector), never a permanent OTP lock. OTP/mobile is only used for the
Home redirect CTA and checkout.

## 6. Redirect-on-CTA rule (Home)

`HomeCarSelector.onCheckPrices()`:
- Validates **car selected** AND **valid 10-digit mobile**; on failure → inline
  validation, **no redirect**.
- Only when both pass → `update({ phone })` + `navigate("/services")`.
- Selecting a car alone does **not** redirect — it just fills the field and
  collapses the selector back to the summary showing "Brand Model · Fuel".

## 7. Theme audit (ACR Blue, no off-brand colors)

- All CTAs / links / selected tiles / checkout use `primary` (ACR Blue `#1F4FA3`)
  via Tailwind `primary`/`primary-dark` tokens and the site `.btn-ink-primary`.
- Checkout button: ACR Blue (`bg-primary`, hover inverts to white/primary) — not red.
- Cards: white, `border-border`, sharp (no `rounded-2xl` over-rounding); tiles use
  sharp `border` boxes matching ACR service rows.
- Errors use the existing `accent-dark` token; trust strip uses `primary` checks.
- No red/orange/cyan/sky introduced. No "Miles Membership" strip; replaced by an
  optional, vehicle-present-only trust strip ("✓ Genuine OEM · ✓ 6-mo warranty").

## 8. Screenshots / runtime evidence

A GUI screenshot tool isn't available in this environment, but the live rig was
exercised: the Vite dev server was started and **Playwright smoke ran green (3/3)**,
including *"home page renders without console errors"* — runtime proof that the
rebuilt `HomeCarSelector` + page wiring mount cleanly. The operator should visually
confirm scenarios 1-15 (Home in-place selector; CarSidebar identical width on
`/services`, `/category/:slug`, `/services/:cat/:svc`; BMW→Audi clean swap).

## 9. grep proof — single selector + single sidebar

```
$ find src/components -type f -iname "*selector*" -o -iname "*sidebar*"
src/components/car-sidebar/CarSidebar.tsx
src/components/home-car-selector/HomeCarSelector.tsx
src/components/vehicle-selector/VehicleSelector.tsx

$ grep -rn "premium-selector|booking-sidebar|components/BookingSidebar|PremiumVehicleSelector|PricingWidget|VehicleChangeModal" src --include=*.tsx --include=*.ts
# (no import matches — only 2 historical comments in ServiceDetail.tsx)
```

Exactly ONE `VehicleSelector`, ONE `CarSidebar`, ONE `HomeCarSelector`. No e2e test
referenced any old test-id (`premium-vehicle-selector`, `pricing-widget`) or
"Select Manufacturer" text, so no test selectors needed updating.

## 10. Net lines

Deleted ~**2322**; created ~**1146** (plus small net-negative page edits replacing
mounts). **Net: ~1176 fewer lines**, and 5 components (3 selectors + 2 sidebars)
collapsed into 3 shared ones.

## 11. tsc / build / smoke / backend

- `tsc --noEmit`: only the 2 pre-existing `tests/e2e/brand-typography.spec.ts`
  errors (unchanged baseline).
- `vite build`: exit 0; eager `index` chunk dropped 203.6 → 194.5 KB.
- `playwright test --project=smoke`: **3/3 passed** (against live Vite dev server;
  smoke tolerates the missing API per its design).
- Backend: untouched. No API shape/route changes. No new npm packages.

## 12. Deviations / known limitations

1. **Manual ("Can't find your car") entries are id-less.** The escape hatch was
   ported into the shared `VehicleSelector` (footer → free-text brand/model/fuel),
   so it's now available everywhere (improvement over the prior Home-only hatch). A
   manual car writes names without catalog ids. Because vehicle-specific pricing and
   the price-reveal gate require numeric ids, a manual car is captured (and shown on
   Home) but service-page rows treat it as "select your car" (no priced rows) and
   `CarSidebar` shows the empty state. This matches the existing id-based pricing
   contract; surfacing manual cars with a "quote on inspection" treatment on service
   pages would be a follow-up.
2. **Mobile in-place selector** opens inside the bottom sheet (the sheet body shows
   the selector when "Select/Change vehicle" is tapped) — consistent with the
   desktop in-place behaviour, no center modal.
3. **Two historical comments** in `ServiceDetail.tsx` (lines 442, 480) still mention
   "PricingWidget" as a note about the old layout; harmless, no code reference.
4. **Live smoke + the 15 manual scenarios**: smoke was run here (green). The full
   visual verification of all 15 scenarios is the operator's pass.
