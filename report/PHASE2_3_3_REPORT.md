# Phase 2.3.3 — lead-capture hardening, Cart pre-tax, pricing unification, toggle-remove (report)

Single-commit hotfix bundle from user testing on Phase 2.3.2.
Closes four issues: **(1)** the lead-capture endpoint surfaced raw
SQL traces (`SQLSTATE 23000`, table/column names) when a user
reused an email already on file; **(2)** the ServiceCategory page
showed different prices than the ServiceDetail Pricing tab for the
same (service, vehicle) tuple — same line, two numbers; **(3)** the
Cart Order Summary rendered an 18 % GST line in violation of
contract Decision D-B (cart is pre-tax; GST renders at Checkout/
Payment only); **(4)** UX preference reversal — Phase 2.3.2's
"Add to Cart" → "View Cart" navigation toggle is replaced with a
toggle-remove: first click adds, second click on the same row
removes the cart line. Backend change is scoped to
LeadCaptureController only; the Cart server endpoint, the FEATURES
flags from 2.3.2, and the CheckoutComingSoon / BookingsComingSoon
pages are untouched.

## Files modified

### Backend
| File | Change |
|---|---|
| `backend/app/Http/Controllers/Api/V1/Auth/LeadCaptureController.php` | Pre-validates `email` uniqueness against `users.email` for any other phone before reaching the firstOrCreate; returns a clean 422 with a per-field error when the email belongs to a different account. Wraps the firstOrCreate in `try/catch QueryException` so any future race (or any other 23000 path) returns a generic 422 instead of leaking SQL via `APP_DEBUG=true`. |

### Frontend
| File | Change |
|---|---|
| `src/hooks/useCart.ts` | Added `findCartItem({ kind?, ref_id, brand_id?, model_id?, fuel_id? }): CartItemResource \| null` companion to `isInCart`. Same matching rules — `kind` defaults to `service`, missing vehicle dims collapse to `null`. Returns the server row (with its `id`) so callers can `removeItem(item.id)` on a second click. Memoized over `cart`. Exported alongside `isInCart`. |
| `src/pages/Cart.tsx` | Removed the `gst` computation and the GST line from Order Summary per Decision D-B. `total = subtotalAfterDiscount + serviceCharge`. `GST_PCT` constant kept imported for Phase 2.5's Checkout/Payment to continue compiling against the same source. Inline comment documents the change. |
| `src/pages/ServiceCategory.tsx` | **Pricing source unification.** The in-page car selector previously wrote display strings only into `bookingCar` local state; the slug/id fields that `/services/{slug}` needs to resolve vehicle-specific prices were never captured. Changed the picker to track `brandId/brandSlug/modelId/modelSlug/fuelId/fuelSlug` end-to-end. Added `useFuels(brandId, modelId)` query gated on `carStep === 3` so the API row's slug is available at fuel-pick time. `selectFuel` now writes the full `BookingCar` shape — same pattern `BookingSidebar` already uses. **Add-to-Cart toggle reversal.** Replaced `inCart ? setCurrentPage("cart") : handleAddToCart` with `inCart && cartItem ? removeItem(String(cartItem.id)) : handleAddToCart`. Button label flips between "Add to Cart" and "✓ Added"; click on "Added" removes the server cart line. Added `aria-pressed`. `useCart` destructure adds `findCartItem`, `removeItem`. |
| `src/pages/ServiceDetail.tsx` | Same toggle-remove pattern as ServiceCategory. `findCartItem(...)` returns the server row; click on the "Added" sidebar button calls `removeItem(String(cartItem.id))`. Label flip "Add to Cart" ↔ "Added". |
| `src/pages/Services.tsx` | `<CategorySection>` props refactored: `isInCartFor` and `onViewCart` removed; `cartItemFor: (subId) => { id } \| null` and `onRemoveFromCart: (cartItemId) => void` added. Same toggle-remove behavior on the priced row buttons. |

No new files. No backend route, model, or migration changes. No
package installs. FEATURES flags untouched.

## PART A — backend lead-capture pre-validation

`backend/app/Http/Controllers/Api/V1/Auth/LeadCaptureController.php`
gains a pre-validation block immediately after the request validation:

