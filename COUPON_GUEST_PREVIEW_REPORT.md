# COUPON_GUEST_PREVIEW — guests can apply a coupon & see the discount; checkout stays gated

**Goal:** a not-signed-in visitor can apply a coupon in the service-page sidebar
and immediately see the discounted total (honest, transparent pricing). Sign-in
is still required at checkout/booking. Coupon validity is still enforced by the
existing logic. No coupon-logic rewrite, no new files, no pricing-calc change.

**Result:** the apply gate was a single piece of **backend route middleware**
(`auth:sanctum` on `POST /cart/coupon`), not frontend code. Removing it — plus
two small supporting changes — delivers guest preview while keeping the per-user
usage limit secure and carrying the coupon through login into checkout.

`tsc` → **2 pre-existing only** · `npm run build` → **clean** · Playwright smoke →
**3/3** · backend suite → **288 passed** (incl. 3 new guest/merge tests).

---

## 1. Audit — where the gate lived, how validation works, where checkout is gated

I grepped `src/` for the apply flow and the "sign to apply" message. **The message
is not in `src/`** — the frontend coupon UI has **no client-side auth gate**:

| Layer | File | Finding |
|---|---|---|
| Sidebar coupon UI | `src/components/CouponInput.tsx` | Reads `applied = totals?.coupon`; calls `useCart().applyCoupon` directly. **No `isAuthenticated` check.** |
| Coupon slider | `src/components/CouponPickerModal.tsx` | Manual-code input + offers list → `onApply(code)`. **No auth check.** |
| Apply/remove | `src/hooks/useCart.ts` | `applyCoupon` → `postCartCoupon(code, activeSessionUuid())`; sends `X-Cart-Session` for guests. **No auth check.** |
| API client | `src/lib/api.ts` | `postCartCoupon` → `POST /cart/coupon`. A 401 here threw `ApiError` (surfaced as the picker's inline error) and fired `acr-session-expired` — the de-facto "sign in to apply" behavior. |
| **The actual gate** | **`backend/routes/api.php:107`** | `Route::post('cart/coupon', …)->middleware(['auth:sanctum','throttle:cart-write'])`. **Guests were rejected by `auth:sanctum` before reaching the controller.** The DELETE (remove) route was already guest-capable. |

**How validation works (and whether it needs auth):**
- `CartCouponController::apply` → `CouponService::validate($code, $cart, $user)`.
- `validate()` already takes a **nullable** `?User $user`. Every check runs without a
  user **except one**: the per-user usage limit
  (`CouponService.php:65` → `$user !== null && $coupon->hasReachedUserLimit(...)`),
  which is skipped when `$user === null`. So **validation already works for guests** —
  it just skips the check that inherently needs a user identity.
- The discount itself is pure math on the cart subtotal (`Coupon::calculateDiscount`)
  and is persisted as `carts.coupon_id`. Guest carts are resolved by the
  `cart-session` middleware via `X-Cart-Session`, so a guest cart can hold a
  `coupon_id` and quote a discount with **no user** involved
  (`CartService::totalsFor` → `Cart::reloadCoupon`).

**Where checkout's sign-in gate lives (left intact):**
- Frontend: `src/pages/Checkout.tsx:359` — `if (!isAuthenticated) { …Login to Continue… }`.
- Backend: `POST /checkout/quote` + `POST /checkout/place-order` are under
  `auth:sanctum` (`routes/api.php:191`); orders routes too. **None touched.**

**Security note discovered during audit:** the per-user limit (`usage_per_user`) was
enforced **only** at the auth-gated apply step. `reloadCoupon()` (used by both cart
totals and checkout `quote`) and `placeOrder()` re-check only *active* + *not-expired*,
never the per-user count. So naively dropping the apply gate would let a user bypass
their per-user limit (guest-apply a coupon they've exhausted → sign in → place order).
Closing that is handled in change #4 below.

---

## 2. Guest-preview mechanism chosen

**Run the existing validation for guests with `$user = null`, persist `coupon_id`
on the guest cart, and let the existing pricing compute the discount — no new
preview path, no client-side faking.** Concretely:

- Drop `auth:sanctum` from the apply route so the request reaches the controller as
  a guest (resolved by `cart-session` via `X-Cart-Session`).
- In the controller, resolve the user **optionally** via `$request->user('sanctum')`
  (the default guard is `web`/session — see change #2 for why this matters).
- `validate()` runs exactly as before; for a guest it skips only `hasReachedUserLimit`.
- The discount is the real server-computed value (`totals.coupon` / `totals.discount`)
  read straight from the cart — same as a logged-in user sees. No security weakened
  beyond allowing the preview; the per-user limit is re-enforced at checkout.

---

## 3. Apply gate removed (where) — D-CPN-1, D-CPN-3, D-CPN-4

Four surgical backend edits (no frontend change — the sidebar already renders
`totals.coupon`). No new files; coupon logic, slider, and pricing untouched.

**1) `backend/routes/api.php` (line 107) — remove the apply auth gate.**
```php
// before
Route::post('cart/coupon', [CartCouponController::class,'apply'])
    ->middleware(['auth:sanctum','throttle:cart-write']);
// after
Route::post('cart/coupon', [CartCouponController::class,'apply'])
    ->middleware('throttle:cart-write');   // guest-capable; cart-session still wraps it
```

**2) `backend/app/Http/Controllers/Api/V1/Cart/CartCouponController.php` — optional user.**
```php
// $user = $request->user();              // default guard is session → null for token requests
$user = $request->user('sanctum');        // present for logged-in caller, null for guest
```
*Why:* `config/auth.php` defaults the guard to `web` (session). `$request->user()`
only returned the Bearer user *because* `auth:sanctum` had called `shouldUse('sanctum')`.
With that middleware gone, the explicit `('sanctum')` guard keeps the per-user check
working for logged-in callers at apply (so the existing "already used" rejection still
fires there) while correctly yielding `null` for guests. This mirrors the pattern
`CouponsController` already uses on its public route.

**Guest display (D-CPN-4) — verified, no change needed.** `CarSidebar` passes
`totals={cart?.totals}` to `<CouponInput>` (`CarSidebar.tsx:295`) and renders the
breakdown at `CarSidebar.tsx:285–290`: `Coupon Discount (CODE) −₹X`, with the final
`total` (line 75) reflecting the discount. After a guest applies, `useCart`'s apply
mutation writes the new cart into the `["cart","guest"]` query, so original →
discount → final total appear immediately.

---

## 4. Checkout sign-in confirmed intact — D-CPN-2 (+ per-user security guard, D-CPN-3)

**Unchanged & confirmed:** `Checkout.tsx:359` still shows "Login to Continue" for
guests; `POST /checkout/quote`, `POST /checkout/place-order`, and all `user/orders`
routes remain under `auth:sanctum`. A guest who applied a coupon and clicks checkout
gets the sign-in prompt exactly as today.

**Security guard added (so removing the apply gate doesn't weaken `usage_per_user`):**

**3) `backend/app/Http/Controllers/Api/V1/Checkout/CheckoutController.php`** — at
`placeOrder`, after the existing empty-cart check and reusing the same 422 pattern:
```php
$cart->loadMissing('coupon');
if ($cart->coupon && $cart->coupon->hasReachedUserLimit($user->id)) {
    return response()->json(['message' => 'You have already used this coupon.'], 422);
}
```
This re-enforces the per-user limit at the **gated** chokepoint where the customer
identity is known and the usage row is actually claimed — so a guest can preview a
coupon they've already exhausted, but cannot redeem it a second time by signing in.
It rejects honestly (no silent full-price charge) and reuses the already-tested
`Coupon::hasReachedUserLimit()`.

---

## 5. Coupon carried into checkout after sign-in — D-CPN-5

**4) `backend/app/Services/Cart/CartMergeService.php`** — in the last-cart-wins
branch, carry the guest cart's coupon onto the surviving user cart:
```php
$userCart->coupon_id  = $guestCart->coupon_id;   // added
$userCart->expires_at = now()->addDays(90);
$userCart->save();
```
The merge already reparents guest **items** on login (auto-merge in
`VerifyOtpController` when `X-Cart-Session` is present, and the explicit
`POST /cart/merge`). It did **not** copy `coupon_id`, so a guest's applied coupon
would have been lost at sign-in. Carrying it (reusing the existing column —
no new state) means the previewed discount survives login into checkout.
Consistent with last-cart-wins: the guest cart is the user's current intent, so its
coupon wins too. The per-user re-check at place-order (change #3) ensures carrying it
can't bypass the limit.

---

## 6. tsc / build / smoke + backend suite

| Check | Result |
|---|---|
| `npx tsc --noEmit` | **2 pre-existing only** — `tests/e2e/brand-typography.spec.ts:121,137` (`HTMLElement \| SVGElement`). No new errors. |
| `npm run build` | **clean** (exit 0, built in ~5s) |
| `npx playwright test --project=smoke` | **3/3 passed** (home renders, login modal opens, /payment → NotFound) |
| `php vendor/bin/pest` (full backend) | **288 passed (1168 assertions)** — incl. CartTest, CheckoutTest, AuthOtpTest, OrdersTest |

**New tests added to existing files** (no new files):
- `tests/Feature/EdgeCases/CouponEdgeCasesTest.php`
  - *"lets a GUEST (not signed in) apply FIRST10 and preview the discounted total"* —
    guest (X-Cart-Session only, no `actingAs`) applies → 200, `totals.coupon.code=FIRST10`,
    discount ₹200, total ₹1800; persists across a fresh `GET /cart`.
  - *"still rejects an invalid coupon code for a guest (validation intact)"* → 422.
- `tests/Feature/EdgeCases/CartMergeTest.php`
  - *"carries a coupon applied on the guest cart onto the user cart on merge"* →
    after merge, `cart.totals.coupon.code=FIRST10` and `userCart.coupon_id` set.

---

## 7. Manual verification per scenario

Verified end-to-end at the API/contract level (real routes → middleware → controllers
→ services → DB) plus frontend data-flow inspection. A live browser click-through is
left to the operator (per the task's closing note).

| # | Scenario | Result | Evidence |
|---|---|---|---|
| a | Guest applies valid coupon → discounted total shows, **no sign-in prompt** | ✅ | New guest-apply test: 200 + `totals.coupon`/`discount`/`total`. Sidebar renders the discount via `CarSidebar.tsx:285–290` + `CouponInput` from the guest cart query. No 401 → no `acr-session-expired` → no sign-in prompt. |
| b | Invalid / expired coupon → existing validation error still works | ✅ | New guest invalid test → 422 "Invalid coupon code."; existing expired/min-order tests still pass. |
| c | Guest clicks checkout/book → sign-in **still required** | ✅ | `Checkout.tsx:359` gate untouched; `checkout/*` + `user/orders` still under `auth:sanctum`; `OrdersTest` "rejects unauthenticated /user/orders with 401" passes. |
| d | Guest signs in at checkout → applied coupon **carried through** | ✅ | New merge test: coupon survives merge (`coupon_id` on user cart + `totals.coupon`). Same path used by the OTP-verify auto-merge. |
| e | Logged-in user coupon flow still works | ✅ | `CouponTest` (apply FIRST10 authed) + all `CouponEdgeCasesTest` authed cases still pass; `$request->user('sanctum')` keeps the per-user check at apply for logged-in users. |
| f | Pricing with coupon correct in all cases | ✅ | `calculateDiscount` / `reloadCoupon` / `totalsFor` unchanged; tests assert subtotal 2000 → discount 200 → total 1800 for guest, authed, and merged carts. |

---

## 8. Deviations / security notes

1. **The gate was backend, not frontend.** PART A's grep over `src/` found no
   client-side auth gate — the "sign to apply" behavior came entirely from the
   `auth:sanctum` route middleware (a 401 surfaced as the picker error). The fix is
   therefore in `backend/` (exactly what PART A step 2 anticipated for an
   auth-requiring API). **No `src/` code changed**; the existing sidebar already
   renders `totals.coupon`, so guest preview lights up for free.

2. **Optional-user resolution was required, not cosmetic.** Because the default guard
   is session-based (`config/auth.php`), simply removing `auth:sanctum` would have made
   `$request->user()` return `null` for *logged-in* callers too, silently dropping the
   per-user check at apply for everyone. Switching to `$request->user('sanctum')`
   preserves the logged-in behavior. (Security preserved, not weakened.)

3. **Per-user limit moved to the gated checkout (security-preserving).** `usage_per_user`
   was previously enforced only at the auth-gated apply step. To avoid a bypass
   (guest-apply an exhausted coupon → sign in → redeem again), I added a per-user
   re-check at `place-order` (change #3), the chokepoint where the usage row is
   claimed. This **restores** the existing security property under the new flow rather
   than weakening it. Net change to the security surface: a guest may *preview* a
   discount they ultimately can't *redeem* — which is the intended transparency, and
   they're told clearly at checkout ("You have already used this coupon").

4. **Coupon-carry on merge uses last-cart-wins semantics.** In the last-cart-wins
   branch the guest cart's `coupon_id` overwrites the user cart's (consistent with
   items already being replaced). The empty-guest-cart branch is untouched (user cart,
   including any existing coupon, is preserved).

5. **Untouched, per constraints:** coupon validation/discount logic
   (`CouponService`, `Coupon::calculateDiscount`), the slider (`CouponPickerModal`),
   pricing calc (`CartService`/`reloadCoupon`), and all checkout auth. No packages
   installed. No files created. Git left to the operator (D-CPN-6).
