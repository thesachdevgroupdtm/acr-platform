# Phase 4.5.5 — Audit (PART A)

**Date:** 2026-05-09
**Scope:** Read-only audit before the final dead-space fix on
`ExploreTrendingGrid` and a sweep of the remaining explore
components.

---

## 1. ExploreTrendingGrid — current state

```ts
// src/components/explore/ExploreTrendingGrid.tsx
export const TRENDING_GRID_CLASS =
  "grid grid-cols-1 md:grid-cols-3 md:auto-rows-[200px] gap-4";

const SIZES = ["LARGE","SMALL","SMALL","WIDE","SMALL","SMALL","LARGE","WIDE"];

const SIZE_CLASSES = {
  LARGE: "md:col-span-2 md:row-span-2",
  SMALL: "md:col-span-1 md:row-span-1",
  WIDE:  "md:col-span-3 md:row-span-1",
};

// Pad / truncate to exactly 8 to keep the pattern stable.
const eight = items.slice(0, 8);
```

**8-card 4-size repeating pattern:**

```
[LARGE 2x2 ][SMALL][SMALL]
[WIDE-HORIZONTAL          ]
[SMALL][SMALL][LARGE 2x2 ]
[WIDE-HORIZONTAL          ]
```

Each card is rendered by an in-file `TrendingCard` component with two
distinct designs: a horizontal image-left layout for `WIDE`, and an
image-on-top + content-overlay layout for `LARGE`/`SMALL`.

**Why this produces the dead-space pattern operator flagged:**

- Backend currently returns **7** trending items (`curl /api/v1/explore | jq '.trending_grid | length'`). The pattern needs 8.
- Position 7 (the second `WIDE`) never renders → bottom row is missing.
- Position 6 (the second `LARGE 2x2`) renders only the first half of its 2-row span without its sibling `WIDE` underneath → visual hole below the `LARGE`.
- Even with 8 items, the pattern is visually busy: two horizontal `WIDE`s (a different card design) interleaved with stack/overlay cards make the grid feel disjointed.

**Decision:** rewrite to the 4-card editorial layout (1 LARGE cols 1-9 rows 1-3 + 3 SMALL stacked cols 9-13). Same shape as the Phase 4.5.4 ExploreCategorySection Variant A fix, just with `col-end-9` instead of `col-end-8` for a slightly wider LARGE.

### Usage of `TRENDING_GRID_CLASS`

`ExploreSkeleton.tsx:9, 53` imports the constant for its trending placeholder. The skeleton renders 8 placeholder boxes mirroring the old pattern. Both files need updating in lock-step or the skeleton-to-content swap will layout-shift.

---

## 2. ExploreEditorial — Trending invocation

```jsx
// src/pages/ExploreEditorial.tsx
{trending_grid.length > 0 && (
  <div data-section="trending">
    <div className="flex items-end justify-between mb-6 pb-3 border-b-2 border-primary">
      <div>
        <h2 className="text-xl md:text-2xl lg:text-3xl font-black uppercase tracking-tighter text-neutral-900">
          Trending <span className="text-primary">Now</span>
        </h2>
        <p className="text-xs text-neutral-500 mt-1">
          Most-read this week.
        </p>
      </div>
    </div>
    <ExploreTrendingGrid items={trending_grid} />
  </div>
)}
```

The header markup already exists in ExploreEditorial — the rewrite of
ExploreTrendingGrid stays a pure grid component (no header inside).
Spec PART B step 5 sketched a full `<section>` with internal heading;
matching the existing convention is cleaner — header stays in
ExploreEditorial, the new component renders only the grid.

---

## 3. Other components — dead-space sweep

| Component | Status | Reason |
|---|---|---|
| `ExploreCategorySection` (Variant A/B/C) | ✓ Already fixed in Phase 4.5.4 | Variant A → 12-col 3-row mosaic; B + C have no dead-space |
| `ExploreFeaturedGrid` | ✓ Fixed in Phase 4.5.2 | 5-card mosaic with center LARGE + 4 SMALL flanking |
| `ExploreInternalLinks` | ✓ Fixed in Phase 4.5.4 | 3-column footer (Browse + Popular + Why-ACR) |
| `ExploreRail` | ✓ No dead-space risk | Horizontal scroll rail, naturally fills row width with `flex-shrink-0` cards |
| `ExploreSearch` / `CategoryFilterChip` / sidebar widgets | n/a | Not grid-card layouts |

**Only `ExploreTrendingGrid` remains.** No other section needs the
4-card editorial conversion.

---

## 4. Reuse vs custom-card decision

The spec D-4.5.5-3 says "Use existing ExploreCard component (size='large' for Card 1, size='small' for Cards 2-4)". HARD CONSTRAINTS forbid modifying `ExploreCard.tsx`. The ExploreCard's stack-layout Link applies `aspect-square` for `size="small"` — that fights against `h-full` from the grid cell.

Following the same pattern Phase 4.5.4 used for `RightSmallCard` in
`ExploreCategorySection`: write a small inline `<TrendingLargeCard>` +
`<TrendingSmallCard>` in `ExploreTrendingGrid.tsx` (same file, no new
public components) that fill the grid cell with `h-full w-full` and
the existing image-on-top + content-overlay design. This:
- Honours D-4.5.5-3 in spirit (size='large' for first, 'small' for the
  3 stacked) without touching ExploreCard
- Keeps the existing visual language (full-bleed image + dark gradient
  + category badge + title overlay + reading-time meta) consistent
  with the Phase 4.5.4 RightSmallCard
- Avoids `ExploreCard`'s baked-in aspect ratio fighting with the grid

Both inline cards fall through to `<ExploreCardFallback>` when an
item lacks `hero_image_url`.

---

## 5. Files that will change in Phase 4.5.5

```
MODIFY:
  src/components/explore/ExploreTrendingGrid.tsx   (full rewrite — 4-card editorial)
  src/components/explore/ExploreSkeleton.tsx       (Trending placeholder mirrors new layout)
  tests/e2e/explore-editorial.spec.ts              (assert exactly 4 trending cards visible)
  playwright.config.ts                             (add explore-trending-screenshot spec)

CREATE:
  tests/e2e/explore-trending-screenshot.spec.ts    (visual record — saves PNG, no asserts)
  PHASE4_5_5_AUDIT.md  (this doc)
  PHASE4_5_5_REPORT.md (after PART F)

DO NOT TOUCH (per HARD CONSTRAINTS):
  src/components/explore/ExploreCategorySection.tsx (4.5.4-fixed)
  src/components/explore/ExploreInternalLinks.tsx   (4.5.4-fixed)
  src/components/explore/ExploreFeaturedGrid.tsx
  src/components/explore/ExploreCard.tsx
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/explore/widgets/*.tsx
  src/components/PageBanner.tsx
  src/pages/SeoPageView.tsx, src/pages/CmsPage.tsx
  All backend, all admin
```

### Code being deleted

The old `TRENDING_GRID_CLASS` export, the `SIZES` / `SIZE_CLASSES` maps,
the entire `WIDE`-card branch in `TrendingCard`, and the `8`-item
truncation logic. Skeleton's old 8-box mirror replaced with the
4-box mirror.

— end of audit —
