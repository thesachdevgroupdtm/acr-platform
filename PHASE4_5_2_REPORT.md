# Phase 4.5.2 — ExploreEditorial Polish Pass — Report

**Date:** 2026-05-09
**Branch:** main (no commit per GIT POLICY)
**Scope:** Surgical polish on Phase 4.5.1 output — three corrections from operator visual review.

All hard constraints respected:
- No new packages
- `app/Models/CmsPage.php` untouched, `src/pages/SeoPageView.tsx` untouched
- Zero backend changes (sanity-checked)
- All Phase 4.5 / 4.5.1 widgets, search, sticky sidebar, fallback, filter — all untouched
- 22 other PageBanner consumers (Services, About, etc.) untouched

---

## 1. Files created

| Path | Purpose |
|---|---|
| `tests/e2e/explore-page-banner.spec.ts` | 2 tests — banner h1 renders, breadcrumb Home is clickable + navigates to `/`. |
| `PHASE4_5_2_AUDIT.md` | PART A audit deliverable. |
| `PHASE4_5_2_REPORT.md` | This document. |

## 2. Files modified

| Path | Change |
|---|---|
| `src/components/explore/ExploreFeaturedGrid.tsx` | Full rewrite — 4-card grid → **5-card mosaic** per D-4.5.2-1. CSS-grid 12-col 2-row with explicit `lg:col-start/col-end/row-start/row-end` placements; mobile uses `order-1` to put LARGE on top + 4 small in 2×2. Graceful degradation for 3 / 4 / 5 hero pages. |
| `src/components/explore/ExploreCard.tsx` | Stripped both `motion.div` entrance wrappers (lines 54 + 110); removed `motion` import. Hover styles unchanged. |
| `src/components/explore/ExploreCategorySection.tsx` | Stripped `motion.section` entrance + `motion.div` wrappers on `ListItemCard` and `SmallCard` (3 sites total); removed `motion` import. Both `index` props now `_index` (intentionally unused; kept in signature for caller compatibility). |
| `src/components/explore/ExploreTrendingGrid.tsx` | Removed per-card `motion.div` wrapper + 0.05s stagger delay. Simplified to plain `<div>` with the same grid-span class. Removed `motion` import. |
| `src/components/explore/ExploreInternalLinks.tsx` | `motion.section` → `<section>`; removed `motion` import. |
| `src/components/explore/ExploreSkeleton.tsx` | Now mirrors PageBanner (h-[40vh] min-h-[300px] dark bar) + 5-card mosaic layout exactly. Layout-stable swap: skeleton positions match content positions byte-for-byte. |
| `src/pages/ExploreEditorial.tsx` | Imports `PageBanner` + `useNavigate`. Banner sits ABOVE the page-level fade. Body wrapped in single `motion.div` with `initial={{opacity:0}} animate={{opacity:1}} transition={{duration:0.3,ease:"easeOut"}}` — one mount-fade for the entire page. Stripped the inner `motion.div` around the trending header. |
| `tests/e2e/explore-editorial.spec.ts` | Updated `featured grid renders 4 static cards` → `featured grid renders 5-card mosaic (no carousel)`. Asserts slot count between 3–5 (graceful degradation), `featured-large` slot present exactly once, no `explore-hero` testid. |
| `playwright.config.ts` | `seo` project `testMatch` regex extended for `explore-page-banner` spec. |

## 3. Files deleted

None. Only the *old* mosaic logic inside `ExploreFeaturedGrid` was replaced — the file itself stays.

---

## 4. PART A — Audit findings

Full doc at `PHASE4_5_2_AUDIT.md`. Summary:

**PageBanner pattern:** EXISTS as `src/components/PageBanner.tsx` (~85 lines). Used by 22 pages (Services, About, Contact, Coupons, Insurance, Corporate, Gallery, Testimonials, ServiceCenters, ServiceCenterDetail, ServiceDetail, ServiceCategory, BookingConfirmation, OrderDetail, MyBookings, Cart, Checkout, Sitemap, CmsPage, Offers, NotFound). `/explore` was the lone editorial page missing it.

**Decision: Path A — REUSE.** No extraction needed.

