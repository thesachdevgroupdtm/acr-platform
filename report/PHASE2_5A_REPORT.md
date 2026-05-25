# Phase 2.5a — Real Checkout / Orders / Payments

Replaces the client-side fake `ACR<timestamp>` flow with backend-persisted
orders. Implements `/PHASE2_CONTRACT.md` §2.6–§2.8 (orders, order_items,
payment_transactions), §3 (models), §4.4 (resources), §5.4 (10 endpoints).

Commit: see "Commit" below.

---

## 1. Files created / modified

### Backend — new
| Path | Purpose |
|---|---|
| `backend/database/migrations/2026_05_04_120001_create_service_centers_table.php` | Table + 4-row seed (Moti Nagar, Gurugram, Noida, Okhla). |
| `backend/database/migrations/2026_05_04_120002_create_orders_table.php` | orders. 5-state workflow + 5 indexes. |
| `backend/database/migrations/2026_05_04_120003_create_order_items_table.php` | order_items. Snapshots pricing + service title. |
| `backend/database/migrations/2026_05_04_120004_create_payment_transactions_table.php` | payment_transactions. Reserved gateway columns. |
| `backend/app/Models/ServiceCenter.php` | Eloquent model; `scopeActive`. |
| `backend/app/Models/Order.php` | Status constants, scopes, `canBeCancelledBy`, `transitionTo` state machine. |
| `backend/app/Models/OrderItem.php` | Snapshot fields + relations. |
| `backend/app/Models/PaymentTransaction.php` | Method/status constants. |
| `backend/app/Services/Order/OrderNumberService.php` | `ACR-{YEAR}-{NNNNN}` atomic generator (lockForUpdate). |
| `backend/app/Services/Order/FakeBookingGuard.php` | Phone/rate/dup/high-risk heuristics (D-2.5a-8). |
| `backend/app/Services/Order/RateLimitedException.php` | → 429. |
| `backend/app/Services/Order/DuplicateBookingException.php` | → 422. |
| `backend/app/Services/Order/PhoneNotVerifiedException.php` | → 403. |
| `backend/app/Services/Checkout/CheckoutService.php` | `quote()`, `placeOrder()`, `cancelOrder()` pipeline. |
| `backend/app/Http/Resources/V1/ServiceCenterResource.php` | |
| `backend/app/Http/Resources/V1/OrderResource.php` | Omits `is_high_risk` from user response. |
| `backend/app/Http/Resources/V1/OrderItemResource.php` | |
| `backend/app/Http/Resources/V1/PaymentTransactionResource.php` | |
| `backend/app/Http/Controllers/Api/V1/Public/ServiceCentersController.php` | `GET /service-centers` (public). |
| `backend/app/Http/Controllers/Api/V1/Checkout/CheckoutController.php` | `POST /checkout/quote`, `POST /checkout/place-order`. `PREFERRED_TIME_OPTIONS` const. |
| `backend/app/Http/Controllers/Api/V1/User/OrderController.php` | `GET/POST /user/orders/*`. Cross-user → 404 (no leakage). |
| `backend/app/Console/Commands/AutoConfirmOrdersCommand.php` | `orders:auto-confirm {--minutes=120}`. |

### Backend — modified
| Path | Why |
|---|---|
| `backend/app/Models/User.php` | Added `orders()` hasMany. |
| `backend/app/Console/Kernel.php` | Registered `orders:auto-confirm` on `everyMinute()->withoutOverlapping(60)`. |
| `backend/app/Exceptions/Handler.php` | Wired the 3 domain exceptions to typed JSON responses. |
| `backend/routes/api.php` | +6 routes (3 controllers). |

### Frontend — new
| Path | Purpose |
|---|---|
| `src/hooks/useServiceCenters.ts` | React Query hook, 5 min staleTime. |
| `src/hooks/useOrders.ts` | `useOrdersList`, `useOrderDetail`, `useCancelOrder`. |
| `src/pages/BookingConfirmation.tsx` | Reads real order via `useOrderDetail`. Reachable as `booking-confirmation-{id}`. |
| `src/pages/OrderDetail.tsx` | Full detail + cancel modal. Reachable as `order-{id}`. |

