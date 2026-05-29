# SERVICE_PAGES_PHASE2_REPORT — Phase 2a (PARTS A + B + C: category page) DONE

GoMechanic-style redesign of the **service CATEGORY page** (`/category/:slug`),
ACR-skinned (blue + Montserrat), reusing existing pieces and shipping image-less
fallbacks. **Stopped at the PART C checkpoint** as planned — the DETAIL page (PART D)
+ smooth-nav (PART E) are Phase 2b.

**Verified:** TSC clean (2 pre-existing only) · Vite build clean · backend **317
passed** (313 + 4 new) · phase2 e2e **3/3** · screenshots captured + visually checked
(desktop 1440 + mobile 390, with/without vehicle).

---

## PART A — backend `inclusions_preview` (DONE)

The category endpoint `GET /api/v1/services/{slug}` (`ServiceController@show`) uses the
full **`ServiceResource`** (not `SubServiceResource` as the brief assumed), so the lean
preview went there. Implemented via the codebase's transient-property + **single bulk
query** pattern (like `resolvedVehiclePrice`) so the full `inclusions[]` stays
detail-only and the list has no N+1:

- `Service::$inclusionsPreview` transient `{labels, total}`.
- `@show` bulk-loads inclusions for the whole category (lean cols), groups in PHP,
  stashes first-4-by-position + total per service.
- `ServiceResource` + `SubServiceResource` emit `inclusions_preview`.

Live proof: `…/services/regular-car-service` → `primary-service.inclusions_preview =
{labels:["Engine Oil Replacement","Oil Filter Replacement","Air Filter Cleaning","Spark
Plug Cleaning"], total:9}`.

Tests `tests/Feature/InclusionsPreviewTest.php` (4): first-4-by-position + total; empty
service → `{labels:[],total:0}`; **exactly one** `service_inclusions` query for 8
services (no N+1); full `inclusions[]` on detail but absent on the list.

---

## PART B — shared building blocks (DONE)

- `src/lib/api.ts` — `SubService` gains `interval_info`, `inclusions_preview`,
  `inclusions[]`; new `InclusionGroup` union + `ServiceInclusionItem` type.
- `src/lib/inclusions.ts` (new) — `groupInclusions()` → ordered Essential/Performance/
  Additional, NULL→Essential, sorted by position, empty groups omitted (ready for 2b).
- `src/components/ServiceMetaRow.tsx` (new) — shared duration/warranty/interval row,
  **non-null only**, collapses to nothing when all missing; `compact` (cards) +
  `detail` (2b strip) variants; ACR blue icons.
- `src/components/explore/ExploreCardFallback.tsx` — generalized with an optional
  `icon` prop so service cards pass a car-relevant lucide icon (backward compatible).

---

## PART C — category page redesign (DONE)

`src/pages/ServiceCategory.tsx`:
- **Leads with the GoMechanic-style service-card list** (D-2-6). Each card =
  image **or `ExploreCardFallback`** (icon-by-category + ACR watermark) · duration pill
  · title · `<ServiceMetaRow>` (duration · warranty · interval, non-null only) ·
  **inclusions_preview** as blue checkmarks (≤4) + "+N more · View All →" to detail ·
  **price column (existing 4-state machine, logic UNCHANGED)** · CTA (Add to Cart /
  Added toggle / Select-Your-Car).
- Sticky category sub-nav (`useSubNavSync`) kept; **`<CarSidebar categorySlug=…>` kept
  mounted, untouched** (prices reveal on vehicle selection exactly as before).
- The static SEO sections (Overview, Services Included, Why Choose, Process, Reviews,
  FAQs, Brands, Why-ACR) are **demoted below** the catalog (D-2-9) — unchanged content.

---

## Screenshots (PART H — captured + visually verified) → `phase2-shots/`

| File | State |
|---|---|
| `category-desktop-vehicle.png` | desktop 1440, vehicle selected (prices + previews) |
| `category-desktop-card.png` | close-up of one card (anatomy proof) |
| `category-desktop-novehicle.png` | desktop 1440, no vehicle (select / fallback state) |
| `category-mobile-vehicle.png` | mobile 390, vehicle selected |