**Key signature note:** Component prop is `breadcrumbs` (plural), each crumb has `{label, onClick?}` not `{label, to}`. Title casing convention in the codebase is Title Case ("Our Services", "About Us"), so I used `title="Explore"` not `"EXPLORE"` — the component already applies `uppercase tracking-tighter` styling at render.

**whileInView usage list (8 sites across 5 files, all stripped):**

| File | Line(s) | Context |
|---|---|---|
| `src/components/explore/ExploreCard.tsx` | 56, 112 | horizontal + stack layout wrappers |
| `src/components/explore/ExploreCategorySection.tsx` | 28, 210, 254 | section root, ListItemCard wrapper, SmallCard wrapper |
| `src/components/explore/ExploreTrendingGrid.tsx` | 59 | per-card wrapper with 0.05s stagger |
| `src/components/explore/ExploreInternalLinks.tsx` | 29 | footer section root |
| `src/pages/ExploreEditorial.tsx` | 134 | trending-grid header block |

Post-strip verification:

```
Grep whileInView in src/components/explore: No matches found
Grep whileInView in src/pages/ExploreEditorial.tsx: No matches found
```

---

## 5. PART B — Featured grid rewrite

**Layout (12-col 2-row CSS grid, desktop):**

```
┌────────┬────────────────┬────────┐
│   C1   │                │   C4   │   row 1
├────────┤  C3 (LARGE)    ├────────┤
│   C2   │  cols 4-9      │   C5   │   row 2
└────────┴────────────────┴────────┘
```

| Slot | Desktop placement | data-slot |
|---|---|---|
| C1 | cols 1-3, row 1 (top-left small) | `featured-small` |
| C2 | cols 1-3, row 2 (bottom-left small) | `featured-small` |
| C3 | cols 4-9, rows 1-2 (CENTER LARGE) | `featured-large` |
| C4 | cols 10-12, row 1 (top-right small) | `featured-small` |
| C5 | cols 10-12, row 2 (bottom-right small) | `featured-small` |

**Mobile:** single column. C3 LARGE renders first via `order-1`; smalls follow via `order-2..5`.

