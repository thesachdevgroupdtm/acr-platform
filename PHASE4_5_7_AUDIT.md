# Phase 4.5.7 — Audit (PART A + B)

**Date:** 2026-05-09
**Mockup:** `C:\Users\Admin\Downloads\acr3.0\explore-final-mockup.png`
**Scope:** Section-by-section read of the operator's hand-drawn mockup before any code change.

---

## 1. Mockup section breakdown (top to bottom)

### Section 1 — TRENDING NOW *(full-width, NO sidebar)*
- Heading: "TRENDING NOW" with thin underline accent below.
- Layout: 5-card mosaic. **LARGE Audi card centered (≈cols 4-9, rows 1-2 of a 12-col 2-row grid)**, 4 SMALL cards flanking (Mercedes-Benz top-left + a luxury/brand top-right + BMW bottom-left + BMW bottom-right).
- Card style: full-bleed image with overlay text + category badge top-left + reading-time meta bottom-left.
- Sidebar relationship: **NONE — this section is full-width above the search bar.**
- This is a CHANGE from Phase 4.5.6 where Trending Now lived inside a 8-col main alongside the LeadForm sidebar. The mockup pulls it out.

### *(Search bar — already correct)*

### Section 2 — BRAND SERVICE *(8-col main, alongside GET A CALLBACK sidebar)*
- Heading: "BRAND SERVICE" with VIEW ALL → link on the right.
- Layout: 12-col 3-row grid (within the 8-col main column):
  - LARGE-stacked Mercedes-Benz left (cols 1-7, rows 1-3) — image-on-top + white text panel below with **uppercase title + 2-3 line description + reading time meta**.
  - 3 SMALL cards stacked right (cols 8-12, one per row):
    - BMW (image overlay)
    - Audi (image overlay)
    - "Audi Service in Delhi — Authorized Multi-Brand Workshop" (image overlay or fallback card)
- Sidebar widget alongside: **GET A CALLBACK** form (already implemented as `LeadFormWidget` — Phase 4.5.3).

### Section 3 — CITY SERVICE *(8-col main, alongside TOP PICKS sidebar)*
- Heading: "CITY SERVICE" with VIEW ALL → link on the right.
- Layout: **4×2 grid of 8 EQUALLY-SIZED cards** (NOT 1 LARGE + 4 SMALL as Phase 4.5.6 did).
  - Row 1: 4 small image-on-top cards (people meeting / training rooms / BMW / etc.) each with description text below
  - Row 2: 4 more small image-on-top cards (different topics) with descriptions
- Card style: image-on-top + small description text below ("It is a long established fact that a reader will be...").
- This is a **MAJOR CHANGE** from Phase 4.5.6's "1 LARGE + 4 SMALL 2×2" City Service layout. The mockup shows a **uniform 4×2 grid of 8 same-sized cards**.
- Sidebar widget alongside: **TOP PICKS** numbered 01-05 list (already implemented as `TopPicksWidget`).

### Section 4 — TRENDING SEARCHES *(full-width horizontal rail)*
- Heading: "TRENDING SEARCHES" with prev/next arrows top-right.
- Layout: horizontal rail of compact image cards (Mercedes-Benz, BMW, Audi, Car, …). Already implemented as `ExploreRail`.
- Sidebar relationship: NONE (full-width).

### Section 5 — BIG GRID 1 + BIG GRID 2 *(8-col main, side-by-side, alongside POPULAR BRANDS / RELATED TOPICS / CONNECT WITH US sidebar)*
- TWO sub-sections rendered side-by-side via `lg:grid-cols-2`:
  - Big Grid 1 (LEFT): heading "HEALTH & FITNESS" + featured card (image-left + text-panel-right) + 3 thumb-rows below (small thumb LEFT + title + meta RIGHT).
  - Big Grid 2 (RIGHT): heading "LIFESTYLE" + same structure.
- Sidebar widgets alongside (sticky): POPULAR BRANDS chips + RELATED TOPICS list + CONNECT WITH US social links — all 3 widgets already implemented.

### Section 6 — SERVICE GUIDE *(8-col main, sidebar continues from section 5)*
- Heading: "SERVICE GUIDE" with VIEW ALL → link on the right.
- Layout:
  - Top row: LUXURY wide card (image LEFT + title/description/reading-time text panel RIGHT). Operator's preferred wide-text-panel design — Phase 4.5.6 already implements this.
  - Bottom row: **3 cards in 3-col grid** (Car Insurance Claim Process, Emergency Roadside Assistance, Audi). Already implemented; data shortfall fills only 2 today (3rd appears as Audi-overlay padding from rails).

### Section 7 — MOST READ THIS WEEK *(full-width horizontal rail)*
- Heading: "MOST READ THIS WEEK" with prev/next arrows top-right.
- Same layout as Trending Searches rail. Already implemented.

### Section 8 — *(footer "Explore More" 3-column dark — already implemented Phase 4.5.4)*

---

## 2. Section order (operator's locked sequence)

```
PageBanner
├── ExploreFeaturedGrid                       (5-card mosaic, hero — UNCHANGED)
├── TrendingNowSection                        (full-width, NO sidebar — MOVED out of container)
├── ExploreSearch                             (full-width — UNCHANGED)
├── CategoryFilterChip                        (full-width when active — UNCHANGED)
├── Container 1 (main 8-col + sticky aside 4-col)
│   ├── main:
│   │   ├── BrandServiceSection
│   │   └── CityServiceSection                (NEW: 4×2 grid of 8 cards)
│   └── aside:
│       ├── LeadFormWidget                    (alongside Brand Service)
│       └── TopPicksWidget                    (alongside City Service via sticky scroll)
├── ExploreRail "Trending Searches"           (full-width)
├── Container 2 (main 8-col + sticky aside 4-col)
│   ├── main:
│   │   ├── BigGridDualSection
│   │   └── ServiceGuideSection
│   └── aside:
│       ├── PopularBrandsWidget
│       ├── RelatedTopicsWidget
│       └── GetSocialWidget
├── ExploreRail "Most Read This Week"         (full-width)
└── ExploreInternalLinks                       (footer 3-column)
```

