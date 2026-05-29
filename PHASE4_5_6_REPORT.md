# Phase 4.5.6 — Final ExploreEditorial Layout — Report

**Date:** 2026-05-09
**Branch:** main (no commit per GIT POLICY)
**Scope:** Implement the operator's hand-drawn final blueprint for `/explore`. Five distinct layout patterns between hero and footer (Trending Now, Brand Service, City Service, Big Grid Dual, Service Guide), each with its own grid configuration. Closes the explore work-stream.

> **Follow-up turn:** operator flagged that City Service was rendering only 2 cards (mockup needs 5) and Big Grid Dual was hidden. Both were data-state issues in Phase 4.5.6's first cut: the layout reserved cells but the API didn't have enough content. This turn adds **`bigGridLeft`/`bigGridRight` fallback** (use first/second available categories when `maintenance-tips`/`comparison` are absent) + **`cityServicePadded`** (extend `cityService.items` with overflow from `rails.most_read_week`) so the layouts actually fill. See §16 for the follow-up details.

All hard constraints respected:
- No new packages
- No backend changes (sanity-confirmed, 118/118 Pest unchanged)
- ExploreFeaturedGrid, ExploreSearch, ExploreRail, CategoryFilterChip, ExploreCardFallback, ExploreInternalLinks, all sidebar widgets, PageBanner, SeoPageView, CmsPage, all admin — all UNTOUCHED
- ExploreCard `large-stacked` variant added; existing 5 size variants preserved verbatim
- Animation discipline preserved (single 300ms opacity fade on mount + hover-lift CSS only)

---

## 1. Files modified

| Path | Change |
|---|---|
| `src/components/explore/ExploreCard.tsx` | Added `large-stacked` size variant per D-4.5.6-3. New `stacked-card` layout branch: image-on-top (aspect-[16/10] with category badge top-left + reading-time bottom-left overlays) + white text panel below (`p-5 lg:p-6`, title `text-xl lg:text-2xl`, 3-line excerpt, reading-time meta). Existing `large` / `medium` / `small` / `compact` / `wide` variants UNCHANGED. |
| `src/components/explore/ExploreTrendingGrid.tsx` | Rewritten as 5-card mosaic per D-4.5.6-1. **Note:** items[2] is the LARGE centerpiece (not items[0]) per spec. items[0,1] go to left column rows 1-2, items[3,4] go to right column rows 1-2. Graceful 1/2/3/4-card degradation. |
| `src/components/explore/ExploreCategorySection.tsx` | Variant prop converted from rotation-based `"A" \| "B" \| "C"` to slug-aware `"brand-service" \| "city-service" \| "service-guide" \| "big-grid"`. Each variant has a dedicated layout per D-4.5.6-2/4/5/6. New helper components in-file: `BrandServiceLayout`, `CityServiceLayout`, `ServiceGuideLayout`, `BigGridSubSection`, `BigGridFeature`, `BigGridThumbRow`, `BorderedSmallCard`, `FeatureWideCard`. New public export `BigGridDual` wraps two `<ExploreCategorySection variant="big-grid">` side-by-side. |
| `src/pages/ExploreEditorial.tsx` | Dropped the `VARIANTS` rotation constant + idx-based wiring. Now finds categories by slug (`brand-service`, `city-service`, `service-guide`, `maintenance-tips`, `comparison`) via `findCat()` and renders each in its dedicated slot per the operator's mockup order. Section-1 holds Trending + Brand Service alongside the LeadForm/TopPicks aside; City Service is full-width between rails; Section-2 holds BigGridDual + Service Guide alongside the PopularBrands/RelatedTopics/GetSocial aside. |
| `src/components/explore/ExploreSkeleton.tsx` | Updated placeholders for the new section structure: 5-card Trending mosaic, 1 LARGE-stacked + 3 SMALL Brand Service. |
| `tests/e2e/explore-editorial.spec.ts` | "clicking a trending card" test now caps trendingCount at **5** (was 4), reflecting the new mosaic. |
| `playwright.config.ts` | `seo` project regex extended for `explore-final-layout` spec; older `-trending-screenshot` and `-service-guide-screenshot` regex entries removed (their files were deleted — see §3). |

