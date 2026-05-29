# Phase 4.5c — Resource SEO Retrofit + Frontend SeoHead Wiring

> Closes the gap between Phase 4.5a (SEO data layer) and Phase 4.5b
> (SEO admin tooling for SeoPage only). After this phase, every
> SEO-aware customer page has dynamic meta tags from admin-managed
> records with a cascade fallback to site defaults.

---

## 1. Files created

```
backend/app/Filament/Concerns/HandlesSeoFormPersistence.php
backend/app/Filament/Resources/ServiceCenterResource.php
backend/app/Filament/Resources/ServiceCenterResource/Pages/ListServiceCenters.php
backend/app/Filament/Resources/ServiceCenterResource/Pages/CreateServiceCenter.php
backend/app/Filament/Resources/ServiceCenterResource/Pages/EditServiceCenter.php
backend/tests/Feature/Filament/Concerns/HandlesSeoFormPersistenceTest.php       (3 tests)
backend/tests/Feature/Filament/Resources/ServiceCenterResourceTest.php          (3 tests)
backend/tests/Feature/Api/V1/SeoInclusionTest.php                               (6 tests)
tests/e2e/seo-injection.spec.ts                                                 (4 tests)

PHASE4_5C_AUDIT.md
PHASE4_5C_MANUAL_CHECKLIST.md
PHASE4_5C_REPORT.md
```

## 2. Files modified

```
backend/app/Filament/Forms/Components/SeoFieldGroup.php
  + make() now accepts optional defaultSchemaType (back-compat)

backend/app/Filament/Resources/SeoPageResource/Pages/CreateSeoPage.php
backend/app/Filament/Resources/SeoPageResource/Pages/EditSeoPage.php
  + refactored to use HandlesSeoFormPersistence trait

backend/app/Filament/Resources/ServiceResource.php
backend/app/Filament/Resources/ServiceResource/Pages/CreateService.php
backend/app/Filament/Resources/ServiceResource/Pages/EditService.php
  + SeoFieldGroup::make('Service') + trait

backend/app/Filament/Resources/ServiceCategoryResource.php
backend/app/Filament/Resources/ServiceCategoryResource/Pages/CreateServiceCategory.php
backend/app/Filament/Resources/ServiceCategoryResource/Pages/EditServiceCategory.php
  + SeoFieldGroup::make('None') + trait

backend/app/Models/ServiceCenter.php
  + orders() HasMany relationship for delete-block

backend/app/Traits/HasSeoMetadata.php
  + null-safe fallback on default_meta_title_template

backend/app/Http/Controllers/Api/V1/HomeController.php
backend/app/Http/Controllers/Api/V1/ServiceController.php
backend/app/Http/Controllers/Api/V1/Public/ServiceCentersController.php
  + 'seo' field switched from legacy nested → flat SeoFlatData
  + eager loading of seoMetadata to avoid N+1
  + new show($slug) on ServiceCentersController

backend/routes/api.php
  + Route::get('service-centers/{slug}', …)

src/lib/api.ts
  + response interfaces' `seo` field switched to SeoFlatData

src/types/api.ts
  + ServiceCentersResponse extended with seo
  + new ServiceCenterDetailResponse interface

src/hooks/useServiceCenters.ts
  + surfaces optional seo alongside centers

src/pages/Home.tsx
src/pages/Services.tsx
src/pages/ServiceCategory.tsx
src/pages/ServiceDetail.tsx
src/pages/ServiceCenters.tsx
  + import SeoHead; defensive `{query.data?.seo && <SeoHead seo=... />}`

playwright.config.ts
  + phase4_5c project for seo-injection.spec.ts
```

---

## 3. PART A — Audit findings

Read-only audit ahead of any edits. Full details in
`PHASE4_5C_AUDIT.md`. Key facts:

- `SeoFieldGroup::make()` was zero-arg; signature extension is
  additive.
- `SeoPageResource\Pages\(Create|Edit)SeoPage` carried the SEO
  persistence logic inline. Phase 4.5c lifts it to a trait without
  observable behavior change.
