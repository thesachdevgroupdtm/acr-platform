# FORMS-1 — Right Form on Right Page + Vehicle-Selector Consolidation

**Scope:** frontend only. Backend untouched. No API response shapes or routes changed.
**Gate run after every step:** `npx tsc --noEmit` (clean except 2 pre-existing
`tests/e2e/brand-typography.spec.ts` errors) **+** `npx vite build` (exit 0).
**Live Playwright smoke + the 14 manual scenarios** require the operator's running
Vite (`:3000`) **and** Laravel API (`:8000`); that stack isn't available in this
sandbox (the app is API-only with no static fallback), so those are left for the
operator's verification pass — see "Testing" at the end.

Execution followed the locked order **0 → 1 → 2 → 3 → 4 → 5**; each step's gate
passed before starting the next.

---

## 1. STEP 0 — Price-reveal rewire (OTP `pricesShown` → `hasVehicle`)

Prices now unlock the instant a **complete vehicle** (brand+model+fuel) is in
booking context. No OTP/"Check Prices" gate. The existing `vehicleSelected`
helper (already derived from `useBookingContext`) is the `hasVehicle` signal —
no new flag invented.

| File | Old gate | New gate |
|---|---|---|
| `src/pages/Services.tsx` | row `showPrice = pricesShown && pricesAvailableForCategory` (prop `pricesShown={booking.pricesShown}`) | `showPrice = hasVehicle && pricesAvailableForCategory` (prop `hasVehicle={vehicleSelected}`); `CategorySectionProps.pricesShown` renamed → `hasVehicle` |
| `src/pages/ServiceCategory.tsx` | `showPrice = pricesShown && priceShowFromApi`; banners `pricesShown && bookingCar` / `!pricesShown` | `showPrice = vehicleSelected && priceShowFromApi`; banners `vehicleSelected && bookingCar` / `!vehicleSelected` |

No-vehicle state is **not a permanent lock**: the row price cell shows
"Select car", the row action shows **"Select Your Car"**, and the section banner
CTA reads "Select Your Car" — all routing the user to the sidebar selector. When a
vehicle is set but a row/category has no priced row, it shows "On Inspection" /
"Call Now" (Services) / "View Details" (Category) instead of a lock.

---

## 2. STEP 1 — Services → cart form

`src/pages/Services.tsx`

- **Before:** `import BookingSidebar from "../components/BookingSidebar"` (monolithic
  car-selector) mounted inside `<aside className="order-1 lg:order-2 …">` with
  `titleStart/titleAccent/titleEnd` + Send-OTP flow.
- **After:** `import { BookingSidebar } from "../components/booking-sidebar"` (modular
  CART form) mounted directly as the right grid column:
  ```tsx
  <BookingSidebar stickyTopPx={STICKY_OFFSET_PX} className="lg:order-2" />
  ```