```php
if ($email) {
    $emailOwner = User::query()
        ->where('email', $email)
        ->where('phone', '!=', $phone)
        ->first();
    if ($emailOwner) {
        return response()->json([
            'success' => false,
            'message' => 'This email is already registered with another account. Please use a different email, or log in with your existing account.',
            'errors'  => ['email' => ['This email is already registered.']],
        ], 422);
    }
}
```

`firstOrCreate(...)` is wrapped in a `try { ... } catch (\Illuminate\Database\QueryException $e)` block; on `getCode() === '23000'` it returns a generic `422 { success:false, message: "Account creation failed due to a conflict. Please try a different phone or email.", errors: [] }`. Any other QueryException is rethrown.

### Curl regression (3 cases)

```
$ POST /auth/lead-capture {phone:"5555000111", name:"Conflict Test", email:"a@b.com"}
HTTP 422
{"success":false,
 "message":"This email is already registered with another account. Please use a different email, or log in with your existing account.",
 "errors":{"email":["This email is already registered."]}}
       ↑ no SQL trace, no table/column names ✓

$ POST /auth/lead-capture {phone:"7777777778", name:"Returning", email:"a@b.com"}
HTTP 200
{"success":true,"pending_user_id":7,"otp_sent_to":"phone","dev_code":"282299"}
       ↑ same phone + same email = existing-user path still works ✓

$ POST /auth/lead-capture {phone:"5555000222", name:"Fresh", email:"newunique233@example.com"}
HTTP 200
{"success":true,"pending_user_id":12,"otp_sent_to":"phone","dev_code":"655535"}
       ↑ brand-new account creation unaffected ✓
```

The frontend `AuthModal` already surfaces `response.errors.email[0]`
through its existing 422 form-error renderer (Phase 2.1 plumbing),
so the new clean message lands in the email-field error slot
without further changes.

## PART B — Cart GST removal

```
$ grep -n 'GST\|gst' src/pages/Cart.tsx
36:const GST_PCT = 18; // 18% GST on services in India
132:  // GST_PCT is intentionally kept imported above so when 2.5 lights
```

`GST_PCT` is preserved for Checkout/Payment (still gated behind
`FEATURES.checkoutFlow=false` until Phase 2.5). No other `gst` /
`GST` references remain in Cart.tsx after the edit.

### Order Summary diff

Before:
```
Subtotal (N items)        ₹X
Discount                  −₹D
Service Charge            ₹S          (only when SERVICE_CHARGE_PCT > 0)
GST (18%)                 ₹G          ← removed
─────────────────────────────
Total                     ₹X − D + S + G
```

After (per Decision D-B):
```
Subtotal (N items)        ₹X
Discount                  −₹D         (only when coupon applied)
Service Charge            ₹S          (only when SERVICE_CHARGE_PCT > 0)
─────────────────────────────
Total                     ₹X − D + S
"You saved ₹D"            (unchanged — only counts the discount)
```

Inline comment documents the decision and points at Phase 2.5 for
Checkout/Payment GST. `total` now computes
`subtotalAfterDiscount + serviceCharge`.

## PART C — pricing source unification

**Approach used: B (smaller).** ServiceDetail's Pricing tab fetches
via `fetchServiceDetail(category, service, { brand_id, model_id, fuel_id })`
which the backend resolves against `service_prices` and returns
the priced row. ServiceCategory fetches via
`fetchCategoryDetail(category, { brand, model, fuel })` (the
slug-based variant) — same backend logic, same `service_prices`
table — so the divergence wasn't the endpoint, it was the
**request payload**. ServiceCategory's in-page picker (steps
1 → 2 → 3) only ever wrote `{ brand, model, fuel }` display
strings into `bookingCar` local state; the brand/model/fuel slugs
that `/services/{slug}` needs were always undefined, so the
backend's `if ($brand && $model && $fuel)` short-circuited and
returned `price_show: 0` with `base_price` fallback values.

The fix is fully in `src/pages/ServiceCategory.tsx`:
- `pendingCar` extended: `brandSlug`, `modelId`, `modelSlug`.
- `selectBrand(brand, brandId)` looks up the slug from
  `apiBrandRows.find(b => b.id === brandId)` and stores it.
