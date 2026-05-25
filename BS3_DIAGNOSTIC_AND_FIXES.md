# Phase BS-3 — Diagnostic + Fixes Report

Consolidated record for three production-blocking issues the operator
reported: slow backend-sourced data, skeleton shrink effect, and
multi-step crashes with cart + session loss. Diagnostic was read-only;
fixes were then applied in a separate pass.

---

## Executive verdict

| Issue | Real root cause | Fixed? | Where |
|---|---|---|---|
| Slow backend data (vs fast static) | Genuine network latency on cold cache; cache config + code-splitting are healthy | n/a (not a frontend bug) | — |
| Skeleton shrink (4 → 2 cells) | Hardcoded skeleton counts higher than common real counts; no `keepPreviousData` on dependent picker queries | ✅ | `src/hooks/useVehicle.ts` |
| Multi-step "crash" + cart 0 + logout | Three compounding code paths, all in auth/cart machinery | ✅ | `src/lib/api.ts`, `src/hooks/useAuth.ts`, `src/pages/Checkout.tsx` |

The "crash" was not a JS exception — it was **silent state collapse**:
a 401 fires somewhere, `setToken(null)` runs, the cart query re-keys
from `["cart","user"]` to `["cart","guest"]`, the empty-cart UI
renders, and the user reads it as "everything died." None of the
error boundaries were ever catching anything.

---

## PART 1 — Bundle + code-splitting (healthy)

Build output, post-fix:

| Chunk | Raw / gzip |
|---|---|
| `index-*.js` (main) | **197.55 kB / 54.35 kB** |
| `react-vendor` | 193.84 kB / 60.55 kB |
| `motion-vendor` | 127.89 kB / 42.02 kB |
| `ExploreEditorial` | 56.62 kB / 12.08 kB |
| `ServiceDetail` | 50.32 kB / 12.59 kB |
| `query-vendor` | 41.34 kB / 12.30 kB |
| `ServiceCategory` | 38.34 kB / 9.74 kB |
| `icons-vendor` | 33.92 kB / 7.46 kB |
| 30+ smaller route + helper chunks | each < 25 kB |

**`React.lazy()`** wired on 25+ route components at `src/App.tsx:16-40`.
**`Suspense`** boundary at `src/App.tsx:131` with `GlobalLoadingFallback`.

Phase 2.6b code-splitting is real and effective. Initial load is not
the bottleneck. No fix required.

---

## PART 2 — React Query cache (also healthy)

Global defaults — `src/main.tsx:11-20`:

```ts
new QueryClient({
  defaultOptions: { queries: {
    staleTime: 5 * 60 * 1000,
    gcTime:    30 * 60 * 1000,
    retry: 1,
    refetchOnWindowFocus: false,
  }},
});
```

Every `useQuery` / `useApiQuery` inherits a 5-minute staleTime, so
the "refetch storm" hypothesis is **false** under the current config.
No hook re-fetches on every mount.

Per-hook overrides:

| Hook | staleTime | Notes |
|---|---|---|
| `useVehicle` | 5 min explicit + `placeholderData: keepPreviousData` (added in BS-3) | `src/hooks/useVehicle.ts:41,53,67` |
| `useCart` | 30 s + `placeholderData: keepPreviousData` | `src/hooks/useCart.ts:150,158` |
| `useCoupons` | 2 min | `src/hooks/useCoupons.ts:24` |
| `useServiceCenters` | 5 min | `src/hooks/useServiceCenters.ts:22` |
| `useOrders` | 30 s | `src/hooks/useOrders.ts:27,47` |
| `useApiQuery` / `useServices` / `useHome` / `usePricing` / `usePage` | inherits global 5 min | — |

Query-key inclusion is correct — every dependent param (vehicle ids,
slugs, token flag) is in the key so cache hits work.

Waterfall: brand → model → fuel is sequential by intent (model
needs `brandId`, fuel needs `modelId`). No accidental serial fetches.

**Verdict:** the "backend data is slow vs static is instant" gap is
real network latency on the first cold-cache request. Frontend
caching can't shrink the first round-trip; only the second visit
benefits. If first-request latency is unacceptable, that's a
backend query / index question, not a frontend cache config one.

