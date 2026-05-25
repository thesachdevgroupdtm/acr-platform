# Phase 4.2 — Filament Admin Resources + Widget + Tests

**Date:** 2026-05-07
**Scope:** Build 5 Filament admin resources (Order, User,
Coupon, ServiceCategory, Service), one OperationsStats
dashboard widget, 27 new Pest tests, and one Playwright admin
smoke spec.
**Status:** ✅ All deliverables green.
- Backend: **58 Pest tests pass** (31 from Phase 4.1 + 27 new),
  221 assertions.
- Frontend: **3/3** existing smoke pass (regression-clean) +
  **2/2** new admin smoke pass.

---

## 1. Files created

### Filament resources
| File | Purpose |
|---|---|
| `backend/app/Filament/Resources/OrderResource.php` | Order list/view/edit with status-aware Confirm/Cancel/MarkCompleted actions |
| `backend/app/Filament/Resources/OrderResource/Pages/ListOrders.php` | List page (no Create header — orders only created via customer flow) |
| `backend/app/Filament/Resources/OrderResource/Pages/EditOrder.php` | Edit page (header actions reduced to ViewAction; no Delete) |
| `backend/app/Filament/Resources/OrderResource/Pages/ViewOrder.php` | View page (default scaffold) |
| `backend/app/Filament/Resources/UserResource.php` | User list/edit; password absent, phone conditional, ToggleAdmin self-protected |
| `backend/app/Filament/Resources/UserResource/Pages/{ListUsers,CreateUser,EditUser}.php` | Standard pages (Edit page header Delete removed) |
| `backend/app/Filament/Resources/CouponResource.php` | Coupon list/create/edit; code uppercase mutator; description as RichEditor T&C |
| `backend/app/Filament/Resources/CouponResource/Pages/{ListCoupons,CreateCoupon,EditCoupon}.php` | Standard pages |
| `backend/app/Filament/Resources/ServiceCategoryResource.php` | Reorderable on `position`; conditional delete; slug auto-fill on create |
| `backend/app/Filament/Resources/ServiceCategoryResource/Pages/{ListServiceCategories,CreateServiceCategory,EditServiceCategory}.php` | Standard pages |
| `backend/app/Filament/Resources/ServiceResource.php` | Service list/create/edit; category select; slug auto-fill; conditional delete |
| `backend/app/Filament/Resources/ServiceResource/Pages/{ListServices,CreateService,EditService}.php` | Standard pages |

### Widget
| File | Purpose |
|---|---|
| `backend/app/Filament/Widgets/OperationsStats.php` | 4-stat dashboard widget; 60s polling |

### Factories (for Pest tests)
| File | Purpose |
|---|---|
| `backend/database/factories/OrderFactory.php` | Minimal Order; default status=pending |
| `backend/database/factories/CouponFactory.php` | Minimal Coupon; default percent/10% |
| `backend/database/factories/ServiceCenterFactory.php` | Minimal ServiceCenter; for Order's FK |

### Tests (27 new)
| File | Tests | Category |
|---|---|---|
| `backend/tests/Feature/Admin/Resources/OrderResourceTest.php` | 2 | Access control |
| `backend/tests/Feature/Admin/Resources/UserResourceTest.php` | 2 | Access control |
| `backend/tests/Feature/Admin/Resources/CouponResourceTest.php` | 2 | Access control |
| `backend/tests/Feature/Admin/Resources/ServiceCategoryResourceTest.php` | 2 | Access control |
| `backend/tests/Feature/Admin/Resources/ServiceResourceTest.php` | 2 | Access control |
| `backend/tests/Feature/Admin/Actions/OrderActionsTest.php` | 8 | Action-level business logic |
| `backend/tests/Feature/Admin/Actions/UserActionsTest.php` | 2 | Action-level (Toggle Admin + self-protection) |
| `backend/tests/Feature/Admin/Actions/CouponDataIntegrityTest.php` | 2 | Action-level (uppercase + RichEditor) |
| `backend/tests/Feature/Admin/SecurityTest.php` | 3 | Security boundaries |
| `backend/tests/Feature/Admin/Widgets/OperationsStatsTest.php` | 2 | Widget data integrity + polling |

