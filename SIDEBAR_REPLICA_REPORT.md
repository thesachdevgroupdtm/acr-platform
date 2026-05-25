# SIDEBAR_REPLICA — Service-page vehicle sidebar as a GoMechanic visual replica

The service-page sidebar (`CarSidebar`, on Services / Category / ServiceDetail,
desktop + mobile) now mirrors GoMechanic for **both** states, optimizing the
**existing** components — **no new files**.

- **STATE 1 (no car)** — the brand picker is embedded **inline** (the existing
  `VehicleSelector` step 1: "Select manufacturer" + search + dense ~80px 3-col
  brand grid). The old car-icon + **"Select Vehicle"** button intermediate
  screen is gone.
- **STATE 2 (car picked)** — a large **model photo** (`hero_image_url`) centered
  at top; below it a thin **navy accent bar** + **Model name** (bold) · **fuel**
  (grey) on the left, **CHANGE** (ACR Blue) on the right; **LUXURY badge** in the
  top-right corner. The **"Genuine OEM · 6-mo warranty"** line is **removed**.

**tsc clean (2 pre-existing only) · vite build clean · smoke 3/3 · both states
verified headless on `/services`.** No selector-flow / API / booking-context /
pricing changes.

---

## 1. Audit (PART A)

| Item | Finding |
|---|---|
| Sidebar component | `src/components/car-sidebar/CarSidebar.tsx` — desktop `<aside>` and `MobileShell` render the **same `body`**, so one change covers both. |
| STATE 1 before | car icon + "Select your car to see accurate pricing" + blue **"Select Vehicle"** button (extra click). |
| STATE 2 before | small `Car` **icon** + "Brand Model · Fuel" + segment badge + blue CHANGE, then a **"Genuine OEM · 6-mo warranty"** trust strip. |
| Reused brand grid | `VehicleSelector` (already embedded when `selectorOpen`) — step 1 is exactly the GoMechanic brand picker (search + ~80px 3-col grid) and drives the full brand→model→fuel flow. |
| Model photo source | `state.car` has no image and booking context is locked → derived from the existing **`useModels(brand_id)`** cache (same data the selector fetched on pick), find by `model_id` → `image` (`hero_image_url`). |
| LUXURY badge | `car.segment` (e.g. "Luxury"). |

---

## 2. STATE 1 — inline brand grid (PART B / D-SIDE-1,2)

The no-car state **is** the selector now:
```ts
const showSelector = selectorOpen || !hasVehicle;   // no car → show the picker
```
- Renders the existing `<VehicleSelector>` (no new grid built) → "Select
  manufacturer" heading + search + brand grid + full flow. Picking a brand
  advances to model→fuel exactly as before.
- The car-icon + **"Select Vehicle"** button + "Select your car…" placeholder are
  **deleted**.
- Added a small **presentational** prop `canClose` (default `true`) to
  `VehicleSelector`: when `false`, the step-1 "X" is hidden (nothing to close
  back to). Sidebar passes `canClose={hasVehicle}` → no "X" in the no-car state;
  on **CHANGE** (car present) the "X" returns so the user can back out to the
  summary. Within-flow back arrows are untouched. (`HomeCarSelector` doesn't pass
  it → unchanged.)

---

## 3. STATE 2 — photo + layout + badge + warranty removed (PART C / D-SIDE-3,4,5)

```
[ ............................... LUXURY ]   ← badge, absolute top-right
            ┌──────────────┐
            │  model photo │   ← hero_image_url, centered, ~140×180, object-contain
            └──────────────┘
 ▌ Audi A3 · Diesel ................. CHANGE   ← navy bar + name(bold)+fuel(grey) | CHANGE (ACR Blue)
```
- **Photo** — `useModels(car.brand_id)` → model `image`, `max-h-[140px]
  max-w-[180px] object-contain`, centered in a 140px row. **Fallback (D-SIDE-3):**
  if `image` is null, the existing `Car` silhouette icon at ~92px (same icon the
  sidebar already used) renders in its place.
