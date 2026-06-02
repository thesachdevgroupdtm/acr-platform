# PROJECT REFRESH AUDIT — Launch Readiness Delta

**This audit:** 2026-06-01 (~36 h before EOD-Monday 2026-06-02 launch deadline)
**Compares against:** `PROJECT_FULL_AUDIT.md` dated **2026-05-29 13:35 IST**
**Method:** Re-ran the same evidence pass — `migrate:status`, read-only DB counts against `acr_v3`, full `pest` run, `playwright --list`, `git remote/log/status`, filesystem diff. **READ-ONLY — no writes, migrations, commits, or installs.**

> Note: today's wall-clock per the harness is 2026-06-02, but this report is filed under the operator-requested `2026-06-01` label and the "~36 h to Monday" framing supplied in the task.

---

## HEADLINE

Since 2026-05-29 the project moved **decisively forward** and nothing regressed:

- **6 of the previous critical/medium blockers are now closed** (OPS-R1, B2, B4, B6, B5-partial, B7) — all verified still present in code/DB.
- **Tests grew 317 → 337 (+20), 100% passing.** No regressions.
- **Surprise win:** `services.image` went **0/92 → 92/92** — the image half of B3 is effectively done.
- **The launch is now gated by exactly two hard blockers: B8 (deploy, never rehearsed) and B10 (admin password).** Everything else is content polish or already shipped.
- **One process risk:** the B5/B7/service-centers round of work is **uncommitted again** (26 working-tree changes) and **not on the `main` remote** — a `git pull` deploy would ship *stale* code missing B5+B7.

---

## PART A — Closed-Blocker Verification

| Blocker | Was (05-29) | Now (06-01) | Still ✅? | Evidence |
|---|---|---|---|---|
| **OPS-R1 / B1** — GitHub remote | ❌ no remote | ✅ `origin` → `github.com/thesachdevgroupdtm/acr-platform.git` (fetch+push) | ✅ | `git remote -v` |
| **B2** — Commit working tree | ⚠️ 4 days uncommitted | ✅ Phase 2 + content-import + archive committed in themed commits (`3e40c1a`, `da6ad59`, `3bda9f3`, `b2d82b2`, `4be9039`) | ✅ (but see note) | `git log --oneline` |
| **B4** — robots.txt hardened | ⚠️ default Laravel | ✅ `Disallow: /admin`, `Disallow: /api/v1`, `Allow: /`, `Sitemap: https://acr-mechanics.in/sitemap.xml` | ✅ | `backend/public/robots.txt` |
| **B6** — carts:prune + schedule | ❌ none | ✅ `PruneGuestCartsCommand.php` present; scheduled in `Kernel.php` ("B6 — prune stale guest carts daily at 03:00"); Pest tests green | ✅ | `Console/Commands/`, `Console/Kernel.php` |
| **B5 (partial)** — LOCATIONS → backend | ❌ hardcoded | ✅ `LOCATIONS` export **removed** from `businessData.ts`; `ServiceCenters.tsx` now uses `useServiceCenters()` API; migration `2026_05_29_153000_extend_service_centers_for_frontend_parity` Ran | ✅ | `src/data/businessData.ts`, `src/pages/ServiceCenters.tsx` |
| **B5 deferred** — TESTIMONIALS + BUSINESS_INFO | (hardcoded) | ⏸️ still exported from `businessData.ts` (deferred per D-B5-6) | ✅ as-deferred | grep `businessData.ts` |
| **B7** — inclusion hand-corrections | ❌ autogroup state | ✅ `ApplyHandCorrectionsB7.php` command + `ApplyHandCorrectionsB7Test.php`; group split rebalanced + 5 km/miles intervals added (see Part B) | ✅ | DB group counts + `interval_info` |

**B2 caveat (NEW process risk):** B2 was genuinely closed, but a **fresh** working-tree delta has since accumulated — the B5 LOCATIONS migration, the B7 corrections command, the service-centers frontend-parity changes, and their reports are **all uncommitted** (`git status --short` = 26 entries). The `main` remote does **not** yet contain B5 or B7. **A deploy pulling from `origin/main` today would ship without LOCATIONS-from-API and without the inclusion corrections.**

---

## PART B — Reality-Check Counts (current vs previous)

