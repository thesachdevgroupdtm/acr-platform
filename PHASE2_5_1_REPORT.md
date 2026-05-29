# Phase 2.5.1 — surgical hotfix bundle

Five issues from Phase 2.5a user testing addressed in one pass. Backend
is touched only to switch cart-merge semantics; everything else is
frontend.

Commit: see "Commit" below.

---

## 1. Files created / modified

### New files (frontend)
| Path | Purpose |
|---|---|
| `src/components/RouteResolutionLoader.tsx` | Render gate placeholder for App.tsx mount. |
| `src/components/VehicleReplaceModal.tsx` | Themed prompt for one-vehicle-per-cart conflict. |
| `src/components/CancelOrderModal.tsx` | Shared themed cancel-booking modal. |
| `src/components/CouponInput.tsx` | Manual coupon input + applied state, used by Cart and Checkout. |
| `src/lib/errors.ts` | `VehicleConflictError` typed domain error + `vehicleLabel` helper. |
| `PHASE2_5_1_REPORT.md` | This report. |

### Modified (frontend)
| Path | Why |
|---|---|
| `src/App.tsx` | `parsePageFromUrl()` + `isRouteResolved` mount gate (PART A). |
| `src/hooks/useCart.ts` | Conflict check in `addItem`; new `replaceVehicleInCart` mutation; `isInCart`/`findCartItem` keyed on (kind, ref_id) only; `applyCoupon`/`removeCoupon` messages updated; `couponCode` field removed from `CheckoutDetails` (with one-time legacy strip on read). |
| `src/pages/ServiceCategory.tsx` | `try/catch` around `addItem`; opens `<VehicleReplaceModal>` on conflict. |
| `src/pages/Services.tsx` | Same. |
| `src/pages/ServiceDetail.tsx` | Same. |
| `src/pages/Cart.tsx` | Removed OFFERS auto-apply + slide-over panel; replaced with `<CouponInput>`. Coupon state now read from `cart.totals.coupon`/`discount`. |
| `src/pages/Checkout.tsx` | Added `<CouponInput>` to right-side order summary; conditional discount line in totals. |
| `src/pages/BookingConfirmation.tsx` | Conditional "Coupon Applied — ₹X" line under Subtotal. |
| `src/pages/MyBookings.tsx` | `<CancelOrderModal>` replaces native `confirm()` + `window.prompt()`. |
| `src/pages/OrderDetail.tsx` | Inline cancel modal extracted to shared `<CancelOrderModal>`. |

### Modified (backend)
| Path | Why |
|---|---|
| `backend/app/Services/Cart/CartMergeService.php` | Tuple-match additive merge → last-cart-wins (PART C). |

---

## 2. PART A — Routing flicker workaround (Issue 1, D-2.5.1-3)

The codebase currently has no URL parsing — `currentPage` is purely
state-driven, defaulting to `"home"`. Hard-refresh on /checkout
served index.html and rendered Home until the user manually navigated
back. Phase 3 will replace this with a real router; today's
workaround:

1. **Minimal `parsePageFromUrl(loc)`** in `src/App.tsx` — maps
   `window.location.pathname` to the `currentPage` string vocabulary
   already in use:
   - `/` and `/home` → `home`
   - `/checkout` → `checkout`
   - `/order/{id}` and `/order-{id}` → `order-{id}`
   - `/booking-confirmation/{id}` → `booking-confirmation-{id}`
   - `/booking-history` → `my-bookings` (alias)
   - everything else → `pathname.slice(1)` (matches existing keys
     like `services`, `service-centers`, `cart`, `my-bookings`, etc.)

2. **Render gate** — new `isRouteResolved` boolean state in App,
   defaulting `false`. `useEffect` on mount runs `parsePageFromUrl`,
   calls `setCurrentPage(initial)` if not Home, then sets the gate
   to `true`. The render branch returns
   `<RouteResolutionLoader />` while `isRouteResolved === false`.

3. **`RouteResolutionLoader`** — centered spinner over a white
   full-viewport, no Header/Footer chrome (the gate is a single
   tick; chrome would itself flash). Shows "Loading" sub-label.

