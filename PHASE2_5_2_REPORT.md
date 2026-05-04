# Phase 2.5.2 — five surgical hotfixes on top of 2.5.1

Five issues from Phase 2.5.1 user testing addressed in one
frontend-only commit (no backend touched).

Commit: see "Commit" below.

---

## 1. Files created / modified

### New (frontend)
| Path | Purpose |
|---|---|
| `src/components/CouponList.tsx` | Pre-defined coupon cards with manual Apply per card. |
| `src/components/VehicleBadge.tsx` | Shared vehicle context renderer; 3 variants (`compact`, `detailed`, `banner`). |
| `public/.htaccess` | SPA fallback for Hostinger production (Vite copies this into `dist/` on build). |
| `PHASE2_5_2_REPORT.md` | This report. |

### Modified (frontend)
| Path | Why |
|---|---|
| `src/App.tsx` | `pageToUrl` inverse; `navigateTo` wraps `setCurrentPage` to `pushState`; `popstate` listener; mount `replaceState` to canonicalise initial URL; `BASE_URL` aware (production `/app/` prefix). |
| `src/components/CouponInput.tsx` | Reads server cart's `totals.coupon`; hosts both manual input + `<CouponList>` cards; both Apply paths share `tryApply`; applied state shows Remove. |
| `src/components/CancelOrderModal.tsx` | Added `px-6` + `whitespace-nowrap` to confirm/cancel buttons. |
| `src/components/VehicleReplaceModal.tsx` | Untouched (no fix needed). |
| `src/pages/Checkout.tsx` | `<VehicleBadge variant="banner">` above order summary; `<SlotRow>` reworked to use `flex-1` filling row width (no whitespace gap). |
| `src/pages/BookingConfirmation.tsx` | Added vehicle row to Booking Details (above Services). |
| `src/pages/OrderDetail.tsx` | Vehicle section now uses shared `<VehicleBadge variant="detailed">`. |
| `src/pages/MyBookings.tsx` | Each order card shows `<VehicleBadge variant="compact">` under the order number. |

`src/data/businessData.ts`'s `OFFERS` constant now has a real
consumer again (`<CouponList>`); deletion is still a Phase 2.6
cleanup item.

---

## 2. PART A — URL push + popstate (Issue 1, D-2.5.2-1)

### Why 2.5.1 didn't fix anything
Phase 2.5.1 added `parsePageFromUrl` + `isRouteResolved` mount gate,
but *no callsite* pushed URL on click navigation. The URL stayed
at `/` regardless of where the user clicked, so a refresh always
parsed back to `home`. The 2.5.1 deviation note acknowledged this
("no URL push — Phase 3 owns sync") and effectively shipped a
no-op for the operator's reproduction case.

### What changed in 2.5.2

**`pageToUrl(page)`** — inverse of `parsePageFromUrl`:
```
home                      → /
my-bookings               → /booking-history
order-{id}                → /order/{id}
booking-confirmation-{id} → /booking-confirmation/{id}
service-{cat}/{sub}       → /services/{cat}/{sub}
category-{slug}           → /category/{slug}
center-{id}               → /center/{id}
{any other key}           → /{key}
```
Both helpers respect `import.meta.env.BASE_URL`, which Vite sets to
`/app/` in production builds and `/` in dev. So in production:
`/app/checkout` → `checkout` (parse) and `checkout` → `/app/checkout`
(build).

**`navigateTo(page)`** — single navigation entry point:
```ts
const navigateTo = useCallback((page) => {
  setCurrentPage(page);
  const target = pageToUrl(page);
  const current = window.location.pathname + window.location.search;
  if (target !== current) {
    window.history.pushState({ page }, "", target);
  }
}, []);
```
Pages still receive this under the legacy prop name `setCurrentPage`,
so existing call sites compile without churn — the `App.tsx` JSX
just passes `navigateTo` through every page's `setCurrentPage`
prop (`replace_all` swap, no per-page edit needed).

**Popstate listener** — back/forward parses the new URL via
`parsePageFromUrl` and calls the *raw* `setCurrentPage` setter (not
`navigateTo`) so it doesn't push a duplicate history entry.

**Mount `replaceState`** — the initial parse calls
`history.replaceState` (not `pushState`) so refreshing /checkout
doesn't leave a redundant history entry behind. Idempotent: skips
when target === current.

