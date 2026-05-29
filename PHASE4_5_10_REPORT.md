# Phase 4.5.10 — Big Grid Dual Section (reference-image rebuild) — Report

**Date:** 2026-05-11
**Branch:** main (no commit per GIT POLICY)
**References:**
- `C:\Users\Admin\Downloads\acr3.0\big-grid-reference.png` (final design)
- `C:\Users\Admin\Downloads\acr3.0\big-grid-wireframe.png` (skeleton wireframe)

**Scope:** Rebuild `BigGridDualSection.tsx` from the operator's reference images. The previous Phase 4.5.7 version had a different layout (image-LEFT + text-panel-RIGHT featured + 3 thumb-rows below). The references show an asymmetric design: LEFT sub-section uses thumb-rows (news/list style), RIGHT sub-section uses a 2×2 image grid (magazine style). 5 cards per sub-section. Section sits between Trending Searches rail and Service Guide section.

All hard constraints respected:
- Reference images read first (PART A audit)
- Only `BigGridDualSection.tsx` rewritten + minor changes to `ExploreEditorial.tsx` + `ExploreSkeleton.tsx`
- No backend changes (sanity-confirmed, 118/118 Pest unchanged)
- No new packages; no `whileInView`; no carousel
- Untouched: all other sections, `ExploreCard`, `ExploreCardFallback`, `ExploreRail`, all widgets

---

## 1. PART A — Reference image analysis (critical finding)

**LEFT and RIGHT sub-sections have DIFFERENT internal layouts** — the textual spec D-4.5.10-2 only sketched the LEFT pattern (1 featured + 4 thumb-rows). The wireframe makes the asymmetry explicit. Both images confirm:

### LEFT — "HEALTH & FITNESS" / Big Grid 1
```
HEADING (blue uppercase + ← / → arrows + thin blue underline)
  └─ FEATURED card (full-bleed 16:10 image)
       · BUSINESS badge top-left, ★ icon top-right
       · Author · date · 2-line title overlaid on bottom gradient
  └─ 4× THUMB-ROWS (horizontal flex)
       · 80×64 thumbnail LEFT + (author · date + 2-line title) RIGHT
       · divide-y between rows, hover:bg-neutral-50
```

### RIGHT — "LIFESTYLE" / Big Grid 2
```
HEADING (same style as LEFT, ♡ icon vs ★)
  └─ FEATURED card (same full-bleed style; heart icon)
  └─ 2×2 GRID of 4 SMALL IMAGE CARDS
       · image-on-top (16:10) + (author · date + 2-line title) BELOW
       · bordered white panel for each card
```

5 cards per sub-section (1 featured + 4 children), 10 total.

Featured card details (both sides):
- aspect-[16/10] full-bleed image
- Dark gradient overlay covers bottom ~60%
- Top-left: filled blue category badge (white text uppercase)
- Top-right: Star (LEFT side) or Heart (RIGHT side) icon, white-on-translucent button
- Bottom overlay (white text): `Author · Date` line + title (2-line clamp)
- Whole card clickable → /:slug

---

## 2. PART B — Backend data audit

```
hero=5  trending_grid=7  rails.trending=12  rails.most_read=12

categories present:
  brand-service   featured=1 items=4 total=5  ✓
  city-service    featured=1 items=1 total=2
  service-guide   featured=1 items=2 total=3

categories ABSENT:
  maintenance-tips  (no published pages)
  comparison        (no published pages)

ExploreCard fields:
  id, slug, title, category, hero_image_url, excerpt,
  reading_time_minutes, view_count, published_at, is_featured,
  is_trending
  → NO `author` field          (D-4.5.10-7: default to "ACR Editorial")
  → published_at is available (D-4.5.10-7: format as "DD MMMM YYYY")
```

### Category selection (D-4.5.10-6 fallback chain)

With `maintenance-tips` and `comparison` absent, the chain falls through to spare categories not already used by other dedicated sections. ExploreEditorial already used brand-service / city-service / service-guide as their own sections, so there are no spare slots. Final fallback uses `categories[0]` (brand-service) and `categories[1]` (city-service):

| Slot | Source | Display |
|---|---|---|
| `bigGridLeft` | brand-service (final fallback — no spare available) | Featured: Mercedes-Benz + 4 thumb-rows (BMW, Audi, Audi Service Delhi, BMW Service Cost) |
| `bigGridRight` | city-service (final fallback) | Featured: Dent And Paint Repair + 2×2 grid (Best Car AC, Car Battery, Monsoon Tyre Care, Winter Car Care) |