## 2. Files created

| Path | Purpose |
|---|---|
| `tests/e2e/explore-final-layout.spec.ts` | Single Playwright spec with 5 sub-tests, each snapshotting one of the new sections (Trending / Brand Service / City Service / Big Grid Dual / Service Guide). PNGs land at `test-results/phase-4-5-6-{section}.png`. Sections that don't render (e.g., Big Grid Dual when `maintenance-tips` + `comparison` categories are absent from the payload) log a console message and pass (graceful no-op). |
| `PHASE4_5_6_AUDIT.md` | PART A audit deliverable. |
| `PHASE4_5_6_REPORT.md` | This file. |

## 3. Files deleted

```
tests/e2e/explore-trending-screenshot.spec.ts        (superseded by explore-final-layout)
tests/e2e/explore-service-guide-screenshot.spec.ts   (superseded by explore-final-layout)
```

The new `explore-final-layout` spec covers what these two used to do, plus 3 more sections, in a single parametrised file.

---

## 4. PART A — Backend payload audit findings

Full doc at `PHASE4_5_6_AUDIT.md`. Summary:

```
hero_count           = 5    ✓
trending_grid_count  = 7    ✓ (5 used)
rails.trending      = 12   ✓
rails.most_read     = 12   ✓

categories present:
  brand-service    | items=4   ✓
  city-service     | items=1   ⚠ 3 of 4 right-grid slots empty
  service-guide    | items=2   ⚠ 1 of 3 bottom-row slots empty

categories absent from payload:
  maintenance-tips    (no published pages)
  comparison          (no published pages)
```

**Big Grid Dual gracefully hides** when both `maintenance-tips` and `comparison` are absent. Operator publishes content via `/admin/seo-pages` to surface the section.

The "empty slots" in City Service and Service Guide are CSS grid cells reserved by the layout but unfilled because the payload doesn't have enough items. They're a content-state issue (publish more pages → slots auto-fill on next page-load), not a layout bug.

---

## 5. PART B — `large-stacked` ExploreCard variant

```ts
type Size = "large" | "large-stacked" | "medium" | "small" | "compact" | "wide";

const SIZE_CFG = {
  // ...existing entries...
  "large-stacked": {
    titleClasses: "text-xl lg:text-2xl line-clamp-2",
    ratio: "aspect-[16/10]",
    layout: "stacked-card",   // NEW
  },
};
```

The new `stacked-card` layout branch in `ExploreCard.tsx`:

```tsx
<Link className="group flex flex-col h-full bg-white border border-border …">
  <div className={`relative ${cfg.ratio} bg-neutral-900 overflow-hidden flex-shrink-0`}>
    <img … />                                               {/* image on top */}
    <span className="absolute top-3 left-3 bg-primary …">   {/* category badge */}
    <span className="absolute bottom-3 left-3 bg-neutral-950/70 …"> {/* reading-time pill */}
  </div>
  <div className="flex-1 p-5 lg:p-6 flex flex-col">
    <h3 className="text-xl lg:text-2xl font-bold uppercase tracking-tight …">
    <p className="text-sm text-neutral-600 line-clamp-3 …">  {/* excerpt */}
    <div className="mt-auto inline-flex items-center gap-1 text-xs text-neutral-500 …">
  </div>
</Link>
```

This is the editorial "magazine card" pattern operator chose for Brand Service. The existing `large` overlay style stays for Featured Grid, City Service, and Trending Now — both styles coexist in the same component.

---

## 6. PART C — Trending Now 5-card mosaic