| Metric | 2026-05-29 | 2026-06-01 | Δ | Note |
|---|---|---|---|---|
| Backend Pest tests | 317 passed | **337 passed** (1487 assertions, 132.9 s) | **+20** | all green, no regressions |
| Frontend Playwright | 137 in 30 files | **137 in 30 files** | 0 | unchanged |
| Migrations Ran / Pending | 48 / 0 | **50 / 0** | +2 ran | 1 new dated migration (service-centers parity) |
| `services` | 92 | 92 | 0 | — |
| **`services.image` populated** | **0/92** | **92/92** | **+92** 🎉 | all `entity-images/services/<slug>.webp` |
| `services.description` populated | 0/92 | **0/92** | 0 | ⚠️ content debt remains |
| `services.interval_info` populated | 5 | **10** | **+5** | B7 added brake/tyre/wheel intervals |
| `service_inclusions` total | 543 | 543 | 0 | corrections moved, didn't add |
| inclusions group split (E/P/A) | 462 / 23 / 58 | **470 / 17 / 56** | +8 / −6 / −2 | B7: 6 Perf + 2 Add → 8 Essential |
| `service_categories` / icon_image | 13 / 13 | 13 / 13 | 0 | — |
| `service_prices` | 52,521 | 52,521 | 0 | — |
| `service_centers` | 4 | 4 | 0 | rows same; schema extended for parity |
| `seo_pages` | 17 | **17** | 0 | ⚠️ still 8.5% of 200 target |
| `orders` | 9 | 9 | 0 | test data |
| `coupons` (active) | 3 (FIRST10/ACCOOL20/ATUL500) | **3 (same)** | 0 | ⚠️ SAVER15 still absent |
| `leads` | 10 | 10 | 0 | — |
| `carts` / `cart_items` | 1487 / 159 | **1487 / 159** | 0 | prune scheduled but not yet fired |
| Custom artisan commands | 7 | **9** | +2 | +`carts:prune`, +`corrections:apply-b7` |
| Filament Resources | 14 | 14 | 0 | ServiceCenterResource modified, not added |
| Git commits on `main` | working-tree only | **53 committed + pushed** | — | now has a real history + remote |
| Uncommitted files | ~working tree | **26** | — | new B5/B7/SC round (see Part A caveat) |

---

## PART C — Remaining Pre-Launch Blockers (updated)

| # | Blocker | Status | Size | Notes since last audit |
|---|---|---|---|---|
| **B3** | Service descriptions + images | ⚠️ **half-closed** | M | **Images DONE (92/92 webp).** Descriptions still **0/92** — pure copywriting. SEO pages still 17/200. Launchable with fallback copy; brand risk if catalog ships with zero prose. |
| **B8** | Hostinger deploy (P5-R1..R6, R9) | ❌ **unchanged** | L | **No `.github/workflows/`, no deploy scripts appeared.** Never rehearsed. This is the actual launch + the single biggest schedule risk. |
| **B9** | Typography PEND-2 / PEND-4 verify | ❌ unverified | M | Not re-checked this pass (out of 30-min budget). Cosmetic; not functionally broken. |
| **B10** | Admin password rotation (OPS-R2) | ❌ unverifiable | S | Cannot confirm read-only. Must be done on production post-deploy. |
| **B2′** | Commit + push the B5/B7/SC working tree | ⚠️ **NEW** | S (15 min) | Re-accumulated since B2 closed. Must land on `origin/main` *before* B8 or deploy ships stale code. |

**Dropped from the blocker list since 05-29:** B1 (✅ remote), B4 (✅ robots), B5-LOCATIONS (✅ API), B6 (✅ prune), B7 (✅ corrections). B3-images (✅ done).

---

## PART D — Anything New / Surprising

