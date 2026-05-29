# Phase 4.5.1 — ExploreEditorial Correction Pass — Report

**Date:** 2026-05-09
**Branch:** main
**Scope:** Surgical fix on Phase 4.5 shipped output. Four corrections per spec D-4.5.1-1 through D-4.5.1-4 + sticky-sidebar restructure + 5 sidebar widgets + newsletter backend + dead-link cleanup.

All hard constraints respected:
- No new packages installed
- `app/Models/CmsPage.php` untouched
- `src/pages/SeoPageView.tsx` untouched
- All existing tests still pass (111/111 backend, 13/13 SEO Playwright)
- ACR Blue (`#1F4FA3`) primary; no fashion accents

---

## 1. Corrections shipped

| # | Spec | Status |
|---|---|---|
| D-4.5.1-1 | Delete `ExploreHero.tsx` carousel; replace with static 4-card `ExploreFeaturedGrid` | ✓ |
| D-4.5.1-2 | Category filter at `/explore?category={slug}` so "View All" links resolve | ✓ |
| D-4.5.1-3 | Fix no-image fallback (gradient + heroicon + restrained title; no giant text) | ✓ |
| D-4.5.1-4 | Sticky sidebar layout (8-col main + 4-col aside, multiple sticky regions) | ✓ |
| D-4.5.1-5 | Newsletter signup widget + backend table + idempotent endpoint | ✓ |
| D-4.5.1-6 | 5 sidebar widgets (Newsletter, TopPicks, PopularBrands, RelatedTopics, GetSocial) | ✓ |
| D-4.5.1-7 | CategoryFilterChip with × clear + scroll-to-top on category switch | ✓ |
| D-4.5.1-8 | Dead-link cleanup (`/explore?sort=trending` removed) | ✓ |

---

## 2. Files manifest

### Backend — created

| Path | Purpose |
|---|---|
| `backend/app/Http/Controllers/Api/V1/Public/NewsletterController.php` | Idempotent `POST /api/v1/newsletter/subscribe`. Returns `{ok, already_subscribed}`. |
| `backend/database/migrations/2026_05_09_080534_create_newsletter_subscriptions_table.php` | `newsletter_subscriptions(id, email UNIQUE, ip_address, timestamps)`. Additive only — no existing column changes. |
| `backend/tests/Feature/Explore/ExploreCategoryFilterTest.php` | 2 tests — `?category=` filter applies; full payload returned without param. |
| `backend/tests/Feature/Newsletter/NewsletterSubscribeTest.php` | 2 tests — idempotent insert; invalid email → 422. |

### Backend — modified

| Path | Change |
|---|---|
| `app/Http/Controllers/Api/V1/Public/SeoPageController.php` | `payload(Request)` reads `?category={slug}`; per-category cache key (`explore-payload:all` or `explore-payload:{slug}`); `buildPayload(?string $categorySlug)` filters trending/categories/rails. |
| `app/Http/Resources/V1/SeoPageCardResource.php` | Adds `icon_name` to `category` block so the fallback can pick a heroicon. |
| `app/Models/SeoPage.php` | Cache bust now walks per-category keys + legacy `explore-payload`. |
| `routes/api.php` | `POST newsletter/subscribe` (throttled `public-read`). |
| `tests/Feature/Explore/ExplorePayloadTest.php` | Updated cache-key assertion: legacy `explore-payload` → `explore-payload:all`. |

### Frontend — created

