# Phase 4.2.5 — Fix log

One entry per fix. Format: issue → root cause → fix → files →
test that now catches a regression.

---

## Fix 1 — `CouponPickerModal` silently masks API errors (🔴 CRITICAL)

**Issue:** Operator reported a "Couldn't load coupons" surface
during manual /admin verification. The string only appears on
the `/coupons` standalone page when `useCoupons("marketing")`
errors. In the cart-side `CouponPickerModal`, the same hook is
used **without** an `isError` branch — an API failure silently
collapsed to "No coupons available right now.", indistinguishable
from a healthy empty list.

**Root cause:** `src/components/CouponPickerModal.tsx` destructured
only `coupons, isLoading, refetch` from the hook; `isError` was
never read.

**Fix:** Added `isError` to the destructure. New conditional
branch renders the new shared `<ApiErrorState>` with retry button
when the hook errors. Empty list now uses the new shared
`<EmptyState>` so the two cases stay visually distinct.

**Files modified:**
- `src/components/CouponPickerModal.tsx` (lines 5-7, 45, 170-200)

**Test that now guards this:**
- `tests/e2e/api-integration.spec.ts` — *"CouponPickerModal
  surfaces explicit error UI when /coupons fails (route-mocked)"*
  — uses `page.route()` to inject a 503 on `/api/v1/coupons*`
  before navigating, opens the picker, and asserts the
  `data-testid="coupon-picker-error"` element appears (NOT the
  empty-state one).

---

## Fix 2 — `/service-centers` page renders entirely from static `LOCATIONS` (🟡 HIGH)

**Issue:** The page shipped a hard-coded `LOCATIONS.map(…)` loop
and never called the backend. This violates the operator's
stated architecture direction (memory: "Target architecture is
full API-driven CMS — no static fallbacks") and means
admin-driven service-center changes never reached customers.

**Root cause:** `src/pages/ServiceCenters.tsx` imported
`LOCATIONS` directly from `data/businessData.ts` and rendered it.
There was no `useServiceCenters()` consumer for this page despite
the hook (and `/api/v1/service-centers` endpoint) existing
since Phase 2.5a.

**Fix:** Rewrote the page to consume `useServiceCenters()`. New
states: skeleton (loading), `<ApiErrorState>` (error with retry),
`<EmptyState>` (success but zero rows), and the success grid.
Until the backend grows `image` / `features` / `rating` columns
(Phase 4.5+), each API row is enriched by looking up the legacy
`LOCATIONS` constant by slug for those presentation-only fields;
centers without a legacy match still render with sensible
defaults. The "View Centre Details" button now links to the
center's slug (which matches the existing static-id format).

**Files modified:**
- `src/pages/ServiceCenters.tsx` — full rewrite (preserves the
  "Global Standards" section verbatim)

**Test that now guards this:**
- `tests/e2e/api-integration.spec.ts` — *"Service centers page
  calls /api/v1/service-centers and renders ≥ 1 center"* —
  asserts the API call fires (via `page.waitForResponse`) and
  at least one "View Centre Details" button is visible.
- `backend/tests/Feature/Api/V1/EndpointContractsTest.php` —
  *"GET /api/v1/service-centers returns only active centers
  under service_centers key"* — locks the endpoint contract
  so the FE isn't fed inactive centers.

---

## Fix 3 — Reusable `ApiErrorState` and `EmptyState` components (🟡 HIGH)

**Issue:** Every page invented its own "Couldn't load X." copy
inline. There was no canonical retry mechanism, no consistent
styling, no shared `data-testid` that tests can target.

**Root cause:** No shared error UI primitive existed.

**Fix:** Created two new components:

- `src/components/ApiErrorState.tsx`
  Props: `message?`, `detail?`, `onRetry?`, `inline?`,
  `data-testid?`. Default `data-testid="api-error-state"`. Visual
  contract matches the existing accent-dark error styling so
  introducing it doesn't change the look.
- `src/components/EmptyState.tsx`
  Props: `title`, `hint?`, `icon?`, `inline?`, `data-testid?`.
  Distinguishes "API succeeded with zero rows" from "API failed".

Adopted in fixes #1 and #2. Other pages (`/coupons`, `/orders`,
`/booking-confirmation`, etc.) keep their existing inline copy
and can migrate incrementally — no breaking refactor in this
commit.