### Frontend — modified
| Path | Why |
|---|---|
| `src/types/api.ts` | Added `OrderResource`, `PlaceOrderRequest`, `CheckoutQuoteRequest/Response`, `OrdersListResponse`, `ServiceCenterResource`, `OrderStatus`, `PaymentStatus`, `PaymentMethod`, `PREFERRED_TIME_OPTIONS`, `MORNING/AFTERNOON/EVENING_SLOTS`. |
| `src/lib/api.ts` | Added 6 typed fetchers: `fetchServiceCenters`, `postCheckoutQuote`, `postPlaceOrder`, `fetchOrders`, `fetchOrder`, `postCancelOrder`. |
| `src/config/features.ts` | `offlineCheckout: true` (flipped); added `couponsLit: false`, `paymentGateway: false`. `checkoutFlow` / `bookingsList` untouched (stay true). |
| `src/pages/Checkout.tsx` | Rewrote: real `postPlaceOrder` flow + 3-row × 2-slot grid + service-center dropdown sourced from API + on-success cart invalidate + navigate to confirmation. |
| `src/pages/MyBookings.tsx` | Rewrote: reads `useOrdersList` instead of `user.bookings`; per-row cancel CTA gated to `status==='pending'`. |
| `src/App.tsx` | Routes `order-{id}` → OrderDetail, `booking-confirmation-{id}` → BookingConfirmation. |

`src/pages/Payment.tsx` is now bypassed by the new Checkout flow (which submits
directly to `/checkout/place-order`). The file is intentionally not deleted —
Phase 2.6 cleanup will remove it alongside the obsolete `acr_checkout_v1`
coupon code path.

`src/data/businessData.ts` is untouched per the constraint. Phase 2.6 will
remove `LOCATIONS` once the frontend has fully migrated to `useServiceCenters`.

---

## 2. Migrations applied

```
INFO  Running migrations.
2026_05_04_120001_create_service_centers_table ......... 242ms DONE
2026_05_04_120002_create_orders_table .................. 218ms DONE
2026_05_04_120003_create_order_items_table ............. 266ms DONE
2026_05_04_120004_create_payment_transactions_table ....  73ms DONE
```

### `service_centers` columns
```
id, slug, name, address, phone, email, city, state, pincode,
latitude, longitude, is_active, sort_order, created_at, updated_at
```
Seeded rows:
```
[
  {"id":1,"slug":"moti-nagar","name":"Moti Nagar","city":"Delhi"},
  {"id":2,"slug":"gurugram","name":"Gurugram","city":"Gurugram"},
  {"id":3,"slug":"noida","name":"Noida","city":"Noida"},
  {"id":4,"slug":"okhla","name":"Okhla","city":"Delhi"}
]
```

### `orders` columns
```
id, order_number, user_id, service_center_id, coupon_id,
status, payment_status,
name_snapshot, phone_snapshot, email_snapshot, address,
vehicle_snapshot, preferred_date, preferred_time,
subtotal, discount, tax, total, notes, is_high_risk,
placed_at, confirmed_at, in_service_at, completed_at,
cancelled_at, cancelled_reason, created_at, updated_at
```
Indexes: `(user_id,status)`, `order_number`, `(phone_snapshot,created_at)`,
`(status,created_at)`, `preferred_date`.

### `order_items` columns
```
id, order_id, service_id, package_id, product_id,
brand_id, model_id, fuel_id,
service_title_snapshot, quantity,
unit_price_snapshot, line_total_snapshot, meta,
created_at, updated_at
```
Index: `order_id`.

### `payment_transactions` columns
```
id, order_id, method, status, amount,
gateway_txn_id, gateway_response, paid_at,
refunded_at, refunded_amount, created_at, updated_at
```
Indexes: `order_id`, `status`.

---

## 3. Models + relations

| Model | Relations |
|---|---|
| `User` | `orders()` hasMany Order (added). |
| `Order` | `user()`, `items()`, `serviceCenter()`, `payments()`. State machine in `transitionTo()`. Cancel rule in `canBeCancelledBy()`. |
| `OrderItem` | `order()`, `service()`, `brand()`, `carModel()`, `fuel()`. |
| `PaymentTransaction` | `order()`. |
| `ServiceCenter` | `scopeActive()`. |