| Path | Purpose |
|---|---|
| `src/components/explore/ExploreFeaturedGrid.tsx` | Static 4-card 12-col 2-row grid (no carousel/autoplay/drag). |
| `src/components/explore/ExploreCard.tsx` | Single-source-of-truth card; 5 sizes (`large \| medium \| small \| compact \| wide`). Renders `<ExploreCardFallback>` when no image. |
| `src/components/explore/ExploreCardFallback.tsx` | Replaces giant-text fallback. Slate gradient + heroicon + category badge + restrained title overlay (`text-sm md:text-base`) + low-opacity ACR watermark. |
| `src/components/explore/CategoryFilterChip.tsx` | Visible only when `?category=` is set; × clears via `setParams(next, { replace: true })`. |
| `src/components/explore/widgets/NewsletterWidget.tsx` | Email input + subscribe; calls `subscribeToNewsletter()`; success state 5s. |
| `src/components/explore/widgets/TopPicksWidget.tsx` | Numbered 01–05 list from `rails.most_read_week`. |
| `src/components/explore/widgets/PopularBrandsWidget.tsx` | Brand chips derived from `payload.categories.find(c => c.slug === 'brand-service')`. Hides if none. |
| `src/components/explore/widgets/RelatedTopicsWidget.tsx` | Top 5 categories, links to `/explore?category={slug}`. |
| `src/components/explore/widgets/GetSocialWidget.tsx` | Reuses `BUSINESS_INFO.social` from `src/data/businessData.ts` — NO hardcoded URLs. |
| `tests/e2e/explore-category-filter.spec.ts` | Playwright — clicking View All navigates with `?category` and chip surfaces. |
| `tests/e2e/explore-no-image-fallback.spec.ts` | Playwright — route-intercepts payload, strips images, asserts `[data-testid="explore-card-fallback"]` mounts and no `text-7xl/6xl/5xl` inside it. |

### Frontend — modified

| Path | Change |
|---|---|
| `src/pages/ExploreEditorial.tsx` | Restructured: `useSearchParams` + `useQuery(["explore-payload", category ?? "all"])`. Two 12-col grid sections, each with 8-col main + 4-col `lg:sticky lg:top-24 lg:self-start` aside; mobile-only widgets in `col-span-12 lg:hidden`. Resolves `activeCategoryName` from categories list. Deleted broken "See all → /explore?sort=trending" CTA. |
| `src/components/explore/ExploreCategorySection.tsx` | All 4 inline cards (FeatureLargeCard, FeatureWideCard, ListItemCard, SmallCard) use `<ExploreCardFallback>` when no image. View All `Link` → `/explore?category=…` with `onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}`. |
| `src/components/explore/ExploreTrendingGrid.tsx` | Trending cards use fallback ternary; removed `https://placehold.co/...` URLs. |
| `src/components/explore/ExploreRail.tsx` | RailCard fallback ternary; `bg-neutral-100` → `bg-neutral-900` for fallback backdrop. |
| `src/components/explore/ExploreSkeleton.tsx` | Minor tweak — no hero carousel block. |
| `src/lib/api.ts` | Added `icon_name?: string \| null` to `ExploreCategoryRef`; `subscribeToNewsletter(email)`; `fetchExplorePayloadByCategory(category, signal)`. |
| `tests/e2e/explore-editorial.spec.ts` | Updated `hero carousel renders` test → `featured grid renders 4 static cards (no carousel)`. Asserts old `explore-hero` testid is gone. |
| `playwright.config.ts` | `seo` project `testMatch` regex extended to include the two new specs. |

### Files deleted

| Path | Reason |
|---|---|
| `src/components/explore/ExploreHero.tsx` | Carousel — replaced by `ExploreFeaturedGrid` per D-4.5.1-1. |

---

## 3. Dead-link manifest (resolved)

From `PHASE4_5_1_AUDIT.md` table — all BROKEN / NEEDS-NEW-ROUTE entries closed:

| Element | Old target | Resolution |
|---|---|---|
| ExploreEditorial "See all →" (Trending header) | `/explore?sort=trending` (no UI) | **REMOVED** — `<button>` block deleted entirely. |
| ExploreCategorySection "View All" | `onPillClick(name)` (no-op) | **WIRED** — now `<Link to={`/explore?category=${slug}`}>` with scroll-to-top onClick. |
| ExploreCategorySection "View All →" alt | broken `?cat=` param | **WIRED** — same `?category=` flow. |
| Internal links cluster | static slugs | unchanged — already worked. |

The `audit doc` lists the full clickable inventory; every row that wasn't `WORKS` is now resolved.

---

## 4. Test results

### Backend (Pest)

```
Tests:    111 passed (498 assertions)
Duration: 18.56s
```