### Frontend (Playwright)
| File | Purpose |
|---|---|
| `tests/e2e/admin-smoke.spec.ts` | 2 admin smoke specs against Laravel :8000 |

### Documentation
| File | Purpose |
|---|---|
| `PHASE4_2_AUDIT.md` | Read-only schema audit (PART A) |
| `PHASE4_2_REPORT.md` | This file |
| `PHASE4_2_MANUAL_CHECKLIST.md` | Operator manual verification |

## 2. Files modified

| File | Change |
|---|---|
| `backend/app/Providers/Filament/AdminPanelProvider.php` | Added `OperationsStats::class` to `widgets()` array (top position) |
| `playwright.config.ts` | Added `admin` project pointing to `127.0.0.1:8000`; tightened smoke `testMatch` regex to `[\\/]smoke\.spec\.ts$` so it doesn't pick up `admin-smoke.spec.ts` |

## 3. Files deleted

| File | Reason |
|---|---|
| `backend/app/Filament/Resources/OrderResource/Pages/CreateOrder.php` | Orders are created exclusively via the customer checkout flow. No admin create page. |

---

## 3. PART A — Schema audit summary

Full findings are in `PHASE4_2_AUDIT.md`. Key conclusions:

**Filament redirect probe (D-4.2-13 finding):**
- Authenticated non-admin → **403 Forbidden** (not 302).
- Unauthenticated → 302 to `/admin/login`.

Tests use `$this->actingAs($customer)->get(...)->assertForbidden()`.

**Schema column substitutions (used throughout resources +
tests):**
| Task spec | Actual DB column | Resource |
|---|---|---|
| `cancellation_reason` | `cancelled_reason` | Order |
| `services_snapshot` | `items()` relationship | Order (rendered as readable list) |
| `address_snapshot` | `address` (text) | Order |
| `title` | `name` | Coupon |
| `value` | `discount_value` | Coupon |
| `min_order` | `min_order_value` | Coupon |
| `max_uses` | `usage_limit` | Coupon |
| `expires_at` (datetime) | `expiry_date` (date) | Coupon (DatePicker not DateTimePicker) |
| `terms` (separate column) | (none) → `description` repurposed | Coupon |
| `display_order` | `position` | ServiceCategory |
| `service_category_id` | `category_id` | Service |
| `duration` | `time_takes` + `time_unit` | Service |

No migrations were created (HARD CONSTRAINT). All resources
work against the existing schema.

---

## 4. PART B — OrderResource

**Structure:** 4 form sections (Customer, Booking, Snapshots,
Cancellation) + 8 table columns + 5 filters + 5 actions.

**Status transition matrix (D-4.2-9):**
| Current | Action | Visible? | Sets |
|---|---|---|---|
| pending | Confirm | ✓ | status=confirmed, confirmed_at=now |
| pending | Cancel (reason ≥10 chars) | ✓ | status=cancelled, cancelled_at, cancelled_reason |
| confirmed | Cancel (reason) | ✓ | status=cancelled, … |
| confirmed | MarkCompleted | ✓ | status=completed, completed_at=now |
| in_service | (none) | — | — (reserved for future garage-floor view) |
| completed/cancelled | (terminal) | ✗ all hidden | — |

**Snapshot rendering:** `services_snapshot` is replaced by
querying `$record->items()` and printing `Title × Qty — ₹Total`
per line. `vehicle_snapshot` JSON renders as `Brand Model
(Fuel)`. NEVER raw JSON.

**No bulk actions, no delete** — data integrity hard rule.

---

## 5. PART C — UserResource

**Form sections:** Profile, Status, Meta (collapsed).

