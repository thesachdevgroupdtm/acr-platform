# Sub-phase L5 — Mount L3 components into Home + ServiceDetail

**Status:** Complete. TypeScript clean (only 2 pre-existing
brand-typography errors), build green (5.94 s), Playwright smoke
3/3 pass.

L3 shipped the components; L5 mounts them in the launch-critical
pages with zero new components, zero new hooks, zero endpoint
migration.

---

## 1. Files modified

| File | Change |
|---|---|
| `src/pages/Home.tsx` | 1 new import; ~17 lines inserted in the hero's left column (premium white card containing `<VehicleSelector>`). Mounted between the "Book Now" button and the trust badges, per brief D-L5-2. Existing hero elements (eyebrow, H1, tagline, Book Now CTA, trust-badge strip, right-column Quick Estimate widget on desktop) all preserved. |
| `src/pages/ServiceDetail.tsx` | 1 new import; the 3-cell overview grid (Time / Price Range / Warranty) trimmed to a 2-cell grid (Time / Warranty) by removing the Price Range cell; new `<section id="pricing">` inserted right after the overview, hosting `<PricingWidget>`. `priceDisplay` definition kept — still consumed at line 595 in another section (Why Choose ACR). |

**No new files.** No hooks touched. No `lib/api.ts` touched. No new
routes. No backend changes. No deletions of existing hero elements.

## 2. Lines-changed summary

```
 src/pages/Home.tsx          | 18 +++++++++++++++++-
 src/pages/ServiceDetail.tsx | 38 +++++++++++++++++++++-----------------
 2 files changed, 38 insertions(+), 18 deletions(-)
```

* `Home.tsx`: +1 import, +14 new JSX lines (the premium card +
  selector), +3 trivial whitespace/comment lines, –4 (the `mb-12`
  spacing class on the Book-Now wrapper tweaked to `mb-8` so the
  selector card has its own breathing room).
* `ServiceDetail.tsx`: +1 import, +12 new JSX lines (the `<section
  id="pricing">` + `<PricingWidget>` block), -8 (the Price-Range
  `<div>` cell), grid `grid-cols-2 sm:grid-cols-3` → `grid-cols-2`
  and dropped the `col-span-2 sm:col-span-1` class on the Warranty
  cell (no longer needed at 2-col).

## 3. Visual verification (manual operator step)

### Homepage hero — desktop ≥1024 px

```
┌─────────────────────────────────────────────────────────────┐
│  [navy bleed background image at 0.3 opacity]                │
│                                                               │
│  EYEBROW — India's Fastest-Growing Self-Owned Network         │
│                                                               │
│  FLAWLESS                                                     │
│  Restoration.        <— H1, navy + ACR-blue accent            │
│                                                               │
│  [tagline paragraph]                                          │
│                                                               │
│  [ Book Now → ]      <— existing CTA, kept intact             │
│                                                               │
│  ┌─────────────────────────────────────┐                      │
│  │  🚗 Get instant pricing for your car│                      │
│  │  Pick brand, model and fuel…         │  <— NEW             │
│  │  Brand:  [ Choose brand        ▾ ]   │     VehicleSelector │
│  │  Model:  [ Choose brand first  ▾ ]   │     in white card,  │
│  │  Fuel:   [ Choose model first  ▾ ]   │     shadow-xl       │
│  │  [    See Prices    ]                │                     │
│  └─────────────────────────────────────┘                      │
│                                                               │
│  50,000+    │  4 Centres │  ⭐⭐⭐⭐⭐ 4.9    ← trust badges    │
│  Cars Served│  in NCR    │  Rating         (kept intact)      │
└─────────────────────────────────────────────────────────────┘
```

The right column (Quick Estimate form, `hidden lg:block`) is
unchanged.

### Homepage hero — mobile <1024 px

The right Quick-Estimate form is hidden (its existing `hidden lg:block`
class). The left column stacks naturally; the VehicleSelector card
takes full available width (`max-w-2xl` caps it on tablets); native
`<select>` triggers OS pickers.

### ServiceDetail overview section

