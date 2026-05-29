# Phase 2 — API Contract Definition

**Status:** Draft. Awaiting product-owner review. **Zero implementation written.**
**Frozen:** the 16 routes from `AUDIT_REPORT.md §4` are unchanged. Phase 2 is additive.
**Scope:** auth (phone+email OTP), addresses, cart with guest→user merge, unified orders, payments stub, content (service_centers, testimonials, faqs, coupons), catalog extensions (service_packages, products, membership_packages).

Conventions:
- Migration code-blocks use Laravel 10 `Schema::create` syntax.
- Currency is `INR` everywhere (not parameterised). Locale is `en_IN`.
- All tables use `bigIncrements` PK and `timestamps()` unless noted.
- All `slug` columns are immutable post-create (per project SEO rule).

---

## 1. ENTITY-RELATIONSHIP DIAGRAM

```
                             ┌──────────────────────────┐
                             │  users  (skeleton+ext)   │
                             │  id, phone(UNQ), email,  │
                             │  is_verified_phone, role │
                             └─────┬────────────────────┘
                                   │ 1                                          ┌─────────────────────────┐
            ┌──────────────────────┼────────────────────────┐                   │ otp_verifications       │
          1 │                    1 │                      1 │                   │ user_id N, channel,     │
            ▼                      ▼                        ▼              N    │ destination,            │
   ┌─────────────────┐  ┌──────────────────┐  ┌──────────────────────┐   ◄──────┤ otp_code, attempts      │
   │   addresses     │  │     carts        │  │      orders          │          └─────────────────────────┘
   │ user_id FK      │  │ user_id N,       │  │ user_id FK,          │
   │ line1, city,    │  │ session_uuid N,  │  │ order_number(UNQ),   │
   │ pincode,        │  │ status, expires  │  │ service_center_id N, │
   │ is_default      │  └────┬─────────────┘  │ vehicle_brand_id,    │
   └─────────────────┘     1 │                │ vehicle_model_id,    │
                             │ N              │ vehicle_fuel_id,     │
                             ▼                │ address_id N,        │
                   ┌───────────────────────┐  │ status, payment_st,  │
                   │     cart_items        │  │ subtotal,total,      │
                   │ cart_id FK,           │  │ coupon_id N          │
                   │ service_id N,         │  └──────┬───────────────┘
                   │ package_id N,         │         │ 1
                   │ product_id N,         │         │
                   │ brand/model/fuel N,   │       N ▼
                   │ qty, price_snapshot,  │  ┌──────────────────────┐    1   ┌──────────────────────────┐
                   │ meta JSON             │  │   order_items        │  ◄─────┤ payment_transactions     │
                   └────────────┬──────────┘  │ order_id FK,         │   N    │ order_id FK, gateway,    │
                                │             │ service_id N,        │        │ gateway_txn_id, amount,  │
       ┌────────────────────────┘             │ package_id N,        │        │ status, payload JSON     │
       │                                      │ product_id N,        │        └──────────────────────────┘
       │ exactly one of:                      │ name_snapshot,       │
       │  service_id (services table)         │ unit_price_snapshot, │
       │  package_id (service_packages)       │ qty, line_total      │
       │  product_id (products)               └──────────────────────┘
       ▼
  ┌──────────────────┐  ┌──────────────────────┐  ┌────────────────────┐
  │  services (X)    │  │  service_packages    │  │     products       │
  │  (existing)      │  │  slug(UNQ), name,    │  │  slug(UNQ), sku,   │
  └──────────────────┘  │  package_price       │  │  price, stock      │
                        └──────┬───────────────┘  └────────────────────┘
                               │ N:M
                               ▼
                        ┌──────────────────────┐
                        │  package_services    │  ──► services (X)
                        │  (pivot)             │
                        └──────────────────────┘

        ┌─────────────────────┐   ┌──────────────────────┐   ┌──────────────────────┐
        │     coupons         │ 1 │   coupon_usages      │ N │  orders (above)      │
        │ code(UNQ), type,    │◄──┤ coupon_id, user_id,  ├──►│                      │
        │ value, valid_until  │   │ order_id, redeemed   │   └──────────────────────┘
        └─────────────────────┘   └──────────────────────┘

  ┌──────────────────────┐    ┌──────────────────────┐   ┌──────────────────────┐
  │   service_centers    │    │    testimonials      │   │        faqs          │
  │ slug(UNQ), lat, lng, │    │ rating, text,        │   │ question, answer,    │
  │ hours JSON           │    │ service_id N (FK X)  │   │ faqable_type N,      │
  └──────────────────────┘    └──────────────────────┘   │ faqable_id N         │  ──► poly:
                                                          └──────────────────────┘     Service|
                                                                                       ServiceCategory|
                                                                                       Page|null

  ┌────────────────────────────┐
  │   membership_packages      │
  │ slug(UNQ), duration_months,│
  │ price, benefits JSON       │
  └────────────────────────────┘
```

**Cardinality summary:**
| Relationship | Cardinality |
|---|---|
| user → addresses | 1:N |
| user → carts | 1:N (active cart selected by status='active') |
| user → orders | 1:N |
| user → otp_verifications | 1:N (history) |
| cart → cart_items | 1:N |
| order → order_items | 1:N |
| order → payment_transactions | 1:N (retries) |
| coupon → coupon_usages | 1:N |
| service_package ↔ service | N:M (via package_services) |
| service → testimonials | 1:N (optional FK; testimonials may be brand-wide) |
| any (Service/ServiceCategory/Page) → faqs | polymorphic 1:N |

(X) = existing table, untouched in Phase 2 schema.

---

## 2. SCHEMA — table-by-table migration definitions

### 2.1 `users` — EXTEND skeleton (NOT recreate)

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone', 15)->nullable()->after('email');
    $table->boolean('is_verified_phone')->default(false)->after('phone');
    $table->boolean('is_verified_email')->default(false)->after('is_verified_phone');
    $table->timestamp('last_login_at')->nullable()->after('remember_token');
    $table->enum('role', ['customer', 'admin'])->default('customer')->after('last_login_at');

    $table->unique('phone');
    $table->index(['role', 'last_login_at']);
});
```

| Column | Type | Notes |
|---|---|---|
| phone | string(15) NULL UNIQUE | Nullable on add to allow soft data backfill; new users always have it |
| is_verified_phone | bool default false | Set true on OTP success |
| is_verified_email | bool default false | Optional channel; not required for booking |
| last_login_at | timestamp NULL | Updated on every successful verify-otp |
| role | enum('customer','admin') default 'customer' | `admin` reserved for Filament users |

**Rationale:** `phone` is nullable on the migration so it can be added without
breaking the seed-empty users table. App-level validation requires phone for
all new sign-ups.

---

### 2.2 `otp_verifications`

```php
Schema::create('otp_verifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    $table->enum('channel', ['phone', 'email']);
    $table->string('destination', 191);          // phone digits or email
    $table->string('otp_code', 8);                // hashed (sha256) — never plaintext
    $table->timestamp('expires_at');
    $table->timestamp('verified_at')->nullable();
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->string('ip', 45)->nullable();
    $table->timestamps();

    $table->index(['channel', 'destination', 'verified_at']);
    $table->index(['user_id', 'verified_at']);
});
```

**Rationale:** `user_id` nullable so OTPs can be sent during lead-capture
before user-record is finalised. `otp_code` stored hashed; verify hashes
the input and compares. `attempts` enforces lockout.

---

### 2.3 `addresses`

```php
Schema::create('addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('label', 50)->default('Home');     // free-text; UI suggests "Home"/"Office"
    $table->string('line1');
    $table->string('line2')->nullable();
    $table->string('city', 80);
    $table->string('state', 80);
    $table->string('pincode', 10);
    $table->string('landmark')->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamps();

    $table->index(['user_id', 'is_default']);
});
```

**Rationale:** label is free-text (not enum) — fewer migrations later.
Application logic ensures only one `is_default=true` per user.

---

### 2.4 `carts`

```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    $table->uuid('session_uuid')->nullable();
    $table->string('currency', 3)->default('INR');
    $table->timestamp('expires_at');                  // 30d guest, 90d user
    $table->enum('status', ['active', 'converted', 'abandoned'])->default('active');
    $table->timestamps();

    $table->unique('session_uuid');
    $table->index(['user_id', 'status']);
    $table->index(['status', 'expires_at']);          // cleanup job

    // CHECK constraint via raw SQL (MySQL 8.0+ supports it):
    //  CHECK (user_id IS NOT NULL OR session_uuid IS NOT NULL)
    // Enforced at app layer too (CartService::ensureOwner()).
});
```

**Rationale:** the (user_id, session_uuid) dual-key shape is the foundation
of the merge protocol (§6.5). `unique(session_uuid)` allows one active
guest cart per browser. `expires_at` indexed with `status` for the
abandoned-cart cleanup cron.

---

### 2.5 `cart_items`

```php
Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cart_id')->constrained()->cascadeOnDelete();

    $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
    $table->foreignId('package_id')->nullable()->constrained('service_packages')->cascadeOnDelete();
    $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();

    $table->foreignId('brand_id')->nullable()->constrained('car_brands')->nullOnDelete();
    $table->foreignId('model_id')->nullable()->constrained('car_models')->nullOnDelete();
    $table->foreignId('fuel_id')->nullable()->constrained('fuel_types')->nullOnDelete();

    $table->unsignedSmallInteger('quantity')->default(1);
    $table->decimal('unit_price_snapshot', 10, 2);    // captured at add-time
    $table->json('meta')->nullable();                  // {recommended_info, time_takes, ...}
    $table->timestamps();

    $table->index('cart_id');

    // CHECK: exactly one of service_id/package_id/product_id non-null.
    //   ((service_id IS NOT NULL) + (package_id IS NOT NULL) + (product_id IS NOT NULL)) = 1
    // Enforced at app layer via CartItem::booted() saving event.
});
```

**Rationale:** `unit_price_snapshot` insulates the cart from later
price changes. `meta` JSON carries display-only fields needed in
Cart UI without join chains. Vehicle FKs use `nullOnDelete` because
losing a brand/model shouldn't delete the cart row.

---

### 2.6 `orders`

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number', 20)->unique();    // ACR-YYYY-NNNNNN
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->foreignId('service_center_id')->nullable()->constrained()->nullOnDelete();

    $table->foreignId('vehicle_brand_id')->nullable()->constrained('car_brands')->nullOnDelete();
    $table->foreignId('vehicle_model_id')->nullable()->constrained('car_models')->nullOnDelete();
    $table->foreignId('vehicle_fuel_id')->nullable()->constrained('fuel_types')->nullOnDelete();
    $table->json('vehicle_snapshot')->nullable();      // {brand_name, model_name, fuel_name} at order time

    $table->date('slot_date');
    $table->time('slot_time');

    $table->string('customer_name_snapshot', 120);
    $table->string('customer_phone_snapshot', 15);
    $table->string('customer_email_snapshot', 191)->nullable();

    $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
    $table->json('address_snapshot');                  // full address at order time

    $table->decimal('subtotal', 10, 2);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('total', 10, 2);

    $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();    // Single coupon_id per order (D-E: no stacking). Schema structurally enforces by having one column, not a pivot table.

    $table->enum('status', [
        'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'refunded',
    ])->default('pending');

    $table->enum('payment_status', [
        'unpaid', 'pending', 'paid', 'failed', 'refunded',
    ])->default('unpaid');

    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status']);
    $table->index(['status', 'created_at']);
    $table->index('payment_status');
});
```