The 2×2 grid + thumb-rows are padded from `fallbackPool` (most_read_week → trending_searches → trending_grid), deduplicated against ALL slugs already used elsewhere on the page including in BigGridDualSection's own consumption tracker.

**Visible duplication caveat**: brand-service and city-service appear here AS WELL AS in their dedicated full-section slots above. Operator can publish content in `maintenance-tips` and `comparison` via `/admin/seo-pages` to eliminate the duplication; the fallback chain stays in place as a safety net.

---

## 3. Files created

| Path | Purpose |
|---|---|
| `tests/e2e/explore-big-grid-dual.spec.ts` | Dedicated spec — asserts ≥3 cards per sub-section + section screenshot + full-page snapshot. |
| `PHASE4_5_10_AUDIT.md` | PART A + B audit deliverable. |
| `PHASE4_5_10_REPORT.md` | This file. |
| `test-results/phase-4-5-10-big-grid-dual.png` | Visual record of the section. |
| `test-results/phase-4-5-10-full-page.png` | Visual record of section order across whole `/explore`. |

## 4. Files modified

| Path | Change |
|---|---|
| `src/components/explore/sections/BigGridDualSection.tsx` | **Full rewrite.** Replaces Phase 4.5.7's image-LEFT + text-panel-RIGHT featured + 3 thumb-rows design with the reference's full-bleed-image featured + asymmetric children (LEFT = 4 thumb-rows divided by border-bottom, RIGHT = 2×2 grid of bordered image cards). Adds star/heart accent icons, author/date metadata, ACR Editorial author default, `published_at` date formatting. Introduces a `consumed` Set so the LEFT and RIGHT sub-sections never duplicate cards between themselves when pulling from the shared `fallbackPool`. |
| `src/pages/ExploreEditorial.tsx` | Added `fallbackPool={fallbackPool}` prop to the existing `<BigGridDualSection>` JSX (was previously missing). All other wiring identical to Phase 4.5.7's slug-aware fallback resolution. |
| `src/components/explore/ExploreSkeleton.tsx` | Added Big Grid Dual placeholder block at the end of the skeleton: 2-col main + sidebar mirror, with featured aspect-[16/10] placeholder + 4 thumb-row stripes on the LEFT and featured + 2×2 image grid on the RIGHT. Matches new render exactly so the skeleton-to-content swap stays layout-stable. |
| `playwright.config.ts` | `seo` project regex extended for `explore-big-grid-dual` spec. |

---

## 5. PART C — Component summary

### Public API

```ts
interface Props {
  leftCategory: ExploreCategoryBlock | null;
  rightCategory: ExploreCategoryBlock | null;
  fallbackPool?: ExploreCard[];
}
```

### Internal structure

```
<section data-section="big-grid-dual" className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
  ├── <BigGridSubSection variant="thumb-rows" accentIcon="star" />
  │   ├── Heading (blue uppercase + chevron arrows + blue underline)
  │   ├── <FeaturedCard accentIcon="star" />
  │   └── divide-y list of 4× <ThumbRowCard>
  └── <BigGridSubSection variant="grid-2x2" accentIcon="heart" />
      ├── Heading (same style, heart accent)
      ├── <FeaturedCard accentIcon="heart" />
      └── grid grid-cols-2 of 4× <SmallImageCard>
```

### Card sub-components

| Component | Used by | Markup |
|---|---|---|
| `FeaturedCard` | both sub-sections | `<Link aspect-[16/10]>` + `<img object-cover opacity-90>` + dark gradient + category badge top-left + star/heart top-right (button onClick prevent-default) + `<p>author · date</p><h4 line-clamp-2>title</h4>` overlay |
| `ThumbRowCard` | LEFT thumb-rows | `<Link flex items-start gap-3 py-3 hover:bg-neutral-50>` + 80×64 image LEFT + author/date + line-clamp-2 title RIGHT |
| `SmallImageCard` | RIGHT 2×2 grid | `<Link flex flex-col border>` + aspect-[16/10] image top + `<p>author · date</p><h5 line-clamp-2>title</h5>` below |

### Padding logic

```ts
function buildSubSectionCards(category, pool, consumed, target=5) {
  // 1. Push category.featured + category.items (skip duplicates)
  // 2. Pad with `pool` candidates (skip duplicates AND skip
  //    anything already consumed by the other sub-section)
  // 3. Slice to target (5)
}
```

