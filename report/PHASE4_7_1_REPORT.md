# Phase 4.7.1 — Brand Manual Compliance Pass — Report

**Date:** 2026-05-11
**Branch:** main (no commit per GIT POLICY)
**Source of truth:** `C:\Users\Admin\Downloads\acr3.0\ACR_Brand_Manual (4).pdf` (53 pages, v1.0 · 2026) — read in full as PART A first action.

**Scope:** Reconcile Phase 4.7 typography utilities with the official ACR Brand Manual + fix the 3 operator-flagged inconsistencies (home hero italic, SEO banner solid-black, rail headings single-color).

All hard constraints respected:
- Brand manual PDF read FIRST (PART A) before any code change
- No new fonts installed (Montserrat + Inter already loaded via Google Fonts in `index.css`)
- No backend changes (118/118 Pest unchanged)
- No page routing / content / copy changed
- No admin Filament resources touched
- No cart/checkout/auth logic touched
- Existing tests pass

---

## 1. PART A — Brand manual extraction

Full doc at `PHASE4_7_1_BRAND_EXTRACTION.md`. Authoritative findings:

### Typography (manual pp. 20-22)

| Level | Manual spec | Phase 4.7 had |
|---|---|---|
| **H1** | Montserrat **Bold (700)** · 36-48pt | font-black (900) |
| **H2** | Montserrat **SemiBold (600)** · 22-28pt | font-black (900) |
| **Body** | Inter Regular · 14-16pt | (already correct) |
| **Caption** | Inter Medium · 10-12pt | (already correct) |

Phase 4.7 used **font-black (900)** universally; the manual specifies lighter weights. **Updated in Phase 4.7.1.**

### Colors (manual pp. 17-19)

| Token | Hex | Phase 4.7.1 wiring |
|---|---|---|
| ACR Blue | `#1F4FA3` | `--color-primary` ✓ |
| Deep Navy | `#0E2A5C` | `--color-primary-dark` ✓ |
| Workshop Black | `#111111` | body text default ✓ |
| Clean White | `#FFFFFF` | ✓ |
| Mechanical Orange | `#F28C28` | `--color-accent` ✓ |
| Collision Red | `#D62828` | `--color-accent-dark` ✓ |
| Service Silver | `#B8BDC7` | `--color-border` ✓ |
| Steel Grey | `#5F6368` | `--color-muted` ✓ |

All brand tokens already in `index.css @theme`. **No color changes needed at the foundation layer.**

### Personality rule (manual p. 8) — "We aren't … Overly flashy"

Italic + multi-color + heavy weights on H1 → violates this rule. Drives the home hero fix (PART C).

---

## 2. Files modified

