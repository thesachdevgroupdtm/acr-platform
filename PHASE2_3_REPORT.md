# Phase 2.3 — Server-authoritative cart (report)

Single-commit landing per the brief. Implements
`/PHASE2_CONTRACT.md` sections §2.4 (carts), §2.5 (cart_items), §3
(Cart, CartItem models + User.carts/activeCart relations), §4.3
(CartResource, CartItemResource), §5.3 #12-#15 (4 cart endpoints),
§6.6 (cart re-snapshot rule), §8 (cart-session middleware +
throttle).

`/cart/coupon` POST and DELETE are landed as 501 stubs — the
coupons table doesn't migrate until 2.6, so the endpoints exist
(route count = 33) but return `Not Implemented`. `/cart/merge`
remains untouched in this commit; it's a 2.4 deliverable.

## Files created

### Backend
| File | Lines |
|---|---|
| `backend/database/migrations/2026_05_03_120003_create_carts_table.php` | 59 |
| `backend/database/migrations/2026_05_03_120004_create_cart_items_table.php` | 50 |
| `backend/app/Models/Cart.php` | 74 |
| `backend/app/Models/CartItem.php` | 96 |
| `backend/app/Services/Cart/CartService.php` | 82 |
| `backend/app/Services/Cart/NoPriceConfiguredException.php` | 15 |
| `backend/app/Http/Middleware/CartSession.php` | 61 |
| `backend/app/Http/Controllers/Api/V1/Cart/CartController.php` | 211 |
| `backend/app/Http/Resources/V1/CartResource.php` | 37 |
| `backend/app/Http/Resources/V1/CartItemResource.php` | 65 |

### Frontend
None new — types and fetchers extended existing files.

## Files modified

### Backend
| File | Change |
|---|---|
| `backend/app/Models/User.php` | Added `carts()` HasMany and `activeCart()` HasOne (status='active'). |
| `backend/app/Http/Kernel.php` | Registered `'cart-session'` route middleware alias. |
| `backend/routes/api.php` | Added 6 cart routes inside a new `cart-session` middleware group; CartController import. |

### Frontend
| File | Change |
|---|---|
| `src/types/api.ts` | Added `CartItemKind`, `CartItemResource`, `CartTotals`, `CartResource`, `CartResponse`, `AddCartItemRequest`, `UpdateCartItemRequest`. |
| `src/lib/api.ts` | Added `headers?` field to `ApiOptions` so per-request headers are wired through the central `api()` helper. Added 6 typed cart fetchers (`fetchCart`, `postCartItem`, `putCartItem`, `deleteCartItem`, `postCartCoupon`, `deleteCartCoupon`); each accepts an optional sessionUuid and stamps it as `X-Cart-Session` for guests. |
| `src/hooks/useCart.ts` | Rewrote on top of React Query. `localStorage` only stores a guest UUID; cart contents always come from the server. Public surface preserved (`items`, `addItem`, `updateQty`, `removeItem`, `clearCart`, `subtotal`, `count`) so Cart/Header/Services/ServiceCategory/ServiceDetail/Checkout/Payment compile unchanged. New `addItem` accepts optional `brand_id`/`model_id`/`fuel_id` for vehicle-priced adds. Coupon mutations stub to a 501-equivalent error. |
| `src/pages/Services.tsx`, `src/pages/ServiceCategory.tsx`, `src/pages/ServiceDetail.tsx` | Added the three vehicle IDs to `addItem` calls so the server picks vehicle-specific prices when a vehicle is selected. Falls back to `service.base_price` when not. |

`src/components/Header.tsx` and `src/pages/Cart.tsx` were NOT
modified — they only read the legacy public cart surface, which the
rewrite preserves verbatim.

## Migration output

```
$ php artisan migrate --force
INFO  Running migrations.
  2026_05_03_120003_create_carts_table ............... 308ms DONE
  2026_05_03_120004_create_cart_items_table .......... 381ms DONE
```

## Schema verification (live MySQL)

