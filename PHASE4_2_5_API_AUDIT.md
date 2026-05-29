# Phase 4.2.5 — Frontend API Audit

**Date:** 2026-05-08
**Scope:** Read-only audit of every frontend API integration
to diagnose operator's reported "Couldn't load coupons" issue
and surface silent fallbacks that hide other failures.

---

## 1. Executive summary

| Category | Count | Detail |
|---|---|---|
| Customer-facing GET endpoints probed | 6 | `/home`, `/coupons`, `/services`, `/services/{slug}`, `/service-centers`, `/vehicle/brands` — **all return 200**. |
| Endpoints with browser-replay failure | 0 | No live integration is broken under the seeded backend. |
| Pages that render fully from STATIC data (no API) | 1 critical + several callsites | `/service-centers` → renders from `LOCATIONS` const; Home / Header / Footer mix static `LOCATIONS`/`TESTIMONIALS` with API. |
| Silent `?? []` fallbacks (data path) | 22 | Listed below. Most pair with explicit `isError` UI on the consumer; a few don't. |
| Silent fallbacks where consumer also ignores `isError` | **1** | **CouponPickerModal** — error state collapses to "No coupons available" (this is most likely what operator saw). |
| React console errors during navigation | 1 | `setState` in render: BookingSidebar updates BookingContext during Services initial render. |
| Filament side-effect findings | 0 | CORS, Sanctum, middleware all unchanged from Phase 4.1. |

---

## 2. Endpoint inventory

### Customer-facing API surface (frontend → backend)

| Hook / Caller | Endpoint | Method | Auth | Used on |
|---|---|---|---|---|
| `useApiQuery` (Home) | `/home` | GET | No | Home, Header, Footer (via cache) |
| `useCoupons("marketing")` | `/coupons?context=marketing` | GET | No | Coupons, Offers |
| `useCoupons("cart")` | `/coupons?context=cart` | GET | Optional bearer / X-Cart-Session | CouponPickerModal |
| `useApiQuery` (Services) | `/services` | GET | No | Services |
| `useApiQuery` (ServiceCategory) | `/services/{slug}` | GET | No | ServiceCategory |
| `useApiQuery` (ServiceDetail) | `/services/{cat}/{svc}` | GET | No | ServiceDetail |
| `useBrands` | `/vehicle/brands` | GET | No | BookingSidebar, EstimateProcess, ServiceCategory |
| `useModels` | `/vehicle/models` | GET | No | (same) |
| `useFuels` | `/vehicle/fuels` | GET | No | (same) |
| `usePricing` (POST) | `/pricing` | POST | No | (legacy — Phase 2.6a inlined into /services) |
| `useCart` | `/cart` | GET | Bearer or X-Cart-Session | every page (Header) |
| `useCart.addItem` | `/cart/items` | POST | (same) | Services, ServiceCategory, ServiceDetail |
| `useCart.applyCoupon` | `/cart/coupon` | POST | (same) | Cart, CouponPickerModal |
| `useCart.removeCoupon` | `/cart/coupon` | DELETE | (same) | Cart |
| `useServiceCenters` | `/service-centers` | GET | No | Checkout |
| `useOrders` | `/user/orders` | GET | Bearer | MyBookings |
| `useOrders.fetchOrder` | `/user/orders/{id}` | GET | Bearer | OrderDetail, BookingConfirmation |
| `useOrders.cancel` | `/user/orders/{id}/cancel` | POST | Bearer | OrderDetail |
| `useAuth.sendOtp` | `/auth/send-otp` | POST | No | AuthModal |
| `useAuth.verifyOtp` | `/auth/verify-otp` | POST | No (issues bearer) | AuthModal |
| `useAuth.logout` | `/auth/logout` | POST | Bearer | Header |
| Checkout quote | `/checkout/quote` | POST | Optional | Checkout |
| Checkout place-order | `/checkout/place-order` | POST | Bearer | Checkout |
| `usePage` (defined, unused) | `/pages/{slug}` | GET | No | (no consumers) |

### Curl probe results

```
GET /home                       -> 200 (13200B)
GET /coupons                    -> 200 (853B)
GET /coupons?context=marketing  -> 200 (853B)
GET /coupons?context=cart       -> 200 (853B)
GET /services                   -> 200 (11657B)
GET /services/car-ac-service-repair  -> 200 (1248B)
GET /service-centers            -> 200 (1331B)
GET /vehicle/brands             -> 200 (1056B)
GET /cart  (no session)         -> 400  expected — "Cart session required"
```