**Rationale:**
- Snapshot fields preserve customer/address state at order time so
  address edits or user-name changes don't rewrite history.
- `order_number` format `ACR-YYYY-NNNNNN` is sortable and
  human-readable; generated atomically via `DB::transaction` +
  `lockForUpdate`.
- **`user_id` uses `restrictOnDelete`**: a user cannot be hard-deleted
  while orders exist; admin uses `role='archived'` for off-boarding,
  or future soft-delete on the users table only (see Assumption 24
  amendment).
- **Vehicle FKs use `nullOnDelete` + new `vehicle_snapshot` JSON**:
  catalog deletion of a brand/model/fuel sets the FK to NULL but the
  displayed `{brand_name, model_name, fuel_name}` is preserved in
  `vehicle_snapshot`. `order_items.name_snapshot` likewise preserves
  the service/package/product display name.

---

### 2.7 `order_items`

```php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();

    $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
    $table->foreignId('package_id')->nullable()->constrained('service_packages')->nullOnDelete();
    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

    $table->string('name_snapshot', 191);
    $table->decimal('unit_price_snapshot', 10, 2);
    $table->unsignedSmallInteger('quantity');
    $table->decimal('line_total', 10, 2);
    $table->timestamps();

    $table->index('order_id');
});
```

**Rationale:** Deletes of service/package/product don't cascade — orders
remain queryable with frozen `name_snapshot`.

---

### 2.8 `payment_transactions`

```php
Schema::create('payment_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->string('gateway', 30);                       // 'razorpay'|'payu'|'manual'
    $table->string('gateway_txn_id', 100)->nullable();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('INR');
    $table->enum('status', ['initiated', 'pending', 'success', 'failed', 'refunded'])
          ->default('initiated');
    $table->json('payload_request')->nullable();
    $table->json('payload_response')->nullable();
    $table->timestamp('attempted_at')->nullable();
    $table->timestamps();

    $table->index(['order_id', 'status']);
    $table->index('gateway_txn_id');
});
```

---

### 2.9 `coupons`

```php
Schema::create('coupons', function (Blueprint $table) {
    $table->id();
    $table->string('code', 30)->unique();
    $table->enum('type', ['percentage', 'flat', 'first_order']);
    $table->decimal('value', 10, 2);
    $table->decimal('min_order_amount', 10, 2)->default(0);
    $table->decimal('max_discount', 10, 2)->nullable();   // NULL = uncapped
    $table->timestamp('valid_from')->nullable();
    $table->timestamp('valid_until')->nullable();
    $table->unsignedInteger('usage_limit_total')->nullable();
    $table->unsignedSmallInteger('usage_limit_per_user')->default(1);
    $table->boolean('is_active')->default(true);
    $table->json('applicable_categories')->nullable();   // [category_id...] NULL = all
    $table->json('applicable_services')->nullable();     // [service_id...] NULL = all
    $table->string('description', 255)->nullable();
    $table->string('badge_text', 30)->nullable();        // e.g. "BEST VALUE"

    // ── Phase 1.1 (commit 6e3e9c1) Offers card visual restoration ──
    // These four fields preserve the card's photo + chips on the public
    // /offers page. Optional; absent = card falls back to gradient + badge.
    $table->string('image', 255)->nullable();
    $table->string('urgency_text', 60)->nullable();      // e.g. "ONLY 3 SLOTS LEFT"
    $table->decimal('rating', 2, 1)->nullable();          // 0.0–5.0
    $table->unsignedInteger('customers')->nullable();     // e.g. 12500 → "12,500+"

    $table->timestamps();

    $table->index(['is_active', 'valid_until']);
});
```

> **Note:** the `image`, `urgency_text`, `rating`, `customers` fields
> preserve the Phase 1.1 (commit `6e3e9c1`) Offers card visual
> restoration. Without them, the public `/offers` page would lose the
> photo + urgency + rating + customer-count chips that were re-added
> after the consolidation deviation.

---

### 2.10 `coupon_usages`

```php
Schema::create('coupon_usages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->timestamp('redeemed_at');
    $table->timestamps();

    $table->index(['coupon_id', 'user_id']);             // per-user limit lookup
    $table->unique(['coupon_id', 'order_id']);           // idempotency
});
```

**Rationale:** `coupon_id` uses `restrictOnDelete`: coupon redemption
history must outlive coupon-row deletion (audit + analytics + dispute
trail). Admin marks `coupons.is_active = false` instead of deleting.
Hard-deleting a coupon throws when any usage exists.

---

### 2.11 `service_centers`

```php
Schema::create('service_centers', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 100)->unique();
    $table->string('name', 120);
    $table->string('address', 255);
    $table->string('city', 80);
    $table->string('state', 80);
    $table->string('pincode', 10);
    $table->string('phone', 15);
    $table->string('email', 191)->nullable();
    $table->decimal('lat', 10, 7)->nullable();
    $table->decimal('lng', 10, 7)->nullable();
    $table->string('image', 255)->nullable();
    $table->json('gallery')->nullable();                 // [url...]
    $table->json('opening_hours')->nullable();           // {mon:["09:00","19:00"], ...}
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('position')->default(0);
    $table->timestamps();

    $table->index(['is_active', 'position']);
    $table->index('city');
});
```

---

### 2.12 `testimonials`

```php
Schema::create('testimonials', function (Blueprint $table) {
    $table->id();
    $table->enum('source', ['google', 'manual']);
    $table->string('google_review_id', 100)->nullable()->unique();
    $table->string('google_author_url', 500)->nullable();
    $table->string('customer_name', 120);
    $table->string('initials', 4)->nullable();
    $table->unsignedTinyInteger('rating');                // 1–5
    $table->text('text');
    $table->string('image', 255)->nullable();
    $table->foreignId('service_id')->nullable()
          ->constrained()->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->unsignedSmallInteger('position')->default(0);
    $table->foreignId('created_by_admin_id')->nullable()
          ->constrained('users')->nullOnDelete();
    $table->timestamp('synced_at')->nullable();           // last Google sync time
    $table->timestamps();

    $table->index(['source', 'is_active', 'rating']);
    $table->index(['is_active', 'is_featured', 'position']);
});
```

**Rationale (Decision D-D):** hybrid testimonial sourcing — Google
Reviews (sync via Places API in Phase 4 admin; structure ready in
Phase 2.6 with manual seed) AND manual admin entries. `source` enum
distinguishes; `google_review_id` UNIQUE for re-sync dedup;
`created_by_admin_id` tracks attribution for manual rows. Listing
rules enforced at endpoint level (§5.7 #29 + new #29b). `service_id`
optional — testimonials can be brand-wide or service-specific.
`initials` avoids client-side parsing of name.

---

### 2.13 `faqs` (polymorphic)

```php
Schema::create('faqs', function (Blueprint $table) {
    $table->id();
    $table->string('question', 255);
    $table->text('answer');
    $table->string('faqable_type', 100)->nullable();    // App\Models\Service | ServiceCategory | Page
    $table->unsignedBigInteger('faqable_id')->nullable();
    $table->unsignedSmallInteger('position')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['faqable_type', 'faqable_id', 'is_active']);
    $table->index(['is_active', 'position']);            // global FAQs
});
```

**Rationale:** polymorphic; NULL `faqable_*` = global. Note: no FK on
`faqable_id` (Laravel polymorphism pattern; integrity guarded at app
layer).

---

### 2.14 `service_packages`

```php
Schema::create('service_packages', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 100)->unique();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->string('image', 255)->nullable();
    $table->decimal('package_price', 10, 2);
    $table->decimal('original_price', 10, 2)->nullable();   // strike-through
    $table->string('badge_text', 30)->nullable();
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('position')->default(0);
    $table->timestamps();

    $table->index(['is_active', 'position']);
});
```

---

### 2.15 `package_services` (pivot)

```php
Schema::create('package_services', function (Blueprint $table) {
    $table->foreignId('package_id')->constrained('service_packages')->cascadeOnDelete();
    $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
    $table->unsignedSmallInteger('position')->default(0);

    $table->primary(['package_id', 'service_id']);
});
```

---

### 2.16 `products`

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 100)->unique();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->string('image', 255)->nullable();
    $table->json('gallery')->nullable();
    $table->decimal('price', 10, 2);
    $table->string('sku', 50)->unique();
    $table->unsignedInteger('stock')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);   // §13: drives /home featured_products
    $table->unsignedSmallInteger('position')->default(0);
    $table->timestamps();

    $table->index(['is_active', 'is_featured', 'position']);
});
```

**Rationale:** `is_featured` flag drives the `featured_products` slot
on `/api/v1/home` (per §13 sourcing map). The composite index
`(is_active, is_featured, position)` supports the home query without
a sort filesort.

---

### 2.17 `membership_packages`

```php
Schema::create('membership_packages', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 100)->unique();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->unsignedSmallInteger('duration_months');
    $table->decimal('price', 10, 2);
    $table->json('benefits')->nullable();                // ["10% off services", ...]
    $table->string('badge_text', 30)->nullable();
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('position')->default(0);
    $table->timestamps();

    $table->index(['is_active', 'position']);
});
```

> **Note (Decision D-A — conditional visibility):** when no rows exist
> with `is_active = true`, `GET /api/v1/membership-packages` returns
> an empty array AND the `/api/v1/home` payload's `membership_package`
> key returns `[]`. The frontend MUST hide the membership section
> entirely on empty response — no placeholder, no skeleton, no
> "Coming soon" UI. Phase 2.6 implementation enforces
> `if (data?.length === 0) return null` in `<MembershipSection>`.

---

### TABLE NAME COLLISION RISK LIST

Per the deferred Track P1.5 work — every NEW table name must be checked
against the legacy production DB at deploy time before `php artisan
migrate` runs. Collision = abort deploy.

| Table | Risk | Why |
|---|---|---|
| `users` | LOW (extension only) | Already exists in skeleton |
| `otp_verifications` | LOW | Specific name; legacy unlikely to use this |
| `addresses` | **HIGH** | `old-backend/app/Models/UserAddress.php` references `user_addresses` — different name, but check legacy DB for raw `addresses` |
| `carts` | **MEDIUM** | Legacy `Cart.php` model exists; name might match |
| `cart_items` | **MEDIUM** | Plausible legacy collision |
| `orders` | **HIGH** | Legacy `Order.php` model uses `orders` — collision certain |
| `order_items` | **MEDIUM** | Legacy uses `order_details` — different but check |
| `payment_transactions` | LOW | Specific name |
| `coupons` | **MEDIUM** | Common name |
| `coupon_usages` | LOW | Specific |
| `service_centers` | **HIGH** | Legacy `ServiceCenterDetail.php` may use `service_center_details` or similar — check |
| `testimonials` | **MEDIUM** | Common |
| `faqs` | **HIGH** | Legacy `Faq.php` uses `faqs` — collision certain |
| `service_packages` | **MEDIUM** | Legacy `ScheduledPackage.php` uses `scheduled_packages` — different but check |
| `package_services` | LOW | Specific |
| `products` | **HIGH** | Legacy `Product.php` uses `products` — collision certain |
| `membership_packages` | **MEDIUM** | Plausible |

**Resolution rule for HIGH/MEDIUM at deploy time:**
1. Inspect legacy table schema. If schema is compatible AND legacy data
   is migrate-able, write a data-migration commit before Phase 2 deploys.
2. If schema is incompatible, namespace the new table (e.g. `acr_v2_orders`)
   and configure model `$table` property explicitly. Defer rename until
   legacy decommission.
3. If legacy table is empty / dead, drop it and proceed normally.

This decision happens **at deploy planning**, not in Phase 2 development.

---

## 3. ELOQUENT MODEL DEFINITIONS — relationship map

```php
// User (extends existing skeleton)
class User extends Authenticatable {
    public function addresses() { return $this->hasMany(Address::class); }
    public function carts()      { return $this->hasMany(Cart::class); }
    public function orders()     { return $this->hasMany(Order::class); }
    public function otps()       { return $this->hasMany(OtpVerification::class); }
    public function defaultAddress() {
        return $this->hasOne(Address::class)->where('is_default', true);
    }
}