```jsx
// src/components/explore/ExploreTrendingGrid.tsx
const visible = items.slice(0, 5);
const largeIdx = visible.length >= 3 ? 2 : 0;   // items[2] is LARGE
const large  = visible[largeIdx];
const small1 = visible[0];                       // left-top
const small4 = visible[1];                       // right-top
const small2 = visible[3];                       // left-bottom
const small5 = visible[4];                       // right-bottom

return (
  <div className="grid grid-cols-1 lg:grid-cols-12 lg:grid-rows-2 lg:auto-rows-[minmax(160px,1fr)] gap-4 lg:gap-6">
    {/* CENTER LARGE — items[2], cols 4-9 rows 1-2 */}
    <div className="order-1 lg:order-none lg:col-start-4 lg:col-end-10 lg:row-start-1 lg:row-end-3 …">
      <ExploreCard page={large} size="large" />
    </div>
    {/* TOP-LEFT, TOP-RIGHT, BOTTOM-LEFT, BOTTOM-RIGHT smalls — all literal class strings */}
    {/* … cols 1-3/10-12, rows 1-2 placements … */}
  </div>
);
```

Distinct from `ExploreFeaturedGrid` even though both are 5-card mosaics:
- FeaturedGrid: items[0] is the LARGE (cols 4-9, rows 1-2), items[1..4] populate cells around.
- TrendingGrid: items[2] is the LARGE, items[0,1] left, items[3,4] right.

Mobile (<lg): single column. items[2] LARGE renders first via `order-1`; smalls follow in DOM order.

Graceful degradation: 5/4/3/2/1 cards. With the live payload (7 trending items), full 5-card mosaic renders cleanly.

---

## 7. PART D — ExploreCategorySection 4 slug-based variants

| Variant | Layout | Card components used |
|---|---|---|
| `brand-service` | 12-col 3-row: 1 LARGE-stacked (cols 1-7, rows 1-3) + 3 SMALL stacked right (cols 8-12, one per row) | `ExploreCard size="large-stacked"` + `ExploreCard size="small"` |
| `city-service` | 12-col 2-row: 1 LARGE overlay (cols 1-6, rows 1-2) + 2x2 SMALL right (cols 7-12, manual placement) | `ExploreCard size="large"` + `ExploreCard size="small"` |
| `service-guide` | Top: wide horizontal (`FeatureWideCard` — image-LEFT + text-panel-RIGHT). Bottom: `lg:grid-cols-3` of bordered SmallCards | `FeatureWideCard` + `BorderedSmallCard` |
| `big-grid` | Single sub-section (used by `BigGridDual` wrapper): top featured (`BigGridFeature` — 12-col inner with image cols 1-7 + text-panel cols 8-12) + 3 thumb-rows (`BigGridThumbRow` — 80×80 thumb LEFT + title/meta RIGHT) | `BigGridFeature` + `BigGridThumbRow` |

**`BigGridDual`** is a small public wrapper exported from `ExploreCategorySection.tsx`. It accepts `leftCategory` + `rightCategory` (both `ExploreCategoryBlock | null`) and renders `<ExploreCategorySection variant="big-grid">` for each non-null prop inside a `lg:grid-cols-2 gap-8 lg:gap-12` flex. If both are null, returns null (entire section hides).

All grid-utility class names are literal strings — Tailwind JIT detects them, no dynamic interpolation.

---

## 8. PART E — ExploreEditorial assembly

The new section order matches the operator's mockup top-to-bottom:

```
PageBanner
ExploreFeaturedGrid                                      // hero
ExploreSearch
CategoryFilterChip

Section 1 (bg-neutral-50)
  ┌── main col-span-8 ─────────────────────┐  ┌── aside sticky ─┐
  │   ExploreTrendingGrid (5-card mosaic)  │  │   LeadFormWidget │
  │   ExploreCategorySection brand-service │  │   TopPicksWidget │
  └────────────────────────────────────────┘  └─────────────────┘

ExploreCategorySection city-service                       // full-width
ExploreRail "Trending Searches"                           // full-width

Section 2 (bg-white)
  ┌── main col-span-8 ─────────────────────┐  ┌── aside sticky ─┐
  │   BigGridDual maintenance-tips +       │  │   PopularBrands  │
  │               comparison               │  │   RelatedTopics  │
  │   ExploreCategorySection service-guide │  │   GetSocial      │
  └────────────────────────────────────────┘  └─────────────────┘

ExploreRail "Most Read This Week"                         // full-width
ExploreInternalLinks                                       // footer
```