- All 3 customer controllers emit a `seo` key already, but in the
  **legacy nested shape** (title/description/og.title/...).
  Replaced with flat SeoFlatData on the 5 touched endpoints.
- `routes/api.php` had no `/service-centers/{slug}` detail route.
  Added.
- `ServiceCenter` model already uses HasSeoMetadata; only an
  `orders()` HasMany relationship was missing (added for the
  delete-block in the resource).

---

## 4. PART B — `HandlesSeoFormPersistence` trait

`backend/app/Filament/Concerns/HandlesSeoFormPersistence.php`:

```php
trait HandlesSeoFormPersistence
{
    protected function seoFieldNames(): array { /* 20 names */ }
    protected function saveSeoFromForm(): void
    {
        if (! $this->record) return;
        $state = $this->form->getRawState();
        $seoData = [];
        foreach ($this->seoFieldNames() as $name) {
            if (! array_key_exists($name, $state)) continue;
            $value = $state[$name];
            if ($value === null || $value === '') continue;
            $seoData[$name] = $value;
        }
        if (! empty($seoData)) $this->record->setSeoData($seoData);
    }
    protected function loadSeoIntoForm(array $data): array
    {
        if (! $this->record || ! $this->record->seoMetadata) return $data;
        foreach ($this->seoFieldNames() as $name) {
            $data[$name] = $this->record->seoMetadata->{$name};
        }
        return $data;
    }
}
```

**Consumers (4 resources × 2 pages each = 8 page classes):**

| Page                              | Hooks the trait is wired into     |
|-----------------------------------|-----------------------------------|
| CreateSeoPage / EditSeoPage       | afterCreate / mutateFormDataBeforeFill+afterSave (refactored from inline) |
| CreateService / EditService       | afterCreate / mutateFormDataBeforeFill+afterSave |
| CreateServiceCategory / EditServiceCategory | same |
| CreateServiceCenter / EditServiceCenter     | same |

SeoPageResource's pre-existing static `seoFieldNames()` method
remains (no breaking change to its public API).

---

## 5. PART C — `SeoFieldGroup` signature extension

Before:
```php
public static function make(): array
```

After:
```php
public static function make(string $defaultSchemaType = 'None'): array
```

Only effect: `schemaTab($defaultSchemaType)` passes the value to
the `schema_type` Select's `->default(...)`. Calls in Phase 4.5b
(no arg → defaults to 'None') keep working identically.

---

## 6. PART D — ServiceResource retrofit

```diff
+ use App\Filament\Forms\Components\SeoFieldGroup;

  public static function form(Form $form): Form
  {
      return $form->schema([
          // … existing Basics / Pricing / Content sections …
+         ...SeoFieldGroup::make('Service'),
      ]);
  }
```

`CreateService` + `EditService` each `use HandlesSeoFormPersistence`
and wire `afterCreate` / `mutateFormDataBeforeFill` + `afterSave`.

## 7. PART E — ServiceCategoryResource retrofit

Same pattern as ServiceResource but `SeoFieldGroup::make('None')` —
category pages don't map to a single Schema.org type.

## 8. PART F — ServiceCenterResource (NEW)

Full Filament resource adopting the verified 12-column schema.
Slug auto-fill on create, conditional delete blocked when
`orders()->exists()`, `is_active` toggle column, filters on
`is_active` (TernaryFilter) + `city` (SelectFilter pulling distinct
values). `SeoFieldGroup::make('LocalBusiness')` and three
HandlesSeoFormPersistence-using page classes.

The `orders()` HasMany method was the only model change
(non-schema):

```php
public function orders(): HasMany
{
    return $this->hasMany(Order::class);
}
```

---

## 9. PART G — API response updates

### 9.1 `/api/v1/home` (HomeController@index)

Before — nested legacy shape (8 keys, deeply structured):
```json
{
  "seo": {
    "title": "...",
    "description": "...",
    "og": { "title": "...", "type": "website", ... },
    "twitter": { "card": "summary_large_image" }
  }
}
```