```
mysql> SHOW COLUMNS FROM carts;
+--------------+----------------------------------------+------+-----+
| id           | bigint unsigned                        | NO   | PRI |
| user_id      | bigint unsigned                        | YES  | MUL |
| session_uuid | char(36)                               | YES  | UNI |
| currency     | varchar(3)                             | NO   |     |
| expires_at   | timestamp                              | NO   |     |
| status       | enum('active','converted','abandoned') | NO   | MUL |
| created_at   | timestamp                              | YES  |     |
| updated_at   | timestamp                              | YES  |     |
+--------------+----------------------------------------+------+-----+

mysql> SHOW COLUMNS FROM cart_items;
+---------------------+----------------------+------+-----+
| id                  | bigint unsigned      | NO   | PRI |
| cart_id             | bigint unsigned      | NO   | MUL |
| service_id          | bigint unsigned      | YES  | MUL |
| package_id          | bigint unsigned      | YES  |     |
| product_id          | bigint unsigned      | YES  |     |
| brand_id            | bigint unsigned      | YES  | MUL |
| model_id            | bigint unsigned      | YES  | MUL |
| fuel_id             | bigint unsigned      | YES  | MUL |
| quantity            | smallint unsigned    | NO   |     |
| unit_price_snapshot | decimal(10,2)        | NO   |     |
| meta                | longtext             | YES  |     |
| created_at          | timestamp            | YES  |     |
| updated_at          | timestamp            | YES  |     |
+---------------------+----------------------+------+-----+
```

CHECK constraint `chk_cart_owner` (user_id IS NOT NULL OR
session_uuid IS NOT NULL) was added successfully — the wrap-in-
try/catch fallback was not exercised on this MariaDB 10.4.32 build.

## Route list (cart subset)

```
GET|HEAD api/v1/cart                        Api\V1\Cart\CartController@show
POST     api/v1/cart/items                  Api\V1\Cart\CartController@addItem
PUT      api/v1/cart/items/{item}           Api\V1\Cart\CartController@updateItem
DELETE   api/v1/cart/items/{item}           Api\V1\Cart\CartController@removeItem
POST     api/v1/cart/coupon                 Api\V1\Cart\CartController@applyCoupon       (501 stub)
DELETE   api/v1/cart/coupon                 Api\V1\Cart\CartController@removeCoupon      (501 stub)
```

`php artisan route:list --path=api --json | jq length` → **33**
(16 + 7 + 4 + 6).

## Curl smoke chain

### GUEST flow (X-Cart-Session UUID, no Bearer)

```
$ GET /cart  -H 'X-Cart-Session: 11111111-2222-3333-4444-555555555555'
HTTP 200
{cart:{id:1, status:'active', currency:'INR', is_user_cart:false,
       expires_at:'2026-06-01T...', item_count:0, items:[],
       totals:{subtotal:0,discount:0,coupon:null,tax:0,total:0}}}

$ POST /cart/items {kind:'service', ref_id:1,
                   vehicle:{brand_id:1, model_id:1, fuel_id:1},
                   meta:{title:'Test', category_slug:'general'}}
HTTP 200
{cart:{ ..., item_count:1, items:[
  { id:1, kind:'service', ref_id:1, display_title:'Battery Charging',
    unit_price_snapshot:1500, quantity:1, line_total:1500,
    vehicle:{brand_id:1, model_id:1, fuel_id:1}, meta:{...} }
], totals:{subtotal:1500, ..., total:1500}}}

$ POST /cart/items same kind+ref_id+vehicle (re-add)
HTTP 200 — quantity bumps to 2, line_total 3000  ✓ same-cart dedup

$ PUT /cart/items/1 {quantity:3}
HTTP 200 — quantity 3, line_total 4500             ✓

$ PUT /cart/items/1 {vehicle:{brand_id:1, model_id:1, fuel_id:2}}
HTTP 200 — unit_price_snapshot 1500 → 1575 (re-snapshot)  ✓ §6.6

$ POST /cart/items {kind:'service', ref_id:4}     ← service has no base_price
HTTP 422 {message:"No price configured for this vehicle."}    ✓

$ POST /cart/items {kind:'package', ref_id:1}
HTTP 422 {message:"Only service items supported (Phase 2.6)"}  ✓

$ POST /cart/coupon {code:'WELCOME10'}
HTTP 501 {message:"Not implemented yet (Phase 2.6 — coupons table not migrated)"}  ✓

$ DELETE /cart/items/1
HTTP 200 — empty cart
```

### USER flow (Bearer token from Phase 2.1)

```
$ POST /auth/lead-capture + verify-otp → token TOKEN_A
$ GET /cart  -H 'Authorization: Bearer TOKEN_A'    ← NO X-Cart-Session
HTTP 200 {cart:{id:2, is_user_cart:true, expires_at:90 days out, ...}}  ✓

$ POST /cart/items {kind:'service', ref_id:1,
                    vehicle:{brand_id:1, model_id:1, fuel_id:3}}
HTTP 200 — unit_price_snapshot 1425 (vehicle (1,1,3) row), quantity 1  ✓

$ With token TOKEN_B (different user): GET /cart
HTTP 200 {cart:{id:3, item_count:0}}                ✓ cross-user isolation
```