---

## 4. Service walkthrough

### `OrderNumberService::generate()`
Atomic. Wraps in `DB::transaction(... lockForUpdate())` so concurrent
placements never collide on the next sequence. Resets to `00001` on year
roll-over (queries `LIKE 'ACR-{YYYY}-%'`).

### `CheckoutService`
- `quote(Cart, $checkoutData)`: pure compute. Subtotal from items, GST 18%,
  total. No DB writes. Returns shape with `breakdown_lines` for the
  frontend's order-summary panel. Coupon math is a TODO marker for Phase 2.5b.
- `placeOrder(Cart, $checkoutData, User)`:
  1. eager-load items + service/brand/model/fuel
  2. `FakeBookingGuard::enforce()` (mutates `$checkoutData['is_high_risk']`)
  3. `quote()` for totals
  4. `OrderNumberService::generate()`
  5. `Order::create()` with snapshots (placed_at = now())
  6. `OrderItem::create()` per cart item
  7. `PaymentTransaction::create()` with `method='cash_at_center'`,
     `status='pending'`, `amount=order.total`
  8. Cart status → `converted`, `expires_at = now()`
  9. Eager-load all relations on the order
  10. `Log::info('Order placed', …)` and return
- `cancelOrder(Order, User, ?reason)`:
  - `canBeCancelledBy()` gate (owner + status='pending')
  - `Order::transitionTo(CANCELLED, $reason)` — sets `cancelled_at`,
    `cancelled_reason`
  - `Log::info('Order cancelled by user', …)`

### `FakeBookingGuard::enforce()`
Order of checks:
1. **A — verified phone**: `!is_verified_phone` → `PhoneNotVerifiedException` (403).
2. **B — 60 min rate limit**: ≥3 orders in last 60 min by phone → `RateLimitedException` (429).
3. **B — 24 hr rate limit**: ≥5 orders in last 24 hr by phone → `RateLimitedException` (429).
4. **C — duplicate**: same phone + same primary service + same date + same
   slot in last 30 min → `DuplicateBookingException` (422).
5. **High-risk flag** (does not block):
   - 60-min count ≥2, or
   - cart total > 50 000 AND user younger than 24 hr.
   - Sets `$checkoutData['is_high_risk'] = true` so `placeOrder()` writes
     `orders.is_high_risk`. Logged via `Log::warning('High-risk order flagged', …)`.

---

## 5. Scheduled command + cron

`AutoConfirmOrdersCommand`:
- Signature: `orders:auto-confirm {--minutes=120}`
- Bulk update: `WHERE status='pending' AND created_at < now()-INTERVAL :minutes MINUTE`
  → `status='confirmed'`, `confirmed_at = now()`.
- Logs to `laravel.log` and prints to stdout.

Registered in `App\Console\Kernel::schedule()`:
```
$schedule->command('orders:auto-confirm')
  ->everyMinute()
  ->withoutOverlapping(60);
```