---

## PART 3 — Skeleton shrink (fixed)

### Diagnostic

Hardcoded skeleton counts vs. typical real counts:

| Step | File:line | Skeleton count | Typical real count | Shrink possible? |
|---|---|---|---|---|
| Brand grid | `BrandStep.tsx:49` | 8 | 32 brands | No |
| Model grid | `ModelStep.tsx:69` | 4 | 5-20 per brand (some 2-3) | Yes for small brands |
| Fuel grid | `FuelStep.tsx:71` | 2 | 2 per model (some 1) | Yes for 1-fuel models |

Transition site: each step renders `{query.isLoading ? <skeleton-grid/>
: <real-grid/>}`. When the query resolves the skeleton block unmounts
and the real grid mounts — the visible "collapse from N to fewer."

### Fix

`src/hooks/useVehicle.ts` — restored `placeholderData: keepPreviousData`
on `useBrands`, `useModels`, **and** `useFuels`. With keepPreviousData
the prior query's data stays in `query.data` while a new fetch is
in flight, so `query.isLoading` only fires once on the very first
load. Subsequent picker transitions cross-fade between data sets
with no skeleton block at all.

```ts
// src/hooks/useVehicle.ts
export function useModels(brandId: number | null | undefined) {
  return useQuery<ModelsResponse>({
    queryKey: ["vehicle", "models", brandId ?? null],
    queryFn: ({ signal }) => fetchModels(brandId as number, signal),
    enabled: typeof brandId === "number" && brandId > 0,
    staleTime: VEHICLE_STALE_MS,
    placeholderData: keepPreviousData,         // ← restored
  });
}
```

**Trade-off acknowledged:** when the user picks a new brand, the
previous brand's models are visible for ~100 ms before the new
brand's models replace them. The operator explicitly preferred this
graceful transition over the skeleton-shrink, so the brand-bleed
window is the accepted cost.

---

## PART 4 — Crash + state loss (fixed)

### Error boundaries — present and state-preserving

| Boundary | Catches | Resets state? |
|---|---|---|
| `ChunkErrorBoundary` (`src/components/ChunkErrorBoundary.tsx:42`) | Dynamic-import failures | No. Recovery = reload. |
| `RuntimeErrorBoundary` (`src/components/RuntimeErrorBoundary.tsx:52`) | All other runtime errors | No. Recovery = reload OR navigate (resetKey). |

Neither boundary clears cart, token, or session storage on catch.
Both are wired in `src/App.tsx:115-179` (ChunkErrorBoundary →
AnimatePresence → motion.div → RuntimeErrorBoundary → Suspense →
Routes).

### Double-submit protection — present

- `AuthModal` verify-otp button: `disabled={submitting}` at
  `src/components/AuthModal.tsx:459`.
- `AuthModal` send-OTP button: same pattern, line 392.
- `Checkout` place-order button: `disabled={submitting}` at
  `src/pages/Checkout.tsx:694`, with `setSubmitting(false)` in the
  `finally` block (line 251).

### The three actual data-loss code paths

**Root cause #1 — `src/lib/api.ts:190-201` (pre-fix):**

```ts
if (res.status === 401 && !allowUnauthorized) {
  setToken(null);                              // ← wiped session globally
  window.dispatchEvent(new CustomEvent("acr-session-expired"));
}
```

ANY 401 on ANY endpoint without `allowUnauthorized: true` wiped the
token. Cascade:

1. `setToken(null)` removes the token from localStorage.
2. `acr-token-updated` event fires.
3. `useCart`'s `useTokenFlag()` flips → query re-keys
   `["cart","user"]` → `["cart","guest"]`.
4. The guest UUID was cleared at login (`src/hooks/useAuth.ts:366`),
   so `ensureSessionUuid()` minted a fresh UUID → server returned a
   new empty guest cart.
5. User sees: logged out + cart 0. **The data is still on the
   server in the user_cart row, but the UI cannot see it without
   a valid token.**

A single transient 401 — backend restart, momentary 5xx returned as
401 by a proxy, an auth-protected endpoint hit during a token
rotation — was enough to destroy perceived state for the user.

