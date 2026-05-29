# Phase 4.5.1 — ExploreEditorial Audit (PART A)

**Date:** 2026-05-09
**Scope:** Read-only audit of every clickable element + dead-link
manifest before surgical correction.

---

## 1. File inventory (current `src/components/explore/`)

```
ExploreCategorySection.tsx   — kept, "View All" link to be wired
ExploreHero.tsx              — DELETE (carousel)
ExploreInternalLinks.tsx     — kept, links resolve correctly
ExploreRail.tsx              — kept, working as-is
ExploreSearch.tsx            — kept, client-side filter
ExploreSkeleton.tsx          — minor tweak (no hero block)
ExploreTrendingGrid.tsx      — kept (folds in fallback via shared card)
```

Plus `src/pages/ExploreEditorial.tsx` — restructured this commit.

---

## 2. seo_page_categories `icon_name` column

Confirmed: column added by Phase 4.5 enhance migration. 9 default
rows seeded with heroicon names (`tag`, `map-pin`,
`wrench-screwdriver`, `shield-check`, `currency-rupee`,
`arrows-right-left`, `newspaper`, `paint-brush`, `sparkles`).

---

## 3. Dead-link manifest (current /explore clickable elements)

| File | Element | Target | Status | Action |
|---|---|---|---|---|
| ExploreEditorial.tsx | Breadcrumb "Home" | `/` | WORKS | keep |
| ExploreEditorial.tsx | "See all →" (Trending header) | `/explore?sort=trending` | BROKEN — sort param ignored frontend | REMOVE button |
| ExploreEditorial.tsx | "See all →" (By Brand header) | calls onPillClick("Brand Service") | WORKS | keep but rewire to `?category=` |
| ExploreCategorySection.tsx | "View All →" per category | `/explore?category={slug}` | NEEDS-NEW-ROUTE | wire via D-4.5.1-2 |
| ExploreCategorySection.tsx | All `/${card.slug}` links (4 instances) | /:slug | WORKS | keep |
| ExploreHero.tsx | All carousel links + dots + buttons | n/a | DELETED | n/a (file removed) |
| ExploreInternalLinks.tsx | Category chip → `/explore?category={slug}` | NEEDS-NEW-ROUTE | wire via D-4.5.1-2 |
| ExploreInternalLinks.tsx | Popular page chip → `/${slug}` | WORKS | keep |
| ExploreRail.tsx | Arrow scroll buttons | scroll only | WORKS | keep |
| ExploreRail.tsx | Card Link `/${card.slug}` | /:slug | WORKS | keep |
| ExploreSearch.tsx | Suggestion click → navigate(`/${slug}`) | /:slug | WORKS | keep |
| ExploreSearch.tsx | Clear recents button | localStorage clear | WORKS | keep |
| ExploreTrendingGrid.tsx | Card Link `/${card.slug}` | /:slug | WORKS | keep |

**Summary:**
- 1 BROKEN link to remove ("See all →" on Trending header — `/explore?sort=trending` was a Phase 4.5b leftover; the new editorial layout has no `sort` UI surface).
- 2 NEEDS-NEW-ROUTE link clusters wired this commit via D-4.5.1-2 (`?category=` filter).
- All other `/{slug}` and external links resolve.

---

## 4. Backend `/api/v1/explore` controller

Located in
`backend/app/Http/Controllers/Api/V1/Public/SeoPageController.php`,
method `payload()` + private `buildPayload()`. Phase 4.5 already
factored out the structured payload assembly. Adding `?category`
filter is an additive change inside `buildPayload()`:

- New parameter `?category` (slug).
- When present: scope `trending_grid`, `categories[].items[]`,
  `rails.trending_searches`, `rails.most_read_week` to that
  category. Hero featured: top 4 in that category, falling back
  to global pinned set when category has fewer than 4 published
  pages with images.
- Cache key: include category slug in the hash
  (`'explore-payload:' . ($category ?? 'all')`).

---

## 5. SeoPageCardResource transformer

`backend/app/Http/Resources/V1/SeoPageCardResource.php` — already
exposes `hero_image_url` AS-IS (returns `null` when not set).
Frontend cards will key off that null for fallback rendering. No
backend transformer change needed for PART E.

---

## 6. Audit complete — proceeding to PART B
