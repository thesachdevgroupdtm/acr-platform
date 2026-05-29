# Phase 1 Diagnosis — `/api/v1/services/{slug}` request flood

Read-only investigation. Root cause is in the frontend data flow; the
backend response shapes are the constraint that forces it.

---

## 1. Every call site of `fetchCategoryDetail` / `useCategoryDetail` / direct apiGet to `/services/{slug}`

| # | File:line | Component | Mechanism | Trigger |
|---|---|---|---|---|
| 1 | `src/lib/api.ts:411` | n/a | The fetcher itself (`apiGet<CategoryDetailResponse>(\`/services/${slug}\`, …)`) — only call site for the URL | (definition) |
| 2 | `src/hooks/useServices.ts:40` | `useCategoryDetail` | `useQuery` (React Query) | called per-component |
| 3 | `src/hooks/useServices.ts:73` | `useAllSubServices` | `useQueries` (React Query, N parallel) | one query per category slug |
| 4 | `src/pages/Home.tsx:47–87` | **`<Home>` page** | **`useEffect` + imperative `Promise.all(...fetchCategoryDetail)`** — bypasses React Query | **Mount** (after `/home` resolves and `serviceCategories.length > 0`) |
| 5 | `src/pages/Sitemap.tsx:29–54` | `<Sitemap>` page | `useEffect` + imperative `Promise.all(...fetchCategoryDetail)` — bypasses React Query | Mount (after `/home` resolves) |
| 6 | `src/components/Header.tsx:142–172` | `<Header>` (always rendered in `App.tsx`) | `useEffect` + imperative `Promise.all(...fetchCategoryDetail)` — bypasses React Query | First time `activeDropdown === "services"` (hover) |
| 7 | `src/pages/Services.tsx:334` (inside `<CategorySection>`) | `<Services>` page → 12× `<CategorySection>` children | `useApiQuery` (React Query shim) | Mount of each CategorySection (one per category) — fires N parallel queries |
| 8 | `src/pages/ServiceCategory.tsx:85–88` | `<ServiceCategory>` page | `useApiQuery` (React Query shim) | Mount, single slug only |