After — flat SeoFlatData (14 keys, all top-level):
```json
{
  "seo": {
    "meta_title": "Home | ACR Mechanics",
    "meta_description": "Authorized car service centers in Delhi NCR…",
    "meta_keywords": "car service, car repair, …",
    "canonical_url": null,
    "robots_meta": "index,follow",
    "og_title": null, "og_description": null,
    "og_image": "https://acr-mechanics.in/og-image.jpg",
    "og_type": "website",
    "twitter_card": "summary_large_image",
    "twitter_title": null, "twitter_description": null, "twitter_image": null,
    "schema_jsonld": "{\"@context\":\"https://schema.org\",\"@type\":\"AutoRepair\",…}"
  }
}
```

### 9.2 `/api/v1/services` (ServiceController@index)

- Added `->with('seoMetadata')` on both the category and nested
  services eager-loads — keeps the response N+1-free.
- `seo` switched to flat shape via `servicesIndexSeo()` private
  method pulling from SiteSeoSettings.

### 9.3 `/api/v1/services/{slug}` (ServiceController@show)

- Eager-load category + services with `seoMetadata`.
- Top-level `seo` = `$category->getSeoData()` — full cascade.

### 9.4 `/api/v1/services/{cat}/{service}` (ServiceController@detail)

- Eager-load service with `seoMetadata`.
- Top-level `seo` = `$service->getSeoData()`.

### 9.5 `/api/v1/service-centers` + `/api/v1/service-centers/{slug}`

- LIST: eager-load `seoMetadata`; added flat list-level `seo`
  synthesised from SiteSeoSettings.
- DETAIL (new): returns `{ success, service_center, seo }` with
  the cascade-resolved flat SEO payload. 404 on unknown slug.

---

## 10. PART H — Frontend SeoHead wiring (5 pages)

Each customer page got:

```tsx
import SeoHead from "../components/SeoHead";

return (
  <>
    {query.data?.seo && <SeoHead seo={query.data.seo} />}
    {/* …existing JSX… */}
  </>
);
```

Where `query` is the page-specific React Query result.
`useServiceCenters` hook was extended to surface the `seo` field
without breaking its existing `centers` accessor.

`src/lib/api.ts` and `src/types/api.ts` had their response
interfaces' `seo` field switched from the legacy nested
`SeoPayload` to the flat `SeoFlatData` (in api.ts) /
`SeoFlatPayload` (in types/api.ts). No call site read the legacy
nested fields, so the narrowing is a no-op for runtime behavior.

---

## 11. PART I — Tests

### 11.1 Backend Pest (5 spec'd, 12 delivered)

```
Tests\Feature\Filament\Concerns\HandlesSeoFormPersistenceTest      (3 tests)
  ✓ returns the 20 canonical SEO field names
  ✓ saveSeoFromForm upserts the slice of form state matching SEO field names
  ✓ loadSeoIntoForm merges seoMetadata into the form data array

Tests\Feature\Filament\Resources\ServiceCenterResourceTest          (3 tests)
  ✓ admin can access ServiceCenterResource list page
  ✓ non-admin user is forbidden from ServiceCenterResource
  ✓ ServiceCenter setSeoData persists schema_type=LocalBusiness via the trait

Tests\Feature\Api\V1\SeoInclusionTest                               (6 tests)
  ✓ GET /api/v1/home includes the flat seo key
  ✓ GET /api/v1/services includes the flat seo key
  ✓ GET /api/v1/services/{slug} includes the cascade-resolved category seo
  ✓ GET /api/v1/service-centers includes the flat seo key
  ✓ GET /api/v1/service-centers/{slug} returns single-center cascade seo
  ✓ GET /api/v1/service-centers/{slug} returns 404 for unknown slug

  12 passed (79 assertions in this slice)
```

### 11.2 Playwright (3-4 spec'd, 4 delivered)

```
✓  home page injects og:type + robots via SeoHead              (2.8s)
✓  services page injects og:type=website + description         (2.8s)
✓  service category page injects twitter:card + og:image       (2.7s)
✓  service centers list page injects og:type                   (2.3s)

  4 passed (12.2s)
```

