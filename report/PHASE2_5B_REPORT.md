# Phase 2.5b — real coupon system (end-to-end)

Replaces the 501 stubs on `POST/DELETE /cart/coupon` with the full
backend pipeline; adds public `GET /coupons` listing; rebuilds the
frontend coupon UX as a modal-based picker. Lights up
`FEATURES.couponsLit`.

Commit: see "Commit" below.

---

## 1. Files created / modified

### Backend — new
| Path | Purpose |
|---|---|
| `backend/database/migrations/2026_05_05_120001_create_coupons_table.php` | Table + 3-row seed (FIRST10, ACCOOL20, SAVER15). |
| `backend/database/migrations/2026_05_05_120002_create_coupon_usages_table.php` | Per-redemption record; (coupon_id,user_id) composite index. |
| `backend/database/migrations/2026_05_05_120003_add_coupon_id_to_carts_table.php` | `carts.coupon_id` FK with `nullOnDelete`. |
| `backend/database/migrations/2026_05_05_120004_add_coupon_fk_to_orders_table.php` | Promotes `orders.coupon_id` (plain column from Phase 2.5a) to a real FK. |
| `backend/app/Models/Coupon.php` | Validation primitives + scopes + `calculateDiscount`. |
| `backend/app/Models/CouponUsage.php` | Redemption record. |
| `backend/app/Services/Coupon/CouponService.php` | `validate`, `applyToCart`, `removeFromCart`, `claim`. |
| `backend/app/Http/Controllers/Api/V1/Cart/CartCouponController.php` | Real `POST/DELETE /cart/coupon`. |
| `backend/app/Http/Controllers/Api/V1/Public/CouponsController.php` | Public `GET /coupons`; supports `?context=cart` for eligibility. |
| `backend/app/Http/Resources/V1/CouponResource.php` | Resource; emits `eligible` / `ineligible_reason` when controller stamps them. |

### Backend — modified
| Path | Why |
|---|---|
| `backend/app/Models/Cart.php` | `coupon_id` fillable + `coupon()` belongsTo. |
| `backend/app/Models/Order.php` | `coupon()` belongsTo (declaration deferred from 2.5a). |
| `backend/app/Services/Cart/CartService.php` | `totalsFor` now reads `cart.coupon`, computes discount, exposes coupon meta; auto-clears stale ref when coupon deactivated/expired. |
| `backend/app/Services/Checkout/CheckoutService.php` | Mirror discount math in `quote`; pin `coupon_id` onto Order; call `CouponService::claim` inside the place-order transaction (D-2.5b-7); eager-load `coupon` everywhere. |
| `backend/app/Http/Controllers/Api/V1/Cart/CartController.php` | Removed 501 stubs (moved to CartCouponController). |
| `backend/app/Http/Controllers/Api/V1/User/OrderController.php` | Eager-load `coupon` on index/show. |
| `backend/app/Http/Resources/V1/OrderResource.php` | `totals.coupon` snapshot when `coupon_id !== null`. |
| `backend/routes/api.php` | Repointed `cart/coupon` POST/DELETE to `CartCouponController`; new public `GET /coupons`. |

### Frontend — new
| Path | Purpose |
|---|---|
| `src/components/CouponPickerModal.tsx` | Modal-based picker; shared by Cart + Checkout. |
| `src/hooks/useCoupons.ts` | React Query hook for `GET /coupons`; 2 min staleTime. |
| `PHASE2_5B_REPORT.md` | This report. |

### Frontend — modified
| Path | Why |
|---|---|
| `src/types/api.ts` | `CouponResource`, `CouponsListResponse`, `AppliedCouponSummary`; OrderResource totals expose optional `coupon`. |
| `src/lib/api.ts` | Imports `CouponsListResponse`; new `fetchCoupons(context)` fetcher. |
| `src/hooks/useCart.ts` | `applyCoupon` / `removeCoupon` now hit real backend mutations (no more local stub). |
| `src/components/CouponInput.tsx` | Replaced inline `<CouponList>` with a single button that opens `<CouponPickerModal>`; renders applied state inline when `cart.totals.coupon` is non-null. |
| `src/pages/Coupons.tsx` | Reads from `useCoupons('marketing')` instead of hardcoded array; adds Copy Code button with clipboard API + 1.5s "Copied!" toast. |
| `src/pages/Cart.tsx` | (No code changes in 2.5b — the 2.5.1 wiring through `cart.totals.coupon`/`discount` works unchanged once the backend lights up.) |
| `src/pages/Checkout.tsx` | GST math now computed on `(subtotal - discount)` so the right-panel total matches the backend's `quote()` when a coupon is applied. Discount-line wording flipped from `Discount` to `Coupon (CODE)`. |
| `src/pages/BookingConfirmation.tsx` | Coupon line shows `Coupon (CODE)` when `totals.coupon` present (falls back to "Coupon Applied"). |
| `src/pages/OrderDetail.tsx` | Same wording flip. |
| `src/config/features.ts` | `couponsLit: true` (flipped from false). |