**Card close-up confirms** (desktop): navy fallback panel + wrench icon + "REGULAR CAR
SERVICE" blue badge + "3 HOURS" duration pill + "ACR" watermark (no broken image);
title; meta row "3 hours · Warranty 1000 kms or 1 month · After every 5,000 kms or 3
Months (Recommended)"; 4 blue-checkmark inclusions in 2 cols; "+5 MORE · VIEW ALL →";
₹17999 / ONWARDS; blue **ADD TO CART**. Mobile stacks to a single column cleanly.
No-vehicle state shows the "Select your car" banner + "Select car"/"Select Your Car"
CTAs with the sidebar selector. No empty groups, no broken images, no "undefined".

**Brand check:** ACR Blue `#1F4FA3` (badges, icons, checkmarks, links, CTA), Deep Navy
`#0E2A5C` fallback panel, Workshop-Black titles (Montserrat SemiBold via SectionHeading
+ font-display), muted meta. **Zero GoMechanic red/grey** anywhere.

---

## Test results

```
backend:   ./vendor/bin/pest                 → 317 passed (313 prior + 4 new)
frontend:  npx tsc --noEmit                  → 2 pre-existing only (brand-typography.spec)
           npm run build                     → clean (exit 0)
           npx playwright test --project=phase2 → 3/3 passed
```
Zero regressions.

---

## Files (git left to operator)

**Backend (PART A):** `app/Models/Service.php`,
`app/Http/Controllers/Api/V1/ServiceController.php`,
`app/Http/Resources/ServiceResource.php`, `app/Http/Resources/SubServiceResource.php`,
`tests/Feature/InclusionsPreviewTest.php`.
**Frontend (B+C):** `src/lib/api.ts`, `src/lib/inclusions.ts` (new),
`src/components/ServiceMetaRow.tsx` (new),
`src/components/explore/ExploreCardFallback.tsx`, `src/pages/ServiceCategory.tsx`,
`tests/e2e/service-pages-phase2.spec.ts` (new), `playwright.config.ts` (phase2 project).
**Report + screenshots:** `SERVICE_PAGES_PHASE2_REPORT.md`, `phase2-shots/*.png`.

No migrations, no slug/pricing/`service_prices` changes, no new packages.

---

## Deviations

1. **`inclusions_preview` on `ServiceResource`** (category page's real shape), not
   `SubServiceResource` as the brief literally stated — added to both for consistency
   but only `@show` populates it (transient + bulk query → lean, no N+1).
2. **CTA stays blue (`btn-ink-primary`), not orange.** The brand tokens list orange
   `#F28C28` for CTAs, but the entire existing app (sidebar, detail, landing) uses blue
   CTAs; introducing orange only on the new category cards would create an inconsistent
   orange-island next to the blue `CarSidebar`. Kept blue for app-wide consistency —
   flag for an app-wide CTA-color decision (easy one-line swap to `btn-ink` + orange if
   you want it later). No GoMechanic red was introduced either way.
3. **`ServiceCard` built inline** in `ServiceCategory.tsx` (not a shared component) and
   **`ExploreCard` not reused for the card body** — its layouts (overlay/stacked/wide)
   don't fit the price-4-state + CTA + inclusions anatomy. Reused `ExploreCardFallback`
   (the image-less fallback, the key reuse) + `ServiceMetaRow` + `SectionHeading`
   instead. The card can be extracted to a shared component in 2b for the detail page's
   "related services".
4. **PART D (detail page) + PART E (smooth-nav) deferred to Phase 2b** per the checkpoint
   — smooth category↔detail nav (`AnimatePresence` scoping) needs both pages, so it
   lands with the detail redesign. `groupInclusions()` is already built for 2b's grouped
   "What's Included".

---

**Phase 2a complete + screenshot-verified. Ready for Phase 2b (detail page + smooth-nav)
on your go.** Two dev servers (Vite :3000, Laravel :8000) were started for the
screenshots — stopping them now.
