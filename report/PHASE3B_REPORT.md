# Phase 3B — Pure react-router Migration

**Date:** 2026-05-07
**Scope:** drop the Phase 3A `setCurrentPage` shim. Each page component
now imports `useNavigate` and `useParams` directly from
`react-router-dom`. Header/Footer derive active state from
`useLocation`. The 5 route-element wrappers, `pageKeyToPath` /
`pathnameToPageKey` helpers, and the dead `RouteResolutionLoader.tsx`
are all deleted.
**Status:** ✅ 53/53 tests pass (28 backend Pest + 25 Playwright).
Test 4 (rapid clicks) verified at **2.4 s** — no architecture
regression.

---

## 1 — Files modified (27 files)

| File | Change |
|---|---|
| `src/App.tsx` | Removed shim entirely: `pageKeyToPath`, `pathnameToPageKey`, `setCurrentPage` callback, `ShimProps` type, 5 route-element wrappers (`ServiceDetailRoute`, `ServiceCategoryRoute`, `ServiceCenterDetailRoute`, `OrderDetailRoute`, `BookingConfirmationRoute`). `<Routes>` now renders page components directly. |
| `src/components/Header.tsx` | Dropped `currentPage` and `setCurrentPage` props. Now reads `currentPath` from `useLocation` and calls `useNavigate` for click handlers. `navItems` carries an explicit `path` field; `isActiveMenu` matches on URL path. SubMenu sub-component lifts `useNavigate` internally (drops the prop). |
| `src/components/Footer.tsx` | Removed optional `setCurrentPage` prop. `QUICK_LINKS` and `USEFUL_LINKS` now carry `path` instead of legacy `page` keys. Uses `useNavigate` inside a `goTo` helper that also resets scroll. |
| `src/components/AuthModal.tsx` | Dropped `setCurrentPage` prop. `redirectTo` is now a URL path (e.g. `/checkout`), passed straight to `useNavigate`. |
| `src/components/HomeFAQ.tsx` | Dropped `setCurrentPage` prop. Uses `useNavigate` directly. |
| `src/components/GlobalLoadingFallback.tsx` | Doc-comment refresh — `RouteResolutionLoader` is no longer the upstream loader; this is now the single global loading surface. |
| `src/pages/Home.tsx` | Drops `setCurrentPage` prop. 6 `setCurrentPage(…)` calls → `navigate('/…')`. |
| `src/pages/Services.tsx` | Same pattern; 4 sites converted including the dynamic `/services/:cat/:sub` and `/category/:slug` paths. |
| `src/pages/ServiceCategory.tsx` | Drops `setCurrentPage` AND `categorySlug` props; reads slug via `useParams<{ slug: string }>`. |
| `src/pages/ServiceDetail.tsx` | Drops `setCurrentPage`, `categorySlug`, `serviceSlug` props; reads both via `useParams<{ category: string; service: string }>`. |
| `src/pages/ServiceCenters.tsx` | Standard conversion; dynamic `/center/:id` link converted. |
| `src/pages/ServiceCenterDetail.tsx` | Drops `centerId` prop; reads via `useParams<{ id: string }>`. |
| `src/pages/Cart.tsx` | Drops `setCurrentPage`. Sub-components in same file (`EmptyCart`, exported `CheckoutSteps`) lift `useNavigate` themselves; their props no longer carry the shim. The shared `CheckoutSteps` is re-imported by `Checkout.tsx` — that call site simplified to `<CheckoutSteps current={2} />`. |
| `src/pages/Checkout.tsx` | 18 `setCurrentPage(…)` sites converted. Place-order success redirects via `navigate(\`/booking-confirmation/\${id}\`)`. Cart-empty fallback redirects via `navigate('/cart')`. |
| `src/pages/MyBookings.tsx` | 10 sites converted. `NotLoggedIn` sub-component lifts `useNavigate` internally; `openAuth("login", "/my-bookings")` now passes a URL path. |
| `src/pages/OrderDetail.tsx` | Drops `orderId` prop; reads via `useParams`. Numeric guard preserved (invalid id → NotFound). |
| `src/pages/BookingConfirmation.tsx` | Drops `orderId` prop; reads via `useParams`. |
| `src/pages/About.tsx`, `Contact.tsx`, `Gallery.tsx`, `Insurance.tsx`, `Corporate.tsx`, `Coupons.tsx`, `Offers.tsx`, `NotFound.tsx`, `Sitemap.tsx`, `Testimonials.tsx`, `CmsPage.tsx` | Standard conversion. `Sitemap.tsx` additionally replaces the page-key derivation `page.toLowerCase().replace(' ', '-')` with an explicit `MAIN_PAGES` array of `{ label, path }` pairs (cleaner than the previous string-mangling). |
| `playwright.config.ts` | `edges` project's `testMatch` widened to include the new `router-params.spec.ts`. |
| `tests/e2e/router-params.spec.ts` | **New file.** 3 useParams + deep-URL tests. |

