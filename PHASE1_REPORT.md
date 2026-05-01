# Phase 1 Frontend Hygiene — Report

Frontend-only pass per the brief in conversation. Backend untouched.

## Files changed (line-count delta across the 4 commits)

```
 src/components/AuthModal.tsx        |  58 ++++-          (+58 / -0)     gating
 src/components/BookingSidebar.tsx   | 335 +++++++++++++++++++-------
 src/components/EstimateProcess.tsx  |  51 +++-
 src/components/Header.tsx           | 239 +++++++++++++------         lazy-fetch + auth gate
 src/config/features.ts              |  36 +++              NEW         3 feature flags
 src/data/businessData.ts            | 256 --------------------         dead hooks removed
 src/hooks/useAuth.ts                | 465 +++++++++++++++++++++++++++++++++++++   (counted as new — first time committed in hooks/)
 src/hooks/useCart.ts                | 218 +++++++++++++++++           (counted as new — first time committed in hooks/)
 src/pages/Offers.tsx                | 100 ++++----        OFFERS reconciled
 src/pages/ServiceCategory.tsx       | 322 +++++++++++++++++--------
 ─────────────────────────────────────────────────────────────────────
 10 files changed, 1508 insertions(+), 572 deletions(-)
```

> Note on the +465 / +218 deltas for `useAuth.ts` / `useCart.ts`: these
> files were physically moved from `src/data/` to `src/hooks/` in an
> earlier session turn. The "move" was unstaged when Phase 1 began, so
> from git's perspective the Phase 1.3 commit *creates* them at their new
> path. The actual source code change in this phase is roughly +30 lines
> per file (the FEATURES flag imports + early-return guards).

## Commits (newest → oldest)

| Commit | Item | Message |
|---|---|---|
| `cdad2c0` | P1.4 | `fix(frontend): replace CAR_DATA fallbacks with React Query loading/error states in vehicle picker` |
| `8239905` | P1.3 | `feat(frontend): gate unimplemented auth/cart/checkout calls behind FEATURES flags` |
| `7e7eefc` | P1.2 | `chore(frontend): consolidate OFFERS to single source in businessData.ts` |
| `fe65639` | P1.1 | `chore(frontend): remove dead useApi* hook layer in businessData.ts` |

Full hashes:
```
cdad2c048bc88af66357509288a8375bdaeb5a56  fix(frontend): replace CAR_DATA fallbacks with React Query loading/error states in vehicle picker
823990530d462342a446fd1ae269a3f1fd300bf5  feat(frontend): gate unimplemented auth/cart/checkout calls behind FEATURES flags
7e7eefce18e842de5440708753ef5589a016456f  chore(frontend): consolidate OFFERS to single source in businessData.ts
fe65639b8953fda076ac43e2a3d1cd03a3721a77  chore(frontend): remove dead useApi* hook layer in businessData.ts
```

## Verification (step 5)

| Check | Result |
|---|---|
| `npm run build` | **PASS**. `vite build` completed in 26.52s, emitted `dist/index.html` + hashed `assets/index-FnYKaeo3.css` (104.57 kB) + `assets/index-CyGdYiR6.js` (716.32 kB / 190.20 kB gzip). Exit 0. |
| `npx tsc --noEmit` | **PASS**. Exit 0 (re-run after each commit). |
| Project-wide grep for `CAR_DATA` | **1 occurrence** — `src/data/businessData.ts:93` (the export). Zero consumers in components/pages. |
| Project-wide grep for `useApiHome\|useApiServiceCategories\|useResource` | **0 occurrences**. Dead hook layer fully removed. |
| Vite dev server boot | **PASS**. `npm run dev` came up on `http://127.0.0.1:3000/`, `GET /` → HTTP 200. |
| Laravel API up | **PASS**. `GET http://127.0.0.1:8000/api/v1/home` → HTTP 200. |
| Network tab — zero requests to `/auth/*`, `/user/*`, `/cart/*`, `/checkout/*` | **NOT VERIFIED IN-BROWSER** — see deviation 1 below. Indirect evidence: every call site for these endpoints is now wrapped in `if (!FEATURES.{auth,cartSync,offlineCheckout})` early-returns; both flags default to `false`. Also: the Header buttons that lead to the auth flow are hidden when `FEATURES.auth=false`. |
| Vehicle picker shows skeleton → API resolves → renders, no flicker | **NOT VERIFIED IN-BROWSER** — same reason. The relevant render branches in `BookingSidebar.tsx`, `ServiceCategory.tsx`, and `EstimateProcess.tsx` now read directly from `useBrands()` / `useModels(brandId)` React Query hooks; loading state renders skeleton tiles, error renders an inline retry, empty renders a "No models available" message, success renders the grid. There is no longer any code path that returns static data. |

## Deviations from the prompt