**Hostinger production cron entry (operator action during deploy):**
```
* * * * * cd /home/<USER>/public_html/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Routes

`php artisan route:list` count post-commit: **45** (was 39; +6 new).

New routes:
```
GET    /api/v1/service-centers                Public\ServiceCentersController@index
POST   /api/v1/checkout/quote                 Checkout\CheckoutController@quote
POST   /api/v1/checkout/place-order           Checkout\CheckoutController@placeOrder
GET    /api/v1/user/orders                    User\OrderController@index
GET    /api/v1/user/orders/{order}            User\OrderController@show
POST   /api/v1/user/orders/{order}/cancel     User\OrderController@cancel
```
Throttle wiring: `service-centers` → `public-read`; checkout → `user-write`;
orders read → `user-read`; cancel → `user-write`.

---

## 7. Verification chain (A–K, all green)

Token issued for verified user `phone=9876543210`.

| | Scenario | Outcome |
|---|---|---|
| **A** | `GET /service-centers` | `HTTP 200`; 4 centers. |
| **B** | `POST /checkout/quote` (1 item, slot, date, center=1) | `HTTP 200`; subtotal 2325, tax 418.5, total 2743.5, gst_pct 18. |
| **C** | `POST /checkout/place-order` | `HTTP 201`; `order_number=ACR-2026-00001`, status `pending`, payment_status `pending`, vehicle_snapshot populated, 1 OrderItem, 1 PaymentTransaction (cash_at_center / pending / 2743.5), cart marked `converted`. |
| **D** | `GET /user/orders` | `HTTP 200`; 1 order (newest first), pagination present. |
| **E** | `GET /user/orders/1` | `HTTP 200`; full detail incl. items + payments + service_center. |
| **F** | `POST /user/orders/1/cancel {reason:"Test cancel"}` | `HTTP 200`; status `cancelled`, `cancelled_at` populated, reason persisted. |
| **G** | Cancel again on already-cancelled order | `HTTP 403`; `"This order cannot be cancelled. Already confirmed or in another state."` |
| **I** | Place order with same payload (same slot+service+phone) | `HTTP 422`; `"Duplicate booking detected. You already booked this service for this slot recently."` |
| **H** | Place 3 orders within 60 min, then 4th | `HTTP 201, 201, 201, 429`. 4th: `"Too many orders in the last hour. Please try again later."` |
| **J** | User B fetches User A's order 1/2/3 | `HTTP 404, 404, 404`. User B's own list `HTTP 200` with 0 orders. |
| **K** | Backdate order to 3hr ago, run `php artisan orders:auto-confirm` | `Auto-confirmed 1 orders.` Order status flips `pending → confirmed`, `confirmed_at` set. |

Order placed in run order: 1 (cancelled), 2 (auto-confirmed in K), 3 (pending). `order_number` increments correctly.

---

## 8. Frontend changes

- **Types**: `OrderStatus`, `PaymentStatus`, `PaymentMethod`, `OrderResource`,
  `OrderItemResource`, `PaymentTransactionResource`, `ServiceCenterResource`,
  `OrdersListResponse`, `OrderResponse`, `CheckoutQuoteRequest/Response`,
  `PlaceOrderRequest`, `ServiceCentersResponse`, `PREFERRED_TIME_OPTIONS`,
  `MORNING/AFTERNOON/EVENING_SLOTS`.
- **Fetchers**: 6 added in `src/lib/api.ts`.
- **Hooks**: `useServiceCenters`, `useOrdersList`, `useOrderDetail`,
  `useCancelOrder`.
- **Pages**:
  - `Checkout.tsx`: real `postPlaceOrder` flow. Slot UI = 3 rows × 2 buttons
    (D-2.5a-1) with strict en-dash slot strings; service center dropdown
    populated from `useServiceCenters()`, pre-selected from
    `acr_booking_ctx_v1.location` slug match. Phone field is read-only
    (auth identity). Coupon UI hidden by `FEATURES.couponsLit`. On success:
    invalidate `cart` + `orders` queries, reset draft, navigate to
    `booking-confirmation-{id}`. On 429/422/403: inline error, no navigation.
  - `BookingConfirmation.tsx` (NEW): reads via `useOrderDetail(id)`. Renders
    real `ACR-2026-NNNNN` number, services, center, slot, total + "Pay at
    Service Center" notice + CTAs to MyBookings / Home.
  - `MyBookings.tsx`: reads `useOrdersList({ per_page: 50 })`. Color-coded
    status badge (`pending=amber, confirmed=primary, in_service=indigo,
    completed=neutral-900, cancelled=neutral-400`). Cancel CTA only when
    `status==='pending'`. View Details → `order-{id}`.
  - `OrderDetail.tsx` (NEW): full sections (vehicle, items, schedule, address,
    notes, totals, payment status). Cancel modal with optional reason.
- **Routing** (`App.tsx`): `order-{id}` → `OrderDetail`,
  `booking-confirmation-{id}` → `BookingConfirmation`.
- **Feature flags**: `offlineCheckout: true` (flipped), `couponsLit: false`,
  `paymentGateway: false`.

---

## 9. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2160 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-TpwbaHP_.css  105.69 kB │ gzip:  17.34 kB
dist/assets/index-DABeA1HP.js   759.85 kB │ gzip: 200.47 kB
✓ built in 40.54s
```

