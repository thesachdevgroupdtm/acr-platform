# TASK: Global page-header refactor — replace heavy PageBanner with lightweight `<PageContextHeader>` on 16 Type-B pages

Read `/PAGE_HEADER_REFACTOR_PLAN.md` first for the full architecture
reasoning, page classification, and rollout phasing. This brief is the
execution prompt — scoped to the build + the 16-page migration.

---

## ARCHITECTURAL CONTEXT

* `src/components/PageBanner.tsx` (86 lines) is a `h-[40vh] min-h-[300px]` cinematic banner used by 22 pages. Operator wants the thick banner gone from non-hero pages.
* The plan classifies pages into Type A (keep cinematic hero), Type B (slim PageContextHeader), Type C (no header). This brief executes the Type B migration.
* Two pages are contract-frozen and MUST NOT BE TOUCHED: `src/pages/CmsPage.tsx`, `src/pages/SeoPageView.tsx`.
* Two more pages stay on the current PageBanner per the plan (Type A): `ServiceCategory.tsx`, `ServiceDetail.tsx`, `ExploreEditorial.tsx`, `ServiceCenterDetail.tsx`. Plus the Home page already has its own bespoke hero (no PageBanner).
* One page is Type C: `NotFound.tsx` (no header at all — bespoke 404 layout).
* The remaining 16 pages are Type B and migrate to the new `<PageContextHeader>` in this brief.

## LOCKED DECISIONS

### D-PHR-1: Component location + name

* New file: `src/components/layout/PageContextHeader.tsx`
* Lives in `src/components/layout/` (alongside the existing `SectionHeading.tsx`).
* Export default. Named export of the props interface for consumers that type-check the call site.

### D-PHR-2: Public API (locked)

```ts
interface BreadcrumbItem {
  label: string;
  to?: string;          // react-router path — preferred
  onClick?: () => void; // fallback for non-router actions
}

interface PageContextHeaderProps {
  title: string;
  subtitle?: string;
  breadcrumbs: BreadcrumbItem[];
  eyebrow?: string;
  action?: {
    label: string;
    to?: string;
    onClick?: () => void;
    icon?: LucideIcon;
  };
  variant?: 'flat' | 'tinted';        // default 'flat'
  density?: 'comfortable' | 'compact'; // default 'comfortable'
}
```

* Breadcrumbs: use real `<nav aria-label="Breadcrumb">` + `<ol>` + `<li>`. Last crumb gets `aria-current="page"` and is non-clickable.
* Breadcrumb separator: `›` (not `/`). Color: `text-neutral-300`.
* When a breadcrumb has `to`, render `<Link to={to}>`. When it only has `onClick`, render `<button onClick={onClick}>`. Never both.
* `subtitle` accepts `string` only (no rich content) — keeps the header reading as one paragraph.

### D-PHR-3: Visual spec (locked)

