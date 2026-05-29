# Phase 4.5.3 — Master Data Audit (PART A)

**Date:** 2026-05-09
**Scope:** Read-only verification of car_brands / car_models / services /
seo_pages state before building Lookup endpoints + LeadFormWidget +
hero pinning.

---

## 1. Master data row counts

| Table | Total rows | Active rows | Suitable? |
|---|---|---|---|
| `car_brands` | 14 | 14 | ✓ |
| `car_models` | 81 | 81 | ✓ |
| `services` | 40 | 40 | ✓ |
| `service_categories` | 12 | n/a | ✓ |

**No seeding needed.** All three lookup tables have well above the
"5 brands × 3 models × N services" minimum threshold from the spec.

Sample relationship integrity check: `maruti-suzuki` has 9 active
models. Cascade is wired correctly via `brand_id` FK.

---

## 2. Column structure (verified per existing models)

### `car_brands` — `App\Models\CarBrand`

```php
$fillable = ['name', 'slug', 'image', 'is_active'];
$casts    = ['is_active' => 'boolean'];

models()  // hasMany CarModel via brand_id
prices()  // hasMany ServicePrice via brand_id
```

No `active()` scope on the model — `where('is_active', true)` is the
canonical filter (used elsewhere in the codebase).

### `car_models` — `App\Models\CarModel`

```php
$table    = 'car_models';
$fillable = ['brand_id', 'name', 'slug', 'image', 'is_active'];
$casts    = ['is_active' => 'boolean'];

brand()  // belongsTo CarBrand via brand_id
prices() // hasMany ServicePrice via model_id
```

### `services` — `App\Models\Service`

```php
$fillable = [
  'category_id', 'name', 'slug', 'description', 'image',
  'base_price', 'time_takes', 'time_unit',
  'warrenty_info', 'recommended_info', 'note', 'is_active',
];
$casts = [
  'is_active'  => 'boolean',
  'base_price' => 'decimal:2',
];

category() // belongsTo ServiceCategory via category_id
prices()   // hasMany ServicePrice via service_id
```

`ServiceCategory` has `id, slug, name`. Eager-load with
`with('category:id,slug,name')` to keep the lookup payload small.

---

## 3. Existing factories (Phase 4.2 audit confirmed)

- `Database\Factories\CarBrandFactory` — generates with unique
  `name`, slug suffix, `is_active=true`.
- `Database\Factories\CarModelFactory` — accepts `brand_id`
  (defaults to `CarBrand::factory()`).
- `Database\Factories\ServiceFactory` — exists, generates with
  category. (Listing confirmed in audit.)
- `Database\Factories\ServiceCategoryFactory` — exists.

All four factories present. Lead tests can compose them freely.

**No factory needed for `Lead`** — created in PART D will get its
own `LeadFactory`.

---

## 4. SeoPage hero pinning state

### Currently pinned (3 of 12+ pages)

| slug | hero_priority | view_count |
|---|---:|---:|
| `mercedes-service-delhi` | 1 | 1002 |
| `bmw-vs-audi-service-comparison` | 2 | 460 |
| `luxury-car-detailing-services` | 3 | 640 |

### Top 8 by view_count (current state)

```
mercedes-service-delhi          pinned=Y pri=1 views=1002
bmw-ac-repair-gurugram          pinned=- pri=- views=942
audi-brake-pad-replacement      pinned=- pri=- views=880
car-battery-replacement-cost    pinned=- pri=- views=820
monsoon-tyre-care-guide         pinned=- pri=- views=760
winter-car-care-checklist       pinned=- pri=- views=700
luxury-car-detailing-services   pinned=Y pri=3 views=640
dent-paint-repair-noida         pinned=- pri=- views=580
```

### Recommended pinning order (PART H)

Per D-4.5.3-7: "Mark 2 more as is_pinned=true (top 2 by view_count
that aren't already pinned). Set hero_priority for all 5 (1 through
5)."

Top 2 unpinned by view_count: `bmw-ac-repair-gurugram` (942) and
`audi-brake-pad-replacement` (880).

Final 5 pinned, ordered by view_count → hero_priority:

