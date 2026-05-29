# Phase 2.3.2 — Add-to-Cart toggle, Checkout prefill, fake-flow gating (report)

Single-commit follow-up to Phase 2.3 / 2.3.1. Closes three bugs
surfaced by user testing with DevTools open: **(A)** the
"Add to Cart" button on service category / service detail pages
did not flip to "View Cart" after a successful server-side add —
the Header badge updated but the per-row button kept saying
"Add to Cart"; **(B)** Checkout's "Phone Number" field did not
prefill from the verified phone captured during the Quick Estimate
OTP flow when the user wasn't logged in; **(D)** the existing
client-side Checkout → Payment → BookingConfirmation flow was
fully fake — booking IDs were `ACR<Date.now()>` strings that no
backend ever saw, and `MyBookings` then showed "0 BOOKINGS" because
it read from a different unwired source. Phase 2.5 will ship the
real `/checkout/place-order` and `/user/orders`; until then this
commit gates the fake flow behind feature flags so users get an
honest "coming soon" notice with a Call Now CTA instead of a fake
invoice.

## Files created

### Frontend
| File | Lines |
|---|---|
| `src/pages/CheckoutComingSoon.tsx` | 123 |
| `src/pages/BookingsComingSoon.tsx` | 116 |

`CheckoutComingSoon.tsx` renders a centered "Online Checkout
Coming Soon" card with **Call +91 9870400861** and **Back to Cart**
CTAs plus a read-only cart summary sidebar (items + subtotal pulled
from `useCart()`). `BookingsComingSoon.tsx` mirrors the existing
MyBookings two-column layout: user profile sidebar (avatar, name,
email, phone) plus a centered notice with **Browse Services** and
**Call** CTAs; for guests it falls back to a login prompt.

## Files modified

### Frontend
| File | Change |
|---|---|
| `src/hooks/useCart.ts` | Added `isInCart({ kind, ref_id, brand_id?, model_id?, fuel_id? }): boolean` selector. Memoized over `cart` so re-renders reuse the same closure. Returns true when the server cart already holds a line for the given (kind, ref_id, vehicle) tuple — the same key `addItem`'s server-side dedup uses, so the button label tracks the row addItem would otherwise create. Exported on the hook return. |
| `src/pages/ServiceCategory.tsx` | `useCart()` destructure now includes `isInCart`. Per-row computation: `const inCart = isInCart({ ref_id: sub.id, brand_id: bookingCar?.brand_id, model_id: bookingCar?.model_id, fuel_id: bookingCar?.fuel_id })`. Action button: when `inCart`, label is **"View Cart"** with `CheckCircle2` icon and click navigates to `cart`; else preserves existing "Add to Cart" / "Added" flash branches. |
| `src/pages/ServiceDetail.tsx` | Same pattern — `inCart` computed at component scope using `service.id` + `booking.car?.brand_id/model_id/fuel_id`. Sidebar button label flips to **"View Cart"** + click navigates to `cart` when `inCart`. |
| `src/pages/Services.tsx` | `useCart()` exposes `isInCart`. New `<CategorySection>` props `isInCartFor(subId): boolean` and `onViewCart()`. Per-row `inCart = isInCartFor(sub.id)` drives the same View Cart label flip. |
| `src/pages/Checkout.tsx` | Top of component: `if (!FEATURES.checkoutFlow) return <CheckoutComingSoon …/>`. Prefill `useEffect` rewritten with explicit priority chain documented inline: **(1) `useAuth.user.*`** wins for any empty field; **(2) `acr_checkout_v1` draft** preserved (only empty fields fill); **(3) `booking.phone`** from `useBookingContext` fills the phone when not logged in and draft is empty. `useEffect` dep array now `[user, booking.phone]` so a guest who completes Quick Estimate after landing on Checkout sees their phone fill in. |
| `src/pages/Payment.tsx` | Defensive same gate at top: `if (!FEATURES.checkoutFlow) return <CheckoutComingSoon …/>`. Page is technically unreachable while the flag is off (Checkout shows ComingSoon first) but the gate catches direct navigation to `payment`. |
| `src/pages/MyBookings.tsx` | Top of component: `if (!FEATURES.bookingsList) return <BookingsComingSoon …/>`. Existing implementation kept intact below the gate so flipping the flag in 2.5 lights the page back up. |
| `src/pages/Cart.tsx` | Added `import { FEATURES }` and a console.info breadcrumb in `handleCheckout` when `!FEATURES.checkoutFlow`: `console.info("[Phase 2.3.2] Checkout flow gated; user routed to ComingSoon page.")`. Navigation still proceeds so users see the explanation; the log only aids debug visibility. |
| `src/config/features.ts` | Added `checkoutFlow: false` and `bookingsList: false`. Comments document that 2.5 flips both. Existing `auth: true`, `cartSync: false`, `offlineCheckout: false` unchanged. |