* Container width: `site-container` (project's existing container utility).
* Variants:
  * `flat`: `bg-white border-b border-border` (default — content-first reading).
  * `tinted`: `bg-surface` (no border — listings / index pages).
* Density (vertical padding):
  * `comfortable` (default): `pt-6 pb-8 md:pt-8 md:pb-10` on `flat`; `pt-7 pb-9 md:pt-9 md:pb-12` on `tinted`.
  * `compact`: `pt-4 pb-5 md:pt-5 md:pb-6`. Use on transactional pages (Cart, Checkout, etc.).
* Layout:
  * Row 1 — breadcrumbs (left) + optional action button (right). `flex items-center justify-between flex-wrap gap-3`. Action hides on `<sm` (use `hidden sm:inline-flex` on the action wrapper) UNLESS it's the page's primary CTA — operator decides per-page when calling.
  * Row 2 — H1 title. `text-2xl md:text-3xl lg:text-4xl font-bold text-neutral-900 tracking-tight leading-tight`. Use `mt-3 md:mt-4` after the breadcrumb row.
  * Row 3 — optional subtitle. `mt-2 md:mt-3 text-sm md:text-base text-neutral-600 leading-relaxed max-w-2xl`.
  * Optional eyebrow: renders BETWEEN breadcrumbs and H1. `mt-3 text-[10px] md:text-xs font-bold uppercase tracking-widest text-primary`.
* No motion / no animation. The component is calm by design.
* Icons: use lucide-react. Only place: optional `action.icon` (rendered as `<Icon className="w-4 h-4" />` after the label).

### D-PHR-4: Breadcrumb rendering rules

* Always render Home as first crumb with `to: '/'` (consumers must include it).
* On `<sm` viewports, if more than 3 crumbs, render `…` for middle ones so the row stays single-line. (Hidden middle crumbs accessible via the truncated `<span>`'s `title` attribute.)
* Last crumb: `aria-current="page"`, `text-neutral-900 font-medium`, non-clickable (`<span>`).
* Non-last crumbs: `text-neutral-500 hover:text-primary transition-colors`.
* Separator: `<li role="presentation" aria-hidden="true" className="text-neutral-300">›</li>` between crumbs.

### D-PHR-5: Migration scope (16 Type-B pages, in 5 batches)

Migrate the following pages in batch order. Each batch is one operator-verified pass.

**Batch 1 (content):** `About.tsx`, `Contact.tsx`, `Gallery.tsx`, `Sitemap.tsx`
**Batch 2 (listings):** `Coupons.tsx`, `Offers.tsx`, `Insurance.tsx`, `Testimonials.tsx`
**Batch 3 (corp / centers / catalog):** `Corporate.tsx`, `ServiceCenters.tsx`, `Services.tsx`
**Batch 4 (transactional, `density="compact"`):** `Cart.tsx`, `Checkout.tsx`, `BookingConfirmation.tsx`, `MyBookings.tsx`, `OrderDetail.tsx`
**Batch 5 (Type C):** `NotFound.tsx` — special case, drop header entirely + write a centered 404 layout. NOT a `PageContextHeader` consumer.

Per-page Edit shape:

```diff
- import PageBanner from "../components/PageBanner";
+ import PageContextHeader from "../components/layout/PageContextHeader";

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

Subtitle wording per page — write something content-first and relevant. Examples:

* Contact: *"Get in touch with the team — usually within an hour."*
* Gallery: *"Real work from our service centers."*
* Sitemap: *"Every page on this site."*
* Coupons: *"Active offers and promo codes."*
* Offers: *"This week's deals across our centers."*
* Insurance: *"Cashless claims with 30+ insurers."*
* Testimonials: *"What customers actually say."*
* Corporate: *"Fleet plans and corporate partnerships."*
* ServiceCenters: *"Find a centre near you."*
* Services: *"Every service we offer — pick what your car needs."*
* About: *"Who we are and what we stand for."*
* Cart: (no subtitle — `compact` density, title alone)
* Checkout: (no subtitle — `compact`)
* BookingConfirmation: (no subtitle — `compact`)
* MyBookings: (no subtitle — `compact`)
* OrderDetail: (no subtitle — `compact`)

Operator may tweak any subtitle wording during browser-verify; treat the list as a default.

### D-PHR-6: Pages NOT to touch (Type A + frozen + already-bespoke)

* `Home.tsx` — bespoke hero, no PageBanner today
* `ServiceCategory.tsx` — keeps current PageBanner
* `ServiceDetail.tsx` — keeps current PageBanner
* `ExploreEditorial.tsx` — keeps current PageBanner (editorial flavor)
* `ServiceCenterDetail.tsx` — keeps current PageBanner (local landing)
* `CmsPage.tsx` — **contract-frozen**, do not touch
* `SeoPageView.tsx` — **contract-frozen**, do not touch

### D-PHR-7: PageBanner deprecation

* Do NOT delete `PageBanner.tsx` in this pass. 5 pages still consume it.
* Add a JSDoc deprecation hint to the top of `PageBanner.tsx`:
  ```ts
  /**
   * @deprecated Use `<PageContextHeader>` from `components/layout` for
   * context/content pages. PageBanner stays for Type-A cinematic
   * surfaces (ServiceCategory, ServiceDetail, ExploreEditorial,
   * ServiceCenterDetail) and for the two contract-frozen pages
   * (CmsPage, SeoPageView).
   */
  ```
* No filename rename in this pass — that's a future task.

### D-PHR-8: GIT POLICY

Operator manages git manually. Do not commit. Per-batch operator review before next batch.

---

## HARD CONSTRAINTS

* DO NOT touch `CmsPage.tsx`, `SeoPageView.tsx`, `ServiceDetail.tsx`, `ServiceCategory.tsx`, `ExploreEditorial.tsx`, `ServiceCenterDetail.tsx`, `Home.tsx`.
* DO NOT modify `PageBanner.tsx` beyond adding the @deprecated JSDoc.
* DO NOT modify backend code.
* DO NOT install new packages.
* DO NOT add new routes.
* DO NOT touch hooks (`useApiQuery`, `useBookingContext`, etc.) — purely a presentation change.
* DO NOT touch `BookingSidebar.tsx`, `PricingWidget.tsx`, `premium-selector/` — those are unrelated to the header refactor.
* DO NOT add motion / animation to `PageContextHeader` — it's deliberately calm.
* DO NOT delete unused imports across pages unless they were ONLY used by the migrated PageBanner block.
* TypeScript: only pre-existing 2 brand-typography errors acceptable.
* All existing Playwright smoke tests must continue passing.

---

────────────────────────────────────────────────────────────
PART A — Build `PageContextHeader` (45 min)
────────────────────────────────────────────────────────────

1. Create `src/components/layout/PageContextHeader.tsx` with the API from D-PHR-2 + visual spec from D-PHR-3.

2. Internal sub-components (in the same file, not exported):
   * `<Breadcrumbs items={…} />` — renders the `<nav><ol>…</ol></nav>` per D-PHR-4.
   * `<HeaderAction action={…} />` — renders the optional right-aligned button.
3. Use `import { Link } from "react-router-dom"` for breadcrumb crumbs that have `to`. For `onClick`-only crumbs, render `<button>`.
4. Use lucide-react `ChevronRight` for the separator if visually preferable to `›` text; default to `›` for lightness.
5. Add a single TSDoc block at the top explaining: this is the canonical lightweight header for Type-B pages; PageBanner stays for Type-A cinematic surfaces.

6. Verify the component renders in isolation:
   * `npx tsc --noEmit` → no new errors.
   * `npm run build` → bundle increases by ~3-4 kB raw / ~1 kB gzip.

7. **DO NOT migrate any consumer in PART A.** PART A ships the component only.

────────────────────────────────────────────────────────────
PART B — Add @deprecated to PageBanner (5 min)
────────────────────────────────────────────────────────────

8. Add the JSDoc deprecation block from D-PHR-7 to `src/components/PageBanner.tsx`. No other change to the file.

────────────────────────────────────────────────────────────
PART C — Batch 1: Content pages (4 pages, 20 min)
────────────────────────────────────────────────────────────

9. Migrate in this order: `About.tsx` → `Contact.tsx` → `Gallery.tsx` → `Sitemap.tsx`. Per page:
   * Replace `<PageBanner …>` with `<PageContextHeader …>` per D-PHR-5 shape.
   * Update import.
   * Replace `onClick: () => navigate("/")` with `to: "/"` in the Home crumb (per D-PHR-4).
   * Add a subtitle from the D-PHR-5 list (or write a new one if context demands).
   * Use default `variant="flat"`, default `density="comfortable"` (don't pass them — defaults).

10. After Batch 1: verify trio.
    ```sh
    npx tsc --noEmit
    npm run build
    npx playwright test --project=smoke
    ```
    Expected: only 2 pre-existing tsc errors, build green, smoke 3/3.

11. Operator browser-verify each of the 4 pages: hard-refresh, confirm header reads correctly, breadcrumbs link, subtitle wording works.

────────────────────────────────────────────────────────────
PART D — Batch 2: Listings (4 pages, 20 min)
────────────────────────────────────────────────────────────

12. Same pattern. Pages: `Coupons.tsx`, `Offers.tsx`, `Insurance.tsx`, `Testimonials.tsx`.

13. Consider passing `variant="tinted"` to differentiate listings from content pages — but only if visually warranted (operator may revert). Default to `flat` if unsure.

14. Verify trio + operator browser-verify.

────────────────────────────────────────────────────────────
PART E — Batch 3: Corp / Centers / Catalog (3 pages, 25 min)
────────────────────────────────────────────────────────────

15. Pages in order: `Corporate.tsx` → `ServiceCenters.tsx` → `Services.tsx`.

16. **`Services.tsx` is the highest-traffic page** with sticky chrome (`SECTION_NAV_OFFSET_PX = 112`, `STICKY_OFFSET_PX = 180`). Critical verifications:
    * Confirm the new header (~120 px) plus the sub-nav (~52 px) still clears the sticky offsets correctly.
    * Test at 375 px / 1024 px / 1440 px viewports manually.
    * Booking-context-driven price reveal on the service cards still fires correctly after the swap.

17. Verify trio + extra operator browser-verify for `Services.tsx`.

────────────────────────────────────────────────────────────
PART F — Batch 4: Transactional pages, `compact` density (5 pages, 25 min)
────────────────────────────────────────────────────────────

18. Pages in order: `Cart.tsx` → `Checkout.tsx` → `BookingConfirmation.tsx` → `MyBookings.tsx` → `OrderDetail.tsx`.

19. Pass `density="compact"` to each.

20. Omit the subtitle on these — title alone keeps the focus on the form / order content below.

21. Critical: Checkout flow is revenue-critical. Operator must exercise the full place-order path end-to-end:
    * Add item to cart → cart page renders with new header → checkout page renders with new header → place order → booking confirmation renders with new header.
    * No console errors, no layout shift breaking the place-order button.

22. Verify trio + manual Checkout flow.

────────────────────────────────────────────────────────────
PART G — Batch 5: NotFound (1 page, 15 min)
────────────────────────────────────────────────────────────

23. `NotFound.tsx` — special case. Remove `<PageBanner>` entirely. Replace with a centered 404 layout. Suggested structure:
    ```jsx
    <div className="min-h-[60vh] flex items-center justify-center px-4 py-16">
      <div className="text-center max-w-md">
        <p className="text-[10px] md:text-xs font-bold uppercase tracking-widest text-primary mb-4">
          404 · Page not found
        </p>
        <h1 className="text-3xl md:text-4xl font-bold text-neutral-900 mb-4">
          We couldn't find that page.
        </h1>
        <p className="text-sm md:text-base text-neutral-600 mb-8">
          The URL might be old, mistyped, or the page may have moved.
        </p>
        <Link to="/" className="inline-flex items-center gap-2 ...">
          Go home
          <ArrowRight className="w-4 h-4" />
        </Link>
      </div>
    </div>
    ```

24. Verify smoke test for `/payment` (already covers NotFound routing) still passes.

────────────────────────────────────────────────────────────
PART H — Final verification + report (15 min)
────────────────────────────────────────────────────────────

25. Full verification trio one final time after all batches.

26. Grep audit:
    ```sh
    grep -rln "from.*['\"].*/components/PageBanner['\"]" src/
    ```
    Expected remaining consumers: `ServiceCategory.tsx`, `ServiceDetail.tsx`, `ExploreEditorial.tsx`, `ServiceCenterDetail.tsx`, `CmsPage.tsx`, `SeoPageView.tsx` (6 pages — all Type A or frozen).

27. Output report at `/PAGE_HEADER_REFACTOR_REPORT.md` with:
    1. Files created: `src/components/layout/PageContextHeader.tsx`
    2. Files modified: 16 pages (Batches 1-4) + `NotFound.tsx` (Batch 5) + `PageBanner.tsx` (deprecation JSDoc)
    3. Files preserved with PageBanner: list of 6 Type-A / frozen pages
    4. Per-batch verify results
    5. Bundle delta (raw + gzip)
    6. TypeScript: 2 pre-existing errors only
    7. Build: ✓
    8. Smoke: 3/3 ✓
    9. Operator browser-verify checklist (one bullet per page touched)
    10. Deviations (if any) — especially around subtitle wording choices, any variant=tinted decisions, and any per-page surprises that needed in-line judgment

Stop after the report. Operator does final visual sweep across all 16 pages.

---

## EXPECTED OUTCOMES

* **PageBanner footprint reduced**: 22 consumers → 6 consumers (Type A + frozen).
* **Above-fold content gain**: 16 pages reclaim ~200-300 px of vertical space each.
* **Mobile improvement**: 40vh banner (=~320 px on a 800-tall phone) → ~80 px header. 4× more content above the fold on mobile.
* **Bundle delta**: +3-4 kB raw / +1 kB gzip for `PageContextHeader`. `PageBanner` stays (still has consumers). Net ≈ +1 kB gzip.
* **No regression**: all 3 smoke tests stay green throughout. No backend changes. No new packages. No new routes. Hooks untouched. PricingWidget / BookingSidebar / premium-selector untouched.
