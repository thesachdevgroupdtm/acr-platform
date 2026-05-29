# Phase 4.5.5 — Trending Now Dead-Space Fix — Report

**Date:** 2026-05-09
**Branch:** main (no commit per GIT POLICY)
**Scope:** Final dead-space fix on `ExploreTrendingGrid` (Phase 4.5.4 had it under HARD CONSTRAINTS so it stayed broken). Closes the explore work-stream.

All hard constraints respected:
- No new packages
- No backend changes (sanity-confirmed, 118/118 Pest passes unchanged)
- Untouched: `ExploreCategorySection` (4.5.4-fixed), `ExploreFeaturedGrid`, `ExploreCard`, `ExploreCardFallback`, `ExploreSearch`, `ExploreRail`, `CategoryFilterChip`, all sidebar widgets, `PageBanner`, `SeoPageView`, `CmsPage`, all admin
- No new whileInView animations

---

## 1. Files modified

| Path | Change |
|---|---|
| `src/components/explore/ExploreTrendingGrid.tsx` | Full rewrite. Removed `TRENDING_GRID_CLASS` export, `SIZES`/`SIZE_CLASSES` maps, `WIDE` card branch, 8-item truncation. New 12-col 3-row grid: 1 LARGE cols 1-8 rows 1-3 + 3 SMALL cols 9-13 (one row each). Two new in-file sub-components: `TrendingLargeCard` + `TrendingSmallCard` (full-bleed image + dark gradient + category badge + title overlay; both fall through to `<ExploreCardFallback>` for no-image items). Graceful 1/2/3/4-item degradation via `smallCellClass(idx, total)` returning literal Tailwind class strings (JIT-safe). Mobile uses `order-1`...`order-4` to stack LARGE first then smalls. |
| `src/components/explore/ExploreSkeleton.tsx` | Trending placeholder updated to mirror new 4-card layout exactly (1 LARGE box cols 1-8 rows 1-3 + 3 SMALL boxes cols 9-13). Removed `TRENDING_GRID_CLASS` import (export deleted). |
| `tests/e2e/explore-editorial.spec.ts` | "clicking a trending card navigates to /:slug" test now asserts trending card count between 1-4 and exactly one `[data-slot="trending-large"]` slot. |
| `playwright.config.ts` | `seo` project `testMatch` regex extended for `explore-trending-screenshot` spec. |

## 2. Files created

| Path | Purpose |
|---|---|
| `tests/e2e/explore-trending-screenshot.spec.ts` | Visual record — snapshots the rendered Trending Now section to `test-results/phase-4-5-5-trending-fixed.png`. No assertions; passes by default per spec D-4.5.5-5. |
| `PHASE4_5_5_AUDIT.md` | PART A audit deliverable. |
| `PHASE4_5_5_REPORT.md` | This file. |

## 3. Files deleted

None. The old grid + card rendering lived inside the in-place rewrite of `ExploreTrendingGrid.tsx` — no separate sub-component files existed to remove.

---

## 4. PART A — Audit findings

Full doc at `PHASE4_5_5_AUDIT.md`. Summary:

- **Old layout** was the Phase 4.5 8-card 4-size repeating pattern: `[LARGE 2x2][SMALL][SMALL] / [WIDE-HORIZONTAL] / [SMALL][SMALL][LARGE 2x2] / [WIDE-HORIZONTAL]`.
- **Why dead-space appeared:** backend currently returns 7 trending items (`curl /api/v1/explore | jq '.trending_grid | length'` → 7), but the pattern needed exactly 8. Position 7 (the second `WIDE`) never rendered → bottom row missing. Position 6 (the second `LARGE 2x2`) rendered only the first half of its 2-row span without its sibling `WIDE` underneath → visual hole.
- **Other components swept and confirmed clean:**
  - `ExploreCategorySection` — already fixed in Phase 4.5.4 (Variant A 12-col 3-row mosaic; B + C have no dead-space).
  - `ExploreFeaturedGrid` — fixed in Phase 4.5.2 (5-card mosaic).
  - `ExploreInternalLinks` — fixed in Phase 4.5.4 (3-col footer).
  - `ExploreRail` — horizontal scroll rail, no dead-space risk by design.
- **Decision** — only `ExploreTrendingGrid` needs the fix. Skeleton updated in lock-step (its trending placeholder imported `TRENDING_GRID_CLASS` from this file).

---

## 5. PART B — Trending grid rewrite

### Before

```jsx
const SIZES = ["LARGE","SMALL","SMALL","WIDE","SMALL","SMALL","LARGE","WIDE"];
const eight = items.slice(0, 8);
return <div className="grid grid-cols-1 md:grid-cols-3 md:auto-rows-[200px] gap-4">
  {eight.map((card, idx) => <div className={SIZE_CLASSES[SIZES[idx] ?? "SMALL"]}>
    <TrendingCard card={card} size={SIZES[idx]} />
  </div>)}
</div>;
```