**Security guarantees enforced in code:**
1. Password field is **absent** from the form schema (D-4.2-4).
2. Phone field is `->disabled()` when `is_verified_phone === true`
   (D-4.2-5), with helper text directing user to support.
3. Toggle Admin action is `->disabled()` when `$record->id ===
   auth()->id()` (D-4.2-10), with tooltip "Cannot toggle your
   own admin status." A defense-in-depth check inside the
   action callback also no-ops the operation if a self-toggle
   somehow reaches the handler.

**No delete action** — orders.user_id is `restrictOnDelete`.

---

## 6. PART D — CouponResource

**Code uppercase (D-4.2-11):** `dehydrateStateUsing(fn ($state)
=> strtoupper($state))` on the code TextInput. The
`extraInputAttributes(['style' => 'text-transform: uppercase'])`
provides visual feedback while typing. Tested with mixed-case
input ("lowercase10" → saved as "LOWERCASE10").

**RichEditor T&C (D-4.2-7):** Filament's built-in `RichEditor`
on the `description` column (audit deviation: no separate
`terms` column exists, so `description` does double duty).
Toolbar restricted to legal-text essentials: bold, italic,
bulletList, orderedList, link, blockquote, h2, h3. Image,
attachFiles, codeBlock are not in the toolbar list.

**Visibility logic:** `max_discount` field is `->visible(fn
(Get $get) => $get('discount_type') === 'percent')`. The
discount-type Select is `->live()` to drive that reactivity.

---

## 7. PART E — ServiceCategoryResource

**Reorderable (D-4.2-8):** `->reorderable('position')` on the
table. Default sort is `position asc`. Note: column is
`position`, NOT `display_order` (audit §6).

**Slug auto-fill on create only:** `live(onBlur: true)` on the
`name` field with an `afterStateUpdated` callback that checks
`$operation === 'create'` and only then sets the slug. This
preserves the SEO sacrosanct rule (memory) — existing slugs
are never auto-rewritten.

**Conditional delete:** the `before()` callback counts
`$record->services()->count()` and either cancels the action
(with a red notification) or proceeds with confirmation.

---

## 8. PART F — ServiceResource

**Belongs-to:** `category_id` (NOT `service_category_id` —
audit §7). Select is searchable + preload, sorted by
ServiceCategory.position.

**Duration:** schema has `time_takes` (string) + `time_unit`
(string, e.g., 'minutes' / 'hours'); the form exposes both.
Display in the table: `time_takes time_unit` or `—` if null.

**Conditional delete:** blocks if `service_prices` rows exist
for this service (uses the existing `prices()` hasMany
relation).

---

## 9. PART G — OperationsStats widget

**Polling:** `protected static ?string $pollingInterval =
'60s';` (D-4.2-12).

**Stats:**
1. **Pending Orders** — `Order::where('status', 'pending')
   ->count()`. Uses `orders_status_created_at_index` (compound
   on status+created_at). Color: warning if > 0 else gray.
   URL: deep-links to OrderResource list filtered by
   status=pending.