### Frontend — deleted
| Path | Reason |
|---|---|
| `src/components/CouponList.tsx` | Inline-card layout dropped per D-2.5b-2; coupons live in the modal now. No remaining importers. |

---

## 2. Migrations

```
INFO  Running migrations.
2026_05_05_120001_create_coupons_table ............................. 325ms DONE
2026_05_05_120002_create_coupon_usages_table ....................... 340ms DONE
2026_05_05_120003_add_coupon_id_to_carts_table ..................... 113ms DONE
2026_05_05_120004_add_coupon_fk_to_orders_table .................... 102ms DONE
```

### `coupons` columns
```
id, code, name, description, discount_type, discount_value, max_discount,
min_order_value, applicable_service_ids, applicable_category_ids,
usage_limit, usage_per_user, expiry_date, is_active, is_featured,
badge, display_order, created_at, updated_at
```
Indexes: `code` (unique), `is_active`, `expiry_date`, `(is_active, expiry_date)`, `is_featured`.

### `coupon_usages` columns
```
id, coupon_id, user_id, order_id, discount_amount, used_at,
created_at, updated_at
```
Indexes: `coupon_id`, `user_id`, `order_id`, `(coupon_id, user_id)`.

### `carts.coupon_id`
`unsignedBigInteger` nullable, FK → `coupons.id`, `ON DELETE SET NULL`. Index: `carts_coupon_id_idx`.

### `orders.coupon_id`
Pre-existing column (Phase 2.5a) now FK-constrained → `coupons.id`, `ON DELETE SET NULL`. Index: `orders_coupon_id_idx`.

### Seed verification
```
1 | FIRST10  | percent 10.00 | min 500.00  | featured=Y
2 | ACCOOL20 | flat 500.00   | min 1500.00 | featured=Y
3 | SAVER15  | percent 15.00 | min 3000.00 | featured=Y
```

---

## 3. Models + relations

| Model | Relations / Methods |
|---|---|
| `Coupon` | `usages()`, `orders()`, `carts()`. Scopes: `active`, `notExpired`, `featured`. Predicates: `isExpired`, `hasReachedGlobalLimit`, `hasReachedUserLimit($userId)`, `appliesToAnyOf($pairs)`, `calculateDiscount($subtotal)`. |
| `CouponUsage` | `coupon()`, `user()`, `order()`. |
| `Cart` | Added `coupon()` belongsTo + `coupon_id` fillable. |
| `Order` | Added `coupon()` belongsTo (placeholder removed). |

---

## 4. CouponService walkthrough

`validate(string $code, Cart $cart, ?User $user): array`

Order of checks (D-2.5b-5), all of which short-circuit:

1. **Code lookup**: `WHERE code = ? AND is_active = true`. Miss → "Invalid coupon code."
2. **Expiry**: `expiry_date IS NULL OR expiry_date >= today`. Miss → "This coupon has expired."
3. **Min order**: `subtotal >= min_order_value`. Miss → "Minimum order ₹X required to use this coupon."
4. **Global limit**: `usages()->count() < usage_limit` (when `usage_limit !== null`). Miss → "This coupon has reached its usage limit."
5. **Per-user limit**: `usages()->where('user_id',$user->id)->count() < usage_per_user` (when both set). Miss → "You have already used this coupon."
6. **Cart applicability**: at least one cart item matches `applicable_service_ids` OR `applicable_category_ids` (when either filter is non-null). Miss → "This coupon is not applicable to items in your cart."

Returns `{ valid, coupon, reason, discount_amount }`.

`applyToCart(Coupon $coupon, Cart $cart): Cart`
- Sets `cart.coupon_id`. Last-apply-wins (D-2.5b-3) — overwrites any existing.
- Caller validates first; this method never re-checks.

`removeFromCart(Cart $cart): Cart`
- Clears `cart.coupon_id` to null.

`claim(Coupon $coupon, User $user, Order $order, float $discountAmount): CouponUsage`
- Inserts a `coupon_usages` row.
- Called inside `CheckoutService::placeOrder` transaction (D-2.5b-7).

---

## 5. CartService / CheckoutService changes

### `CartService::totalsFor`
Pre-2.5b: hardcoded `$discount = 0; $coupon = null`.