## 2 — Files deleted

| File | Reason |
|---|---|
| `src/components/RouteResolutionLoader.tsx` | Obsolete since Phase 3A — was only consumed by the legacy `isRouteResolved` URL-parse gate, which `BrowserRouter` makes redundant. Phase 3A flagged it as dead code; Phase 3B deletes it. |

---

## 3 — PART A: setCurrentPage audit (before)

```
src/App.tsx                          40   (shim definition + 22 Route element passes)
src/components/Header.tsx            19
src/pages/Checkout.tsx               18
src/pages/Cart.tsx                   13
src/pages/MyBookings.tsx             10
src/pages/OrderDetail.tsx            10
src/pages/ServiceDetail.tsx          10
src/pages/Home.tsx                    8
src/pages/BookingConfirmation.tsx     8
src/pages/ServiceCategory.tsx         7
src/pages/Sitemap.tsx                 7
src/pages/Services.tsx                5
src/pages/Testimonials.tsx            5
src/pages/Coupons.tsx                 4
src/pages/Offers.tsx                  4
src/pages/NotFound.tsx                4
src/pages/CmsPage.tsx                 4
src/pages/ServiceCenterDetail.tsx     4
src/pages/ServiceCenters.tsx          4
src/components/Footer.tsx             4
src/pages/About.tsx                   3
src/pages/Contact.tsx                 3
src/pages/Gallery.tsx                 3
src/pages/Insurance.tsx               3
src/pages/Corporate.tsx               3
src/components/AuthModal.tsx          3
src/components/HomeFAQ.tsx            3

TOTAL: 209 occurrences across 27 files.
```

**Post-3B grep:** zero application code references. Only doc-comment historical references in `App.tsx` and `GlobalLoadingFallback.tsx`.

---

## 4 — PART B: URL mapping reference (used during conversion)

| Legacy page key | New URL |
|---|---|
| `home` | `/` |
| `services` | `/services` |
| `service-{cat}/{sub}` | `/services/{cat}/{sub}` |
| `category-{slug}` | `/category/{slug}` |
| `service-centers` | `/service-centers` |
| `center-{id}` | `/center/{id}` |
| `insurance` / `corporate` / `gallery` / `about` / `contact` / `offers` / `coupons` / `sitemap` / `cms-preview` / `testimonials` / `not-found` | `/{key}` |
| `cart` / `checkout` | `/cart` / `/checkout` |
| `my-bookings` | `/my-bookings` (canonical) |
| `order-{id}` | `/order/{id}` |
| `booking-confirmation-{id}` | `/booking-confirmation/{id}` |

Every alias preserved across both 3A and 3B; no URL changes for end users or external links.

---

## 5 — PART C: Pages converted (tier-by-tier)