- The component renders its own sticky `<aside>` (desktop) + fixed `MobileStickyBar`
  (confirmed `position: fixed lg:hidden`, so it's safe as a direct grid child).
- Lost capture UI (location `<select>`, phone, OTP, "EXPERIENCE THE BEST…" headline)
  is intentional — location/phone are collected at checkout. Price rows now reveal on
  `hasVehicle` (STEP 0), so removing the OTP CTA doesn't strand pricing.

---

## 3. STEP 2 — Category → cart form (+ inline selector deleted)

`src/pages/ServiceCategory.tsx` — the bespoke third copy of the brand→model→fuel
machine and all its fused page state were removed.

**Deleted:**
- The inline sidebar booking-card form (location/car/phone/OTP/"Check Prices").
- The full car-selector modal (`<AnimatePresence>{showCarSelector && …}</AnimatePresence>`,
  255 lines — removed via a verified line-range splice, UTF-8 no-BOM, LF preserved).
- Fused local state: `bookingPhone`, `otpSent/otpValue/otpVerified`, `bookingErrors`,
  `pricesShown`, `showCarSelector`, `carStep`, `pendingCar`, `carSearch`; the
  second `useBookingContext()` (`updateBookingCtx`); the auth-prefill effect and the
  local→context sync effect; handlers `onPhoneChange/sendOtp/verifyOtp/checkPrices/
  openCarSelector/closeCarSelector/selectBrand/selectModel/selectFuel/carBack`;
  derived `selectedCarLabel`, `filteredBrands/filteredModels/filteredFuels`,
  `bcInput/bcInputErr`; the module `FUEL_TYPES` const; the `useModels/useFuels` import.
- The sidebar trust-badge mini-card (the main column's WHY-CHOOSE section already
  carries the same trust content).

**Kept / rewired:**
- `bookingCar` and `bookingLocation` are now **read-only consts derived from booking
  context** (`bookingCtx0.car` / `bookingCtx0.location`) so the price list, banners,
  and add-to-cart keep working unchanged — the cart form writes context, this page
  reads it.
- `useBrands()`/`apiBrandRows` **stayed** — the overview/SEO copy ("X, Y and more",
  "{N}+ supported") uses brand data; it is not selector-only.
- All page content (overview, price list, services included, why choose, process,
  reviews, FAQs, supported brands) is unchanged.

**Mount:** `<BookingSidebar categorySlug={categorySlug} stickyTopPx={STICKY_OFFSET_PX + 68} className="lg:order-2" />`

**Line count removed:** `ServiceCategory.tsx` **1664 → 968 lines (−696)**.

---

## 4. STEP 3 — Cart-form props optional

`src/components/booking-sidebar/BookingSidebar.tsx`

- `currentService` → `currentService?: {…} | null` (default `null`).
- `categorySlug` → `categorySlug?: string` (default `""`).
- `useAutoAddService` already no-ops when `service` is null (verified:
  `if (!service || !hasVehicle) return;`), so a null/omitted service ⇒ no auto-add.
- Mounts simplified to `<BookingSidebar … />` (Services) and
  `<BookingSidebar categorySlug={categorySlug} … />` (Category) — no placeholder props.
- **ServiceDetail unchanged** — it still passes `currentService={service}` and
  auto-adds (regression #12 preserved).

---

## 5. STEP 4 — Inner selector consolidated onto `PremiumVehicleSelector`

**Goal: one brand→model→fuel state machine.** Before FORMS-1 there were three
implementations (Home/old-Services monolithic inline, Category inline,
`PremiumVehicleSelector`). Category's copy was deleted in STEP 2. STEP 4 migrated
Home's monolithic copy onto the shared selector.

`src/components/BookingSidebar.tsx` (monolithic — now **Home-only**):
- The inline `carStep` 1/2/3 grids, `pendingCar`, `carSearch`, `useBrands/useModels/
  useFuels` queries, `brandList/modelList/FUELS/fuelIconFor`, and `selectBrand/
  selectModel/selectFuel/carBack` were removed.
- The selector view now renders the shared selector inside Home's existing
  OTP/location/phone shell:
  ```tsx
  <PremiumVehicleSelector mode="panel" showCta={false}
    onComplete={() => { clear errors.car; closeCarSelector(); }} />
  ```
  `useSelectorState` already writes the chosen vehicle to booking context, so
  `onComplete` just closes the inline view.
- **Line count removed:** `BookingSidebar.tsx` **781 → 481 lines (−300)**.

**Now using `PremiumVehicleSelector`:** Home shell (panel) · cart-form change-vehicle
modal `VehicleChangeModal` (widget) · `PricingWidget` (widget). **One machine.**

**Styling match:** `PremiumVehicleSelector` `panel` mode (previously wired but
unused by any consumer) restyled to `bg-transparent` so it sits flush inside Home's
sharp ACR card, which already supplies border/shadow/padding — no rounded
card-in-card. `widget`/`hero` styling untouched, so PricingWidget and the cart-form
modal are visually unaffected. (Inner tiles remain `rounded-2xl`; that token is
shared across all modes, so it was left as-is to avoid restyling the selector on
ServiceDetail/Services/Category.)

**"Other" free-text — DEVIATION (see §11):** preserved via a Home-shell **"Enter
manually"** fallback (brand/model/fuel text inputs → writes a no-id car to context),
**not** ported into the shared id-based `BrandStep`/`ModelStep`. Rationale below.

---

## 6. STEP 5 — Three selector bug fixes (one place, everywhere)

| Fix | File(s) | Change |
|---|---|---|
| **D-5 stale data** | `src/hooks/useVehicle.ts` | Removed `placeholderData: keepPreviousData` from `useModels` and `useFuels`. On a brand→brand (or model→model) change the dependent grid now shows its skeleton instead of the previous parent's children lingering through the refetch window (the "BMW models linger ~5-6s after Audi" bug). Kept on `useBrands` only (stable top-level list, avoids first-open flash). `useSelectorState` already resets child picks on a parent change, so no in-progress selection is lost. |
| **D-6 skeleton stability** | `ModelStep.tsx`, `FuelStep.tsx` | Counts biased LOW so real data **grows in**, never shrinks: brands **8** (already), models **4 → 3**, fuels **2** (already). Skeleton tile dimensions aligned to the real tiles exactly: models `min-h-[60px] md:min-h-[68px] rounded-2xl`; fuels `min-h-[72px] rounded-2xl` (was `h-[80px]`) — same grid/gap → zero reflow on swap. |
| **D-7 layout shift / overlap** | `src/components/BookingSidebar.tsx` | Home card gets `lg:min-h-[520px]`, matching the open-selector view's `h-[520px]`. Idle (form) and active (selector) are now the same height — no jump on toggle, card can't grow to overlap content below. Idle content stays top-aligned (block flow); the min-height reserves the space below. |

---

## 7. Per-page right-column form — FINAL STATE

| Page | Route | Right-column component | Form | Inner selector |
|---|---|---|---|---|
| Home | `/` | monolithic `BookingSidebar` (`src/components/BookingSidebar.tsx`) | **car-selector** (location + OTP + brand→model→fuel) — KEPT | `PremiumVehicleSelector` (panel) + manual fallback |
| Services | `/services` | modular `BookingSidebar` (`src/components/booking-sidebar/`) | **CART** | `PremiumVehicleSelector` (widget, via VehicleChangeModal empty-state) |
| ServiceCategory | `/category/:slug` | modular `BookingSidebar` | **CART** | `PremiumVehicleSelector` (widget) |
| ServiceDetail | `/services/:category/:service` | modular `BookingSidebar` | **CART** (auto-adds current service) | `PremiumVehicleSelector` (widget) |

There is only one leaf service page (`/services/:category/:service`); no other
nested service route exists. `PricingWidget` (a component, not a route) also uses
`PremiumVehicleSelector` (widget).

---

## 8. Vehicle-selection fallback (no stranding)

A user landing directly on `/services` or `/category/:slug` with no car is not
stranded: the cart form's `VehicleSummary` shows the "Select your car to see
accurate pricing." empty state → **Select Vehicle** button → `VehicleChangeModal`
→ `PremiumVehicleSelector` → writes the triple to booking context → prices reveal
(STEP 0). Confirmed end-to-end in code; left for live manual verification (scenarios
5 & 6).

---

## 9. Screenshots

Not capturable in this sandbox (no live Vite/Laravel + no browser). For the
operator's pass, capture:
- **Services** & **Category** right column — should be the cart form (vehicle
  summary + cart + coupon + checkout), **no** Send-OTP selector.
- **BMW → Audi** in any selector — clean skeleton→data, no lingering BMW models.
- **Home card idle vs open-selector** — identical height, no shift/overlap.

---

## 10. Net lines & duplication removed

Directly-observed before/after on the files FORMS-1 changed (the repo's working tree
also carried unrelated pre-existing edits, so this is the FORMS-1-attributable delta,
not `git diff --stat`):

| File | Before | After | Δ |
|---|---|---|---|
| `src/pages/ServiceCategory.tsx` | 1664 | 968 | **−696** |
| `src/components/BookingSidebar.tsx` (monolithic) | 781 | 481 | **−300** |
| `src/pages/Services.tsx` | 563 | 575 | +12 |
| `src/components/booking-sidebar/BookingSidebar.tsx` | 153 | 158 | +5 |
| `src/hooks/useVehicle.ts` | 74 | 75 | +1 |

**~996 lines of duplicated brand→model→fuel selector code removed** (two of the
three implementations), replaced by reuse of the single shared
`PremiumVehicleSelector`. Net change across all FORMS-1 files is strongly negative.
**Confirmed: exactly one brand→model→fuel step machine remains** —
`PremiumVehicleSelector` (`useSelectorState` + `BrandStep`/`ModelStep`/`FuelStep`).

---

## 11. Build status, deviations, deferred

**Build/TS:** `tsc --noEmit` clean except the **2 pre-existing**
`tests/e2e/brand-typography.spec.ts` errors (unchanged baseline). `vite build`
exit 0 at every step. PowerShell-written files verified **no BOM, LF-only** (repo
convention preserved). Backend untouched.

**Deviations / deferred:**
1. **"Other" free-text NOT ported into shared `BrandStep`/`ModelStep`** (deviates
   from D-4 and verification #13). `VehicleSelection` is strictly id-based
   (`brand_id: number`, …) and the shared selector backs pricing on
   ServiceDetail/Services/Category/PricingWidget. Forcing no-id "Other" values into
   that contract would require making the ids nullable, rippling into
   `useSelectorState`, `SelectionDisplay`, the context write, and price lookups —
   real regression risk to the just-fixed pricing flow, exactly the kind of breakage
   the locked sequencing is meant to avoid. Instead "Other" is preserved on **Home**
   via an "Enter manually" shell fallback (writes a no-id car → off-catalog →
   priced on inspection downstream). **Consequence:** the cart-form change-vehicle
   modal (Services/Category/ServiceDetail) has no "Other" — but that modal never had
   one (it always used the id-based `PremiumVehicleSelector`), so this is not a
   regression there; it is a known gap vs. the old inline Services/Category forms.
   *Recommended follow-up if "Other" is required everywhere:* add an optional
   `allowOther` prop + a nullable-id `VehicleSelection` variant to the shared
   selector, as its own change with pricing regression tests.
2. **Sidebar trust-badge mini-card dropped on Category** — the main column's WHY
   CHOOSE section already carries trust content; matches ServiceDetail's clean
   cart-only sidebar.
3. **Inner selector tiles remain `rounded-2xl`** — shared token across modes; left
   as-is so the cart-form selector on other pages isn't restyled. Only the Home card
   *wrapper* was flattened (panel = `bg-transparent`) to match the sharp ACR card.
4. **A few selector-only icon imports remain in `ServiceCategory.tsx`** (e.g.
   `ArrowLeft`, `X`, `Search`, `RefreshCw`). They are harmless (no `noUnusedLocals`,
   no TS error) and were left untouched because several lucide icons there are
   referenced indirectly via `icon: Icon` data arrays — blind pruning risked removing
   a used one. The monolithic `BookingSidebar.tsx` imports **were** pruned (verified).
5. **Live Playwright smoke + 14 manual scenarios** not run here (needs live
   Vite + Laravel). tsc + build were the per-step automated gate.

**Operator: please run the 14 verification scenarios.**