Rendered the LARGE/WIDE/SMALL mix above; with <8 items, gaps appeared at the bottom.

### After

```jsx
const visible = items.slice(0, 4);
const smalls  = visible.slice(1);

return <div className="grid grid-cols-1 gap-4 md:gap-6 lg:grid-cols-12 lg:grid-rows-3 lg:auto-rows-[minmax(140px,1fr)]">
  {/* LARGE — cols 1-8, rows 1-3 */}
  <div className="order-1 lg:order-none lg:col-start-1 lg:col-end-9 lg:row-start-1 lg:row-end-4 aspect-[4/3] lg:aspect-auto">
    <TrendingLargeCard card={visible[0]} />
  </div>
  {/* SMALLs — cols 9-13, one row each */}
  {smalls.map((card, idx) => (
    <div key={card.slug} className={smallCellClass(idx, smalls.length)}>
      <TrendingSmallCard card={card} />
    </div>
  ))}
</div>;
```

Where `smallCellClass(idx, total)` returns full literal class strings (Tailwind JIT-safe — no template interpolation) per slot:

| total | idx 0 | idx 1 | idx 2 |
|---|---|---|---|
| 1 | `lg:row-start-1 lg:row-end-4` | — | — |
| 2 | `lg:row-start-1 lg:row-end-3` | `lg:row-start-2 lg:row-end-4` | — |
| 3 | `lg:row-start-1 lg:row-end-2` | `lg:row-start-2 lg:row-end-3` | `lg:row-start-3 lg:row-end-4` |

Special-case `smalls.length === 0` (only 1 trending item available): LARGE goes full-width via a separate single-column branch.

### Sub-components