Post-2.5b:
```php
if ($cart->coupon_id !== null) {
  $coupon = $cart->coupon;
  if ($coupon && $coupon->is_active && !$coupon->isExpired()) {
    $discount = $coupon->calculateDiscount($subtotal);
    $couponMeta = ['code' => …, 'name' => …, 'discount_amount' => $discount];
  } else {
    // Stale ref — coupon got deactivated/expired since apply.
    // Auto-clear so the cart never quotes a phantom discount.
    $cart->coupon_id = null;
    $cart->save();
  }
}
```
Cart still pre-tax (D-B). Total = `subtotal - discount`.

### `CheckoutService::quote`
Mirrors the same discount math. GST is computed on `(subtotal - discount)` per D-2.5b-6. `breakdown_lines` now includes a `Coupon (CODE)` row when applicable.

### `CheckoutService::placeOrder`
- Pre-loads `cart.coupon` alongside the existing item relations.
- Pins `cart.coupon_id` onto the new Order row at create time.
- After `Order::create`, calls `couponService->claim($coupon, $user, $order, $discount)` inside the same `DB::transaction`.
- `Order.discount` field (already on the schema since 2.5a) gets populated via the discount value from `quote()`.

---

## 6. Routes

`php artisan route:list` — **46 total** (45 pre-2.5b + 1 new `GET /coupons`).

New / changed:
```
GET    /api/v1/coupons                       Public\CouponsController@index
POST   /api/v1/cart/coupon                   Cart\CartCouponController@apply
DELETE /api/v1/cart/coupon                   Cart\CartCouponController@remove
```

Throttle wiring: `coupons` → `public-read`; cart coupon ops → `cart-write`.

`POST /cart/coupon` requires `auth:sanctum` (Bearer token) so the per-user limit is enforceable. `DELETE /cart/coupon` runs inside `cart-session` only — guests with cookied/UUID carts can still remove a previously-applied code.

`GET /coupons` is public (no auth middleware); the controller resolves the optional Bearer via `$request->user('sanctum')` so anonymous requests get marketing-shaped responses while authenticated requests carrying `?context=cart` get per-coupon eligibility.

---

## 7. Curl chain results

Token issued for `phone=9876543299` (user_id=15).

| | Scenario | Outcome |
|---|---|---|
| **A** | `GET /coupons` | `HTTP 200`; 3 featured coupons returned with badges. |
| **B** | `GET /coupons?context=cart` (empty cart) | `HTTP 200`; all 3 marked `eligible=false` with reason "Add items to your cart to use this coupon." After adding 1 item @₹2325: FIRST10 eligible, ACCOOL20 "not applicable to items in your cart", SAVER15 "Minimum order ₹3,000 required". |
| **C** | `POST /cart/coupon {code:"FIRST10"}` (cart ₹2325) | `HTTP 200`; `cart.totals = { subtotal:2325, discount:232.5, coupon:{code:'FIRST10',name:'First Booking Discount',discount_amount:232.5}, tax:0, total:2092.5 }`. |
| **D** | `POST /cart/coupon {code:"XXXXX"}` | `HTTP 422`; `"Invalid coupon code."` |
| **E** | `POST /cart/coupon {code:"FIRST10"}` (cart ₹475 < min ₹500) | `HTTP 422`; `"Minimum order ₹500 required to use this coupon."` |
| **F** | `DELETE /cart/coupon` | `HTTP 200`; `cart.totals.discount=0`, `coupon=null`, `total=2325` (back to subtotal). |
| **G** | Apply FIRST10 + place order | `HTTP 201`; `order_number=ACR-2026-00008`; `totals = { subtotal:2325, discount:232.5, tax:376.65, total:2469.15, coupon:{code:'FIRST10',…} }`. `coupon_usages` row written: `coupon_id=1, user_id=15, order_id=8, discount=232.50`. |
| **H** | Re-add cart item, retry FIRST10 (usage_per_user=1) | `HTTP 422`; `"You have already used this coupon."` |
| **I** | `POST /cart/coupon {code:"ACCOOL20"}` (cart has Battery Charging, not AC) | `HTTP 422`; `"This coupon is not applicable to items in your cart."` |
| **J** | `POST /cart/coupon {code:"SAVER15"}` (cart ₹2325 < min ₹3000) | `HTTP 422`; `"Minimum order ₹3,000 required to use this coupon."` |

All 10 verifications green.

---

## 8. Frontend summary

### `<CouponPickerModal>`
- Themed modal matching the AuthModal/VehicleReplaceModal vocabulary (fixed-overlay, neutral-900/95 backdrop, white card, X close).
- Header: "AVAILABLE COUPONS." with sparkle icon.
- Manual entry block (sticky top): code input + Apply button.
- Body (scrollable): per-coupon cards rendered from `useCoupons('cart')`, each showing code + badge, name, description, conditions row (`Min order ₹X · Max ₹Y off · Valid till YYYY-MM-DD`), and an Apply button. Ineligible cards are dimmed with the server's `ineligible_reason` shown inline.
- Apply (either path) → `useCart.applyCoupon(code)` → modal auto-closes on success; per-card / general error surfaced on failure.
- Refetches eligibility on every open so the cart-state-derived flags stay fresh.