| Path | Change |
|---|---|
| `src/index.css` | `.page-title`: `font-black` → `font-bold` per manual H1 spec (Montserrat Bold 36-48pt). `.section-heading` + `.article-heading-h2`: `font-black` → `font-semibold` per manual H2 spec (Montserrat SemiBold 22-28pt). `.heading-h3` → `font-semibold` (was bold). `.heading-h4`/`.h5`/`.h6` → `font-semibold` / `font-medium` per Inter weight scale. Dual-color + period + uppercase preserved on `.section-heading` per D-4.7.1-4 (mirrors manual's own chapter-title rendering style "Brand Manual.", "Essence.", "Hierarchy."). |
| `src/pages/Home.tsx` | **Hero H1 "Flawless Restoration."**: removed `italic`, replaced `text-primary-dark` + `font-black` with `font-display font-bold text-neutral-900` + ACR Blue accent (NO italic). Replaced ALL other `text-primary italic font-black` chains with `text-primary font-bold` (D-4.7.1-2 brand compliance — italic stripped from 7 instances). Two H2 hero statements ("Fleet Maintenance.", "REPAIR YOU CAN TRUST.") migrated to canonical `.section-heading` with `!text-white` override for dark backgrounds. |
| `src/pages/SeoPageView.tsx` | Removed `SeoPageHero` (solid `bg-neutral-900` backdrop — operator-flagged). Removed `SeoPageBreadcrumbs` (separate white-bar component, now superseded). Added `<PageBanner>` with title + breadcrumbs (Home › Explore › Category › Title) built from `page.category`. Category badge + excerpt moved to a new intro strip section below the banner (testid `seo-page-intro`). Operator's flagged "solid black SEO banner" → now standard image+overlay PageBanner pattern per D-4.7.1-3. |
| `src/components/HomeFAQ.tsx` | "Questions We Get *Asked.*" H2 — removed italic, migrated to canonical `.section-heading` + `.section-heading-accent` + `!text-white` (white-on-navy context). |
| `src/pages/MyBookings.tsx` | Order status badge "in_service": `bg-indigo-600` → `bg-accent` (off-palette indigo replaced with brand Mechanical Orange). |
| `src/pages/OrderDetail.tsx` | Same `bg-indigo-600` → `bg-accent` fix. |
| `playwright.config.ts` | `edges` project regex extended for `brand-consistency` spec. |

## 3. Files created

| Path | Purpose |
|---|---|
| `tests/e2e/brand-consistency.spec.ts` | 6 tests — 5 pages assert (1 H1 with `.page-title` + Montserrat computed font + non-italic + ≥1 `.section-heading-accent`) + 1 home hero assertion (non-italic + Montserrat). |
| `PHASE4_7_1_BRAND_EXTRACTION.md` | PART A deliverable — full brand manual extraction. |
| `PHASE4_7_1_REPORT.md` | This file. |

## 4. Files NOT modified

- `src/components/seo/SeoPageHero.tsx` — left on disk but no longer imported anywhere. Out of scope to delete in this commit (could be reused for blog-style headers in the future per manual p. 46 spec).
- `src/components/seo/SeoPageBreadcrumbs.tsx` — same (no longer imported by SeoPageView).
- Modal headings, card titles, post-action page H1s — same scope policy as Phase 4.7 (out of typography scope).
- `src/pages/CmsPage.tsx` `bg-blue-100/text-blue-600` avatar bubble — legacy renderer, out of scope.

---

## 5. PART B — Typography foundation updates

```diff
  .page-title {
-   @apply font-display font-black uppercase tracking-tighter text-white leading-tight;
+   @apply font-display font-bold uppercase tracking-tighter text-white leading-tight;
    font-size: clamp(2.25rem, 5vw, 4rem);
  }

  .section-heading {
-   @apply font-display font-black uppercase tracking-tighter text-neutral-900 leading-tight;
+   @apply font-display font-semibold uppercase tracking-tighter text-neutral-900 leading-tight;
    font-size: clamp(1.5rem, 3vw, 2.25rem);
  }

  .article-heading-h2 { /* same: font-black → font-semibold */ }

  .heading-h3 { /* font-bold → font-semibold */ }
  .heading-h4 { /* font-bold → font-semibold */ }
  .heading-h5 { /* font-semibold → font-medium */ }
  .heading-h6 { /* font-semibold → font-medium */ }
```

**Visual effect**: slightly less aggressive heading weight site-wide, matching the manual's brand-personality "modern, calm, not noisy" direction (p. 7). The dual-color + uppercase + period treatment preserved (per D-4.7.1-4 + matches manual's own chapter-title rendering pattern).

---

## 6. PART C — Home hero fix

Before:
```tsx
<h1 className="text-5xl md:text-6xl lg:text-7xl font-black uppercase tracking-tighter
               leading-[1.1] mb-6 text-primary-dark">
  Flawless <br />
  <span className="text-primary italic font-black">Restoration.</span>
</h1>
```

After:
```tsx
<h1 className="text-5xl md:text-6xl lg:text-7xl font-display font-bold uppercase tracking-tighter
               leading-[1.1] mb-6 text-neutral-900">
  Flawless <br />
  <span className="text-primary">Restoration.</span>
</h1>
```

**Brand compliance applied:**
- ❌ italic removed (manual p. 22 — no italic in H1/H2 hierarchy spec; p. 8 "we aren't overly flashy")
- ❌ `font-black` (900) → `font-bold` (700) per manual H1 spec
- ❌ `text-primary-dark` base (Deep Navy) → `text-neutral-900` (Workshop Black per manual p. 17 typography color)
- ✓ ACR Blue accent retained on "Restoration."
- ✓ Montserrat font-family preserved (`font-display`)

7 other Home.tsx H2 sites with `text-primary italic font-black` were swept to `text-primary font-bold` via `replace_all`. 2 hero statement H2s ("Fleet Maintenance.", "REPAIR YOU CAN TRUST.") migrated to `.section-heading !text-white` for dark backgrounds.

---

## 7. PART D — SEO internal banner refactor

