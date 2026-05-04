# Phase 2.4 — Cart merge protocol (report)

Single-commit landing per the brief. Implements
`/PHASE2_CONTRACT.md` §6.5(d) — the cart-merge bookmark planted in
`VerifyOtpController` during Phase 2.1 — and §5.3 #18, the
explicit `POST /cart/merge` endpoint for re-merge / multi-device.
After five hotfix iterations on the 2.3 series, the cart system
returns to the planned roadmap. Guest carts now flow seamlessly
into user carts on OTP verification; the orphan-cart UX gap
observed in 2.3 testing is closed.

## Files created

### Backend
| File | Lines |
|---|---|
| `backend/app/Services/Cart/CartMergeService.php` | 119 |
| `backend/app/Services/Cart/CartMergeException.php` | 17 |
| `backend/app/Http/Controllers/Api/V1/Cart/MergeCartController.php` | 60 |

## Files modified

### Backend
| File | Change |
|---|---|
| `backend/app/Http/Controllers/Api/V1/Auth/VerifyOtpController.php` | Constructor-injects `CartMergeService`. `finishVerification(...)` signature gains `Request $request`; both call sites in `__invoke` (dev-bypass + normal path) updated. After token state mutation, when `X-Cart-Session` header carries a valid UUID, calls `cartMerge->mergeGuestIntoUser(...)`. Failure logs `Log::warning` and continues — merge never blocks login. Bookmark comment in the class docblock replaced with the actual Phase 2.4 description. |
| `backend/routes/api.php` | Imported `MergeCartController`; appended `Route::post('cart/merge', MergeCartController::class)->middleware(['auth:sanctum', 'throttle:cart-write'])` inside the existing `cart-session` middleware group. |

### Frontend
| File | Change |
|---|---|
| `src/lib/api.ts` | Added `postCartMerge(guestSessionUuid, signal?): Promise<CartResponse>`. Refactored `postVerifyOtp` to accept an optional `guestSessionUuid` and stamp it as `X-Cart-Session` so the server-side hook fires before the token is issued. |
| `src/hooks/useAuth.ts` | `verifyOtp(...)` reads `localStorage["acr_cart_session"]`, passes it to `postVerifyOtp` (header path), AND fires-and-forgets `postCartMerge(uuid)` after success (defense in depth — idempotent on the server). Imports updated. |
| `src/config/features.ts` | `cartSync: false → true`. Comment rewritten to point at the merge protocol. |

## Migrations

**None.** Phase 2.4 is pure logic on existing schema — `carts.status`
and `cart_items.cart_id` are the only columns touched at runtime.
The merge service uses `lockForUpdate()` against rows that already
exist; no new columns or indexes added.

## Route list (cart subset)

```
GET|HEAD api/v1/cart                        Api\V1\Cart\CartController@show
POST     api/v1/cart/items                  Api\V1\Cart\CartController@addItem
PUT      api/v1/cart/items/{item}           Api\V1\Cart\CartController@updateItem
DELETE   api/v1/cart/items/{item}           Api\V1\Cart\CartController@removeItem
POST     api/v1/cart/coupon                 Api\V1\Cart\CartController@applyCoupon       (501 stub)
DELETE   api/v1/cart/coupon                 Api\V1\Cart\CartController@removeCoupon      (501 stub)
POST     api/v1/cart/merge                  Api\V1\Cart\MergeCartController              ← NEW
```

`php artisan route:list --path=api --json | jq length` → **34**
(33 + 1 from this commit).

## CartMergeService walkthrough

Algorithm (every step inside a single `DB::transaction`):

1. `SELECT … FOR UPDATE` the guest cart by `(session_uuid, status='active')`.
2. `SELECT … FOR UPDATE` the user cart by `(user_id, status='active')`.
   firstOrCreate equivalent inside the txn — VerifyOtpController
   calls the merge BEFORE the user has hit any `cart-session` route,
   so the user cart may not yet exist.
3. Self-merge guard (`guestCart.id === userCart.id`) — defensive;
   shouldn't trigger because guest carts have null `user_id` and
   user carts have null `session_uuid`, but cheap to keep.
4. Iterate guest items. Build a tuple-keyed map of user items:
   `kind|ref_id|brand_id|model_id|fuel_id` (nulls collapse to literal
   `'null'` so no-vehicle items match no-vehicle items).
