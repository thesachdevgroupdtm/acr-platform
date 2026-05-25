# Phase 4.2.5b — CORS fix for Vite fallback ports

**Date:** 2026-05-08
**Trigger:** Operator reported persistent customer-facing failures
in real browser usage despite Phase 4.2.5 fixes:
- `/coupons`: "Couldn't load coupons."
- Header services dropdown: "Could not load services."
- Global toast: "API: Failed to fetch"

**Status:** ✅ Root-cause identified and fixed. Verified in a real
browser at the operator's actual port (:3001).

---

## TL;DR

**Root cause:** The operator's Vite was running on
`http://localhost:3001` (because `:3000` was already bound by a
stale dev process). The backend's CORS allowlist hard-coded
`:3000` and `:4173` only — any request from `:3001` had its
preflight returned with **no `Access-Control-Allow-Origin` header**,
so the browser blocked the fetch with a generic "Failed to fetch".

The Phase 4.2.5 frontend error UIs were doing exactly what they
were designed to do — surfacing the failure as "Couldn't load
coupons" / "Could not load services" — because the network call
genuinely failed at the CORS layer.

**Fix:** Added an `allowed_origins_patterns` regex in
`backend/config/cors.php` that covers loopback hosts on Vite's
fallback port range (3000-3010). Cleared the Laravel config
cache. Verified in a real browser at :3001 that coupons and
services now load with no CORS errors and no API failures.

---

## Diagnostic walkthrough

### STEP 1 — Verify backend is reachable

```
GET /api/v1/home    → 200 (13200B) in 0.38s
GET /api/v1/services → 200 (11657B) in 0.46s
GET /api/v1/coupons → 200 (853B)   in 0.42s
```

Backend healthy. Direct curl from terminal works.

### STEP 2 — Find the actual frontend port

```
$ netstat -ano | grep LISTEN | grep ":300"
TCP    0.0.0.0:3000           LISTENING       2596    ← stale Vite
TCP    0.0.0.0:3001           LISTENING       29100   ← operator's Vite
```

Two Vite instances running. `npm run dev` pins `--port=3000`,
but Vite **silently falls back to :3001** when :3000 is bound
by an older instance. Operator's browser opens `localhost:3001`.

### STEP 3 — Compare CORS preflight from each port

```
$ curl -X OPTIONS -H "Origin: http://localhost:3000" .../api/v1/coupons
< HTTP/1.0 204 No Content
< Access-Control-Allow-Origin: http://localhost:3000     ← header present

$ curl -X OPTIONS -H "Origin: http://localhost:3001" .../api/v1/coupons
< HTTP/1.0 204 No Content
                                                          ← NO ACAO header
```

**Smoking gun.** `:3001` preflight returns 204 but **without** the
`Access-Control-Allow-Origin` header. The browser's CORS
policy then refuses to expose the actual GET response to the
page's JavaScript → fetch() rejects → React Query enters error
state → Phase 4.2.5's error UIs render exactly the strings the
operator reported.

### STEP 4 — Locate the misconfiguration

`backend/config/cors.php` (pre-fix):

```php
'allowed_origins' => array_values(array_filter(array_unique(array_merge(
    array_map('trim', explode(',', (string) env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://127.0.0.1:3000,http://localhost:4173,http://127.0.0.1:4173'
    ))),
    [env('FRONTEND_URL', 'http://localhost:3000')],
)))),

'allowed_origins_patterns' => [
    // Only RFC1918 LAN IPs on :3000 / :4173 — no loopback fallback.
    '#^http://192\.168\.\d+\.\d+:(3000|4173)$#',
    '#^http://10\.\d+\.\d+\.\d+:(3000|4173)$#',
    '#^http://172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+:(3000|4173)$#',
],
```

Loopback hosts on any non-:3000 port (3001, 3002, …) had no
matching pattern — preflight fell through to "no allowed origin
match", which returns 204 without ACAO.

### STEP 5 — Verify routes + DB

Routes registered correctly (`php artisan route:list`).
DB has data:
- 3 active+featured coupons
- 40 active services
- 12 active categories

Neither was the cause.

---

## The fix

`backend/config/cors.php` — added one new regex pattern:

```php
'allowed_origins_patterns' => [
    // RFC1918 LAN IPs on :3000 / :4173 (existing).
    '#^http://192\.168\.\d+\.\d+:(3000|4173)$#',
    '#^http://10\.\d+\.\d+\.\d+:(3000|4173)$#',
    '#^http://172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+:(3000|4173)$#',

    // Phase 4.2.5b — Vite fallback port range. The npm script
    // pins `vite --port=3000`, but Vite silently falls back to
    // 3001/3002/… when 3000 is already bound. Pattern covers
    // loopback hosts on any Vite-range port (3000-3010).
    '#^http://(localhost|127\.0\.0\.1):30(0[0-9]|10)$#',
],
```