Net delta: **+4 new** (`ExploreCategoryFilterTest` ×2, `NewsletterSubscribeTest` ×2). Phase 4.5 baseline was 107.

### Frontend (Playwright `seo` project)

```
13 passed (39.1s)
```

Net delta: **+2 new** (`explore-category-filter`, `explore-no-image-fallback`). Phase 4.5 baseline was 11. The carousel test was rewritten in place to match the static grid; no test count change there.

### TypeScript

`npx tsc --noEmit` — clean (no output).

---

## 5. Bundle size delta

```
ExploreEditorial chunk:
  Phase 4.5    : 28.30 kB raw │ gzip:  6.35 kB
  Phase 4.5.1  : 40.17 kB raw │ gzip:  8.20 kB
  Δ            : +11.87 kB raw │ gzip: +1.85 kB
```

**Honest deviation from spec.** Spec predicted a *decrease* "since the carousel is gone." Actual is a **+11.87 kB raw increase**. Reason: the carousel was small (~3 kB) and we *added* more than we removed:

- `ExploreCardFallback` + `ExploreCard` (single-source card) — ~3 kB
- 5 widget components (Newsletter, TopPicks, PopularBrands, RelatedTopics, GetSocial) — ~5 kB combined
- `CategoryFilterChip` — ~0.5 kB
- `useSearchParams` + filter logic in ExploreEditorial — ~2 kB
- Sticky-sidebar grid markup overhead — ~1 kB
- Newsletter API helper + types — ~0.5 kB

Net is still well within Phase 4.5's "+30–50 KB explore-route envelope." App shell (`index-Babmn7UQ.js`) grew from 189.48 → 190.49 kB raw / 52.46 → 52.86 kB gzip — tiny (1 kB / 0.4 kB), basically noise from the new exports in `src/lib/api.ts`.

```
Full asset summary (relevant chunks):
ExploreEditorial-Pu6L0dXN.js   40.17 kB │ gzip:  8.20 kB
SeoPageView-CnUM8U-f.js        23.24 kB │ gzip:  6.71 kB    (untouched)
CmsPage-C_70VO9L.js            23.31 kB │ gzip:  6.19 kB    (untouched)
index-Babmn7UQ.js             190.49 kB │ gzip: 52.86 kB    (+1 kB raw)
react-vendor                  193.82 kB │ gzip: 60.54 kB    (unchanged)
motion-vendor                 127.89 kB │ gzip: 42.02 kB    (unchanged)
```

Build clean: `✓ built in 14.51s`.

---

## 6. Verifying acceptance per spec

| Acceptance criterion | Evidence |
|---|---|
| Carousel removed; 4-card static grid mounts | `tests/e2e/explore-editorial.spec.ts › featured grid renders 4 static cards (no carousel)` — passes; asserts `explore-hero` testid is `count(0)`. |
| Category filter end-to-end | `ExploreCategoryFilterTest.php` (backend) + `explore-category-filter.spec.ts` (E2E) — all pass. |
| No-image fallback design | `ExploreCardFallback` mounts with `data-testid="explore-card-fallback"`; e2e test verifies no `text-7xl/6xl/5xl` inside. |
| Sticky sidebar layout | `ExploreEditorial.tsx` lines 162 + 194 — `lg:sticky lg:top-24 lg:self-start` on both sections. Mobile drops to `lg:hidden` widget block. |
| Newsletter idempotent backend | `NewsletterSubscribeTest.php` — duplicate POST returns 200 with `already_subscribed: true`, only 1 row in DB. |
| 5 widgets present | `NewsletterWidget`, `TopPicksWidget`, `PopularBrandsWidget`, `RelatedTopicsWidget`, `GetSocialWidget` — all wired into both sections of the page. |
| `BUSINESS_INFO.social` reused | `GetSocialWidget.tsx:25` — `BUSINESS_INFO.social?.[p.key]`; no hardcoded URLs. |
| ACR Blue palette only | All new components use `bg-primary` / `text-primary` (the `#1F4FA3` token). No purple / teal / gradient brand colors. |
| Existing tests pass | 107 → 111 backend pass, 11 → 13 SEO Playwright pass, no regressions. |
| Production DB safety | Newsletter migration is purely additive (new table). No existing schema touched. |
| SEO slugs untouched | No slug column written in any migration; `is_published` / `slug` of existing rows unchanged. |
| `app/Models/CmsPage.php` untouched | `git diff main -- backend/app/Models/CmsPage.php` empty. |
| `src/pages/SeoPageView.tsx` untouched | `git diff main -- src/pages/SeoPageView.tsx` empty. |