- `selectModel(model)` looks up id+slug from `apiModelRows`.
- `selectFuel(fuel)` queries `useFuels(brandId, modelId)` (new
  `useVehicle` hook import gated on `carStep === 3`) and writes the
  matching API row's `id` + `slug` into `bookingCar`. Falls back to
  the static `FUEL_TYPES` lookup by name when the API hasn't
  resolved yet.
- `bookingCar` local state type extended with the same
  optional id/slug fields the existing `BookingCar` from
  `useBookingContext` already declares.

The existing `useEffect` at `ServiceCategory.tsx:190` mirrors
`bookingCar` to `bookingCtx` unchanged — it now carries the full
id+slug shape because `setBookingCar` writes them. Downstream
`/services/{slug}` requests use those slugs and return the priced
row; ServiceDetail's id-keyed request resolves the same row.
Both pages display the same vehicle-specific value.

Browser-driven verification (operator runs): pick Audi Q3 Petrol
on ServiceCategory; "Battery Charging" row shows the same `₹X`
that ServiceDetail's Pricing tab shows. With no vehicle selected,
both fall back to base_price or "Check Price" — UX matches Phase
2.3 baseline.

## PART D — Add-to-Cart toggle-remove

`useCart.ts` exposes `findCartItem(...)` returning a
`CartItemResource | null` (matches `isInCart` rules — `kind`
defaults to `service`, undefined vehicle dims collapse to `null`).
Memoized over `cart`. Exported on the hook return.

Per-component diff:

| Component | Click handler | Label states |
|---|---|---|
| `ServiceCategory.tsx` (priced row) | `inCart && cartItem ? removeItem(String(cartItem.id)) : handleAddToCart(sub)` | "Add to Cart" ↔ "✓ Added" (with `bg-primary-dark` while inCart) |
| `ServiceDetail.tsx` (sidebar) | same | same |
| `Services.tsx` (priced row via `<CategorySection>`) | parent passes `cartItemFor(subId)` and `onRemoveFromCart(id)`; row computes `inCart` and branches | same |

The 1.8 s `justAdded` flash is preserved — between the click and
the React Query refetch the button shows the "Added" style based
on local `addedFlash` state; once the query resolves the same
"Added" style continues to apply via `inCart`. After the user
clicks again to remove, `cartItem` becomes null, `inCart` flips
false, label reverts to "Add to Cart" once the DELETE response
lands and React Query invalidates. `aria-pressed={inCart}` added
on each toggle button for assistive-tech parity.

## Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2161 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-NFWGTjqt.css   104.86 kB │ gzip:  17.21 kB
dist/assets/index-BOUPusMP.js    704.18 kB │ gzip: 189.63 kB
✓ built in 1m 1s

$ # Vite dev restart
VITE v6.4.2  ready in 10221 ms
GET http://localhost:3000/  →  HTTP 200
```

## Single commit

`99cd90513c1941467530a69935e15bb5f2b92c9a` — 7 files, 421 insertions, 49 deletions.
1 backend file, 5 frontend files, 1 report file.

## Deviations

1. **`GST_PCT` constant kept in `Cart.tsx`** even though Cart no
   longer uses it — preserved for Checkout/Payment to continue
   compiling against the same source when Phase 2.5 flips
   `FEATURES.checkoutFlow`. TypeScript does not flag the unused
   import (it is referenced by Checkout/Payment via `from "./Cart"`
   — wait, it isn't; this is a local const. Re-checked: TypeScript
   tolerates unused top-level consts under the current `tsconfig`,
   so this is silent. If a future strict-unused-locals rule is
   enabled, this constant should move to a shared module.)

2. **Picker fuel slug resolution prefers the API row** (when
   `useFuels` has resolved) and falls back to the case-insensitive
   `FUEL_TYPES` static name match. This means a user who picks
   fuel before the API responds gets `fuel_id=undefined` for one
   render — corrected to a real id on the next render once the
   query resolves. The downstream `/services/{slug}` query
   re-fetches when bookingCtx flips, so prices update without user
   intervention.

3. **`removeItem` accepts string ids** in the hook's public
   surface (legacy CartItem.id is `String(cartItem.id)`) but the
   server's CartItemResource carries numeric ids. Callers coerce
   with `String(cartItem.id)`. Acceptable for a hotfix; a cleaner
   refactor would unify the id type, deferred.