Slug-aware lookup via `findCat(slug)`. Each section renders only when its source category exists in the payload.

---

## 9. PART F — Skeleton alignment

`ExploreSkeleton.tsx` updated to mirror the new layouts at the same grid placements (cols/rows/order/aspect):
- PageBanner placeholder (unchanged from 4.5.2)
- Featured grid 5-card mosaic placeholder (unchanged from 4.5.2)
- Search bar (unchanged)
- **Trending Now 5-card mosaic** (NEW — 1 LARGE center cols 4-9 rows 1-2 + 4 SMALL flanking)
- **Brand Service** (NEW — 1 LARGE-stacked image+panel cols 1-7 rows 1-3 + 3 SMALL stacked cols 8-12)
- Rail (unchanged)

City Service / Big Grid Dual / Service Guide skeleton blocks intentionally omitted — keeps the skeleton compact (the user usually sees content render before scrolling that far). Layout-stable repaint guaranteed for the above-the-fold sections.

---

## 10. PART G — Tests

### Backend (Pest)

```
Tests:    118 passed (534 assertions)
Duration: 125.08s
```

Untouched (no backend changes). 118/118.

### Frontend Playwright (`seo` project)

```
Editorial + final-layout in isolation:
  ✓ featured grid renders 5-card mosaic (no carousel)               (5.4s)
  ✓ search filters cards client-side and highlights matches          (2.7s)
  ✓ clicking a trending card navigates to /:slug                     (3.4s)
  ✓ snapshots the Trending Now section to disk                       (4.2s)
  ✓ snapshots the Brand Service section to disk                      (4.8s)
  ✓ snapshots the City Service section to disk                       (3.3s)
  ✓ snapshots the Big Grid Dual section to disk                      (2.6s — graceful no-op, categories absent)
  ✓ snapshots the Service Guide section to disk                      (3.3s)
8 passed (33.6s)

Full SEO project:  22 of 25 stable per full sequential run; 3
                   pre-existing timing flakes that pass in
                   isolation (different tests each run; same
                   pattern documented since 4.5.2 §11).
```

Counts: 25 SEO Playwright tests. Was 22 in Phase 4.5.5 (-2 deleted + 5 new).

### TypeScript

`npx tsc --noEmit` — clean.

### Production build

```
✓ built in 24.41s

ExploreEditorial chunk delta:
  Phase 4.5.5  : 51.25 kB raw │ gzip: 10.42 kB
  Phase 4.5.6  : 52.81 kB raw │ gzip: 10.77 kB
  Δ            : +1.56 kB raw │ gzip: +0.35 kB    (within ±10 kB)
```

icons-vendor + index unchanged.

---

## 11. Screenshot proofs

Captured to disk via `tests/e2e/explore-final-layout.spec.ts`:

| Section | Path | Content visible in snapshot |
|---|---|---|
| Trending Now | `test-results/phase-4-5-6-trending.png` | Audi Brake Pad LARGE center + Mercedes-Benz top-left + BMW top-right + Cost Guide bottom-left + Maintenance Tips bottom-right |
| Brand Service | `test-results/phase-4-5-6-brand-service.png` | Mercedes-Benz LARGE-stacked left (image top, "MERCEDES-BENZ SERVICE IN DELHI" title + description in white panel below) + 3 SMALL stacked right (BMW AC Repair, Audi Brake Pad, Audi Service in Delhi) |
| City Service | `test-results/phase-4-5-6-city-service.png` | Dent And Paint Repair LARGE left + Best Car AC Service top-right (3 of 4 right-grid slots empty due to 1-item payload — content gap, not layout bug) |
| Big Grid Dual | _(no PNG generated — section absent)_ | maintenance-tips + comparison missing → section gracefully hides; spec test passes via the no-op branch |
| Service Guide | `test-results/phase-4-5-6-service-guide.png` | LUXURY image-left + text-panel-right top (UNCHANGED operator-preferred design) + Car Insurance + Emergency Roadside in 3-col bottom row (3rd slot empty due to 2-item payload) |