Then cleared the config cache (`php artisan config:clear`) so
the new pattern took effect immediately on the running backend.

`backend/.env.example` — added a comment block explaining that
`CORS_ALLOWED_ORIGINS` does not need to be edited for Vite
fallback ports (the regex handles them).

---

## Verification

### Curl preflight after fix

```
Origin :3000 → ACAO: http://localhost:3000   ✓ (existing)
Origin :3001 → ACAO: http://localhost:3001   ✓ (NEW)
Origin :3002 → ACAO: http://localhost:3002   ✓ (NEW)
Origin 127.0.0.1:3001 → ACAO: http://127.0.0.1:3001  ✓ (NEW)
```

All four origins now get the correct `Access-Control-Allow-Origin`
response header.

### Browser verification (the actual fix gate)

`tests/e2e/cors-3001-verify.spec.ts` — new Playwright spec that
hits the operator's actual port (`http://localhost:3001`) with
a real browser and asserts:
1. `/coupons` page renders at least one of the canonical seeded
   codes (FIRST10/ACCOOL20/SAVER15/ATUL500), AND
2. No CORS-related console errors fire, AND
3. No `/api/v1/*` request returns 4xx/5xx (excluding 429), AND
4. The "Couldn't load coupons" error UI does NOT surface.

Plus a parallel test for `/services` that asserts:
1. The header services dropdown renders (proves `/home` and
   `/services` both succeeded), AND
2. No CORS / API failures.

```
[cors-fallback] tests/e2e/cors-3001-verify.spec.ts  ✓ Coupons (CORS allows fallback port) (14.6s)
[cors-fallback] tests/e2e/cors-3001-verify.spec.ts  ✓ Services + Home (1.8s)
2 passed (18.0s)
```

### Backend regression

```
$ ./vendor/bin/pest
Tests:    65 passed (329 assertions)
Duration: 21.69s
```

No backend test regression.

---

## Files changed

| File | Change |
|---|---|
| `backend/config/cors.php` | Added one regex line in `allowed_origins_patterns` covering loopback hosts on Vite fallback port range. |
| `backend/.env.example` | Added a Phase 4.2.5b comment explaining the new pattern; no env-value change. |
| `tests/e2e/cors-3001-verify.spec.ts` | NEW. 2 browser tests pinned to `:3001` proving coupons and services load via real browser. |
| `playwright.config.ts` | Added `cors-fallback` project pointing to `:3001`. |
| `tests/e2e/api-integration.spec.ts` | Hardened against Vite HMR networkidle flakes (replaced `networkidle` with `commit` + explicit waits). |

---

## Operator action

1. Restart the Laravel backend so `php artisan config:clear`
   takes effect for new requests:

   ```
   cd backend
   php artisan config:clear
   php artisan serve --host=127.0.0.1 --port=8000
   ```

2. Reload the browser at `http://localhost:3001/` (or whichever
   port your Vite picked — the new regex covers `:3000`–`:3010`).

3. Verify in DevTools → Network that `/api/v1/coupons`,
   `/api/v1/home`, `/api/v1/services` all return 200 with
   `Access-Control-Allow-Origin: http://localhost:<your-port>`.

4. Coupons page should show seeded codes; header Services
   dropdown should show categories; no "Failed to fetch" toast.

If you want to **eliminate the dual-Vite confusion** for good,
kill the stale `:3000` process before starting `npm run dev`:

```
netstat -ano | grep ":3000"     # find PID
taskkill /PID <pid> /F          # kill it
npm run dev                     # now binds :3000 cleanly
```

---

## Why this wasn't caught earlier

Every Playwright test in the existing suite (smoke, edges,
admin, api-integration) targets `localhost:3000`. The operator's
real-world fallback to `:3001` was outside the test grid. The
new `cors-fallback` project closes that gap.

---

## Why the Phase 4.2.5 fixes were still correct

The error UIs that surfaced ("Couldn't load coupons" /
"Could not load services") were doing exactly the right thing —
the underlying network call genuinely failed at the CORS layer.
Phase 4.2.5 turned a previously-silent breakage into a visible
breakage, which is what made the operator able to identify and
report it. Without those error UIs the symptom would have been
"the page is just empty" with no signal pointing at CORS.

The Phase 4.2.5 silent-fallback removal worked. This commit
fixes the underlying CORS misconfiguration that was being
exposed.
