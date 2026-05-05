# Phase 2.6a — Cleanup + migrations + UX polish

## Sections delivered

### 1. Frontend dead-code removal
**Deleted:**
- `src/pages/Payment.tsx` — legacy fake-gateway page, bypassed since Phase 2.5a's real checkout flow.
- `src/pages/BookingsComingSoon.tsx` — only rendered behind `FEATURES.bookingsList`, permanently true since 2.5a.
- `src/pages/CheckoutComingSoon.tsx` — same pattern.

**Stripped:**
- `src/App.tsx`: `Payment` import + `case "payment"` route handler.
- `src/components/Header.tsx`: "₹ Pay Online" link + dead `user.bookings.length` badge on the My Bookings menu item.
- `src/pages/Cart.tsx`: `CheckoutSteps` narrowed from 3 steps to 2; "Payment" step removed.
- `src/pages/Checkout.tsx` + `src/pages/MyBookings.tsx`: removed `FEATURES.checkoutFlow`/`FEATURES.bookingsList` early-return branches.
- `src/hooks/useAuth.ts`: removed `addBooking` callback, `BookingRecord` interface, `AcrUser.bookings` field, and the `bookings: []` line in `presentUser`.
- `src/data/businessData.ts`: removed `OFFERS`, `OfferCoupon` interface, `computeCouponDiscount`, `pickBestOffer`, and `CAR_DATA`. Coupons now flow through `/coupons` API.
- `src/pages/Offers.tsx`: migrated from local `OFFERS` const to `useCoupons("marketing")` hook (matches `/coupons` page pattern).

**Deferred:** `LOCATIONS`, `BUSINESS_INFO`, `TESTIMONIALS` in `businessData.ts` still have 5+ active consumers each (Header, Footer, Home, ServiceCenter pages). Removing them is a multi-page consumer migration; will land in 2.6b/Phase 3.

### 2. Backend FK forward-compat columns
Verified — `cart_items` and `order_items` migrations already declare `package_id` + `product_id` as nullable `unsignedBigInteger` with no FK constraints, both models include them in `$fillable`, and `OrderItemResource` exposes them. No new migrations required.

### 3. ServiceResource vehicle/base/effective price
- `ServiceResource.php`: emits three explicit price fields — `base_price`, `vehicle_price` (null unless a vehicle context was passed AND a `service_prices` row exists), `effective_price` (`vehicle_price ?? base_price`). Legacy `price` retained as alias of `effective_price`.
- `SubServiceResource.php`: same 3-field shape, plus a `withVehiclePrice()` chain method and a transient-property fallback (`Service::$resolvedVehiclePrice`) so list endpoints can bulk-resolve prices.
- `Service.php`: added the `?float $resolvedVehiclePrice = null` transient property — declared as a real PHP property so it does NOT route through Eloquent attributes.
- `ServiceController@index`: bulk-resolves `service_prices` once for the requested vehicle and stamps each Service instance's `resolvedVehiclePrice`. Replaces the frontend's parallel POST `/pricing` query.
- Frontend `Services.tsx` + `ServiceCategory.tsx`: dropped `usePricingFor` calls. `priceMap` is now derived from each sub-service's inline `vehicle_price`. The 4-state machine (no-vehicle / loading / price / no-price) still applies, driven by `servicesQuery.isLoading` (or `detailQuery.isLoading`) instead of a separate pricing query.
- `src/lib/api.ts`: added `vehicle_price` + `effective_price` to `CategorySubService` and `SubService` types.

### 4. Cart::reloadCoupon centralization
Added `Cart::reloadCoupon(float $subtotal): ?array` — single source of truth for "is the applied coupon still valid, and what's its discount on this subtotal." Auto-clears (and persists) `coupon_id` when the referenced coupon has been deactivated or expired. Returns `['coupon', 'discount', 'meta']` or `null`.

`CartService::totalsFor` and `CheckoutService::quote` both replaced their inline coupon-validation+self-heal blocks with a single call to `$cart->reloadCoupon($subtotal)`. Cart and Checkout can no longer disagree on discount math.

### 5. Themed Logout modal
Added `src/components/LogoutConfirmModal.tsx` (matches `CancelOrderModal` shell — same `motion`-driven backdrop/card pattern, primary-toned button instead of danger-toned). Wired into `MyBookings.tsx` replacing the native `confirm("Log out of your account?")` call.

### 6. Centralized 401 session-expired toast
- `src/lib/api.ts`: on 401 from any request (when `allowUnauthorized` is false), dispatches a `acr-session-expired` `CustomEvent` on `window` after wiping the token.
- `src/components/SessionExpiredToast.tsx`: listens for the event, renders a single bottom-right toast that auto-dismisses after 6s (or via the X button). Mounted once at the App root.

## Verification

- `npx tsc --noEmit` → clean (exit 0)
- `npm run build` → ✓ built in 14.03s
- `php artisan migrate` → "Nothing to migrate." (Section 2 columns already in place from prior phases.)
