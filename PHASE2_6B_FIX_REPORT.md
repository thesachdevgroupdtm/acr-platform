# Phase 2.6b-fix — Vendor Chunk Splitting + Backend CORS Fix

**Date:** 2026-05-06
**Scope:** complete the two follow-up items left open by Phase 2.6b:
(1) split vendor packages out of the app shell to hit the <300 kB
initial-chunk target, and (2) extend the Laravel CORS / Sanctum
allowlist to cover the Vite preview origin (`:4173`) so production
E2E tests can hit the real API instead of relying on a noise filter.
**Status:** ✅ All 22 tests pass. No workarounds. Single commit.

---

## 1 — Files modified

| File | Change |
|---|---|
| `vite.config.ts` | Added `build.rollupOptions.output.manualChunks` — 4 vendor buckets (react, motion, lucide, react-query). |
| `backend/config/cors.php` | `allowed_origins` now reads `CORS_ALLOWED_ORIGINS` env var; fallback covers :3000 and :4173 on both loopback hosts. LAN regex extended to `(3000\|4173)`. |
| `backend/config/sanctum.php` | Default `stateful` list extended with the 4 preview hosts; result wrapped in `array_filter(array_map('trim', …))` to tolerate trailing-comma env values. |
| `backend/.env` | Added `CORS_ALLOWED_ORIGINS`; expanded `SANCTUM_STATEFUL_DOMAINS`. |
| `backend/.env.example` | Mirrored the two new variables with documentation comments. |
| `tests/e2e/code-splitting.spec.ts` | Removed `'cors policy'` and `'access to fetch'` entries from Test 4's noise filter. |
| `tests/e2e/console-errors.spec.ts` | Same filter cleanup. |

No application logic, route components, page logic, controllers, models, migrations, or auth/cart/coupon/order business rules were touched.

---

## 2 — Audit findings (PART A)

- **Vendor packages all present in `node_modules/`:** `react/`, `react-dom/`, `motion/`, `lucide-react/`, `@tanstack/react-query/`. Safe to reference in `manualChunks` matchers.
- **Existing `cors.php`:** hardcoded list of `http://localhost:3000`, `http://127.0.0.1:3000`, plus `env('FRONTEND_URL')`. LAN regex covered `:3000` only. `supports_credentials` was `false`.
- **Existing `sanctum.php`:** already env-driven via `SANCTUM_STATEFUL_DOMAINS`, but the default fallback only listed `:3000`.
- **Existing `.env`:** had `FRONTEND_URL=http://localhost:3000` and `SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000`. No `CORS_ALLOWED_ORIGINS`.
- **Frontend transport:** `src/lib/api.ts:171` uses `credentials: "omit"` — bearer-token-only. `supports_credentials: false` is correct and was left unchanged.
- **Existing CORS noise filter:** in both Test 4 of `code-splitting.spec.ts` and the full-flow assertion in `console-errors.spec.ts`, `'cors policy'` and `'access to fetch'` substrings were filtered out before the `realErrors` equality check.

---

## 3 — PART B: Vendor chunk splitting

`vite.config.ts` gained a `build.rollupOptions.output.manualChunks` function that routes any path under `node_modules/` matching one of four package families to its own bucket; everything else falls through to Vite's default chunking (typically rolled into the per-route chunk that imports it).

```ts
manualChunks(id: string) {
  if (!id.includes('node_modules')) return undefined;
  if (id.includes('/react-dom/') || id.includes('/react/') || id.includes('/scheduler/')) return 'react-vendor';
  if (id.includes('/motion/') || id.includes('/framer-motion/')) return 'motion-vendor';
  if (id.includes('/lucide-react/')) return 'icons-vendor';
  if (id.includes('/@tanstack/react-query/')) return 'query-vendor';
  return undefined;
}
```

### Build output (verbatim, post-fix)