5. For each guest item:
   - Tuple match → bump matched user item's `quantity` (capped 99);
     `delete()` the guest item row. **Counter `merged++`**.
   - No match → `UPDATE cart_items SET cart_id=$userCart->id WHERE id=$guestItem->id`;
     add to the in-memory map for subsequent guest items in the
     same batch. **Counter `moved++`**.
6. Mark `guestCart.status = 'converted'` and `expires_at = now()`
   (so future expiry sweeps don't try to mutate it).
7. Bump `userCart.expires_at = now()->addDays(90)` (active-engagement
   refresh).
8. `Log::info('Cart merge completed', { user_id, guest_uuid, … merged_items, moved_items })`.

**Idempotency:** a second call with the same `guest_uuid` finds the
guest cart already `'converted'` (its status no longer matches
`'active'`), the lookup returns null, and the function short-circuits
to the user cart unchanged. No exceptions; same return type.

**unit_price_snapshot policy:** the user-cart side wins on a
quantity bump. The guest item is deleted, not its price merged.
This matches the contract's "authenticated session has older prices
honored" intent.

**Lock window:** both rows are held under transactional locks for
the duration of the merge. Concurrent operations on either cart
(e.g. a stray `addItem` from another tab during the merge) wait on
the lock and serialize correctly.

## VerifyOtpController integration

The Phase 2.1 docblock note "Cart merge (commit 2.4) is intentionally
NOT in scope of 2.1" is replaced with the Phase 2.4 description.
`finishVerification` now accepts the `Request` so it can read the
`X-Cart-Session` header. Both invocation paths — dev-bypass and
normal OTP — pass the request through. Merge is best-effort:
`Log::warning` on failure, no rethrow.

## Curl chain results

### OTP-driven merge (scenarios 1–5)

```
$ POST /cart/items × 2 with X-Cart-Session: aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
   → guest cart item_count=2 ✓

$ POST /auth/lead-capture {phone:"7777666555", name:"Merge Test", intent:"signup"}
HTTP 200 → dev_code 752912

$ POST /auth/verify-otp WITH X-Cart-Session header
HTTP 200 → token "16|yLUY1phsqhBIjeGaHOnjl4R66uLo8udctFPHkOpy2143e605"
       laravel.log: "Cart merge completed" {user_id:14, …, merged_items:0, moved_items:2}

$ GET /cart Authorization: Bearer <token>  (NO X-Cart-Session)
HTTP 200
  item_count=2  is_user_cart=true  expires_at=2026-08-02T05:10:32.000000Z (~90 days)
  - Battery Charging x1   (kind=service, ref=1)
  - Battery Replacement x1 (kind=service, ref=2)

mysql> SELECT id, status, expires_at FROM carts WHERE session_uuid='aaaaaaaa-…';
   id=11  status='converted'  expires_at='2026-05-04 05:10:32'   ✓
```

### Explicit /cart/merge (scenarios 6–7)

```
$ POST /cart/items {ref_id:3} with X-Cart-Session: bbbbbbbb-cccc-dddd-…
   → UUID_B guest cart has 1 item (Flat Bed Towing)

$ POST /cart/merge {guest_session_uuid:"bbbbbbbb-…"} Bearer <token>
HTTP 200
  item_count=3  is_user_cart=true
  - Battery Charging x1
  - Battery Replacement x1
  - Flat Bed Towing x1                              ← merged from UUID_B  ✓
```

### Edge cases (scenarios 8–11)

```
$ POST /cart/merge {guest_session_uuid:"00000000-0000-0000-0000-000000000000"}
HTTP 200  item_count=3 (unchanged — no guest cart found, no-op)   ✓

$ POST /cart/merge {guest_session_uuid:"bbbbbbbb-…"}   ← already converted
HTTP 200  item_count=3 (no change — idempotent)                   ✓

$ POST /cart/merge {guest_session_uuid:"not-a-uuid"}
HTTP 422  {"errors":{"guest_session_uuid":["The guest session uuid field must be a valid UUID."]}}   ✓

$ POST /cart/merge   (no Authorization header)
HTTP 401  {"message":"Unauthenticated."}                           ✓
```

### Same-tuple dedup (scenarios 12–14)

```
$ POST /cart/items {ref_id:1, vehicle:{1,1,1}} with X-Cart-Session: cccccccc-…
   → UUID_C guest cart has Battery Charging (matches user's existing tuple)

$ POST /cart/merge {guest_session_uuid:"cccccccc-…"}
HTTP 200
  item_count=4 (was 3; +1 from the bumped quantity)
  items array length=3 (NOT duplicated)
  - Battery Charging (ref=1) x2     ← bumped from x1 to x2 ✓
  - Battery Replacement (ref=2) x1
  - Flat Bed Towing (ref=3) x1
```

## FEATURES.cartSync flip diff

```diff
-  /** /cart/* (server-authoritative cart) lands in Phase 2.3. */
-  cartSync: false,
+  /**
+   * Phase 2.4 — server-side cart merge protocol live; client and
+   * server cart stay synchronized through the OTP-verify hook
+   * (X-Cart-Session header → server-side merge before token issue)
+   * and the explicit POST /cart/merge endpoint for the multi-device
+   * / re-merge case. Set true now that the protocol ships.
+   */
+  cartSync: true,
```

## Frontend changes

### `src/lib/api.ts`

`postVerifyOtp(req, guestSessionUuid?, signal?)` — second arg is
the guest UUID; when present it goes out as `X-Cart-Session`.

```ts
export const postVerifyOtp = (
  req: VerifyOtpRequest,
  guestSessionUuid?: string | null,
  signal?: AbortSignal,
) =>
  api<VerifyOtpResponse>("/auth/verify-otp", {
    method: "POST",
    body: req,
    signal,
    headers: guestSessionUuid ? { "X-Cart-Session": guestSessionUuid } : undefined,
  });

export const postCartMerge = (guestSessionUuid: string, signal?: AbortSignal) =>
  apiPost<CartResponse>("/cart/merge", { guest_session_uuid: guestSessionUuid }, signal);
```

### `src/hooks/useAuth.ts` — `verifyOtp` body

```ts
const guestUuid = typeof window !== "undefined"
  ? window.localStorage.getItem("acr_cart_session")
  : null;

const res: VerifyOtpResponse = await postVerifyOtp(input, guestUuid);
setToken(res.token);
const u = presentUser(res.user);
setUser(u);

// Defense in depth — non-blocking; idempotent server-side.
if (guestUuid) {
  postCartMerge(guestUuid).catch((err) => {
    console.warn("[Phase 2.4] Cart merge after OTP failed", err);
  });
}
```

`useCart` already invalidates the cart query when the auth token
flag flips (the `useTokenFlag` listener on `acr-token-updated`),
so the next `/cart` fetch picks up the merged user cart with zero
extra wiring.

## Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2161 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-Bs7Co01j.css   104.96 kB │ gzip:  17.23 kB
dist/assets/index-CxDupAoS.js    740.94 kB │ gzip: 196.19 kB
✓ built in 1m

$ # Vite dev restart
VITE v6.4.2  ready in 1817 ms
GET http://localhost:3000/  →  HTTP 200
```

## Single commit

`f7ed3ccb2a67d7982edab791d02ad6e69e9f74bf` — 9 files, 635 insertions, 10 deletions.
3 new backend files (CartMergeService + exception + MergeCartController),
2 backend modifications (VerifyOtpController, routes/api.php),
3 frontend modifications (api.ts, useAuth.ts, features.ts),
1 report file. No migration. No package install.

## Deviations

1. **`MergeCartController` route also binds `auth:sanctum`** even
   though it sits inside the `cart-session` group. The
   `cart-session` middleware accepts EITHER auth OR an
   `X-Cart-Session` header — but the merge endpoint specifically
   requires auth (the body provides the guest UUID; the `Bearer`
   token provides the user identity). Belt-and-braces: the merge
   route declares `auth:sanctum` explicitly so a user could not
   somehow reach it via `X-Cart-Session` only.

2. **The merge service firstOrCreates the user cart** when none
   exists. The `cart-session` middleware would normally do this on
   the request thread, but `VerifyOtpController` invokes the merge
   BEFORE the user has hit any cart-session route, so the
   user-cart row may not yet exist. Doing the firstOrCreate inside
   the merge transaction keeps the lock semantics clean.

3. **Quantity-cap on tuple-merge is 99** to match
   `CartController::addItem`'s same-cart re-add cap. If the merge
   would push a line past 99 (guest x60 + user x60 = 120), the
   line is clamped to 99. Logged inside `Log::info` indirectly via
   the merged-items counter; an explicit overflow warning could be
   added later if it shows up in production.

4. **The legacy 5-hotfix-deferred Phase 2.3 cart-merge bug** —
   "guest user logs in, sees empty cart" — is now structurally
   impossible: the OTP-verify hook merges synchronously before the
   token is issued, and the explicit `/cart/merge` endpoint
   handles the multi-device case. The Phase 2.3.1 deviation #4
   item is closed.
