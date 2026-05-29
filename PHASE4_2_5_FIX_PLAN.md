# Phase 4.2.5 — Fix plan

Severity legend (per PART B):
- 🔴 CRITICAL — user-visible break, blocks core flow
- 🟡 HIGH — user-visible break or architecture-direction violation
- 🟢 MEDIUM — silent fallback, no immediate user impact
- ⚪ LOW — minor inconsistency

| # | Issue | Severity | Fix approach | Effort |
|---|---|---|---|---|
| 1 | `CouponPickerModal` ignores `useCoupons` `isError` — error path silently shows "No coupons available". This is the most plausible source of the operator's "Couldn't load coupons" report when it occurred in cart context. | 🔴 | Pull `isError`, `error`, `refetch` from the hook; render explicit `<ApiErrorState>` with retry button when `isError === true`. | 15 min |
| 2 | `/service-centers` page renders entirely from static `LOCATIONS`. Architecture-direction memory: "Target architecture is full API-driven CMS — no static fallbacks." | 🟡 | Replace static loop with `useServiceCenters()`. Render skeleton/error/empty/list states. Merge static `feature/image/rating` onto API rows by slug as a presentation enhancement (until backend grows those columns in 4.5+). | 30 min |
| 3 | No reusable error UI. Every page rolls its own "Couldn't load X" copy. | 🟡 | Build `src/components/ApiErrorState.tsx` and `src/components/EmptyState.tsx`. Consume in fixes #1 + #2; future hooks can adopt incrementally. | 20 min |
| 4 | React `setState`-in-render warning when navigating to `/services`: `BookingSidebar` calls `update(patch)` from `useBookingContext()` during the auth-hydration `useEffect`, which lands while the parent (`Services`) is still rendering. | 🟢 | Defer the patch with `queueMicrotask(() => update(patch))` — or use a startTransition. Keeps the side-effect in the same effect, just outside the React render flush. | 15 min |
| 5 | 22 `?? []` defensive fallbacks scattered across `useOrders`, `useCoupons`, `useServiceCenters`, page-level memo derivations. Most pair with explicit `isError` UI on consumers — these are NOT silent. | 🟢/⚪ | Document as acceptable in audit. No fix this commit. | — |
| 6 | `LOCATIONS` / `TESTIMONIALS` static imports across Header/Footer/Home/ServiceCategory/etc. The `/service-centers` migration (#2) demonstrates the pattern; broader migration is Phase 4.5+ scope. | ⚪ | Document. No fix this commit. | — |

## New tests planned (PART D)

### Backend Pest contract tests (`tests/Feature/Api/V1/EndpointContractsTest.php`)

| # | Test | Endpoint |
|---|---|---|
| 1 | GET /home returns expected shape | /api/v1/home |
| 2 | GET /coupons returns only active coupons | /api/v1/coupons |
| 3 | GET /coupons?context=cart returns same shape | /api/v1/coupons |
| 4 | GET /services returns categories array | /api/v1/services |
| 5 | GET /services/{slug} returns category detail | /api/v1/services/{slug} |
| 6 | GET /service-centers returns only active centers | /api/v1/service-centers |
| 7 | GET /vehicle/brands returns brands list | /api/v1/vehicle/brands |

### Frontend Playwright integration tests (`tests/e2e/api-integration.spec.ts`)

| # | Test |
|---|---|
| 1 | `/coupons` page loads at least one coupon from the API |
| 2 | `/service-centers` page calls `/api/v1/service-centers` and renders ≥ 1 center |
| 3 | No critical-page API call returns 4xx/5xx (sweep across `/`, `/services`, `/coupons`, `/cart`, `/service-centers`) |
| 4 | `CouponPickerModal` renders an explicit error UI when `/coupons` 5xxs (route-mocked) |

Total new tests planned: **7 backend Pest + 4 Playwright = 11**.
Pre-4.2.5: 58 backend + 27 frontend (smoke + edges + admin) = 85.
Post-4.2.5: ~65 backend + ~31 frontend = ~96.
