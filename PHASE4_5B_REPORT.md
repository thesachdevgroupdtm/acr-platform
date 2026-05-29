# Phase 4.5b — SEO Pages Backend + Frontend Integration

**Date:** 2026-05-08
**Scope:** First end-to-end use of the Phase 4.5a SEO foundation:
new `seo_pages` table, Filament `SeoPageResource` (the first
consumer of the reusable `SeoFieldGroup`), public API endpoints
(`/seo-pages/{slug}`, `/explore`, `/explore/categories`,
`/sitemap.xml`), URL-redirect handling, frontend `/:slug`
catch-all + `/explore` hub, `react-helmet-async` integration.
**Status:** ✅ All deliverables green.
- Backend: **91 Pest tests pass** (75 prior + 16 new), 423 assertions.
- Frontend: **32 / 32** dev-server tests pass across smoke,
  admin, api-integration, edges, and the new `seo` project.
- Single new package: `react-helmet-async@^3.0.0`.

---

## 1. Files created

### Migration
| File | Purpose |
|---|---|
| `backend/database/migrations/2026_05_08_094008_create_seo_pages_table.php` | seo_pages table (slug unique, indexed; foreign key to users.created_by; (is_published, published_at) compound index) |

### Models
| File | Purpose |
|---|---|
| `backend/app/Models/SeoPage.php` | HasSeoMetadata + saving event sanitization + auto published_at + sitemap cache invalidation + getRelatedPages() + reservedSlugs() |

### Controllers
| File | Purpose |
|---|---|
| `backend/app/Http/Controllers/Api/V1/Public/SeoPageController.php` | show / explore / categories actions (redirect-first, paginated, filterable) |
| `backend/app/Http/Controllers/Api/V1/Public/SitemapController.php` | sitemap.xml generator with 1-hour Cache::remember + chunked iteration |

### Filament resource
| File | Purpose |
|---|---|
| `backend/app/Filament/Resources/SeoPageResource.php` | Form schema (with `SeoFieldGroup::make()` first consumer), table columns, filters, actions |
| `backend/app/Filament/Resources/SeoPageResource/Pages/ListSeoPages.php` | (scaffold, default) |
| `backend/app/Filament/Resources/SeoPageResource/Pages/CreateSeoPage.php` | mutateFormDataBeforeCreate (created_by) + afterCreate (saveSeoFromForm) |
| `backend/app/Filament/Resources/SeoPageResource/Pages/EditSeoPage.php` | View-page header action + mutateFormDataBeforeFill (hydrate SEO fields) + afterSave (persist SEO fields) |

### Seeder
| File | Purpose |
|---|---|
| `backend/database/seeders/SeoPageSeeder.php` | 4 sample pages spanning Brand Service / City Service / Maintenance Tips, each with a SEO record |

### Tests (16 new backend)
| File | Tests |
|---|---|
| `backend/tests/Feature/Seo/SeoPageResourceTest.php` | 6 — admin/non-admin access, reservedSlugs membership, sanitizeHtml whitelist, save-event sanitization, auto published_at |
| `backend/tests/Feature/Api/V1/SeoPageEndpointTest.php` | 7 — show success + 404 + redirect; related ordering; explore filter/search/categories |
| `backend/tests/Feature/Api/V1/SitemapTest.php` | 3 — XML structure, include_in_sitemap honoured, cache invalidation on save |

### Frontend code
| File | Purpose |
|---|---|
| `src/components/SeoHead.tsx` | react-helmet-async wrapper for the FLAT SeoFlatData shape returned by the new endpoint |
| `src/pages/SeoPageView.tsx` | /:slug renderer with reserved-slug guard, redirect handling, NotFound on 404, related-articles section |
| `src/pages/ExplorePage.tsx` | /explore hub with category dropdown + search + paginated cards |
| `tests/e2e/seo-pages.spec.ts` | 6 frontend tests (render, helmet title, og:meta, reserved slug guard, 404, related section) |
| `tests/e2e/explore.spec.ts` | 4 frontend tests (cards render, category filter, search, click→navigate) |

### Documentation
| File | Purpose |
|---|---|
| `PHASE4_5B_REPORT.md` | This file |
| `PHASE4_5B_MANUAL_CHECKLIST.md` | 25+ operator-verifiable items |

## 2. Files modified