### `<CouponInput>` rework
- When **no** coupon applied: a single "Apply Coupon" button (themed, full-width). Click opens `<CouponPickerModal>`.
- When applied: inline applied-state row showing `[sparkle] FIRST10 — ₹X off [×]`; the × calls `removeCoupon`.
- Used in Cart and Checkout right panels — same component, same surface.

### `Coupons.tsx` (marketing landing)
- Reads `useCoupons('marketing')`.
- Skeleton (4 placeholder cards) during load, themed empty state when no coupons returned.
- Each card: code + badge, name, description, conditions, **Copy Code** button.
- Copy uses `navigator.clipboard.writeText` + 1.5s "Copied!" toast inside the button. No Apply button on this page (per D-2.5b-2 — apply lives in the modal accessed from Cart/Checkout).

### Type changes
- `AppliedCouponSummary { code, name, discount_amount }` — used by `CartTotals.coupon` and `OrderResource.totals.coupon`.
- `CouponResource { id, code, name, description, discount_type, discount_value, max_discount, min_order_value, expiry_date, badge, eligible?, ineligible_reason? }`.
- `CouponsListResponse { coupons: CouponResource[] }`.

### `useCart` changes
- `applyCoupon(code)` → `postCartCouponApi(code)` mutation. On success, `qc.setQueryData(['cart',…])` updates the cached cart in place.
- `removeCoupon()` → `deleteCartCouponApi()` same pattern.
- The pre-2.5b "coming soon" message paths are gone.

### Feature flag
`FEATURES.couponsLit: true`.

---

## 9. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-B-sNDa1V.css  107.26 kB │ gzip:  17.55 kB
dist/assets/index-ClcXyovw.js   782.23 kB │ gzip: 205.33 kB
✓ built in 21.14s
```

Pre-existing >500 kB chunk warning unchanged.

---

## 10. Commit

`feat(api,frontend): Phase 2.5b — real coupon system. coupons + coupon_usages migrations + carts.coupon_id + orders.coupon_id FK conversions; CouponService with validation + applyToCart + claim; replaces 501 stubs on POST/DELETE /cart/coupon; adds GET /coupons (public listing); modal-based picker UX with eligibility flags + ineligibility reasons; /coupons marketing page with copy-to-clipboard. Lights up FEATURES.couponsLit. Closes Phase 2.5b of /PHASE2_CONTRACT.md.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 11. Deviations

- **`POST /cart/coupon` is auth-required, `DELETE` is not.** Apply needs the user identity to enforce `usage_per_user`; remove just clears a stored FK and is harmless on a guest cart. Documented in route-comment.
- **`GET /coupons` is public, with optional auth pickup.** Resolved via `$request->user('sanctum')` inside the controller rather than `auth:sanctum` middleware so anonymous requests still get the marketing payload (with `eligible=false` reasons in cart context). The Sanctum guard is installed app-wide so the optional pickup works without explicit middleware.
- **Per-card error in the picker is keyed by code.** A single global error slot would have been simpler; per-card placement makes the failure mode legible when the user tries multiple cards in sequence.
- **`CouponService::validate` is called twice per apply** — once by the picker modal via `?context=cart`, then again by the controller. The picker call is cheap (one query per coupon, in-memory eligibility), and the controller call is the authoritative gatekeeper. Consolidating would save one query but couple the listing endpoint to the cart endpoint.
- **No coupon search / filter in the picker.** The codebase only ships 3 coupons today and the UI scales fine to ~20–30 cards. When a future commit ships a larger catalog this can become a search box; for now it would be premature.
- **Cart's `<Discount>` row label changed to `Coupon (CODE)`.** The pre-2.5b row already said "Discount" with no code; now that we have the code from server state, surfacing it makes the cart preview match the receipt and the order detail.
- **`CouponList.tsx` deleted** even though the spec said it could be kept. The modal renders its own card list inline; keeping a duplicate component would be Phase 2.6 cleanup waiting to happen.
- **Stale-coupon auto-clear lives in CartService::totalsFor + CheckoutService::quote.** Both write to the cart on stale detection. Could be centralized into a `Cart::reloadCoupon()` model method in 2.6 — for now the duplication is contained and intentional (kept inline so each math path is self-contained).
- **No backfill needed for `orders.coupon_id` FK.** Every existing row from Phase 2.5a has `coupon_id IS NULL`, so adding the FK constraint is a metadata-only operation.
