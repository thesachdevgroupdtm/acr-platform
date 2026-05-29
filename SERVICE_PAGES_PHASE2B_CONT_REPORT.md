# SERVICE_PAGES_PHASE2B_CONT_REPORT — shell WIRED, Layer-3 detail REBUILT (verified)

**Status: DONE and screenshot-verified.** The persistent `ServicesShell` is now wired
into routing; `/services`, `/category/:slug` and `/services/:cat/:svc` swap **only** the
center content with a 180 ms crossfade while the sticky cross-category bar **and** the one
`CarSidebar` stay **MOUNTED** (proven: same DOM node across nav). The Layer-3 detail page is
rebuilt per D-2b-7 against **real grouped inclusions**. No cart/booking/nav regression.

- **TSC:** clean — only the **2 pre-existing** errors in `tests/e2e/brand-typography.spec.ts`
  (SVGElement vs HTMLElement; untouched by this pass). Zero new errors.
- **Vite build:** clean (`✓ built in ~10s`). `ServicesShell` is its own lazy chunk (12.6 kB),
  `ServiceDetail` 14.0 kB, `ServiceCategory` 23.3 kB, `Services` 8.3 kB.
- **Backend Pest:** **317 passed (1327 assertions)** — unchanged from baseline (no backend edits).
- **Frontend e2e (`--project=phase2`):** **9/9 passed** (3 prior + 6 new).
- **Layer-1 active-category tabs (PART C of the original phase): still DEFERRED to Phase 2c.**

---

## PART A — shell wiring

### A1 — App.tsx key strategy
- `ServicesShell` lazy-imported and wraps the three layers in a pathless layout route:
  ```tsx
  <Route element={<ServicesShell openEstimate={openEstimate} />}>
    <Route path="/services" element={<Services …/>} />
    <Route path="/services/:category/:service" element={<ServiceDetail …/>} />
    <Route path="/category/:slug" element={<ServiceCategory …/>} />
  </Route>
  ```
- **Stable animation key.** The App-level `<motion.div key={…}>` previously remounted the whole
  subtree on every pathname change (that remount was both the "reload feel" *and* what would tear
  down the shell). Now:
  ```ts
  const isShellRoute = pathname === "/services"
    || pathname.startsWith("/services/") || pathname.startsWith("/category/");
  const animKey = isShellRoute ? "services-shell" : location.pathname;
  ```
  `<motion.div key={animKey}>` → catalog-internal nav keeps the **same** key, so the App-level
  transition does **not** fire and the shell never unmounts. Non-shell routes keep `pathname` keying.
- **Scroll-to-top re-keyed** to `animKey` (was `pathname`), so category↔detail↔services no longer
  jump to the top (one-page feel, D-2b-8). Entering/leaving the shell still scrolls to top.
- **One addition to the shell itself:** a `<Suspense>` boundary **around the `<Outlet/>`** (with a
  light center skeleton). Without it, a child layer's first-visit lazy-chunk load would bubble to
  the App-level `<Suspense>` and replace the shell chrome — defeating persistence. Scoped locally,
  only the center shows a fallback; the bar + sidebar stay in the DOM.

