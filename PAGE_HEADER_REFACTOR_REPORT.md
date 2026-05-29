# Page header refactor — Phase 1 + Phase 2 (low-risk migration)

**Status:** Complete. TypeScript clean (only 2 pre-existing
brand-typography errors), build green (7.17 s), Playwright smoke
3/3 pass.

---

## 0. Brief reconciliation (pre-flight findings)

Three brief assumptions didn't match the actual codebase. Resolved before any code:

| Brief | Reality | Resolution |
|---|---|---|
| `src/components/layout/PageBanner.tsx` | Actually `src/components/PageBanner.tsx` (not in `layout/` subdir) | Used the correct path silently |
| Tailwind tokens `acr-blue` / `acr-blue-700` | Project uses `primary` (#1F4FA3 = ACR blue) / `primary-dark` | Substituted `bg-primary` / `text-primary` / `hover:bg-primary-dark` throughout |
| Phase 2 targets `/privacy`, `/terms`, `/faqs` | **None of those pages exist** in `src/pages/` | Asked operator → locked alternates: **About + Gallery + Sitemap** (all real Type-B PageBanner consumers per the earlier plan's Batch 1) |

## 1. Files created

| Path | Role |
|---|---|
| `src/components/layout/Breadcrumb.tsx` | Semantic `<nav><ol>` breadcrumb. `mobileMode="full"` (default) wraps the full chain on mobile; `mobileMode="back"` collapses to a `‹ Parent` link on `<lg`. Last crumb is `aria-current="page"`, non-clickable, `text-neutral-900 font-medium`. `›` separator with `role="presentation" aria-hidden`. |
| `src/components/layout/PageHeader.tsx` | The canonical contextual page header. `bg-white` default / `bg-neutral-50` subtle variant. `border-b border-neutral-200`. `py-8 md:py-10 lg:py-12` (~120 px desktop, ~80 px mobile). Title `text-2xl md:text-3xl lg:text-4xl font-bold`. Optional subtitle `text-base md:text-lg text-neutral-600 max-w-2xl`. Optional right-aligned CTA (anchor or button). `align="left"` (default) or `align="center"`. NO motion, NO cinematic image, NO inline animation. |

## 2. Files modified

| Path | Change |
|---|---|
| `src/components/PageBanner.tsx` | Added a 22-line `@deprecated` JSDoc block above the export. Documents Type A vs Type B/C split, points consumers at `PageHeader`, lists the 5 surfaces where PageBanner intentionally stays (homepage / service detail / service category / location landing / explore / future campaigns). **Zero behavior change.** |
| `src/pages/About.tsx` | Swapped `import PageBanner` → `import PageHeader`. Replaced `<PageBanner>` with `<PageHeader>`, added subtitle *"Who we are and what we stand for."*, converted `breadcrumbs: { onClick: () => navigate("/") }` to `{ href: "/" }`. Default variant (flat). |
| `src/pages/Gallery.tsx` | Same migration shape. Subtitle *"Real work from our service centres."* Default variant. |
| `src/pages/Sitemap.tsx` | Same. Subtitle *"Every page on this site."* Used `variant="subtle"` to differentiate the utility/index page from About + Gallery (which are marketing-tone content). |

## 3. Migration coverage

| Phase | Status |
|---|---|
| **Phase 1** — Build `<PageHeader>` + `<Breadcrumb>` + deprecate `<PageBanner>` | ✅ Complete |
| **Phase 2** — Migrate 3 low-risk pages (About + Gallery + Sitemap) | ✅ Complete |
| **Phase 3** — Migrate remaining 13 Type-B pages | 🟡 Pending — separate task. Recommended order in §9. |
| Type-A pages (Home / ServiceCategory / ServiceDetail / ServiceCenterDetail / ExploreEditorial / CmsPage / SeoPageView) | ✅ Unchanged — keep current cinematic hero |

## 4. Visual verification (operator-facing)

**Header height comparison** (estimated, per page):

| Page | Before (`PageBanner`) | After (`PageHeader`) | Reduction |
|---|---|---|---|
| About | ~40vh (≈ 320 px on 800-tall viewport) | ~120 px desktop / ~88 px mobile | **~62–73 %** |
| Gallery | Same as above | Same as above | Same |
| Sitemap | Same | Same | Same |

**On the homepage and service-detail pages: no change.** PageBanner is untouched as a component; the only edit was the `@deprecated` JSDoc block which is comment-only.

## 5. Type-A pages confirmed unchanged

`grep -rln "from .*PageBanner" src/` after this pass returns 20 files including:

| Page | Why preserved |
|---|---|
| `ServiceCategory.tsx` | Type-A — category landing, cinematic hero earns the space |
| `ServiceDetail.tsx` | Type-A — service marketing surface, PricingWidget below relies on the visual hierarchy |
| `ServiceCenterDetail.tsx` | Type-A — local landing for SEO/Maps traffic |
| `ExploreEditorial.tsx` | Type-A — magazine-cover editorial flavor |
| `CmsPage.tsx` | Contract-frozen (per memory) — DO NOT touch |
| `SeoPageView.tsx` | Contract-frozen — DO NOT touch |
| `ServiceCenters.tsx`, `Services.tsx`, `Cart.tsx`, `Checkout.tsx`, `BookingConfirmation.tsx`, `MyBookings.tsx`, `OrderDetail.tsx`, `Contact.tsx`, `Insurance.tsx`, `Testimonials.tsx`, `Corporate.tsx`, `Coupons.tsx`, `Offers.tsx`, `NotFound.tsx` | Type-B / C — **scheduled for Phase 3** (separate task) |

Home page hero: still uses its bespoke navy-bleed split with BookingSidebar — not a PageBanner consumer at all. No risk of disturbance.

## 6. Verification results

| Check | Result |
|---|---|
| `npx tsc --noEmit` | Only the 2 pre-existing `brand-typography.spec.ts` errors. **Zero new errors.** ✓ |
| `npm run build` | ✓ 7.17 s. `index.js` 195.67 kB / 54.01 kB gzip. |
| Bundle delta (vs pre-pass 195.44 kB / 53.99 kB) | **+230 bytes raw / +20 bytes gzip.** Adding two small components (Breadcrumb + PageHeader) with three consumers swapped — net near-zero footprint change. |
| `npx playwright test --project=smoke` | 3/3 pass (home renders without console errors · login modal opens · /payment routes to NotFound) ✓ |

## 7. Operator browser-verify checklist

```sh
npm run dev
# open http://localhost:3000
```

**Type-A pages (should look exactly as before):**
- [ ] `/` — homepage hero with navy bleed + BookingSidebar in right column (unchanged).
- [ ] `/services/<any-category>/<any-service>` — service detail page with cinematic PageBanner header (unchanged).
- [ ] `/category/<any-slug>` — category landing PageBanner (unchanged).

**Type-B migrated pages (NEW slim header):**
- [ ] `/about` — slim white header with "Home › About" breadcrumb, "About Us" title, "Who we are and what we stand for." subtitle. No 40vh banner. Content starts right below.
- [ ] `/gallery` — slim white header with "Home › Gallery" + "Gallery" title + "Real work from our service centres." subtitle.
- [ ] `/sitemap` — slim **subtle (light-gray)** header (`variant="subtle"`) with "Home › Sitemap" + "Sitemap" title + "Every page on this site." subtitle.

**Type-B NOT migrated yet (still on cinematic PageBanner — OK):**
- [ ] `/contact` — old PageBanner still there.
- [ ] `/services` — old PageBanner still there.
- [ ] `/insurance`, `/testimonials`, `/corporate`, `/coupons`, `/offers`, `/service-centers`, `/cart`, `/checkout`, `/my-bookings` — all still on old PageBanner. **This is expected** until Phase 3 ships.

**Mobile (375 px viewport in DevTools):**
- [ ] `/about` mobile header compact (~80 px tall), breadcrumb readable, title + subtitle stack cleanly.
- [ ] `/gallery` same.
- [ ] `/sitemap` same.
- [ ] Tap "Home" breadcrumb on any of the three — navigates to `/` via react-router (no full page reload).

## 8. Visual delta summary

| | Before (PageBanner on `/about`) | After (PageHeader on `/about`) |
|---|---|---|
| Background | Dark `bg-neutral-900/80` overlay on Unsplash image | Flat `bg-white` |
| Height | `h-[40vh] min-h-[300px]` ≈ 300–432 px | `py-8 md:py-10 lg:py-12` ≈ 96–144 px |
| Motion | `motion.div` fade-in + slide-up | None (calm by design) |
| Title color | White | `text-neutral-900` |
| Title size | `.page-title` clamp (large) | `text-2xl md:text-3xl lg:text-4xl` |
| Breadcrumb | All-caps `text-white/50`, `/` separator, `<span>`-only (no semantic markup) | Mixed-case `text-neutral-600`, `›` separator, semantic `<nav><ol>` with `aria-current` |
| Subtitle | Not supported | Optional, `text-neutral-600 max-w-2xl` |
| Mobile feel | Eats ~40 % of viewport above any content | Eats ~10 % of viewport — content visible immediately |
| Content-first reading | Fights for attention with the banner | Wins immediately |

## 9. Next-phase recommendation (Phase 3 — separate task)

**Migrate remaining 13 Type-B pages.** Suggested order (lowest risk first):

| Batch | Pages | Notes |
|---|---|---|
| **3a — Content / listings** | `Contact.tsx`, `Coupons.tsx`, `Offers.tsx`, `Insurance.tsx`, `Testimonials.tsx` | Content + listing. Subtitles per the plan doc's D-PHR-5 reference. |
| **3b — Corp + catalog** | `Corporate.tsx`, `ServiceCenters.tsx`, `Services.tsx` | `Services.tsx` is the highest-traffic. Extra verify on its sticky chrome (`SECTION_NAV_OFFSET_PX = 112`, `STICKY_OFFSET_PX = 180`). |
| **3c — Transactional** (`density="compact"` if added) | `Cart.tsx`, `Checkout.tsx`, `BookingConfirmation.tsx`, `MyBookings.tsx`, `OrderDetail.tsx` | Revenue-critical — exercise the place-order flow end-to-end after the swap. |
| **3d — Special** | `NotFound.tsx` | Drop the header entirely; replace with a centered 404 layout. Not a `PageHeader` consumer. |

After Phase 3, `grep "from .*PageBanner"` should return only **6 files**: ServiceCategory + ServiceDetail + ServiceCenterDetail + ExploreEditorial + CmsPage + SeoPageView. At that point Phase 4 (PageBanner rename or delete) becomes possible.

## 10. Deviations

1. **3 silent fixes vs the literal brief** — wrong PageBanner path, wrong color token (`acr-blue` → `primary`), non-existent target pages. All flagged in §0 + addressed by operator-confirmed substitution (About + Gallery + Sitemap).
2. **`navigate` left as unused import** in `About.tsx` + `Gallery.tsx`. The original PageBanner usage relied on `onClick: () => navigate("/")` for the Home crumb; the new `PageHeader` uses `href: "/"` which `<Link>` consumes directly. Project's `tsconfig.json` has no `noUnusedLocals`, so TS doesn't error. Left in scope for minimal-diff (matches prior pattern from the earlier polish + hero passes). `Sitemap.tsx`'s `navigate` IS still used elsewhere — kept.
3. **`Sitemap.tsx` uses `variant="subtle"`** while About + Gallery use the default. Judgment call: Sitemap is a utility/index page, the light-gray background flags it as a different kind of surface vs the marketing-tone pages. Operator may revert to default — single prop change.
4. **Subtitle wording** is my proposed default per the plan doc — operator may adjust during browser-verify; no architecture impact.
5. **`Breadcrumb` uses `<ol>` semantic markup** instead of the brief's `<span>` pattern. Brief had spans + Fragment; I upgraded to proper `<nav aria-label="Breadcrumb">` → `<ol>` → `<li>` per accessibility best practice. Same visual output, better assistive-tech behavior.
