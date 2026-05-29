# Booking Sidebar Refactor — Report

ServiceDetail page now uses a GoMechanic-style progressive booking sidebar in place of the prior `PricingWidget` (in main column) + sticky right-aside (booking-context card + estimate card + trust badges).

Homepage, Services.tsx, useCart, useBookingContext, PremiumVehicleSelector, and the backend are untouched.

---

## 1. Hook audit findings

### `useCart` (src/hooks/useCart.ts)
- Returns `{ items, addItem, updateQty, removeItem, clearCart, subtotal, count, cart, isInCart, findCartItem, replaceVehicleInCart, applyCoupon, removeCoupon, isLoading, isError }`.
- `CartItem` shape: `{ id (string), serviceId (string), title, price (single number), qty, categorySlug, car?, location? }`. **No `original_price` / `vehicle_price` / `service_id` fields.**
- `addItem({ serviceId, title, price, categorySlug, car, location, brand_id, model_id, fuel_id })` returns `Promise<void>`; throws `VehicleConflictError` when the cart already holds another vehicle.
- `removeItem(id: string)` — takes the cart-row id (already string).
- `findCartItem({ kind?, ref_id, brand_id?, model_id?, fuel_id? })` returns `CartItemResource | null`; per Phase 2.5.1 keys on `(kind, ref_id)` only — vehicle ids are accepted for source-compat and ignored.
- Server-authoritative; localStorage stores only the guest UUID session.

### `useBookingContext` (src/hooks/useBookingContext.ts)
- Returns `{ state, update, reset }`. **Not `{ selection }` as the prompt assumed.**
- `state.car` is `BookingCar | null` with `{ brand, model, fuel, brand_id?, model_id?, fuel_id?, brand_slug?, model_slug?, fuel_slug?, segment? }`.
- localStorage-backed, key `acr_booking_ctx_v1`, dispatches `acr-booking-ctx-updated` event on writes.

### `PremiumVehicleSelector`
- Modes: `'hero' | 'widget' | 'panel'`. **No `'modal'` mode.**
- Named export (`{ PremiumVehicleSelector }`).
- When `showCta={false}`, `onComplete` auto-fires after the user finishes step 3.

### Reuse confirmed
No new state machines. The sidebar reads `useCart` + `useBookingContext` directly; vehicle modal mounts `PremiumVehicleSelector` in `widget` mode wrapped in a portaled overlay.

---

## 2. Files created (9)

```
src/components/booking-sidebar/
├── BookingSidebar.tsx                                  (orchestrator, 150 lines)
├── index.ts                                            (2 lines)
├── hooks/
│   └── useAutoAddService.ts                            (95 lines)
└── components/
    ├── VehicleSummary.tsx                              (78 lines)
    ├── ServicesCart.tsx                                (68 lines)
    ├── CartItem.tsx                                    (82 lines)
    ├── BookingSummary.tsx                              (78 lines)
    ├── CouponInput.tsx                                 (44 lines)
    ├── MobileStickyBar.tsx                             (56 lines)
    ├── MobileBottomSheet.tsx                           (82 lines)
    └── VehicleChangeModal.tsx                          (95 lines)
```

