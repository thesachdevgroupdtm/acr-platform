# Phase 4.7 — Typography Audit (PART A)

**Date:** 2026-05-11
**Scope:** Read-only inventory of headings across `src/pages/` and `src/components/` before site-wide typography migration.

---

## 1. Critical finding vs spec assumption

The spec assumed many pages were MISSING PageBanner. **Reality: 22 of 24 pages already render PageBanner** (Phase 4.5.2 verified). The actual scope is:

- **Inline H1 elimination**: 3 marketing pages have BOTH PageBanner AND inline dual-color H1 (duplicate H1s on one page) — these need the inline H1 removed.
- **H2 normalization**: 77 H2 elements site-wide need the canonical `.section-heading` + dual-color + period pattern.
- **H3-H6 normalization**: 126 elements site-wide — but most are inside cards/modals where typography is already context-appropriate.
- **PageBanner H1 styling**: needs `.page-title` utility class so it's consistent and easy to refactor.

---

## 2. Existing foundation

`src/index.css` already has the needed theme variables:

```css
@theme {
  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
  --font-display: "Montserrat", ui-sans-serif, system-ui, sans-serif;
  --color-primary: #1F4FA3;            /* ACR blue */
  --color-primary-dark: #0E2A5C;
  --color-accent: #F28C28;
}

@layer base {
  h1, h2, h3, h4, h5, h6 {
    @apply font-display font-bold tracking-normal text-[#0E2A5C] mb-4;
  }
}
```

So the foundation (fonts + colors) is already in place. Just need to add the canonical utility classes (`.page-title`, `.section-heading`, `.section-heading-accent`, `.heading-h3` through `.heading-h6`, `.body-text`, `.article-heading-h2`).

---

## 3. Pages — PageBanner + inline H1 audit