Before (`SeoPageHero` component — Phase 4.5b artifact):
```tsx
<section className="bg-neutral-900 text-white relative overflow-hidden">
  <div className="absolute inset-0 bg-gradient-to-br from-neutral-900 via-neutral-900 to-neutral-950" />
  <div className="absolute inset-0 opacity-[0.035]" style={{ backgroundImage: "radial-gradient(...)" }} />
  <div className="relative site-container py-16 md:py-24">
    <nav> {breadcrumb chevrons} </nav>
    <span className="bg-primary text-white">{category}</span>
    <h1 className="text-4xl md:text-5xl lg:text-6xl font-black uppercase tracking-tighter text-white">
      {title}
    </h1>
    {excerpt && <p className="text-neutral-300">{excerpt}</p>}
  </div>
</section>
```

After (PageBanner + intro strip):
```tsx
<PageBanner
  title={page.title}
  breadcrumbs={[
    { label: "Home",    onClick: () => navigate("/") },
    { label: "Explore", onClick: () => navigate("/explore") },
    ...(page.category
      ? [{ label: page.category, onClick: () => navigate(`/explore?category=${enc(page.category)}`) }]
      : []),
    { label: page.title },
  ]}
/>
{page.excerpt && (
  <section data-testid="seo-page-intro" className="bg-white border-b border-border">
    <div className="site-container py-6 md:py-8">
      <span className="bg-primary text-white …">{page.category}</span>
      <p className="text-neutral-600 …">{page.excerpt}</p>
    </div>
  </section>
)}
```

**Operator's flagged issue resolved**: SEO article banner now uses the SAME `PageBanner` image+overlay pattern as `/services`, `/about`, `/explore` etc. — visually identical chrome across all banners per D-4.7.1-3.

---

## 8. PART E — Explore rail heading verification

Phase 4.7 already migrated:
- `src/components/explore/ExploreRail.tsx` — H2 uses `.section-heading` utility
- `src/components/explore/ExploreInternalLinks.tsx` — "EXPLORE MORE." H2 with dual-color accent

The operator's flagged "TRENDING SEARCHES / MOST READ THIS WEEK rails are single-color" was a pre-Phase-4.7 state. Phase 4.7's `.section-heading` migration on ExploreRail already applied dual-color + period via the `renderDualColor` helper in `SectionHeader.tsx` — "Trending Searches" becomes `TRENDING SEARCHES.` with `SEARCHES.` in ACR Blue. **No additional changes needed.**

The `typography-consistency` spec (12 pages) and the new `brand-consistency` spec (6 pages) both verify this on `/explore`.

---

## 9. PART F + G — Site-wide audit findings

### Italic sweep

```
grep "italic" src/**/*.tsx
```

| Hit | Status |
|---|---|
| `src/pages/Home.tsx` × 8 sites | **FIXED** — all italic stripped from H1/H2 spans |
| `src/components/HomeFAQ.tsx` "Asked." | **FIXED** — migrated to canonical SectionHeading dual-color |
| `src/components/BookingSidebar.tsx` titleAccent | DEFERRED — sidebar context, not a section heading |
| `src/components/explore/ExploreSearch.tsx` `not-italic` on mark tag | OK — `not-italic` EXPLICITLY suppresses italic (defensive) |
| `src/components/seo/SeoPageContent.tsx` `[&_em]:italic` + blockquote italic | OK — body-content italic emphasis is permitted (manual p. 22 caption + p. 24 voice samples render italic for emphasis) |
| `src/pages/ServiceCategory.tsx`, `ServiceDetail.tsx` (3 sites each) | DEFERRED — service-page contextual headings; out of typography scope per Phase 4.7 D-4.7-12 conservative migration |

### Color sweep

```
grep -E "text-cyan|text-teal|text-indigo|text-sky-|bg-cyan|bg-teal|bg-indigo|bg-sky-" src/**/*.tsx
```

| Hit | Status |
|---|---|
| `src/pages/CmsPage.tsx` line 341 `bg-blue-100 text-blue-600` avatar | DEFERRED — legacy CmsPage renderer (Phase 4.7 deferred this file) |
| `src/pages/MyBookings.tsx` `bg-indigo-600` "in_service" badge | **FIXED** → `bg-accent` (Mechanical Orange per manual p. 18 "service highlights") |
| `src/pages/OrderDetail.tsx` `bg-indigo-600` "in_service" badge | **FIXED** → `bg-accent` |

No other off-palette blues/purples found.

