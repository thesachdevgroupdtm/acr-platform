# Phase 4.2 — Schema Audit

**Date:** 2026-05-07
**Purpose:** Document actual DB schema, model conventions, and
Filament redirect behavior BEFORE building admin resources.

---

## 1. Filament redirect probe (PART A.6)

| Scenario | HTTP Status | Notes |
|---|---|---|
| Unauthenticated user → `/admin` | **302** | Redirects to `http://localhost/admin/login` |
| Authenticated non-admin → `/admin` | **403** | `canAccessPanel()` returns false → Forbidden |

**Test strategy:** When testing "non-admin cannot access XxxResource":
authenticate the user first (`actingAs($customer)`), then assert
`->assertForbidden()` (HTTP 403). Filament does NOT redirect a
logged-in non-admin; it 403s.

---

## 2. orders table

```
id, order_number (unique), user_id, service_center_id (nullable),
coupon_id (nullable), status (enum), payment_status (enum),
name_snapshot, phone_snapshot, email_snapshot (nullable),
address (text, nullable), vehicle_snapshot (json),
preferred_date, preferred_time, subtotal, discount, tax, total,
notes (text, nullable), is_high_risk, placed_at, confirmed_at,
in_service_at, completed_at, cancelled_at, cancelled_reason,
created_at, updated_at
```

**Status enum values (DB):** `pending`, `confirmed`, `in_service`,
`completed`, `cancelled`. Note: `in_service` exists between
`confirmed` and `completed`.

**Payment status enum:** `pending`, `paid`, `failed`, `refunded`.

**Model constants (Order::STATUS_*):**
- STATUS_PENDING, STATUS_CONFIRMED, STATUS_IN_SERVICE,
  STATUS_COMPLETED, STATUS_CANCELLED.

**Schema deviations vs PHASE4_2 task spec:**

| Task expected | Actual | Resolution |
|---|---|---|
| `cancellation_reason` | `cancelled_reason` | Use `cancelled_reason` throughout. |
| `services_snapshot` | (none) | Use `items()` relationship instead. |
| `address_snapshot` | `address` (text) | Use `address` column. |
| `vehicle_snapshot` (string?) | `vehicle_snapshot` (json) | Already JSON cast — render as `Brand Model (Fuel)`. |
| Status: pending → confirmed → completed | pending → confirmed → in_service → completed | See "Order status transitions" below. |

**Order status transitions** (DEVIATION from task D-4.2-9):

The Order model's `transitionTo()` enforces:
- pending → confirmed | cancelled
- confirmed → in_service | cancelled
- in_service → completed
- completed/cancelled = terminal

Task D-4.2-9 specified `confirmed → completed` directly.
**Resolution:** Admin "Mark Completed" action will SKIP
`in_service` and bypass `transitionTo()`, directly setting
`status='completed'` and `completed_at=now()`. The intermediate
`in_service` state is reserved for a future garage-floor view
(Phase 4+) — not relevant for the day-1 admin sprint. Documented
in PHASE4_2_REPORT.md deviations.

**Relationships:**
- `user()` belongsTo User
- `items()` hasMany OrderItem
- `serviceCenter()` belongsTo ServiceCenter
- `payments()` hasMany PaymentTransaction
- `coupon()` belongsTo Coupon

**No factory exists** for Order. Will create
`OrderFactory.php` for tests.

---

## 3. order_items table

```
id, order_id, service_id, package_id, product_id, brand_id,
model_id, fuel_id, service_title_snapshot, quantity,
unit_price_snapshot, line_total_snapshot, meta (json),
created_at, updated_at
```

Used in OrderResource view page to render service line items.

---

## 4. users table

```
id, name, email (nullable, unique), is_admin (boolean, default 0),
phone (nullable, unique), is_verified_phone (boolean),
is_verified_email (boolean), email_verified_at, password (nullable),
remember_token, last_login_at, role (enum customer|admin),
created_at, updated_at
```

