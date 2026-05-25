# Phase 4.5.4 — Audit (PART A)

**Date:** 2026-05-09
**Scope:** Read-only audit before surgical polish on category-section
dead-space + footer revamp.

---

## 1. ExploreCategorySection variants — current logic

3 variants, parent rotates via `idx % 3`:

| Variant | Layout | Renders |
|---|---|---|
| **A** | `grid grid-cols-1 lg:grid-cols-[1.5fr_1fr] gap-6` | 1 LARGE feature (4:3) on left + 3 ListItemCards (horizontal small) stacked on right |
| **B** | `grid grid-cols-1 md:grid-cols-2 gap-6` (each col: feature + 2 list items) | 50/50 split — left and right both have a LARGE on top + 2 ListItemCards |
| **C** | `space-y-4` (wide feature on top + 3 small in row) | 1 wide horizontal feature + 3 SmallCard image-on-top below |

## 2. Variant rotation in ExploreEditorial

```ts
// src/pages/ExploreEditorial.tsx
const VARIANTS: Array<"A" | "B" | "C"> = ["A", "B", "C"];

// Section 1
section1Cats.map((cat, idx) => <ExploreCategorySection variant={VARIANTS[idx % 3]} … />);
// idx 0 → A,  idx 1 → B

// Section 2
section2Cats.map((cat, idx) => <ExploreCategorySection variant={VARIANTS[(idx + 2) % 3]} … />);
// idx 0 → C,  idx 1 → A,  idx 2 → B
```

So Variant A appears in BOTH sections (Section 1 idx 0; Section 2 idx 1).

## 3. Dead-space variant identified

**Variant A is the culprit.** With current `[1.5fr 1fr]` template + 4:3 LARGE on left + 3 thin horizontal ListItemCards on right, the LARGE's tall 4:3 box doesn't sync with the 3 narrow stacked items — the right column ends well above the LARGE's bottom edge → empty white space below the right column.

PART B converts Variant A to the new 12-col 3-row spec (Card 1 cols 1-7 rows 1-3 + 3 SMALL cards filling cols 8-13 row-by-row). Each grid cell now extends to the LARGE's height, eliminating dead space.

**Variants B and C: NO dead-space pattern.** Both use full-width grid layouts that fill row by design. Both LEFT UNTOUCHED.

## 4. ExploreInternalLinks — current footer

```
section bg-neutral-900 text-white py-12 md:py-16
  div site-container
    h2 "Explore More" (with primary "More" accent)
    p subhead
    div grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-10
      ┌─ Categories column (1fr ≈ 33%)
      │   h3 "By Category"
      │   <ul space-y-2> dot + Link per category
      └─ Popular column (2fr ≈ 67%)
          h3 "Popular Now"
          <div flex flex-wrap gap-2> chip Links
```

2 columns. `popularSlugs` come from `rails.trending_searches.slice(0, 12).map(c => c.slug)` (passed by ExploreEditorial).

## 5. seo_page_categories — slug + icon_name data

```
brand-service       Brand Service          icon=tag
city-service        City Service           icon=map-pin
ac-repair           AC Repair              icon=sun
service-guide       Service Guide          icon=wrench-screwdriver
battery             Battery                icon=bolt
maintenance-tips    Maintenance Tips       icon=shield-check
insurance-guides    Insurance Guides       icon=shield-check
cost-guide          Cost Guide             icon=currency-rupee
comparison          Comparison             icon=arrows-right-left
news                News                   icon=newspaper
city-services       City Services          icon=map-pin
brand-services      Brand Services         icon=tag
denting-painting    Denting & Painting     icon=paint-brush
luxury-cars         Luxury Cars            icon=sparkles
service-cost        Service Cost           icon=currency-rupee
```

15 rows. `icon_name` populated for every row using heroicon-style names. The `ExploreCategoryBlock` TypeScript type already exposes `icon_name: string | null` (verified `src/lib/api.ts:828`), so the new footer can read it directly.

Some duplicates exist in the data (`city-service` vs `city-services`, `brand-service` vs `brand-services`, `cost-guide` vs `service-cost`) — that's an existing data-quality issue out of scope here.

## 6. Heroicon library availability

**`@heroicons/react` is NOT installed.** Verified via `package.json` — only `lucide-react` (^0.546.0) is in dependencies.

HARD CONSTRAINTS forbid installing new packages. **Will use lucide-react** (already in project) for all icons. The existing `ExploreCardFallback.tsx` already maps the heroicon-style `icon_name` strings to lucide components — same map will be reused / mirrored:

| icon_name (DB) | lucide-react component |
|---|---|
| `tag` | Tag |
| `map-pin` | MapPin |
| `wrench-screwdriver` / `wrench` | Wrench |
| `shield-check` | ShieldCheck |
| `currency-rupee` | IndianRupee |
| `arrows-right-left` | ArrowLeftRight |
| `newspaper` | Newspaper |
| `paint-brush` | Paintbrush |
| `sparkles` | Sparkles |
| `bolt` | Bolt |
| `sun` | Sun |
| `book-open` | BookOpen |
| `light-bulb` | Lightbulb |
| `scale` | Scale |
| `exclamation-triangle` | AlertTriangle |
| (default) | BookOpen |

Documented as **deviation §1** in the report: spec said `@heroicons/react/24/outline`; we used lucide-react instead because installing new packages is forbidden.

## 7. Files that will change in Phase 4.5.4

```
MODIFY:
  src/components/explore/ExploreCategorySection.tsx   (Variant A only — full rewrite)
  src/components/explore/ExploreInternalLinks.tsx     (full rewrite — 3-col footer)

CREATE:
  tests/e2e/explore-footer-revamp.spec.ts             (2 Playwright tests)
  PHASE4_5_4_AUDIT.md  (this doc)
  PHASE4_5_4_REPORT.md (after PART F)

playwright.config.ts                                  (add new spec to seo project regex)

DO NOT TOUCH (per HARD CONSTRAINTS):
  src/components/explore/ExploreFeaturedGrid.tsx
  src/components/explore/ExploreCard.tsx
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/ExploreTrendingGrid.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/ExploreSkeleton.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/explore/widgets/*.tsx
  src/components/PageBanner.tsx
  src/pages/SeoPageView.tsx, src/pages/CmsPage.tsx
  All backend, all admin
```

— end of audit —