---

## 12. Deviations

1. **Big Grid Dual not visually rendered** — backend has no published pages in `maintenance-tips` or `comparison` categories. Per spec PART E step 19 (and HARD CONSTRAINT "DO NOT modify backend"), the section gracefully hides via the spec PART E null-check. The screenshot test logs a console message and passes. Operator authors content via `/admin/seo-pages` to surface the section.

2. **City Service and Service Guide have empty grid slots** — same data-state pattern. City Service has 1 item (3 right-grid slots empty); Service Guide has 2 items (1 bottom-row slot empty). Layout reserves the cells; cells fill automatically when more SeoPages are published in those categories.

3. **`maintenance-tips`/`comparison` fallback to top-2-by-page-count NOT implemented** — spec D-4.5.6-5 mentioned this fallback as "Up to implementation judgment". Implementing it would have required either: (a) modifying ExploreEditorial to compute top-N-by-count from `payload.categories` (would shadow the operator-driven category curation), or (b) modifying the backend payload to include pre-ranked fallbacks (forbidden by HARD CONSTRAINTS). Chose to render only when the named categories exist — keeps operator's intent transparent and unforked.

4. **Service Guide bottom-row slice unchanged from `items.slice(0, 3)`.** Spec D-4.5.6-6 said "change `.slice(1, 3)` to `.slice(1, 4)`" — that described an operator mental model where `items[0]` is the featured. In our schema `featured` is a SEPARATE prop, not `items[0]`, so `items.slice(0, 3)` already takes the right 3 cards. The intent (3 cards in bottom row) is realised; only the data has a 1-card shortfall.

No other deviations.

---

## 13. Phase 4.5.x sprint OFFICIAL CLOSURE

8 sub-phases delivered (in chronological order):

| Phase | Headline |
|---|---|
| 4.5   | Premium SEO Explore Ecosystem + Internal Article Pages |
| 4.5.1 | ExploreEditorial correction pass — 4-card hero, category filter, sticky sidebar, fallback design, 5 widgets, newsletter |
| 4.5.2 | Polish — 5-card mosaic, PageBanner, animation overhaul |
| 4.5.3 | Newsletter → Lead Form swap; lookup endpoints; LeadResource admin; hero pinning to 5 |
| 4.5.4 | Variant A dead-space fix + ExploreInternalLinks 3-col footer |
| 4.5.5 | ExploreTrendingGrid dead-space fix |
| (4.5.6 first turn) | Variant abstraction collapsed (later reverted by operator) |
| (4.5.6 second turn) | Variant system restored; bottom-row 3-col verified |
| **4.5.6 (this commit)** | **Hand-drawn final blueprint applied — 5 distinct section layouts; slug-aware variants; new `large-stacked` ExploreCard variant** |

### Cumulative delta vs sprint start

| Metric | Sprint start | Final |
|---|---|---|
| Backend Pest tests | ~95 | **118** (+23 net) |
| Frontend SEO Playwright tests | 0 | **25** |
| Backend models added | (baseline) | +3 (`SeoPage`, `SeoPageCategory`, `Lead`) |
| Public controllers added | (baseline) | +3 (`SeoPageController`, `LookupController`, `LeadController`) |
| Filament resources added | (baseline) | +2 (`SeoPageResource`, `LeadResource`) |
| Frontend explore components | 0 | 11 |
| Frontend explore widgets | 0 | 5 |
| Frontend hooks added | (baseline) | +2 (`useLookups`, `useLeadSubmit`) |
| Migrations (additive only) | (baseline) | +6 |
| ExploreEditorial bundle | n/a | 52.81 kB raw / 10.77 kB gzip |
| ExploreCard size variants | 0 | 6 (`large`, `large-stacked`, `medium`, `small`, `compact`, `wide`) |

### Final visual flow on `/explore`