class OtpVerification extends Model {
    public function user() { return $this->belongsTo(User::class); }
}

class Address extends Model {
    public function user() { return $this->belongsTo(User::class); }
}

class Cart extends Model {
    public function user()  { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(CartItem::class); }
}

class CartItem extends Model {
    public function cart()    { return $this->belongsTo(Cart::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function package() { return $this->belongsTo(ServicePackage::class, 'package_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function brand()   { return $this->belongsTo(CarBrand::class, 'brand_id'); }
    public function carModel(){ return $this->belongsTo(CarModel::class, 'model_id'); }
    public function fuel()    { return $this->belongsTo(FuelType::class, 'fuel_id'); }
}

class Order extends Model {
    public function user()           { return $this->belongsTo(User::class); }
    public function items()          { return $this->hasMany(OrderItem::class); }
    public function payments()       { return $this->hasMany(PaymentTransaction::class); }
    public function serviceCenter()  { return $this->belongsTo(ServiceCenter::class); }
    public function brand()          { return $this->belongsTo(CarBrand::class, 'vehicle_brand_id'); }
    public function carModel()       { return $this->belongsTo(CarModel::class, 'vehicle_model_id'); }
    public function fuel()           { return $this->belongsTo(FuelType::class, 'vehicle_fuel_id'); }
    public function address()        { return $this->belongsTo(Address::class); }
    public function coupon()         { return $this->belongsTo(Coupon::class); }
}

class OrderItem extends Model {
    public function order()   { return $this->belongsTo(Order::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function package() { return $this->belongsTo(ServicePackage::class, 'package_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}

class PaymentTransaction extends Model {
    public function order() { return $this->belongsTo(Order::class); }
}

class Coupon extends Model {
    public function usages() { return $this->hasMany(CouponUsage::class); }
    public function orders() { return $this->hasMany(Order::class); }
}

class CouponUsage extends Model {
    public function coupon() { return $this->belongsTo(Coupon::class); }
    public function user()   { return $this->belongsTo(User::class); }
    public function order()  { return $this->belongsTo(Order::class); }
}

class ServiceCenter extends Model {
    public function orders() { return $this->hasMany(Order::class); }
    public function faqs()   { return $this->morphMany(Faq::class, 'faqable'); }
}

class Testimonial extends Model {
    public function service() { return $this->belongsTo(Service::class); }
}

class Faq extends Model {
    public function faqable() { return $this->morphTo(); }
}

// Add to existing models:
class Service extends Model {                                         // existing — extend
    public function testimonials() { return $this->hasMany(Testimonial::class); }
    public function faqs()         { return $this->morphMany(Faq::class, 'faqable'); }
    public function packages()     {
        return $this->belongsToMany(ServicePackage::class, 'package_services', 'service_id', 'package_id')
                    ->withPivot('position');
    }
}

class ServiceCategory extends Model {                                 // existing — extend
    public function faqs() { return $this->morphMany(Faq::class, 'faqable'); }
}

class Page extends Model {                                            // existing — extend
    public function faqs() { return $this->morphMany(Faq::class, 'faqable'); }
}

class ServicePackage extends Model {
    public function services() {
        return $this->belongsToMany(Service::class, 'package_services', 'package_id', 'service_id')
                    ->withPivot('position');
    }
}

class Product extends Model {
    public function cartItems()  { return $this->hasMany(CartItem::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
}

class MembershipPackage extends Model {
    // No relations in Phase 2 — purely catalog. Subscription tracking
    // deferred (would need user_memberships pivot). See §11 assumption.
}
```

---

## 4. API RESOURCE SHAPES (response envelopes)

### 4.1 User

```ts
interface UserResource {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  is_verified_phone: boolean;
  is_verified_email: boolean;
  role: 'customer' | 'admin';
  default_address: AddressResource | null;
  created_at: string;     // ISO
  last_login_at: string | null;
}
```
```php
public function toArray($r): array {
    return [
        'id'                => $this->id,
        'name'              => $this->name,
        'phone'             => $this->phone,
        'email'             => $this->email,
        'is_verified_phone' => (bool) $this->is_verified_phone,
        'is_verified_email' => (bool) $this->is_verified_email,
        'role'              => $this->role,
        'default_address'   => $this->whenLoaded('defaultAddress',
                                  fn() => new AddressResource($this->defaultAddress)),
        'created_at'        => $this->created_at?->toISOString(),
        'last_login_at'     => $this->last_login_at?->toISOString(),
    ];
}
```

### 4.2 Address

```ts
interface AddressResource {
  id: number;
  label: string;
  line1: string;
  line2: string | null;
  city: string;
  state: string;
  pincode: string;
  landmark: string | null;
  is_default: boolean;
}
```
```php
return [
    'id' => $this->id, 'label' => $this->label,
    'line1' => $this->line1, 'line2' => $this->line2,
    'city' => $this->city, 'state' => $this->state,
    'pincode' => $this->pincode, 'landmark' => $this->landmark,
    'is_default' => (bool) $this->is_default,
];
```

### 4.3 Cart (full) + CartItem

```ts
interface CartResource {
  id: number;
  status: 'active' | 'converted' | 'abandoned';
  currency: 'INR';
  expires_at: string;
  items: CartItemResource[];
  totals: {
    subtotal: number;
    discount: number;
    coupon: { code: string; type: string; value: number } | null;
    tax: number;     // ALWAYS 0 in /cart per Decision D-B; populated only by /checkout/quote and /checkout/place-order
    total: number;
  };
  item_count: number;
}

interface CartItemResource {
  id: number;
  kind: 'service' | 'package' | 'product';
  ref_id: number;                          // service_id | package_id | product_id
  name: string;
  slug: string;
  image: string | null;
  unit_price: number;                      // = unit_price_snapshot
  quantity: number;
  line_total: number;
  vehicle: { brand_id: number; model_id: number; fuel_id: number } | null;
  meta: Record<string, unknown> | null;
}
```
```php
// CartResource
return [
    'id' => $this->id, 'status' => $this->status,
    'currency' => $this->currency,
    'expires_at' => $this->expires_at->toISOString(),
    'items' => CartItemResource::collection($this->items),
    'totals' => $this->totals(),               // computed in service
    'item_count' => $this->items->sum('quantity'),
];

// CartItemResource
$kind = $this->service_id ? 'service' : ($this->package_id ? 'package' : 'product');
$ref  = $this->service ?? $this->package ?? $this->product;
return [
    'id' => $this->id, 'kind' => $kind,
    'ref_id' => $ref->id, 'name' => $ref->name, 'slug' => $ref->slug,
    'image' => $ref->image,
    'unit_price' => (float) $this->unit_price_snapshot,
    'quantity' => $this->quantity,
    'line_total' => (float) ($this->unit_price_snapshot * $this->quantity),
    'vehicle' => $this->brand_id ? [
        'brand_id' => $this->brand_id,
        'model_id' => $this->model_id,
        'fuel_id' => $this->fuel_id,
    ] : null,
    'meta' => $this->meta,
];
```

### 4.4 Order (list lean / detail full)

```ts
interface OrderListItem {
  id: number;
  order_number: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  total: number;
  slot_date: string;     // YYYY-MM-DD
  slot_time: string;     // HH:MM
  item_count: number;
  created_at: string;
}

interface OrderResource extends OrderListItem {
  customer: { name: string; phone: string; email: string | null };
  // Resolved from FK rows when vehicle_*_id is non-null; falls back
  // to vehicle_snapshot JSON when the catalog row was deleted post-order.
  vehicle: { brand: string; model: string; fuel: string };
  service_center: { id: number; name: string; address: string } | null;
  address: AddressResource | null;
  address_snapshot: { line1: string; line2: string | null; city: string;
                      state: string; pincode: string; landmark: string | null };
  items: OrderItemResource[];
  payments: PaymentTransactionResource[];
  totals: { subtotal: number; discount: number; tax: number; total: number };
  coupon: { code: string; description: string | null } | null;
  notes: string | null;
}

type OrderStatus = 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled' | 'refunded';
type PaymentStatus = 'unpaid' | 'pending' | 'paid' | 'failed' | 'refunded';

interface OrderItemResource {
  id: number;
  kind: 'service' | 'package' | 'product';
  ref_id: number | null;
  name: string;
  unit_price: number;
  quantity: number;
  line_total: number;
}
```
```php
// list
return [
    'id' => $this->id, 'order_number' => $this->order_number,
    'status' => $this->status, 'payment_status' => $this->payment_status,
    'total' => (float) $this->total,
    'slot_date' => $this->slot_date->toDateString(),
    'slot_time' => substr($this->slot_time, 0, 5),
    'item_count' => $this->items_count ?? $this->items->count(),
    'created_at' => $this->created_at->toISOString(),
];

// detail — adds customer, vehicle, items, payments, totals, address,
// coupon, notes per the TS shape above.
//
// vehicle resolution (snapshot fallback when catalog row deleted):
//   $brand = $this->brand?->name      ?? data_get($this->vehicle_snapshot, 'brand_name');
//   $model = $this->carModel?->name   ?? data_get($this->vehicle_snapshot, 'model_name');
//   $fuel  = $this->fuel?->name       ?? data_get($this->vehicle_snapshot, 'fuel_name');
//   return ['vehicle' => compact('brand', 'model', 'fuel')];
```

### 4.5 PaymentTransaction

```ts
interface PaymentTransactionResource {
  id: number;
  gateway: string;
  gateway_txn_id: string | null;
  amount: number;
  status: 'initiated' | 'pending' | 'success' | 'failed' | 'refunded';
  attempted_at: string | null;
}
```

### 4.6 Coupon (public + admin shapes diverge)

```ts
// Public list (lean)
interface CouponPublicResource {
  code: string;
  type: 'percentage' | 'flat' | 'first_order';
  value: number;
  description: string | null;
  badge_text: string | null;
  min_order_amount: number;
  max_discount: number | null;
  valid_until: string | null;

  // Phase 1.1 visual restoration fields (§2.9)
  image: string | null;
  urgency_text: string | null;
  rating: number | null;            // 0.0–5.0
  customers: number | null;         // raw count; UI formats with toLocaleString
}

// Validate-coupon response
interface CouponValidateResponse {
  valid: boolean;
  reason: string | null;          // why-not when invalid
  coupon: CouponPublicResource | null;
  discount_preview: number;
}
```

### 4.7 ServiceCenter

```ts
interface ServiceCenterListItem {
  id: number;
  slug: string;
  name: string;
  city: string;
  image: string | null;
  is_active: boolean;
}

interface ServiceCenterResource extends ServiceCenterListItem {
  address: string;
  state: string;
  pincode: string;
  phone: string;
  email: string | null;
  lat: number | null;
  lng: number | null;
  gallery: string[];
  opening_hours: Record<string, [string, string] | null>;
}
```

### 4.8 Testimonial (Decision D-D — hybrid Google + manual)

```ts
interface TestimonialResource {
  id: number;
  source: 'google' | 'manual';
  customer_name: string;
  initials: string;
  rating: number;                     // 1–5
  text: string;
  image: string | null;
  google_author_url: string | null;
  service_id: number | null;
  is_featured: boolean;
}
```
```php
// Public TestimonialResource — admin-only fields omitted.
public function toArray($r): array {
    return [
        'id'                => $this->id,
        'source'            => $this->source,
        'customer_name'     => $this->customer_name,
        'initials'          => $this->initials ?? mb_substr($this->customer_name, 0, 2),
        'rating'            => (int) $this->rating,
        'text'              => $this->text,
        'image'             => $this->image,
        'google_author_url' => $this->google_author_url,
        'service_id'        => $this->service_id,
        'is_featured'       => (bool) $this->is_featured,
    ];
    // Omitted: is_active, created_by_admin_id, synced_at, google_review_id (admin-only).
}
```

### 4.9 Faq

```ts
interface FaqResource {
  id: number;
  question: string;
  answer: string;
  scope: { type: 'service' | 'category' | 'page'; id: number } | null;
}
```

### 4.10 ServicePackage (list / detail)

```ts
interface ServicePackageListItem {
  id: number;
  slug: string;
  name: string;
  image: string | null;
  package_price: number;
  original_price: number | null;
  badge_text: string | null;
}

interface ServicePackageResource extends ServicePackageListItem {
  description: string | null;
  services: Array<{ id: number; slug: string; name: string }>;
}
```

### 4.11 Product (list / detail)

```ts
interface ProductListItem {
  id: number;
  slug: string;
  name: string;
  image: string | null;
  price: number;
  in_stock: boolean;
}

interface ProductResource extends ProductListItem {
  description: string | null;
  gallery: string[];
  sku: string;
  stock: number;
}
```

### 4.12 MembershipPackage

```ts
interface MembershipPackageResource {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  duration_months: number;
  price: number;
  benefits: string[];
  badge_text: string | null;
}
```

### 4.13 SiteInfo (config-derived, no DB row)

```ts
interface SiteInfoResource {
  name: string;
  tagline: string | null;
  about: string | null;
  phone: string;
  email: string;
  whatsapp: string;
  social: {
    facebook?:  string;
    twitter?:   string;
    instagram?: string;
    linkedin?:  string;
    youtube?:   string;
    whatsapp?:  string;
  };
  trust_points: string[];
  business_hours: Record<string, [string, string] | null> | null;
  /**
   * Per Decision D-B: tax rate exposed for UI hints only (e.g.
   * "(incl. GST)" labels). Binding computation stays server-side at
   * /checkout/quote and /checkout/place-order. 0 = no tax applied.
   */
  tax_percentage: number;
}
```
```php
// SiteInfoResource — backed by config('site') / .env. No model, no DB.
public function toArray($r): array {
    $cfg = config('site');
    return [
        'name'           => $cfg['name'],
        'tagline'        => $cfg['tagline']  ?? null,
        'about'          => $cfg['about']    ?? null,
        'phone'          => $cfg['phone'],
        'email'          => $cfg['email'],
        'whatsapp'       => $cfg['whatsapp'],
        'social'         => $cfg['social']   ?? (object) [],
        'trust_points'   => $cfg['trust_points']   ?? [],
        'business_hours' => $cfg['business_hours'] ?? null,
        'tax_percentage' => (float) ($cfg['tax_percentage'] ?? 0),    // D-B
    ];
}
```

---

## 5. ENDPOINT INVENTORY

### 5.1 AUTH

| # | Method | URI | Auth | Purpose | Request body | Response | Throttle |
|---|---|---|---|---|---|---|---|
| 1 | POST | `/auth/lead-capture`  | public  | upsert user by phone, send phone OTP, return pending_user_id | `{name, phone, email?}` | `{success, pending_user_id, otp_sent_to: 'phone'}` | 5/min/ip |
| 2 | POST | `/auth/send-otp`      | public  | (re)send OTP on a channel | `{channel: 'phone'\|'email', destination}` | `{success, expires_at, dev_code?}` | 5/min/ip |
| 3 | POST | `/auth/verify-otp`    | public  | verify code, mark user verified, issue Sanctum token | `{channel, destination, code}` | `{success, token, user: UserResource}` | 10/min/ip |
| 4 | POST | `/auth/login`         | public  | phone-only entry; triggers send-otp + returns pending state | `{phone}` | `{success, pending_user_id, otp_sent_to: 'phone'}` | 5/min/ip |
| 5 | POST | `/auth/logout`        | sanctum | revoke current token | – | `{success}` | 60/min/user |
| 6 | GET  | `/user/profile`       | sanctum | fetch logged-in user | – | `{user: UserResource}` | 120/min/user |
| 7 | PUT  | `/user/profile`       | sanctum | update name/email (phone change requires verify-otp) | `{name?, email?}` | `{user: UserResource}` | 60/min/user |

### 5.2 ADDRESSES (all `auth:sanctum`, `60/min/user`)

| # | Method | URI | Purpose | Request | Response |
|---|---|---|---|---|---|
| 8  | GET    | `/user/addresses` | list user's addresses | – | `{addresses: AddressResource[]}` |
| 9  | POST   | `/user/addresses` | create | `{label?, line1, line2?, city, state, pincode, landmark?, is_default?}` | `{address: AddressResource}` |
| 10 | PUT    | `/user/addresses/{id}` | update | partial of above | `{address: AddressResource}` |
| 11 | DELETE | `/user/addresses/{id}` | delete | – | `{success}` |

### 5.3 CART (custom mw `cart-session` — resolves cart by session_uuid OR auth user)

| # | Method | URI | Purpose | Request | Response | Throttle |
|---|---|---|---|---|---|---|
| 12 | GET    | `/cart`               | fetch active cart | – | `{cart: CartResource}` | 120/min |
| 13 | POST   | `/cart/items`         | add line | `{kind:'service'\|'package'\|'product', ref_id, quantity?, vehicle?:{brand_id,model_id,fuel_id}, meta?}` | `{cart: CartResource}` | 60/min |
| 14 | PUT    | `/cart/items/{id}`    | update qty/vehicle | `{quantity?, vehicle?}` | `{cart: CartResource}` | 60/min |
| 15 | DELETE | `/cart/items/{id}`    | remove line | – | `{cart: CartResource}` | 60/min |
| 16 | POST   | `/cart/coupon`        | apply. **If a coupon is already applied, the new code REPLACES the existing one (not stacked). Returns 422 if the new code is invalid (existing coupon stays).** Per D-E. | `{code}` | `{cart: CartResource}` (or 422 with reason) | 60/min |
| 17 | DELETE | `/cart/coupon`        | remove | – | `{cart: CartResource}` | 60/min |
| 18 | POST   | `/cart/merge`         | merge guest cart into user cart | `{session_uuid}` (auth required) | `{cart: CartResource}` | 10/min |

**`X-Cart-Session` header** is sent by the frontend on all unauthenticated cart calls.

### 5.4 CHECKOUT (`auth:sanctum`)

| # | Method | URI | Purpose | Request | Response |
|---|---|---|---|---|---|
| 19 | POST | `/checkout/quote`        | dry-run final pricing. **Computes** `tax = round(subtotal_after_discount * tax_percentage / 100, 2)` using `config('site.tax_percentage')` (default 0). Response `totals` include the actual tax line. Per D-B. | `{address_id, service_center_id?, slot_date, slot_time, vehicle:{brand_id,model_id,fuel_id}}` | `{cart: CartResource, totals: {...}}` |
| 20 | POST | `/checkout/place-order`  | persist order, return order_number + payment URL. **Same tax rule as #19**; persists computed tax to `orders.tax`. Per D-B. | same as quote + `{notes?}` | `{order: OrderResource, payment_url}` |

### 5.5 ORDERS (`auth:sanctum`)

| # | Method | URI | Purpose | Response |
|---|---|---|---|---|
| 21 | GET  | `/user/orders` | list (paginated) | `{data: OrderListItem[], meta: {current_page, last_page, total}}` |
| 22 | GET  | `/user/orders/{order_number}` | detail | `{order: OrderResource}` |
| 23 | POST | `/user/orders/{order_number}/cancel` | cancel (if status allows) | `{order: OrderResource}` |

### 5.6 PAYMENTS

| # | Method | URI | Auth | Purpose |
|---|---|---|---|---|
| 24 | POST | `/payments/initiate/{order_number}` | sanctum | return gateway redirect URL (stub: returns dev success URL) |
| 25 | POST | `/payments/callback`                | public  | gateway webhook (stub: marks order paid in dev) |
| 26 | GET  | `/payments/status/{order_number}`   | sanctum | poll status (lookup latest payment_transaction) |

### 5.7 CONTENT (all public, `120/min/ip`)

| # | Method | URI | Purpose | Response |
|---|---|---|---|---|
| 27 | GET  | `/service-centers` | list | `{centers: ServiceCenterListItem[]}` |
| 28 | GET  | `/service-centers/{slug}` | detail | `{center: ServiceCenterResource}` |
| 29  | GET  | `/testimonials` | featured slider for home/sections. **Filter:** `is_active=true AND rating >= 3` ORDER BY `rating DESC, is_featured DESC, position ASC`. **Limit: 5** (default; cap at 10 via `?limit=`). Per D-D. | `{testimonials: TestimonialResource[]}` |
| 29b | GET  | `/testimonials/all` | full testimonials page list. **Filter:** `is_active=true AND rating >= 4` ORDER BY `rating DESC, created_at DESC`. Paginated (`per_page=20`). Per D-D. | `{data: TestimonialResource[], meta: {current_page, last_page, total}}` |
| 30 | GET  | `/faqs?service_id=&category_id=&page_id=` | scoped | `{faqs: FaqResource[]}` |
| 31 | GET  | `/coupons` | active public coupons. **Filter:** `is_active=true AND (valid_from IS NULL OR valid_from ≤ now) AND (valid_until IS NULL OR valid_until > now) AND (usage_limit_total IS NULL OR redeemed_count < usage_limit_total)` where `redeemed_count = (SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = coupons.id)`. Same filter applies internally to `/coupons/validate` (#32) and `/cart/coupon` (#16) before approving any coupon. | `{coupons: CouponPublicResource[]}` |
| 32 | POST | `/coupons/validate` | dry-run validate against current cart (applies the §31 filter + per-user limit check) | `{result: CouponValidateResponse}` |
| 33 | GET  | `/service-packages` | list | `{packages: ServicePackageListItem[]}` |
| 34 | GET  | `/service-packages/{slug}` | detail | `{package: ServicePackageResource}` |
| 35 | GET  | `/products` | list | `{products: ProductListItem[]}` |
| 36 | GET  | `/products/{slug}` | detail | `{product: ProductResource}` |
| 37 | GET  | `/membership-packages` | list. **Filter:** `is_active=true ORDER BY position ASC`. Returns `[]` when no active plans exist (frontend hides section per D-A). | `{packages: MembershipPackageResource[]}` |
| 38 | GET  | `/site-info` | site-wide config (phone, email, social URLs, hours, trust points) — backed by `config/site.php`, no DB. Replaces frontend `BUSINESS_INFO` static. | `{site: SiteInfoResource}` |

### 5.8 ADMIN (deferred — Phase 4 / Filament)

Filament generates routes; this contract only commits to which entities
get admin CRUD:

- ServiceCategory, Service, CarBrand, CarModel, FuelType, ServicePrice (existing)
- User (read + role toggle, no password edit), OtpVerification (read-only audit)
- Address (read-only — users edit their own)
- Cart (read-only)
- Order (status edits, payment retries), OrderItem (read), PaymentTransaction (read)
- Coupon (full CRUD), CouponUsage (read)
- ServiceCenter, Testimonial, Faq, ServicePackage, Product, MembershipPackage (full CRUD).
  **Testimonial admin policy (Decision D-D):** admin can toggle
  `is_active` per row (enable/disable), create manual testimonials
  (`source='manual'`), and trigger Google Reviews sync (Phase 4 button
  — Phase 2 ships the sync stub). Admin **cannot** edit `text` /
  `rating` of `source='google'` rows (immutable from Google);
  can only toggle `is_active` and `is_featured` for them.
- Page, Section (existing — full CRUD)

---

## 6. STATE MACHINES & PROTOCOLS

### 6.1 Order status

```
pending ──► confirmed ──► in_progress ──► completed   (terminal)
   │            │              │
   ├────────────┼──────────────┴─────► cancelled       (terminal)
   │            │
   └────────────┴──────────────────────► refunded      (terminal, only after paid)
```

| From | Allowed → | Trigger |
|---|---|---|
| pending     | confirmed, cancelled                | admin/auto-confirm; user/admin cancel |
| confirmed   | in_progress, cancelled              | service center starts; admin cancel |
| in_progress | completed, cancelled                | service center marks done; admin cancel |
| completed   | refunded                            | admin only (post-paid) |
| cancelled   | (terminal)                          | – |
| refunded    | (terminal)                          | – |

### 6.2 Payment status

```
unpaid ──► pending ──► paid                            (terminal)
   │           │
   └───────────┴────► failed                           (retryable: new transaction row)
                          │
                          └─► (status returns to unpaid for retry)

paid ──► refunded                                      (terminal, admin only)
```

**Interaction with order status:**
| Order status | Allowed payment_status |
|---|---|
| pending     | unpaid, pending, paid, failed |
| confirmed   | paid (or unpaid for COD-style) |
| in_progress | paid |
| completed   | paid, refunded |
| cancelled   | unpaid, refunded |
| refunded    | refunded (must match) |

### 6.3 Cart lifecycle

```
created (status='active', expires_at=now+30d guest / +90d user)
   │
   ├──► converted (on /checkout/place-order success)         (terminal)
   │
   └──► abandoned (cron: status='active' AND expires_at < now)  (terminal)
```

Cleanup cron (hourly): `Cart::where('status','active')->where('expires_at','<',now())->update(['status'=>'abandoned'])`.
Real implementation in Phase 6.

### 6.4 OTP lifecycle

```
generated (verified_at=null, attempts=0, expires_at=now+10m)
   │
   ├──► verified (verified_at=now, attempts++)             (terminal)
   │
   ├──► expired (now >= expires_at, no verify)             (terminal — must regenerate)
   │
   └──► exhausted (attempts >= 3 with wrong code)          (terminal — must regenerate)
```

Re-`/auth/send-otp` invalidates prior unverified OTPs for same `(channel, destination)`
by setting their `verified_at = now()` with a sentinel marker, OR (cleaner)
deletes them. Only one active OTP per (channel, destination) at a time.

### 6.5 GUEST → USER MERGE PROTOCOL

**The most important Phase 2 protocol.** Detailed step-by-step:

```
(a) FIRST VISIT  (frontend, no auth)
    │
    ├─ localStorage["acr_cart_session"] = crypto.randomUUID()
    └─ all /cart/* requests set header: X-Cart-Session: <uuid>

(b) GUEST ADDS ITEM  (POST /cart/items, no auth, with X-Cart-Session)
    │
    ├─ middleware 'cart-session' resolves cart:
    │   if (auth user) → cart by user_id, status='active'
    │   else if (X-Cart-Session) → cart by session_uuid, status='active'
    │       (create if missing: row with session_uuid, user_id=null,
    │        expires_at=now+30d)
    │   else → 400 "Cart session required"
    └─ insert cart_item

(c) LEAD CAPTURE  (POST /auth/lead-capture {name,phone,email?})
    │
    ├─ User::firstOrCreate(['phone' => $phone], [name, email, role='customer'])
    ├─ generate OTP for channel='phone', destination=$phone
    ├─ persist otp_verifications row, expires=now+10m
    ├─ OtpDriver->send('phone', $phone, $code)
    └─ return { pending_user_id: user.id, otp_sent_to: 'phone' }

(d) VERIFY OTP  (POST /auth/verify-otp {channel,destination,code})
    │
    ├─ find active otp by (channel, destination, verified_at IS NULL)
    │   WHERE expires_at > now AND attempts < 3
    ├─ verify code (hash compare); on mismatch: attempts++, return 422
    ├─ on match:
    │   ├─ mark otp.verified_at = now
    │   ├─ user.is_verified_phone = true (or _email)
    │   ├─ user.last_login_at = now
    │   ├─ token = $user->createToken('app')->plainTextToken
    │   │
    │   ├─ MERGE STEP:
    │   │   $guestCart = Cart::where('session_uuid', $headerUuid)
    │   │                    ->where('status','active')->first();
    │   │   $userCart  = Cart::where('user_id', $user->id)
    │   │                    ->where('status','active')->first();
    │   │
    │   │   case 1: no guest cart       → no-op
    │   │   case 2: no user cart        → $guestCart->update(['user_id'=>$user->id])
    │   │   case 3: both exist          → for each $guestCart->items:
    │   │                                    upsert into $userCart by
    │   │                                    (kind, ref_id, vehicle), summing qty
    │   │                                  delete $guestCart
    │   └─ return { token, user: UserResource, cart: CartResource }

(e) FRONTEND POST-VERIFY
    │
    ├─ localStorage.removeItem("acr_cart_session")    (no longer needed)
    ├─ store token via setToken()
    └─ subsequent /cart/* calls authenticated, no X-Cart-Session header
```

**Race condition: same phone, two browsers, two guest UUIDs**

Scenario: User opens site on phone (UUID-A, items {Service-1}) and on
desktop (UUID-B, items {Service-2}) simultaneously. Verifies OTP on
desktop first.

Resolution at desktop verify:
1. Desktop carries `X-Cart-Session: UUID-B`. Merge step finds guest cart B.
   - User has no existing cart → cart B becomes user's. Items: {Service-2}.

User now opens phone. Phone still has UUID-A in localStorage and an
unauthenticated active cart (with {Service-1}). User verifies OTP on phone.

2. Phone carries `X-Cart-Session: UUID-A`. Merge step:
   - User HAS existing cart (the desktop one with {Service-2}).
   - For each item in cart A ({Service-1}): upsert into user cart (cart B).
   - Delete cart A.

Final user cart: {Service-1, Service-2}. **Last-write-wins as union.**
This is the documented intended behavior — never lose items.

Edge case: same `(kind, ref_id, vehicle)` in both carts → quantities
sum (defined by upsert key). Documented; not a "merge conflict."

### 6.6 Cart item price re-snapshot rule

```
On POST /cart/items
  ├─ For kind='service':
  │     unit_price_snapshot = lookup service_prices
  │       (service_id, brand_id, model_id, fuel_id) when vehicle is set,
  │       OR services.base_price when vehicle is null.
  │     If no service_prices row matches and base_price is null → 422.
  ├─ For kind='package'  → service_packages.package_price (vehicle-agnostic).
  └─ For kind='product'  → products.price            (vehicle-agnostic).

On PUT /cart/items/{id} when ANY of the following changes:
  quantity, vehicle.brand_id, vehicle.model_id, vehicle.fuel_id
  ├─ For kind='service' → re-fetch service_prices for the new tuple.
  │     If found      → update unit_price_snapshot.
  │     If not found  → 422 { reason: "No price configured for this vehicle." }
  ├─ For kind='package' → no re-snapshot (price is vehicle-agnostic).
  └─ For kind='product' → no re-snapshot.

The line_total in CartResource is always (unit_price_snapshot * quantity)
computed in the resource, never persisted. Cart totals (§4.3) are
re-derived on every read.
```

### 6.7 Coupon concurrency guarantee

```
At /checkout/place-order, the order-placement transaction MUST acquire
a row-level lock on the coupon BEFORE incrementing usage:

  DB::transaction(function () use ($cart, $couponId) {
      $coupon = Coupon::lockForUpdate()->find($couponId);   // SELECT ... FOR UPDATE
      $redeemedCount = CouponUsage::where('coupon_id', $coupon->id)->count();

      if ($coupon->usage_limit_total !== null
          && $redeemedCount >= $coupon->usage_limit_total) {
          throw new CouponExhaustedException();
      }
      // ... create order, create coupon_usage row, commit ...
  });

The /cart/coupon application (§5.3 #16) is a SOFT validate — the
final hard check happens at place-order under the row lock. This
prevents over-redemption when two users place orders simultaneously
near a usage_limit_total boundary; the second transaction will see
the first's coupon_usage row and reject with a graceful 409.

Per-user limit (usage_limit_per_user) is enforced under the same
lock by counting prior coupon_usages for (coupon_id, user_id).
```

**Stacking guarantee (Decision D-E):** `orders.coupon_id` is a single
nullable FK. No multi-coupon application path exists in the contract —
not in `cart_items`, not in `orders`, not in any pivot. Per Decision
D-E, this is **structural, not policy**: even if a future feature
request asks for stacking, it requires a schema migration (replace
`orders.coupon_id` with an `order_coupons` pivot) rather than a flag
flip. `/cart/coupon` semantics replace any existing coupon on the
cart; `/checkout/place-order` persists the single applied coupon to
`orders.coupon_id`.

---

## 7. OTP SERVICE INTERFACE

### 7.1 Contract

```php
namespace App\Services\Otp;

interface OtpDriverInterface
{
    /**
     * @param  'phone'|'email' $channel
     * @param  string          $destination  10-digit phone (no +91) or email
     * @param  string          $code         4–6 digit code, plaintext
     * @return bool                          delivered? (true means we trust it shipped)
     */
    public function send(string $channel, string $destination, string $code): bool;
}
```

### 7.2 Phase 2 implementations

```php
class DevModeOtpDriver implements OtpDriverInterface {
    // Logs to laravel.log; controller exposes the code in response
    // when APP_DEBUG=true (gated by env).
    public function send(string $channel, string $destination, string $code): bool {
        Log::info("[OTP/{$channel}] {$destination} → {$code}");
        return true;
    }
}

class SmtpEmailOtpDriver implements OtpDriverInterface {
    // Email-only — falls back to dev driver for phone channel.
    public function send(string $channel, string $destination, string $code): bool {
        if ($channel !== 'email') {
            return app(DevModeOtpDriver::class)->send($channel, $destination, $code);
        }
        Mail::to($destination)->queue(new OtpMail($code));
        return true;
    }
}
```

### 7.3 Container binding

```php
// AppServiceProvider::register()
$this->app->bind(OtpDriverInterface::class, function () {
    $key = config('services.otp.driver', 'dev');
    return match ($key) {
        'dev'        => new DevModeOtpDriver(),
        'smtp-email' => new SmtpEmailOtpDriver(),
        default      => throw new \RuntimeException("Unknown OTP driver: {$key}"),
    };
});
```

### 7.4 PRODUCTION SAFETY GUARD

```php
// AppServiceProvider::boot()
if (app()->environment('production')
    && app(OtpDriverInterface::class) instanceof DevModeOtpDriver
) {
    throw new \RuntimeException(
        'Refusing to boot: DevModeOtpDriver bound in production. '.
        'Set OTP_DRIVER in production .env to a real driver.'
    );
}
```

This guard fires at app boot — before any HTTP request can succeed.
**No silent dev-OTP in production, ever.**

### 7.5 OTP_DEV_BYPASS flag

```php
// VerifyOtpController::store()
if (env('OTP_DEV_BYPASS', false) === true && !app()->environment('production')) {
    if (preg_match('/^\d{4,6}$/', $request->code)) {
        // Accept any 4–6 digit code as valid in dev. Persisted otp_verifications
        // row is still created (audit), user.is_verified_phone flips to true.
        return $this->finishVerification($user, $channel);
    }
}
```

Per **Decision D-C**, the temporary launch posture is
`OTP_DEV_BYPASS=true` when `APP_ENV != production`. This means: any
4–6 digit code is accepted on `/auth/verify-otp` during development
and staging. The persisted `otp_verifications` row is still created
(for audit), `user.is_verified_phone` (or `_email`) flips to true.

The production safety guard from §7.4 STILL THROWS at boot if
`DevModeOtpDriver` is bound in production — so this bypass is
structurally impossible to ship. When real SMS/WhatsApp is procured
later, set `OTP_DEV_BYPASS=false` and bind a real driver. No code
change in controllers.

### 7.6 Future drivers (Phase 6+)

| Driver | Channel | Provider |
|---|---|---|
| `Msg91SmsDriver` | phone (SMS) | MSG91 |
| `Msg91WhatsAppDriver` | phone (WhatsApp) | MSG91 |
| `TwilioSmsDriver` | phone (SMS) | Twilio (alternate) |
| `MailgunOtpDriver` | email | Mailgun (alternate to SMTP) |

Adding any: implement interface + add to `match` block + `OTP_DRIVER` env value. No other code touches.

---

## 8. AUTH MIDDLEWARE MAP

| # | Endpoint | Middleware stack | Throttle |
|---|---|---|---|
| 1  | POST /auth/lead-capture           | `public` | 5/min/ip |
| 2  | POST /auth/send-otp               | `public` | 5/min/ip |
| 3  | POST /auth/verify-otp             | `public` | 10/min/ip |
| 4  | POST /auth/login                  | `public` | 5/min/ip |
| 5  | POST /auth/logout                 | `auth:sanctum` | 60/min/user |
| 6  | GET  /user/profile                | `auth:sanctum` | 120/min/user |
| 7  | PUT  /user/profile                | `auth:sanctum` | 60/min/user |
| 8  | GET    /user/addresses            | `auth:sanctum` | 120/min/user |
| 9  | POST   /user/addresses            | `auth:sanctum` | 60/min/user |
| 10 | PUT    /user/addresses/{id}       | `auth:sanctum` | 60/min/user |
| 11 | DELETE /user/addresses/{id}       | `auth:sanctum` | 60/min/user |
| 12 | GET    /cart                      | `cart-session` | 120/min |
| 13 | POST   /cart/items                | `cart-session` | 60/min |
| 14 | PUT    /cart/items/{id}           | `cart-session` | 60/min |
| 15 | DELETE /cart/items/{id}           | `cart-session` | 60/min |
| 16 | POST   /cart/coupon               | `cart-session` | 60/min |
| 17 | DELETE /cart/coupon               | `cart-session` | 60/min |
| 18 | POST   /cart/merge                | `auth:sanctum` | 10/min/user |
| 19 | POST /checkout/quote              | `auth:sanctum` | 60/min/user |
| 20 | POST /checkout/place-order        | `auth:sanctum` | 10/min/user |
| 21 | GET  /user/orders                 | `auth:sanctum` | 120/min/user |
| 22 | GET  /user/orders/{n}             | `auth:sanctum` | 120/min/user |
| 23 | POST /user/orders/{n}/cancel      | `auth:sanctum` | 10/min/user |
| 24 | POST /payments/initiate/{n}       | `auth:sanctum` | 10/min/user |
| 25 | POST /payments/callback           | `public` (signature-verified inside) | 60/min/ip |
| 26 | GET  /payments/status/{n}         | `auth:sanctum` | 120/min/user |
| 27–37 | Content (GET) + POST /coupons/validate | `public` | 120/min/ip (60/min for validate) |

### Custom middleware: `cart-session`

```php
// Stack effect:
//   if (Auth::guard('sanctum')->check()) → request->cart = active cart for user
//   elseif ($request->header('X-Cart-Session')) → resolve/create by session_uuid
//   else → 400 { "message": "Cart session required" }
//
// Sets request attribute 'cart' for the controller; never returns 401.
// /cart/* controllers read $request->cart->id directly.
```

### Throttle config (RouteServiceProvider::configureRateLimiting)

```php
RateLimiter::for('auth-public', fn ($r) => Limit::perMinute(5)->by($r->ip()));
RateLimiter::for('auth-verify', fn ($r) => Limit::perMinute(10)->by($r->ip()));
RateLimiter::for('cart-write',  fn ($r) =>
    Limit::perMinute(60)->by($r->user()?->id ?: $r->ip())
);
RateLimiter::for('user-read',   fn ($r) => Limit::perMinute(120)->by($r->user()?->id));
RateLimiter::for('user-write',  fn ($r) => Limit::perMinute(60)->by($r->user()?->id));
RateLimiter::for('public-read', fn ($r) => Limit::perMinute(120)->by($r->ip()));
```

---

## 9. TYPESCRIPT TYPES

To live at `src/types/api.ts` during Phase 2 implementation. Domain-grouped.

```ts
// ── Auth ──
export interface UserResource {
  id: number; name: string; phone: string; email: string | null;
  is_verified_phone: boolean; is_verified_email: boolean;
  role: 'customer' | 'admin';
  default_address: AddressResource | null;
  created_at: string; last_login_at: string | null;
}
export interface LeadCaptureRequest { name: string; phone: string; email?: string; }
export interface LeadCaptureResponse {
  success: true; pending_user_id: number; otp_sent_to: 'phone' | 'email';
}
export interface SendOtpRequest { channel: 'phone' | 'email'; destination: string; }
export interface SendOtpResponse { success: true; expires_at: string; dev_code?: string; }
export interface VerifyOtpRequest { channel: 'phone' | 'email'; destination: string; code: string; }
export interface VerifyOtpResponse {
  success: true; token: string; user: UserResource; cart?: CartResource;
}

// ── Address ──
export interface AddressResource {
  id: number; label: string; line1: string; line2: string | null;
  city: string; state: string; pincode: string; landmark: string | null;
  is_default: boolean;
}
export interface AddressInput {
  label?: string; line1: string; line2?: string;
  city: string; state: string; pincode: string;
  landmark?: string; is_default?: boolean;
}

// ── Cart ──
export type CartItemKind = 'service' | 'package' | 'product';
export interface CartItemResource {
  id: number; kind: CartItemKind; ref_id: number;
  name: string; slug: string; image: string | null;
  unit_price: number; quantity: number; line_total: number;
  vehicle: { brand_id: number; model_id: number; fuel_id: number } | null;
  meta: Record<string, unknown> | null;
}
export interface CartTotals {
  subtotal: number; discount: number;
  coupon: { code: string; type: string; value: number } | null;
  tax: number; total: number;
}
export interface CartResource {
  id: number; status: 'active' | 'converted' | 'abandoned';
  currency: 'INR'; expires_at: string;
  items: CartItemResource[]; totals: CartTotals; item_count: number;
}
export interface AddCartItemRequest {
  kind: CartItemKind; ref_id: number; quantity?: number;
  vehicle?: { brand_id: number; model_id: number; fuel_id: number };
  meta?: Record<string, unknown>;
}

// ── Order / Payment ──
export type OrderStatus =
  'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled' | 'refunded';
export type PaymentStatus =
  'unpaid' | 'pending' | 'paid' | 'failed' | 'refunded';
export interface OrderItemResource {
  id: number; kind: CartItemKind; ref_id: number | null;
  name: string; unit_price: number; quantity: number; line_total: number;
}
export interface OrderListItem {
  id: number; order_number: string;
  status: OrderStatus; payment_status: PaymentStatus;
  total: number; slot_date: string; slot_time: string;
  item_count: number; created_at: string;
}
export interface OrderResource extends OrderListItem {
  customer: { name: string; phone: string; email: string | null };
  vehicle: { brand: string; model: string; fuel: string };
  service_center: { id: number; name: string; address: string } | null;
  address: AddressResource | null;
  address_snapshot: {
    line1: string; line2: string | null; city: string; state: string;
    pincode: string; landmark: string | null;
  };
  items: OrderItemResource[];
  payments: PaymentTransactionResource[];
  totals: { subtotal: number; discount: number; tax: number; total: number };
  coupon: { code: string; description: string | null } | null;
  notes: string | null;
}
export interface PaymentTransactionResource {
  id: number; gateway: string; gateway_txn_id: string | null;
  amount: number; status: 'initiated' | 'pending' | 'success' | 'failed' | 'refunded';
  attempted_at: string | null;
}

// ── Coupons ──
export type CouponType = 'percentage' | 'flat' | 'first_order';
export interface CouponPublicResource {
  code: string; type: CouponType; value: number;
  description: string | null; badge_text: string | null;
  min_order_amount: number; max_discount: number | null;
  valid_until: string | null;
  // Phase 1.1 visual restoration fields
  image: string | null;
  urgency_text: string | null;
  rating: number | null;
  customers: number | null;
}
export interface CouponValidateResponse {
  valid: boolean; reason: string | null;
  coupon: CouponPublicResource | null; discount_preview: number;
}

// ── Content ──
export interface ServiceCenterListItem {
  id: number; slug: string; name: string; city: string;
  image: string | null; is_active: boolean;
}
export interface ServiceCenterResource extends ServiceCenterListItem {
  address: string; state: string; pincode: string;
  phone: string; email: string | null;
  lat: number | null; lng: number | null;
  gallery: string[];
  opening_hours: Record<string, [string, string] | null>;
}
export interface TestimonialResource {
  id: number; customer_name: string; initials: string;
  rating: number; text: string; image: string | null;
  service_id: number | null; is_featured: boolean;
}
export interface FaqResource {
  id: number; question: string; answer: string;
  scope: { type: 'service' | 'category' | 'page'; id: number } | null;
}

// ── Catalog ──
export interface ServicePackageListItem {
  id: number; slug: string; name: string; image: string | null;
  package_price: number; original_price: number | null; badge_text: string | null;
}
export interface ServicePackageResource extends ServicePackageListItem {
  description: string | null;
  services: Array<{ id: number; slug: string; name: string }>;
}
export interface ProductListItem {
  id: number; slug: string; name: string; image: string | null;
  price: number; in_stock: boolean;
}
export interface ProductResource extends ProductListItem {
  description: string | null; gallery: string[]; sku: string; stock: number;
}
export interface MembershipPackageResource {
  id: number; slug: string; name: string; description: string | null;
  duration_months: number; price: number; benefits: string[]; badge_text: string | null;
}

// ── Site config (no DB) ──
export interface SiteInfoResource {
  name: string;
  tagline: string | null;
  about: string | null;
  phone: string;
  email: string;
  whatsapp: string;
  social: {
    facebook?:  string;
    twitter?:   string;
    instagram?: string;
    linkedin?:  string;
    youtube?:   string;
    whatsapp?:  string;
  };
  trust_points: string[];
  business_hours: Record<string, [string, string] | null> | null;
  tax_percentage: number;     // Decision D-B (UI hint only; server-binding at checkout)
}
```

---

## 10. FRONTEND RE-WIRING PLAN

Eight currently-gated calls (FEATURES flags introduced in Phase 1.3),
mapped to the Phase 2 commit that re-enables them.

| # | Call site | Endpoint | Currently gated by | Re-enabled in commit | Components un-gated |
|---|---|---|---|---|---|
| 1 | `useAuth.ts:210`  | GET /user/profile      | `FEATURES.auth`            | **2.1** Auth + OTP        | `<Header>` (login button), `<MyBookings>` (auth wall), `<Cart>` (signup CTA) |
| 2 | `useAuth.ts:251`  | POST /auth/register    | `FEATURES.auth`            | **2.1** Auth + OTP        | `<AuthModal>` signup tab |
| 3 | `useAuth.ts:282`  | POST /auth/login       | `FEATURES.auth`            | **2.1** Auth + OTP        | `<AuthModal>` login tab |
| 4 | `useAuth.ts:306`  | POST /auth/logout      | `FEATURES.auth`            | **2.1** Auth + OTP        | `<Header>` logout |
| 5 | `useAuth.ts:329`  | PUT /auth/profile      | `FEATURES.auth`            | **2.1** Auth + OTP        | profile editor (page TBD) |
| 6 | `useAuth.ts:369`  | POST /user/addresses   | `FEATURES.auth`            | **2.2** Addresses         | `<Checkout>` address picker, `<MyBookings>` address book |
| 7 | `useCart.ts:88`   | POST /cart/sync        | `FEATURES.cartSync`        | **2.3** Cart + **2.4** merge | `<Cart>`, `<Header>` cart count (now from server) |
| 8 | `useAuth.ts:405`  | POST /checkout/offline | `FEATURES.offlineCheckout` | **2.5** Checkout + Orders  | `<Payment>` "Pay Later" button |

**Note:** `/auth/register` (call 2) and `/auth/login` (call 3) collapse
into the OTP flow in Phase 2:
- `/auth/register` → no longer exists; signup flows through
  `/auth/lead-capture` → `/auth/verify-otp`. The frontend `signup()` in
  `useAuth.ts` will call those two new endpoints sequentially.
- `/auth/login` exists but takes only `{phone}`, then proceeds via OTP.
  No password endpoint — OTP-only auth.
- `useAuth.ts:251,282` will need code changes (not just flag flip) to
  match the new contract. Documented as Phase 2.1 implementation work.

**Cart sync (call 7):** Phase 1's `useCart.ts` mirrors local items to
`/cart/sync` opportunistically. Phase 2 changes the model: cart is
server-authoritative once a session exists. The call is replaced with
proper `/cart/items` POST/PUT/DELETE on every mutation. The `cartSync`
flag stays a single bit (off → frontend keeps localStorage-only;
on → server is the source of truth).

**Phase 2.6 conditional-visibility rule (D-A):** `<MembershipSection>`
in the home page implements `if (data?.length === 0) return null` —
never renders an empty container, no skeleton, no "Coming soon" UI.
Same rule applies to any other section sourced from a list endpoint
that may return `[]` post-launch (currently only memberships).

**Phase 2.6 testimonials re-wiring (D-D):** `<TestimonialsSlider>` on
home/category pages calls `/testimonials` (max 5, rating ≥ 3) with a
"Read More" button linking to `/testimonials`. `<TestimonialsPage>`
calls `/testimonials/all` (rating ≥ 4, paginated). Both replace the
static `TESTIMONIALS` array from `src/data/businessData.ts`.

---

## 11. ASSUMPTIONS & DEFAULTS

Each is a candidate for product-owner override before contract approval.

| # | Decision | Default | Reason | Override? |
|---|---|---|---|---|
| 1  | OTP code length | 6 digits | Indian banking convention; matches MSG91 SMS templates | yes |
| 2  | OTP expiry | 10 minutes | Long enough for slow SMS, short enough to limit attack window | yes |
| 3  | OTP max attempts before regen required | 3 | Industry norm | yes |
| 4  | OTP regenerate cooldown | 30s (note: bypass mode per D-C ignores cooldown) | Prevents inbox flood while allowing genuine retries | yes |
| 5  | Cart expiry — guest | 30 days | Reasonable browsing window | yes |
| 6  | Cart expiry — user | 90 days | Long enough that abandoned carts stay queryable | yes |
| 7  | Order number format | `ACR-YYYY-NNNNNN` | Sortable, human-readable, branded | yes |
| 8  | Currency | INR fixed | Single-market launch; no multi-currency support in Phase 2 | yes |
| 9  | Coupon stacking | Forbidden — one coupon per order | Simpler UX + avoids multi-discount math edge cases | **LOCKED per Decision D-E** — no override planned. Structurally enforced: `orders.coupon_id` is a single nullable FK (not a pivot). |
| 10 | Address types | Free-text label, not enum | Avoids future migration when "Office" / "Friend" / etc. requested | yes |
| 11 | Membership tracking | Catalog only, no `user_memberships` pivot | Defers subscription mechanics to later phase | yes (open Q) |
| 12 | Tax rate | 0% (subtotal == taxable amount) | GST handling is its own project; leave hooks in place | yes |
| 13 | Email channel for OTP | Optional; phone is mandatory | Phone is the unique identifier per §2.1 | yes |
| 14 | Phone format | 10-digit Indian (no `+91` prefix stored) | All seeded phones match; UI strips prefix | yes |
| 15 | Auth strategy | OTP-only (no passwords, ever) | Phone is universal in target market; eliminates password reset flow | **decision needed** |
| 16 | Sanctum token expiry | Default Laravel (no expiry) | Simpler token UX; revocation on logout | yes |
| 17 | Order cancellation window | Allowed in `pending`, `confirmed`; not after `in_progress` | Customer can change mind before service starts | yes |
| 18 | Refund flow | Admin-initiated only | No self-serve refund button | yes |
| 19 | Payment gateway | Stub in 2.6, real integration deferred | Lets the rest of Phase 2 ship without gateway dependency | yes |
| 20 | FAQ scoping | Polymorphic to Service / ServiceCategory / Page; or global | Covers existing UI surfaces without new tables | yes |
| 21 | Testimonial source | Manually curated (admin-entered) — NOT user-submitted | Avoids review-moderation system this phase | yes |
| 22 | Coupon validation timing | At apply (POST /cart/coupon) AND at place-order (re-checked) | Prevents stale-coupon abuse | no — security |
| 23 | Merge race resolution | Last-write-wins union of cart items | Documented in §6.5; simplest non-lossy | yes |
| 24 | Soft deletes | NOT enabled in Phase 2 EXCEPT possibly users (deferred — `restrictOnDelete` from orders is used now; soft delete added in Phase 4 admin if user deletion becomes needed). All other tables stay hard-delete + `is_active`. | YAGNI — re-add per-table when first need appears; user deletion is the only realistic scenario | yes |
| 25 | Audit logs | Not in scope for Phase 2 | `coupon_usages`, `payment_transactions`, `otp_verifications` carry their own histories | yes |
| 26 | BUSINESS_INFO source | `config/site.php`, env-backed. **NO database table.** Frontend reads via the new public `GET /api/v1/site-info` endpoint that returns the config payload. Replaces the `BUSINESS_INFO` static const in `src/data/businessData.ts`. | Single-row, infrequently-changing data is overkill for a DB row + admin CRUD; a config file + redeploy is the right cadence | yes (override = move to a `site_settings` 1-row table if non-developer edits become routine) |
| 27 | Tax computation timing | Computed at checkout ONLY. `/cart` always returns `cart.totals.tax = 0`; `/checkout/quote` and `/checkout/place-order` apply tax using `config('site.tax_percentage')`, default 0. Persisted to `orders.tax`. Service detail / category pages display `base_price` (or vehicle-resolved price) WITHOUT tax. **Locked per Decision D-B.** | Cart pages preview pre-tax total (consistent with Indian e-commerce convention); tax only revealed at checkout step | no — locked |
| 28 | Auth temporary mode | dev/staging accepts any 4–6 digit OTP via `OTP_DEV_BYPASS=true`. **Production deploys MUST set `OTP_DEV_BYPASS=false` AND bind a real `OtpDriverInterface` implementation** (Msg91Sms, Msg91WhatsApp, Mailgun, etc.) before opening traffic. The §7.4 boot guard refuses to boot if `DevModeOtpDriver` is bound in production. **Per Decision D-C.** | Lets non-blocking dev/staging proceed without paid SMS provider; structural production guard prevents misconfiguration. | no — structural |

---

## 12. PHASE 2 IMPLEMENTATION PLAN — sequenced commits

### 2.1 Auth + OTP infrastructure
**Migrations:** extend `users` (phone, is_verified_phone, is_verified_email, last_login_at, role), create `otp_verifications`.
**Models:** extend `User`; new `OtpVerification`.
**Services:** `App\Services\Otp\OtpDriverInterface`, `DevModeOtpDriver`, `SmtpEmailOtpDriver` (skeleton). Container binding in `AppServiceProvider`. Production-safety guard. `OTP_DEV_BYPASS` flag.
**Controllers:** `Api\V1\Auth\LeadCaptureController`, `SendOtpController`, `VerifyOtpController`, `LoginController`, `LogoutController`, `Api\V1\User\ProfileController`.
**Resources:** `UserResource`.
**Routes:** 7 endpoints (§5.1).
**Sanctum:** wire stateful guard for `auth:sanctum`; ensure `EnsureFrontendRequestsAreStateful` is enabled in `Http\Kernel` (currently commented). Configure token name `app`.
**Env (per Decision D-C):** `OTP_DEV_BYPASS=true` in `.env.local` and `.env.staging` (any 4–6 digit code accepted on `/auth/verify-otp`). Production `.env` keeps `OTP_DEV_BYPASS=false` AND binds a real `OtpDriverInterface` — validated by the §7.4 boot guard refusing `DevModeOtpDriver` in production.
**Frontend:** flip `FEATURES.auth = true`. Rewrite `useAuth.ts` `signup`/`login` to call `/auth/lead-capture` → `/auth/verify-otp` chain. AuthModal UI for OTP entry step. `<Header>` Login/Signup buttons un-hide. Type imports from new `src/types/api.ts`.

### 2.2 User addresses
**Migrations:** `addresses`.
**Models:** `Address`; `User::addresses()`, `User::defaultAddress()`.
**Controllers:** `Api\V1\User\AddressController` (index, store, update, destroy).
**Resources:** `AddressResource`.
**Routes:** 4 endpoints (§5.2).
**Frontend:** flip `FEATURES.auth` paths in `useAuth.ts` for address ops. `<Checkout>` address picker connected. `<MyBookings>` address book.

### 2.3 Cart (server-authoritative)
**Migrations:** `carts`, `cart_items`.
**Models:** `Cart`, `CartItem`; relations across Service / ServicePackage / Product / vehicle FKs.
**Middleware:** `cart-session` (resolves cart by user OR session_uuid).
**Controllers:** `Api\V1\Cart\CartController` (show, addItem, updateItem, removeItem, applyCoupon, removeCoupon).
**Resources:** `CartResource`, `CartItemResource`.
**Services:** `App\Services\Cart\CartService` (compute totals, apply coupon, snapshot prices on add).
**Routes:** 6 endpoints (§5.3 minus merge — that's 2.4).
**Frontend:** keep `FEATURES.cartSync = false` for now — frontend reads/writes via new server endpoints directly (not the legacy `/cart/sync`). `useCart.ts` rewritten on top of `useApiQuery` (no more localStorage as source of truth). React Query keys: `['cart']`. Server is canonical.

### 2.4 Cart merge protocol
**Migration:** none new (uses 2.3 schema).
**Frontend:** generate session UUID on first visit, store in localStorage, send `X-Cart-Session` header on `/cart/*` calls. Token-aware switch: drop header once auth token exists.
**Controllers:** `Api\V1\Cart\MergeController` (POST /cart/merge — auth-only).
**Logic:** the merge step embedded in `VerifyOtpController` per §6.5(d). Standalone `/cart/merge` endpoint covers explicit re-merge / multi-device case.
**Frontend flip:** flip `FEATURES.cartSync = true` (means "server is authoritative"). Header cart count comes from `/cart` not localStorage.

### 2.5 Checkout + Orders
**Migrations:** `orders`, `order_items`, `payment_transactions`.
**Models:** `Order`, `OrderItem`, `PaymentTransaction`.
**Services:** `App\Services\Checkout\PlaceOrderService` (atomic transaction: snapshot cart → create order + items → mark cart converted → create payment_transaction[initiated]).
**Controllers:** `Api\V1\Checkout\QuoteController`, `PlaceOrderController`, `Api\V1\User\OrderController` (index, show, cancel).
**Resources:** `OrderResource` (full + list shapes), `OrderItemResource`, `PaymentTransactionResource`.
**Routes:** 5 endpoints (§5.4 + §5.5).
**Order number generator:** `App\Services\Order\OrderNumberGenerator::next()` (DB-locked sequence).
**Frontend flip:** flip `FEATURES.offlineCheckout = true`. `<Payment>` "Pay Later" calls `/checkout/place-order` then redirects to a thank-you page seeded with `OrderResource`. `<MyBookings>` reads from `/user/orders`.

### 2.6 Content + catalog
**Migrations:** `coupons`, `coupon_usages`, `service_centers`, `testimonials`, `faqs`, `service_packages`, `package_services`, `products`, `membership_packages`.
**Models:** all of above with relationships per §3.
**Controllers:** read-only `Api\V1\Content\*` controllers — `ServiceCenterController`, `TestimonialController`, `FaqController`, `CouponController` (with `validate` action), `ServicePackageController`, `ProductController`, `MembershipPackageController`.
**Payments stub:** `Api\V1\Payment\PaymentController` (initiate, callback, status — stub gateway returning dev success).
**Resources:** `ServiceCenterResource`, `TestimonialResource`, `FaqResource`, `CouponPublicResource`, `ServicePackageResource`, `ProductResource`, `MembershipPackageResource`.
**Routes:** 11 endpoints (§5.7) + 3 payment endpoints (§5.6).
**Seeders:** `ServiceCenterSeeder` (4 centres from `LOCATIONS` static), `TestimonialSeeder` (6 rows from the existing static `TESTIMONIALS` array as `source='manual'`; a small set of `source='google'` rows can be hand-seeded for QA visual testing — real Google Places API sync is Phase 4 admin work, per D-D), `FaqSeeder` (sample), `CouponSeeder` (5 from `OFFERS` static — `urgency_text`, `rating`, `customers`, `image` columns now exist per C1, mapped 1:1 from the static; the SHINE250 + ACCOOL20 enrichment from commit `6e3e9c1` carries through), `ServicePackageSeeder` (1–2 sample bundles), `MembershipPackageSeeder` (sample).
**Frontend:** strip `LOCATIONS`, `TESTIMONIALS`, `OFFERS`, `BUSINESS_INFO` from `src/data/businessData.ts` (or convert to fallback shape). Replace consumers with new endpoints. This commit also closes Phase 2 task #14 ("API endpoints for static-content gaps").

---

### Migrations summary

| Commit | Migrations introduced |
|---|---|
| 2.1 | `extend_users_for_auth`, `create_otp_verifications` |
| 2.2 | `create_addresses` |
| 2.3 | `create_carts`, `create_cart_items` |
| 2.4 | (none — code-only) |
| 2.5 | `create_orders`, `create_order_items`, `create_payment_transactions` |
| 2.6 | `create_coupons`, `create_coupon_usages`, `create_service_centers`, `create_testimonials`, `create_faqs`, `create_service_packages`, `create_package_services`, `create_products`, `create_membership_packages` |

**Total: 17 migrations (1 extension + 16 creates).**

---

## 13. EXISTING /home PAYLOAD KEYS — POST-PHASE-2 SOURCING

Per `/HOME_PAYLOAD_AUDIT.md`, today every key on `/api/v1/home` other
than `service_categories`, `car_brands`, and `settings` returns `[]`
or `null`. After Phase 2.6 lands, the same response shape is preserved
but each key has a real source. The `HomeController@index` controller
itself is **frozen** — no signature change — only its query
composition (or the seeded `pages`+`sections` rows it eager-loads) is
updated.

| Key | Source after Phase 2.6 | Implementation |
|---|---|---|
| `service_categories` | unchanged (existing) | already nested with `services` per Phase 1.6 |
| `car_brands`         | unchanged (existing) | – |
| `car_models`         | empty (lazy-fetch)   | – (frontend uses `/vehicle/models?brand_id=` after brand picked) |
| `service_centers`    | `service_centers` table | `ServiceCenter::where('is_active',true)->orderBy('position')->get()` |
| `offer_slider`       | `sections` JSON (page slug=`home`, section type=`offer_slider`) | populated via `Page::with('activeSections')->where('slug','home')->first()` then filtered to type |
| `tabular_offers`     | `sections` JSON (page slug=`home`, section type=`tabular_offer`) | same pattern as `offer_slider` |
| `service_packages`   | `service_packages` table | `ServicePackage::where('is_active',true)->orderBy('position')->limit(N)->get()` |
| `featured_products`  | `products WHERE is_featured=true AND is_active=true` | requires schema amendment — see §2.16 (`is_featured` column added in this revision) |
| `faqs`               | `faqs WHERE faqable IS NULL` (global) | `Faq::whereNull('faqable_type')->where('is_active',true)->orderBy('position')->get()` |
| `brand_logo_slider`  | `car_brands WHERE image IS NOT NULL AND is_active=true` | derived; no new storage |
| `membership_package` | `membership_packages` table | `MembershipPackage::where('is_active',true)->orderBy('position')->get()`. **Filter: `is_active=true`. Returns `[]` when none active — frontend hides section per D-A.** |
| `home_page_setting`  | `sections` JSON (page slug=`home`, section type=`hero`) | populated via the same Page+Section eager-load |
| `settings`           | unchanged (`config('app.name')`) | will be enriched to call `SiteInfoResource` shape inline (see §4.13) — same key, more keys inside |
| `seo`                | `sections` JSON (page slug=`home`, section type=`seo`) OR `pages.seo_*` | seed a `home` page row with seo fields populated |

**Deployment sequence for §13 to take effect (Phase 2.6 commit):**
1. Migrations create `service_centers`, `testimonials`, `faqs`,
   `service_packages`, `package_services`, `products`,
   `membership_packages` (per §12 commit 2.6).
2. Products migration adds `is_featured` (this revision, §2.16).
3. Seeders populate from frontend static (`LOCATIONS` →
   `service_centers`, `TESTIMONIALS` → `testimonials`, `OFFERS` →
   `coupons`, sample `service_packages`, sample `membership_packages`).
4. Page seeder creates a `home` page row with `sections` of types
   `hero`, `seo`, `offer_slider`, `tabular_offer`. Content payloads
   come from the legacy frontend hero copy + a Phase-2 admin handover.
5. `HomeController@index` is updated to compose the response from the
   new sources. Public response shape is unchanged — only the values
   transition from `[]`/`null` to populated.

The HomeController itself does NOT change in Phase 2 schema/route
listing — it stays at its existing `routes/api.php` mapping. The
controller's query composition is the implementation work, scoped
to commit 2.6.

---

## CHANGES (architectural review patch, this revision)

| Patch | Section(s) touched | One-line summary |
|---|---|---|
| C1 | §2.9, §4.6, §9 | Added `image`, `urgency_text`, `rating`, `customers` to `coupons` schema + `CouponPublicResource` + TS types — preserves Phase 1.1 (`6e3e9c1`) Offers card visual restoration. |
| C2 | §2.6, §11 (Assumption 24) | `orders.user_id` `cascadeOnDelete` → `restrictOnDelete`; rationale + Assumption 24 amended (soft-delete deferred to Phase 4 admin). |
| C3 | §2.6, §4.4 | `orders.vehicle_*_id` `cascadeOnDelete` → `nullOnDelete` + columns made nullable; new `orders.vehicle_snapshot` JSON; `OrderResource.vehicle` resolution falls back to snapshot when FKs are null. |
| C4 | §2.10 | `coupon_usages.coupon_id` `cascadeOnDelete` → `restrictOnDelete`; coupon redemption history now outlives coupon-row deletion (admin uses `is_active=false` instead). |
| G1 | §6.6 (new) | Cart item price re-snapshot rule on POST `/cart/items` and on PUT when `(quantity, vehicle.brand_id, vehicle.model_id, vehicle.fuel_id)` changes; service items re-fetch `service_prices`, products/packages stay vehicle-agnostic. |
| G2 | §5.7 #31 | `/coupons` list filter spelled out — `is_active=true AND valid_from window AND usage_limit_total not exhausted`; same filter applies to `/coupons/validate` and `/cart/coupon`. |
| G3 | §6.7 (new) | Coupon concurrency guarantee — `/checkout/place-order` MUST `SELECT … FOR UPDATE` on the coupon row before incrementing `coupon_usages`; `/cart/coupon` is soft-validate, hard check at place-order. |
| G4 | §4.13 (new), §5.7 #38 (new), §9, §11 (Assumption 26 new) | `BUSINESS_INFO` → `config/site.php` + new public `GET /api/v1/site-info` returning `SiteInfoResource`. No DB table. |
| G5 | §13 (new), §2.16 amendment | Sourcing map for every existing `/home` payload key post-Phase 2.6; `products.is_featured` column + composite index added to drive `featured_products`. |
| D-A | §2.17, §5.7 #37, §13, §10 | Membership conditional visibility — empty array hides section (no placeholder, no skeleton). |
| D-B | §11 #27, §4.13, §9, §5.4, §4.3 | Tax at checkout only; `tax_percentage` in site config; cart `totals.tax` always 0. |
| D-C | §7.5, §11 #4 #28, §12.1 | OTP_DEV_BYPASS=true in dev/staging; production guard from §7.4 unchanged. |
| D-D | §2.12 (replaced), §4.8 (replaced), §5.7 #29 + new #29b, §5.8, §10, §12.6 | Hybrid testimonials: Google + manual, admin enable/disable, listing rules (slider rating ≥ 3, page rating ≥ 4). |
| D-E | §11 #9, §2.6 inline, §5.3 #16, §6.7 | Coupon stacking forbidden — structurally enforced via single `orders.coupon_id` FK (no pivot). |

---

## End of contract