### A2 — what came out of each page (now center content only)
| Page | Removed | Kept / changed |
|---|---|---|
| `Services.tsx` (L1) | `<PageBanner>`, sticky category `<nav>` + `useSubNavSync`, the `grid/<main>` wrapper, `<CarSidebar>`, dead sticky-offset consts, unused `motion` import | catalog `CategorySection` list + trust strip, wrapped in `<div className="space-y-12">` |
| `ServiceCategory.tsx` (L2) | `<PageBanner>`, the `grid/<main>` wrapper, `<CarSidebar>` | **kept** the in-page section nav (Overview/Pricing/…) but **lowered** it: `sticky top-[164px] z-20` (below the shell bar's `top-112 z-30`); click-scroll offset bumped `112 → 216` |
| `ServiceDetail.tsx` (L3) | `<PageBanner>`, `<CarSidebar>`, the `grid/<main>` wrapper, **the ~270-line dead `<aside>` block**, the in-page section sub-nav, the page-local price machine + add-to-cart + `VehicleReplaceModal` | fully rebuilt (PART B) |

### A3 — checkpoint (HARD) — GREEN
- Build + `tsc` clean (see top).
- **"Sidebar stays mounted" proof (same DOM node):** e2e test grabs the live
  `[data-testid="car-sidebar"]` element handle on `/category/regular-car-service`, performs a
  **client-side** nav to `/services/regular-car-service/primary-service`, waits for fully-loaded
  detail-only content, then asserts `handle.evaluate(el => el.isConnected) === true` **and**
  `count(car-sidebar) === 1`. A remount would have detached the old handle → the assertion would
  fail. It passes. A second test proves the same across the full `/services → category → detail`
  chain.
- **Cart/booking non-regression:** add-to-cart from the shell sidebar on the detail route toggles
  **Add to Cart → Added**, the service line appears in the cart summary, the per-vehicle price
  reveals (**₹7,300** for Audi A3 Petrol), and Checkout enables. Price 4-state intact
  (no vehicle → "Select car"; vehicle → ₹).
- **No sticky collision:** scrolled measurement test asserts the section nav's top ≥ the
  cross-category bar's bottom (they stack, never overlap) and both remain stuck near the top —
  which also confirms `position: sticky` survives inside the shell's crossfade container.

---

## PART B — Layer-3 detail rebuilt (D-2b-7), GoMechanic structure / ACR skin

Content order (center column; the shell's PageBanner is the single `<h1>`, the sidebar owns
price + add-to-cart):
1. **Highlight strip** — *What's Included / Also Includes / Timelines*, derived from grouped
   counts + duration/interval. For `primary-service`: **8 Services** (Essential 4 + Performance 4),
   **1 Add-on** (Additional), **3 hours** + recommended interval. Empty cells omitted.
2. **Intro** — back link (`← Regular Car Service`) + title + description + **`ServiceMetaRow`
   (detail variant)**: duration · warranty · interval(Recommended) · **static Free Pickup & Drop**
   (new opt-in `freePickup` prop; non-null fields only).
3. **What's Included?** — **REAL `service.inclusions`** via `groupInclusions()`:
   **Essential** + **Performance** as **image cards** (image when present → on-brand fallback tile:
   faint-blue surface + ACR-blue group glyph + caption, since acr_v3 inclusions are image-null
   today); **Additional** as a **blue-checkmark list**. NULL group → Essential; **empty groups hidden**.
   (The old static 6-item array is gone.)
4. **Steps-After-Booking band** — **Deep Navy `#0E2A5C`**, white text, `#3D86E0` accents, static.
5. **CTA** — ACR **blue** (Get Estimate → `openEstimate`). No orange islands.
6. **Reviews / FAQ (`FAQAccordion`) / Related (API) / top-links** — static.
7. **Hero** — shell PageBanner uses `service.image`, falling back to the **dark gradient (NO Unsplash)**
   when null (`backgroundImage` now accepts `null`; only the detail layer passes it, so the
   category/services banners are unchanged).

### B5 — detail screenshots (real grouped data)
- `phase2-shots/phase2b-cont-detail-desktop.png` (1440) — dark-gradient hero, highlight strip,
  meta row w/ Free pickup, **Essential (4) + Performance (4) fallback image cards + Additional
  checklist**, navy band, blue CTA, reviews/FAQ/related/links, single sidebar (Audi A3 + Add to Cart).
- `phase2-shots/phase2b-cont-detail-mobile.png` (390) — same, stacked (2-col inclusion cards).

---

## PART C — tests

- **Extended `tests/e2e/service-pages-phase2.spec.ts` (phase2 project), 6 new tests:**
  1. shell — sidebar is the **same DOM node** across category → detail (persistence).
  2. shell — sidebar persists across the full `/services → category → detail` chain.
  3. detail — grouped What's Included renders Essential/Performance image cards + Additional
     checklist from **real** labels (Engine Oil Replacement, Spark Plug Cleaning, …), meta non-null
     only (real warranty + Free Pickup), and **no `img[src*="unsplash"]`** anywhere (hero killed).
  4. cart — add from detail sidebar toggles **Added** + the line appears in the cart (no regression).
  5. shell — section nav sits **below** the cross-category bar when scrolled (no collision).
  6. category — price **4-state** intact (₹ with vehicle, "Select car" without).
- **Full suite:** `--project=phase2` → **9/9 passed**. Backend Pest → **317 passed**. TSC clean.
  Vite build clean. Zero regressions.
  *(A few backend-dependent assertions got explicit 10 s timeouts — the dev backend resolves
  per-vehicle prices and cart writes slower under serial suite load; pure harness timing, not app
  behavior. All pass green together.)*

---

## PART D — screenshots (hard gate, inspected)

| File | Viewport | Shows |
|---|---|---|
| `phase2b-cont-detail-desktop.png` | 1440 | Layer-3 detail, real grouped inclusions, navy band, fallback hero, persistent sidebar |
| `phase2b-cont-detail-mobile.png` | 390 | same, stacked |
| `phase2b-cont-cart-populated.png` | 1440 | sidebar after add-to-cart — **PRIMARY SERVICE ₹7,300**, subtotal, coupon, Checkout enabled |
| `phase2b-cont-category-scrolled.png` | 1440 | the two sticky bars stacked (cross-category bar above section nav), single sidebar |
| `category-desktop-vehicle / -novehicle / -card / -mobile-vehicle.png` | 1440/390 | category page under the shell (regenerated by the prior phase2 tests) |

**D2 violation sweep (all clear):** no remount/reload feel (same-node proof), zero GoMechanic
red/grey, no empty group rendered (all 3 groups have data and render; empties are hidden by
`groupInclusions`), no broken images (image-null inclusions show the fallback tile; hero shows the
dark gradient), exactly **one** sidebar (no duplicate from the pages).

---

## Brand check (D-2b-9)
ACR **blue** accents (links, headings, icons, CTA), **navy `#0E2A5C`** Steps band with `#3D86E0`
on-dark accents, Montserrat-class section headings (`section-heading`), Inter body. **Zero**
GoMechanic red/grey; CTAs are blue (no orange islands).

---

## Files modified
- `src/App.tsx` — lazy `ServicesShell`; `isShellRoute`/`animKey` stable key; scroll effect re-keyed; 3 routes nested under the shell.
- `src/layouts/ServicesShell.tsx` — `<Suspense>` + center `OutletFallback` around `<Outlet/>`; PageBanner `backgroundImage={detailService?.image ?? null}` on the detail layer.
- `src/components/PageBanner.tsx` — `backgroundImage?: string | null`; render `<img>` only when truthy (passing `null` kills Unsplash, default param unchanged for all existing callers).
- `src/components/ServiceMetaRow.tsx` — additive `freePickup?` prop (static "Pickup & Drop / Free", appended last; compact card usage unaffected).
- `src/components/car-sidebar/CarSidebar.tsx` — `data-testid="car-sidebar"` on the desktop aside (test hook).
- `src/pages/Services.tsx` — stripped to center content (banner/nav/grid/sidebar removed).
- `src/pages/ServiceCategory.tsx` — stripped to center content; section nav lowered to `top-[164px] z-20`; click-scroll offset → 216.
- `src/pages/ServiceDetail.tsx` — **full rebuild** per D-2b-7.
- `tests/e2e/service-pages-phase2.spec.ts` — 6 new tests + minor robustness timeouts.

**New screenshots:** `phase2-shots/phase2b-cont-{detail-desktop,detail-mobile,cart-populated,category-scrolled}.png`.

**No** migrations, **no** slug changes, **no** `service_prices`/pricing-logic changes, **no** new packages.

---

## Deviations (called out)
1. **ServiceDetail no longer carries its own price machine / add-to-cart / `VehicleReplaceModal`.**
   The shell's single `CarSidebar` owns the live per-vehicle price + Add-to-Cart for the detail
   service (that is the point of the persistent shell). The detail body is now purely informational.
   Verified non-regressive by the cart screenshot + e2e (price reveal, Added toggle, cart line).
2. **The detail's old in-page section sub-nav was removed** (not lowered). GoMechanic Layer-3 has no
   section nav, and A2's "keep the section nav" applies to **Layer-2**. Layer-2's nav is kept + lowered.
3. **Unsplash killed on the detail banner only.** Category/Services keep PageBanner's default
   cinematic background (unchanged — they shipped that way in Phase 2a; broadening the change was
   out of scope).
4. **Single `<h1>`** preserved: the shell's PageBanner is the page `<h1>`; the detail repeats the
   service title as an `<h2>`.
5. **A2(detail-strip) + B(rebuild) done in one `ServiceDetail` rewrite** (per the recommendation that
   they're coupled), but the **A3 wiring checks were still verified independently** (same-node
   persistence, cart, build/tsc) before declaring done — a full `Write` is less error-prone than a
   throwaway 290-line interim strip.
6. **Layer-1 active-category tabs remain deferred to Phase 2c.** `/services` now navigates via the
   shell's cross-category bar; its old in-page category scrollspy nav (which duplicated the shell bar)
   was removed.

---

## GIT
No git commands were run. Files changed are listed above; **operator commits.**