2. **Today's Bookings** — `Order::whereDate('created_at',
   today())->count()`. Color: info.
3. **This Week's Revenue** — sum of `orders.total` where
   `status='completed'` AND `created_at` in
   `[startOfWeek, endOfWeek]`. Format: `₹` + `number_format`.
   Uses the same compound index. Color: success.
4. **Active Customers** — distinct `users` with at least one
   order in the last 30 days. EXISTS subquery on
   `orders.user_id` (uses `orders_user_id_status_index`).
   Color: primary.

**Performance baseline (PART L):**
- Combined widget queries: **28 ms** on current data
  (9 orders, 19 users) — well below the 500 ms target.
- Service list (25 rows with category eager-loaded): **6.5 ms**.
- All under 2× expected; baseline filed for future regression
  monitoring.

**Registered in AdminPanelProvider->widgets() array at top
position** (above `AccountWidget` and `FilamentInfoWidget`).

---

## 10. PART H — Tests

### Test counts per file
| File | Count |
|---|---|
| Resources/OrderResourceTest.php | 2 |
| Resources/UserResourceTest.php | 2 |
| Resources/CouponResourceTest.php | 2 |
| Resources/ServiceCategoryResourceTest.php | 2 |
| Resources/ServiceResourceTest.php | 2 |
| Actions/OrderActionsTest.php | 8 |
| Actions/UserActionsTest.php | 2 |
| Actions/CouponDataIntegrityTest.php | 2 |
| SecurityTest.php | 3 |
| Widgets/OperationsStatsTest.php | 2 |
| **Total new** | **27** |
| (Existing AdminAuthTest from 4.1) | 3 |
| **Total under tests/Feature/Admin/** | **30** |

### Verbatim Pest output (Admin tests only)

```
   PASS  Tests\Feature\Admin\Actions\CouponDataIntegrityTest
  ✓ it uppercases the coupon code on save
  ✓ it saves and retrieves rich-text terms in the description field

   PASS  Tests\Feature\Admin\Actions\OrderActionsTest
  ✓ it shows the Confirm action only when status is pending
  ✓ it hides the Confirm action when status is confirmed
  ✓ it Confirm action transitions pending to confirmed and stamps confirmed_at
  ✓ it Cancel action requires a reason of at least 10 characters
  ✓ it Cancel action stores reason and stamps cancelled_at
  ✓ it Mark Completed visibility requires confirmed status
  ✓ it Cancel action is allowed from confirmed status
  ✓ it terminal states (completed/cancelled) hide all transition actions

   PASS  Tests\Feature\Admin\Actions\UserActionsTest
  ✓ it Toggle Admin action flips is_admin from false to true
  ✓ it Toggle Admin action prevents self-modification (self-protection)

   PASS  Tests\Feature\Admin\AdminAuthTest
  ✓ it lets an admin user access the admin panel
  ✓ it blocks a non-admin user from the admin panel
  ✓ it defaults newly created users to is_admin=false (no panel access)

   PASS  Tests\Feature\Admin\Resources\CouponResourceTest
  ✓ it lets an admin access CouponResource list page
  ✓ it blocks a non-admin user from CouponResource list page

   PASS  Tests\Feature\Admin\Resources\OrderResourceTest
  ✓ it lets an admin access OrderResource list page
  ✓ it blocks a non-admin user from OrderResource list page

   PASS  Tests\Feature\Admin\Resources\ServiceCategoryResourceTest
  ✓ it lets an admin access ServiceCategoryResource list page
  ✓ it blocks a non-admin user from ServiceCategoryResource list page

   PASS  Tests\Feature\Admin\Resources\ServiceResourceTest
  ✓ it lets an admin access ServiceResource list page
  ✓ it blocks a non-admin user from ServiceResource list page

   PASS  Tests\Feature\Admin\Resources\UserResourceTest
  ✓ it lets an admin access UserResource list page
  ✓ it blocks a non-admin user from UserResource list page

   PASS  Tests\Feature\Admin\SecurityTest
  ✓ it does not expose a password field on the User edit form
  ✓ it marks the phone field read-only when the user is phone-verified
  ✓ it keeps the phone field editable when the user is unverified

   PASS  Tests\Feature\Admin\Widgets\OperationsStatsTest
  ✓ it computes pending orders correctly and uses 60s polling
  ✓ it computes today bookings count correctly

  Tests:    30 passed (101 assertions)
  Duration: 8.75s