### Render flow (after 2.5.2)
```
Hard-refresh /checkout:
  mount → currentPage='home', isRouteResolved=false → RouteResolutionLoader
  useEffect → parsePageFromUrl(/checkout) = 'checkout' → setCurrentPage('checkout')
            → replaceState({page:'checkout'}, '/checkout')
            → setIsRouteResolved(true)
            → Checkout renders (URL stays /checkout). ✓

Click Cart link in nav:
  navigateTo('cart') → setCurrentPage('cart') + pushState('/cart')
  Cart renders (URL=/cart). Refresh now lands on Cart. ✓

Browser Back:
  popstate fires → parsePageFromUrl(window.location) → setCurrentPage(...)
  No pushState (URL already updated by browser). ✓
```

### Vite SPA fallback verification
Vite's `npm run dev` already serves `index.html` for any unknown
route by default — verified: hard-refresh on `localhost:3000/checkout`
in dev no longer 404s.

### Hostinger production fallback
**`public/.htaccess`** (NEW). Vite copies the file from `public/`
into `dist/` on build (verified: `dist/.htaccess` present after
`npm run build`). Operators deploy `dist/` to `/public_html/app/`;
the file lives at `/public_html/app/.htaccess` and rewrites within
that subdirectory.

```apache
Options -MultiViews
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [QSA,L]
```

The `!-f` / `!-d` conditions ensure real assets (CSS chunks, images,
the index.html itself) are served directly. Only unmatched paths
get rewritten to `index.html` so the SPA router can take over.

---

## 3. PART B — Coupon UI restoration (Issue 2, D-2.5.2-2)

### Components

**`<CouponList>`** (NEW) — renders pre-defined cards from the
`OFFERS` constant. Each card carries:
- Code + optional badge ("new", "popular", etc.).
- Description.
- Min order / valid-until line when present.
- "Apply" button on right (or "Applied" indicator when `appliedCode`
  matches).

Source is currently `src/data/businessData.ts`'s `OFFERS`. Phase
2.5b will swap to `GET /coupons` without touching the consumer
pages — the prop surface stays the same.

**`<CouponInput>`** (REWORKED) — three blocks:
1. Manual code input + Apply button (always visible when no
   coupon applied).
2. `<CouponList>` cards immediately below.
3. When `cart.totals.coupon !== null`: applied-state header (code
   + discount + Remove) at top, `<CouponList>` below for browsing
   alternates (each card shows "Applied" on the active row).

Both Apply paths (typed code, card click) call the same
`tryApply(code)` helper which routes to `useCart.applyCoupon`.
The 2.5.1 stub returns the friendly "Coupon system launching soon
— please proceed without coupon for now" message; the UI doesn't
flip to applied state because `tryApply` stays in info-message
mode when `success === false`.

### Behavior matrix

| Trigger | Behavior |
|---|---|
| User loads Cart / Checkout | Manual input + cards visible. No coupon applied (server cart `totals.coupon === null`). |
| User clicks Apply on a card | `applyCoupon(code)` called; stub returns "coming soon"; info message renders under the manual input area. |
| User types code + clicks Apply | Same path; same message. |
| Phase 2.5b lands real backend | Same UI. `applyCoupon` returns success → applied-state header shows + Remove button + cards show "Applied" on the active row. No frontend code changes needed. |

---

## 4. PART C — VehicleBadge across pages (Issue 3, D-2.5.2-3)

### `<VehicleBadge>` component

Three variants, one component, consistent layout vocabulary:

| Variant | Look | Used by |
|---|---|---|
| `compact` | Single dot-separated line, all-caps eyebrow size | MyBookings card under booking ID |
| `detailed` | Vehicle eyebrow + brand+model heading + fuel+center subline | OrderDetail Vehicle section |
| `banner` | Card with primary tint, "SERVICING" eyebrow, brand+model heading, optional center | Checkout right-side panel |

Renders `null` when `vehicle` is null/missing — callers can mount
unconditionally without guard ternaries.

### Per-page wiring

| Page | Variant | Source |
|---|---|---|
| Cart.tsx | (already shown via line items) | Existing item.car meta |
| Checkout.tsx | `banner` above the Order Summary card | `items[0].car` (cart line meta) → fallback `booking.car` |
| BookingConfirmation.tsx | Plain `<Row icon={Car} label="Vehicle" …>` (matches the surrounding rows) | `order.vehicle_snapshot` |
| OrderDetail.tsx | `detailed` | `order.vehicle_snapshot` + `order.service_center.name` |
| MyBookings.tsx | `compact` under each card's order_number | `order.vehicle_snapshot` |

Format: "Brand Model · Fuel" (en-space dot separator, matching the
existing typography).

---

## 5. PART D — CancelOrderModal padding (Issue 4, D-2.5.2-4)