**Browser-replay log via Playwright probe** (see archive below):

| Page | Endpoint hits | Notes |
|---|---|---|
| `/` | `/home`, `/cart` | both 200 |
| `/coupons` | `/cart`, `/home`, `/coupons?context=marketing` | all 200 |
| `/services` | `/cart`, `/home`, `/services`, `/vehicle/brands` | all 200 |
| `/category/car-ac-service-repair` | `/cart`, `/home`, `/services/car-ac-service-repair`, `/vehicle/brands` | all 200 |
| `/cart` | `/cart`, `/home` | 200 (Header issues both); the cart page itself relies on the cached `useCart` |
| `/service-centers` | `/cart`, `/home` | **NO call to `/service-centers`** — page is static |
| `/about`, `/contact` | `/cart`, `/home` | 200 (no page-specific API) |

### CORS / preflight check

```
OPTIONS /api/v1/coupons (Origin http://localhost:3000)
< HTTP/1.0 204 No Content
< Access-Control-Allow-Origin: http://localhost:3000
< Access-Control-Allow-Methods: GET
< Access-Control-Allow-Headers: authorization,content-type
< Access-Control-Max-Age: 3600
```

CORS is correct and unchanged since Phase 4.1.

---

## 3. "Couldn't load coupons" diagnosis

**Reproducibility:** Could not reproduce against the running
backend. `/api/v1/coupons` returns 200 on every probe (curl,
Playwright headed). The `/coupons` page renders ATUL500 /
FIRST10 / ACCOOL20 cards correctly when navigated to fresh.

**Most likely root cause for the operator's report:**

The string **"Couldn't load coupons."** appears on
`src/pages/Coupons.tsx:82` only when `useCoupons("marketing")`
returns `isError === true`. The hook's `queryFn` throws when
`fetchCoupons` errors at the network layer (timeout, CORS
preflight failure, 5xx, JSON parse failure). With the backend
healthy, this path doesn't fire — but during operator's
manual-verification window, the backend was being restarted
several times (filament cache clear, route:list, etc.). A
single in-flight request during a restart returns
ECONNREFUSED → fetch rejection → useCoupons.isError=true →
"Couldn't load coupons" UI surfaces correctly.

**Why this still matters:**

In the cart-side path (`CouponPickerModal`), the same hook is
used but the consumer **does NOT** check `isError`. On the
same network blip it would silently render "No coupons
available right now." — indistinguishable from a healthy
empty list. That is a real D-4.2.5-2 violation and the
highest-leverage fix in this commit.

---

## 4. Silent fallbacks inventory

### 4.1 Acceptable defensive `?? []` (consumer ALSO surfaces error)

These pair the empty-array fallback with explicit `isError`
or `error` UI, so the user is never silently misled.

| File | Line | Why OK |
|---|---|---|
| `src/pages/Sitemap.tsx` | 40, 45 | Sitemap; static-friendly degrade is acceptable. |
| `src/pages/Services.tsx` | 77, 104, 124, 412 | `servicesQuery.error` surfaced separately. |
| `src/pages/ServiceDetail.tsx` | 732 | "Related services" — secondary; missing-related is fine. |
| `src/pages/ServiceCategory.tsx` | 104, 226, 227, 228 | Brand/model/fuel and sub-services have explicit error UI in the booking sidebar (lines 1470, 1543, 1620+). |
| `src/pages/Home.tsx` | 33, 45, 46 | Sub-services error surfaced at line 581 ("Could not load services"). |
| `src/pages/BookingConfirmation.tsx` | 35 | `order` is null-checked separately. |
| `src/hooks/useOrders.ts` | 31 | Consumer (MyBookings) shows error on isError. |
| `src/hooks/useCoupons.ts` | 28 | Coupons page shows error; CouponPickerModal does NOT (see 4.2). |
| `src/components/BookingSidebar.tsx` | 86, 87, 88 | Each list has explicit "Couldn't load brands/models/fuel types" UI. |
| `src/hooks/useServiceCenters.ts` | 22 | Checkout consumes; Checkout already shows fallback (see 4.3). |
| `src/components/Header.tsx` | 140, 147, 501, 643 | Mega-menu degrades gracefully (no impact when empty). |
| `src/components/EstimateProcess.tsx` | 188, 195 | Has explicit error UI at 647, 686. |

### 4.2 SILENT — needs fix in this commit

