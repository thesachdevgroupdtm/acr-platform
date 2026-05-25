# Phase 4.5.10 — Audit (PART A + B)

**Date:** 2026-05-11
**References:**
- `C:\Users\Admin\Downloads\acr3.0\big-grid-reference.png` (final design with photos)
- `C:\Users\Admin\Downloads\acr3.0\big-grid-wireframe.png` (skeleton wireframe)

---

## 1. PART A — Reference image analysis

### Critical finding: LEFT and RIGHT sub-sections have DIFFERENT internal layouts

The wireframe makes this asymmetry crystal clear (the textual spec D-4.5.10-2 sketched only the LEFT sub-section's pattern, but the wireframe shows the RIGHT side renders differently). Final-design photo confirms it.

### LEFT sub-section ("HEALTH & FITNESS" / Big Grid 1)

```
┌────────────────────────────────────────┐
│ HEALTH & FITNESS              ← / →    │  ← blue uppercase + arrows
├────────────────────────────────────────┤
│ ╔══════════════════════════════════╗  │
│ ║  [BUSINESS]               [★]    ║  │  ← featured: full-bleed image
│ ║                                  ║  │     · category badge top-left
│ ║                                  ║  │     · star/bookmark icon top-right
│ ║                                  ║  │     · author + date bottom-left
│ ║  Bathin · Yesterday 03:52 pm     ║  │     · title overlaid bottom
│ ║  It is a long established fact   ║  │
│ ║  that a reader will be ...       ║  │
│ ╚══════════════════════════════════╝  │
├────────────────────────────────────────┤
│ ┌────┐ Maclean John  16 April 2017    │
│ │img │ Established fact that a reader  │  ← thumb row (horizontal flex)
│ └────┘ will be distracted by readable  │
├────────────────────────────────────────┤
│ ┌────┐ Ziminiar  16 April 2017         │
│ │img │ Long established fact ...       │
│ └────┘                                  │
├────────────────────────────────────────┤
│ ┌────┐ Vanth  16 April 2017            │
│ │img │ Long established fact ...       │
│ └────┘                                  │
├────────────────────────────────────────┤
│ ┌────┐ Vanth  16 April 2017            │
│ │img │ Long established fact ...       │
│ └────┘                                  │
└────────────────────────────────────────┘
```

5 cards total = 1 featured + 4 stacked thumb-rows.
Thumb row: ~80×60 image LEFT (rounded) + author/date line + 2-line title RIGHT.
Subtle border-bottom divider between rows.

### RIGHT sub-section ("LIFESTYLE" / Big Grid 2)

```
┌────────────────────────────────────────┐
│ LIFESTYLE                    ← / →     │
├────────────────────────────────────────┤
│ ╔══════════════════════════════════╗  │
│ ║  [FASHION]                [♡]    ║  │  ← featured (same style as left)
│ ║                                  ║  │     · heart icon instead of star
│ ║                                  ║  │
│ ║  Astaroth · Yesterday 03:52 pm   ║  │
│ ║  Siriya attaced by a long ...    ║  │
│ ╚══════════════════════════════════╝  │
├────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────┐    │
│ │ img card     │  │ img card     │    │  ← 2×2 grid of 4 small image cards
│ │              │  │              │    │     each: image + author/date + title BELOW
│ │ Astaroth     │  │ Astaroth     │    │
│ │ 17 Apr 2017  │  │ 17 Apr 2017  │    │
│ │ It is a long │  │ It is a long │    │
│ │ ...          │  │ ...          │    │
│ └──────────────┘  └──────────────┘    │
├────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────┐    │
│ │ img card     │  │ img card     │    │
│ │              │  │              │    │
│ │ Astaroth     │  │ Astaroth     │    │
│ │ 17 Apr 2017  │  │ 17 Apr 2017  │    │
│ │ It is a long │  │ It is a long │    │
│ └──────────────┘  └──────────────┘    │
└────────────────────────────────────────┘
```

5 cards total = 1 featured + 4 small image cards in a 2×2 grid.
Small image card: image on top + author/date + title BELOW (not horizontal thumb-row).

### Featured-card details (both sub-sections)

- Aspect ratio: ~16/10 (visually shorter than 4/3, slightly taller than 16/9)
- Full-bleed image with dark gradient overlay covering bottom 50-60%
- Category badge top-left (white tag with text on translucent backdrop in wireframe, **filled blue with white text** per ACR convention)
- Bookmark/star/heart icon top-right (the wireframe shows ★ on left and ♡ on right — interchangeable; spec D-4.5.10-3 allows either)
- Bottom overlay (white text on dark gradient):
  - Line 1: `Author · Date` (small, light gray)
  - Line 2: Title (large, bold, 2-line clamp)
- Whole card clickable → /:slug

### Section heading

- Title LEFT: blue uppercase text + thin blue underline below
- RIGHT: prev/next arrows (chevron icons — visual only per D-4.5.10-5, no functionality)
- Bottom border: 1px solid blue/20

### Spacing

- Inter-card gap inside each sub-section: ~12px
- Gap between left and right sub-sections: 32px (per D-4.5.10-1 `lg:gap-12` = 48px on desktop)
- Sub-section bottom margin: 32px

### Mobile

- Sub-sections stack vertically
- Featured card aspect stays 16/10
- Thumb-rows / 2×2 grid: no major change beyond available width

---

## 2. PART B — Backend data audit

`curl /api/v1/explore`:

```
categories present:
  brand-service   featured=1 items=4 total=5  ✓ enough for either sub-section
  city-service    featured=1 items=1 total=2  ⚠ minimum threshold; partial render only
  service-guide   featured=1 items=2 total=3  ⚠ thin; partial render

trending_grid    = 7   (fallback pool source)
rails.most_read  = 12  (fallback pool source)
rails.trending   = 12  (fallback pool source)

sample hero card keys:
  ['category', 'excerpt', 'hero_image_url', 'id', 'is_featured',
   'is_trending', 'published_at', 'reading_time_minutes', 'slug',
   'title', 'view_count']
```

### Findings

- **No `author` field on cards** → use D-4.5.10-7 default: **"ACR Editorial"**
- **No `created_at` / `updated_at`** but **`published_at` is present** → use this for the date display, format as `DD MMMM YYYY`
- **`maintenance-tips` and `comparison` categories ABSENT** from payload (no published SeoPages in those categories) → fallback chain per spec D-4.5.10-6

### Category selection plan

```
leftCategory  = findCat('maintenance-tips')           ?? null
                ?? first spare category not already used elsewhere
                ?? categories[0] (final fallback, may duplicate)

rightCategory = findCat('comparison')                 ?? null
                ?? next spare category
                ?? categories[1]
```

With current payload (brand-service, city-service, service-guide all used by other dedicated sections in ExploreEditorial):
- `leftCategory` → falls through to brand-service (categories[0]) — duplicates Brand Service section visually but layout differs
- `rightCategory` → falls through to city-service (categories[1])

Operator can publish `maintenance-tips` + `comparison` content via `/admin/seo-pages` to surface non-duplicating content. The fallback exists so the section RENDERS today (the explicit problem the operator flagged: "currently MISSING OR rendering as wrong component").

### Fallback pool for padding

Each sub-section needs 5 cards (1 featured + 4 children). Backend gives 2-5. Pad from:
```
[...rails.most_read_week, ...rails.trending_searches, ...trending_grid]
```
deduped against ALL slugs already used elsewhere on the page (Trending mosaic + Brand Service + City Service + Service Guide + both Big Grid Dual sub-sections' featured/items).

---

## 3. Files to modify

```
REWRITE (replaces Phase 4.5.7 version which had a different design):
  src/components/explore/sections/BigGridDualSection.tsx
    + Asymmetric layouts (LEFT = thumb rows, RIGHT = 2x2 image grid)
    + Featured card with full-bleed image + author/date/title overlay
    + ACR Editorial default author + published_at date formatting

MODIFY:
  src/pages/ExploreEditorial.tsx
    + Use existing imports; rewire fallback resolution; pass fallbackPool prop
    + (Already imports BigGridDualSection — just changes data flow)

  src/components/explore/ExploreSkeleton.tsx
    + Add Big Grid Dual placeholder (2-col grid with featured + thumb/2x2 placeholders)

  tests/e2e/explore-sections-screenshots.spec.ts
    + Update Big Grid Dual selector if data-section attribute changes

NEW:
  tests/e2e/explore-big-grid-dual.spec.ts
    + Dedicated screenshot + ≥3 cards per sub-section assertion

  PHASE4_5_10_AUDIT.md  (this doc)
  PHASE4_5_10_REPORT.md (after PART H)
```

Untouched: all other sections, ExploreCard, ExploreCardFallback, ExploreRail, all widgets, backend, admin.

— end of audit — proceeding with implementation —