| hero_priority | slug | view_count | source |
|---:|---|---:|---|
| 1 | `mercedes-service-delhi` | 1002 | already pinned |
| 2 | `bmw-ac-repair-gurugram` | 942 | **NEW PIN** |
| 3 | `audi-brake-pad-replacement` | 880 | **NEW PIN** |
| 4 | `luxury-car-detailing-services` | 640 | already pinned |
| 5 | `bmw-vs-audi-service-comparison` | 460 | already pinned |

This honours both rules: the existing 3 pins stay (no churn), the 2
new pins are the top 2 unpinned, and hero_priority cleanly enumerates
1-5 by view_count desc.

PART H will execute via a one-off tinker command (no migration —
data update only) + `php artisan cache:clear`.

---

## 5. Newsletter infrastructure inventory (PART B targets)

Files to delete:

```
src/components/explore/widgets/NewsletterWidget.tsx
backend/app/Http/Controllers/Api/V1/Public/NewsletterController.php
backend/tests/Feature/Newsletter/NewsletterSubscribeTest.php
backend/tests/Feature/Newsletter/  (the directory itself, post-delete)
```

Files to edit:

```
src/lib/api.ts                          → drop subscribeToNewsletter() + types
backend/routes/api.php                  → drop POST /v1/newsletter/subscribe
src/pages/ExploreEditorial.tsx          → swap NewsletterWidget → LeadFormWidget
```

Migrations:

```
NEW: 2026_05_09_*_drop_newsletter_subscriptions_table.php
     up()   → dropIfExists('newsletter_subscriptions')
     down() → recreate the same shape (rollback safety)
```

Existing migration `2026_05_09_080534_create_newsletter_subscriptions_table.php`
stays in the migration history (immutable). The new drop migration runs
on top of it, leaving migration order intact.

Test count delta: `-2` (NewsletterSubscribeTest had 2 tests).

---

## 6. Endpoints / files to be CREATED

| Concern | Path |
|---|---|
| Lookup controller | `backend/app/Http/Controllers/Api/V1/Public/LookupController.php` |
| Lookup tests | `backend/tests/Feature/Lookups/LookupTest.php` (3 tests) |
| Lead migration | `backend/database/migrations/2026_05_09_*_create_leads_table.php` |
| Lead model | `backend/app/Models/Lead.php` |
| Lead factory | `backend/database/factories/LeadFactory.php` |
| Lead controller | `backend/app/Http/Controllers/Api/V1/Public/LeadController.php` |
| Lead submit tests | `backend/tests/Feature/Leads/LeadSubmitTest.php` (4 tests) |
| Filament resource | `backend/app/Filament/Resources/LeadResource.php` (+ Pages) |
| Filament test | `backend/tests/Feature/Admin/Resources/LeadResourceTest.php` (2 tests) |
| Frontend hooks | `src/hooks/explore/useLookups.ts`, `src/hooks/explore/useLeadSubmit.ts` |
| Frontend widget | `src/components/explore/widgets/LeadFormWidget.tsx` |
| Frontend tests | `tests/e2e/explore-lead-form.spec.ts` (3 tests) |
| Reports | `PHASE4_5_3_AUDIT.md` (this), `PHASE4_5_3_REPORT.md` (PART J) |

Test delta projection: 111 (Phase 4.5.2) - 2 (Newsletter removed) +
3 (Lookup) + 4 (Lead) + 2 (LeadResource) = **118 backend tests**.

Frontend SEO Playwright: 15 (Phase 4.5.2) + 3 (lead-form) - 0 (no
existing newsletter spec to remove; one was never written) = **18 SEO
Playwright tests**.

---

## 7. Existing patterns to copy

- **API response envelope:** existing public controllers (e.g.
  `SeoPageController::payload`) return raw arrays/JSON, not a
  `{data: [...]}` wrapper. To match Phase 4.5.3 spec ("Response:
  [{id, slug, name}, ...]") I'll return arrays directly. The frontend
  hooks per spec read `r.data.data` though — so use `LookupResource`
  (`->response()` returns `{data: [...]}` shape via JsonResource
  defaults). Spec sample uses `r.data.data` → keep the JsonResource
  envelope.

- **Throttle middleware:** `'public-read'` is registered in Phase 4.5
  (verified in routes file). New lookup routes use it.

- **Cache:** `Cache::remember($key, $seconds, fn)` — standard pattern
  in `SeoPageController`.

- **Lead spam check:** count of phone in last 24h → if `>= 3`, set
  `status='spam'` automatically. Don't tell the user (still 200 OK).

— end of audit —