- **Accent bar** — `w-1 self-stretch rounded-full bg-[#0E2A5C]` (Navy) left of the name.
- **Name** `text-neutral-900` (#111) bold · **fuel** `text-neutral-500` (steel grey) inline.
- **CHANGE** `text-primary` (ACR Blue #1F4FA3), right-aligned; reopens the selector.
- **LUXURY badge** — `absolute top-0 right-0` ACR badge (`bg-primary/10 text-primary border-primary/30`), kept, realigned to the corner.
- **REMOVED** the "Genuine OEM · 6-mo warranty" trust-strip entirely.
- The "Go ahead and book a service" / Browse-Services block + cart/coupon/checkout below are **unchanged**.

### Card chrome (D-SIDE-6)
Desktop card `shadow-xl` → **`rounded-xl shadow-sm`** (12px radius, subtle shadow),
1px `border-border` kept. Padding/density unchanged.

---

## 4. Components reused — NO new files

| Reused | How |
|---|---|
| `VehicleSelector` (+ `BrandGrid`/`ModelGrid`/`FuelGrid`) | Rendered inline as STATE 1; one additive `canClose` prop for chrome. |
| `useModels(brandId)` hook | Source of the STATE 2 model photo (cache hit from the pick). |
| `Car` (lucide) icon | STATE 2 photo fallback (already imported). |
| Existing ACR badge / `text-primary` / `border-border` tokens | Badge, CHANGE, accent, card. |

Files touched: **`CarSidebar.tsx`** (states + photo + warranty removal + card) and
**`VehicleSelector.tsx`** (additive `canClose` prop). No files created.

---

## 5. ACR colors confirmed (D-SIDE-5)

| Element | Color |
|---|---|
| CHANGE | `text-primary` = **ACR Blue #1F4FA3** (not GoMechanic red) |
| Accent bar | `bg-[#0E2A5C]` = **Navy** |
| Name | `text-neutral-900` (#111) |
| Fuel | `text-neutral-500` (steel grey ≈ #5F6368) |
| LUXURY badge | existing ACR badge style (`bg-primary/10 text-primary border-primary/30`) |

---

## 6. tsc / build / smoke (PART D)

| Check | Result |
|---|---|
| `npx tsc --noEmit` | only the **2 pre-existing** `brand-typography.spec.ts` errors |
| `npx vite build` | **clean** (exit 0) |
| `npx playwright test --project=smoke` | **3/3 passed** |

---

## 7. Side-by-side vs GoMechanic (headless on `/services`, 1366×900)

| # | Scenario | Result |
|---|---|---|
| a | No car → brand grid directly | **32 brand cells** render inline; no "Select Vehicle" button, no icon placeholder; step-1 "X" hidden ✓ |
| b | Pick brand → model → fuel | flow works → Audi A3 · Diesel, `entry_mode=structured` ✓ |
| c | Car → large model PHOTO centered top | `<img src=…/storage/entity-images/models/a3.png>` ✓ |
| d | Name + fuel + CHANGE aligned | name bold + fuel grey + CHANGE (ACR Blue) right, navy accent bar ✓ |
| e | LUXURY badge top-right | segment "Luxury" badge shown in corner ✓ |
| f | No genuine-oem / warranty line | "Genuine OEM" present=false, "6-mo warranty" present=false ✓ |
| g | CHANGE reopens selector | "Select manufacturer" visible again; "X" available to back out ✓ |
| h | Photo fallback | conditional `image ? <img> : <Car icon>` — A3 has an image; fallback path is the existing silhouette (code-verified) |
| i | Pricing/booking unaffected | hasVehicle gating, add-to-cart, coupon, checkout untouched ✓ |

---

## 8. Deviations

1. **Model photo via `useModels(brand_id)`**, not a new booking-context field —
   booking context is locked by the constraints. It hits the React-Query cache
   populated when the car was picked (instant in the common case); on a cold page
   load it fetches once (5-min stale), showing the `Car` fallback meanwhile.
2. **`canClose` prop added to `VehicleSelector`** — purely presentational
   (hides the step-1 "X" when the selector *is* the no-car state). Not a flow
   change; default `true` preserves all existing callers.
3. **Fuel grey** uses the component's established `text-neutral-500` token
   (≈ #5F6368) for consistency with the rest of the app rather than a one-off hex.
4. **Accent bar** uses `bg-[#0E2A5C]` (Navy) per D-SIDE-5's first option.

## Constraints honoured

No new components/files · selector flow / APIs / booking context / pricing
unchanged · services list & browse block not redesigned · GENUINE OEM + warranty
removed · LUXURY badge kept (realigned) · model `hero_image_url` used · ACR colors
only · no packages installed · tsc 2 pre-existing only · smoke 3/3 · git left to
operator (D-SIDE-7).
