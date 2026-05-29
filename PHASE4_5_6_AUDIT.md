# Phase 4.5.6 — Audit (PART A)

**Date:** 2026-05-09
**Scope:** Read-only audit before implementing the operator's hand-drawn final blueprint for /explore.

---

## 1. Backend payload structure (verified via `curl /api/v1/explore`)

```
hero_count           = 5    ✓  (5-card mosaic OK)
trending_grid_count  = 7    ✓  (5-card mosaic uses first 5)
rails.trending      = 12   ✓  (rail unchanged)
rails.most_read     = 12   ✓  (rail unchanged)

categories present:
  brand-service    | items=4   ✓  Has 1 LARGE-stacked + 3 SMALL right
  city-service     | items=1   ⚠  Need 4 for 2x2 right grid → 3 slots empty
  service-guide    | items=2   ⚠  Need 3 for bottom row → 1 slot empty
```

### Categories MISSING from payload
- `maintenance-tips` — backend has zero published pages in this category
- `comparison` — backend has zero published pages in this category

**Big Grid Dual fallback strategy** (per spec D-4.5.6-5 + PART E step 19): If neither maintenance-tips nor comparison exist, the entire Big Grid Dual section gracefully hides (`return null`). Operator authors content via `/admin/seo-pages` Filament UI to surface the section.

### Data-state-driven render plan

| Section | Cards configured | Cards rendered | Empty slots |
|---|---:|---:|---:|
| Hero (FeaturedGrid) | 5 | 5 | 0 |
| Trending Now (5-mosaic) | 5 | 5 (from 7 available) | 0 |
| Brand Service | 1 LARGE-stacked + 3 SMALL | 4 (4 items) | 0 ✓ |
| City Service | 1 LARGE + 4 SMALL (2×2) | 2 (1 item) | 3 |
| Big Grid Dual | 2× (1 feature + 3 thumb-rows) | 0 (categories absent) | section hidden |
| Service Guide | 1 LARGE wide + 3 SMALL bottom | 3 (1 featured + 2 items) | 1 |

The "empty slots" here are CSS grid cells that reserve space but render no card. They're a content-state concern, not a layout bug — operator addresses by publishing more SeoPages via the existing Filament admin.

---

## 2. Files to modify

| Path | Why |
|---|---|
| `src/components/explore/ExploreCard.tsx` | Add `'large-stacked'` size variant (image-on-top + white panel below with title/description/meta) per D-4.5.6-3. Existing 5 variants untouched. |
| `src/components/explore/ExploreTrendingGrid.tsx` | Rewrite to 5-card mosaic per D-4.5.6-1. Different from FeaturedGrid: `items[2]` is the LARGE center; `items[0,1]` go left, `items[3,4]` go right. |
| `src/components/explore/ExploreCategorySection.tsx` | Convert variant prop from `"A" \| "B" \| "C"` (rotation-based) to `'brand-service' \| 'city-service' \| 'service-guide' \| 'big-grid'` (intent-based, slug-keyed). Each variant has its own grid + card-style mix per D-4.5.6-2/4/5/6. |
| `src/pages/ExploreEditorial.tsx` | Drop the rotation-based `VARIANTS` constant + idx % 3 wiring. Find categories by slug (brand-service, city-service, service-guide, maintenance-tips, comparison) and render each in its dedicated slot per D-4.5.6 PART E step 18. Add Big Grid Dual placement between rails. |
| `src/components/explore/ExploreSkeleton.tsx` | Update placeholders to match new section structure per PART F. |

## 3. Files NEW

| Path | Purpose |
|---|---|
| `tests/e2e/explore-final-layout.spec.ts` | 5 screenshot specs (Trending, Brand Service, City Service, Big Grid Dual, Service Guide). |
| `PHASE4_5_6_AUDIT.md` | This doc. |
| `PHASE4_5_6_REPORT.md` | After PART H. |

## 4. Files NOT touched (per HARD CONSTRAINTS)

```
src/components/explore/ExploreFeaturedGrid.tsx
src/components/explore/ExploreSearch.tsx
src/components/explore/ExploreRail.tsx
src/components/explore/CategoryFilterChip.tsx
src/components/explore/ExploreCardFallback.tsx
src/components/explore/ExploreInternalLinks.tsx
src/components/explore/widgets/{LeadForm,TopPicks,PopularBrands,RelatedTopics,GetSocial}Widget.tsx
src/components/PageBanner.tsx
src/pages/SeoPageView.tsx, src/pages/CmsPage.tsx
All backend, all admin
```

## 5. Key design notes

- **Variant prop refactor** is necessary. The rotation-based system (`A`/`B`/`C` keyed by `idx % 3`) was generic — it didn't know which category was at which index. The new design needs slug-aware rendering ("Brand Service must use the LARGE-stacked layout", "City Service must use the 2×2 right grid"). Switching the prop to slug-keyed variants makes the intent explicit and the data flow predictable.

- **`big-grid` is rendered via a dual-category wrapper**. Spec PART D-4.5.6-5 + step 16 leave implementation choice — I'll create a small inline `<BigGridDual>` helper INSIDE `ExploreCategorySection.tsx` that takes two category props. Simpler than threading a new public component.

- **Service Guide bottom-row "3-card grid"** — current Variant C already uses `md:grid-cols-3` + `items.slice(0, 3)`. The actual visible bug is data-driven (only 2 items in payload). No code change needed for the layout; the empty 3rd slot is a content-state issue documented in §1.

- **Tailwind JIT class generation** — every grid utility (`lg:col-start-X lg:col-end-Y lg:row-start-Z lg:row-end-W`) needs to be a literal class name (not template-interpolated) for the JIT to detect it. Following the pattern Phase 4.5.4 + 4.5.5 established.

— end of audit —