### Before / after render path

```
Before (Phase 2.5a):
  mount → currentPage='home' → Home page renders → user navigates manually

After (Phase 2.5.1):
  mount → currentPage='home', isRouteResolved=false → RouteResolutionLoader
        ↓ useEffect: parsePageFromUrl → setCurrentPage('checkout'), isRouteResolved=true
        ↓ re-render
        Checkout page renders. User never sees Home.
```

Click navigation inside the app continues to use `setCurrentPage`
without URL sync (kept identical to 2.5a). Phase 3 router migration
will own back-button history + URL push.

---

## 3. PART B — One-vehicle-per-cart (Issue 2, D-2.5.1-1)

### `VehicleConflictError`
New typed error in `src/lib/errors.ts`. Carries:
- `existingVehicle` — first vehicle-bearing line in current cart.
- `newVehicle` — vehicle the user is trying to add.
- `pendingItem` — the full `AddCartItemRequest` to replay on confirm.

Helpers: `VehicleSummary`, `vehicleLabel(v)` for human-readable labels
(joined `brand model fuel`).

### `useCart.addItem` change

Before: fire-and-forget, swallowed all errors.

After: returns `Promise<void>` so callers can `await`.
- Computes `newVehicleKey = brand-model-fuel`.
- Looks up the cart's first vehicle-bearing item; if its key differs
  from the new request's, throws `VehicleConflictError` (no API call).
- Otherwise proceeds to `addMutation.mutateAsync(req)` as before.

A request without a vehicle never conflicts (gets base-price
treatment server-side). A cart row without a vehicle never conflicts
either.

### `useCart.replaceVehicleInCart(pendingItem)`