---

## 3. Differences vs Phase 4.5.6 implementation

| Section | Phase 4.5.6 state | Mockup wants |
|---|---|---|
| **Trending Now** | Inside Container 1 main (alongside Lead Form sidebar) | Full-width above search bar (no sidebar) |
| **City Service** | Full-width between Section 1 and Trending Searches rail; layout = 1 LARGE + 4 SMALL (2×2) | Inside Container 1 main (alongside Top Picks sidebar); layout = **4×2 grid of 8 same-sized cards** |
| **Container 1 sidebar** | Lead + TopPicks both alongside Brand+Trending main | Lead alongside Brand Service, Top Picks alongside City Service (single sticky aside scrolls with both) |
| **Brand Service** | LARGE-stacked + 3 SMALL right ✓ | Same — UNCHANGED |
| **Big Grid Dual + sidebar 2** | ✓ (with fallback content) | Same — UNCHANGED |
| **Service Guide** | Wide top + 3-col bottom ✓ | Same — UNCHANGED |
| **Other widgets / rails / footer** | All correct | All correct |

---

## 4. Backend payload state (verified `curl /api/v1/explore`)

```
hero=5
trending_grid=7              ← TrendingNowSection uses first 5
rails.trending_searches=12
rails.most_read_week=12

categories present:
  brand-service   featured=1  items=4   ✓ (1 LARGE-stacked + 3 SMALL right — fits)
  city-service    featured=1  items=1   ⚠ 4×2 grid needs 8 cards; will pad from rails (Phase 4.5.6 follow-up pattern)
  service-guide   featured=1  items=2   ⚠ 3-col bottom needs 3; pads to 3 from rails as before
```

Categories absent: `maintenance-tips`, `comparison` — Big Grid Dual falls back to first/second spare categories per Phase 4.5.6 follow-up pattern.

### Padding strategy

For City Service's 4×2 = 8 cards: `featured` (1) + `items` (1) = 2 native cards. Need 6 more. Pad with overflow from `rails.most_read_week` → `rails.trending_searches` → `trending_grid`, skipping slugs already used elsewhere on the page. Reusing the exact `cityServicePadded` pattern from Phase 4.5.6 follow-up, just extending the target count from 4 → 7 (since the LARGE/featured one + 7 right-grid would be 8).

Wait — re-reading the mockup: there's NO LARGE in City Service. It's 8 EQUAL cards in 4×2. So `featured` and `items` get merged into one 8-card array. Padding target = 8 total cards.

---

## 5. Files to modify

```
DELETE:
  src/components/explore/ExploreCategorySection.tsx   (variant-multiplexer; logic moves to dedicated section files)
  src/components/explore/ExploreTrendingGrid.tsx      (logic moves to TrendingNowSection)

CREATE (under src/components/explore/sections/):
  TrendingNowSection.tsx        (5-card mosaic with LARGE center, items[2])
  BrandServiceSection.tsx       (1 LARGE-stacked left + 3 SMALL right — Phase 4.5.6 BrandServiceLayout extracted)
  CityServiceSection.tsx        (NEW: 4×2 grid of 8 equal cards, padded from rails)
  BigGridDualSection.tsx        (Phase 4.5.6 BigGridDual extracted; same logic + helpers)
  ServiceGuideSection.tsx       (wide top + 3-col bottom — Phase 4.5.6 ServiceGuideLayout extracted)

MODIFY:
  src/pages/ExploreEditorial.tsx          (clean assembly per §2 above)
  src/components/explore/ExploreSkeleton.tsx (mirror new layouts)
  tests/e2e/explore-editorial.spec.ts     (assertions still valid; trending count caps still ok)
  tests/e2e/explore-final-layout.spec.ts  (rename to per-section screenshot file)
  playwright.config.ts                     (regex update)

NEW SCREENSHOT TESTS:
  tests/e2e/explore-sections-screenshots.spec.ts (5 sections + 1 full-page proof)
```

Untouched per HARD CONSTRAINTS: `ExploreFeaturedGrid`, `ExploreSearch`, `CategoryFilterChip`, `ExploreCard`, `ExploreCardFallback`, `ExploreRail`, `ExploreInternalLinks`, all sidebar widgets, `PageBanner`, `SeoPageView`, `CmsPage`, all backend, all admin.

---

## 6. Risk + ambiguity log

- **City Service mockup interpretation**: The mockup's City Service section shows 8 small cards in 4×2 with image-on-top + text-below. Each card displays "It is a long established fact that a reader will be..." — looks like a placeholder. Confidence: HIGH that it's a uniform grid of small same-sized cards.
- **City Service card style**: image-on-top + text-on-white-panel-below (matching the `large-stacked` ExploreCard variant at smaller size). I'll add a new `card-grid` variant tied to the 4-col grid. Or use existing `medium` size with `aspect-[16/10]`.
- **Big Grid headings ("HEALTH & FITNESS" / "LIFESTYLE")**: These don't map to existing categories. The mockup is illustrating GENERIC editorial section names. Real category names will use whatever's in the payload (with maintenance-tips/comparison fallback chain).
- **Service Guide bottom 3rd card** ("Audi" with giant overlay): looks like a fallback-style card from a different SeoPage padded into the slot — same Phase 4.5.6 pattern.

— end of audit — proceeding with implementation —