**Graceful degradation by `pages.length`:**
- ≥5 → all 5 slots render
- 4 → C1 + C2 + C3 + C4 (skip C5)
- 3 → C1 + C3 + C4 (skip C2 + C5; keeps the LARGE center + a small either side)
- <3 → returns `null` (parent's empty branch handles it)

**Backend confirms 3 hero pages currently** (verified via `curl /api/v1/explore | jq .hero | length`). The degradation path renders 3 slots in the live environment.

Cards continue to use the existing `<ExploreCard size="large" | "small">` component — no rewrite of card internals (per HARD CONSTRAINTS).

---

## 6. PART C — PageBanner integration

**Path A: REUSE existing component.**

`src/pages/ExploreEditorial.tsx` now imports:

```tsx
import PageBanner from "../components/PageBanner";
import { useNavigate, useSearchParams } from "react-router-dom";
```

And renders, ABOVE the page-level fade wrapper:

```tsx
<PageBanner
  title="Explore"
  breadcrumbs={[
    { label: "Home", onClick: () => navigate("/") },
    { label: "Explore" },
  ]}
/>
```

The banner mounts instantly with the page chrome; the fade wraps only the editorial body so the banner doesn't fade in twice.

**Pages affected:** none beyond /explore. No extraction or refactor — the component was already shared.

---

## 7. PART D — Animation overhaul

**Single page-level fade in `ExploreEditorial.tsx`:**

```tsx
<motion.div
  initial={{ opacity: 0 }}
  animate={{ opacity: 1 }}
  transition={{ duration: 0.3, ease: "easeOut" }}
>
  {/* hero grid + search + sticky sections + rails + footer */}
</motion.div>
```

One animation. Fires once on mount. No scroll triggers, no stagger, no y-transforms.

**File-by-file animation removals:**

| File | Removed |
|---|---|
| `ExploreCard.tsx` | 2× `motion.div` wrappers with `initial / whileInView / viewport / transition`; `motion` import |
| `ExploreCategorySection.tsx` | 1× `motion.section` (root) + 2× `motion.div` (ListItemCard, SmallCard) + `motion` import |
| `ExploreTrendingGrid.tsx` | per-card `motion.div` with `delay: idx * 0.05` stagger; `motion` import |
| `ExploreInternalLinks.tsx` | `motion.section` root; `motion` import |
| `ExploreEditorial.tsx` | inner `motion.div` around trending header; replaced page-body `<div>` with `motion.div` for the single fade |

**Animations KEPT (per spec D-4.5.2-3):**
- Card hover-lift — `hover:-translate-y-1 hover:shadow-xl` (Tailwind CSS)
- Image-scale on hover — `group-hover:scale-[1.03] transition-transform duration-500` (Tailwind CSS)
- Search dropdown enter/exit — owned by `ExploreSearch.tsx` (untouched)
- CategoryFilterChip — untouched
- PageBanner internal mount fade — owned by shared component, fires once on mount (not whileInView)

---

## 8. PART E — Skeleton alignment

`ExploreSkeleton.tsx` rewritten:

- **PageBanner placeholder:** `h-[40vh] min-h-[300px] mb-12` dark bar matching the actual PageBanner dimensions exactly. Includes a small breadcrumb-pulse + title-pulse so the silhouette reads as "page banner."
- **Featured-grid placeholder:** identical 12-col 2-row grid markup with all 5 slots filled with `bg-neutral-200 animate-pulse` blocks. Same `lg:col-start/col-end/row-start/row-end + aspect-[4/3] lg:aspect-auto` placements.
- **Trending grid / category sample / rail:** unchanged from Phase 4.5 — those layouts didn't change.

Net effect: the skeleton-to-content swap is a width/height-stable repaint. No layout shift on payload arrival.

---

## 9. PART F — Tests

### Backend (Pest)

```
Tests:    111 passed (498 assertions)
Duration: 23.40s
```

Untouched (no backend changes). Sanity-confirmed.

### Frontend (Playwright `seo` project)

```
15 passed (46.5s)
```

Was 13 in Phase 4.5.1; +2 new in Phase 4.5.2 (`explore-page-banner` × 2 tests).

| Spec | Tests |
|---|---|
| `explore-editorial.spec.ts` | 3 (featured-grid 5-card mosaic + search highlight + trending-card → /:slug) |
| `explore-category-filter.spec.ts` | 1 |
| `explore-no-image-fallback.spec.ts` | 1 |
| `explore-page-banner.spec.ts` | **2 NEW** (banner h1 + breadcrumb click; banner sits above fade) |
| `seo-pages.spec.ts` | 8 |

**Note on flakiness:** During development, two pre-existing tests (`Article tag chips`, `Unknown single-segment slug renders the NotFound page`) intermittently timed out under load when running the full project. Both pass in isolation and on the final clean run (15/15 in 46.5s). These are pre-existing timing flakes, not regressions from Phase 4.5.2 changes. The Playwright config already runs `workers: 1` sequentially with no retries — increasing the per-test timeout would mask rather than fix the flake. Recommend a Phase 6 stability pass on the SEO test suite.

### TypeScript

`npx tsc --noEmit` — clean (no output).

---

## 10. PART G — Bundle size delta

```
ExploreEditorial chunk:
  Phase 4.5.1  : 40.17 kB raw │ gzip:  8.20 kB
  Phase 4.5.2  : 41.40 kB raw │ gzip:  8.39 kB
  Δ            : +1.23 kB raw │ gzip: +0.19 kB
```

**Within spec's ±5 kB acceptable range.** PageBanner import + the page-level motion wrapper added a touch more than the animation removal saved. Net is small.

```
Full asset summary (relevant chunks):
ExploreEditorial-BF0k987i.js   41.40 kB │ gzip:  8.39 kB    (+1.23 kB raw vs 4.5.1)
SeoPageView                    23.24 kB │ gzip:  6.71 kB    (untouched)
CmsPage                        23.31 kB │ gzip:  6.19 kB    (untouched)
index-D9IeAsP4.js             190.49 kB │ gzip: 52.86 kB    (unchanged)
react-vendor                  193.82 kB │ gzip: 60.54 kB    (unchanged)
motion-vendor                 127.89 kB │ gzip: 42.02 kB    (unchanged)
```

Build: `✓ built in 15.85s`. Clean.

---

## 11. Deviations

1. **Bundle grew slightly (+1.23 kB raw)** rather than shrinking. PART G expected "marginally less (animation removal) OR marginally more (PageBanner if created)". Animation removal saved ~0.5 kB; the page-level `motion.div` + `useNavigate` import cost about ~1.7 kB (mostly the `motion.div` instance + JSX shape change). Net +1.23 kB raw, well within the ±5 kB envelope.

2. **PageBanner reused as-is** — the component has its own internal `motion.div` mount animation (`initial={{opacity: 0, y: 20}} animate={{opacity: 1, y: 0}}`). Per spec D-4.5.2-3 we removed scroll-triggered animations; this is a *mount-time* animation that fires once with the page chrome. It's consistent with the spec's spirit ("Single-shot opacity-only mount animation"). I left it untouched because (a) it matches the visual consistency goal — every other page uses the same banner with the same mount fade, and (b) modifying PageBanner would touch 22 other consumers and is out of scope for this surgical pass.

3. **Two pre-existing SEO tests flaked once each during development** but pass cleanly in isolation and on the final full run (15/15). Documented under §9. Not a regression from Phase 4.5.2.

4. **`category-filter-chip` is hidden when no `?category` param is set** — the `explore-page-banner` test doesn't try to verify the chip; it's only relevant on filtered URLs.

No other deviations.

---

## 12. Known issues / Phase 6 candidates

- **PageBanner internal animation** (`y: 20` on mount) could be unified with the spec's `opacity-only` direction. Out of scope here (touches 22 pages); flagged for a separate site-wide animation-cleanup pass.
- **SEO Playwright flakiness** — two tests intermittently timeout under sequential load. Recommend a stability pass: bump per-test timeouts on the slowest two, or split the `seo` project into two faster halves.
- **Hero source has 3 pages** in the live payload — the operator's reference image shows 5 cards. To see the full 5-card mosaic, the SeoPageMockSeeder would need to mark 5+ pages with `is_pinned`. Frontend handles 3/4/5 gracefully; backend enrichment is a content task, not code.
- **`ListItemCard` and `SmallCard` `index` prop** is now unused (just `_index`). The prop kept its place in the type signature so callers don't change. If a future refactor consolidates the card-component family further, the prop can be dropped entirely.

---

## 13. Phase 4.5 follow-ups (carried forward)

These were noted in earlier reports and are still open:

- Filament admin resource for `newsletter_subscriptions` so operators can export subscriber list.
- Bulk image upload for SEO pages (partially staged in Phase 4.4 commits).
- App shell ~190 kB / 53 kB gzip — `react-helmet-async` is the load-bearer. Defer Helmet to a dynamic import on `/home` + `/services` if shell-size budget tightens.

---

## 14. Files-touched summary

```
MODIFIED (frontend):
  src/components/explore/ExploreFeaturedGrid.tsx
  src/components/explore/ExploreCard.tsx
  src/components/explore/ExploreCategorySection.tsx
  src/components/explore/ExploreTrendingGrid.tsx
  src/components/explore/ExploreInternalLinks.tsx
  src/components/explore/ExploreSkeleton.tsx
  src/pages/ExploreEditorial.tsx
  tests/e2e/explore-editorial.spec.ts
  playwright.config.ts

CREATED:
  tests/e2e/explore-page-banner.spec.ts
  PHASE4_5_2_AUDIT.md
  PHASE4_5_2_REPORT.md  (this file)

DELETED:
  (none)

UNTOUCHED (per HARD CONSTRAINTS):
  src/components/PageBanner.tsx
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/widgets/*.tsx
  src/pages/SeoPageView.tsx
  src/pages/CmsPage.tsx
  src/pages/Services.tsx (and 21 other PageBanner consumers)
  All backend files
```

Per GIT POLICY: **no `git add`, `git commit`, or `git push` performed.** Operator commits manually.

— end of report —