| Tier | Files | Status |
|---|---|---|
| 1 — Static | About, Contact, Gallery, Insurance, Corporate, NotFound, Coupons, Offers, CmsPage | ✅ |
| 2 — Listings | Services, ServiceCenters, Testimonials, Sitemap | ✅ |
| 3 — Param pages | ServiceCategory (`useParams<{ slug }>`), ServiceCenterDetail (`useParams<{ id }>`) | ✅ |
| 4 — Cart/Checkout | Cart (with sub-components: `EmptyCart`, `CheckoutSteps`), Checkout (18 sites + 4 `<CheckoutSteps>` usages) | ✅ |
| 5 — Auth-required | MyBookings (with `NotLoggedIn`), OrderDetail (`useParams<{ id }>`), BookingConfirmation (`useParams<{ id }>`) | ✅ |
| 6 — High-touch | Home (with `HomeFAQ` child), ServiceDetail (`useParams<{ category, service }>`) | ✅ |

22 page files + 4 component files = 26 conversion sites (SubMenu inside Header.tsx counts as a 27th).

---

## 6 — PART D: Header / Footer / AuthModal conversion

### Header
- Dropped both `currentPage` and `setCurrentPage` props.
- `currentPath = useLocation().pathname` is the single source of truth for active-state.
- `navItems` carry explicit `path` fields. `isActiveMenu` rewritten to match on path patterns:
  - `/services` highlights for `/services`, `/category/*`, `/services/*/*`
  - `/service-centers` highlights for `/service-centers` and `/center/*`
  - `more` (no path) highlights when any of its sub-item paths match
- The desktop "More" dropdown trigger button no longer navigates on click (legacy code navigated to `/more` → NotFound). Hover behaviour unchanged.
- SubMenu sub-component drops the `setCurrentPage` prop and uses `useNavigate` itself.

### Footer
- Removed optional `setCurrentPage` prop entirely.
- `QUICK_LINKS` and `USEFUL_LINKS` arrays now carry `path` (`/services`, etc.) instead of legacy page keys.
- Local `goTo(path)` helper wraps `navigate(path)` + scroll-to-top.

### AuthModal
- Removed `setCurrentPage` prop.
- `redirectTo` is now a URL path. Cart passes `"/checkout"`; MyBookings passes `"/my-bookings"`. AuthModal calls `navigate(redirectTo)` on success.

---

## 7 — PART E: Shim deletion confirmation

```
$ grep -rn "setCurrentPage" src/
src/App.tsx:50: * setCurrentPage callback / 5 route-element wrappers) has been

$ grep -rn "pageKeyToPath\|pathnameToPageKey" src/
src/App.tsx:49: * The Phase 3A shim layer (pageKeyToPath / pathnameToPageKey /

$ grep -rn "RouteResolutionLoader" src/
src/components/GlobalLoadingFallback.tsx:12: * Phase 3B — RouteResolutionLoader (the Phase 2.5.1 URL-parse
```