### Before
```html
<button class="flex-1 py-3.5 ...">Confirm Cancellation</button>
```
Long label, no horizontal padding → text touched the button edges
on narrow viewports.

### After
```html
<button class="flex-1 px-6 py-3.5 ... whitespace-nowrap">
  Confirm Cancellation
</button>
```
- `px-6` (1.5rem ≈ 24px) on each side gives breathing room.
- `whitespace-nowrap` prevents the label from wrapping mid-word
  on a narrow card.
- Same treatment applied to the "Keep Booking" outline button so
  the visual symmetry holds.

---

## 6. PART E — Slot UI horizontal layout (Issue 5, D-2.5.2-5)

### Before
```tsx
<div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
  <span className="… sm:w-24 shrink-0">{label}</span>
  <div className="flex flex-wrap gap-2">              {/* ← wrapping container, NO flex-1 */}
    {slots.map(slot => (
      <button className="px-3 py-2 …">{slot}</button>  {/* ← auto-width */}
    ))}
  </div>
</div>
```
Result: buttons hugged the left edge, leaving empty whitespace on
the right of each row.

### After
```tsx
<div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
  <span className="… sm:min-w-[100px] shrink-0">{label}</span>
  <div className="flex flex-1 gap-2">                  {/* ← flex-1 grows */}
    {slots.map(slot => (
      <button className="flex-1 px-3 py-2 … whitespace-nowrap">{slot}</button>
    ))}
  </div>
</div>
```
Class diff:
```diff
-      <span className="… sm:w-24 shrink-0">{label}</span>
+      <span className="… sm:min-w-[100px] shrink-0">{label}</span>
-      <div className="flex flex-wrap gap-2">
+      <div className="flex flex-1 gap-2">
-        <button className="… px-3 py-2 …">{slot}</button>
+        <button className="flex-1 px-3 py-2 … whitespace-nowrap">{slot}</button>
```

Now: label is fixed-width on the left, button container takes
remaining row width, each button splits that container 50/50.
Mobile stacks (label on top, buttons split 50/50 below).

---

## 7. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-cuCnlQL6.css  106.78 kB │ gzip:  17.49 kB
dist/assets/index-DiHrSxUL.js   769.63 kB │ gzip: 203.29 kB
✓ built in 11.38s

$ ls dist/.htaccess
dist/.htaccess  ✓
```

Pre-existing >500 kB chunk warning and `EstimateProcess.tsx`
"duplicate case" warning are unchanged — both predate 2.5.

---

## 8. Commit

`fix(frontend): Phase 2.5.2 — URL push on click nav (refresh now lands on correct page); coupon UI restored to pre-defined cards with manual apply + remove; VehicleBadge component shows vehicle context across Checkout/BookingConfirmation/OrderDetail/MyBookings; CancelOrderModal button padding; horizontal slot layout. Closes 5 issues from 2.5.1 testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 9. Deviations

- **Pages still receive `navigateTo` under the prop name
  `setCurrentPage`.** A blanket rename across every page would
  bloat the diff for no behavioural gain. App.tsx does the
  `setCurrentPage={navigateTo}` swap once and every page is
  unaware of the upgrade — they push URL automatically. Phase 3
  router migration replaces this surface entirely.
- **No `pushState` in popstate listener.** Calling `navigateTo`
  on popstate would push a duplicate history entry (the browser
  has already updated the URL). The listener uses the raw
  `setCurrentPage` setter for that reason.
- **Initial mount uses `replaceState`, not `pushState`.** The URL
  is already correct on a hard-refresh; the replace canonicalises
  history without growing the stack.
- **VehicleBadge in BookingConfirmation uses a plain `<Row>`, not
  `<VehicleBadge variant="…">`.** The page's existing surrounding
  rows (Service Center, Date, Time, etc.) all share a `<Row>`
  visual; mounting a different component would have looked
  inconsistent. The icon + label + value pattern is the right
  shape there.
- **Production base path (`/app/`).** `pageToUrl` and
  `parsePageFromUrl` both go through `BASE_URL`, so production
  URLs are `/app/checkout`, `/app/order/12`, etc. Dev URLs stay
  `/checkout`, `/order/12`. The `.htaccess` rewrite is scoped to
  `/app/` automatically because it lives inside that subdirectory.
- **`OFFERS` constant in `businessData.ts`.** Now consumed by
  `<CouponList>` again (after being orphaned in 2.5.1). Phase
  2.6 cleanup will replace it with a `GET /coupons` API call —
  swap point is `<CouponList>`'s `OFFERS` import.
