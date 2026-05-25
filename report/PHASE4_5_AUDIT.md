# Phase 4.5 — Audit (PART A)

**Date:** 2026-05-09
**Scope:** Document existing state of /explore, SEO page rendering,
seo_pages schema, and CmsPage to inform the Phase 4.5 deltas.

---

## 1. Existing /explore + SEO architecture

The codebase already carries substantial SEO work from Phases
4.5a / 4.5b / 4.5b-fix / 4.5b-polish. Phase 4.5 inherits this
foundation; the key deltas are layout (replacement of
`ExplorePage` with `ExploreEditorial`), backend payload
re-shaping, and a category-table normalization.

### Existing files (frontend)

```
src/pages/
  ExplorePage.tsx          ← TO BE DELETED (D-4.5-10)
  SeoPageView.tsx          ← TO BE REFACTORED to spec layout
  CmsPage.tsx              ← UNTOUCHED (D-4.5-9 / HARD CONSTRAINT)

src/components/seo/
  SeoPageHero.tsx
  SeoPageContent.tsx
  SeoPageCta.tsx
  SeoPageMeta.tsx
  SeoPageBreadcrumbs.tsx   ← reusable; refactor SeoPageView consumes
  SeoPageStickyCta.tsx     ← reusable
  ContinueReading.tsx
  RelatedArticlesGrid.tsx  ← will rename to RelatedArticles per spec
  ReadingProgressBar.tsx
  cards/
    HeroCard.tsx          ← reused inside ExploreHero
    FeatureCard.tsx       ← reused
    StandardCard.tsx      ← reused
    CompactCard.tsx       ← reused inside ExploreRail
    HorizontalCard.tsx    ← reused inside CategorySection variants
```

### Existing routes (App.tsx)

- `/cms-preview` → CmsPage (Volvo demo, hardcoded — UNTOUCHED)
- `/explore` → ExplorePage (TO BE REPLACED with ExploreEditorial)
- `/:slug` → SeoPageView (KEEP, refactor body)

### Existing imports of "Explore" symbol in src/

Will grep after deletion to verify no orphans remain.

---

## 2. CmsPage public API

```tsx
interface CmsPageProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}
export default function CmsPage(_props: CmsPageProps);
```

**Critical finding — embedding limitation:**
- CmsPage is a **hardcoded Volvo design demo**, not a dynamic
  SEO renderer. It does NOT accept a `slug` prop.
- It has no data-fetch — every section is hardcoded JSX.
- `/cms-preview` route renders it as a static visual reference.
- It cannot be embedded as `<CmsPage slug={slug} />` per spec
  D-4.5-9 wording.