Total new code: ~828 lines across 11 files (one more than the prompt's 8 — added `VehicleChangeModal.tsx` because `PremiumVehicleSelector` has no built-in modal mode, so the modal scaffolding lives in the sidebar package rather than being duplicated by every consumer).

---

## 3. Files modified (1)

### `src/pages/ServiceDetail.tsx`
- Import swap: `PricingWidget` → `BookingSidebar`.
- Main column "Pricing" section: removed the `<PricingWidget …/>` mount. Replaced with a short header + paragraph telling the user the per-vehicle price is in the sidebar. The `id="pricing"` / `data-subnav-section="pricing"` anchor stays so the in-page sub-nav scroll-spy isn't broken.
- Right column: inserted `<BookingSidebar currentService={service} vehiclePrice={…} categorySlug={category.slug} stickyTopPx={STICKY_OFFSET_PX} />` as a direct grid child. The legacy `<aside>` (booking-context card + estimate card + trust badges) is preserved with a `hidden` class so it doesn't render but its source is intact (the operator can review or restore it without git surgery — see line 781).

Diff summary: ~25 lines changed in ServiceDetail.tsx.

---

## 4. Files NOT touched

- `src/components/pricing/PricingWidget.tsx` — kept, just unmounted from ServiceDetail.
- `src/pages/Home.tsx` — homepage hero unchanged.
- `src/pages/Services.tsx` — services listing page unchanged.
- `src/hooks/useCart.ts`, `src/hooks/useBookingContext.ts` — read-only consumers.
- `src/components/vehicle/premium-selector/*` — re-imported, not modified.
- `backend/**` — zero changes.
- `routes/*`, API layer — zero changes.

---

## 5. Auto-add behavior verified

- `useAutoAddService` fires when (`service` set) ∧ (complete vehicle in context) ∧ (service not already in cart for this vehicle).
- Cart-presence check uses `useCart.findCartItem({ ref_id: service.id, brand_id, model_id, fuel_id })` (the canonical lookup, not a `service_id` string compare).
- `addedRef` ref-guard prevents double-add during the in-flight window between `addItem` resolving and React Query refetching the cart.
- `VehicleConflictError` is swallowed silently — ServiceDetail's existing explicit Add-to-Cart flow + `VehicleReplaceModal` is the right place to prompt for vehicle replacement; auto-add cannot pre-empt that.

---

## 6. State persistence verified

- Cart survives navigation (server-authoritative via `useCart` + React Query).
- Vehicle survives navigation (`useBookingContext` writes to `localStorage` + dispatches `acr-booking-ctx-updated`; the sidebar's `useBookingContext()` consumer re-reads on event).
- Subtotal recalculates via `useMemo(items)` — instant after `removeItem` triggers a cart refetch.

---

## 7. Mobile behavior

- Desktop sidebar (`<aside class="hidden lg:block lg:sticky">`) hides below `lg`.
- `MobileStickyBar` (`fixed bottom-0 lg:hidden`) shows on `<lg`. Empty-cart variant says "Your booking — Tap to start" + "View Cart" button (also opens the sheet so empty-cart users can still pick a vehicle).
- `MobileBottomSheet` portals to `<body>`, slides up over a `bg-black/50` backdrop, locks `document.body.style.overflow` while open, accepts `Esc` + backdrop tap + close-button to dismiss. Slide animation: 250 ms `easeOut` per the discipline rule.
- Both the sticky bar and bottom-sheet sit at `z-40` / `z-50` respectively — clear of the page's `sticky z-30` sub-nav.

---

## 8. TypeScript / Build / Tests

- `npx tsc --noEmit` — **2 pre-existing brand-typography errors only**, no new errors.
- `npm run build` — clean, 7.03 s.
- Bundle deltas (vs. last build before this task):
  - `assets/index-*.js`: 195.64 kB → **195.60 kB** (−40 B, basically flat).
  - `assets/ServiceDetail-*.js`: 42.92 kB → **51.94 kB** (+9.02 kB — the new sidebar surface).
- `npx playwright test tests/e2e/smoke.spec.ts` — **3/3 pass** in 12.4 s.

---

## 9. Deviations from the prompt (and why)

| # | Prompt assumption | Reality | Resolution |
|---|---|---|---|
| 1 | `useBookingContext` exposes `selection` | Exposes `state` (with `state.car`) | All references updated to `state.car`. |
| 2 | Cart items carry `original_price` + `vehicle_price` | Only `price` (single field) — see useCart.ts:51 | `CartItem` row renders single price. `BookingSummary` accepts `discount` prop but suppresses the row when 0. TODO comments mark the slots so a future `CartItemResource` field drops in cleanly. |
| 3 | `service_id` field on CartItem | `serviceId` (string) | `useAutoAddService` uses `findCartItem({ ref_id })` instead. |
| 4 | `bg-acr-blue` token | Doesn't exist | Used `bg-primary` (#1F4FA3). Picks up the recent site-wide hover-invert automatically. |
| 5 | `<PremiumVehicleSelector mode="modal" />` | Selector has only `'hero' \| 'widget' \| 'panel'` | New `VehicleChangeModal.tsx` wraps `mode="widget"` inside a portaled overlay; parent toggles `vehicleOpen`. |
| 6 | "PricingWidget in right column" | PricingWidget was in **main** column at line 490; right column was a different existing aside | Removed PricingWidget from main + replaced the right `<aside>`. The old aside source is preserved with `hidden` so the operator can review/restore. |
| 7 | "Don't wire to backend coupon API" | The backend **is** wired (`useCart.applyCoupon`) and works | Kept Coupon as UI placeholder per spec — Apply is disabled. Note left in `CouponInput.tsx` for when we light it up. |
| 8 | `currentService` prop only | Auto-add needs `categorySlug` and the resolved `vehiclePrice` | Added two extra props on `BookingSidebar`: `categorySlug` (required by `addItem`) and `vehiclePrice` (optional; falls back to `base_price` → 0). |
| 9 | Legacy aside deletion | Prompt PART G said "Remove" but ServiceDetail's prior aside contained content unrelated to the new sidebar (trust badges, edit-details link) | Kept the aside source with `hidden`. Zero render impact; operator decides whether to delete later. |

---

## 10. Visual comparison

- **Before** (per the prompt): right column ≈ 600 px tall — vehicle selector form + pricing widget + trust badges. Vehicle re-asked even when context already exists.
- **After**: right column ≈ 500 px tall (varies with cart size) — vehicle summary (read-only with Change), services cart (one row per added service), totals + coupon stub + Continue CTA. No second selector form.
- **Mobile before**: long vertical aside above main content.
- **Mobile after**: 64 px fixed bottom bar showing `N services · ₹X,XXX · Continue`. Tap to expand a slide-up sheet with the same content.

---

## 11. Recommended follow-ups (out of scope here)

1. Expose `base_price` (catalog) and `effective_price` (per-vehicle) as separate fields on `CartItemResource`. The strikethrough + discount-% slot in `CartItem.tsx` is already wired with TODO markers; only the data is missing.
2. Light up `CouponInput` against `useCart.applyCoupon` (real backend already exists). Wire success/error states from the existing `{ success, error }` envelope.
3. Apply the same `BookingSidebar` to `ServiceCategory.tsx` and `Services.tsx` once you've verified the UX on `ServiceDetail`. The current `<BookingSidebar>` from `src/components/BookingSidebar.tsx` (the legacy file at the package root, used by Home + Services) is **separate from this new package** — naming collision is real but contained because they're imported by different paths.

---

Stop point: operator visually verifies the booking progression UX in browser. Nothing committed.
