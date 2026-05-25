# Phase 4.5.2 — Audit (PART A)

**Date:** 2026-05-09
**Scope:** Read-only audit before surgical polish.

---

## 1. PageBanner pattern

**Existence:** YES — shared component already exists.

**File:** `src/components/PageBanner.tsx`

**Props (signature is `breadcrumbs` plural):**

```ts
interface BreadcrumbItem {
  label: string;
  onClick?: () => void;     // <- callbacks, NOT `to` paths
}

interface PageBannerProps {
  title: string;
  breadcrumbs: BreadcrumbItem[];
  label?: string;
  backgroundImage?: string; // default unsplash automotive
  children?: ReactNode;
}
```

**Existing usage (22 pages):** Services, About, Contact, Coupons, Insurance, Corporate, Gallery, Testimonials, ServiceCenters, ServiceCenterDetail, ServiceDetail, ServiceCategory, BookingConfirmation, OrderDetail, MyBookings, Cart, Checkout, Sitemap, CmsPage, Offers, NotFound. /explore is the lone editorial page WITHOUT this banner.

**Site title-casing convention:** `Services.tsx` uses `title="Our Services"` — Title Case. `About.tsx` uses `title="About Us"`. So /explore should be `title="Explore"` (NOT `"EXPLORE"` shouted — the component already applies `uppercase tracking-tighter` styling at render time).

**Breadcrumb convention:** breadcrumbs are passed as a list. Last item is non-clickable (no onClick); preceding items get `onClick: () => navigate("/path")`.

**Internal animation:** PageBanner already has a one-shot `motion.div` with `initial={{ opacity: 0, y: 20 }}` → `animate={{ opacity: 1, y: 0 }}`. This is a mount-time animation, not `whileInView`. It's allowed under D-4.5.2-3's spirit (single-shot, fires once on page load) — leaving it untouched.

**Decision: Path A (REUSE).** Import `PageBanner` directly into `ExploreEditorial`. No extraction or refactor needed.

---

## 2. whileInView usage in explore feature

```
src/components/explore/ExploreCard.tsx              line 56     (horizontal layout wrapper)
src/components/explore/ExploreCard.tsx              line 112    (stack layout wrapper)
src/components/explore/ExploreCategorySection.tsx   line 28     (section root motion.section)
src/components/explore/ExploreCategorySection.tsx   line 210    (ListItemCard wrapper)
src/components/explore/ExploreCategorySection.tsx   line 254    (SmallCard wrapper)
src/components/explore/ExploreTrendingGrid.tsx      line 59     (per-card wrapper, with stagger via delay: idx * 0.05)
src/components/explore/ExploreInternalLinks.tsx     line 29     (footer section root)
src/pages/ExploreEditorial.tsx                      line 134    (TrendingGrid header block)
```

8 occurrences across 5 files. All use `viewport={{ once: true, ... }}` with a fade-up `y` transform (12 or 16 or 20 px), some with stagger delays.

**Action plan (PART D):**
- Remove every `whileInView` declaration above.
- Remove `viewport={{ once: true, ... }}` siblings.
- Remove `initial={{ opacity: 0, y: ... }}` siblings — entrance is now the page-level fade.
- Remove `transition` props that exist only to drive the entrance animation. Keep transitions on hover (`transition-all duration-300` Tailwind classes) — those are CSS, not framer-motion.
- Where the wrapper is a `motion.section` / `motion.div` purely for the entrance animation, demote it to a plain `<section>` / `<div>`. One pass strips the `motion.` prefix and removes the now-unused `motion` import.
- Stagger delays (`delay: idx * 0.05` in ExploreTrendingGrid) — gone.

**Page-level fade (per D-4.5.2-3):** wrap the ExploreEditorial return body in a single `motion.div`:

```tsx
<motion.div
  initial={{ opacity: 0 }}
  animate={{ opacity: 1 }}
  transition={{ duration: 0.3, ease: "easeOut" }}
>
  {/* page content */}
</motion.div>
```

The PageBanner sits *outside* this fade so it loads instantly with the page chrome.

**Animations to KEEP (per D-4.5.2-3):**
- Hover-lift on cards (`hover:-translate-y-1 hover:shadow-xl` — Tailwind CSS, not motion)
- Image-scale on hover (`group-hover:scale-[1.03]` — Tailwind CSS)
- Search dropdown enter/exit (`AnimatePresence` in `ExploreSearch` — untouched)
- CategoryFilterChip transitions (CSS only currently)
- PageBanner internal mount fade (one-shot, fires once)
- Modal / accordion / non-explore animations (out of scope)

---

## 3. Scope reconciliation

The HARD CONSTRAINTS section listed `ExploreCard`, `ExploreCategorySection`, `ExploreRail` under "DO NOT touch". PART D explicitly lists `ExploreCategorySection.tsx` and `ExploreTrendingGrid.tsx` as targets for animation removal. Reading the spec as a whole: "DO NOT touch" means "do not refactor structure / behavior / styling"; PART D's animation strip is the explicit exception. Treating the two together: I'll touch those files for the *single specific purpose* of stripping framer-motion entrance animations. No other changes.

`ExploreRail` has zero `whileInView` usages (verified) → no edit needed.

---

## 4. Skeleton implications

`ExploreSkeleton.tsx` currently renders:
- Hero section: full-width 16:9 dark block (matched the old carousel). Will be REPLACED with the 5-card mosaic skeleton.
- A new PageBanner skeleton block goes ABOVE the hero block: dark gray bar ~h-[40vh] min-h-[300px] matching PageBanner's actual dimensions.
- Trending / category / rail skeletons stay.

---

## 5. Files that will change in Phase 4.5.2

```
MODIFY:
  src/components/explore/ExploreFeaturedGrid.tsx     (rewrite: 5-card mosaic)
  src/components/explore/ExploreCard.tsx             (strip whileInView, drop motion import)
  src/components/explore/ExploreCategorySection.tsx  (strip whileInView ×3, demote motion.* tags)
  src/components/explore/ExploreTrendingGrid.tsx     (strip whileInView + stagger, drop motion import)
  src/components/explore/ExploreInternalLinks.tsx    (strip whileInView, demote motion.section)
  src/components/explore/ExploreSkeleton.tsx         (PageBanner placeholder + 5-card mosaic)
  src/pages/ExploreEditorial.tsx                     (PageBanner + page-level fade wrapper)
  tests/e2e/explore-editorial.spec.ts                (4 cards → 5 cards assertion)

CREATE:
  tests/e2e/explore-page-banner.spec.ts              (banner renders with title + breadcrumb)
  PHASE4_5_2_AUDIT.md   (this doc)
  PHASE4_5_2_REPORT.md  (after PART G)

DO NOT TOUCH (per HARD CONSTRAINTS):
  src/components/PageBanner.tsx                      (already used by 22 pages — reuse as-is)
  src/components/explore/ExploreCardFallback.tsx
  src/components/explore/ExploreSearch.tsx
  src/components/explore/CategoryFilterChip.tsx
  src/components/explore/ExploreRail.tsx
  src/components/explore/widgets/*.tsx
  src/pages/SeoPageView.tsx
  src/pages/CmsPage.tsx
  src/pages/Services.tsx (and the other 21 PageBanner consumers)
  any backend file
```

— end of audit —