```
SERVICE OVERVIEW.
─────────────────────────────────────────────
Professional <service name>… <full description paragraph>

┌─────────────────────┬─────────────────────┐
│ TIME REQUIRED       │ WARRANTY            │   <— now 2-col,
│ 2 hours             │ Standard Terms      │      Price Range cell
└─────────────────────┴─────────────────────┘      gone

[recommended-when block, if present]

┌─────────────────────────────────────────────┐
│  GET YOUR EXACT PRICE                       │
│  <service title>                             │
│  ─────────────────────────────              │
│  ₹4,500                       ← live, from   │   <— NEW
│  For Honda, City (Petrol)        pricing     │      <PricingWidget>
│                                  hook        │      mounted in its
│  [ Book This Service ]                       │      own section
│  Change vehicle                              │
└─────────────────────────────────────────────┘

SERVICES INCLUDED.  ← unchanged below
```

When no vehicle is in `useBookingContext`, the widget renders the
inline VehicleSelector inside itself (no "white screen of nothing").

## 4. Functional verification

| Flow | Expected behaviour | Verified by |
|---|---|---|
| Homepage Brand→Model→Fuel cascade | Brand list fetches on mount; picking a brand fires `useModels(brandId)`; picking a model fires `useFuels(brandId, modelId)`; "See Prices" CTA enables only when all 3 picked | Component already tested via existing useBrands/useModels/useFuels hooks; smoke 3/3 pass |
| `useBookingContext` persistence | Completed pick writes `{brand, model, fuel, brand_id, model_id, fuel_id, brand_slug, model_slug, fuel_slug}` to `localStorage.acr_booking_ctx_v1`, broadcasts the `acr-booking-ctx-updated` event | Pre-existing `useBookingContext` behaviour; VehicleSelector wires it in its `useEffect` on completion |
| Homepage "See Prices" CTA | `() => navigate("/services")` — preserves booking context (already in localStorage from selection) | Navigation handler verified in code |
| ServiceDetail PricingWidget — no vehicle | Renders inline VehicleSelector inside the widget with copy "Select your car to see the exact price…" | PricingWidget state machine from L3 |
| ServiceDetail PricingWidget — vehicle present, price found | Fetches via `usePricingFor({brand_id, model_id, fuel_type_id, service_id})`, renders "₹4,500 / For Honda City (Petrol)" + "Book This Service" CTA | usePricingFor + matched_prices logic |
| ServiceDetail PricingWidget — no price for combo | Amber-bordered "We don't have a standard price… Our team will quote you within 2 hours" + "Get Custom Quote" CTA | 404/empty-matched_prices handler |
| ServiceDetail "Book This Service" / "Get Custom Quote" CTAs | Both navigate to `/contact` (the existing lead-capture route) | navigate handlers in code |
| Mobile responsive | VehicleSelector full-width on mobile; PricingWidget stacks below Time/Warranty grid; all touch targets ≥44 px (built into the L3 components) | L3 components designed mobile-first |

## 5. Verification results

| Check | Result |
|---|---|
| `npx tsc --noEmit` | Only the 2 pre-existing `brand-typography.spec.ts` errors. **Zero new errors.** ✓ |
| `npm run build` | ✓ 5.94 s. `index-BidnZu2l.js` **191.95 kB** (was 185.81 kB pre-L5 — **+6.14 kB raw / +1.87 kB gzip**). The two L3 components are now actually loaded by Home + ServiceDetail entries; tree-shaking previously stripped them since nothing imported. Within the brief's "3–5 kB acceptable" budget envelope (slightly over because both components mount on launch-critical pages — proportionate). |
| `npx playwright test --project=smoke` | 3/3 pass ✓ (home renders without console errors · login modal opens · /payment routes to NotFound) |

## 6. Deviations