New mutation. On user confirm:
1. Sequential `DELETE /cart/items/{id}` for every existing item
   (no `/cart` wipe endpoint exists yet — Phase 2.3 deviation #5).
2. `POST /cart/items` with the pending request.
3. Invalidates `['cart']` query.

Best-effort on individual delete failures — the next `/cart` fetch
reflects whatever survived.

### `isInCart` / `findCartItem` change

Key narrowed from (kind, ref_id, brand_id, model_id, fuel_id) to
(kind, ref_id) — the vehicle tuple is now constant across all cart
rows so including it created spurious "not in cart" states whenever
the user changed vehicle. Backward-compat: callers may still pass
`brand_id`/`model_id`/`fuel_id` — they're ignored.

### Wiring (3 Add-to-Cart sites)

Each page now wraps the `addItem` call in `try/catch`:

```tsx
try {
  await addItem({ ... });
} catch (err) {
  if (err instanceof VehicleConflictError) {
    setVehicleConflict(err.details);
    return;
  }
}
```

Local state per page: `vehicleConflict: VehicleConflictDetails | null`,
`replacing: boolean`. Modal mounted at the bottom of each page's
JSX:

```tsx
<VehicleReplaceModal
  open={vehicleConflict !== null}
  details={vehicleConflict}
  onConfirm={confirmReplaceVehicle}
  onClose={() => setVehicleConflict(null)}
  pending={replacing}
/>
```

Modal pattern matches `AuthModal`: `fixed inset-0 z-[10000]`,
neutral-900/95 backdrop, white card, X close. Primary button
"Replace Cart" (`btn-ink-primary`), secondary "Keep Existing Cart"
(outline). Buttons disable while the replacement is in flight.

### Audit of other Add-to-Cart sites

`grep addItem|useCart\(\)` across `src/components/` found only
`Header.tsx` reading `count` from cart — not an Add-to-Cart trigger.
`BookingSidebar.tsx` and `EstimateProcess.tsx` do not call `addItem`.
QuickEstimate flow lives inside `EstimateProcess.tsx`; same — no
addItem call. Three callsites total (Services, ServiceCategory,
ServiceDetail), all wired.

---

## 4. PART C — Last-cart-wins merge (Issue 2, D-2.5.1-2)

### `CartMergeService.php` diff (semantics)

Before — additive merge (Phase 2.4):
```
foreach $guestItems as $guestItem:
  if tuple-match in $userCart: bump quantity, delete guest row
  else: reparent guest row (UPDATE cart_id)
```

After — last-cart-wins (Phase 2.5.1):
```
if $guestCart->items()->count() === 0:
  preserve user cart (no-op, just stamp expiries)
else:
  CartItem::where(cart_id=$userCart->id)->delete()  // wipe user
  CartItem::where(cart_id=$guestCart->id)->update(cart_id=$userCart->id)  // reparent guest
```

The previous `tupleKey()` helper is gone (no longer needed). All
guest-item snapshots (price, vehicle, meta) survive untouched —
this is a row-reparent, not a recreate.

Idempotency preserved: re-running after first merge no-ops because
the guest cart is already `status='converted'` (filter at the top).

Logged via `Log::info('Cart merge: last-cart-wins', { … replaced_user_items, moved_guest_items })`.

### Curl regression

```
USER cart 26 seeded with ref_id=1 (Audi Q3 Petrol).
Logout (cart preserved).
GUEST cart 27 created via X-Cart-Session UUID with ref_ids=[2, 3] (different vehicle).
Re-login same phone via /auth/verify-otp + X-Cart-Session header.
GET /cart with new Bearer →
  cart_id=26 is_user_cart=true
  items: ref_id=2, ref_id=3 (vehicle: brand=1/model=1/fuel=2)
  ref_id=1 is GONE.
```

**Expected**: 2 items only, both from guest, prior user item replaced.
**Got**: matched. ✅

---

## 5. PART D — Themed CancelOrderModal (Issue 4, D-2.5.1-4)

### Component preview
- Title: "Cancel Booking?" (with primary-coloured "?" accent).
- Body: "You are about to cancel booking **{orderNumber}**. This action cannot be undone."
- Field: textarea "Reason (optional)" — placeholder "Why are you cancelling? Helps us improve.", `maxLength={255}` (server cap), live char counter (X/255).
- Buttons: "Confirm Cancellation" (danger — `bg-accent-dark text-white`) on the right; "Keep Booking" (outline) on the left.
- Behavior:
  - Reason resets when the modal reopens (defensive against stale text).
  - Submit on Enter does NOT trigger cancel — only the explicit button.
  - Backdrop click ignored while `pending` is true.
  - Optional `errorMessage` slot under the textarea.

### Wiring

**`MyBookings.tsx`** — replaced:
```ts
if (!confirm("Cancel this booking?")) return;
const reason = window.prompt("Reason (optional)") ?? null;
```
with `cancelTarget` state + `<CancelOrderModal>` mounted at root,
opened by per-row click `() => openCancelModal(o)`.

**`OrderDetail.tsx`** — extracted the inline modal block (~50 LOC of
ad-hoc `fixed inset-0 bg-black/50` + textarea + buttons) and
replaced it with `<CancelOrderModal>` reusing the same handler.
Eliminates the visual / styling drift between the two pages.

`MyBookings.tsx`'s separate `confirm("Log out of your account?")`
on the Logout button is left untouched — out of scope (D-2.5.1-4
is specifically about cancel; logout confirmation is a different
ask).

---

## 6. PART E — Coupon UI behavior (Issue 3, D-2.5.1-5)

### Behavior matrix

| Trigger | Before (2.5a) | After (2.5.1) |
|---|---|---|
| Cart loads with items | Best-applicable OFFER auto-applied; "Discount (CODE)" line shown | No auto-apply; subtotal/discount line absent (totals.discount=0) |
| Type code + Apply | Manual code valid → applied locally; written into `acr_checkout_v1.couponCode` | Code calls `useCart.applyCoupon` → returns "Coupon system launching soon — coupons will be available shortly. Please proceed without coupon for now." Friendly inline message; no local state mutated. |
| Coupon currently applied | Slide-over OFFERS panel + per-coupon "Apply this coupon" list | Server cart's `totals.coupon` is the source of truth (always null in 2.5.1 since backend is 501). |
| Remove coupon | Manual button on the auto-applied card | `<CouponInput>` shows Remove × button only when `totals.coupon !== null`. Stub returns "Nothing to remove yet." |
| Persistence Cart→Checkout | LocalStorage `couponCode` field; restored across navigations | Server cart state; persists naturally because the server is the source. (Both pages render the same `<CouponInput>`.) |
| OrderResource.discount > 0 | Order detail showed discount line; BookingConfirmation did NOT | Both pages now show conditional "Coupon Applied — ₹X" line under subtotal. |

### Friendly "coming soon" message

Surfaced by `useCart.applyCoupon` on every Apply click in 2.5.1:

```
Coupon system launching soon — coupons will be available shortly.
Please proceed without coupon for now.
```

Rendered as an info paragraph (not error styling) under the Apply
button so the tone stays soft. The form does NOT mark the coupon
"applied" — the server state stays unchanged.

### `acr_checkout_v1` schema cleanup

`CheckoutDetails.couponCode` removed from the type. `EMPTY_CHECKOUT`
loses the field. `readCheckout()` strips a legacy `couponCode` key
from any prior browser draft on first load (one-time silent
migration; idempotent — second navigation no-ops). Documented inline
so a future cleanup pass can drop the strip safely.

### `OFFERS` constant in `businessData.ts`

Untouched per the constraint. Cart and Checkout no longer import
from it; the constant is now read only by the standalone
`/offers` and `/coupons` marketing pages. Phase 2.6 cleanup will
delete it.

---

## 7. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2165 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-D-lFrV0-.css  106.56 kB │ gzip:  17.47 kB
dist/assets/index-DZ_iuDXu.js   764.19 kB │ gzip: 201.94 kB
✓ built in 14.27s
```

The pre-existing >500 kB chunk warning and the EstimateProcess.tsx
"duplicate case" warning are unchanged by this commit (both predate
Phase 2.5).

---

## 8. Commit

`fix(api,frontend): Phase 2.5.1 — routing flicker workaround pre-Phase-3; one-vehicle-per-cart enforcement with VehicleReplaceModal; cart merge switched to last-cart-wins; themed CancelOrderModal replaces native confirm/prompt; coupon UI behavior corrected (no auto-apply, manual entry, remove option, friendly coming-soon messaging until 2.5b backend lands). Closes 4 issues from 2.5a user testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 9. Deviations

- **PART A: minimal URL parsing added.** The spec wording assumed
  `parsePageFromUrl` already existed; in practice the codebase had
  no URL parsing. I added the minimum mapping so hard-refresh on
  /checkout, /order-12, etc. actually lands on the right page. The
  render gate alone (without parsing) would only hide the Home
  flash but still leave the user on Home. This addition is in
  the same surgical spirit and unblocks the operator's reproduction
  case.
- **No URL push on click navigation.** The router still updates
  `currentPage` state directly; the URL is read-only on mount.
  Phase 3 router migration owns the bidirectional sync.
- **`MyBookings.tsx` Logout `confirm()` left in place.** The spec
  scoped D-2.5.1-4 to cancel; the logout dialog is unrelated and a
  drive-by replacement risks bloating the diff.
- **Coupon Apply does not call POST `/cart/coupon`.** The frontend's
  `applyCoupon` returns the "coming soon" message locally without
  hitting the backend; calling the 501 stub would just churn an
  HTTP roundtrip for the same UX. Phase 2.5b will swap the local
  stub for `postCartCoupon` in one line.
- **`OFFERS` constant not deleted from `businessData.ts`.** Per
  constraint. Phase 2.6 cleanup batch removes it alongside
  `LOCATIONS`, `Payment.tsx`, etc.
- **Cancellation in `MyBookings.tsx` shows the modal even on rows
  that won't be cancellable.** The Cancel button is gated to
  `status==='pending'` already, so the modal can only open for a
  pending order; the backend re-checks anyway.
- **`useCart.addItem` now returns `Promise<void>`.** Existing
  fire-and-forget callers (those not migrated to the conflict
  flow) continue to compile — TS lets you call an
  `async`-returning function without `await`. A swallowed
  `VehicleConflictError` leaves the cart unchanged, which is the
  correct "fail closed" fallback.