Sites 4, 5, 6, 7 are **N×** patterns (one request per service category).
Site 8 is the only legitimate single-slug consumer (a category detail
page that needs exactly that one category's data).

---

## 2. React Query key stability

| Site | Key | Stable? |
|---|---|---|
| 2 (`useCategoryDetail`) | `["category-detail", slug, vehicle ?? null]` | Stable — `slug` is a string prop; `vehicle` should be passed as a memoized object. Caller's responsibility. |
| 3 (`useAllSubServices`) | `["category-detail", slug, null]` per slug in array | Stable — each child query has a string-prefixed deterministic key. |
| 7 (Services → CategorySection) | `["category-detail", category.slug, carSlugs]` where `carSlugs = useMemo({brand,model,fuel}, [car])` | **Stable** — `carSlugs` is memoized against `booking.car`; `car` is a stable ref from `useBookingContext`. Confirmed `Services.tsx:42–49` (parent useMemo on `carContext`) and `Services.tsx:326–333` (child useMemo on `carSlugs`). React Query's default `queryKeyHashFn` is `JSON.stringify`, so even a re-created object with same content rehashes to the same key. |
| 8 (ServiceCategory) | Same pattern, same memoization (`ServiceCategory.tsx:76–84`) | Stable. |

Sites 4, 5, 6 do **not** use React Query — they bypass it entirely with
imperative `Promise.all(...fetchCategoryDetail)` inside `useEffect`. They
have no key, no cache, no dedup against React Query consumers.

The `useEffect` deps in those three sites are deliberately stringified:
- Home.tsx:87 → `[serviceCategories.map((c) => c.slug).join("|")]`
- Sitemap.tsx:54 → `[categories.map((c) => c.slug).join("|")]`
- Header.tsx:172 → `[activeDropdown, apiCategories.map((c) => c.slug).join("|")]`

The string is content-equal across renders once `home.data` is stable
(React Query memoizes responses), so each effect fires **once** per
mount cycle — not in a render loop.

---

## 3. `/api/v1/home` response shape

Top-level keys (live curl):
```
['success', 'service_categories', 'car_brands', 'car_models',
 'service_centers', 'offer_slider', 'tabular_offers', 'service_packages',
 'featured_products', 'faqs', 'brand_logo_slider', 'membership_package',
 'home_page_setting', 'settings', 'seo']
```

`service_categories[i]` keys:
```
['id', 'slug', 'title', 'name', 'description', 'image', 'image_1',
 'icon_image', 'position']
```

**No nested sub-services**, **no prices**, **no service counts**.
`service_categories[].services` does not exist.

What components currently fetch `/services/{slug}` for that's missing
from `/home`:
- `services` array (the actual sub-services like `battery-charging`,
  `battery-replacement`) — required by Home's hero carousel, Sitemap's
  "All Services" column, and Header's mega-menu third-level dropdown.
- `price`, `base_price`, `time_takes`, `warrenty_info`,
  `recommended_info` per sub-service.

**The home payload alone is insufficient to render the home carousel,
the sitemap "All Services" list, or the header mega-menu sub-services.**

---

## 4. `/api/v1/services` response shape

Top-level keys:
```
['success', 'categories', 'available_category_ids', 'brand', 'model',
 'fuel', 'seo']
```

`categories[i]` keys: identical to `service_categories[i]` from `/home`.
`categories[i].services` does not exist.

`/services` is functionally a duplicate of `/home`'s
`service_categories` list (filtered by vehicle availability via
`available_category_ids`). It does **not** include sub-services either.

---

## 5. Which component fires the flood and on which route

**Definitive: `src/pages/Home.tsx` lines 47–87** — the
`useEffect` that runs `Promise.all(serviceCategories.map(c => fetchCategoryDetail(c.slug)))`
on mount once `home.data` resolves.

Sequence on home page load:
1. React Query fires `GET /api/v1/home` (1 request).
2. Response arrives (12 categories).
3. `home.data` change triggers the dep `[…join("|")]` to transition
   from `""` to `"car-battery|car-emergency-services|…"`.
4. Effect runs. `Promise.all` dispatches **12 parallel
   `GET /api/v1/services/{slug}`** requests, all sharing one
   `AbortController`.
5. The flat result array is reduced into `allSubServices` for the
   carousel render.

`<Header>` is rendered on every route by `App.tsx` but its mega-menu
fetch is gated on `activeDropdown === "services"` — does not fire on
plain home load. `<Sitemap>` only mounts on the sitemap route. Neither
is the source on a home page snapshot.

`<Services>` (the `/services` page) ALSO fires N parallel
`/services/{slug}` calls via 12 `<CategorySection>` children — but
through React Query (site 7), not the imperative path. On the home
route Services is not mounted, so it does not contribute.

---

## 6. Render-loop / unstable-deps audit

Checked all four parent `useMemo` / `useEffect` dep arrays in the affected
files:

| File | Hook | Deps | Verdict |
|---|---|---|---|
| Home.tsx | `useEffect` (sub-services flood) | `[serviceCategories.map(c => c.slug).join("|")]` | Content-stable string. No loop. |
| Sitemap.tsx | `useEffect` | `[categories.map(c => c.slug).join("|")]` | Content-stable. No loop. |
| Header.tsx | `useEffect` | `[activeDropdown, apiCategories.map(c => c.slug).join("|")]` | Content-stable. No loop. |
| Services.tsx:42–49 | `useMemo` for `carContext` | `[booking.car]` | Booking ref stable from context. No loop. |
| Services.tsx:326–333 | `useMemo` for `carSlugs` (per-card) | `[car]` (prop = `booking.car`) | Stable. No loop. |
| ServiceCategory.tsx:76–84 | `useMemo` for `carSlugs` | `[bookingCtx0.car]` | Stable. No loop. |

**No render loop exists.** Each affected effect fires exactly once per
mount cycle (twice in dev under StrictMode initial-mount).

---

## 7. Backend `/services/{slug}` pricing-detail check

`ServiceController@show` (read-only inspection of the live response):

Without vehicle params:
```json
{
  "success": true, "category": {...},
  "services": [{"id":1, "slug":"battery-charging", "price":"1500.00", "base_price":"1500.00", ...}],
  "price_show": 0, "price_list": null,
  "brand": null, "model": null, "fuel": null,
  "faqs": [], "faq_contents": null, "seo": {...}
}
```

With `?brand=…&model=…&fuel=…`: the controller resolves slugs → IDs,
queries the `service_prices` table, and overlays the resolved price
on each `services[i].price`. `price_show` flips to `1`. The same join
that exists for the per-slug endpoint could be applied to a list
endpoint with a single SQL pass.

Current behaviour: `services[i].price` always carries the static
`base_price` when no vehicle is supplied, and the resolved per-vehicle
price when one is. So `/services/{slug}` does include service
definitions and a base price even without vehicle context — it does
**not** require vehicle params just to enumerate the services in a
category.

The flooding endpoints (Home, Sitemap, Header) all call without
vehicle context, so they're getting service lists + base prices + 6
fields they don't need (faqs, faq_contents, brand, model, fuel,
price_show, price_list) repeated 12×.

---

## a) Definitive answer

**Component:** `src/pages/Home.tsx`.
**Lines:** 47–87 (the post-`/home` aggregation effect).
**Why:** the home page's hero "Specialized Care" carousel renders a
flat, filterable list of sub-services across **all** categories. Neither
`/api/v1/home` nor `/api/v1/services` returns sub-services in their
payloads, so the page falls back to firing one `/services/{slug}` per
category (currently 12) and concatenating the results in JS. This is a
straightforward N+1: the only endpoint that returns sub-services keys
them per-category.