| File | Line | Problem |
|---|---|---|
| `src/components/CouponPickerModal.tsx` | 170-179 | No `isError` branch — error degrades to "No coupons available". |

### 4.3 Architecture-direction violation (memory: "no static fallbacks")

| File | Line | Problem |
|---|---|---|
| `src/pages/ServiceCenters.tsx` | 4, 27 | Imports and renders entirely from `data/businessData.ts → LOCATIONS`. The backend `/api/v1/service-centers` endpoint exists, returns 4 seeded rows, but the page never calls it. |

### 4.4 Static usage that we are NOT migrating in this commit

These are out-of-scope per HARD CONSTRAINT "Fix ONLY actual
broken integrations." Documented for Phase 4.5+ planning.

| File | Static import | Use |
|---|---|---|
| `src/components/BookingSidebar.tsx` | LOCATIONS | Default-location hydration + name lookup |
| `src/components/Header.tsx` | (no LOCATIONS) | (uses API only) |
| `src/components/Footer.tsx` | LOCATIONS | Footer center list |
| `src/components/EstimateProcess.tsx` | LOCATIONS | Modal location list |
| `src/pages/Home.tsx` | LOCATIONS, TESTIMONIALS | Hero locations strip + testimonial carousel |
| `src/pages/ServiceCategory.tsx` | LOCATIONS, TESTIMONIALS | Sidebar + reviews section |
| `src/pages/ServiceDetail.tsx` | LOCATIONS, TESTIMONIALS | Sidebar + reviews section |
| `src/pages/ServiceCenterDetail.tsx` | LOCATIONS | Detail page lookup |
| `src/pages/Sitemap.tsx` | LOCATIONS | Sitemap |
| `src/pages/Testimonials.tsx` | TESTIMONIALS | Page |

These will surface in Phase 4.5 (SEO/CMS pages) or a dedicated
"static-to-API" sprint.

---

## 5. React state warning during navigation

**Symptom (Playwright probe):**
```
services | Cannot update a component (Services) while rendering a
different component (BookingSidebar). To locate the bad setState()
call inside Services, follow the stack trace ...
```

**Root cause:** `BookingSidebar` mounts inside `Services.tsx`.
On its first render BookingSidebar's `useEffect` (line 91-103)
calls `update(patch)` from `useBookingContext()` — and the
context provider lives in the Services-page parent tree.
React 18's strict-mode renders the effect twice; the second
synchronous `update(patch)` lands while React is still
flushing the parent (Services) render.

This is a console warning, not a runtime break. Tracked here
for completeness; we'll fix it in PART C (move the side-effect
into a `useEffect` with explicit dependencies + a "should run
once" guard, OR defer with `queueMicrotask`).

---

## 6. Filament side-effects audit

`git diff` of CORS / Sanctum / middleware between Phase 4.1
and current HEAD showed no changes (Phase 4.2 only added
Filament resources + a widget; no shared config touched):

| File | Phase 4.1 → now | Conclusion |
|---|---|---|
| `backend/config/cors.php` | unchanged | OK |
| `backend/config/sanctum.php` | unchanged | OK |
| `backend/bootstrap/app.php` | unchanged | OK |
| `backend/routes/api.php` | unchanged | OK |
| `backend/.env.example` | (Phase 4.1 added admin login note) | safe |

`composer.lock` only gained Filament-related packages; no
package was downgraded or removed.

---

## 7. Recommended fix plan (no fixes applied yet)

(See `PHASE4_2_5_FIX_PLAN.md` for full table.)

1. **🔴 CRITICAL** — `CouponPickerModal` ignores `isError`.
   Fix: surface explicit error state with retry button.
2. **🟡 HIGH** — `/service-centers` page is fully static.
   Fix: call `useServiceCenters()`; show skeleton, error,
   empty states; merge static feature/image/rating onto
   API rows by slug as a presentation enhancement (until
   the backend grows those columns in 4.5+).
3. **🟡 HIGH** — Build reusable `ApiErrorState` and
   `EmptyState` so future hooks have a consistent error
   path.
4. **🟢 MEDIUM** — React `setState`-in-render warning in
   BookingSidebar. Fix: defer the auth-hydration and
   default-location patches with `queueMicrotask`.
5. **⚪ LOW** — Document remaining LOCATIONS/TESTIMONIALS
   static usages for Phase 4.5+ migration; do not migrate
   in this commit.

---

## Appendix A — Raw probe log

Saved verbatim to `PHASE4_2_5_NETWORK_LOG.txt` (sister file).
