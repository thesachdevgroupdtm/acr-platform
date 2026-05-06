# Phase 3A — react-router-dom v7 Foundation (Shim Commit)

**Date:** 2026-05-06
**Scope:** Replace the legacy string-keyed `currentPage` state machine
in App.tsx with `<BrowserRouter>` + `<Routes>` while keeping every
page component unchanged. The shim translates legacy `setCurrentPage`
prop calls into `useNavigate()` and derives Header's `currentPage`
prop from the URL.
**Status:** ✅ All 50 tests pass (47 pre-existing + 3 new router
patterns). Phase 2.6b architecture (rapid-clicks Test 4) preserved.

---

## 1 — Files modified

| File | Change |
|---|---|
| `package.json` | `react-router-dom@^7.15.0` added (4 packages, 0 vulnerabilities). |
| `src/main.tsx` | Wrapped `<App />` in `<BrowserRouter basename={ROUTER_BASENAME}>`. Basename derives from `import.meta.env.BASE_URL` so dev (`/`) and prod (`/app/`) both work. |
| `src/App.tsx` | Full rewrite of routing logic. Legacy `currentPage` state, `parsePageFromUrl`, `pageToUrl`, `popstate` listener, and `isRouteResolved` gate all replaced. New helpers `pathnameToPageKey` + `pageKeyToPath` are the entire shim surface. 5 small route-element wrappers (`ServiceDetailRoute`, `ServiceCategoryRoute`, `ServiceCenterDetailRoute`, `OrderDetailRoute`, `BookingConfirmationRoute`) call `useParams` and forward as props. |
| `playwright.config.ts` | `edges` project's testMatch widened to include `router-patterns.spec.ts`. |
| `tests/e2e/router-patterns.spec.ts` | **New file.** 3 router-pattern tests. |

**Files NOT modified (Commit B scope):** every page component
(Home, Services, Cart, Checkout, MyBookings, ServiceCategory,
ServiceDetail, all 22), Header.tsx, Footer.tsx, AuthModal.tsx — all
still receive `setCurrentPage` exactly as they did pre-3A.

---

## 2 — Route map (every alias preserved)

| URL | Element |
|---|---|
| `/` | `Home` |
| `/services` | `Services` |
| `/services/:category/:service` | `ServiceDetailRoute` → `ServiceDetail` |
| `/category/:slug` | `ServiceCategoryRoute` → `ServiceCategory` |
| `/service-centers` | `ServiceCenters` |
| `/center/:id` | `ServiceCenterDetailRoute` → `ServiceCenterDetail` |
| `/insurance` | `Insurance` |
| `/corporate` | `Corporate` |
| `/gallery` | `Gallery` |
| `/about` | `About` |
| `/contact` | `Contact` |
| `/offers` | `Offers` |
| `/coupons` | `Coupons` |
| `/sitemap` | `Sitemap` |
| `/cart` | `Cart` |
| `/checkout` | `Checkout` |
| `/booking-history` | `MyBookings` (alias preserved from Phase 2.5.2) |
| `/my-bookings` | `MyBookings` (canonical) |
| `/order/:id` | `OrderDetailRoute` → `OrderDetail` (numeric guard preserved) |
| `/booking-confirmation/:id` | `BookingConfirmationRoute` → `BookingConfirmation` |
| `/cms-preview` | `CmsPage` |
| `/testimonials` | `Testimonials` |
| `/not-found` | `NotFound` |
| `*` (catch-all) | `NotFound` — `/payment` regression-locked here per Phase 2.6a |

23 explicit routes + 1 catch-all. Smoke test for `/payment` → `NotFound` continues to pass (catch-all owns it).

---

## 3 — Architecture preservation (the load-bearing concern)

The Phase 2.6b boundary stack stayed intact, just with the key swapped from `currentPage` to `location.pathname`:

```tsx
<ChunkErrorBoundary>
  <AnimatePresence mode="wait">
    <motion.div key={location.pathname} ...>
      <Suspense fallback={<GlobalLoadingFallback />}>
        <Routes location={location}>
          {/* 23 routes + catch-all */}
        </Routes>
      </Suspense>
    </motion.div>
  </AnimatePresence>
</ChunkErrorBoundary>
```

Two specific calls:
- `<Routes location={location}>` — explicit location prop so the exiting motion.div retains its OLD route children during the exit animation (mode="wait" still works correctly).
- Suspense INSIDE motion.div — Phase 2.6b's "rapid sequential clicks" architecture is preserved; per-motion-div Suspense lets each new route's chunk fetch suspend independently of the previous route's exit animation.

### Test 4 (rapid clicks) — verified explicitly

| Run | Duration | Result |
|---|---|---|
| Pre-3A (Phase 2.6b-fix) | 2.2 s | ✅ |
| **Post-3A (this commit)** | **2.5 s** | ✅ |

No hang, no architecture regression. The 0.3 s drift is within normal noise across machines.

---

## 4 — Bundle size impact

| Chunk | Pre-3A | Post-3A | Δ |
|---|---|---|---|
| **Initial app shell (`index-*.js`)** | 137.20 kB / 34.23 kB gzip | **173.48 kB / 46.72 kB gzip** | **+36.28 kB / +12.49 kB gzip** |
| `react-vendor` | 193.81 / 60.54 | unchanged | 0 |
| `motion-vendor` | 127.89 / 42.02 | unchanged | 0 |
| `query-vendor` | 41.31 / 12.30 | unchanged | 0 |
| `icons-vendor` | 29.12 / 6.44 | unchanged | 0 |
| Per-route chunks | 21 chunks, sizes unchanged (just new hashes) |

App shell still well under the 300 kB / 90 kB gzip target (137 → 173 leaves 127 kB headroom raw, 56 kB headroom gzip). Total first-load:

| | Sum (raw) | Sum (gzip) |
|---|---|---|
| Pre-3A | 529 kB | 156 kB |
| Post-3A | **565 kB** | **168 kB** |

The +36 kB raw / +12 kB gzip cost matches the predicted react-router-dom v7 size. **Vite's "chunks larger than 500 kB" warning still gone.**

---

## 5 — New tests added (3)

| # | Test | What it locks down | Result |
|---|---|---|---|
| 1 | `useLocation: URL bar reflects the active route after click navigation` | Programmatic-via-button nav (Header → shim → `useNavigate`) → URL bar updates AND target page renders. | ✅ 3.5 s |
| 2 | `useSearchParams: query string round-trips and survives reload` | `?source=…&ref=…` arrives as query, `URLSearchParams` reads both keys, reload preserves them. Checks BrowserRouter basename + Vite SPA fallback agree under reload. | ✅ 1.5 s |
| 3 | `programmatic navigate(): URL and rendered route update in lockstep` | Three sequential nav clicks (Gallery → Corporate → Services) — each step asserts URL AND heading agree. Then `goBack()` lands on `/corporate` AND re-renders Corporate (browser history is the source of truth). | ✅ 5.6 s |

These are the canonical router-pattern guarantees Phase 3B's "useNavigate per page" migration depends on; if any of them ever broke, the whole shim approach is unsafe.

---

## 6 — Full test suite (verbatim)

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
Duration: 3.76 s
```

### Frontend (`npx playwright test`)

```
[smoke]      smoke.spec.ts                ✓ 3 tests   ( 7.6 s)
[production] code-splitting.spec.ts       ✓ 5 tests   (13.7 s)
[production] console-errors.spec.ts       ✓ 1 test    ( 2.5 s)
[mobile]     mobile.spec.ts               ✓ 3 tests   ( 6.9 s)
[edges]      auth-edges.spec.ts           ✓ 2 tests   ( 7.9 s)
[edges]      cart-merge.spec.ts           ✓ 2 tests   ( 3.4 s)
[edges]      coupon-flow.spec.ts          ✓ 2 tests   ( 7.5 s)
[edges]      journey.spec.ts              ✓ 1 test    ( 2.6 s)
[edges]      router-patterns.spec.ts      ✓ 3 tests   (10.6 s)