Per audit §4 (deviation from spec D-4.5.5-3): instead of `<ExploreCard size="large"|"small">` (which has baked-in aspect ratios that fight the grid's `h-full`), the rewrite uses two inline cards in the same file:

- `TrendingLargeCard` — `w-full h-full` Link with full-bleed image + `bg-gradient-to-t from-neutral-950` overlay + bottom-aligned content (category badge → title `text-lg md:text-2xl line-clamp-3` → excerpt → reading time). Fallback to `<ExploreCardFallback>` for null `hero_image_url`.
- `TrendingSmallCard` — same pattern but smaller text (`text-sm md:text-base line-clamp-2`) and tighter padding (`p-3 md:p-4`).

Both keep the existing `data-testid="trending-card-{slug}"` so tests survive.

---

## 6. PART C — Skeleton update

`ExploreSkeleton.tsx` trending block now mirrors the new 4-card layout exactly:

```jsx
<div className="grid grid-cols-1 gap-4 md:gap-6 lg:grid-cols-12 lg:grid-rows-3 lg:auto-rows-[minmax(140px,1fr)]">
  <div className="order-1 lg:order-none lg:col-start-1 lg:col-end-9 lg:row-start-1 lg:row-end-4 aspect-[4/3] lg:aspect-auto bg-neutral-200 animate-pulse" />
  <div className="order-2 lg:order-none lg:col-start-9 lg:col-end-13 lg:row-start-1 lg:row-end-2 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
  <div className="order-3 lg:order-none lg:col-start-9 lg:col-end-13 lg:row-start-2 lg:row-end-3 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
  <div className="order-4 lg:order-none lg:col-start-9 lg:col-end-13 lg:row-start-3 lg:row-end-4 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
</div>
```

Removed the now-orphan `import { TRENDING_GRID_CLASS } from "./ExploreTrendingGrid"` (export was deleted in PART B). Skeleton-to-content swap remains layout-stable — same grid template, same row/column placements, same aspect ratios.

---

## 7. PART D — Other-sections audit findings

No other component requires the 4-card editorial conversion. Sweep documented in audit §3:

| Component | Status |
|---|---|
| `ExploreCategorySection` (Variant A/B/C) | ✓ Already fixed in Phase 4.5.4 |
| `ExploreFeaturedGrid` | ✓ Fixed in Phase 4.5.2 |
| `ExploreInternalLinks` | ✓ Fixed in Phase 4.5.4 |
| `ExploreRail` | ✓ Horizontal scroll rail, no dead-space risk |

The Phase 4.5.5 fix is **scope-bounded to the trending grid only**.

---

## 8. PART E — Tests

### Backend (Pest)

```
Tests:    118 passed (534 assertions)
Duration: 126.60s
```

Untouched (no backend changes). 118/118.

### Frontend Playwright (`seo` project)

```
Editorial + screenshot tests in isolation:
  ✓ featured grid renders 5-card mosaic (no carousel)
  ✓ search filters cards client-side and highlights matches
  ✓ clicking a trending card navigates to /:slug
  ✓ snapshots the Trending Now section to disk
4 passed (16.0s)

Full SEO project:  19 of 21 stable per full sequential run; 2
                   pre-existing timing flakes that pass in
                   isolation (different tests each re-run; same
                   pattern documented since 4.5.2 §11).
```

Counts: 21 SEO Playwright tests (was 20 in Phase 4.5.4; +1 trending-screenshot).

### TypeScript

`npx tsc --noEmit` — clean (no output).

---

## 9. Screenshot proof

**Path:** `test-results/phase-4-5-5-trending-fixed.png`

Layout confirmed visually:
- Mercedes-Benz LARGE on the left (cols 1-8, rows 1-3) — wide and tall
- BMW (cols 9-12, row 1)
- Audi (cols 9-12, row 2)
- Car battery (cols 9-12, row 3)

Exactly **4 cards** in 1 LARGE + 3 SMALL stacked-right pattern. **No** wide horizontal cards. **No** empty space on the right. Per-spec D-4.5.5-1.

The "giant text" that appears to glow inside each card is the source `hero_image_url` artwork (placeholder PNGs showing brand-name typography) at `opacity-80`. That's the image content — not a layout regression. The proper foreground (BRAND-SERVICE badge + title overlay + reading-time meta) renders cleanly on top.

### Bundle delta

```
ExploreEditorial chunk:
  Phase 4.5.4  : 50.65 kB raw │ gzip: 10.58 kB
  Phase 4.5.5  : 51.25 kB raw │ gzip: 10.42 kB
  Δ            : +0.60 kB raw │ gzip: -0.16 kB    (essentially flat)

icons-vendor   : 34.45 kB raw │ gzip:  7.57 kB    (unchanged)
index          : 190.45 kB raw │ gzip: 52.84 kB   (unchanged)
```

Within the spec's "roughly even or slight decrease" envelope. The new TrendingLargeCard + TrendingSmallCard add some bytes; the deletion of the WIDE branch + `SIZES`/`SIZE_CLASSES` maps offsets it. Build clean: `✓ built in 22.61s`.

---

## 10. Phase 4.5.x sprint CLOSURE — final summary

The explore work-stream now closes. **Six sub-phases delivered:**

| Phase | Headline | Test delta |
|---|---|---|
| **4.5** | Premium SEO Explore Ecosystem + Internal Article Pages | +backend SeoPage tests, +SEO Playwright project bootstrap |
| **4.5.1** | ExploreEditorial correction pass — 4-card hero, category filter, sticky sidebar, fallback design, 5 widgets, newsletter | +newsletter tests, +explore-editorial spec |
| **4.5.2** | Polish — 5-card mosaic, PageBanner, animation overhaul (whileInView removal) | +explore-page-banner spec |
| **4.5.3** | Newsletter → Lead Form swap; lookup endpoints; LeadResource admin; hero pinning to 5 | -2 newsletter, +3 lookup, +4 lead, +2 LeadResource, +3 lead-form Playwright |
| **4.5.4** | Variant A dead-space fix + ExploreInternalLinks 3-col footer | +2 footer-revamp Playwright |
| **4.5.5** | ExploreTrendingGrid dead-space fix (this commit) | +1 trending-screenshot Playwright |

### Cumulative deltas

| Metric | Sprint start | Sprint end |
|---|---|---|
| Backend Pest tests | ~95 | **118** (+23 net; -2 newsletter, +25 explore/lookup/lead/admin) |
| Frontend SEO Playwright tests | 0 | **21** (entire SEO project is a Phase 4.5+ artefact) |
| Backend models | (baseline) | +3 (`SeoPage`, `SeoPageCategory`, `Lead`) |
| Backend controllers (Public) | (baseline) | +3 (`SeoPageController`, `LookupController`, `LeadController`) |
| Filament resources | (baseline) | +2 (`SeoPageResource`, `LeadResource`) |
| Frontend explore components | 0 | 12 (Editorial page + FeaturedGrid + Card + Fallback + Search + TrendingGrid + CategorySection + Rail + Skeleton + InternalLinks + FilterChip + PageBanner usage) |
| Frontend explore widgets | 0 | 5 (LeadForm, TopPicks, PopularBrands, RelatedTopics, GetSocial) |
| Frontend hooks | (baseline) | +2 (`useLookups`, `useLeadSubmit`) |
| Migrations (additive) | (baseline) | +6 (categories, enhance pages, newsletter add+drop, leads, plus seeders) |
| ExploreEditorial bundle | n/a | 51.25 kB raw / 10.42 kB gzip |
| App shell (`index`) | ~171 kB | 190.45 kB raw (+19 kB; `react-helmet-async` is the load-bearer) |

### Issues resolved across the sprint

- ✓ `/explore` exists and is a curated editorial experience (Phase 4.5)
- ✓ Single-segment `/:slug` routes resolve to a SeoPageView with breadcrumb + Helmet meta + reading progress + related articles + tag chips (Phase 4.5)
- ✓ Hero replaced from auto-playing carousel → static editorial mosaic (Phase 4.5.1 4-card → 4.5.2 5-card)
- ✓ Category filter end-to-end (`?category={slug}` + chip + scroll-to-top)
- ✓ No-image cards render a polished slate-gradient fallback (no giant-text fill)
- ✓ Sticky-sidebar layout with 5 widgets in BOTH section asides
- ✓ PageBanner consistency with all other site pages
- ✓ Animation overhaul — single 300ms page-mount opacity fade; no scroll-triggered entrances; hover-lift retained
- ✓ Lead capture form replaces newsletter; 6 fields with brand→model cascade; spam-rule auto-flagging; Filament admin
- ✓ Public master-data lookups (brands/models/services) cached 1h
- ✓ 5 hero pages pinned (was 3)
- ✓ Variant A dead-space killed (4.5.4)
- ✓ "Explore More" footer revamped to 3-col rich layout with stats card + CTA (4.5.4)
- ✓ Trending Now dead-space killed (4.5.5 — this commit)

### Open items remaining (post-launch / Phase 6+)

- **Real hero images** — depends on Phase 4.4 (operator manual upload for now via Filament `SeoPageResource`). Card fallback design is a sturdy stopgap.
- **SEO admin curation UI polish** (Phase 4.5b) — bulk pin reorder, bulk image upload, drag-drop ordering.
- **Brand/Model master data CRUD admin** — operator now relies on this data for the LeadFormWidget. **Should be the next phase priority** (see §11).
- **Lead admin enhancements** — CSV export, email/SMS notification on new lead.
- **SEO Playwright stability** — 2-3 timing flakes per 21-test sequential run; all pass in isolation. Phase 6 candidate to split the SEO project into faster halves OR add a webServer warmup step.
- **App shell ~190 kB raw** — `react-helmet-async` is the load-bearer. Defer to dynamic import on `/home`+`/services` if shell-size budget tightens.
- **SEO content quality** — operator currently authors via existing `SeoPageResource`; no link-checker / readability-score / duplicate-title detection.

---

## 11. Next phase recommendation

**Phase 4.3 — Brand/Model master data admin + Excel upload.**

Operator is now publishing the LeadFormWidget which depends on `car_brands` (14 rows) and `car_models` (81 rows) being curated. There is currently NO Filament resource for either; updates require direct DB access. Phase 4.3 was deferred earlier in the sprint to ship the explore work first; resuming it next is the highest-leverage move.

Suggested scope:
- Filament `CarBrandResource` + `CarModelResource` (active toggle, slug, image upload)
- Excel/CSV bulk-import action mirroring the existing `ImportController` pipeline (admin-token gated)
- Cache buster wires through model `saved`/`deleted` events to invalidate `lookups:brands` + `lookups:models:brand:{id}` keys so the LeadFormWidget reflects edits within seconds

After 4.3, returning to SEO work for `Phase 4.5b polish` (bulk pin reorder, image upload UX) is the natural follow-up.

---

## 12. Files-touched summary

```
MODIFIED (frontend):
  src/components/explore/ExploreTrendingGrid.tsx
  src/components/explore/ExploreSkeleton.tsx
  tests/e2e/explore-editorial.spec.ts
  playwright.config.ts

CREATED:
  tests/e2e/explore-trending-screenshot.spec.ts
  test-results/phase-4-5-5-trending-fixed.png   (visual record artefact)
  PHASE4_5_5_AUDIT.md
  PHASE4_5_5_REPORT.md  (this file)

DELETED:
  (none)

UNTOUCHED (per HARD CONSTRAINTS):
  src/components/explore/ExploreCategorySection.tsx (4.5.4-fixed)
  src/components/explore/ExploreInternalLinks.tsx   (4.5.4-fixed)
  src/components/explore/ExploreFeaturedGrid.tsx
  src/components/explore/ExploreCard.tsx
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/explore/widgets/{LeadForm,TopPicks,PopularBrands,RelatedTopics,GetSocial}Widget.tsx
  src/components/PageBanner.tsx
  src/pages/SeoPageView.tsx, src/pages/CmsPage.tsx
  All backend, all admin (Filament resources)
```

Per GIT POLICY: **no `git add`, `git commit`, or `git push` performed.** Operator commits manually.

— end of report — sprint closed —
