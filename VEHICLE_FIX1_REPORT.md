# REBUILD-VEHICLE-FIX1 — CarSidebar width parity + Home blank space

Two layout/CSS-only fixes. No component logic, selector flow, redirect rule,
coupon, pricing, auto-add, or data-hook changes. No components rebuilt.

---

## FIX 1 — CarSidebar width parity across the three service pages

**Width difference found** (all three pages use `site-container` + `gap-10 lg:gap-12`):

| Page | Grid wrapper | Main | Sidebar | Sidebar fraction |
|---|---|---|---|---|
| Services (`Services.tsx:232`) | `lg:grid-cols-3` | `lg:col-span-2` | column (`lg:order-2`) | **1/3 (≈33%)** |
| Category (`ServiceCategory.tsx:452`) | `lg:grid-cols-3` | `lg:col-span-2` | column (`lg:order-2`) | **1/3 (≈33%)** |
| ServiceDetail (`ServiceDetail.tsx:418`) | `lg:grid-cols-12` | `lg:col-span-7` | `lg:col-span-5` | **5/12 (≈42%) — wider** |

So ServiceDetail's sidebar rendered ~9% wider than the two that agree.

**Canonical width chosen:** the Services + Category width (1/3) — the two that already
agree, which the brief flags as the intended sidebar width. ServiceDetail aligned to it.

**What changed on ServiceDetail (only):**
- `ServiceDetail.tsx:422` — main `lg:col-span-7` → **`lg:col-span-8`**
- `ServiceDetail.tsx:790` — CarSidebar `className="lg:col-span-5"` → **`lg:col-span-4"`**

**Why this is exact, not approximate:** with the same `gap`, a 12-col grid's
`col-span-4` equals a 3-col grid's `col-span-1`:
`4·track12 + 3·gap = 4·(C−11g)/12 + 3g = (C−2g)/3 = track3`.
Likewise `col-span-8` (main) == `col-span-2` of 3. So ServiceDetail's sidebar is now
**pixel-identical** to Services/Category without touching its `grid-cols-12` (minimal
edit). `CarSidebar` hardcodes no width — its `<aside>` is `hidden lg:block lg:sticky
… ${className}` and the inner card just fills the column — so width is driven entirely
by the page grid, set consistently on all three.

---

## FIX 2 — Home car-selector blank space below the CTA

**Root cause:** the blank space was **inside the card** (not the hero). The
collapsed `HomeCarSelector` card carried `lg:min-h-[520px]` (added in rebuild B-4 to
match the open-selector height), but the collapsed content (headline + location +
select-car + mobile + CHECK PRICES + rating strip) is ~380px, leaving ~140px dead
space under the rating strip on every page load. The hero row (`min-h-[85vh]
lg:min-h-[600px]`, `items-center`) was **not** the source and was left untouched, so
the left column (FLAWLESS RESTORATION heading + stats) is unchanged.

**Fix — Option A (collapse to content):** removed `lg:min-h-[520px]` from the
`HomeCarSelector` outer card div (`HomeCarSelector.tsx`). Height is now reserved
**only when the selector is open**: the in-place `VehicleSelector` carries its own
`h-[520px] max-h-[80vh]`, and its negative-margin wrapper makes the card ≈520px while
open. So:

| State | Card height |
|---|---|
| Collapsed (idle / page load) | **sizes to content** — no dead space |
| Selector open (after user clicks SELECT YOUR CAR) | **~520px** — all 3 steps fit, no clipping |

The card grows only on an explicit user click — far better than a permanent gap on
every load, and the open selector still shows brand/model/fuel without clipping
(internal `flex-1 overflow-y-auto` body).

---

## Screenshots

A GUI screenshot tool isn't available in this environment. Runtime proof: the Vite
dev server was started and **Playwright smoke ran 3/3 green**, including "home page
renders without console errors" — confirming the collapsed Home card and the wired
pages mount cleanly after the CSS changes. Operator to visually confirm scenarios
1-4 (equal sidebar width on the 3 pages; no Home gap collapsed; selector opens
in-place without clipping; cart/coupon/checkout/redirect unchanged).

---

## tsc / build / smoke / backend

- `tsc --noEmit`: clean except the 2 pre-existing `tests/e2e/brand-typography.spec.ts`
  errors (unchanged baseline).
- `vite build`: exit 0 (index chunk 194.49 KB).
- `playwright test --project=smoke`: **3/3 passed** (live dev server).
- Backend untouched. No logic, selector-flow, redirect, coupon, pricing, or
  data-hook changes. No new packages. Files touched: `ServiceDetail.tsx` (2 class
  values) and `HomeCarSelector.tsx` (1 class value).