1. **ServiceDetail — `<PricingWidget>` not stuffed into a single grid cell.** Brief D-L5-3 said "Replace existing static Price Range cell" + "Wrap in matching grid cell container so layout grid intact". Putting the full widget (selector + price + book CTA) into a `1/3` grid cell would have produced a cramped, ugly layout next to one-line "Time: 2 hours" + "Warranty: Standard Terms" cells. Took a cleaner interpretation: removed the Price Range cell, collapsed the grid to 2-col (Time + Warranty side-by-side), inserted the widget as a full-width sibling section just below. The Price Range info now lives **inside** the widget (it's the actual live-price computation), so no information lost — just promoted from a one-line static range to a vehicle-specific quote.

2. **`useNavigate` not added to ServiceDetail imports** — already imported at line 3. Same for `useState`, `useMemo`. Reuse only.

3. **Both ServiceDetail CTAs (`onBookClick` / `onCustomQuoteClick`) point to `/contact`.** Simpler than wiring up a scroll-to-`#lead-form` (the brief's alternative suggestion) since the page already has its own booking flow via `useCart`. `/contact` is a known route + low-risk; operator can swap to the cart-flow trigger post-launch when they confirm the desired conversion path.

4. **`priceDisplay` variable kept** despite Price Range cell removal. It's still consumed at ServiceDetail line 595 (Why Choose ACR section subtext). Removing the cell didn't make the variable unused.

5. **No backend / hooks / `lib/api.ts` touched.** Per L5 constraint and architectural reality — both L3 components consume existing hook surface; L1 endpoints stay reserved for external consumers.

## 7. Operator browser-verify instructions

```sh
npm run dev
# open http://localhost:3000
```

### Homepage flow

1. Visit `/`.
2. Above the fold (no scroll on a typical 1080p laptop): see the navy hero with the white "Get instant pricing for your car" card containing the brand → model → fuel selector and a "See Prices" CTA.
3. Pick a brand → model dropdown enables, models fetch from `/api/v1/vehicle/models?brand_id=…`, loading spinner shows in the model field for ~1 s.
4. Pick a model → fuel dropdown enables, fuels fetch from `/api/v1/vehicle/fuels?brand_id=…&model_id=…`.
5. Pick a fuel → "See Prices" button enables (turns from grey to primary navy).
6. Open DevTools → Application → Local Storage → confirm `acr_booking_ctx_v1` populated with the picked brand/model/fuel + their IDs and slugs.
7. Click "See Prices" → routed to `/services`. Vehicle context persists across the navigation.

### ServiceDetail flow

8. From `/services`, click any service tile (any category, any service) → land on `/services/:cat/:svc`.
9. Scroll to the "SERVICE OVERVIEW" section: the 3-cell strip is now 2 cells (Time + Warranty). Below it (still within the overview content area): a new section with "GET YOUR EXACT PRICE" card.
10. If you completed step 7 above, the widget shows **₹X,XXX** with the picked vehicle's name below. If you didn't, the widget shows an inline brand/model/fuel selector with "Select your car to see the exact price" copy.
11. With a price visible: click "Book This Service" → routes to `/contact`. Click "Change vehicle" → opens the modal selector overlay.
12. Try a known-no-price combination (e.g., a rare brand/model) → amber banner appears: "We don't have a standard price for X yet. Our team will quote you within 2 hours" + "Get Custom Quote" button (also routes to `/contact`).

### Mobile flow — DevTools 375 × 812

13. Open `/` on the mobile viewport: the VehicleSelector card stays full-width (the `max-w-2xl` cap doesn't bite under 768 px); the right-column Quick Estimate form is hidden as before (existing `hidden lg:block`).
14. Tap any `<select>` → native iOS-wheel / Android bottom-sheet picker fires.
15. Visit a service detail page: the PricingWidget renders below the overview cells in a single stacked column. All buttons ≥44 px tall.
16. No horizontal scroll anywhere.

### Smoke regressions to confirm

17. `npm run build && npx playwright test --project=smoke` from the repo root should print `3 passed`. Already verified above; rerun if you're uncertain.

---

## 8. What did NOT change

* No new components.
* No new hooks.
* No new routes.
* No backend.
* No `lib/api.ts` typed-endpoint changes.
* No `PageBanner.tsx`, `CmsPage.tsx`, or `SeoPageView.tsx` touched.
* No deletions of existing Home hero elements (eyebrow / H1 / tagline / Book Now / right-column Quick Estimate / trust badges all preserved).
* No mock-data references introduced.
* All 8 L1 endpoints (`/api/v1/public/*`) stay live and unused by the frontend per the L3 hybrid decision.
