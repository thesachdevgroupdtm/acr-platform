# Phase 4.5a — SEO Architecture Foundation

**Date:** 2026-05-08
**Scope:** Foundation only — polymorphic `seo_metadata` table,
single-row `site_seo_settings`, `url_redirects` table,
`HasSeoMetadata` trait, `SchemaTemplateEngine` service,
reusable Filament `SeoFieldGroup` component, 10 Pest tests.
**Status:** ✅ All deliverables green.
- Backend: **75 Pest tests pass** (65 prior + 10 new), 361 assertions.
- Frontend: **5 / 5** dev-server smoke + admin pass (no FE
  changes in this commit; regression-clean).

---

## 1. Files created

### Migrations
| File | Purpose |
|---|---|
| `backend/database/migrations/2026_05_08_084928_create_seo_metadata_table.php` | Polymorphic SEO record (25 columns, unique on seoable pair) |
| `backend/database/migrations/2026_05_08_084929_create_site_seo_settings_table.php` | Single-row site defaults (12 columns) |
| `backend/database/migrations/2026_05_08_084930_create_url_redirects_table.php` | 301/302 redirect mapping (9 columns, unique from_path) |

### Models
| File | Purpose |
|---|---|
| `backend/app/Models/SeoMetadata.php` | morphTo seoable; casts schema_data to array |
| `backend/app/Models/SiteSeoSettings.php` | `current()` singleton accessor; casts organization_jsonld to array |
| `backend/app/Models/UrlRedirect.php` | `findActiveFor()` lookup helper |

### Trait
| File | Purpose |
|---|---|
| `backend/app/Traits/HasSeoMetadata.php` | morphOne seoMetadata + getSeoData() (cascade) + setSeoData() (upsert helper) |

### Service
| File | Purpose |
|---|---|
| `backend/app/Services/SchemaTemplateEngine.php` | JSON-LD generator for 7 schema types + custom override + null-compaction |

### Filament
| File | Purpose |
|---|---|
| `backend/app/Filament/Forms/Components/SeoFieldGroup.php` | Reusable 5-tab form group (Basic/OG/Twitter/Schema/Advanced). NOT applied to any resource in 4.5a |

### Seeder
| File | Purpose |
|---|---|
| `backend/database/seeders/SiteSeoSettingsSeeder.php` | Idempotent (`updateOrCreate({id:1})`) site default values |

### Tests
| File | Tests | Category |
|---|---|---|
| `backend/tests/Feature/Seo/SeoMetadataPolymorphismTest.php` | 3 | morphOne attach (Service + ServiceCategory) + unique-constraint enforcement |
| `backend/tests/Feature/Seo/SiteSeoSettingsTest.php` | 2 | Singleton accessor + cascade fallback |
| `backend/tests/Feature/Seo/SchemaTemplateEngineTest.php` | 3 | LocalBusiness + Service templates + Custom override |
| `backend/tests/Feature/Seo/UrlRedirectTest.php` | 2 | Lookup + active filter |

### Documentation
| File | Purpose |
|---|---|
| `PHASE4_5A_ARCHITECTURE.md` | Polymorphic design, fallback chain, schema engine, Filament group, redirects, roadmap |
| `PHASE4_5A_REPORT.md` | This file |

## 2. Files modified

| File | Change |
|---|---|
| `backend/app/Models/Service.php` | Added `use HasSeoMetadata` (3-line import + trait line) |
| `backend/app/Models/ServiceCategory.php` | Added `use HasSeoMetadata` |
| `backend/app/Models/ServiceCenter.php` | Added `use HasSeoMetadata` |
| `backend/database/seeders/DatabaseSeeder.php` | Added `SiteSeoSettingsSeeder::class` to the call list |

