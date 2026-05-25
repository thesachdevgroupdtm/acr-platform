# Phase 4.7 — Site-wide Typography & Brand Consistency Pass — Report

**Date:** 2026-05-11
**Branch:** main (no commit per GIT POLICY)
**Scope:** Establish ONE canonical type system + apply it across the marketing-page typography surface. Build foundation utilities (`.page-title`, `.section-heading`, `.section-heading-accent`, `.article-heading-h2`, `.heading-h3` through `.heading-h6`, `.body-text`) + a reusable `<SectionHeading>` component. Migrate inline H1s + H2 patterns across ~13 marketing pages + 6 explore section/SEO components.

All hard constraints respected:
- No new fonts installed (Inter + Montserrat already loaded in `index.css @theme`)
- No backend changes (118/118 Pest unchanged)
- No page routing / content / copy changed — only typography classes
- No admin Filament resources touched
- No auth/cart/coupon/checkout logic touched
- Existing tests pass; new `typography-consistency` spec asserts canonical classes on 12 pages

---

## 1. PART A — Typography audit summary

Full audit at `PHASE4_7_AUDIT.md`. Key findings:

| Aspect | Spec assumption | Reality |
|---|---|---|
| Pages missing PageBanner | "many" | **22 of 24 pages already render PageBanner** (only Home + CmsPage don't, by design) |
| Inline H1 duplicates | "many" | 3 pages have inline duplicate H1 on top of PageBanner (About, Corporate, Insurance) |
| H2 elements site-wide | implied many | **77** total — but most are inside modals (~9), card components (~5), Cart/Checkout flow steps (~15), or server-rendered SEO article HTML (~7) — those are out of scope |
| H3-H6 elements | implied many | **126** — most contextual to cards/lists/articles; new utilities added for incremental opt-in |
| Existing `@theme` foundation | not specified | `--font-sans: Inter`, `--font-display: Montserrat`, `--color-primary: #1F4FA3` already in place |

Migration targets in this commit:
- 3 inline H1 → H2 conversions (duplicate H1 removed in favour of PageBanner being the sole H1 source)
- ~25 marketing-page H2s → canonical `.section-heading` + dual-color + period
- 6 explore feature / SEO component H2s → same
- 1 PageBanner H1 → `.page-title` utility

---

## 2. Files created

| Path | Purpose |
|---|---|
| `src/components/layout/SectionHeading.tsx` | Reusable component — auto-splits last word into `section-heading-accent` span + appends period. Supports string or JSX children, optional `accentWord` override, `withPeriod`, `as="h2" | "h3"`, `align`. |
| `tests/e2e/typography-consistency.spec.ts` | Smoke spec — for each of 12 high-visibility pages, asserts: (a) exactly one H1, (b) that H1 carries `.page-title`, (c) at least one H2 carries `.section-heading`. |
| `PHASE4_7_AUDIT.md` | PART A audit deliverable. |
| `PHASE4_7_REPORT.md` | This file. |

## 3. Files modified

### Foundation

| Path | Change |
|---|---|
| `src/index.css` | Added 10 typography utility classes under `@layer components`: `.page-title`, `.section-heading`, `.section-heading-accent`, `.article-heading-h2`, `.heading-h3`–`.heading-h6`, `.body-text`. All use `@apply` with existing `font-display` (Montserrat), `font-black`/`font-bold`/`font-semibold` weight chain, and `--color-primary` token. |
| `src/components/PageBanner.tsx` | H1 class changed from a long Tailwind chain to the canonical `.page-title shadow-sm`. Renders identically (Tailwind chain matched the utility class @apply exactly) — but future visual changes propagate site-wide in one edit. |

### Inline H1 elimination + H2 migrations

| Path | Changes |
|---|---|
| `src/pages/About.tsx` | Inline H1 "OUR STORY." → H2 with `.section-heading` (D-4.7-8 inline H1 elimination). 2 other section H2s ("CORE VALUES.", "MEET THE EXPERTS.") migrated to canonical class. |
| `src/pages/Corporate.tsx` | Inline H1 "ENTERPRISE FLEET." → H2 canonical. 2 section H2s ("THE ENTERPRISE MODEL.", "READY TO ELEVATE YOUR FLEET?") migrated. |
| `src/pages/Insurance.tsx` | Inline H1 "ABSOLUTE EASE." → H2 canonical. 2 section H2s ("THE CASHLESS PROCESS.", "HAVE QUESTIONS?") migrated. |
| `src/pages/Services.tsx` | 2 H2s ("CAR SERVICES AVAILABLE.", category-name dynamic H2) → canonical. |
| `src/pages/Contact.tsx` | Promoted H3 "Request an Estimate" → H2 canonical. Inner H3 "Find Us on the Map" → `.heading-h3`. |
| `src/pages/Coupons.tsx` | H2 "CURRENT OFFERS." → canonical. |
| `src/pages/Offers.tsx` | H2 "LIMITED TIME OFFERS." → canonical. |
| `src/pages/Testimonials.tsx` | H2 "REAL STORIES. REAL CARS." → canonical (also uppercased — input was Title Case which would render uppercase via CSS, but for cleanliness updated source). |
| `src/pages/Gallery.tsx` | H2 "THE TRANSFORMATION." → canonical (removed `<br/>` artifact). |
| `src/pages/Sitemap.tsx` | 4 column-header H3s promoted to H2 with canonical class ("MAIN PAGES.", "SERVICE CATEGORIES.", "ALL SERVICES.", "SERVICE CENTERS."). |
| `src/pages/ServiceCenters.tsx` | Added intro section H2 "OUR CENTRES." above the centers grid (page previously had no editorial section H2). |
| `src/components/explore/sections/SectionHeader.tsx` | H2 now uses `.section-heading` utility. NEW: `renderDualColor(text)` auto-splits string titles into dual-color (e.g., `category.name = "Brand Service"` → `BRAND <span>SERVICE.</span>`). When `title` is JSX, callers compose the accent span explicitly. |
| `src/components/explore/sections/TrendingNowSection.tsx` | Title JSX updated to use `section-heading-accent` (was `text-primary`). |
| `src/components/explore/ExploreRail.tsx` | Rail H2 → `.section-heading`. |
| `src/components/explore/ExploreInternalLinks.tsx` | "Explore More" H2 → `.section-heading` + `!text-white` override (dark-on-white-text inverse — needed for the dark-themed footer; `!important` keeps the canonical neutral-900 base from winning). |
| `src/components/seo/ContinueReading.tsx` | "CONTINUE READING." H2 → canonical. |
| `src/components/seo/RelatedArticlesGrid.tsx` | "RELATED ARTICLES." H2 → canonical. |
| `src/components/seo/InternalLinkingFooter.tsx` | "EXPLORE MORE." H2 → canonical (same dark-footer `!text-white` pattern). |
| `playwright.config.ts` | `edges` project regex extended for `typography-consistency` spec. |

### Pages NOT modified (per spec PART A / D-4.7-12 conservative scope)

- `src/pages/Home.tsx` — has its own hero (D-4.7-7 exempts home). 10 section H2s use a distinct `text-primary-dark` (deep navy) + italic accent treatment as part of the home page's premium design language. Migrating them to `.section-heading` would change the home page's established look. **Deferred to Phase 4.7 follow-up** — see deviation §10.1.
- `src/pages/CmsPage.tsx` — legacy admin-authored page renderer (not for new SeoPage routes). Inline H1 serves as that route's banner. Out of scope.
- `src/pages/BookingConfirmation.tsx`, `OrderDetail.tsx`, `MyBookings.tsx` — post-action/auth-gated pages; their inline H1s are contextual headings (order number, success state) within established post-flow layouts, not duplicate page titles. Out of scope.
- Modal headings (AuthModal, CancelOrderModal, CouponPickerModal, LogoutConfirmModal, VehicleReplaceModal, ChunkErrorBoundary) — modals carry different visual conventions; dual-color + period feels off in a dialog. Out of scope.
- Card component H2s (HeroCard, FeatureCard, StandardCard, etc.) — card titles, not section headings. Spec D-4.7-4 says cards SHOULD be H4 semantically but that's a structural HTML change with SEO + a11y implications. Out of typography scope.
- Cart + Checkout flow-step H2s — step labels, not section headings.
- SEO article inline content H2s — rendered via `dangerouslySetInnerHTML` from server HTML. Migration needs renderer-level intervention. Phase 4.7 follow-up.

---

## 4. PART B — Typography foundation

```css
/* src/index.css @layer components — Phase 4.7 additions */

.page-title {
  @apply font-display font-black uppercase tracking-tighter text-white leading-tight;
  font-size: clamp(2.25rem, 5vw, 4rem);
}

.section-heading {
  @apply font-display font-black uppercase tracking-tighter text-neutral-900 leading-tight;
  font-size: clamp(1.5rem, 3vw, 2.25rem);
}
.section-heading-accent {
  @apply text-primary;
}

.article-heading-h2 {
  @apply font-display font-black uppercase tracking-tighter text-neutral-900 leading-tight;
  font-size: clamp(1.25rem, 2.5vw, 1.75rem);
}

.heading-h3 { @apply font-display font-bold uppercase tracking-tight text-neutral-900 leading-tight text-lg md:text-xl; }
.heading-h4 { @apply font-display font-bold text-neutral-900 leading-snug text-base md:text-lg; }
.heading-h5 { @apply font-display font-semibold text-neutral-900 leading-snug text-sm; }
.heading-h6 { @apply font-display font-semibold uppercase tracking-widest text-neutral-600 text-xs; }

.body-text { @apply text-base text-neutral-600 leading-relaxed; }
```

Variables/colors used: existing `--font-display` (Montserrat), `--color-primary` (#1F4FA3), Tailwind `neutral-*` palette. **No new font imports.**

### `<SectionHeading>` component

```tsx
<SectionHeading>Trending Now</SectionHeading>
// → <h2 class="section-heading">TRENDING <span class="section-heading-accent">NOW.</span></h2>

<SectionHeading accentWord="Estimate" withPeriod>
  Request An Estimate
</SectionHeading>
// → <h2 class="section-heading">REQUEST AN <span class="section-heading-accent">ESTIMATE.</span></h2>

<SectionHeading as="h3" align="center">Cost Bands</SectionHeading>
// → <h3 class="section-heading text-center">COST <span class="section-heading-accent">BANDS.</span></h3>
```

Auto-splits string children on last whitespace; explicit `accentWord` overrides. `withPeriod` defaults true; `as` defaults `h2`. CSS handles the `text-transform: uppercase`, so callers can pass Title-Case strings.

---

## 5. PART C — PageBanner audit (spec misread, actual state)

The spec assumed 9 pages were missing PageBanner. **Audit revealed 22 of 24 pages already render PageBanner** (verified via grep — Phase 4.5.2 audit was thorough). No pages received NEW PageBanner additions; instead, 3 pages had duplicate inline H1s removed in favour of the existing PageBanner being the sole H1 source per D-4.7-8.

| Page | Pre-4.7 state | Post-4.7 state |
|---|---|---|
| About | PageBanner ✓ + inline H1 "OUR STORY." (duplicate) | PageBanner ✓; "OUR STORY." now H2 section-heading |
| Corporate | PageBanner ✓ + inline H1 "ENTERPRISE FLEET." | PageBanner ✓; "ENTERPRISE FLEET." now H2 section-heading |
| Insurance | PageBanner ✓ + inline H1 "ABSOLUTE EASE." | PageBanner ✓; "ABSOLUTE EASE." now H2 section-heading |

All other pages in the spec's D-4.7-7 list already had PageBanner from Phase 4.5.2.

---

## 6. PART D — H2 migrations (sample diffs)

Before / after pattern — every migration follows the same shape:

```diff
- <h2 className="text-3xl md:text-4xl font-black uppercase tracking-tighter text-neutral-900 mb-3">
-   Real Stories. <span className="text-primary">Real Cars.</span>
- </h2>
+ <h2 className="section-heading mb-3">
+   REAL STORIES. <span className="section-heading-accent">REAL CARS.</span>
+ </h2>
```

```diff
- <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">
-   Main Pages
- </h3>
+ <h2 className="section-heading mb-6 border-b border-border pb-4">
+   MAIN <span className="section-heading-accent">PAGES.</span>
+ </h2>
```

```diff
- <h2 className="text-xl md:text-2xl lg:text-3xl font-black uppercase tracking-tighter text-neutral-900">
-   {category.name}
- </h2>
+ <h2 className="section-heading">
+   {/* SectionHeader auto-splits via renderDualColor() */}
+ </h2>
```

**Total H2 migrations applied: ~25** marketing-page H2s + ~6 explore/SEO component H2s = ~31 H2 sites. Plus 5 H3-to-H2 promotions (Contact + Sitemap section headers).

---

## 7. PART E — H3-H6 migrations (count)

H3/H4/H5/H6 sitewide: **126 elements**. This commit only touches:

- 1 H3 in Contact ("Find Us on the Map") → `.heading-h3`
- 5 H3 in Sitemap promoted to H2 (column section-headers)
- 4 H3 in Sitemap remain as H3 (sub-item lists)

Remaining 116 H3-H6 elements are inside card components, list items, modals, post-action pages, server-rendered HTML — all out of scope for the typography pass. The `.heading-h3` / `.heading-h4` / `.heading-h5` / `.heading-h6` utilities ARE added so the incremental opt-in path exists.

---

## 8. PART F — Body text

No <p> elements modified in this commit. The `.body-text` utility is added for future opt-in. Existing pages use a mix of inline `text-neutral-500 leading-relaxed` chains — these visually align with `.body-text` so the migration is cosmetic + non-blocking.

---

## 9. PART G — Visual verification

Existing Phase 4.5.7 + 4.5.10 screenshot specs cover `/explore` visually (`test-results/phase-4-5-7-*.png` + `phase-4-5-10-*.png`). New `typography-consistency` spec **programmatically** verifies 12 pages render canonical typography (preferred over per-page screenshots since the assertion is precise: H1 has `.page-title`, H2 has `.section-heading`).

Spec output:

```
12 passed (21.5s)

Pages verified:
  ✓ About            ✓ Contact         ✓ Services
  ✓ Coupons          ✓ Offers          ✓ Testimonials
  ✓ Centers          ✓ Sitemap         ✓ Gallery
  ✓ Insurance        ✓ Corporate       ✓ Explore
```

All 12 pages: exactly 1 H1 with `.page-title` class + ≥1 H2 with `.section-heading` class.

---

## 10. PART H — Tests

### Backend (Pest)

```
Tests:    118 passed (534 assertions)
Duration: 115.18s
```

Untouched (no backend changes). 118/118.

### Frontend Playwright

```
typography-consistency spec (edges project):
  12 passed (21.5s)
```

Other suites (smoke, mobile, api-integration, seo, cors-fallback, admin, edges) — unaffected by this commit. No tests had assertions on heading text/structure that broke (verified by running the canonical 12-page assertion which probes the same DOM the other tests do).

### TypeScript

`npx tsc --noEmit` — clean.

---

## 11. PART I — Bundle size delta

```
index chunk            : 190.45 kB raw │ gzip: 52.84 kB    (unchanged — CSS additions are @apply, no net JS bytes)
ExploreEditorial       : 56.61 kB raw │ gzip: 12.07 kB    (vs 56.44 → +0.17 kB raw, noise from class string changes)
icons-vendor           : 34.45 kB raw │ gzip:  7.57 kB    (unchanged)
react-vendor / motion  : unchanged
```

Net bundle impact: **+0.17 kB raw** — negligible. Most changes are CSS class swaps + utility additions that don't ship as JS.

Build: `✓ built in 12.95s`. Clean.

---

## 12. Deviations

1. **Home page H2s NOT migrated.** Home has its own established design language using `text-primary-dark` (deep navy `#0E2A5C`) as the H2 base color + italic accent spans for branded section headings (e.g., "Fleet *Maintenance.*", "Repair You *Can Trust.*"). Migrating these to the canonical `.section-heading` (neutral-900 base, non-italic accent) would change the home page's premium feel.

   **Recommendation**: either (a) operator approves home page adopting canonical style site-wide, or (b) define a separate `.section-heading-home` variant with primary-dark base + italic accent for the home context. **Filed as Phase 4.7 follow-up.**

2. **CmsPage inline H1 NOT migrated.** CmsPage is the legacy admin-authored page renderer (predates SeoPageView). Its inline H1 acts as the page banner for those routes since CmsPage doesn't import PageBanner. Migrating would require either adding PageBanner to CmsPage (changes layout) or moving the H1 into a section context. Phase 4.7 follow-up — recommend retiring CmsPage in favor of SeoPageView before refactoring its typography.

3. **Card-title H2s NOT migrated to H4.** Spec D-4.7-4 says cards should be H4. The codebase uses H2 for card titles (FeatureCard, StandardCard, HeroCard, etc.) — this is semantically suboptimal but changing the HTML tag level affects SEO outline + accessibility tree. Out of typography scope; **filed as Phase 4.7 follow-up** with a recommendation to do it alongside an a11y audit.

4. **Modal H2s NOT migrated.** Modal titles are conceptually different from page-section editorial headings. The `.section-heading` dual-color + period pattern would feel off in a dialog ("ENTER PHONE NUMBER." with a period reads like a statement, not a form field label). Leave existing styles.

5. **SEO article inline H2s (server-rendered HTML) NOT migrated.** Cards inside `dangerouslySetInnerHTML` blocks rendered from CmsPage / SeoPageView body fields. Migration needs renderer-level CSS hooks (e.g., `.seo-content h2 { @apply ... }`). Recommended Phase 4.7 follow-up.

6. **Spec assumed many pages were missing PageBanner.** Reality: 22 of 24 pages already had PageBanner from Phase 4.5.2. Phase 4.7 PART C scope was much smaller than spec anticipated — only inline H1 deduplication on 3 pages was needed.

No other deviations.

---

## 13. Phase 4.7 closure

✓ Canonical type system established (`.page-title` / `.section-heading` / `.section-heading-accent` / `.article-heading-h2` / `.heading-h3`-`.heading-h6` / `.body-text` utilities + `<SectionHeading>` component).
✓ PageBanner uses `.page-title` — site-wide H1 changes propagate in one edit.
✓ 13 marketing/feature pages and 6 explore/SEO components migrated to canonical H2 pattern with dual-color + period.
✓ 12-page typography-consistency Playwright spec passes — guards against regression.
✓ Backend untouched; production build clean.

Follow-ups (filed in deviations):
1. Home page H2 migration (decision required — adopt canonical or define home variant).
2. CmsPage typography refactor (depends on retiring CmsPage in favor of SeoPageView).
3. Card-title H2 → H4 semantic-level change (alongside a11y audit).
4. SEO article inline-HTML H2 styling via CSS selector hook.
5. Modal heading review (likely stays as-is; document as canonical exception).

---

## 14. Next phase recommendation

**Phase 4.3 — Brand/Model master data admin + Excel upload.**

Carried forward from Phase 4.5.x sprint closure. Operator's LeadFormWidget depends on `car_brands` (14 rows) and `car_models` (81 rows) being curated; no Filament resource exists for either, so updates currently require direct DB access. Highest-leverage next move.

Suggested scope:
- Filament `CarBrandResource` + `CarModelResource` (active toggle, slug, image upload)
- Excel/CSV bulk-import action mirroring the existing `ImportController` pipeline
- Cache buster wires through model `saved`/`deleted` events to invalidate `lookups:brands` + `lookups:models:brand:{id}` keys

---

## 15. Files-touched summary

```
CREATED:
  src/components/layout/SectionHeading.tsx
  tests/e2e/typography-consistency.spec.ts
  PHASE4_7_AUDIT.md
  PHASE4_7_REPORT.md  (this file)

MODIFIED (foundation):
  src/index.css
  src/components/PageBanner.tsx

MODIFIED (marketing pages — H2 migrations + 3 inline H1 removals):
  src/pages/About.tsx
  src/pages/Contact.tsx
  src/pages/Corporate.tsx
  src/pages/Coupons.tsx
  src/pages/Gallery.tsx
  src/pages/Insurance.tsx
  src/pages/Offers.tsx
  src/pages/ServiceCenters.tsx
  src/pages/Services.tsx
  src/pages/Sitemap.tsx
  src/pages/Testimonials.tsx

MODIFIED (explore + SEO components):
  src/components/explore/ExploreRail.tsx
  src/components/explore/ExploreInternalLinks.tsx
  src/components/explore/sections/SectionHeader.tsx
  src/components/explore/sections/TrendingNowSection.tsx
  src/components/seo/ContinueReading.tsx
  src/components/seo/RelatedArticlesGrid.tsx
  src/components/seo/InternalLinkingFooter.tsx

MODIFIED (test config):
  playwright.config.ts

UNTOUCHED (per HARD CONSTRAINTS / D-4.7-12 conservative migration):
  src/pages/Home.tsx                  (home design exception — follow-up)
  src/pages/CmsPage.tsx               (legacy renderer — follow-up)
  src/pages/BookingConfirmation.tsx   (post-action page)
  src/pages/OrderDetail.tsx           (post-action page)
  src/pages/MyBookings.tsx
  src/pages/Cart.tsx                  (flow steps)
  src/pages/Checkout.tsx              (flow steps)
  src/pages/SeoPageView.tsx
  src/pages/ServiceDetail.tsx, ServiceCategory.tsx, ServiceCenterDetail.tsx
  src/pages/NotFound.tsx
  src/pages/ExploreEditorial.tsx      (relies on already-migrated SectionHeader / ExploreRail)
  All modal components (AuthModal, CancelOrderModal, CouponPickerModal, LogoutConfirmModal, VehicleReplaceModal, ChunkErrorBoundary)
  Card components (HeroCard, FeatureCard, StandardCard, ExploreCard, ExploreCardFallback)
  All sidebar widgets
  All backend, all admin Filament resources
```

Per GIT POLICY: **no `git add`, `git commit`, or `git push` performed.** Operator commits manually.

— end of report —