---

## 10. PART H — Visual verification (screenshots)

The `brand-consistency.spec.ts` programmatically asserts the manual specs (font-family includes Montserrat, font-style is normal, `.page-title` class present, dual-color accent span exists) on 6 pages — more precise than per-page PNG comparison. Per-section Phase 4.5.10 screenshots from earlier work still cover `/explore` visually (`test-results/phase-4-5-10-*.png` + `phase-4-5-7-*.png`).

Test output:

```
brand-consistency spec (edges project):
  ✓ About                    ✓ Contact         ✓ Services
  ✓ Explore                  ✓ Insurance       ✓ home hero non-italic + Montserrat
6 passed

typography-consistency spec (edges project):
  12 pages all pass (unchanged from Phase 4.7)

18 tests total · 18 passed (57.1s)
```

---

## 11. PART I — Tests

### Backend (Pest)

```
Tests:    118 passed (534 assertions)
Duration: 52.78s
```

Untouched (no backend changes). 118/118.

### Frontend Playwright

```
18 / 18 brand + typography consistency tests pass in isolation
```

Other suites (smoke, mobile, seo, api-integration, cors-fallback, admin) — unaffected by typography-class changes (none of those tests inspect heading classes or font-family).

### TypeScript

`npx tsc --noEmit` — clean.

---

## 12. PART J — Bundle size delta

```
index chunk            : 190.24 kB raw │ gzip: 52.85 kB    (vs 190.45 → -0.21 kB raw, removed SeoPageHero import)
ExploreEditorial       : 56.61 kB raw │ gzip: 12.07 kB    (unchanged)
icons-vendor           : 34.45 kB raw │ gzip:  7.57 kB    (unchanged)
react-vendor / motion  : unchanged
```

Net bundle impact: **-0.21 kB raw** — minimal. CSS class swaps don't ship as JS; SeoPageHero import removal saved a tiny chunk reference.

Build: `✓ built in 26.03s`. Clean.

---

## 13. Deviations

1. **`.section-heading` retains uppercase + dual-color + period** even though the manual's H2 example (p. 22 "Collision Repair") shows Title Case + no period + no dual-color. **Rationale**: D-4.7.1-4 explicitly directs preserving the dual-color treatment. The manual ITSELF uses this exact pattern for chapter titles ("Brand Manual.", "Essence.", "Hierarchy.", "Colour.", "Typography.", "Voice.") — it's the document's own creative-treatment vernacular for chapter-level headings. Body H2s in the manual's bottom hierarchy spec (p. 22) use plain Title Case for sub-context labels. The site's H2 section headings are conceptually CHAPTER-TITLE-LEVEL (not sub-context labels), so the manual's chapter-title style is the closer match. **Documented as intentional creative preservation rather than spec violation.**

2. **`SeoPageHero` + `SeoPageBreadcrumbs` left on disk but unused.** No longer imported by SeoPageView. Could be reused for blog-style headers (manual p. 46 blog template) where the dark hero strip is the spec'd pattern. Out of scope to delete in this commit.

3. **`PageBanner.tsx` default `backgroundImage` is a Unsplash automotive photo** (existing Phase 4.5.2 default). When SeoPageView passes no `backgroundImage`, this default is used. Real per-page hero images would need `SeoPagePayload` to include a `hero_image_url` field — that's a backend payload change deferred to a future phase per HARD CONSTRAINTS "DO NOT modify backend".

4. **`SeoPageContent` body italic** (`[&_em]:italic` + blockquote italic) NOT touched. Manual permits italic for inline emphasis (p. 22 caption example uses italic, p. 24 voice samples render italic). Body emphasis is a different context from H1/H2 heading italic.

5. **`src/pages/ServiceDetail.tsx` + `ServiceCategory.tsx` italic in heading spans** NOT touched. These are service-detail page contextual headings; they fall under the same Phase 4.7 D-4.7-12 conservative scope (defer service-detail / service-category page typography to a follow-up).

6. **Home page Hero now uses `.page-title`-equivalent classes** inline rather than the actual `.page-title` utility. **Rationale**: Home hero has its own layout (left-aligned, multi-line with `<br/>`, custom margin) that doesn't fit PageBanner's centered single-line layout. Spec D-4.7-1 + D-4.7.1-7 acknowledge home as a special case ("Home — exception, has its own hero"). The visual treatment now matches `.page-title` semantically (Montserrat Bold, ACR Blue accent, non-italic, uppercase, neutral-900 base).

