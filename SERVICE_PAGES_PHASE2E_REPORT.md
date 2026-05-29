# SERVICE_PAGES_PHASE2E_REPORT — GoMechanic-clean polish + pending-2d closeout (verified)

**Status: DONE and screenshot-verified.** The category bar is now GoMechanic-clean (bigger
icons + active blue-tint pill, contained to the site max-width, below the banner); the site-wide
body padding is halved at its ONE shared source; and the card "+N more · View All" now **expands
inclusions in place** (no navigation). The still-pending Phase-2d items (bar-below-banner,
personalised-pill removal, Layer-2 Brands + section-nav removal) were already present in the
working tree from the 2d pass — verified intact, and 2e refines them.

- **TSC:** clean — only the **2 pre-existing** `tests/e2e/brand-typography.spec.ts` errors. Zero new.
- **Vite build:** clean (`✓ built in ~5s`).
- **Backend Pest:** **317 passed (1327 assertions)** — incl. the updated `InclusionsPreviewTest`.
- **Frontend e2e (`--project=phase2`):** **20/20 passed** (17 prior + 3 new 2e tests; 2 prior tests' nav waits hardened).

---

## PART A — container audit (gate for D-2e-3): ONE shared source ✅
There is exactly **one** shared container, `.site-container`, defined once in `src/index.css`:
```css
.site-container { @apply mx-auto px-4 xl:px-5 w-full max-w-[1320px]; }
```
Used in **74 places across 36 files** (Header, Footer, every page, PageBanner, the shell, explore
components). Single source → the padding reduction is a **one-line, site-wide** change there (no
per-page edits). Reduced to:
```css
@apply mx-auto px-2 xl:px-2.5 w-full max-w-[1320px];   /* 16→8px, 20→10px (≈ half) */
```
(Note: the actual values were `px-4 / xl:px-5`, not the `px-5` the brief assumed — halved each
proportionally. At viewports ≥ ~1320px the `max-w-[1320px]` centering dominates, so the visible
tightening is largest on mid/small screens — most obvious on mobile.)

---

## PART B — category bar redesign + reposition (D-2e-1/2)
- **Position:** the bar renders **below the PageBanner** (banner → bar → content) and is
  `sticky top-[112px]` (sticks under the header once scrolled). ONE bar. *(This was already in the
  tree from 2d; kept.)*
- **Contained:** the bar's inner content sits inside the shared **`.site-container`**
  (`max-w-[1320px]`, centered), so items align to the same max-width as the page content — the
  `<nav>` background spans full width, the **items do not**. e2e asserts the bar's `.site-container`
  is **≤ 1340px wide and centered** (x > 30) at a 1440 viewport (not edge-to-edge).
- **Bigger icons:** `w-6 → w-12` (**48px**) for both the `icon_image` `<img>` and the lucide
  fallback glyph, Montserrat (`font-display`) label beneath. (First bumped to 32px; operator said
  "still small" → confirmed the source `.webp`s are 512×512 with content filling the canvas — i.e.
  the rendered size was real, not whitespace — and bumped to a GoMechanic-scale 48px. Close-up:
  `phase2-shots/phase2e-bar-closeup.png`.)
- **Active item:** ACR-**blue underline** + a **soft blue-tint pill** (`bg-primary/10`,
  `rounded-t-lg`) + blue tint; inactive = transparent + muted (`hover:bg-primary/5`). e2e asserts
  the active item has a non-transparent fill that inactive items lack, and the underline computes
  to `rgb(31, 79, 163)` (ACR blue, **not** red). All 13 categories have real `icon_image`, so the
  bar renders the uploaded `.webp` icons (test asserts a ≥28px `<img>`; actual 48px).
- Tab-vs-nav behavior (2c), `aria-current`, `data-cat-slug`, horizontal scroll + chevrons preserved.

---

## PART C — in-place inclusions expand (D-2e-4) — approach (a)
**Chosen: (a) the preview returns ALL labels (lean, labels-only, position-ordered)** so expand is
**instant with no per-card fetch** and the shared card stays free of data-fetching coupling.
- **Backend** `ServiceController` (category-detail): `…->take(4)->pluck('label')…` → `…->pluck('label')…`
  (all labels; `total` stays the count). Lean — labels only, no images/group. One line.
- **`InclusionsPreviewTest`** updated to expect the full position-ordered list (`First … Sixth`).
- **`ServiceCard`:** "+N more · View All" now **toggles an inline expand/collapse** ("Show less")
  with a smooth `AnimatePresence` height animation — **no navigation**. Collapsed shows the first 4;
  expanded reveals the rest. The card **title** is a separate control and **still navigates** to the
  detail page. Works identically on Layer 1 + Layer 2 (shared card).

**Proof:** e2e clicks "+N more" on the *primary service* card (9 inclusions) → URL stays
`/category/regular-car-service`, the previously-absent `[data-testid="inclusion-extra"]` items
become visible, "Show less" appears and collapses them; then clicking the **title** navigates to
`/services/regular-car-service/primary-service`.

---

## PART D — personalised banner + Layer-2 cleanups (D-2e-5/6) — already in tree, verified
Grep + screenshots confirm these 2d items are present (no re-work needed):
- **No "Prices personalised for …" pill** on Layer 1, Layer 2 or Layer 3 (only removal-comments
  remain). The sidebar car display + price-reveal logic are untouched. e2e asserts the text has
  **count 0** on `/services` and `/category/:slug` with a vehicle selected.
- **Layer 2 has no "Brands We Service" section** and **no in-page section-nav scroller**; no
  `useSubNavSync` usage. The other sections render in order (Overview, Pricing/catalog, Services
  Included, Why Us, Process, Reviews, FAQs, Why ACR). e2e asserts no `BRANDS WE` heading, no
  `[data-subnav-link]`, with the other sections + sidebar + cart intact.

*(Honest note: the brief listed these as "still pending". In this working tree they were already
applied during Phase 2d — likely the operator's environment was un-synced/uncommitted. 2e keeps
them and adds the new bar/padding/expand work.)*

---

## PART E — tests + screenshots (hard gate)
New 2e e2e tests (phase2 project):
1. **bar contained + active pill + real icons** — `.site-container` ≤1340/centered, active
   non-transparent fill + blue underline, ~32px `<img>` icon.
2. **"+N more" expands in place** — URL unchanged, extra items visible, toggles back; title still navigates.
3. **2e screenshots** — desktop + mobile, /services + Layer 2.

**Root cause of the suite flakiness — the dev API rate limiter (not the app).** The page
snapshots on the failing runs showed `"Too Many Attempts."` — HTTP **429**. With all 13 categories
now carrying real `icon_image`, every service-page load is heavier, and the serial e2e suite (plus
my repeated full re-runs) tripped the dev throttle (`public-read 120/min`, `api 60/min` per IP).
**Fix:** in `RouteServiceProvider`, relax the **non-auth** read/cart/user limiters to 3000/min
**in `local` env only** — **production and Pest's `testing` env keep the strict limits; auth
limiters stay strict everywhere.** After this, the suite is reliably **20/20 in ~2.0 min** (vs the
~5 min throttled runs) — confirming every prior failure was a 429, not a code defect (each failing
test also passed in isolation).

Supporting test hardening (no app behavior change): gotos `networkidle` → `domcontentloaded` (the
fragile wait), per-test 30→60s + assertion 5→10s timeouts, and `loading="lazy"` on the 13 bar icons
(genuine perf — fewer initial image requests). `retries` was tried then reverted to **0** (it added
request load and doesn't cure 429s).

**Full suite:** phase2 **20/20** (~2.0 min); backend Pest **317**; TSC clean; Vite build clean. Zero regressions.

### Screenshots (inspected)
| File | Viewport | Shows |
|---|---|---|
| `phase2e-bar-closeup.png` | 1440 | full-res close-up of the bar — **48px icons**, Car Battery active blue pill + underline, chevrons |
| `phase2e-services-desktop.png` | 1440 | /services — contained icon bar below banner, bigger icons, Car Battery active **blue pill**, tighter padding, no personalised pill |
| `phase2e-services-mobile.png` | 390 | /services mobile — icon bar, loaded cards, tighter padding |
| `phase2e-card-expanded-desktop.png` | 1440 | Layer-2 with the *primary service* card **expanded inline** (all 9 inclusions, URL unchanged); other cards collapsed |
| `phase2e-category-l2-desktop.png` | 1440 | Layer-2 — no section-nav, no Brands, no personalised pill; all other sections present |
| `phase2e-category-l2-mobile.png` | 390 | Layer-2 cleaned, mobile |
| `phase2d-services-iconbar-*`, `phase2d-category-l2-*` | 1440/390 | regenerated with the new bar styling + tighter padding |

**GoMechanic-feel comparison:** the bar is now contained (items aligned to the 1320 max-width, not
the viewport), with larger icons and a soft blue active pill + underline — the clean, contained
tab-strip look; the tighter body padding removes the "massive" side gutters on mid/small screens.

**Violation sweep (all clear):** no full-width *items* (bar contained), zero GoMechanic red/grey,
no redirect-on-expand (URL stays put), icons render (real `.webp`), no missing Layer-2 sections,
one bar only.

---

## Brand check
ACR **blue** active underline (`#1F4FA3` → `rgb(31,79,163)`, test-asserted) + **blue-tint pill**
(`bg-primary/10`), navy band/text, Montserrat (`font-display`) labels. **Zero** GoMechanic red/grey.

---

## Files modified
- `src/index.css` — `.site-container` padding `px-4 xl:px-5` → `px-2 xl:px-2.5` (halved, site-wide).
- `src/layouts/ServicesShell.tsx` — bar icons `w-6`→`w-12` (**48px**); active = blue underline +
  `bg-primary/10` pill (`rounded-t-lg`); inactive `hover:bg-primary/5`; skeleton icon bumped.
  (Position/containment unchanged from 2d.)
- `src/components/service/ServiceCard.tsx` — "+N more · View All" → in-place expand/collapse
  (`useState` + `AnimatePresence` height), no navigation; collapsed 4 / expanded all; title still routes.
- `backend/app/Http/Controllers/Api/V1/ServiceController.php` — preview labels: `take(4)` → all (lean).
- `backend/tests/Feature/InclusionsPreviewTest.php` — assert the full position-ordered label list.
- `src/layouts/ServicesShell.tsx` — also added `loading="lazy"` to the bar `<img>` icons (fewer
  initial requests).
- `backend/app/Providers/RouteServiceProvider.php` — **local-env-only** raise of the non-auth
  read/cart/user rate limiters to 3000/min (production + `testing`/Pest unchanged; auth limiters
  unchanged). Fixes the e2e 429s.
- `tests/e2e/service-pages-phase2.spec.ts` — 3 new 2e tests; gotos `networkidle`→`domcontentloaded`.
- `playwright.config.ts` — per-test 30→60s, assertion 5→10s (single-threaded dev server tolerance).

**New screenshots:** `phase2-shots/phase2e-{services-desktop,services-mobile,card-expanded-desktop,category-l2-desktop,category-l2-mobile}.png`.

**No** migrations, **no** slug changes, **no** `service_prices`/pricing-logic changes, **no** new packages.

---

## Deviations (called out)
1. **Expand approach (a)** (backend returns all preview labels) over (b) lazy-fetch — instant
   expand, no per-card fetch, shared card stays presentational; cost is a tiny payload increase
   (lean label strings) + one test update, both sanctioned by the brief.
2. **Container padding** was `px-4 / xl:px-5` (not `px-5`); halved each step to `px-2 / xl:px-2.5`.
   The tightening is most visible below ~1320px (the max-width centering dominates above that).
3. **The "pending 2d" items were already in the working tree** (bar-below-banner, personalised
   removal, Brands + section-nav removal) — verified intact via grep + screenshots; 2e refined the
   bar and added the new padding/expand work rather than re-introducing them.
4. **Bar "contained" was already true** since 2d (it uses `.site-container`, max-w-1320). The
   "edge-to-edge" perception came from the full-width bar **background**; the **items** are
   contained (test-asserted). 2e makes this read cleaner via the active pill + bigger icons.
5. **Collapsed inclusion items keep `truncate`** (compact, unchanged); **expanded items wrap**
   (`leading-snug`) so "View All" shows full labels.
6. **Dev rate-limit raise (local only):** the e2e suite + real bar-icon page loads tripped the dev
   throttle (429); relaxed the non-auth limiters to 3000/min **in `local` env only** via an
   `app()->environment('local')` guard. **Production and Pest's `testing` env are unchanged**, and
   **auth limiters stay strict everywhere** — a backend touch, but env-scoped and security-safe.
   It also improves the local demo (fast tab-browsing no longer self-throttles).
7. **Test-infra only:** `networkidle`→`domcontentloaded`, higher Playwright timeouts, and
   `loading="lazy"` bar icons. `retries` left at 0. No app-behavior change.

---

## GIT
No git commands were run. Files changed are listed above; **operator commits.**