```

### Factories created
- `OrderFactory` — populates all required Order columns;
  default status=pending, sane snapshots, ServiceCenter
  factory dependency.
- `CouponFactory` — default percent/10% with cap and unique
  code; tests override individual fields as needed.
- `ServiceCenterFactory` — default Delhi NCR center with
  unique slug.

---

## 11. PART I — Playwright admin smoke

`tests/e2e/admin-smoke.spec.ts` (2 specs):
1. **`admin login page renders without console errors`** —
   navigates to `/admin/login`, waits for an email/phone
   input to appear, asserts no non-extension console errors.
2. **`non-existent admin path returns a clean status (never
   500)`** — navigates to `/admin/nonexistent-resource`,
   asserts HTTP status ∈ {200, 302, 404}.

Wired into `playwright.config.ts` as a new `admin` project:
```
{
  name: 'admin',
  testMatch: /admin-smoke\.spec\.ts$/,
  use: { ...devices['Desktop Chrome'], baseURL: 'http://127.0.0.1:8000' },
}
```

Smoke testMatch tightened to `/[\\/]smoke\.spec\.ts$/` so the
`smoke` project does NOT also pick up `admin-smoke.spec.ts`.

---

## 12. PART J — Full test suite output

### Backend Pest (full)
```
Tests:    58 passed (221 assertions)
Duration: 12.56s
```

(31 from Phase 4.1 + 27 new = 58.)

### Frontend Playwright smoke (regression)
```
[smoke] tests\e2e\smoke.spec.ts:18:1  home page renders without console errors  ✓
[smoke] tests\e2e\smoke.spec.ts:44:1  clicking the Login button opens the auth modal  ✓
[smoke] tests\e2e\smoke.spec.ts:60:1  /payment routes to NotFound (no silent home redirect)  ✓
3 passed (16.1s)
```

### Frontend Playwright admin smoke (new)
```
[admin] admin-smoke.spec.ts › admin login page renders without console errors  ✓
[admin] admin-smoke.spec.ts › non-existent admin path returns a clean status (never 500)  ✓
2 passed (3.7s)
```

---

## 13. PART K — Manual checklist

`PHASE4_2_MANUAL_CHECKLIST.md` covers ~30 operator-verifiable
items. Highlights the operator should NOT skip: Toggle Admin
self-protection, password absent on edit form, drag-reorder
on categories, code uppercase mutation, RichEditor toolbar,
delete-blocked flows for categories/services with relations.

---

## 14. PART L — Performance baseline

| Query | Time | Notes |
|---|---|---|
| Widget: 4 stats combined | **28 ms** | Well below 500 ms target. |
| Service list (25 rows + category eager) | **6.5 ms** | |

These are acceptable baselines for current data volume (9
orders, 19 users, 40 services). With production data (~thousands
of orders), expect:
- Pending Orders count — index-only scan, < 5 ms.
- Today's Bookings — date range scan, < 30 ms.
- Active Customers — EXISTS subquery, < 50 ms on ~10K users.
- Service list — 25 rows with category eager-load, < 50 ms.

If any future timing spike exceeds 2× baseline, investigate
index usage with EXPLAIN.

---

## 15. Deviations

1. **Order status flow.** D-4.2-9 specifies `confirmed →
   completed`, but the Order model's `transitionTo()` enforces
   `confirmed → in_service → completed`. **Resolution:** the
   admin "Mark Completed" action bypasses `transitionTo()`
   and directly assigns `status='completed'` + `completed_at
   = now()`. The `in_service` state is reserved for a future
   garage-floor view. Admin can still see in_service orders
   in the list (they appear as a status badge), but the
   day-1 admin set has no transition into in_service.

2. **Filament redirect for non-admin = 403, not 302.** D-4.2-13
   prompted the probe: authenticated non-admins get a hard
   403 from Filament's `canAccessPanel()` gate. Tests use
   `assertForbidden()` accordingly.

3. **Coupon T&C uses `description`.** No `terms` column
   exists in the `coupons` table. Per task fallback ("terms
   or terms_and_conditions or description"), the resource
   exposes `description` as a single RichEditor — no separate
   plain-text description Textarea. Operators should be aware
   that the admin-edit description displays as rich HTML in
   the customer-facing UI.

4. **OrderResource has no Create page.** Removed the
   generated `CreateOrder.php` page. Admin cannot place an
   order on a customer's behalf — orders flow exclusively
   through `/cart/checkout`. View, edit (limited fields),
   and the 3 status actions are the only admin operations.

5. **UserResource has Create page (kept).** Operators may
   need to bootstrap an admin user; the page renders with
   no password field, so the only path to create a *usable*
   admin is name+phone+is_admin=true followed by an OTP login
   from the customer flow. Documented in
   `docs/ADMIN_SETUP.md` from Phase 4.1.

6. **Schema column name substitutions.** Documented in
   `PHASE4_2_AUDIT.md` and §3 above. Resources use actual DB
   column names throughout.

7. **Three new factories created.** `OrderFactory`,
   `CouponFactory`, `ServiceCenterFactory` — all minimal,
   deterministic, and only populate columns required by tests.

8. **Playwright `smoke` project regex tightened.** The original
   `/smoke\.spec\.ts$/` would also match `admin-smoke.spec.ts`.
   Changed to `/[\\/]smoke\.spec\.ts$/` so the admin project
   is fully isolated.

9. **Login page test uses input selector, not text label.**
   The `Sign in` text is inside a Livewire-deferred button;
   waiting on it is flaky on cold Vite/Filament boots. Test
   waits on the always-present email/phone input instead.

---

## 16. Known issues / technical debt for Phase 4.3+

1. **role enum + is_admin coexist** (Phase 4.1 carry-over).
   Both columns encode admin status; Filament reads only
   `is_admin`. Phase 6 cleanup should drop `role` and any
   code paths that read it.

2. **No admin notification on order status change.** Customers
   are not auto-notified when admin confirms or cancels.
   Confirm modal text reads "Customer should be notified
   manually if no notification system is configured." Phase
   5 (notifications) will wire SMS/email on transitions.

3. **Vehicle snapshot field shape varies.** Some snapshots
   use `brand_name`/`model_name`/`fuel_name`, others use
   `brand`/`model`/`fuel`. The render helper handles both;
   if a third shape appears in production data, extend
   `OrderResource::renderVehicle()`.

4. **OperationsStats does not memoize.** Each poll re-runs
   all 4 queries. Adequate at current volume; if dashboard
   latency grows, wrap in a 30s cache.

5. **No admin audit log.** Status changes, toggles, deletes
   are not logged. Phase 6 (compliance) should add an
   `admin_audit_log` table.

6. **OrderResource Edit form allows free status changes via
   the Select.** The action buttons enforce the status
   transition matrix, but a determined admin can also pick
   any value from the form Select (except in completed/
   cancelled, where the field is `disabled`). Acceptable for
   day-1 operator trust model, but Phase 6 should consider
   a stricter form-side validator.

---

## 17. Phase 4.3 preview

**Scope:** Master data CRUD — Brands, Models, Fuel Types,
ServicePrices.

**Key decisions to surface:**
- Excel/CSV import via Maatwebsite (separate package add —
  HARD CONSTRAINT relaxed for that phase only).
- Slug-based references (CSV columns reference brands by slug,
  not auto-increment ID).
- Validation row-by-row, partial import allowed (skip bad rows,
  report at end).
- Pre-import preview ("12 new, 3 updated, 1 invalid").
- Upsert by slug = idempotent re-runs.
- Resources: BrandResource, CarModelResource, FuelTypeResource,
  ServicePriceResource.
- Estimated effort: ~3 days.
- ~10 new tests (import validation, upsert idempotence, slug
  collision, partial-failure rollback semantics).

**Out of scope for 4.3 (deferred to later):**
- Image upload for brand/model logos (Phase 4.4).
- SEO pages CRUD (Phase 4.5).
- Order admin's in_service intermediate (deferred indefinitely
  unless garage-floor view becomes a requirement).

---

**Phase 4.2 complete. Awaiting operator manual verification per
PHASE4_2_MANUAL_CHECKLIST.md.**