The only remaining matches are doc-comment mentions in two files that document the historical relationship (Phase 3B replaced 3A's shim; 3B deleted RouteResolutionLoader). No live code references.

---

## 8 — PART F: New router-pattern tests

`tests/e2e/router-params.spec.ts` (3 tests) — all pass.

| # | Test | What it locks down | Result |
|---|---|---|---|
| 1 | `useParams: /category/:slug surfaces the slug in the rendered page` | Hits a guaranteed-unique synthetic slug; ServiceCategory's not-found branch echoes the slug verbatim, proving useParams resolved the URL segment. | ✅ |
| 2 | `useParams (multi-param): /services/:category/:service resolves both` | Deep two-param URL renders ServiceDetail without crash; URL bar reflects both segments. | ✅ |
| 3 | `direct deep-URL load: hard refresh keeps the route stable, no React errors` | Fresh page-load on `/services/car-battery/battery-charging` mounts ServiceDetail; hard reload preserves both params; no real React errors. | ✅ |

These complement Phase 3A's `router-patterns.spec.ts` (3 tests for `useLocation`, `useSearchParams`, programmatic navigate).

---

## 9 — PART G: Full test suite (verbatim)

### Backend (`vendor/bin/pest`)
```
PASS  Tests\Unit\ExampleTest                   (1)
PASS  Tests\Feature\ExampleTest                (1)
PASS  Tests\Feature\Smoke\AuthOtpTest          (3)
PASS  Tests\Feature\Smoke\CartTest             (2)
PASS  Tests\Feature\Smoke\CheckoutTest         (1)
PASS  Tests\Feature\Smoke\CouponTest           (2)
PASS  Tests\Feature\Smoke\OrdersTest           (2)
PASS  Tests\Feature\Smoke\PricingTest          (1)
PASS  Tests\Feature\EdgeCases\CartMergeTest         (3)
PASS  Tests\Feature\EdgeCases\CouponEdgeCasesTest   (4)
PASS  Tests\Feature\EdgeCases\OrderEdgeCasesTest    (3)
PASS  Tests\Feature\EdgeCases\AddressTest           (3)
PASS  Tests\Feature\EdgeCases\PricingFallbackTest   (2)

Tests:    28 passed (120 assertions)
Duration: 74.29 s
```

### Frontend (`npx playwright test`)
```
[smoke]      smoke.spec.ts                ✓ 3 tests   ( 6.0 s)
[production] code-splitting.spec.ts       ✓ 5 tests   (13.3 s)
[production] console-errors.spec.ts       ✓ 1 test    ( 3.0 s)
[mobile]     mobile.spec.ts               ✓ 3 tests   ( 8.1 s)
[edges]      auth-edges.spec.ts           ✓ 2 tests   (12.9 s)
[edges]      cart-merge.spec.ts           ✓ 2 tests   ( 5.5 s)
[edges]      coupon-flow.spec.ts          ✓ 2 tests   (14.1 s)
[edges]      journey.spec.ts              ✓ 1 test    ( 3.6 s)
[edges]      router-patterns.spec.ts      ✓ 3 tests   (12.6 s)
[edges]      router-params.spec.ts        ✓ 3 tests   (22.3 s)

25 passed (1.8 m)
```

**Combined: 53/53 green.**

---

## 10 — PART H: Critical scenario verification

| Scenario | Verified by | Result |
|---|---|---|
| `/payment` → NotFound | `smoke.spec.ts` test 3 | ✅ |
| `/booking-history` → MyBookings (alias preserved) | App.tsx Route + integration: 22 e2e tests across 5 lazy routes | ✅ |
| `/order/:id` → OrderDetail with id from useParams | OrderDetail uses `useParams<{ id }>` + numeric guard preserved | ✅ |
| `/category/:slug` → ServiceCategory with slug from useParams | `router-params.spec.ts` test 1 | ✅ |
| `/services/:cat/:svc` → ServiceDetail with both useParams | `router-params.spec.ts` tests 2 + 3 | ✅ |
| Hard refresh on each above URL → renders correctly | `code-splitting.spec.ts` test 3 + `router-params.spec.ts` test 3 | ✅ |
| Browser back/forward navigates correctly | `router-patterns.spec.ts` test 3 | ✅ |

---

## 11 — Test 4 (rapid clicks) timing

The Phase 2.6b architecture-preservation gate, re-verified across two router-migration commits:

| Phase | Duration | Result |
|---|---|---|
| 2.6b-fix (pre-router) | 2.2 s | ✅ |
| 3A (router + shim) | 2.5 s | ✅ |
| **3B (pure router)** | **2.4 s** | ✅ |

No drift. The Suspense-inside-motion.div + `<Routes location={location}>` architecture works identically with or without the shim.

---

## 12 — Bundle size delta

| Chunk | Phase 3A (shim) | Phase 3B (pure) | Δ |
|---|---|---|---|
| **Initial app shell (`index-*.js`)** | 173.48 kB / 46.72 kB gzip | **171.51 kB / 46.19 kB gzip** | **−1.97 kB / −0.53 kB gzip** |
| Per-route chunks | unchanged | unchanged | 0 |
| Vendor chunks | unchanged | unchanged | 0 |

A modest win from removing the shim helpers + 5 wrapper components. The bulk of Phase 3A's +36 kB cost was the router itself, not the shim. App shell still well under the 300 kB / 90 kB target.

---

## 13 — Build outputs

### TypeScript
```
$ npx tsc --noEmit
(exit 0, no output)
```

### Vite production build
```
$ npm run build
✓ 2178 modules transformed.
dist/index.html                              0.77 kB │ gzip:   0.36 kB
dist/assets/index-CFsGvZtO.css             111.69 kB │ gzip:  18.05 kB
… 21 per-route chunks (sizes unchanged) …
dist/assets/icons-vendor-BUGp-X7s.js        29.12 kB │ gzip:   6.44 kB
dist/assets/query-vendor-B7JjJB5a.js        41.31 kB │ gzip:  12.30 kB
dist/assets/motion-vendor-D9SD0d82.js      127.89 kB │ gzip:  42.02 kB
dist/assets/index-FrrlaNYS.js              171.51 kB │ gzip:  46.19 kB   ← −1.97 kB vs 3A
dist/assets/react-vendor-DXoTT26f.js       193.81 kB │ gzip:  60.54 kB
✓ built in 24.23s
```

The Vite ">500 kB chunk" warning stays absent.

---

## 14 — Code cleanliness summary

```
Pages with `setCurrentPage`         : 0 (was 22)
Components with `setCurrentPage`    : 0 (was 4 — Header, Footer, AuthModal, HomeFAQ)
App.tsx wrapper components          : 0 (was 5)
App.tsx shim helpers                : 0 (was 2 — pageKeyToPath, pathnameToPageKey)
Dead files                          : 0 (RouteResolutionLoader.tsx deleted)

Live setCurrentPage references in source : 0 (only doc-comment mentions remain)
Live RouteResolutionLoader references    : 0
Live pageKeyToPath / pathnameToPageKey   : 0
```

---

## 15 — Deviations

1. **Mobile menu "More" button.** The legacy click handler in mobile menu was inside `if (!item.hasDropdown)`, so "More" never navigated on click — only its dropdown opened. Behavior preserved. The desktop nav, however, *did* navigate to `/more` (NotFound) on click; Phase 3B's desktop click handler now early-returns when `item.path` is missing, so "More" cleanly does nothing on click instead of leading to a NotFound dead-end. This is a marginal UX improvement; flagging it as a real-world behaviour change but not a regression.

2. **Sitemap "Main Pages" derivation.** Legacy code mangled labels with `page.toLowerCase().replace(' ', '-')` to derive page keys. Phase 3B replaces this with an explicit `MAIN_PAGES: { label, path }[]` array. Same 11 destinations, more legible.

3. **`pageKeyToPath`/`pathnameToPageKey` doc references kept.** Two doc-comments in App.tsx and GlobalLoadingFallback.tsx mention these now-deleted helpers as historical context for what was removed. Grep matches them but they are not runtime references. Treated as documentation, not deviation.

4. **Mobile FAQ test sub-component impact.** Mobile menu's nav button class previously highlighted on `currentPage === item.id`. The Phase 3B equivalent uses `isActiveMenu(item)`, which has slightly broader matching for "Services"/"Service Centers" (now highlights for category/center sub-routes too in mobile view, matching the desktop nav). No test relied on the narrower behaviour; mobile.spec.ts test 1 still passes.

---

## 16 — How to run

```bash
# Three shells:
cd backend && php artisan serve --host=127.0.0.1 --port=8000
npm run dev
npm run build && npm run preview -- --port 4173 --host 127.0.0.1

# Tests:
npm test                          # full suite (Pest + 4 e2e projects = 53 tests)
npm run test:e2e:smoke            # 3 smoke tests
npm run test:e2e:production       # 6 production tests
npm run test:e2e:edges            # 13 edges tests (incl. 6 router pattern + param)
npm run test:e2e:mobile           # 3 mobile tests
```