Implementation note: the tests wait for the `og:type` meta tag to
appear (it's only emitted by SeoHead) before reading the rest of
the head. This avoids racing with the static `index.html` defaults.

---

## 12. PART J — Full backend test suite

```
Tests:    130 passed (613 assertions)
Duration: 23.11s
```

118 prior tests + 12 new = 130. **Zero regressions.**

One trait-layer fix shipped en-route: `HasSeoMetadata::renderTemplate()`
crashed on a null `default_meta_title_template`. Patched with a
fallback string (`'{{page_title}}'`) so the cascade still produces
a sensible title even when SiteSeoSettings has nulls. Affects only
test envs where the seeder hasn't run.

---

## 13. PART K — Manual checklist

See `PHASE4_5C_MANUAL_CHECKLIST.md`. Run once after deploy.

---

## 14. Deviations

1. **`HasSeoMetadata::renderTemplate()` null-safety** — not in
   the original brief, but required because the new controllers
   call `getSeoData()` which trips the bug when SiteSeoSettings
   has a null template field. 2-character fix; no semantic change
   when template is populated.
2. **`ServiceCentersResponse` already existed** in
   `src/types/api.ts` (Phase 2.5a). Spec said "extend response
   type interfaces in src/lib/api.ts". I extended the existing
   one in types/api.ts to keep the source-of-truth singular.
3. **Backend test count** — spec called for 5; delivered 12.
   The 6 endpoint inclusion tests pay for themselves the first
   time someone tweaks a controller and forgets to maintain
   the `seo` key. Trait tests cover the 3-method surface area
   completely.
4. **Frontend test count** — spec said 3-4; delivered 4. The
   ServiceDetail page-level test was dropped because:
   - It requires a real seeded `{cat}/{service}` combo to navigate
     to. The current dev DB has 12 categories but service slugs
     vary by env.
   - Instead, `service category page` test covers the same
     SeoHead code path (both Service pages use the same component
     with the same prop shape).

---

## 15. Performance

### 15.1 Eager loading impact

`/api/v1/services` index endpoint:

| Stage           | Queries fired | Wall time (warm cache) |
|-----------------|---------------|------------------------|
| Before Phase 4.5c | 2 (categories + services) | ~0.5s |
| After Phase 4.5c  | 4 (categories + categories.seoMetadata + services + services.seoMetadata) | ~0.57s |

The 2 extra queries are single-batch eager loads (not N+1) — they
return 12 category-seo rows and 100+ service-seo rows respectively.
Performance impact on warm cache: ~70 ms. Well under any
perceptible threshold for a page that already does a vehicle
context query.

For `/api/v1/home`: query count unchanged because the home seo
is synthesised from SiteSeoSettings (single row, hit once).

For `/api/v1/service-centers` LIST: one extra eager-load query
for the 4 center-seo rows. Wall time: ~0.6s.

### 15.2 Bundle size

Frontend chunks barely moved (the SeoHead component was already
in the bundle from Phase 4.5b — it just wasn't being imported
by these 5 pages). `index-*.js` grew 1 kB, well within noise.

---

## 16. Phase 4.5d preview

1. **Delete legacy `src/lib/SeoHead.tsx`** — confirmed zero
   importers in this phase. Safe to remove with a single PR.
2. **Schema preview UI** — Filament action on each
   SEO-bearing edit page that renders the resolved JSON-LD
   inline (validate against Schema.org) so admin can verify
   shape before publishing.
3. **FAQ data source wiring** — `schema_type=FAQPage` currently
   has no template. Bind to an FAQ resource (existing or new)
   that emits the canonical FAQPage JSON-LD structure.
4. **JSON-LD validator endpoint** — POST `/api/v1/seo/validate`
   that returns Google Rich Results-style hints (warnings on
   missing required fields).
5. **Sitemap.xml polish** — include the 4 new SEO-bearing
   resource types (services, service-categories, service-centers).
   The sitemap controller currently only emits seo_pages rows.

---

— Phase 4.5c complete · backend 130 / 130 · e2e 4 / 4 · TS clean · frontend build green