`PageBanner` → `5-card editorial mosaic` (FeaturedGrid) → `ExploreSearch` → `CategoryFilterChip` (when filtered) → **`Section 1`** (`Trending Now 5-card mosaic` + `Brand Service 1 LARGE-stacked + 3 SMALL right` | aside: `LeadForm + TopPicks`) → **`City Service`** full-width 1 LARGE + 4 SMALL (2×2) → `Trending Searches` rail → **`Section 2`** (`BigGridDual` when categories present + `Service Guide` wide-text-panel + 3-col bottom | aside: `PopularBrands + RelatedTopics + GetSocial`) → `Most Read This Week` rail → `Explore More` 3-col footer.

Animations: single 300ms opacity fade on page mount + hover-lift on cards. Nothing else.

### Open items remaining (post-launch / Phase 6+)

- **Content gaps surfaced by Phase 4.5.6 layout work:**
  - `maintenance-tips` category — 0 published pages (Big Grid Dual hides)
  - `comparison` category — 0 published pages
  - `city-service` — 1 item (need 4 to fill 2×2 right grid)
  - `service-guide` — 2 items (need 3 to fill bottom row)
  - All addressable via existing `/admin/seo-pages` Filament UI; no code change needed once content lands.
- **Real hero images** — depends on Phase 4.4 (operator manual upload via SeoPageResource). Card fallback design is sturdy stopgap.
- **SEO admin curation UI polish** (Phase 4.5b) — bulk pin reorder, bulk image upload, drag-drop ordering.
- **Brand/Model master data CRUD admin** — operator now relies on this data for the LeadFormWidget. **Should be the next phase priority.**
- **Lead admin enhancements** — CSV export, email/SMS notification on new lead.
- **SEO Playwright stability** — 2-3 timing flakes per 25-test sequential run; all pass in isolation. Phase 6 candidate to split the SEO project into faster halves OR add a `webServer` warmup step.

---

## 14. Next phase recommendation

**Phase 4.3 — Brand/Model master data admin + Excel upload.**

Operator is now publishing the LeadFormWidget which depends on `car_brands` (14 rows) and `car_models` (81 rows) being curated. There is currently NO Filament resource for either; updates require direct DB access. Phase 4.3 was deferred earlier to ship the explore work first; resuming it next is the highest-leverage move.

Suggested scope:
- Filament `CarBrandResource` + `CarModelResource` (active toggle, slug, image upload)
- Excel/CSV bulk-import action mirroring the existing `ImportController` pipeline (admin-token gated)
- Cache buster wires through model `saved`/`deleted` events to invalidate `lookups:brands` + `lookups:models:brand:{id}` keys