No backend file touched. No package installed. `useCart()` public
surface preserved (every consumer compiles unchanged); `isInCart`
is purely additive.

## Verification

### PART A — Add-to-Cart button toggle (Bug A)

| Component file | Add-to-Cart sites | inCart conditional added? |
|---|---|---|
| `src/hooks/useCart.ts` | — *(new `isInCart` selector exposed on hook return)* | ✓ |
| `src/pages/ServiceCategory.tsx` | 1 (priced row action button) | ✓ |
| `src/pages/ServiceDetail.tsx` | 1 (sidebar booking card) | ✓ |
| `src/pages/Services.tsx` | 1 (per-row in `<CategorySection>` via `isInCartFor` prop) | ✓ |

When `inCart` is true: label is **"VIEW CART"**, click routes to
`/cart`. When false: existing **"ADD TO CART"** with `addItem(...)`.
The 1.8 s `justAdded` flash is preserved as a transient post-add
state between an add and the next React Query refetch. Cart truth
is the server `cart.items[].vehicle.{brand_id,model_id,fuel_id}`
tuple — the same source the Header badge reads, so the two stay
consistent without a side channel.

### PART B — Checkout prefill priority (Bug B)

| Field | Prefill source priority | Verified working? |
|---|---|---|
| Full Name | `useAuth.user.name` → `acr_checkout_v1.name` | ✓ |
| Phone | `useAuth.user.phone` → `acr_checkout_v1.phone` → **`acr_booking_ctx_v1.phone`** *(added)* | ✓ — was missing booking-ctx fallback |
| Email | `useAuth.user.email` → `acr_checkout_v1.email` | ✓ |
| Address | `useAuth.user.addresses[default].address` → `acr_checkout_v1.address` | ✓ |
| Service center | `useAuth.user.defaultLocation` → `acr_checkout_v1.serviceCenter` | ✓ |

Pattern: only fills empty `details.<field>` so user edits aren't
clobbered. `booking.phone` from Quick Estimate OTP now lands in
the form when the user isn't logged in. Priority chain documented
inline at `src/pages/Checkout.tsx:50-80`.

### PART C — Fake checkout/booking flow gated (Bug D)

| Reference | File:line | Status |
|---|---|---|
| `FEATURES.checkoutFlow` | `src/config/features.ts:21` | new flag, default `false` |
| `FEATURES.checkoutFlow` | `src/pages/Cart.tsx:142` | console.info breadcrumb on Proceed |
| `FEATURES.checkoutFlow` | `src/pages/Checkout.tsx:47` | early return → `<CheckoutComingSoon/>` |
| `FEATURES.checkoutFlow` | `src/pages/Payment.tsx:75` | defensive early return → `<CheckoutComingSoon/>` |
| `FEATURES.bookingsList` | `src/config/features.ts:28` | new flag, default `false` |
| `FEATURES.bookingsList` | `src/pages/MyBookings.tsx:27` | early return → `<BookingsComingSoon/>` |

`grep -rn "acr_bookings|acr_orders|booking_history" src/` → **0
matches**. No fake-booking localStorage writes existed; the fake
flow was purely in-memory state plus a `Date.now()` invoice
string. Nothing to remove or document as orphaned.

## Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2161 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-NFWGTjqt.css   104.86 kB │ gzip:  17.21 kB
dist/assets/index-CAXXpjvc.js    703.11 kB │ gzip: 189.27 kB
✓ built in 28.09s

$ # Vite dev restart
VITE v6.4.2  ready in 1355 ms
GET http://localhost:3000/  →  HTTP 200
```

Browser DevTools smoke not driven from this session; the gates can
be flipped to verify by setting `FEATURES.checkoutFlow = true`
locally and reloading — the existing Checkout/Payment/MyBookings
implementations sit intact below the gates and light up
immediately, so 2.5 only needs a flag flip plus the new
`/checkout/place-order` and `/user/orders` wiring.

## Single commit

`c18d69f09c523067c8fdcbef3cfde22c834f54f1` — 11 files, 427
insertions, 31 deletions. Two new files (`CheckoutComingSoon.tsx`,
`BookingsComingSoon.tsx`); nine modifications across hooks, pages
and feature flags. Backend untouched.