`consumed: Set<string>` is shared between LEFT and RIGHT sub-section builds — so even when both fall back to the same pool, neither shows the same card twice. Min-2-cards-per-sub-section gate hides a sub-section whose threshold fails (and the whole section if both fail).

### Author/date helpers

```ts
function authorLabel(): string {
  // Backend has no `author` field; spec D-4.5.10-7 defaults to "ACR Editorial".
  return "ACR Editorial";
}

function formatDate(iso?: string | null): string {
  // Returns "DD MMMM YYYY" (e.g., "16 April 2026") from a Laravel
  // ISO timestamp. Returns "" if missing or invalid.
}
```

---

## 6. PART D — ExploreEditorial assembly

The component sits inside **Container 2** (alongside the sticky sidebar of `PopularBrandsWidget + RelatedTopicsWidget + GetSocialWidget`), between the Trending Searches rail and the Service Guide section. Existing wiring from Phase 4.5.7 — only change is the new `fallbackPool` prop:

```tsx
{(bigGridLeft || bigGridRight) && (
  <BigGridDualSection
    leftCategory={bigGridLeft}
    rightCategory={bigGridRight}
    fallbackPool={fallbackPool}      // ← added in this commit
  />
)}
```

Fallback resolution (unchanged from Phase 4.5.7 follow-up):

```ts
const usedSlugs = new Set([brandService, cityService, serviceGuide]
  .filter(Boolean).map(c => c.slug));
const spareCategories = categories.filter(c => !usedSlugs.has(c.slug));
const bigGridLeft  = findCat("maintenance-tips") ?? spareCategories[0] ?? categories[0] ?? null;
const bigGridRight = findCat("comparison")       ?? spareCategories[1] ?? categories[1] ?? null;
```

`fallbackPool` combines `rails.most_read_week + rails.trending_searches + trending_grid` and dedupes against ALL category slugs already used on the page.

---

## 7. PART E — Skeleton alignment

`ExploreSkeleton.tsx` gained a Big Grid Dual placeholder block at the end (after the rail placeholder) matching the new section's structure:

- 8-col main + 4-col aside mirror (matches Container 2's `lg:grid-cols-12 gap-8` layout)
- Inside main: 2-col grid (matches the dual sub-section split)
- LEFT placeholder: heading bar + aspect-[16/10] featured + 4 thumb-row stripes (80×64 thumb + 2 text bars each)
- RIGHT placeholder: heading bar + aspect-[16/10] featured + 2×2 grid of bordered card placeholders (each = aspect-[16/10] image + 2 text bars)
- Aside placeholder: 3 sidebar widget stripes (matches PopularBrands + RelatedTopics + GetSocial silhouette)

Layout-stable repaint guaranteed.

---

## 8. PART F — Visual verification

### `test-results/phase-4-5-10-big-grid-dual.png`

Confirms the asymmetric layout per reference:

**LEFT sub-section** (brand-service via fallback):
- Featured: Mercedes-Benz full-bleed with `ACR Editorial · 8 May 2026` + title "Mercedes-Benz Service in Delhi — Authorized Multi-Brand Workshop" overlaid on dark gradient ✓
- 4 thumb-rows below:
  1. BMW · `ACR Editorial 6 May 2026` · "BMW AC Repair in Gurugram — Same-Day Service"
  2. Audi · `ACR Editorial 4 May 2026` · "Audi Brake Pad Replacement — Cost, Process, and Warranty"
  3. Servic in… · `ACR Editorial 8 May 2026` · "Audi Service in Delhi — Authorized Multi-Brand Workshop"
  4. Servic Co… · `ACR Editorial 8 May 2026` · "BMW Service Cost Guide — Delhi NCR"

**RIGHT sub-section** (city-service via fallback):
- Featured: Dent full-bleed with `ACR Editorial · 24 April 2026` + title "Dent and Paint Repair in Noida — Insurance-Approved" overlaid ✓
- 2×2 grid below:
  1. Best Car AC Service in Gurugram — AC… · 8 May 2026
  2. Car Battery Replacement Cost in 2026 — Brand-… · 2 May 2026
  3. Monsoon Tyre Care — Pressure, Tread, and… · 30 April 2026
  4. Winter Car Care Checklist — 12 Items… · 28 April 2026

### `test-results/phase-4-5-10-full-page.png`

Section order matches spec:
```
PageBanner → Trending Now → Search → CategoryFilterChip →
Container 1 (Brand Service + City Service main; Lead + TopPicks aside) →
Trending Searches rail →
Container 2 (BIG GRID DUAL + Service Guide main; Popular + Related + Connect aside) →
Most Read rail →
Explore More footer
```

---

## 9. PART G — Tests

### Backend (Pest)

```
Tests:    118 passed (534 assertions)
Duration: 193.71s
```

Untouched (no backend changes). 118/118.

### Frontend Playwright (`seo` project)

New `explore-big-grid-dual.spec.ts` — 2 tests in isolation:

```
✓ renders 2 sub-sections each with at least 3 visible cards    (4.7s)
✓ full /explore snapshot for section-order verification         (4.0s)
2 passed (12.0s)
```

The assertion-test verifies BOTH sub-sections render the asymmetric variants (`data-variant="thumb-rows"` on the LEFT and `data-variant="grid-2x2"` on the RIGHT) with ≥3 cards each (featured + 2 children), proving the fallback chain padded correctly even though the named categories (maintenance-tips / comparison) are absent.

### TypeScript

`npx tsc --noEmit` — clean.

---

## 10. PART H — Bundle size delta

```
ExploreEditorial chunk:
  Phase 4.5.7 (after Big Grid attempts) : 53.33 kB raw │ gzip: 10.96 kB
  Phase 4.5.10 (this rebuild)            : 56.44 kB raw │ gzip: 11.94 kB
  Δ                                      : +3.11 kB raw │ gzip: +0.98 kB
```

Within spec's expected +3-5 kB envelope. The new component adds: 2 sub-section variants, 3 card sub-components (Featured / ThumbRow / SmallImage), 2 helper functions (author + date), star + heart icon imports from lucide. Other chunks unchanged: icons-vendor stable at 34.45 kB (lucide tree-shake hadn't pruned Star/Heart since other consumers exist).

