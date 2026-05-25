# Global Page Header Refactor Plan

**Status:** Plan only — no code changes here. Companion implementation
prompt lives in `/PAGE_HEADER_REFACTOR_PROMPT.md`.

---

## 0. Current state (audit)

| Asset | Reality |
|---|---|
| `src/components/PageBanner.tsx` | 86 lines. `h-[40vh] min-h-[300px]` cinematic banner with hardcoded Unsplash background, `bg-neutral-900/80` overlay, breadcrumbs, title (uses `.page-title` Phase 4.7 utility), optional eyebrow `label`, optional `children` slot. |
| Consumers | **22 pages** import + mount `<PageBanner>` (23 files total, one is PageBanner itself). |
| Contract-frozen consumers | `CmsPage.tsx`, `SeoPageView.tsx` — DO NOT touch in any pass. |
| Typical usage | Simple: `title="…"` + 2-item `breadcrumbs=[Home, X]`. No `label`, no `children` on most pages. |
| Variations | ServiceDetail uses 3-level breadcrumb (Home / Category / Service). A few pass `label` or `children`. None pass custom `backgroundImage` in the sample we read. |
| Mobile behaviour | Same 40vh height — feels disproportionate on small screens because it eats half the viewport before any content. |

**Problem statement (operator's words):**
* Banner height excessive
* Repetitive cinematic headers → visual fatigue
* Pages feel template-like
* Real hero sections (homepage, service-detail marketing) become duplicated / conflicting with the banner
* Future custom-hero pages will hit double-banner hierarchy collisions

---

## 1. Shared lightweight page-header architecture

**New canonical component:** `src/components/layout/PageContextHeader.tsx`

### Public API

```tsx
interface BreadcrumbItem {
  label: string;
  to?: string;          // react-router path (preferred over onClick)
  onClick?: () => void; // fallback for non-router actions
}

interface PageContextHeaderProps {
  /** Page title — H1, semantic. */
  title: string;
  /** Optional subtitle / one-line description. */
  subtitle?: string;
  /** Breadcrumbs (always include Home as first crumb). */
  breadcrumbs: BreadcrumbItem[];
  /** Optional small eyebrow tag (replaces the legacy `label` prop). */
  eyebrow?: string;
  /** Optional right-aligned compact CTA. */
  action?: {
    label: string;
    to?: string;
    onClick?: () => void;
    icon?: LucideIcon;
  };
  /** Visual variant. Default 'flat' for context-page reading flow. */
  variant?: 'flat' | 'tinted';
  /** Tightens vertical padding for transactional pages. */
  density?: 'comfortable' | 'compact';
}
```

### Visual spec

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  Home ›  All Services                              [ See Centers → ]│  ← row 1: breadcrumbs (left) + optional action (right)
│                                                                     │
│  All Services                                                       │  ← row 2: H1 title (text-2xl md:text-3xl, font-bold, neutral-900)
│  Pick what your car needs — we'll quote on the next screen.         │  ← row 3: optional subtitle (text-sm md:text-base, neutral-600)
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
   ↑ ~88-120 px tall on desktop (vs 40vh = ~300-432 px today)
   ↑ on-page content starts immediately below — no banner gap
```

### Density token table

| Density | Vertical padding (desktop) | Vertical padding (mobile) | Use case |
|---|---|---|---|
| `comfortable` (default) | `pt-6 pb-8 md:pt-8 md:pb-10` | `pt-5 pb-6` | About / Contact / Gallery / Corporate / Services |
| `compact` | `pt-4 pb-5 md:pt-5 md:pb-6` | `pt-3 pb-4` | Cart / Checkout / MyBookings / OrderDetail (transactional) |

### Variant table

| Variant | Background | Border-bottom | Use case |
|---|---|---|---|
| `flat` (default) | `bg-white` | `border-b border-border` | Most context pages — content-first reading |
| `tinted` | `bg-surface` | none | Listing / index pages where a faint differentiation helps signpost |

Both variants stay under ~120 px tall. Zero cinematic imagery. Zero motion. Subtle only.

### Composition rules

* Renders inside `site-container` (matches the project's existing container utility).
* H1 uses the project's `.page-title` Phase 4.7 utility **at its smaller end** (`clamp(1.5rem, 3vw, 2.25rem)` instead of the heavier banner sizing). New CSS variant `.page-title-context` introduced if needed; otherwise inline class overrides.
* Breadcrumbs are real `<nav aria-label="Breadcrumb">` with `<ol>` for accessibility.
* Action button is `min-h-[40px]` (smaller than the 44px touch-target rule for hero CTAs — this is secondary navigation, not a primary action).
* Animation: none. The point is to feel calm, not cinematic.

---

## 2. PageBanner deprecation strategy

**Don't delete on day 1.** Coexist during migration.

1. **Phase R1 — additive**: ship `<PageContextHeader>` alongside the existing `<PageBanner>`. No consumer migration yet.
2. **Phase R2 — migrate Type-B consumers**: 16 pages move from `<PageBanner>` to `<PageContextHeader>`. Page-by-page Edits.
3. **Phase R3 — classify Type-A / Type-C**: 6 pages stay on `<PageBanner>` (or get bespoke hero sections). 1-2 pages move to no-header.
4. **Phase R4 — rename, audit, retire**: rename `PageBanner.tsx` → `LegacyCinematicBanner.tsx` (or similar) so any future use is intentional. Or delete if zero consumers remain.

Risk mitigation:
* Each page migration is a single-file Edit. Roll back via `git restore <file>` per page.
* Smoke tests run after every batch.
* Visual regression: operator browser-verifies each batch before the next batch starts.

---

## 3. Breadcrumb redesign

### Current

```jsx
<div className="text-[10px] font-bold uppercase tracking-widest text-white/50">
  <span className="cursor-pointer hover:text-white">Home</span>
  <span className="text-white/30">/</span>
  <span className="text-white">All Services</span>
</div>
```

* All-caps, dark theme only, slash separator, no semantic HTML.

### New

```jsx
<nav aria-label="Breadcrumb" className="text-xs">
  <ol className="flex items-center flex-wrap gap-x-1.5 gap-y-1 text-neutral-500">
    <li>
      <Link to="/" className="hover:text-primary transition-colors">Home</Link>
    </li>
    <li aria-hidden="true" className="text-neutral-300">›</li>
    <li className="text-neutral-900 font-medium" aria-current="page">All Services</li>
  </ol>
</nav>
```

Changes:
* Semantic `<nav>` + `<ol>` for screen readers
* Mixed case (Sentence Case) — matches modern web norms
* `›` separator instead of `/` (lighter, more refined)
* Last crumb non-clickable + `aria-current="page"`
* Color palette: neutral-500 default, neutral-900 for current page, primary on hover
* Uses `<Link>` from react-router for `to` props (no full reload)

### Mobile

Breadcrumbs wrap (`flex-wrap`). On very narrow screens, only show last 2 crumbs:
* Add a `truncate?: boolean` prop to `<PageContextHeader>` (default true on `<sm` breakpoint)
* When truncated: render `… ›  Current Page` with the truncated middle crumbs accessible via `<title>` attribute

---

## 4. Compact premium header layouts (ASCII references)

### `flat` + `comfortable` (default — About, Contact, Services, etc.)

```
┌────────────────────────────────────────────────────────┐  ← bg-white
│                                                        │
│  Home ›  Contact Us                                    │  ← breadcrumb row
│                                                        │
│  Contact Us                                            │  ← H1 (~28-36 px)
│  Get in touch with the team — usually within an hour.  │  ← subtitle (optional, neutral-600)
│                                                        │
├────────────────────────────────────────────────────────┤  ← border-b border-border
│                                                        │
│  [page content starts immediately here]                │
│                                                        │
```

### `tinted` + `comfortable` (listings — Services, Coupons, Offers)

```
┌────────────────────────────────────────────────────────┐  ← bg-surface
│                                                        │
│  Home ›  All Services             [ Find Center → ]    │  ← breadcrumb + action right-aligned
│                                                        │
│  All Services                                          │
│  Pick what your car needs.                             │
│                                                        │
└────────────────────────────────────────────────────────┘
   [page content with bg-white surface change for contrast]
```

### `flat` + `compact` (transactional — Cart, Checkout)

```
┌────────────────────────────────────────────────────────┐  ← bg-white
│  Home ›  Cart                                          │
│  Your Cart                                             │  ← H1 smaller (~22-26 px)
└────────────────────────────────────────────────────────┘
```

### `flat` + eyebrow (occasionally — Insurance, Corporate landing)

```
┌────────────────────────────────────────────────────────┐
│  Home ›  Insurance                                     │
│  ───────────────                                       │  ← eyebrow line "CASHLESS INSURANCE PARTNERS"
│  Insurance Partners                                    │
│  HDFC Ergo · ICICI Lombard · 28 more.                  │
└────────────────────────────────────────────────────────┘
```

---

## 5. Migration strategy — page-by-page

### Classification

| Type | Pages | Header treatment |
|---|---|---|
| **A — Full cinematic hero** (keep large hero) | `Home.tsx` (already custom, no PageBanner) · `ServiceCategory.tsx` · `ServiceDetail.tsx` · `SeoPageView.tsx` (frozen) · `CmsPage.tsx` (frozen) · `ExploreEditorial.tsx` · `ServiceCenterDetail.tsx` | **Keep existing hero / PageBanner.** These are landing + marketing surfaces where the cinematic feel earns its space. (Total: 7 pages.) |
| **B — Context page** (slim PageContextHeader) | `Services.tsx` · `About.tsx` · `Contact.tsx` · `Gallery.tsx` · `Coupons.tsx` · `Offers.tsx` · `Corporate.tsx` · `Insurance.tsx` · `ServiceCenters.tsx` · `Testimonials.tsx` · `Sitemap.tsx` · `Cart.tsx` · `Checkout.tsx` · `BookingConfirmation.tsx` · `MyBookings.tsx` · `OrderDetail.tsx` | **Migrate to `<PageContextHeader>`.** Use `comfortable` density for browse pages, `compact` for transactional. (Total: 16 pages.) |
| **C — No header** (or full-bleed custom) | `NotFound.tsx` | **Drop the banner entirely.** A 404 page deserves a centered "lost" treatment, not a breadcrumb header to a non-existent route. (Total: 1 page.) |

### Migration order (low-risk first)

1. **Batch 1 — high-frequency content browse** (4 pages): `About`, `Contact`, `Gallery`, `Sitemap`. Lowest visual stakes, easiest sanity check.
2. **Batch 2 — listings** (4 pages): `Coupons`, `Offers`, `Insurance`, `Testimonials`.
3. **Batch 3 — corporate / centers** (3 pages): `Corporate`, `ServiceCenters`, `Services`. (`Services` is the most-visited route; ship after batch 1 + 2 validate the pattern.)
4. **Batch 4 — transactional** (4 pages, `compact` density): `Cart`, `Checkout`, `BookingConfirmation`, `MyBookings`, `OrderDetail`. The footprint reduction matters most here — every pixel saved on banner is pixels available for the order form / status.
5. **Batch 5 — special** (1 page): `NotFound` → no-header centered layout.

Each batch is one operator-verified pass. 4-5 small passes total.

### Per-page Edit shape (Batch 1-4)

```diff
- import PageBanner from "../components/PageBanner";
+ import PageContextHeader from "../components/layout/PageContextHeader";

  ...
- <PageBanner
-   title="Contact Us"
-   breadcrumbs={[
-     { label: "Home", onClick: () => navigate("/") },
-     { label: "Contact" }
-   ]}
- />
+ <PageContextHeader
+   title="Contact Us"
+   subtitle="Get in touch with the team — usually within an hour."
+   breadcrumbs={[
+     { label: "Home", to: "/" },
+     { label: "Contact" }
+   ]}
+ />
```

Per-page time budget: 2-5 minutes including the subtitle wording call.

---

## 6. Where cinematic heroes stay vs go

### Stay (TYPE A)

* **Home** — its own custom navy-bleed hero (already not using PageBanner).
* **ServiceCategory** — category landing reads as a marketing surface, deserves the visual weight to differentiate it from the All-Services listing.
* **ServiceDetail** — service-specific hero anchors the product page; PricingWidget below it depends on the visual hierarchy.
* **SeoPageView** — contract-frozen, keep.
* **CmsPage** — contract-frozen, keep.
* **ExploreEditorial** — content/editorial flavor; cinematic banner reads as magazine cover.
* **ServiceCenterDetail** — local landing pages for SEO/Maps traffic; image-rich hero earns its space.

### Future hero pages (Type A new)

* Location landing pages (`/locations/moti-nagar`, `/locations/karol-bagh`)
* Campaign pages (`/diwali-offer`, `/insurance-renewal`)
* These should each build a bespoke hero block — **NOT** reach for the legacy `PageBanner` default.

### Go (TYPE B → PageContextHeader)

* Everything else listed in §5 Batch 1-4.

### Gone (TYPE C)

* `NotFound` — replace banner with a centered SVG + headline + "Go home" CTA.

---

## 7. Spacing hierarchy strategy

Without the 40vh banner eating viewport, the visual rhythm needs re-calibration.

| Surface | Top spacing |
|---|---|
| `<PageContextHeader>` itself | `pt-6 pb-8 md:pt-8 md:pb-10` (comfortable) or `pt-4 pb-5 md:pt-5 md:pb-6` (compact) |
| First content section after header | `pt-0` (no extra top padding — header's `pb-8` is the visual gap) |
| Section-to-section gap on the page body | unchanged from each page's existing rhythm (typically `py-12 md:py-16 lg:py-24`) |
| Mobile-vs-desktop | header `pt`/`pb` halve on `<md` breakpoint |

**Critical rule:** sections that previously assumed a 40vh banner ate the top of the viewport now sit much higher. Pages with sticky chrome (header, sub-nav) should re-check that their first content row isn't slipping under the chrome. `Services.tsx` already uses `SECTION_NAV_OFFSET_PX = 112` and `STICKY_OFFSET_PX = 180` for this — the new `PageContextHeader` height (~120px) plus the header stack still clears.

---

## 8. Mobile behaviour

| Aspect | Behaviour |
|---|---|
| Padding | `pt-5 pb-6` (vs desktop's `pt-6/8 pb-8/10`) |
| Title size | `text-2xl` mobile / `text-3xl md:text-4xl` (vs banner's heavy `.page-title` size) |
| Subtitle | `text-sm` mobile / `text-base md:text-lg` |
| Breadcrumbs | Wrap with `flex-wrap`; truncate middle crumbs on `<sm` viewports to keep the row to 1 line (last 2 crumbs always visible) |
| Action button | Hides on `<sm` if it's a non-essential secondary action; promotes to its own button if essential |
| Total height | `~80 px` mobile (vs `40vh` = `~320 px` on a 800-tall phone). **~4× more above-fold content.** |
| Touch targets | Breadcrumb links `min-h-[36px]` tap targets via padding; action button `min-h-[40px]` |
| Sticky behaviour | Optional — the header is short enough that it can become `sticky top-[112px]` (below the main nav) on long pages without consuming much chrome. **NOT in v1**; future enhancement. |

---

## 9. Low-risk rollout strategy

| Phase | Scope | Risk | Verify |
|---|---|---|---|
| **R0 — Plan** (this doc) | Approval | None | Operator signs off on plan |
| **R1 — Build** | Ship `<PageContextHeader>` to `src/components/layout/`. Zero consumer changes. | None | `tsc`, `npm run build`, smoke 3/3 |
| **R2 — Batch 1** (4 pages: About, Contact, Gallery, Sitemap) | One Edit per page. Per-page subtitle wording. | Low — all are simple content pages | Per-batch: browser smoke each route. Run `npx playwright test --project=smoke`. |
| **R3 — Batch 2** (4 pages: Coupons, Offers, Insurance, Testimonials) | Same | Low | Same |
| **R4 — Batch 3** (3 pages: Corporate, ServiceCenters, Services) | Same. Services is highest-traffic — extra browser verify on its sticky sub-nav. | Medium — Services depends on sticky offset constants | `Services.tsx` sticky chrome stack manual smoke at 375 / 1024 / 1440 px |
| **R5 — Batch 4** (5 transactional pages with `compact` density) | One Edit per page | Low-Medium — Checkout flow is revenue-critical, exercise the place-order path end-to-end | Manual: add-to-cart → cart → checkout → confirmation route fully |
| **R6 — Batch 5** (NotFound) | Bespoke 404 layout (no PageContextHeader, no PageBanner) | Low | Visit `/payment` (existing smoke covers it routes to NotFound) |
| **R7 — Retire** | Rename `PageBanner.tsx` → `LegacyCinematicBanner.tsx`. Re-grep for any lingering imports. Keep file for Type-A consumers OR refactor those to bespoke heroes and delete entirely. | Medium — depends on whether all Type-A consumers want bespoke heroes | Per-consumer browser verify |

**Rollback unit:** one page = one `git restore <file>`. No multi-file revert needed for any single migration.

**Bundle impact:**
* `<PageContextHeader>` adds ~3-4 KB raw (~1 KB gzip).
* `PageBanner` removal (when it happens) saves ~2 KB raw (no `motion`, no inline image URL).
* Net: bundle stays within ±1 KB gzip of current.

---

## Next step — execution

See `/PAGE_HEADER_REFACTOR_PROMPT.md` for the focused Claude Code implementation prompt. The prompt is scoped to:
* Build `<PageContextHeader>`
* Migrate Batches 1-5 (Type-B pages)
* Breadcrumb redesign
* Global layout cleanup

It explicitly excludes:
* Type-A page redesigns (those stay on PageBanner or get separate bespoke-hero phases)
* Backend changes
* Layout architecture rebuilds outside of PageBanner deprecation