**Files created:**
- `src/components/ApiErrorState.tsx`
- `src/components/EmptyState.tsx`

---

## Fix 4 — React `setState`-in-render warning during `/services` navigation (🟢 MEDIUM)

**Issue:** Console warning during initial Services-page render:
*"Cannot update a component (Services) while rendering a
different component (BookingSidebar). To locate the bad
setState() call inside Services …"*. Console-only — no runtime
break — but visible during dev/demo.

**Root cause:** Two synchronous state dispatches landed inside
the parent's first render flush in dev/strict mode:
1. `useSubNavSync` (consumed by `Services`) called
   `setActiveSlug(...)` synchronously inside its mount
   `useEffect`. Strict mode's double-effect fire put the second
   dispatch into the same render cycle as the first.
2. `BookingSidebar`'s auth-hydration and default-location
   effects called `update(patch)` from `useBookingContext()`,
   whose provider lives in the parent tree.

**Fix:** Wrapped both dispatches in `queueMicrotask(...)`. The
microtask schedules the dispatch after the current render
cycle drains, eliminating the warning without changing
observable behavior — observer-based subsequent updates still
arrive in time for the user's first scroll.

**Files modified:**
- `src/hooks/useSubNavSync.ts` (initial-active-fallback dispatch)
- `src/components/BookingSidebar.tsx` (two auth-hydration paths)

**Test that now guards this:**
- The Playwright probe used during PART A audit picked up the
  original warning verbatim. It is no longer reachable with
  the deferred dispatches; future regressions would surface in
  any console-error guard test (smoke, edges) since both
  parent pages also navigate-by-URL during their suites.

---

## Fix 5 — Brittle coupon assertion broke on legitimate admin edits (drift-resilience)

**Issue:** `tests/e2e/coupon-flow.spec.ts` asserted
`FIRST10 && ACCOOL20 && SAVER15` are visible. With Filament
admin live (Phase 4.2), operators legitimately add/deactivate
coupons via the admin panel. Operator's local DB had ATUL500
added and SAVER15 deactivated, which broke the test.

**Root cause:** Test conflated "API integration works" with
"specific seed rows still exist verbatim".

**Fix:** Rewrote the assertion to "at least one of the
canonical seeded codes is visible AND no error UI surfaces."
Polls up to 15s using `expect.poll(...)` to absorb cold-cache
chunk load + initial fetch.

**Files modified:**
- `tests/e2e/coupon-flow.spec.ts` (test #1 only — second test
  unchanged)

---

## Fix 6 — `/api/v1/*` rate-limit (429) tripping the no-failure sweep

**Issue:** When the full Playwright suite runs back-to-back,
the dev backend's 60 rpm public-API rate limiter kicks in
mid-sweep. The new "No critical-page API call returns 4xx/5xx"
test counted 429 as a failure, producing flaky red runs only
when run with the rest of the suite (passes in isolation).

**Root cause:** Test asserted blanket 4xx/5xx ban without
distinguishing rate-limit responses (which are
**by-design** behavior).

**Fix:** Filter `status === 429` from the failure list. The
test still catches genuine 4xx/5xx (auth break, contract
mismatch, server error) but tolerates the rate limiter under
load.

**Files modified:**
- `tests/e2e/api-integration.spec.ts` (third test only)

---

## Cleanup

- **Removed temporary probe spec:** `tests/e2e/api-probe.spec.ts`
  (used during PART A audit; deleted after writing PHASE4_2_5_NETWORK_LOG.txt).
- **Removed temporary playwright project:** the `probe` project
  block in `playwright.config.ts` is replaced by the permanent
  `api-integration` project pointing to the new spec.