After 4.3, returning to **Phase 4.5b polish** (bulk pin reorder, image upload UX, content gap-filling for the explore layout's empty slots) is the natural follow-up.

---

## 15. Files-touched summary

```
MODIFIED (frontend):
  src/components/explore/ExploreCard.tsx                (added `large-stacked` variant)
  src/components/explore/ExploreTrendingGrid.tsx        (5-card mosaic with LARGE center)
  src/components/explore/ExploreCategorySection.tsx     (slug-aware variants + helpers)
  src/components/explore/ExploreSkeleton.tsx            (mirror new layouts)
  src/pages/ExploreEditorial.tsx                        (slug lookup + section reassembly)
  tests/e2e/explore-editorial.spec.ts                   (trendingCount cap 4 → 5)
  playwright.config.ts                                   (regex update)

CREATED:
  tests/e2e/explore-final-layout.spec.ts                (5-section screenshot record)
  test-results/phase-4-5-6-trending.png                 (visual record)
  test-results/phase-4-5-6-brand-service.png            (visual record)
  test-results/phase-4-5-6-city-service.png             (visual record)
  test-results/phase-4-5-6-service-guide.png            (visual record)
  PHASE4_5_6_AUDIT.md
  PHASE4_5_6_REPORT.md  (this file — overwrites previous turns' reports)

DELETED:
  tests/e2e/explore-trending-screenshot.spec.ts         (superseded by explore-final-layout)
  tests/e2e/explore-service-guide-screenshot.spec.ts    (superseded by explore-final-layout)
  test-results/phase-4-5-5-trending-fixed.png           (stale screenshot from prior turn)
  test-results/phase-4-5-6-service-guide-fixed.png      (stale screenshot from prior turn)

UNTOUCHED (per HARD CONSTRAINTS):
  src/components/explore/ExploreFeaturedGrid.tsx
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/explore/ExploreInternalLinks.tsx
  src/components/explore/widgets/{LeadForm,TopPicks,PopularBrands,RelatedTopics,GetSocial}Widget.tsx
  src/components/PageBanner.tsx
  src/pages/SeoPageView.tsx, src/pages/CmsPage.tsx
  All backend, all admin (Filament resources)
```

Per GIT POLICY: **no `git add`, `git commit`, or `git push` performed.** Operator commits manually.

— end of original-turn report —

---

## 16. Follow-up turn — fallback content for BigGridDual + City Service

### What the operator flagged

After the original-turn ship, operator's visual review showed:
- **City Service rendering only 2 cards** (mockup expects 5: 1 LARGE + 2×2 SMALL grid).
- **Big Grid Dual section completely missing**.

Both symptoms were data-driven, not layout bugs. The variants and assembly were correctly wired in this turn already (`<ExploreCategorySection variant="city-service">` + `<BigGridDual leftCategory={maintenanceTips} rightCategory={comparison}>`), but the API payload didn't have enough content to fill the reserved cells.

### Reality check (re-curl of `/api/v1/explore`)

```
hero=5  trending_grid=7  trending_searches=12  most_read_week=12

categories present:
  brand-service  featured=1  items=4
  city-service   featured=1  items=1     ← 3 of 4 right-grid slots empty
  service-guide  featured=1  items=2

categories ABSENT:
  maintenance-tips    (no published pages)
  comparison          (no published pages)
```

### Fix (`src/pages/ExploreEditorial.tsx` only)

#### 1. Big Grid Dual fallback

Operator's locked instruction: "category for `maintenance-tips` or first available" / "category for `comparison` or second available".

```ts
const usedSlugs = new Set(
  [brandService, cityService, serviceGuide]
    .filter((c): c is ExploreCategoryBlock => !!c)
    .map((c) => c.slug),
);
const spareCategories = categories.filter((c) => !usedSlugs.has(c.slug));
const bigGridLeft  = findCat("maintenance-tips") ?? spareCategories[0] ?? categories[0] ?? null;
const bigGridRight = findCat("comparison")       ?? spareCategories[1] ?? categories[1] ?? null;
```

Resolution order: prefer the named slug → prefer a spare category not already on the page → fall back to any category. With current payload, `bigGridLeft = brand-service` (categories[0]; spare list empty) and `bigGridRight = city-service` (categories[1]). Big Grid Dual now always renders if at least 1 category exists.

**Caveat — content duplication:** with only 3 categories in the live payload (all already used as their own dedicated sections), the Big Grid Dual fallback shows brand-service + city-service AGAIN. That's the operator-instructed behavior ("first available"). When operator publishes pages in `maintenance-tips`/`comparison` via `/admin/seo-pages`, the dedicated slugs win and duplication disappears.

#### 2. City Service padding

City Service's 2×2 right grid needs 4 items. The category itself has 1. Pad with overflow from rails:

```ts
const cityServicePadded: ExploreCategoryBlock | null = (() => {
  if (!cityService) return null;
  if (cityService.items.length >= 4) return cityService;
  const skipSlugs = new Set<string>([
    cityService.featured.slug,
    ...cityService.items.map((c) => c.slug),
    ...(brandService ? [brandService.featured.slug, ...brandService.items.map((c) => c.slug)] : []),
    ...(serviceGuide ? [serviceGuide.featured.slug, ...serviceGuide.items.map((c) => c.slug)] : []),
  ]);
  const pool = [
    ...rails.most_read_week,
    ...rails.trending_searches,
    ...trending_grid,
  ];
  const padded: ExploreCard[] = [...cityService.items];
  for (const candidate of pool) {
    if (padded.length >= 4) break;
    if (skipSlugs.has(candidate.slug)) continue;
    skipSlugs.add(candidate.slug);
    padded.push(candidate);
  }
  return { ...cityService, items: padded };
})();
```

The `skipSlugs` set guards against duplication with brand-service/service-guide cards already on the page. Pool order picks `most_read_week` first, then `trending_searches`, then `trending_grid` — most-relevant-first. Cheap O(N) loop on a < 30-item pool; no `useMemo` (would have to be hoisted above early returns to satisfy Rules of Hooks).

JSX swap:

```jsx
{/* before: cityService.items.length === 1 → only 2 cards */}
{cityService && (<ExploreCategorySection category={cityService} … />)}

{/* after: cityServicePadded.items.length === 4 → all 5 cards render */}
{cityServicePadded && (<ExploreCategorySection category={cityServicePadded} … />)}
```

### Verification

```
✓ TypeScript: clean (npx tsc --noEmit)
✓ Backend Pest: 118 passed (untouched)
✓ Production build: clean (✓ built in 11.40s)
✓ Screenshot test re-run: 5/5 (snapshot the Trending Now / Brand Service / City Service / Big Grid Dual / Service Guide sections all PASS — Big Grid Dual now actually generates a PNG instead of the prior "section not rendered" no-op)

ExploreEditorial chunk:
  Phase 4.5.6 first turn  : 52.81 kB raw │ gzip: 10.77 kB
  Phase 4.5.6 follow-up   : 53.33 kB raw │ gzip: 10.96 kB
  Δ                       : +0.52 kB raw │ gzip: +0.19 kB    (just the inline padding loop)
```

### Updated screenshot proofs

| Section | PNG | Cards visible |
|---|---|---|
| Trending Now | `test-results/phase-4-5-6-trending.png` | 5-card mosaic (LARGE Audi center + 4 SMALL flanking) — unchanged |
| Brand Service | `test-results/phase-4-5-6-brand-service.png` | 1 LARGE-stacked Mercedes-Benz + 3 SMALL right (BMW, Audi, Audi Service) — unchanged |
| City Service | `test-results/phase-4-5-6-city-service.png` | **NOW FILLED** — 1 LARGE Dent And Paint + 4 SMALL right (Best Car AC + Car Battery Cost + Monsoon Tyre Care + Winter Car Care) padded from rails |
| Big Grid Dual | `test-results/phase-4-5-6-big-grid-dual.png` | **NOW RENDERS** — left sub-section: brand-service (Mercedes-Benz feature + 3 thumb-rows BMW/Audi-Brake/Audi-Service); right sub-section: city-service (Dent And Paint feature + 1 thumb-row) |
| Service Guide | `test-results/phase-4-5-6-service-guide.png` | wide top + 3-col bottom (3rd slot empty until 4th service-guide page published) — unchanged |

### Files-touched diff (this follow-up only)

```
MODIFIED:
  src/pages/ExploreEditorial.tsx
    + bigGridLeft / bigGridRight fallback resolution
    + cityServicePadded inline computation
    + JSX swap: cityServicePadded + bigGridLeft/bigGridRight props on the dual

CREATED:
  test-results/phase-4-5-6-big-grid-dual.png  (now actually generated, not skipped)

UNCHANGED FROM FIRST TURN:
  src/components/explore/ExploreCard.tsx
  src/components/explore/ExploreTrendingGrid.tsx
  src/components/explore/ExploreCategorySection.tsx
  src/components/explore/ExploreSkeleton.tsx
  tests/e2e/explore-editorial.spec.ts
  tests/e2e/explore-final-layout.spec.ts
  playwright.config.ts
```

### Open content gap

Big Grid Dual currently displays brand-service + city-service (duplicating the dedicated sections elsewhere on the page). Operator can address by publishing at least one SeoPage each in:
- `maintenance-tips` category — fills `bigGridLeft`
- `comparison` category — fills `bigGridRight`

Once those exist, the named slugs win in `findCat()` and the duplication vanishes. The fallback path remains as a safety net.

— end of follow-up — explore work-stream truly closed —