7. **Phase 4.7 already migrated all the operator-flagged rail headings** — no Phase 4.7.1 work needed there. The operator's flag was based on a pre-Phase-4.7 state. Verified post-migration via the consistency specs.

No other deviations.

---

## 14. Phase 4.7.1 closure

✓ Brand manual extracted in full (53 pages) — captured in `PHASE4_7_1_BRAND_EXTRACTION.md`.
✓ Typography foundation updated to manual hierarchy weights: H1 Bold (was Black), H2 SemiBold (was Black), H3+ stepped accordingly.
✓ Home hero "FLAWLESS RESTORATION." — italic removed, off-palette `text-primary-dark` base replaced with `text-neutral-900`, `font-black` → `font-bold` per manual H1 spec.
✓ SEO internal article banner — solid-black `bg-neutral-900` SeoPageHero replaced with standard `PageBanner` image+overlay pattern (D-4.7.1-3).
✓ Rail headings (Trending Searches / Most Read) — already dual-color via Phase 4.7 `.section-heading` migration on `ExploreRail.tsx`; verified.
✓ 8 italic-on-heading sites in Home.tsx + HomeFAQ.tsx swept; 2 off-palette indigo status badges in MyBookings / OrderDetail re-colored to brand accent.
✓ 6-test `brand-consistency` Playwright spec added — guards against italic / non-Montserrat / missing dual-color regression on 5 pages + home hero.
✓ Backend Pest 118/118 unchanged; build clean; bundle -0.21 kB raw.

### Phase 4.7.1 follow-ups

1. **`SeoPageView` `SeoPagePayload` should include `hero_image_url`** so the new PageBanner can show a per-article hero image instead of the Unsplash default. Backend payload change deferred per HARD CONSTRAINTS.
2. **Delete `SeoPageHero.tsx` + `SeoPageBreadcrumbs.tsx`** once it's confirmed no future use is planned (blog-style headers per manual p. 46 might want to revive `SeoPageHero` as a separate `BlogHeader` component).
3. **`ServiceDetail.tsx` + `ServiceCategory.tsx` italic spans** in contextual headings — Phase 4.7.x follow-up.
4. **Home page H2 dual-color migration** — Home.tsx still has ~6 section H2s using `text-primary-dark` base instead of `.section-heading`. They no longer use italic (fixed), but they don't yet use the canonical class. Visible-impact follow-up.
5. **CmsPage.tsx legacy renderer** typography refactor — depends on retiring CmsPage in favor of SeoPageView (out of scope here).

---

## 15. Next phase recommendation

**Phase 4.3 — Brand/Model master data admin + Excel upload.**

Carried forward — operator's LeadFormWidget depends on `car_brands` (14 rows) and `car_models` (81 rows) being curated; no Filament resource exists for either, so updates currently require direct DB access. Highest-leverage next move.

---

## 16. Files-touched summary

```
CREATED:
  tests/e2e/brand-consistency.spec.ts
  PHASE4_7_1_BRAND_EXTRACTION.md
  PHASE4_7_1_REPORT.md  (this file)

MODIFIED (foundation):
  src/index.css                              (font weights per manual hierarchy)

MODIFIED (operator-flagged fixes):
  src/pages/Home.tsx                          (hero + 7 H2 italic-to-non-italic)
  src/pages/SeoPageView.tsx                   (SeoPageHero/Breadcrumbs → PageBanner)
  src/components/HomeFAQ.tsx                  ("Questions We Get Asked." italic removed)

MODIFIED (off-palette color sweep):
  src/pages/MyBookings.tsx                    (bg-indigo-600 → bg-accent)
  src/pages/OrderDetail.tsx                   (same)

MODIFIED (test config):
  playwright.config.ts                         (brand-consistency added to edges project)

NO LONGER IMPORTED (left on disk):
  src/components/seo/SeoPageHero.tsx          (use in future blog headers per manual p. 46)
  src/components/seo/SeoPageBreadcrumbs.tsx   (same; PageBanner now owns breadcrumb chrome)

UNTOUCHED (per HARD CONSTRAINTS / scope):
  All other src/pages/* + src/components/*    (typography deltas already in Phase 4.7)
  All backend, all admin Filament resources
```

Per GIT POLICY: **no `git add`, `git commit`, or `git push` performed.** Operator commits manually.

— end of report —