---

## 7. Deviations from spec

1. **Bundle size grew** instead of shrinking — see §5. Driven by the breadth of new components (5 widgets + fallback + filter chip) outweighing the carousel deletion. Documented honestly; well within the original Phase 4.5 envelope.

2. **Cache-key migration** — Phase 4.5 used a single `explore-payload` cache key. Phase 4.5.1 needed per-category keys (`explore-payload:all` + `explore-payload:{slug}`). The legacy key is still flushed by `SeoPage::saved/deleted` so any stale 4.5 entry self-evicts.

3. **`ExplorePayloadTest` cache assertion updated** — touched a Phase 4.5 test to match the new key. This is *not* a regression: the underlying behavior (cache exists after first call) is identical; only the key string differs. Documented in the test with a Phase 4.5.1 comment.

No other deviations.

---

## 8. Files-touched summary

```
git status (vs main):
  modified:   playwright.config.ts
  modified:   src/lib/api.ts
  modified:   src/pages/ExploreEditorial.tsx
  modified:   src/components/explore/ExploreCategorySection.tsx
  modified:   src/components/explore/ExploreTrendingGrid.tsx
  modified:   src/components/explore/ExploreRail.tsx
  modified:   src/components/explore/ExploreSkeleton.tsx
  deleted:    src/components/explore/ExploreHero.tsx
  modified:   tests/e2e/explore-editorial.spec.ts

  modified:   backend/app/Http/Controllers/Api/V1/Public/SeoPageController.php
  modified:   backend/app/Http/Resources/V1/SeoPageCardResource.php
  modified:   backend/app/Models/SeoPage.php
  modified:   backend/routes/api.php
  modified:   backend/tests/Feature/Explore/ExplorePayloadTest.php

  new file:   src/components/explore/ExploreCard.tsx
  new file:   src/components/explore/ExploreCardFallback.tsx
  new file:   src/components/explore/ExploreFeaturedGrid.tsx
  new file:   src/components/explore/CategoryFilterChip.tsx
  new file:   src/components/explore/widgets/NewsletterWidget.tsx
  new file:   src/components/explore/widgets/TopPicksWidget.tsx
  new file:   src/components/explore/widgets/PopularBrandsWidget.tsx
  new file:   src/components/explore/widgets/RelatedTopicsWidget.tsx
  new file:   src/components/explore/widgets/GetSocialWidget.tsx
  new file:   tests/e2e/explore-category-filter.spec.ts
  new file:   tests/e2e/explore-no-image-fallback.spec.ts

  new file:   backend/app/Http/Controllers/Api/V1/Public/NewsletterController.php
  new file:   backend/database/migrations/2026_05_09_080534_create_newsletter_subscriptions_table.php
  new file:   backend/tests/Feature/Explore/ExploreCategoryFilterTest.php
  new file:   backend/tests/Feature/Newsletter/NewsletterSubscribeTest.php

  new file:   PHASE4_5_1_AUDIT.md
  new file:   PHASE4_5_1_REPORT.md  (this document)
```

---

## 9. Next-phase candidates

- App shell still ~190 kB raw / 53 kB gzip — `react-helmet-async` is the load-bearer (carried over from Phase 4.5b). Defer Helmet usage to a dynamic import on `/home` + `/services` routes if shell-size budget tightens.
- `ExploreEditorial` chunk now at 40 kB — if it grows further, consider splitting widgets behind `<Suspense>` boundaries (mobile-only widget block first, since it's never seen on desktop).
- Newsletter table currently has no admin surface. Filament resource could be added in a follow-up so operators can export the subscriber list.

— end of report —