22 passed (1.1m)
```

**Combined: 50/50 green.**

---

## 7 — Build outputs

### TypeScript
```
$ npx tsc --noEmit
(exit 0, no output)
```

### Vite production build
```
$ npm run build
✓ 2185 modules transformed.
dist/index.html                              0.77 kB │ gzip:   0.36 kB
dist/assets/index-CFsGvZtO.css             111.69 kB │ gzip:  18.05 kB
… 21 per-route chunks (sizes unchanged from 2.6b-fix) …
dist/assets/icons-vendor-BUGp-X7s.js        29.12 kB │ gzip:   6.44 kB
dist/assets/query-vendor-B7JjJB5a.js        41.31 kB │ gzip:  12.30 kB
dist/assets/motion-vendor-D9SD0d82.js      127.89 kB │ gzip:  42.02 kB
dist/assets/index-qMaPugf3.js              173.48 kB │ gzip:  46.72 kB   ← +36 kB for router
dist/assets/react-vendor-DXoTT26f.js       193.81 kB │ gzip:  60.54 kB
✓ built in 19.82s
```

---

## 8 — Deviations

1. **`RouteResolutionLoader.tsx` is now dead code.** The component
   is no longer imported by App.tsx (the `isRouteResolved` gate is
   redundant — BrowserRouter parses the URL synchronously). The
   file remains on disk for now; deleting it is a one-line Commit B
   change and doesn't belong in a shim-only commit.

2. **`scrollTo(0, 0)` dependency switched** from `[currentPage]` to
   `[location.pathname]`. Behavior is the same except that
   `?coupon=FIRST10` query-only changes no longer reset scroll —
   which is the correct behavior (the user shouldn't lose their
   scroll position on a query-only update). No test relies on the
   old behavior.

3. **No `<Link>` updates needed.** The Phase 3 spec mentioned "all
   `<Link>` components" but the current codebase doesn't use
   `<Link>` for nav — it uses `<button onClick={setCurrentPage}>`.
   The shim handles those automatically. Adding `<Link>` is a
   separate UX consideration (right-click → open in new tab works
   with `<Link>` but not buttons) and belongs in a follow-up
   accessibility/UX phase, not Commit B.

---

## 9 — Verification gates

✅ All 47 pre-existing tests pass without modification
✅ 3 new router-pattern tests pass
✅ Test 4 (rapid clicks) verified at 2.5 s — no architecture regression
✅ TypeScript clean
✅ Production build clean
✅ App shell <300 kB raw / <90 kB gzip target still met (173 / 47)
✅ `/payment` → NotFound regression-locked (smoke test 2.6c green)
✅ `/booking-history` → MyBookings alias preserved (Phase 2.5.2)

**Commit A is clean. Ready for go/no-go on Commit B.**

---

## 10 — What Commit B will do

- Drop the `setCurrentPage` prop from every page interface.
- Each page imports `useNavigate` directly and replaces shim calls.
- Each parameterized page imports `useParams` directly (delete the
  5 wrapper components in App.tsx).
- Header.tsx: derive `currentPage` itself via `useLocation` (drop
  the prop in callers).
- Footer.tsx: same pattern.
- Delete `pageKeyToPath` + `pathnameToPageKey` helpers.
- Delete `RouteResolutionLoader.tsx`.
- Add 2-3 more router tests (useParams extraction; auth-route
  element wrapper if we add one; direct URL load → page renders).

Estimated diff: ~25 files, ~300 lines net. All 50 tests + 2-3 new
must remain green.

---

## 11 — How to run

```bash
# Three shells:
cd backend && php artisan serve --host=127.0.0.1 --port=8000
npm run dev
npm run build && npm run preview -- --port 4173 --host 127.0.0.1

# Tests:
npm test                          # full suite (Pest + 4 e2e projects)
npm run test:e2e:edges            # includes the 3 new router tests
```
