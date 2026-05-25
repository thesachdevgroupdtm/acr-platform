# SIDEBAR_REPLICA_FIX — exact GoMechanic replica (corners / photo / density / checkout)

Corrected the prior pass's divergences against the GoMechanic reference: round
corners → **square**, small photo → **large (~61% card width)**, loose →
**compact**, and the cart-grows-pushes-checkout-off-screen bug. Existing
components only — **no new files**.

**tsc clean (2 pre-existing only) · vite build clean · smoke 3/3 · measured
headless on `/services`.**

---

## 1. Audit — current vs reference (measured)

| Element | Was | Now | Reference |
|---|---|---|---|
| Card corners | `rounded-xl` (12px) | **`rounded-none`** → measured **0px** | square/boxy ✓ |
| Car photo | `max-h-140 max-w-180` in fixed `h-[140px]` box | **`w-full max-w-[240px] max-h-[180px]`** → measured **240px wide = 61% of the 395px card** | large, ~60-65% ✓ |
| Card padding | `p-5 sm:p-6` (20/24px) | **`p-4`** (16px) | compact ✓ |
| Section gaps | `space-y-4` (16px) | **`space-y-3`** (12px) | compact ✓ |
| Photo→name gap | `mb-3` | **`mb-2`** | compact ✓ |
| Accent bar | `w-1 rounded-full` pill | **`w-1` `rounded-none`** → measured **0px** | sharp rectangle ✓ |
| Fallback icon | 92px | **120px** | matches larger photo |
| Brand/Model/Fuel cells + search + skeletons | `rounded-lg` | **`rounded-none`** | square grid ✓ |
| Cart items list | unbounded → checkout pushed off-screen | **`max-h-[220px] overflow-y-auto`** | reachable ✓ |
| Card shadow | `shadow-sm` | `shadow-sm` (kept; minimal) | light ✓ |

Checkout-off-screen cause: the items list had no height bound, so a growing cart
made the sticky card taller than the viewport, dropping the summary/checkout below
the fold until you scrolled to the page footer.

---

## 2. Sharp corners (PART B / D-FIX-1)

- **Card** (`CarSidebar` desktop): `rounded-xl` removed → square (measured `0px`).
- **Accent bar**: `rounded-full` → `rounded-none` (measured `0px`).
- **Badge**: already square (no radius) — kept.
- **Brand/Model/Fuel grid cells, search inputs, loading skeletons**: `rounded-lg`
  → `rounded-none` (the shared selector is now boxy in both STATE 1 and the home
  selector — consistent, see Deviations).

## 3. Photo enlarged (PART B / D-FIX-2)

`max-h-[140px] max-w-[180px]` (in a fixed 140px box) → **`w-full max-w-[240px]
max-h-[180px] object-contain`**, centered, fixed-height box removed. Measured
**240px** rendered width on a **395px** card = **61%** (target 60-65%). Fallback
silhouette enlarged 92→120px.

## 4. Compact spacing (PART C / D-FIX-3)

Card padding `p-5 sm:p-6` → **`p-4`**; vertical rhythm `space-y-4` → **`space-y-3`**;
photo→name `mb-3` → **`mb-2`**; name-row gap `gap-2.5` → **`gap-2`**. Dead space
between photo, name row and content tightened.

## 5. STATE 2 layout exact (D-FIX-4)

Square card → large centered photo (top) → row: **▌navy accent bar** + **Model
name** (bold `text-neutral-900` #111) **· fuel** (`text-neutral-500` grey) on the
left, **CHANGE** (`text-primary`, ACR Blue, right) → **LUXURY badge** square,
`absolute top-0 right-0`. The **GENUINE OEM · warranty** line stays **removed**.
STATE 1 (D-FIX-5) reuses the now-square dense brand grid.

## 6. Checkout reachability (PART D / D-FIX-6)

**Chosen: option B** — cap the cart-items list (`max-h-[220px] overflow-y-auto`)
so it scrolls internally instead of growing the card. Cleaner than a sticky button
here (the card uses `overflow-hidden` for the embedded selector, which would defeat
a nested `position: sticky`). Measured with 6 services added:
- list `max-height: 220px`, content scrolls internally (`scrollHeight > clientHeight`);
- **Checkout button bottom = 841px ≤ 900 viewport → visible at scroll-top AND after scrolling** (previously pushed below the fold).
Mobile already has an always-visible checkout bar (`MobileShell`), unaffected.

## 7. Components reused — NO new files

`CarSidebar.tsx` (card + STATE 2 + cart cap), `VehicleSelector` + `BrandGrid` +
`ModelGrid` + `FuelGrid` (squared corners). **No files created.** No changes to
selector flow, APIs, booking context, pricing, or cart logic.

## 8. tsc / build / smoke (PART E)

| Check | Result |
|---|---|
| `npx tsc --noEmit` | only the **2 pre-existing** `brand-typography.spec.ts` errors |
| `npx vite build` | **clean** (exit 0) |
| `npx playwright test --project=smoke` | **3/3 passed** |

## 9. Side-by-side closeness (headless measurements on `/services`)

| Check | Result |
|---|---|
| a. Sharp/square corners | card `border-radius: 0px`, accent bar `0px` ✓ |
| b. Large photo centered | **240px = 61%** of the 395px card, `object-contain` ✓ |
| c. Compact | `p-4` + `space-y-3` + `mb-2`, no fixed-height photo box ✓ |
| d. Name+fuel+CHANGE row + accent bar + LUXURY top-right | aligned, navy bar, badge corner ✓ |
| e. No warranty line | absent ✓ |
| f. Cart → checkout reachable | 6 items → list scrolls internally, checkout bottom **841px ≤ 900** ✓ |
| g. ACR colors | CHANGE `text-primary` (#1F4FA3), accent `#0E2A5C` navy, ACR badge ✓ |
| h. Pricing/cart/selection | logic untouched (Audi A3 · Diesel, structured pick, add/remove works) ✓ |

---

## 10. Deviations

1. **Shared grids squared** — `BrandGrid`/`ModelGrid`/`FuelGrid` are the same
   components the **home** selector uses; squaring their corners (required for the
   sidebar STATE 1 per D-FIX-5, reuse-only) also makes the home selector boxy.
   This is consistent with the GoMechanic boxy theme; no new file was an option.
2. **Cart list cap = 220px** (not 300px) — tuned down so the checkout button
   clears a 900px viewport at scroll-top (300px left it 22px below the fold).
   Shows ~5-6 rows before internal scroll.
3. **`shadow-sm` kept** on the card — "minimal/no heavy shadow"; a hairline shadow
   reads cleaner than none against the page; border is the primary edge.
4. **Photo width via `w-full max-w-[240px]`** rather than a raw percentage, so it
   stays ~61-64% across the (consistent) `lg:grid-cols-3` sidebar column and never
   overflows on a narrower card.

## Constraints honoured

No new files · selector flow / APIs / booking context / pricing / cart logic
unchanged · cart not redesigned (only list-height cap) · sharp corners · large
photo (~61%) · compact · ACR colors · no packages · tsc 2 pre-existing only ·
smoke 3/3 · git left to operator (D-FIX-9).