1. **`services.image` 0 → 92/92.** Every service now points at `entity-images/services/<slug>.webp`. Not in the closed-blocker list handed to this audit — an undocumented win that closes the image half of B3.
2. **New migration** `2026_05_29_153000_extend_service_centers_for_frontend_parity` (Ran) — adds frontend-parity columns to `service_centers` for the B5 LOCATIONS migration.
3. **New commands (2):** `corrections:apply-b7` (`ApplyHandCorrectionsB7.php`) and `carts:prune` (`PruneGuestCartsCommand.php`).
4. **New tests (+20 Pest):** `ServiceCentersExtendedTest.php` (B5), `ApplyHandCorrectionsB7Test.php` (B7), plus the B6 prune suite. New dir `tests/Feature/Public/`.
5. **ServiceCenterResource + API + Model + ServiceCenterResource (HTTP)** all modified for frontend parity; `ServiceCentersController` confirmed `Cache::remember`-backed (cached + live).
6. **New report files:** `B4_B6_REPORT.md`, `B5_LOCATIONS_REPORT.md`, `B7_REPORT.md` (dated 05-29 → 06-01).
7. **New non-code artifacts (uncommitted):** `ACR_Project_Tracker.xlsx`, `ACR_Project_Tracker_Datewise.xlsx`, `report.zip`, `report/` dir.
8. **B7 = 13 corrections decomposed:** 8 group moves (6 Performance→Essential fluid top-ups + 2 Additional→Essential exterior-inspection) **+** 5 km-interval values (front-brake-pad, rear-brake-shoes, tyre-rotation, wheel-balancing, complete-wheel-care). Matches SP-PEND-1/2/3 closure.

---

## PART E — Contradictions / Regressions

- **No test regressions.** 337/337 pass (was 317/317). The +20 are all green.
- **No DB counts moved unexpectedly.** Every Δ is explained by a closed blocker (image upload, B7 intervals/groups).
- **No files disappeared** that shouldn't have; `LOCATIONS` removal from `businessData.ts` is intentional (B5).
- **Carry-over drift (unchanged, not a regression):** `SAVER15` coupon still missing (DB has ATUL500); `seo_pages` still 17/200; `services.description` still 0/92.
- **Live contradiction to watch:** committed `main`/remote ≠ working tree. B5 + B7 exist **only** in the uncommitted tree. Deploying from git without committing first = silent feature loss.

---

## PART F — Realistic Time-to-Launch (refreshed)

Date 2026-06-01, deadline **EOD 2026-06-02 (Monday)** → **~36 h wall-clock, realistically ~8–10 focused working hours.**

**Lower bound — engineering only, content deferred: ~6–9 h**
- Commit + push B5/B7/SC tree (B2′) — 15 min
- B8 Hostinger deploy (first-time: env + DB import + build → `/app`, backend → `/backend` + cron + SSL + smoke) — **6–8 h, the long pole**
- B10 admin password rotation post-deploy — 5 min
- (B4/B6 already shipped; carts:prune fires on first scheduled run)

**Realistic — with content authoring: +1 day, runs in parallel**
- B3 descriptions: 0/92 → author copy. Images already done, so this is text-only (~1 writer-day). **Can be backfilled post-launch** via Filament without redeploy since it's pure DB content.

**Risk callouts (could blow the deadline):**
1. 🔴 **B8 has never been rehearsed.** First-time Hostinger deploys routinely lose 2–4 h to PHP ext-intl, file perms, `.env`, storage symlink, and CORS-origin surprises. With only ~36 h, a failed first attempt eats the buffer. **Start the deploy dry-run today, not Monday.**
2. 🔴 **Commit-before-deploy.** If B8 pulls `origin/main` without B2′ landing first, the live site ships **without** LOCATIONS-from-API and **without** the inclusion corrections — a silent regression discovered only in production smoke.
3. 🟡 **No CI / no rollback automation** (`.github/workflows/` still absent). Any hotfix after Monday is a manual FTP/SSH cycle. Acceptable for launch, but plan for it.
4. 🟡 **B10 is unverifiable from here** — easy to forget. Put it on the post-deploy checklist explicitly; the leaked default admin credential is a real exposure once the panel is public.
5. 🟢 **Content debt (descriptions, 183 SEO pages) is NOT a launch blocker** — fallbacks render, and content backfills live without redeploy. Don't let it hold the gate.

**Bottom line for the operator:** the engineering surface is launch-ready and de-risked since 05-29. **The next move should be B8 deploy prep (with B2′ commit-and-push as its mandatory first step), not B3 content** — B3 can stream in after the site is live. Recommend: commit/push now → start the Hostinger deploy dry-run today → rotate admin password on cutover → backfill descriptions next week.

---

*End of refresh audit. No writes, migrations, commits, or installs were performed. The only file created is this report.*