No Filament resource pages modified (per HARD CONSTRAINT — that's Phase 4.5c).
No frontend files modified (per HARD CONSTRAINT — that's Phase 4.5b).

---

## 3. PART A — Architecture documentation

Full detail in `PHASE4_5A_ARCHITECTURE.md`. Sections:

1. **Polymorphic SEO design** — ER diagram + morphTo/morphOne pattern, list of models that get the trait in 4.5a vs later phases.
2. **Fallback chain** — 3-level cascade (resource SEO → site defaults → page-title literal); worked example for a Service without SEO record.
3. **Schema template engine** — 7 supported types, template + schema_data merge pattern, worked LocalBusiness example, Custom override.
4. **Filament SeoFieldGroup** — 5-tab structure, validation rules, helper-text style; live-preview deferred to 4.5d.
5. **URL redirects** — schema, lookup helper, hit-tracking deferral, catch-all middleware in 4.5b.
6. **Roadmap** — 4.5a (this commit) → 4.5b (SeoPage + frontend) → 4.5c (retrofit) → 4.5d (preview/validator).
7. **Out of scope** — explicit deferrals.

---

## 4. PART B — Migrations + schema verification

### `seo_metadata` (25 columns)

```
id, seoable_type, seoable_id,
meta_title (70), meta_description (160), meta_keywords (255),
canonical_url, robots_meta (default 'index,follow'),
og_title (70), og_description (200), og_image, og_keywords (255),
og_type (default 'website'),
twitter_card (default 'summary_large_image'),
twitter_title (70), twitter_description (200), twitter_image,
schema_type (default 'None'), schema_data (json), custom_jsonld (text),
include_in_sitemap (default true), priority (default 0.5),
changefreq (default 'monthly'),
created_at, updated_at

Indexes: PRIMARY (id), seoable_type+seoable_id, UNIQUE seoable pair
```

### `site_seo_settings` (12 columns)

```
id, default_meta_title_template (default '{{page_title}} | ACR Mechanics'),
default_meta_description, default_og_image,
default_twitter_handle, default_twitter_card (default 'summary_large_image'),
default_robots_meta (default 'index,follow'),
organization_jsonld (json),
google_site_verification, facebook_domain_verification,
created_at, updated_at
```

### `url_redirects` (9 columns)

```
id, from_path (unique), to_path,
status_code (default 301), is_active (default true),
hits (unsigned int, default 0), notes (text),
created_at, updated_at

Indexes: PRIMARY (id), UNIQUE from_path, (from_path, is_active)
```

---

## 5. PART C — Models

- `SeoMetadata` — fillable for all 20 SEO fields; casts
  schema_data to array, include_in_sitemap to boolean,
  priority to float; `seoable()` returns morphTo.
- `SiteSeoSettings` — fillable for 9 default fields; casts
  organization_jsonld to array; static `current()` returns
  the (always exactly one) row, creating it via `firstOrCreate`
  if the seeder hasn't run.
- `UrlRedirect` — fillable, casts is_active/status_code/hits;
  static `findActiveFor($path)` returns first active match or
  null.

---

## 6. PART D — HasSeoMetadata trait

```php
trait HasSeoMetadata {
    public function seoMetadata(): MorphOne   // morphOne(SeoMetadata, 'seoable')
    public function getSeoData(): array        // cascade-resolved 16-field array
    public function setSeoData(array $data): SeoMetadata  // upsert helper
    protected function renderTemplate(string $template, array $vars): string
}
```

Cascade order for every visible field:
**resource SEO → site defaults → resource name/title literal**

Applied (3-line `use HasSeoMetadata` lines) on:
- `App\Models\Service`
- `App\Models\ServiceCategory`
- `App\Models\ServiceCenter`

Phase 4.5c will surface this in the corresponding Filament forms; Phase 4.5b will create `SeoPage` that uses it from inception.

---

## 7. PART E — SchemaTemplateEngine

`generate(SeoMetadata $seo): ?string` returns:
- `null` if `schema_type === 'None'` AND no `custom_jsonld`.
- The raw `custom_jsonld` (after a `json_decode` sanity check) if present.
- A template-generated JSON-LD string for `LocalBusiness`,
  `Service`, `FAQPage`, `BreadcrumbList`, `Article` types.

Templates fill from the polymorphic `seoable` parent; operator
overrides go through `schema_data` (e.g. `priceRange`,
`openingHours`, `areaServed`).

Output is null-compacted: `null` keys are stripped before
encoding so the JSON-LD doesn't carry `"image": null,
"openingHours": null,` noise.

Latitude/longitude are cast through `(float)` before encoding
so the rendered JSON-LD has numeric coords rather than the
decimal-cast strings (`"28.6000000"`) Eloquent returns from a
decimal column.

---

## 8. PART F — Site settings seeded

`SiteSeoSettingsSeeder::run()` calls
`SiteSeoSettings::updateOrCreate({id:1}, [...])` with:
- `default_meta_title_template = '{{page_title}} | ACR Mechanics'`
- A canonical meta_description for Delhi NCR car repair.
- An `AutoRepair` organization_jsonld snippet (rendered on
  every page in 4.5b layout).
- All verification tokens null (operator fills in via Filament
  in 4.5d).

Wired into `DatabaseSeeder::run()` so a fresh `php artisan db:seed`
covers it. Verified in tinker:
```
id=1 template={{page_title}} | ACR Mechanics
org-jsonld type: AutoRepair
```

---

## 9. PART G — Filament SeoFieldGroup

Reusable component:

```php
SeoFieldGroup::make()  // returns array<int, Section>
```

Returns one collapsed `Section` containing five tabs:
- **Basic SEO** — meta_title, meta_description, meta_keywords,
  canonical_url, robots_meta.
- **Open Graph** — og_title, og_description, og_image,
  og_keywords, og_type.
- **Twitter Cards** — twitter_card, twitter_title,
  twitter_description, twitter_image.
- **Schema.org** — schema_type Select + dynamic schema_data
  KeyValue (visible only for templated types).
- **Advanced** — custom_jsonld, include_in_sitemap, priority,
  changefreq.

Operator-friendly helper text on every field. Validation rules
match Google's truncation budgets (70 chars for titles, 160
for descriptions).

**NOT applied to any resource in 4.5a** — Phase 4.5c does the
retrofit.

---

## 10. PART H — Tests (10 new, verbatim output)

```
   PASS  Tests\Feature\Seo\SchemaTemplateEngineTest
  ✓ it LocalBusiness template generates valid JSON-LD for a ServiceCenter   0.55s
  ✓ it Service template generates valid JSON-LD with offers from base_price 0.08s
  ✓ it Custom JSON-LD overrides the template path                            0.07s

   PASS  Tests\Feature\Seo\SeoMetadataPolymorphismTest
  ✓ it SeoMetadata can be attached to a Service via morphOne                 0.10s
  ✓ it SeoMetadata can be attached to a ServiceCategory                      0.10s
  ✓ it Each resource can have only one SeoMetadata (unique constraint)       0.08s

   PASS  Tests\Feature\Seo\SiteSeoSettingsTest
  ✓ it SiteSeoSettings::current() returns the seeded single row              0.09s
  ✓ it Resource without SEO falls back to site defaults via the cascade      0.08s

   PASS  Tests\Feature\Seo\UrlRedirectTest
  ✓ it URL redirect can be created and found by from_path                    0.08s
  ✓ it Inactive redirects are not returned by findActiveFor                  0.09s

  Tests:    10 passed (32 assertions)
  Duration: 1.57s
```

---

## 11. PART I — Full test suite output

### Backend Pest (verbatim final count)
```
Tests:    75 passed (361 assertions)
Duration: 27.41s
```

(65 prior — Phase 4.1/4.2/4.2.5 — plus 10 new SEO tests.)

### Frontend Playwright dev-server projects
```
[smoke] tests/e2e/smoke.spec.ts      ✓ 3/3
[admin] tests/e2e/admin-smoke.spec.ts ✓ 2/2

5 passed (26.4s)
```

No FE files were modified in this commit; the smoke + admin
projects confirm regression-clean.

---

## 12. PART J — Migration rollback verification

```
$ php artisan migrate:rollback --step=3
2026_05_08_084930_create_url_redirects_table .................... 22ms DONE
2026_05_08_084929_create_site_seo_settings_table ............... 10ms DONE
2026_05_08_084928_create_seo_metadata_table .....................  9ms DONE

$ php artisan db:table seo_metadata
WARN  Table [seo_metadata] doesn't exist.    ← rollback worked

$ php artisan migrate
2026_05_08_084928_create_seo_metadata_table ..................... 54ms DONE
2026_05_08_084929_create_site_seo_settings_table ............... 15ms DONE
2026_05_08_084930_create_url_redirects_table .................... 62ms DONE

$ php artisan db:seed --class=SiteSeoSettingsSeeder
INFO  Seeding database.                       ← reseed worked
```

All three migrations are reversible.

---

## 13. Build outputs

No frontend changes → no `tsc` / Vite re-build needed. Backend
PHP files all lint-clean (`php -l` on every new file).

---

## 14. Deviations

1. **Latitude/longitude cast to float in template engine.** The
   `service_centers.latitude` column is `decimal(8,7)` which
   Eloquent's decimal cast returns as the string
   `"28.6000000"`. The LocalBusiness template casts through
   `(float)` so the JSON-LD output has numeric coordinates
   (matching Schema.org's `GeoCoordinates` spec). Test was
   adjusted to assert the float, not the string.

2. **`offers.price` test asserts numeric value, not type.**
   `json_encode(1500.0)` round-trips back as PHP int 1500.
   The test casts back to float for the comparison rather than
   asserting `===` against `1500.0`.

3. **No `make:model` calls used.** Model files were created
   directly via `Write` to keep them in the documented format
   (PSR-12 + project header docblock convention) without the
   `php artisan make:model` boilerplate that we'd just delete.

4. **`app(SchemaTemplateEngine::class)` resolution path.** The
   trait calls the engine via Laravel's container, NOT via
   direct `new SchemaTemplateEngine()`. Lets future phases
   bind a mock for testing or a custom subclass without
   touching the trait.

5. **JSON-LD null compaction.** The template engine strips
   `null` keys recursively before `json_encode` so the output
   doesn't carry `"image": null, "openingHours": null,` noise.
   Pure presentation polish; no functional impact.

---

## 15. Phase 4.5b preview

**Theme:** SeoPage resource + frontend integration.

Likely scope:
- New `seo_pages` migration (slug, title, body, layout, status,
  published_at) — the editable content pages currently hard-coded
  in the React frontend.
- New `App\Models\SeoPage` with `HasSeoMetadata` baked in.
- `SeoPageResource` Filament page with `SeoFieldGroup` surfaced
  (the first real consumer of the group built in 4.5a).
- Frontend catch-all route `/:slug` that fetches the SeoPage
  by slug, renders the body, and injects SEO via
  `react-helmet-async` (one new package).
- `UrlRedirect` middleware on the catch-all so 301s fire before
  the page lookup.
- Sitemap.xml generator that walks every `HasSeoMetadata`
  resource where `include_in_sitemap=true`.

Estimated effort: **~3 days**. New tests: ~10 backend (resource
CRUD + middleware) + ~10 frontend (Playwright catch-all +
helmet injection + redirect honor).