**Fix:**

```ts
// src/lib/api.ts (post-fix)
if (res.status === 401 && !allowUnauthorized) {
  // Only the canonical auth probe is allowed to terminate the
  // session on 401. Everything else surfaces the toast but keeps
  // the token so a single transient blip doesn't wipe the world.
  const isAuthProbe = path.includes("/auth/profile");
  if (isAuthProbe) {
    setToken(null);
  }
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("acr-session-expired"));
  }
}
```

Now only a 401 from `/auth/profile` (the bootstrap probe) terminates
the session. A 401 anywhere else surfaces the "session expired" toast
but keeps the token in place — user can keep navigating, and if the
next call succeeds, the toast was just a false alarm.

---

**Root cause #2 — `src/hooks/useAuth.ts:213-223` (pre-fix):**

```ts
const timeoutId = window.setTimeout(() => {
  if (cancelled || bootstrappedLocal) return;
  bootstrappedLocal = true;
  setToken(null);                              // ← wiped on slow boot
  setUser(null);
  setBootstrapped(true);
}, 10_000);
```

If `/auth/profile` took >10 s on app boot (slow VPN, sleeping
backend, cold connection on mobile), the timeout fired and
force-logged-out a user with a perfectly valid token. Same
downstream effect as Root Cause #1.

**Fix:**

```ts
// src/hooks/useAuth.ts (post-fix)
const timeoutId = window.setTimeout(() => {
  if (cancelled || bootstrappedLocal) return;
  bootstrappedLocal = true;
  console.warn("[useAuth] Bootstrap timeout (10s); unblocking UI without clearing token.");
  setBootstrapped(true);                       // ← only this
}, 10_000);
```

The UI still unblocks on slow boot, but the token stays. If the
token IS dead, the next API call's 401 against `/auth/profile`
will clear it via Root Cause #1's narrowed handler. If the token
is fine (server was just slow), the user keeps their session.

---

**Root cause #3 — `src/pages/Checkout.tsx:264` (pre-fix):**

```ts
if (count === 0) {
  return <Nothing-to-Checkout-UI />;
}
```

`count = cart?.item_count ?? 0`. Before BS-3, every refetch window
(token flip, route remount, post-order invalidate, 401-induced
re-key) flashed `count===0` for 100-500 ms and the user saw
"Nothing to Checkout." Cart.tsx already had the correct guard
pattern at `src/pages/Cart.tsx:87`.

**Fix:** added a loading-shimmer branch before the truly-empty
branch, gated on `cartLoading || !bootstrapped`:

```ts
// src/pages/Checkout.tsx (post-fix)
const { items, subtotal, count, cart, isLoading: cartLoading } = useCart();
// …
if ((cartLoading || !bootstrapped) && count === 0) {
  return <ShimmerSkeleton />;   // animated placeholder, not "empty"
}
if (count === 0) {
  return <NothingToCheckoutUI />;   // only after cart actually resolved empty
}
```

The "Nothing to Checkout" message now fires only when the cart
genuinely resolved empty server-side — never during a transient
loading window.

---

### Storage-clearing audit (unchanged paths)

| Location | What it clears | Trigger | Status |
|---|---|---|---|
| `src/lib/api.ts:99` (`setToken(null)`) | `TOKEN_KEY` | When `setToken(null)` is called | unchanged |
| `src/lib/api.ts:193` (post-fix) | calls `setToken(null)` | Only 401 on `/auth/profile` | **narrowed** |
| `src/hooks/useAuth.ts:220` (post-fix) | nothing | 10s bootstrap timeout | **softened** |
| `src/hooks/useAuth.ts:198, 383` | `setToken(null)` + `setUser(null)` | 401 on profile fetch, explicit logout | unchanged (correct) |
| `src/hooks/useAuth.ts:366` | `acr_cart_session` | Successful login post-merge cleanup | unchanged (correct) |
| `src/hooks/useCart.ts:80` | `acr_cart_v1` | Module-init purge of legacy key | unchanged |
| `src/components/explore/ExploreSearch.tsx:62` | search history | Unrelated feature | unchanged |