Bundle warning is the existing >500 kB threshold; code-splitting is a
Phase 2.6 cleanup item.

The pre-existing `EstimateProcess.tsx` "duplicate case" warning is unrelated
to this commit (it's been there since pre-Phase-2.5).

---

## 10. Commit

`feat(api,frontend): Phase 2.5a — real Checkout/Orders/Payments backend …`
(see commit hash printed by `git log -1 --oneline` after this report lands).

---

## 11. Deviations

- **Slot validation uses Rule::in on the canonical en-dash strings**, not
  IDs. This is the cheapest path that survives a frontend mistake (sending a
  hyphen instead of en-dash → 422 immediately) and keeps the column a plain
  VARCHAR rather than a separate slot table. If a slot capacity model lands
  in Phase 2.6, the en-dash strings remain the natural primary key.
- **`coupon_id` declared as plain `unsignedBigInteger` (no FK)** — the
  `coupons` table doesn't exist yet. Phase 2.5b will add the table and the
  FK constraint in the same migration to avoid an orphaned constraint.
- **Vehicle snapshot is a single object on `orders.vehicle_snapshot`** even
  though `order_items` separately stores `brand_id/model_id/fuel_id`. This
  reflects the Phase 2.5a invariant that all cart items share a single
  vehicle (Quick-Estimate flow). Cross-vehicle carts (Phase 2.6+) would
  promote the snapshot to per-item.
- **Payment.tsx not deleted** — the new Checkout bypasses it but the file
  is left in place to keep the diff minimal. Phase 2.6 cleanup batch
  removes it.
- **ServiceCenter is technically a Phase 2.6 deliverable** pulled forward
  here because `orders.service_center_id` needs an FK target. Phase 2.6
  may extend the table (opening hours, photos) without re-migrating it.
- **`vehicle_snapshot.brand_slug/model_slug/fuel_slug`** included in addition
  to ids+names. Cheap and unblocks SEO-friendly receipt URLs without a
  follow-up migration.
- **Cancellation does not refund payment rows** in Phase 2.5a since the
  only method is `cash_at_center` / `status='pending'` — there's nothing
  to refund. When real-gateway payments land in Phase 4+, the cancel path
  needs an explicit `succeeded → refunded` transition.

---

## 12. Outstanding items for Phase 2.5b (coupons)

- Create `coupons` table migration + Coupon model.
- Drop the deferred FK on `orders.coupon_id` and constrain to `coupons.id`.
- Replace the 501s on `POST/DELETE /cart/coupon` with real apply/remove
  logic (compute discount in `CartService::totalsFor` + write
  `cart.coupon_id`).
- Extend `CheckoutService::quote()` to consume cart's applied coupon
  (or a body-supplied `coupon_code`), compute `discount`, recompute `tax`
  on `subtotal - discount`, recompute `total`.
- Wire `coupon_id` through to `Order::create()` in `placeOrder()`.
- Frontend: flip `FEATURES.couponsLit = true`, surface the coupon input
  in Checkout's order-summary panel + show the discount line in the
  totals breakdown.
- Add a Coupon `belongsTo` relation to Order model.

## 13. Outstanding items for Phase 2.6 (cleanup batch)

- Delete `src/pages/Payment.tsx` (now unreachable).
- Delete the legacy `LOCATIONS` constant from `src/data/businessData.ts`
  (replaced by `useServiceCenters()`).
- Delete `BookingsComingSoon.tsx` and `CheckoutComingSoon.tsx` —
  feature flags that would route to them are now true.
- Remove the `addBooking` shim from `useAuth` (legacy `AcrUser.bookings`
  array is dead code).
- Migrate `package_id` and `product_id` columns on `cart_items` /
  `order_items` to real FKs (depends on `service_packages` / `products`
  tables landing).
- Code-splitting on the 760 KB main chunk (existing build warning).