The same N+1 pattern (different mechanism, same root cause) lives at
`Sitemap.tsx:29` (mount-time imperative flood) and `Header.tsx:142`
(first-hover imperative flood) and `Services.tsx:334` (per-card React
Query — N parallel via the shim).

## b) Is the data needed?

**Yes — the data is needed**, but the **way it is fetched is wrong**.
The Home carousel, Sitemap "All Services" column, and Header mega-menu
genuinely need every sub-service across every category. The redundancy
is in the request count, not in the data being requested. There is no
existing endpoint that returns this in one round-trip.

Per-category fetching is also returning duplicate `category` objects
12× (one wrapper per slug response) and 12× empty `faqs` arrays — about
1 KB of redundancy per request × 12 = ~12 KB of redundant payload on
top of the 12-fold round-trip latency.

## c) Correct data-flow recommendation

**Backend needs to enrich.** The cheapest, most surgical fix:

> Add `service_categories[].services` to the `/api/v1/home` response
> (and optionally to `/api/v1/services`). Each nested entry carries
> the same `services` rows the per-slug endpoint already returns,
> minus the wrapper fields (faqs, faq_contents, brand/model/fuel,
> price_show — those are slug-detail concerns, not list concerns).

With that change:
1. `Home.tsx` deletes the entire 47–87 useEffect, the
   `allSubServices`/`subsLoading`/`subsError` state, and reads
   `home.data.service_categories[].services` directly.
2. `Sitemap.tsx` does the same.
3. `Header.tsx` mega-menu reads from the same cached `useApiQuery(["home"])`
   that already runs in the header — its 142-line effect deletes too.
4. `Services.tsx` deletes the per-`<CategorySection>` `useApiQuery`
   and reads `services` from the parent's `useServiceCategories` query
   instead.
5. `ServiceCategory.tsx` (single-slug page) keeps using `/services/{slug}`
   because it also wants vehicle-resolved prices, faqs, and
   per-category SEO — the per-slug endpoint stays valid for that one
   detail-page consumer.

This collapses 13 home-page requests → 1, eliminates Sitemap's 12
parallel requests, eliminates Header's 12-on-hover, and turns Services'
12 child queries into a single parent query. **Net: 1 request to render
all of /, /services, /sitemap, and the open mega-menu.**

Alternative paths (for record):
- **A standalone `/api/v1/sub-services` flat endpoint.** Works, but
  forces every list page to issue *two* round-trips (categories +
  sub-services) instead of one. Worse than nesting.
- **Frontend-only consolidation of the four sites into a shared
  `useAllSubServices` (already exists in `src/hooks/useServices.ts`).**
  That deduplicates queries between Home/Sitemap/Header *via React Query
  cache*, but only after the first one resolves — first paint of the
  first list page still pays for 12 round-trips. The N+1 is
  fundamentally a backend-shape issue.

**Recommendation: backend nesting is the right fix.** It is additive
(adding a key to an existing response), backward-compatible (keeps
existing behaviour for legacy callers), and one ServiceCategoryResource
adjustment.

## d) Cancellation cause confirmation

**StrictMode dev-only initial-mount double-effect**, *not* a real
render loop, *not* component-unmount on navigation.

Evidence:
- `src/main.tsx:14–24` wraps `<App>` in `<StrictMode>` (React 19's
  StrictMode runs every effect setup→cleanup→setup once on first
  mount in development).
- The Home effect's cleanup (`Home.tsx:81–84`) calls `ctrl.abort()`,
  which aborts every fetch sharing that AbortController. On
  StrictMode's synthetic unmount, the in-flight 12 are aborted →
  shown as `(canceled)` in DevTools.
- StrictMode's remount immediately re-fires the effect with a fresh
  AbortController → second batch of 12 — these complete normally.
- The "2 succeeded" snapshot the user observed is the moment of
  observation: 2 of the **second** batch had completed, the rest
  were still in flight, and all 10 of the **first** batch had been
  cancel-marked. Re-checking after a moment would show all 12 of the
  second batch succeeded (24 requests total in the timeline, 12
  canceled + 12 OK).
- No component-unmount-on-nav cause: the user reported observing this
  on the home page itself, with no navigation. If they had navigated
  away, the cancellation would happen on every route change too — they
  did not report that.
- No render-loop cause: the effect dep is a content-stable joined
  string (per §6).

Production builds (without StrictMode) would show 12 requests and 12
successes — no cancellations. The doubling is dev-only. **The
cancellations are a cosmetic dev artefact; the underlying N+1 is the
real bug.**

---

## End of diagnosis

Path: `/PHASE1_DIAGNOSIS.md`. No code modified. Stopping per brief.