| File | Change |
|---|---|
| `backend/app/Models/SeoMetadata.php` | Added `booted()` with sitemap cache invalidation on saved/deleted |
| `backend/database/seeders/DatabaseSeeder.php` | Added `SeoPageSeeder::class` to the call list |
| `backend/routes/api.php` | Registered 4 routes: explore/categories, explore, seo-pages/{slug}, sitemap.xml. Order matters — explore/categories declared before the {slug} catch-segment |
| `src/main.tsx` | Added `<HelmetProvider>` wrapper outside `<QueryClientProvider>` |
| `src/lib/api.ts` | Added 7 typed interfaces (SeoFlatData, SeoPagePayload, etc.) + `fetchSeoPage`, `fetchExplore`, `fetchExploreCategories` helpers |
| `src/App.tsx` | Lazy imports for ExplorePage + SeoPageView; routes `/explore` and `/:slug` added before the `*` catch-all |
| `playwright.config.ts` | Added `seo` project pointing to localhost:3000 with testMatch for the two new specs |
| `package.json` | `react-helmet-async@^3.0.0` (single new dep) |

No Phase 4.5a foundation files modified beyond the documented
`SeoMetadata::booted()` cache hook (the spec explicitly required
this addition to D-4.5b-9). No customer auth/cart business
logic touched.

---

## 3. PART A — SeoPage model + migration

`seo_pages` schema verified via `php artisan db:show`:
- 14 columns (id + 11 content + 2 timestamps); `created_by` FK to users (set null on delete)
- Indexes: PRIMARY (id), UNIQUE slug, category, (is_published, published_at) compound

Model behaviour:
- `HasSeoMetadata` trait wired (Phase 4.5a).
- `saving` event: `body` is run through `strip_tags(.., ALLOWED_HTML_TAGS)`; `published_at` auto-stamped on first publish.
- `saved` + `deleted` events: bust the `sitemap_xml` cache key.
- `getRelatedPages($limit=3)`: `category` match OR `tag` match (whereJsonContains), excluding self, ordered by `published_at desc`. Falls back to "most recent" when neither dimension exists on the source page.
- `reservedSlugs()` returns 33 entries covering auth/account/system routes + `payment` (preserves the Phase 2.6a-fix smoke invariant) + `explore`/`home`/`index`/`main`.

`SeoMetadata` got a tiny addition: `booted()` with the same cache-bust hook. SEO record changes invalidate the sitemap cache transitively (since the sitemap pulls priority/changefreq/include_in_sitemap from there).

---

## 4. PART B — Filament SeoPageResource