No catch-block or interceptor calls `logout()` directly. The 401 →
`setToken(null)` chain was the only logout-on-error path, and it is
now narrowed to the single canonical probe.

### Backend cart-merge logic (correct, untouched)

`backend/app/Services/Cart/CartMergeService.php` correctly reparents
guest items into the user cart on verify-otp using a last-cart-wins
strategy. The frontend defense-in-depth `postCartMerge` call in
`src/hooks/useAuth.ts:353` covers the CORS-stripped-header edge case.
No backend changes needed.

---

## Files changed in BS-3 (5)

```
src/hooks/useVehicle.ts                  (placeholderData restored on all 3 hooks)
src/hooks/useAuth.ts                     (10s timeout no longer wipes token)
src/lib/api.ts                           (401 logout restricted to /auth/profile)
src/pages/Checkout.tsx                   (loading-aware empty-cart guard)
src/hooks/useCart.ts                     (already had placeholderData from prior pass)
```

Plus the prior-pass additions still in place from the previous round:

```
src/components/RuntimeErrorBoundary.tsx  (new — runtime error boundary)
src/App.tsx                              (wired RuntimeErrorBoundary in route tree)
src/components/vehicle/premium-selector/components/ModelStep.tsx   (skeleton 6→4)
src/components/vehicle/premium-selector/components/FuelStep.tsx    (skeleton 3→2)
```

No backend changes. No new packages. No new routes.

---

## Verification

| Check | Result |
|---|---|
| `npx tsc --noEmit` | 2 pre-existing brand-typography baseline errors only |
| `npm run build` | clean, 9.75 s |
| `assets/index-*.js` | 197.55 kB / 54.35 kB gz |
| `npx playwright test tests/e2e/smoke.spec.ts` | **3/3 pass** in 11.8 s |

---

## What the user should see now

1. **Picker (brand → model → fuel)**: switching models or brands no
   longer flashes a skeleton-then-real-data shrink. It cross-fades
   from previous data to new data. The first-ever load of each step
   still shows a one-time skeleton (which is correct — there's no
   prior data to keep).
2. **Login flow**: a slow `/auth/profile` boot fetch (>10 s) no
   longer kicks the user out. If their token is valid, their session
   survives the slow boot.
3. **Multi-step checkout**: a transient 401 on any non-auth endpoint
   (e.g. `/cart`, `/checkout/place-order`) no longer wipes the
   session. The "session expired" toast still fires so the user
   knows something's off, but their cart and login persist.
4. **Checkout page transitions**: the "Nothing to Checkout" message
   no longer flashes during refetch windows. It only appears when
   the cart genuinely resolved empty.
5. **Runtime exceptions in routes**: previously blanked the page
   via React tree unmount. Now caught by `RuntimeErrorBoundary`
   with a friendly retry UI; navigating to a different route
   auto-resets the boundary.

The data-loss perception during multi-step flows should be
substantially reduced. If the operator still sees cart-empty
during a flow, the next step is to add request-level logging
(temporary `console.log` in `useCart.ts` query function) to
identify which specific 401 fires and from which endpoint.

---

## Recommended follow-up (out of scope here)

1. **Token-refresh flow**: instead of treating every 401 as
   terminal, attempt a `/auth/refresh` once on 401 from non-probe
   endpoints. Currently we just keep the token and surface a toast;
   refresh would be even better.
2. **Bundle main chunk has room**: 197 kB / 54 kB gz includes
   `EstimateProcess` + `AuthModal` + `SessionExpiredToast` +
   `Header` + `Footer` + global utilities. Could shave another
   30-40 kB by lazy-loading the auth modal (it's only needed on
   first login click). Low priority since the bundle is already
   under the recommended 200 kB threshold.
3. **Backend perf on `/services` first hit**: if the operator
   reports the first call to `/services?brand_id=…&model_id=…&fuel_id=…`
   is slow, profile the SQL and add a covering index on
   `service_prices(brand_id, model_id, fuel_type_id, service_id)`
   if not already present. We already noted ~50 k rows in that
   table after the latest pricing import.