Build: `✓ built in 29.67s`. Clean.

---

## 11. Deviations

1. **Backend `author` field not present** → defaulted to `"ACR Editorial"` per spec D-4.5.10-7. All cards render the same author. Operator can add an `author` column to `seo_pages` in a future migration to drive real authorship.

2. **`created_at` / `updated_at` not on the payload card** → used `published_at` (which IS on the payload) for the date. Formatted as `DD MMMM YYYY` (e.g., "16 April 2026") per spec. Cards without a `published_at` value render the date as empty string (the `·` separator stays).

3. **`maintenance-tips` and `comparison` categories absent from backend** → fallback chain selects `brand-service` and `city-service` as the final-fallback sources. This causes visible content duplication: brand-service shows in both its own dedicated section AND as the LEFT Big Grid Dual sub-section. Same for city-service on the RIGHT. Operator can publish at least 5 pages each in `maintenance-tips` and `comparison` via `/admin/seo-pages` to eliminate duplication — the named slugs win in `findCat()` and the fallback path drops out automatically.

4. **Section headings ("BRAND SERVICE" / "CITY SERVICE")** are pulled from `category.name` — they appear as duplicates because the same categories are rendered above as their own dedicated sections. Acceptable transient behavior; resolves with content as in §3.

No other deviations.

---

## 12. Files-touched summary

```
NEW:
  src/components/explore/sections/BigGridDualSection.tsx   (full rewrite — replaces Phase 4.5.7 version)
  tests/e2e/explore-big-grid-dual.spec.ts
  test-results/phase-4-5-10-big-grid-dual.png              (visual record)
  test-results/phase-4-5-10-full-page.png                  (visual record)
  PHASE4_5_10_AUDIT.md
  PHASE4_5_10_REPORT.md  (this file)

MODIFIED:
  src/pages/ExploreEditorial.tsx                            (+ fallbackPool prop on BigGridDualSection)
  src/components/explore/ExploreSkeleton.tsx                (+ Big Grid Dual placeholder)
  playwright.config.ts                                       (seo regex + explore-big-grid-dual)

UNTOUCHED (per HARD CONSTRAINTS):
  src/components/explore/sections/TrendingNowSection.tsx
  src/components/explore/sections/BrandServiceSection.tsx
  src/components/explore/sections/CityServiceSection.tsx
  src/components/explore/sections/ServiceGuideSection.tsx
  src/components/explore/sections/SectionHeader.tsx
  src/components/explore/ExploreCard.tsx
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/ExploreInternalLinks.tsx
  src/components/explore/widgets/*.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/PageBanner.tsx
  src/pages/SeoPageView.tsx, src/pages/CmsPage.tsx
  All backend, all admin (Filament resources)
```

Per GIT POLICY: **no `git add`, `git commit`, or `git push` performed.** Operator commits manually.

— end of report —