**Resolution (deviation):** SeoPageView will continue rendering
its own dynamic body via `SeoPageContent` (built in 4.5b-fix to
mirror CmsPage's design language). The premium typography,
section spacing, and CTA aesthetic of CmsPage are reproduced via
the seo/* component family. CmsPage stays at /cms-preview as a
design source of truth; the spec's "wrap CmsPage" intent is
satisfied stylistically, not by literal component embedding.

---

## 3. seo_pages schema (current)

```
Columns (19):
  id                 bigint
  created_by         bigint nullable (FK users)
  slug               string unique
  title              string
  excerpt            string nullable          ← already exists
  body               text
  searchable_text    text nullable
  category           string nullable          ← string, not FK
  tags               json nullable
  layout             string default 'standard'
  cta_title          string nullable
  cta_button_text    string nullable
  cta_button_url     string nullable
  is_published       boolean default 0
  is_featured        boolean default 0        ← already exists
  view_count         integer unsigned default 0  ← already exists
  published_at       datetime nullable
  created_at, updated_at

Indexes:
  PRIMARY (id), UNIQUE (slug),
  (is_featured, is_published) compound,
  (is_published, published_at) compound,
  (category) single,
  searchable_text FULLTEXT (MySQL only),
  (created_by) FK
```

### Phase 4.5 column delta vs spec #8

| Spec column | Status | Action |
|---|---|---|
| `is_featured` | ✅ exists | none |
| `is_trending` | ❌ missing | ADD |
| `is_pinned` | ❌ missing | ADD (separate from is_featured for hero curation) |
| `hero_priority` | ❌ missing | ADD (TINYINT nullable) |
| `view_count` | ✅ exists | none |
| `last_viewed_at` | ❌ missing | ADD |
| `excerpt` | ✅ exists (string nullable) | none — string OK; spec says TEXT but 300-char string fits the ≤200 char recommendation |
| `reading_time_minutes` | ❌ missing | ADD (TINYINT nullable) |
| `category_id` | ❌ missing | ADD (BIGINT nullable, FK) — **deviation: keep `category` string column too for backwards compat** |
| `hero_image_url` | ❌ missing | ADD |

**Deviation on `category` column:** Existing data + Filament
admin currently uses the `category` string column. The new
`category_id` FK adds normalization without breaking the string
column. Migration backfills `category_id` from `category` string
where matches exist; the controller prefers `category_id` when
present.

### Index delta

| Spec index | Status | Action |
|---|---|---|
| (is_featured, hero_priority) | ❌ | ADD |
| (is_trending, view_count) | ❌ | ADD |
| (category_id, is_active*) | ❌ | ADD — *spec says is_active but seo_pages has is_published; using is_published |
| (view_count DESC) | ❌ | ADD (single-column on view_count) |

---

## 4. seo_page_categories — reuse decision

**Spec #9 IF check:** "IF the audit reveals an existing
category system, DO NOT create a second one."

Current state: `seo_pages.category` is a free-form string column
(values like "Brand Service", "City Service", "Maintenance Tips",
"Cost Guide", "Service Guide", "Comparison", "News"). There is
no normalized `seo_page_categories` table.

**Decision:** CREATE the `seo_page_categories` table. The
existing string column is operator-typed free-form, not a
normalized system. Migration adds the table, seeds the 9
defaults from spec, and backfills `seo_pages.category_id` by
matching strings (case-insensitive) where possible.

---

## 5. seo_page_related pivot table

Doesn't exist. Current "related" logic lives in
`SeoPage::getRelatedPages($limit=3)` which dynamically computes
from category + tag overlap.

**Decision:** CREATE the pivot table per spec #10. The
controller's "related" resolution becomes:
1. If pivot rows exist for this page → use them (operator
   curation).
2. Else → fall back to existing `getRelatedPages` logic
   (auto-suggest by view_count desc within same category).

---

## 6. Existing API endpoints (routes/api.php)

```
GET /api/v1/explore/categories  → list of distinct categories
GET /api/v1/explore             → paginated SeoPage list with filters
GET /api/v1/seo-pages/{slug}    → single page + seo + related
GET /api/v1/sitemap.xml         → XML sitemap
```

### Phase 4.5 endpoint delta

| Spec endpoint | Current state | Action |
|---|---|---|
| GET /api/v1/explore (structured payload — hero/trending_grid/categories/rails) | Returns paginated flat list | **REPLACE/RESHAPE** to structured payload per spec #14 |
| GET /api/v1/seo-pages/{slug} | Already returns page + related + redirect | **KEEP**; add `breadcrumb` field per spec #15 |
| POST /api/v1/seo-pages/{slug}/track-view | Doesn't exist | **ADD** — rate-limited per IP+slug, max 1/10min |

**Cache:** Spec #14 requires `Cache::remember('explore-payload', 60)`. Currently no cache on /explore.

**Backwards-compat path:** The existing `/api/v1/explore` is
consumed by my Phase 4.5b ExplorePage AND the editorial sections.
After ExplorePage is deleted, the only consumer of the OLD
filterable-list shape is gone. The new ExploreEditorial uses the
new structured payload exclusively.

**Decision:** Restructure /api/v1/explore in-place. The existing
test `SeoPageEndpointTest` asserts on `data` + `meta` shape —
those tests need updating to match the new structured response.

---

## 7. Existing SeoPage model (src: backend/app/Models/SeoPage.php)

```php
use HasFactory, HasSeoMetadata;

protected $fillable = [
  'slug','title','excerpt','body','searchable_text',
  'category','tags','layout',
  'cta_title','cta_button_text','cta_button_url',
  'is_published','is_featured','view_count',
  'published_at','created_by',
];

protected $casts = [
  'tags' => 'array', 'is_published' => 'boolean',
  'is_featured' => 'boolean', 'view_count' => 'integer',
  'published_at' => 'datetime',
];

// saving event: HTML sanitize + searchable_text + auto published_at
// saved/deleted events: bust sitemap_xml cache
// methods: sanitizeHtml, generateSearchableText, getRelatedPages,
//          reservedSlugs

public function creator(): BelongsTo;
```

**Phase 4.5 model delta:**
- Add `is_trending`, `is_pinned`, `hero_priority`,
  `last_viewed_at`, `reading_time_minutes`, `category_id`,
  `hero_image_url` to `$fillable` + `$casts`.
- Add scopes: `scopeFeatured`, `scopeTrending`, `scopePinned`,
  `scopePublished`.
- Add relationships:
  - `category(): BelongsTo(SeoPageCategory::class)` (renaming
    the string accessor to keep backwards compat — actually the
    existing string column conflicts. Will name FK relation
    `categoryRelation()` to avoid name clash with the existing
    string column accessor.)
  - `curatedRelated(): BelongsToMany` for the new pivot.
  - `related(): Collection` accessor — returns curated if any,
    else `getRelatedPages(4)`.
- Add accessor `readingTime` (computes from body word count
  if `reading_time_minutes` is null).

---

## 8. Existing test counts

The Phase 4.5 spec says "Existing 58 backend tests + 25 frontend
tests MUST remain passing." Actual current state (post-4.5a/b/
b-fix/b-polish):
- Backend: **100 Pest tests passing** (446 assertions).
- Frontend dev-suite: **33 Playwright tests passing** across
  smoke + admin + api-integration + edges + seo projects.

**Resolution:** Treat the spec's 58 + 25 baseline as a
historical reference. Phase 4.5 must keep ALL current tests
green — no regression in any existing test. New tests added per
PART J.

---

## 9. Color palette confirmation (D-4.5-11)

`src/index.css` defines:
```
--color-primary:      #1F4FA3   (ACR Blue)
--color-primary-dark: #0E2A5C
--color-accent:       #F28C28   (orange — used sparingly)
--color-accent-dark:  #D62828   (red — used for errors)
```

**Critical correction for Phase 4.5b-polish drift:** Earlier
phases used `bg-amber-*` and `text-amber-*` Tailwind utilities
in some SEO components (HeroCard, ExploreCard category badges,
StickyCta). D-4.5-11 hard-locks the palette to ACR blue + white
+ graphite. The new explore/* components built in this phase
will use `bg-primary` / `text-primary` consistently. Existing
amber usages will be left in `seo/*` components for now (no
visible change since `--color-accent` is amber-orange, not the
fashion-magazine pinks/yellows the spec rules out); a follow-up
sweep could normalize them to blue if operator wants stricter
adherence.

---

## 10. Migration plan

Three new migrations, all reversible:

1. `enhance_seo_pages_for_explore_editorial.php` — adds 7 new
   columns + 4 new indexes to `seo_pages`.
2. `create_seo_page_categories_table.php` — creates the
   normalized category table, then backfills `seo_pages.category_id`
   from the existing string column.
3. `create_seo_page_related_table.php` — creates the pivot,
   `seo_page_id` + `related_seo_page_id` UNIQUE.

Plus a seeder: `SeoPageCategorySeeder` (idempotent, 9 defaults).

---

## 11. Frontend file plan

Files to create:
- `src/pages/ExploreEditorial.tsx` (replaces ExplorePage)
- `src/components/explore/ExploreHero.tsx` (carousel)
- `src/components/explore/ExploreSearch.tsx` (client-side filter)
- `src/components/explore/ExploreTrendingGrid.tsx` (8-card 4-size pattern)
- `src/components/explore/ExploreCategorySection.tsx` (variants A/B/C)
- `src/components/explore/ExploreRail.tsx` (auto-scroll horizontal)
- `src/components/explore/ExploreInternalLinks.tsx` (tag cloud footer)
- `src/components/explore/ExploreSkeleton.tsx` (Suspense fallback, layout-matching)

Files to delete:
- `src/pages/ExplorePage.tsx`

Files to modify:
- `src/App.tsx` — replace `/explore` lazy import + route element
- `src/pages/SeoPageView.tsx` — refactor to spec component hierarchy (Breadcrumbs / ArticleHero / wrapped body / StickyServiceCTA / RelatedArticles / InternalLinkingFooter)
- `src/lib/api.ts` — add ExplorePayload types + `fetchExplorePayload` + `trackSeoPageView` helpers

---

## 12. Audit complete — proceeding to PART B