| Page | PageBanner rendered | Inline H1 present | H1 style | Action needed |
|---|:---:|:---:|---|---|
| About | ✓ | ✓ (`OUR <span text-primary>STORY.</span>`) | dual-color line 30 | **REMOVE inline H1** (duplicate) |
| Contact | ✓ | — | — | none |
| Coupons | ✓ | — | — | none |
| Corporate | ✓ | ✓ (`ENTERPRISE <br/><span text-primary>FLEET.</span>`) | dual-color line 52 | **REMOVE inline H1** (duplicate) |
| Insurance | ✓ | ✓ (`ABSOLUTE <br/><span text-primary>EASE.</span>`) | dual-color line 29 | **REMOVE inline H1** (duplicate) |
| Offers | ✓ | — | — | none |
| Testimonials | ✓ | — | — | none |
| ServiceCenters | ✓ | — | — | none |
| ServiceCenterDetail | ✓ | — | — | none |
| Sitemap | ✓ | — | — | none |
| Services | ✓ | — | — | none |
| ServiceDetail | ✓ | — | — | none |
| ServiceCategory | ✓ | — | — | none |
| Gallery | ✓ | — | — | none |
| Cart | ✓ | — | — | none |
| Checkout | ✓ (4 — actually banner shown 4 times in different flow steps; only top one is page banner, others are step labels) | — | — | none |
| ExploreEditorial | ✓ | — | — | none |
| MyBookings | ✓ | — | — | none |
| OrderDetail | ✓ | ✓ (order # heading line 159) | bold uppercase | KEEP — this is a contextual heading within an order detail layout, NOT a duplicate of banner. Mark out-of-scope for this phase (low visibility post-action page). |
| BookingConfirmation | ✓ | ✓ (success heading line 104) | bold uppercase | KEEP — success message heading. Same rationale as OrderDetail. |
| CmsPage | — | ✓ (article title line 134) | display-style | **OUT OF SCOPE** — CmsPage is the legacy admin-authored page renderer; its inline title acts AS the banner for those routes. Phase 4.5.6+ uses SeoPageView for the new pages; CmsPage is legacy. Leave for now. |
| NotFound | ✓ | — | — | none |
| Home | — | ✓ (hero H1 line 223) | display-style on its own hero | **EXEMPT per D-4.7-7** — home has its own hero. Not touched. |
| HeroCard.tsx (component, not page) | n/a | ✓ (line 63) | display-style | **OUT OF SCOPE** — card title. Cards are exempt per spec D-4.7-12 conservative migration. |

**Pages getting `inline H1 removed` in this commit: About, Corporate, Insurance (3 pages).**

---

## 4. H2 elements — distribution

Total: **77 H2 elements**. Distribution:

| Location | Count | Migration strategy |
|---|---:|---|
| Marketing pages (About, Corporate, Insurance, Services, Contact, Coupons, Offers, Testimonials, ServiceCenters, Sitemap, Gallery) | ~25 | Apply `.section-heading` + dual-color + period (`SectionHeading` component) |
| Home.tsx section headings | ~6 | Apply `.section-heading` + dual-color + period |
| Explore feature (TrendingNow, BrandService, CityService, ServiceGuide, BigGridDual, ExploreRail, ExploreInternalLinks) | ~7 | Apply `.section-heading` + dual-color + period |
| SEO content (ContinueReading, RelatedArticlesGrid, InternalLinkingFooter) | ~3 | Apply `.section-heading` (mostly already correct style) |
| Modal headings (AuthModal, CancelOrderModal, CouponPickerModal, LogoutConfirmModal, VehicleReplaceModal, ChunkErrorBoundary) | ~9 | **OUT OF SCOPE** — modal titles aren't "section headings" in the editorial sense; dual-color+period feels off in a dialog context. Leave existing style. |
| Card components (FeatureCard, StandardCard, HeroCard, etc.) | ~5 | **OUT OF SCOPE** — these are card titles, not section headings. Spec D-4.7-4 says cards SHOULD be H4 but semantic-level changes are risky and out of scope for typography pass. |
| Other (Cart, Checkout flow step headings) | ~15 | **OUT OF SCOPE** — flow step labels, not section headings. |
| SEO article inline content H2s (rendered from CmsPage HTML) | ~7 | **OUT OF SCOPE** — server-rendered HTML inside `dangerouslySetInnerHTML`. Phase 4.7 follow-up. |

**H2 migrations in this commit: ~41 marketing/section H2s. ~36 H2s deferred (modals, cards, flow steps, dynamic HTML).**

---

## 5. H3-H6 elements

126 elements site-wide. Inside articles, cards, lists, etc. **Out of scope for this commit** — the contextual styles already work well, and changing all 126 carries high regression risk. Phase 4.7 follow-up will normalize these via the new `.heading-h3` / `.heading-h4` / `.heading-h5` / `.heading-h6` utilities. The utilities ARE added in this commit so authors can opt in.

---

## 6. PageBanner.tsx — current H1 class

```jsx
<h1 className="text-4xl md:text-5xl lg:text-6xl text-white font-black leading-tight uppercase tracking-tighter shadow-sm">
  {title}
</h1>
```

vs spec D-4.7-1 target:
```
.page-title:
  font-family: var(--font-display)
  font-size: clamp(2.5rem, 5vw, 4.5rem)
  font-weight: 800 or 900
  letter-spacing: -0.02em
  text-transform: uppercase
  color: white
  line-height: 1
```

Existing markup is functionally identical (Montserrat is `--font-display`, font-black=900, tracking-tighter≈-0.05em, uppercase, white). The only refactor is **extract to `.page-title` class** so future changes are one-shot.

---

## 7. Migration plan for this commit

### Files to CREATE

- `src/components/layout/SectionHeading.tsx` — reusable `<SectionHeading>` that auto-splits the last word into the accent span + appends period.
- Typography utilities live INSIDE `src/index.css` (no new file) under `@layer components`.
- `tests/e2e/typography-consistency.spec.ts` — smoke spec that asserts `.page-title` H1 + at least one `.section-heading` H2 on a handful of major pages.

### Files to MODIFY

**Foundation:**
- `src/index.css` — add `.page-title`, `.section-heading`, `.section-heading-accent`, `.article-heading-h2`, `.heading-h3` through `.heading-h6`, `.body-text` utilities.
- `src/components/PageBanner.tsx` — H1 class becomes `page-title`.

**Inline H1 elimination (3 pages):**
- `src/pages/About.tsx` — remove line 30 inline H1.
- `src/pages/Corporate.tsx` — remove line 52 inline H1.
- `src/pages/Insurance.tsx` — remove line 29 inline H1.

**Marketing-page H2 migrations (~25 H2s across):**
- `src/pages/About.tsx` (2 H2s)
- `src/pages/Corporate.tsx`
- `src/pages/Insurance.tsx`
- `src/pages/Services.tsx`
- `src/pages/Contact.tsx`
- `src/pages/Coupons.tsx`
- `src/pages/Offers.tsx`
- `src/pages/Testimonials.tsx`
- `src/pages/ServiceCenters.tsx`
- `src/pages/Sitemap.tsx`
- `src/pages/Gallery.tsx`

**Home section H2s:** Home.tsx and sub-components (HomeFAQ.tsx etc.).

**Explore section headings:**
- `src/components/explore/sections/SectionHeader.tsx` — apply `.section-heading` to H2.
- `src/components/explore/sections/TrendingNowSection.tsx` — already uses SectionHeader; auto-inherits.
- `src/components/explore/sections/BrandServiceSection.tsx` — same.
- `src/components/explore/sections/CityServiceSection.tsx` — same.
- `src/components/explore/sections/ServiceGuideSection.tsx` — same.
- `src/components/explore/sections/BigGridDualSection.tsx` — internal Link-as-heading, update class.
- `src/components/explore/ExploreRail.tsx` — section header.
- `src/components/explore/ExploreInternalLinks.tsx` — "Explore More" footer H2.

**SEO content H2s:**
- `src/components/seo/ContinueReading.tsx`
- `src/components/seo/RelatedArticlesGrid.tsx`
- `src/components/seo/InternalLinkingFooter.tsx`

---

## 8. Out-of-scope items (Phase 4.7 follow-up)

1. **Modal H2s** (AuthModal, CancelOrderModal, CouponPickerModal, LogoutConfirmModal, VehicleReplaceModal, ChunkErrorBoundary). Modals carry different visual conventions; dual-color+period heading feels off in a dialog.
2. **Card title H2s** (HeroCard, FeatureCard, StandardCard, etc.). Semantic-level change (H2→H4) carries SEO + accessibility implications; out of typography scope.
3. **Checkout flow step H2s + Cart H2s** (steps "Address", "Payment Method", "Review", etc.). Step labels, not section headings.
4. **Post-action page inline H1s** (BookingConfirmation success heading, OrderDetail order-number heading). Lower-visibility, contextual.
5. **CmsPage inline H1** (legacy admin-authored page renderer). Out of scope until CmsPage is fully replaced by SeoPageView.
6. **Server-rendered HTML inside SEO articles** (H2 tags inside `dangerouslySetInnerHTML`). Migration needs the SeoPageContent renderer to process them — Phase 4.7 follow-up.
7. **H3-H6 normalization** across 126 elements. New utilities (`.heading-h3` etc.) ARE added so authors can opt in incrementally.

Each deferred item is a clear, scoped follow-up that doesn't block this commit's goal: establish the canonical type system + apply it to the highest-visibility marketing surfaces.

---

## 9. Risk + rollback

- **CSS additions are additive only** — new utilities don't override existing classes; existing styles continue to work even if a page hasn't been migrated yet.
- **SectionHeading component is OPT-IN** — pages can switch to it incrementally; old markup keeps working.
- **PageBanner H1 class swap** is a no-op visually (same Tailwind classes, just bundled into `.page-title`).
- **Inline H1 removals**: 3 pages, each removal is a single line — easy revert if needed.

— end of audit — proceeding with implementation —