### Negative — missing session

```
$ GET /cart  (no Authorization, no X-Cart-Session)
HTTP 400 {message:"Cart session required (auth or X-Cart-Session)"}  ✓

$ GET /cart  -H 'X-Cart-Session: not-a-uuid'
HTTP 400 {message:"Invalid X-Cart-Session header"}                    ✓
```

## Frontend

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2161 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-BlUgo5JH.css   104.77 kB │ gzip:  17.20 kB
dist/assets/index-CbOgGgqv.js    735.04 kB │ gzip: 194.31 kB
✓ built in 37.38s
```

Browser DevTools smoke not driven from this session. Static check
confirms:
- `<Header>` reads `count` from new hook — preserved field name.
- `<Cart>` reads `items`, `subtotal`, `count`, `updateQty`,
  `removeItem`, `clearCart` — all preserved.
- `<Services>`, `<ServiceCategory>`, `<ServiceDetail>` add the three
  optional vehicle IDs; older addItem call signature accepted via
  the optional fields.

## Same-cart re-add behavior

**Chosen: dedup by (cart_id, service_id, brand_id, model_id, fuel_id)
tuple — bump `quantity`, re-snapshot `unit_price_snapshot`.**

Rationale: the contract describes lines as service+vehicle bundles,
not free-form carts. Two identical services for the same vehicle
are conceptually quantity 2 of one line, not two separate lines.
The unit price re-snapshots on bump so a price-config change
between the first and second add is reflected (worst case: cart
shows a slightly higher line total than the user remembers — never
a stale lower one).

Documented inline in `CartController::addItem`.

## Deviations

1. **Coupon endpoints are 501 stubs.** `/cart/coupon` POST and
   DELETE exist (route count = 33 matches the brief) but reply
   `501 {message:"Not implemented yet (Phase 2.6 — coupons table
   not migrated)"}`. `CartService::totalsFor` always reports
   `coupon=null, discount=0`. Lights up in 2.6 alongside the
   coupons table.

2. **package_id and product_id columns lack FK constraints in
   2.3.** Per brief, the `service_packages` and `products` tables
   land in 2.6; the FKs to them are added then. Cart-write
   endpoints reject `kind=package|product` with 422 today.

3. **`is_user_cart` field added to CartResource** beyond what
   contract §4.3 required. Useful for the frontend to decide
   whether to send `X-Cart-Session` on subsequent requests; cheap
   to compute (`user_id !== null`). Documented inline in
   CartResource.

4. **`updateQty(id, qty)` and `removeItem(id)` accept the
   stringified server item id.** The legacy hook used a
   `serviceId-timestamp` composite as the row key; the new hook
   passes through the numeric server id (string-coerced on the
   `.id` field of the returned CartItem). Cart.tsx and Header.tsx
   compile because they only treat `id` as opaque.

5. **`clearCart()` deletes items one-by-one** rather than calling a
   `DELETE /cart` endpoint. The contract didn't reserve a
   "wipe cart" route in 2.3; commit 2.4 will likely add one
   alongside `/cart/merge`. The N×DELETE path is correct and
   idempotent today.

6. **`useTokenFlag` hook listens on `acr-token-updated`** so the
   query key flips between `["cart","user"]` and `["cart","guest"]`
   on login/logout — guarantees an immediate refetch instead of
   serving the wrong cart from React Query's cache.

7. **`addItem` is fire-and-forget** (returns void). The legacy
   hook's signature was synchronous, and changing it to return a
   promise would force every call site to handle errors. Errors
   `console.warn` with the API message; the cart query refetches
   automatically on visibility change so transient failures
   self-heal.

8. **`src/pages/ServiceDetail.tsx` carries pre-2.3 drift in the
   diff** (~70 inserts unrelated to cart wiring). These are
   accumulated working-tree changes from earlier phases that
   were never committed. The 2.3-specific change in this file is
   only the three `brand_id`/`model_id`/`fuel_id` fields on
   `addItem`; the rest of the diff is prior-phase work being
   captured by git for the first time. Same pattern as
   `composer.json`/`composer.lock` in 2.1.1.

## Single commit

`d810eeba11482c1b28a21fa03ffd8aa61735bf39` — 20 files, 1474
insertions, 135 deletions.