**1. Browser smoke-test not run.** The brief said *"Run the dev server, open the home page and the booking sidebar in a browser. Open DevTools Network tab. Confirm: zero requests..."* — I started both the Vite dev server (port 3000) and the Laravel API (port 8000) and confirmed they boot, but I cannot drive a browser from this session to inspect the Network tab. The verification table marks those two checks as **not verified in-browser** with the indirect static evidence I do have. Please run the smoke test yourself before treating Phase 1 as fully closed; the dev server and API are both still running.

**2. ESBuild warning surfaces during `npm run build`.** Build exits 0 but emits:

```
[plugin vite:esbuild] src/components/EstimateProcess.tsx:
  This case clause will never be evaluated because it duplicates an earlier case clause
  936 | case 5:
  937 | case 3:
  938 |   return renderSuccess();
```

This is **pre-existing** (not introduced by Phase 1 — Phase 1 only edited the brand/model select fields around line 620–700). Flagging it here so it gets eyes; not fixing it because the brief said pure-frontend hygiene scoped to the four enumerated work items.

**3. ServiceCategory.tsx had `CAR_DATA` consumers outside the picker.** The brief listed the four picker sites; ServiceCategory also used `CAR_DATA` in two SEO/marketing copy spots (`brandList.slice(0,5)` for a "we support these brands" list and `CAR_DATA[brandList[0]][0]` for a "whether you drive a Maruti Swift, a Hyundai Creta..." paragraph). The brief required removing the `CAR_DATA` import from this file, so I rewired the marketing copy to draw from the same `useBrands()` query result. The Maruti-Swift template was simplified to a brand-only template since fetching first-model-of-first-brand for marketing copy alone is wasteful. UI prose changes are minor and reversible; flagging in case content review is desired.

**4. `Offers.tsx` UI fields without a data source were dropped.** The local `OFFERS` array carried `urgencyText`, `rating`, `customers`, `image` fields that are not part of the canonical `OfferCoupon` shape. The brief instructed *"reconcile to the businessData.ts definition (it is the canonical source)"* — those fields had no equivalent on the canonical type, so the card UI now shows the badge / discount / coupon code / minOrder derived from canonical data, dropping the unsourced marketing extras. Visual layout density is similar but the imagery is now a primary-color gradient instead of a Unsplash photograph (no per-coupon image field exists).

**5. `STATIC_FUELS` removed from BookingSidebar.** Not in the brief's enumerated four sites, but it was a sibling static fallback (4 hardcoded fuels with icons) gated behind the same `apiFuels.length > 0 ? apiFuels : STATIC_FUELS` pattern. Removing it was necessary to honor the "no static fallback" contract the picker now claims. Fuel options come exclusively from `/api/v1/vehicle/fuels?brand_id=&model_id=` with proper loading/error/empty states; the local `FUEL_TYPES` icon-decoration const at `ServiceCategory.tsx:48` is unrelated (it's used purely for the `Droplet/Fuel/Wind/BatteryCharging` icon mapping in the modal — kept).

## Stopping point

Per the brief: stopping after this report. Phase 2 is not started.

---

## Phase 1.1 (post-review patch)

**Commit:** `6e3e9c106615bff2167444e4f57f74065745f410`
**Message:** `fix(frontend): restore Offers card richness lost in Phase 1 consolidation`

Addresses deviation #4 above. The 7e7eefc consolidation dropped four
marketing fields (`urgencyText`, `rating`, `customers`, `image`) that the
pre-merge local `OFFERS` array carried but the canonical `OfferCoupon`
type didn't have.

**Changes:**
- `src/data/businessData.ts` — extended `OfferCoupon` with four optional
  fields. Seeded values from `git show 7e7eefc^:src/pages/Offers.tsx`
  onto the two coupons with a clean category match: `ACCOOL20` ←
  AC DEEP CLEANING, `SHINE250` ← PREMIUM CERAMIC COATING. The original
  local "MASTER FULL SERVICE" entry has no canonical equivalent (no
  coupon targets `regular-car-service`); per the brief's "don't
  fabricate" rule, no fields were guessed for `FIRST10`, `SAVER15`, or
  `POWER300`.
- `src/pages/Offers.tsx` — card photo now shows when `offer.image` is
  set, falls back to the gradient otherwise. Urgency / rating /
  customers chips are conditionally rendered (`{offer.x && <Chip/>}`)
  so absent fields don't surface as empty pills. Customer counts
  format via `toLocaleString("en-IN")` (12500 → 12,500+).

**Verification:**
- `npx tsc --noEmit` → exit 0
- `npm run build` → exit 0 (16.68s)
- Browser visual confirmation of `/offers` page (photo + chips on the
  two enriched coupons, gradient + badge-only on the other three) is
  **not run from this session** — same browser-driving limitation as
  deviation #1; please confirm visually when you're at the screen.