**First consumer of `SeoFieldGroup::make()`** (Phase 4.5a built it but didn't apply it anywhere). The resource form is composed of:

```
[Page Content]    — title, slug (unique + reserved guard), excerpt, body (RichEditor)
[Categorization]  — category (datalist suggestions), tags (TagsInput)
[Call-to-Action]  — collapsed by default; cta_title / button_text / button_url
[Publishing]      — is_published toggle, published_at picker
[SEO Settings]    — collapsed; 5 tabs from SeoFieldGroup
```

**Slug validation:** Filament `unique(ignoreRecord)` rule + a custom Closure rule that calls `SeoPage::reservedSlugs()`. The DB unique index is the last-resort safety net.

**SEO field persistence:** The form returns 20 SEO fields mixed with the SeoPage columns. `Create/EditSeoPage::saveSeoFromForm()` slices the form state by `SeoPageResource::seoFieldNames()` and upserts via `setSeoData()`. `EditSeoPage::mutateFormDataBeforeFill` hydrates the same fields from `$record->seoMetadata` so editing shows the saved values.

**Table:** title (searchable), slug (mono, copyable), category (badge), tag count, is_published toggle column, published_at, created_at (toggleable hidden).

**Filters:** is_published TernaryFilter, category SelectFilter (distinct values), "Has SEO record" custom filter (whereHas seoMetadata).

**Actions:** view (opens /:slug in new tab), edit, delete (with confirmation), bulk delete.

---

## 5. PART C — API endpoints (with sample responses)

### `GET /api/v1/seo-pages/{slug}`

Sample (`/seo-pages/audi-service-delhi`):
```json
{
  "page": {
    "id": 1,
    "slug": "audi-service-delhi",
    "title": "Audi Service in Delhi — Authorized Multi-Brand Workshop",
    "excerpt": "Comprehensive Audi service in Delhi NCR...",
    "body": "<p>Looking for a trusted <strong>Audi service center...</strong>",
    "category": "Brand Service",
    "tags": ["audi", "delhi", "service"],
    "layout": "standard",
    "cta": {
      "title": "Book Your Audi Service Today",
      "button_text": "Book Now",
      "button_url": "/services"
    },
    "published_at": "2026-05-08T09:48:09+00:00"
  },
  "seo": {
    "meta_title": "Audi Service in Delhi | ACR",
    "meta_description": "Authorized Audi service in Delhi NCR...",
    "og_title": "Audi Service in Delhi",
    "og_image": "https://acr-mechanics.in/og-audi.jpg",
    "schema_jsonld": "{\"@context\":\"https://schema.org\",\"@type\":\"Article\",...}",
    "...": "..."
  },
  "related_pages": [
    { "slug": "bmw-service-cost-guide", "title": "BMW Service Cost Guide — Delhi NCR", "...": "..." }
  ],
  "redirect": null
}
```

When a `url_redirects` row matches `/{slug}`, the response is just:
```json
{ "redirect": { "to": "/audi-service-delhi", "status": 301 } }
```

### `GET /api/v1/explore?category=X&search=Y&page=N`

```json
{
  "data": [ { "id": 1, "slug": "...", "title": "...", "excerpt": "...", "category": "...", "tags": [...], "published_at": "..." } ],
  "meta": { "current_page": 1, "last_page": 1, "total": 4, "per_page": 20 }
}
```

### `GET /api/v1/explore/categories`

```json
{ "categories": ["Brand Service", "City Service", "Maintenance Tips"] }
```

### `GET /api/v1/sitemap.xml`

Returns Sitemap-protocol-0.9 XML covering: 5 static landing routes, every published SeoPage, every active ServiceCategory, every active Service. Each row honours `include_in_sitemap` from its SEO record.

`Cache::remember('sitemap_xml', 3600)`. Invalidated on SeoPage AND SeoMetadata save/delete events.

---

## 6. PART D — react-helmet-async integration

`npm install react-helmet-async` (5 packages added; `react-fast-compare`, `shallowequal`, etc. peer deps). No conflicts with existing `motion@11`, `@tanstack/react-query@5`, `react-router-dom@7`.

`main.tsx` wraps the existing `<QueryClientProvider>` in `<HelmetProvider>` (outermost — Helmet's context needs to live above any component that calls `<Helmet>`).

`src/components/SeoHead.tsx` is a flat-shape component that consumes the `seo` field from the new endpoint. It coexists with the legacy `src/lib/SeoHead.tsx` (Phase 1.6 — used by `/home`, `/services` SEO via the old SeoHelper) which has a nested SeoPayload shape.

---

## 7. PART E — SeoPageView with redirect handling

`src/pages/SeoPageView.tsx`:

1. **Reserved-slug guard** (`new Set(...)` lookup) returns `<NotFound />` immediately. No API round-trip for system paths.
2. **`useQuery`** with `enabled: !!slug && !isReserved`, `retry: false`, 5-min staleTime.
3. **`useEffect`** watches `query.data?.redirect` and calls `navigate(to, { replace: true })`.
4. **Loading** → `<PageSkeleton />` (lightweight pulse blocks).
5. **isError + ApiError.status === 404** → `<NotFound />` (preserves Phase 2.6a-fix invariant: unknown URLs land on NotFound at the same URL).
6. **isError other** → `<ApiErrorState>` with retry.
7. **Success** → `<SeoHead>` + `<PageBanner>` + body (sanitized HTML via `dangerouslySetInnerHTML`; safe because the backend strips on save) + CTA section + Related Articles grid.

Internal CTA URLs starting with `/` are intercepted with `e.preventDefault()` + `navigate()` so they stay in-SPA.

---

## 8. PART F — ExplorePage with filters

`src/pages/ExplorePage.tsx`:

- Two `useQuery`s: explore-categories (10-min staleTime, fills the dropdown) and explore (server-paginated, `keepPreviousData` so page-flips don't flash a skeleton).
- Filter state: `category`, `search`, `page`. Changing category or search resets `page = 1`.
- Per-card data-testid (`explore-card-{slug}`) + on the controls (`explore-category-filter`, `explore-search`, `explore-error`) so the new Playwright project can target precisely without CSS selector drift.
- Empty state via the Phase 4.2.5 `<EmptyState>`. Error state via `<ApiErrorState>` with retry.

---

## 9. PART G — Seeded SEO pages

`SeoPageSeeder` upserts 4 pages keyed by slug (idempotent):
- `audi-service-delhi` — Brand Service (tags: audi, delhi, service)
- `bmw-service-cost-guide` — Brand Service (tags: bmw, pricing, delhi)
- `monsoon-car-care-tips` — Maintenance Tips (tags: monsoon, maintenance, tyres)
- `best-car-ac-service-gurugram` — City Service (tags: gurugram, ac, service)

Each gets a SEO record with meta_title / meta_description / og_image / schema_type=Article + priority/changefreq tuned by content type. Live curl proves all 3 endpoints (`/seo-pages/{slug}`, `/explore`, `/sitemap.xml`) return non-empty payloads against the seed.

Wired into `DatabaseSeeder` so a fresh `php artisan db:seed` covers it.

---

## 10. PART H — Backend tests (verbatim)

```
   PASS  Tests\Feature\Api\V1\SeoPageEndpointTest
  ✓ it GET /api/v1/seo-pages/{slug} returns the page when published     0.46s
  ✓ it GET /api/v1/seo-pages/{slug} returns 404 when the page is unpublished  0.20s
  ✓ it GET /api/v1/seo-pages/{slug} returns redirect payload when url_redirect is active 0.18s
  ✓ it related_pages is ordered by category match then most recent      0.33s
  ✓ it GET /api/v1/explore filters by category                          0.20s
  ✓ it GET /api/v1/explore search filters by title                      0.20s
  ✓ it GET /api/v1/explore/categories returns distinct categories       0.21s

   PASS  Tests\Feature\Api\V1\SitemapTest
  ✓ it GET /api/v1/sitemap.xml returns well-formed XML                  0.57s
  ✓ it Sitemap respects include_in_sitemap=false on a SeoPage           0.12s
  ✓ it Sitemap is cached and invalidated on SeoPage save                0.11s

   PASS  Tests\Feature\Seo\SeoPageResourceTest
  ✓ it admin can access SeoPageResource list page                       0.40s
  ✓ it non-admin user is forbidden from SeoPageResource                 0.13s
  ✓ it SeoPage::reservedSlugs() includes critical system paths          0.07s
  ✓ it SeoPage sanitizeHtml strips disallowed tags but keeps whitelist  0.07s
  ✓ it SeoPage saving event auto-strips script tags from body           0.10s
  ✓ it SeoPage auto-stamps published_at on first publish                0.09s
```

16 new backend tests + 75 prior = **91 total backend Pest, 423 assertions, 44.71s.**

---

## 11. PART I — Frontend tests (verbatim)

```
[seo] tests/e2e/explore.spec.ts
  ✓ /explore renders seeded SeoPage cards                              13.3s
  ✓ /explore filter dropdown narrows results to a category              3.2s
  ✓ /explore search filters results by query string                     3.7s
  ✓ Clicking an explore card navigates to /:slug                        3.7s

[seo] tests/e2e/seo-pages.spec.ts
  ✓ /:slug renders the seeded SEO page with title and body              2.7s
  ✓ Helmet injects meta_title as document.title                         2.6s
  ✓ Helmet injects og:title and og:description meta tags                2.6s
  ✓ Reserved slug /cart does NOT render the SEO page view               1.8s
  ✓ Unknown single-segment slug renders the NotFound page               3.6s
  ✓ Related Articles section renders when a sibling exists              2.5s

10 passed (42.8s)
```

10 new frontend tests + 22 prior dev-server tests = **32 total frontend Playwright dev-suite, 1.8 min.**

---

## 12. PART J — Full test suite output

### Backend Pest
```
Tests:    91 passed (423 assertions)
Duration: 44.71s
```

### Frontend Playwright (smoke + admin + api-integration + edges + seo)
```
32 passed (1.8m)
```

Broken-out:
- smoke: 3/3
- admin: 2/2
- api-integration: 4/4
- edges: 13/13
- **seo (NEW): 10/10**

`production` project requires `vite preview` on :4173 (not part of this commit's environment); unchanged.

### Combined
**91 backend + 32 frontend = 123 tests passing.**
(Phase 4.5a baseline was 75 + 22 = 97. Phase 4.5b adds 16 backend + 10 frontend = 26 new tests, exceeding the ~20 target.)

---

## 13. PART K — Manual checklist

`PHASE4_5B_MANUAL_CHECKLIST.md` covers ~25 items across:
- Filament admin SEO page CRUD (slug validation, RichEditor toolbar, reserved slug rejection, save/reload persistence)
- SeoFieldGroup 5-tab UX (collapsed by default, schema_data appears for templated types)
- Frontend /:slug rendering (page banner, body HTML, category badge, CTA, related articles)
- View source verification (title, meta description, og:*, twitter:*, JSON-LD)
- /explore hub (cards, filter, search, navigation)
- Reserved-slug routing (cart, admin, payment, explore)
- URL redirect (tinker insert + browser address-bar update)
- sitemap.xml (valid XML, expected entries, include_in_sitemap honoured, cache header)

---

## 14. Deviations

1. **Reserved-slug list expanded.** The locked decision listed 26 slugs; the actual list ships with 33 to cover all current frontend routes (`testimonials`, `gallery`, `insurance`, `corporate`, `cms-preview`, `booking-history`, `my-bookings`, `order`, `booking-confirmation`, `not-found`, `payment`). The smoke test "/payment routes to NotFound" depends on `payment` being reserved.

2. **`src/lib/SeoHead.tsx` left untouched.** The project already had a dependency-free `SeoHead` at `src/lib/SeoHead.tsx` (Phase 1.6) consuming a nested `SeoPayload`. The new helmet-backed component lives at `src/components/SeoHead.tsx` consuming the FLAT `SeoFlatData` shape. Both coexist; new /:slug routes use the helmet one, legacy /home + /services keep the in-place mutator until Phase 4.5c migrates them.

3. **`/explore/categories` route is declared BEFORE `/seo-pages/{slug}`.** Order matters — `categories` is otherwise swallowed as a slug param.

4. **Sitemap test asserts via path suffix, not full URL.** Test env's `APP_URL` is `http://localhost:8000` (with port); production is `http://localhost`. Test matches `/<path></loc>` to stay env-agnostic.

5. **`keepPreviousData` on /explore.** Cleaner page-flip than a fresh skeleton flash. Imported from `@tanstack/react-query` v5 (already installed).

6. **CTA internal URL handling.** URLs starting with `/` are intercepted in `onClick` and routed via `navigate()` to keep navigation in-SPA. External URLs (`https://wa.me/...`) follow the default anchor behaviour.

7. **Explore filter resets pagination.** Changing `category` or `search` snaps `page` back to 1 to avoid showing "Page 4 of 1" empty states.

---

## 15. Performance baseline (sitemap caching)

Live curl measurements (warm Laravel cache, dev DB with 4
seeded SEO pages + 12 service categories + 40 services):

| Endpoint | Cold | Warm (cached) |
|---|---|---|
| `GET /api/v1/sitemap.xml` | ~80 ms | ~5 ms |
| `GET /api/v1/seo-pages/{slug}` | ~12 ms | ~12 ms (no caching layer; one query w/ eager-load) |
| `GET /api/v1/explore` (paginated) | ~14 ms | ~14 ms |

Sitemap cache hit-rate-by-design: 1 visit per hour from Googlebot, 0 from operators (Filament regenerates as a side effect of admin saves via the model events). Expected HTTP cache hit-rate from CDN is much higher because of the `Cache-Control: public, max-age=3600` header on the sitemap response.

The `chunk(100)` iteration on each SEO-aware model means a 10k-page seed regenerates the cache in roughly 10 chunks × ~30 ms = 300 ms. Acceptable for an hourly worst-case regen. If a partner deploys 100k+ SEO pages on this stack, switch to a paginated sitemap_index.xml.

---

## 16. Phase 4.5c preview

**Theme:** retrofit existing resources with `HasSeoMetadata` and surface `SeoFieldGroup` in their Filament forms.

Likely scope:
- Apply `HasSeoMetadata` trait to remaining models: `Coupon`, `Order` (admin-only), `Page` (CMS).
- Add `...SeoFieldGroup::make()` to `ServiceResource`, `ServiceCategoryResource`, `ServiceCenterResource`, `CouponResource`. Each gets the same Create/Edit page hooks pattern from this commit.
- Migrate static `LOCATIONS` / `TESTIMONIALS` arrays in the React frontend to API-backed equivalents (the foundation table for testimonials lands here too).
- 4.5d preview: live Google search-result preview, FAQPage data source wiring, JSON-LD validator endpoint.

Estimated effort: **~2 days** (the heavy lifting was already in 4.5a/4.5b; 4.5c is mostly hook plumbing). New tests: ~10 backend (each retrofit gets 2-3 contract tests).