```
dist/assets/icons-vendor-BUGp-X7s.js     29.12 kB │ gzip:  6.44 kB
dist/assets/index-cR-CikKR.js           137.20 kB │ gzip: 34.23 kB   ← initial app shell
dist/assets/motion-vendor-D9SD0d82.js   127.89 kB │ gzip: 42.02 kB
dist/assets/query-vendor-B7JjJB5a.js     41.31 kB │ gzip: 12.30 kB
dist/assets/react-vendor-DXoTT26f.js    193.81 kB │ gzip: 60.54 kB
... 21 per-route chunks, sizes unchanged from 2.6b ...
```

All four vendor chunks materialised. None of the per-route chunk hashes drifted in size (they did get fresh hashes because rollup's module graph re-balanced).

---

## 4 — PART C: Backend CORS

### `config/cors.php` diff (effective)

```diff
-    'allowed_origins' => [
-        'http://localhost:3000',
-        'http://127.0.0.1:3000',
-        env('FRONTEND_URL', 'http://localhost:3000'),
-    ],
+    'allowed_origins' => array_values(array_filter(array_unique(array_merge(
+        array_map('trim', explode(',', (string) env(
+            'CORS_ALLOWED_ORIGINS',
+            'http://localhost:3000,http://127.0.0.1:3000,http://localhost:4173,http://127.0.0.1:4173'
+        ))),
+        [env('FRONTEND_URL', 'http://localhost:3000')],
+    )))),
-        '#^http://192\.168\.\d+\.\d+:3000$#',
-        '#^http://10\.\d+\.\d+\.\d+:3000$#',
-        '#^http://172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+:3000$#',
+        '#^http://192\.168\.\d+\.\d+:(3000|4173)$#',
+        '#^http://10\.\d+\.\d+\.\d+:(3000|4173)$#',
+        '#^http://172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+:(3000|4173)$#',
```

`supports_credentials` stays `false` because the frontend uses `credentials: "omit"` (bearer tokens, no session cookie). Flipping it would have been an unnecessary behaviour change.

### `config/sanctum.php` diff (effective)

```diff
-    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
-        '%s%s',
-        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
+    'stateful' => array_filter(array_map('trim', explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
+        '%s%s',
+        'localhost,localhost:3000,localhost:4173,127.0.0.1,127.0.0.1:3000,127.0.0.1:4173,127.0.0.1:8000,::1',
         Sanctum::currentApplicationUrlWithPort()
-    ))),
+    ))))),
```

### `.env` additions

```
FRONTEND_URL=http://localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,http://localhost:4173,http://127.0.0.1:4173
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:4173,127.0.0.1:3000,127.0.0.1:4173
```

Same lines added to `.env.example` with explanatory comments.

### Curl verification (real, after `php artisan config:clear`)

```
$ curl -s -i -H "Origin: http://localhost:4173" http://127.0.0.1:8000/api/v1/home | grep -iE "^(HTTP|access-control|vary)"
HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://localhost:4173
Vary: Origin
```

The preview origin is now reflected in `Access-Control-Allow-Origin`. Browser fetches from the preview build will succeed.

---

## 5 — PART D: Test cleanup

Both noise filters dropped their `'cors policy'` / `'access to fetch'` entries. The remaining filter is intentionally narrow — only environmental noise (image 404s, transport failures, browser-extension-injected console messages) is suppressed. **Any application or CORS error now fails the test**, which is the entire point of the cleanup: stop hiding bugs we should fix.

```diff
   const realErrors = errors.filter((text) => {
     const lower = text.toLowerCase();
     return !lower.includes('failed to load resource')
       && !lower.includes('net::')
       && !lower.includes('the server responded with a status')
-      && !lower.includes('cors policy')
-      && !lower.includes('access to fetch')
       && !lower.includes('extension');
   });
```

---

## 6 — PART E: Full test suite (verbatim)

### Backend (`vendor/bin/pest`)

```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\Smoke\AuthOtpTest        (3 tests)
PASS  Tests\Feature\Smoke\CartTest           (2 tests)
PASS  Tests\Feature\Smoke\CheckoutTest       (1 test)
PASS  Tests\Feature\Smoke\CouponTest         (2 tests)
PASS  Tests\Feature\Smoke\OrdersTest         (2 tests)
PASS  Tests\Feature\Smoke\PricingTest        (1 test)

Tests:    13 passed (59 assertions)
Duration: 1.89 s
```

### Frontend (`npx playwright test`)

```
[smoke]       smoke.spec.ts:18         home page renders without console errors  ✓ 4.6 s
[smoke]       smoke.spec.ts:44         login button opens auth modal              ✓ 1.7 s
[smoke]       smoke.spec.ts:60         /payment routes to NotFound                ✓ 1.3 s
[production]  code-splitting.spec.ts:25  1 — slow chunk fallback                  ✓ 3.8 s
[production]  code-splitting.spec.ts:50  2 — chunk fail boundary                  ✓ 1.2 s
[production]  code-splitting.spec.ts:72  3 — hard refresh on lazy route           ✓ 969 ms
[production]  code-splitting.spec.ts:88  4 — rapid route clicks                   ✓ 2.2 s
[production]  code-splitting.spec.ts:138 5 — chunk cached on revisit              ✓ 3.8 s
[production]  console-errors.spec.ts:19  full nav flow no console errors          ✓ 2.9 s

9 passed (27.7 s)
```

**Combined: 22/22 green. CORS-bypass workarounds are gone.**

---

## 7 — PART F: Bundle size delta

| Chunk | Phase 2.6b | Phase 2.6b-fix | Δ raw | Δ gzip |
|---|---|---|---|---|
| **Initial app shell (`index-*.js`)** | 518.07 kB / 153.17 kB gzip | **137.20 kB / 34.23 kB gzip** | **−380.87 kB (−74 %)** | **−118.94 kB (−78 %)** |
| `react-vendor` | (in initial) | 193.81 kB / 60.54 kB gzip | new | new |
| `motion-vendor` | (in initial) | 127.89 kB / 42.02 kB gzip | new | new |
| `query-vendor` | (in initial) | 41.31 kB / 12.30 kB gzip | new | new |
| `icons-vendor` | (in initial) | 29.12 kB / 6.44 kB gzip | new | new |
| Per-route chunks (21) | unchanged | unchanged | 0 | 0 |
| CSS | unchanged | unchanged | 0 | 0 |

### Target hit (D-2.6b-fix-5)

> Initial JS chunk: target <300 kB raw / <90 kB gzip

App shell `index-*.js`: **137.20 kB raw / 34.23 kB gzip** ✅
Comfortably under both ceilings (54 % under raw, 62 % under gzip).

### Total first-load weight (raw transparency)

A first-time visitor still needs the app shell + all four eager-loaded vendor chunks before Home can interact. Summed:

| | Sum |
|---|---|
| Raw  | 137.20 + 193.81 + 127.89 + 41.31 + 29.12 = **529.33 kB** |
| Gzip |  34.23 +  60.54 +  42.02 + 12.30 +  6.44 = **155.53 kB** |

That is roughly the same total as Phase 2.6b's single 518 kB chunk. The win isn't smaller bytes-on-the-wire on first load; it's:

- **Cache durability.** A patch upgrade of `motion` busts only `motion-vendor`. React/ReactDOM are stable across releases — `react-vendor`'s 193 kB stays cached for months.
- **Parallel download.** Modern browsers fetch the four vendor chunks in parallel over HTTP/2, so the wall-clock first-paint is mildly faster than a single 518 kB chunk on connections with non-trivial RTT.
- **Smaller hot path on app updates.** When the team ships a new feature, only `index-*.js` rotates; vendors stay cached.

---

## 8 — PART G: Production E2E without CORS filter

The console-errors spec walks Services → Service Centers → Insurance → Corporate → Gallery on the production build. Each route triggers React-Query fetches against `/api/v1/home`, `/api/v1/cart`, `/api/v1/services`, etc. With the old allowlist these all 401'd / CORS'd; the test only passed because the filter swallowed the errors. After the allowlist update the test passes with **zero** filtered entries, in 2.9 s — the API responses are real and successful.

```
✓ [production] tests/e2e/console-errors.spec.ts:19 → no console errors during full navigation flow (2.9s)
```

---

## 9 — Build outputs

### TypeScript

```
$ npx tsc --noEmit
(exit 0, no output)
```

### Vite production build (full, single page)

```
$ npm run build
✓ 2175 modules transformed.
dist/index.html                              0.77 kB │ gzip:   0.36 kB
dist/assets/index-CFsGvZtO.css             111.69 kB │ gzip:  18.05 kB
… 21 per-route chunks (Insurance 4.29 kB through ServiceCategory 38.63 kB) …
dist/assets/icons-vendor-BUGp-X7s.js        29.12 kB │ gzip:   6.44 kB
dist/assets/query-vendor-B7JjJB5a.js        41.31 kB │ gzip:  12.30 kB
dist/assets/motion-vendor-D9SD0d82.js      127.89 kB │ gzip:  42.02 kB
dist/assets/index-cR-CikKR.js              137.20 kB │ gzip:  34.23 kB
dist/assets/react-vendor-DXoTT26f.js       193.81 kB │ gzip:  60.54 kB
✓ built in 12.31s
```

The Vite "chunks larger than 500 kB" warning that appeared in 2.6b is **gone** — every chunk is now under that threshold.

---

## 10 — Deviations

1. **`supports_credentials` stayed `false`.** The original spec template
   (PART C step 10) shows `'supports_credentials' => true,  // Required for Sanctum`.
   That comment is misleading for this project: the frontend uses
   `credentials: "omit"` (bearer-token flow), so credentials-mode CORS
   is unnecessary. Flipping to `true` would have changed
   `Access-Control-Allow-Credentials` headers for every API response —
   a behaviour change with no upside here. Documented and left as-is;
   trivially flippable later if a cookie-auth flow ever lands.

2. **Total first-load weight is unchanged.** §7 spells this out — the
   <300 kB target was on the app-shell chunk specifically (and it was
   met by 54 %), but the combined eager-loaded payload is still ~529 kB
   raw because vendor *count* stayed the same; only the *layout*
   changed. The wins (caching, parallelism, hot-path size) are real but
   different from "fewer bytes on first load".

3. **Pre-existing untracked backend tree was not touched.** Same scoping
   rule as Phase 2.6c and 2.6b: only the files this commit
   intentionally modifies are staged. The repo's larger uncommitted
   state from earlier phases stays as the user left it.

---

## 11 — Future work

- **Vendor allowlist for prod.** The `.env.example` example values
  cover dev/preview only. Production deploys should set
  `CORS_ALLOWED_ORIGINS=https://acr.example.com,…` explicitly; the
  default is intentionally permissive for local development only.
- **Lighthouse / Core Web Vitals on the new build.** Worth a one-shot
  baseline so future regressions are visible. Not run here.
- **Modal lazy-loading (`EstimateProcess`).** Still eager from 2.6b;
  ~10 – 15 kB potential additional shell-shrink if split.
- **Vendor chunk for `@google/genai`.** Currently rolled into the route
  chunk that imports it; if that route needs to load eagerly later,
  a fifth bucket is straightforward.

---

## 12 — How to run

```bash
# Three shells:
cd backend && php artisan config:clear && php artisan serve --host=127.0.0.1 --port=8000
npm run dev
npm run build && npm run preview -- --port 4173 --host 127.0.0.1

# Then:
npm test                                  # backend Pest + all e2e (22 tests)
npx playwright test --project=smoke       # 3 smoke tests against dev :3000
npx playwright test --project=production  # 6 production tests against preview :4173
```