**Notes:**
- `password` is nullable (OTP-flow doesn't require it).
- `role` enum coexists with `is_admin` boolean — Phase 6 cleanup.
  Per HARD CONSTRAINTS, do NOT touch `role`.
- `last_login_at` exists, will display in form Meta section.

**Existing factory:** `UserFactory` with `admin()` state from
Phase 4.1. ✅

---

## 5. coupons table

```
id, code (unique), name, description (text), discount_type (enum),
discount_value, max_discount (nullable), min_order_value,
applicable_service_ids (json, nullable),
applicable_category_ids (json, nullable),
usage_limit (nullable), usage_per_user (nullable),
expiry_date (date, nullable), is_active, is_featured,
badge (nullable), display_order, created_at, updated_at
```

**Discount type enum (DB):** `percent`, `flat`.
**Model constants:** `DISCOUNT_TYPE_PERCENT`, `DISCOUNT_TYPE_FLAT`.

**Schema deviations vs task spec:**

| Task expected | Actual | Resolution |
|---|---|---|
| `title` | `name` | Use `name` for "Title" form label. |
| `value` | `discount_value` | Use `discount_value`. |
| `min_order` | `min_order_value` | Use `min_order_value`. |
| `max_uses` | `usage_limit` | Use `usage_limit`. |
| `expires_at` | `expiry_date` (date, not datetime) | Use `DatePicker` (not `DateTimePicker`). |
| `terms` (RichEditor T&C) | (no `terms` column) | **Use `description` field as RichEditor T&C.** Task spec line 23 explicitly listed `description` as fallback. Drop the separate Textarea description — single field for both basic description and T&C. Documented in deviations. |
| `discount_type` enum 'percentage'/'fixed' | 'percent'/'flat' | Use actual enum values. |

**No factory exists** for Coupon. Will create `CouponFactory.php`.

---

## 6. service_categories table

```
id, name, slug (unique), description (text, nullable),
image (nullable), icon_image (nullable), position (smallint),
is_active, created_at, updated_at
```

**Schema deviation:**

| Task expected | Actual | Resolution |
|---|---|---|
| `display_order` | `position` | Use `position` — table will be `reorderable('position')`. |

**Existing factory:** `ServiceCategoryFactory` ✅

---

## 7. services table

```
id, category_id (FK), name, slug, description (text, nullable),
image (nullable), base_price (decimal, nullable),
time_takes (string, nullable), time_unit (string, nullable),
warrenty_info (text, nullable), recommended_info (text, nullable),
note (text, nullable), is_active, created_at, updated_at
```

**Schema deviations:**

| Task expected | Actual | Resolution |
|---|---|---|
| `service_category_id` | `category_id` | Use `category_id`. |
| `duration` (int minutes) | `time_takes` (string) + `time_unit` (string) | Use `time_takes` + `time_unit` separately. Form: TextInput for `time_takes` numeric, Select for `time_unit` (minutes/hours). Display as `"60 minutes"` or `"—"` if null. |
| `display_order` on services | (no such column) | DROP from form — `display_order` does not exist on services. The task script mentioned it as optional anyway. |

UNIQUE constraint: `(category_id, slug)` — slug unique per category.

**Existing factory:** `ServiceFactory` ✅

---

## 8. service_centers table

```
id, slug (unique), name, address, phone, email (nullable),
city, state, pincode, latitude, longitude, is_active,
sort_order, created_at, updated_at
```

OrderResource will use `ServiceCenter` model for the
`service_center_id` Select dropdown — match `name (city)` format.

---

## 9. Existing factories

- ✅ `UserFactory` (with `admin()` state)
- ✅ `ServiceCategoryFactory`
- ✅ `ServiceFactory`
- ✅ `CarBrandFactory`, `CarModelFactory`, `FuelTypeFactory`,
  `ServicePriceFactory`

**To be created in PART H:**
- `OrderFactory` (minimal, with status state)
- `CouponFactory` (minimal, with code generator)
- `ServiceCenterFactory` (minimal, for Order tests)

---

## 10. Status transitions matrix (final, for OrderResource)

| Current | Transition | Action button | Sets |
|---|---|---|---|
| pending | → confirmed | "Confirm" | status, confirmed_at |
| pending | → cancelled | "Cancel" (with reason) | status, cancelled_at, cancelled_reason |
| confirmed | → cancelled | "Cancel" (with reason) | status, cancelled_at, cancelled_reason |
| confirmed | → completed (skips in_service) | "Mark Completed" | status, completed_at |
| in_service | (no admin action — handled by garage-floor view, future) | — | — |
| completed | terminal | — | — |
| cancelled | terminal | — | — |

**Test plan for transitions:** assert action visibility AND
post-action status/timestamp/reason changes.

---

## 11. Filament discovery wiring

`AdminPanelProvider` already has:
```php
->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
```

So new resources + widgets just need to be placed in those
directories. The widget array currently contains only
`AccountWidget` and `FilamentInfoWidget` — `OperationsStats`
will be added explicitly.

---

## 12. Models load probe

```
9 orders, 19 users, 3 coupons, 12 cats, 40 services, 4 centers
```

All models load cleanly. Production data is preserved.

---

**Audit complete. Proceeding to PART B (OrderResource).**
