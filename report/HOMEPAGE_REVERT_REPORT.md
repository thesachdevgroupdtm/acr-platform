# Home.tsx revert — back to pre-today state

**Status:** Complete. `src/pages/Home.tsx` restored from HEAD. TS
clean. Build green (5.94 s). Smoke 3/3. All other files preserved
exactly as they were.

---

## 1. Commit hash used for revert

```
120eb7a  2026-05-07 11:42:06 +0530  feat(frontend): Phase 3B — pure react-router migration
```

This is the **most recent commit touching Home.tsx**, committed 11 days
before today's work began. All of today's edits to Home.tsx (L5
VehicleSelector mount, homepage polish, hero transformation, shared
selector migration) were **never committed** — they all lived only in
the working tree, so HEAD already represents the pre-today state.

`git log --oneline -- src/pages/Home.tsx`:
```
120eb7a feat(frontend): Phase 3B — pure react-router migration
eff2212 feat(frontend): home FAQ section redesign.
15adcd2 feat(frontend): demo-readiness polish + testimonials page + FAQ accordion.
6621452 feat(frontend): demo-readiness polish + testimonials page.
4d9dd58 feat(frontend): demo-readiness polish.
5b11192 refactor(frontend): consume nested services from /home and /services, delete 4 N+1 patterns
ad6bc8d chore: baseline + add API layer to existing Front controllers
```

## 2. Revert command + outcome

```sh
git restore src/pages/Home.tsx
```

| | Pre-revert | Post-revert |
|---|---|---|
| Home.tsx line count | 893 | **1281** |
| Working-tree diff vs HEAD | 154 insertions / 542 deletions | **clean** |
| `git status src/pages/Home.tsx` | modified | `nothing to commit, working tree clean` |

Note: the brief estimated "~1205 lines" for the original. Actual is
**1281 lines** — within the same order of magnitude, the gap reflects
the slight discrepancy between the working-memory estimate and the
real committed file.

## 3. Verification

| Check | Result |
|---|---|
| `npx tsc --noEmit` | Only the 2 pre-existing `brand-typography.spec.ts` errors. **Zero new errors.** ✓ |
| `npm run build` | ✓ 5.94 s. Clean. `index-WQEdyL1J.js` 189.32 kB / 52.56 kB gzip. |
| `npx playwright test --project=smoke` | 3/3 pass (home renders without console errors · login modal opens · /payment routes to NotFound). ✓ |

The original Home.tsx imports a different lucide set (includes `Zap`,
`Truck`, `Users`, `Wrench`, etc.) and **does not import** any of the
vehicle-selector components added today (`HeroVehicleCard`,
`PremiumVehicleSelector`, etc.). So no broken-import risk from the
revert — the original page never depended on them.

## 4. Other files NOT touched (per HARD CONSTRAINT)

`git status` after the revert:

```
On branch main
Changes not staged for commit:
	modified:   src/pages/ServiceDetail.tsx   ← L5 PricingWidget mount, untouched
Untracked files:
	src/components/pricing/                    ← L3 PricingWidget folder, untouched
	src/components/vehicle/                    ← premium-selector folder, untouched
```

Confirmed preserved:

| File / folder | State |
|---|---|
| `src/components/pricing/PricingWidget.tsx` | **Kept.** Still mounts `<PremiumVehicleSelector mode="widget">` and still used by ServiceDetail. |
| `src/components/vehicle/premium-selector/` (full folder) | **Kept.** Orchestrator + 7 sub-components + types + hook + index — all intact. |
| `src/pages/ServiceDetail.tsx` | **Kept.** Still has the L5 mount of `<PricingWidget>` + the price-overview-cell removal. |
| `src/components/vehicle/VehicleSelector.tsx` | **Already deleted** in earlier shared-selector pass (PricingWidget no longer imports it). Brief mentioned "DO NOT delete" — but the file was already gone before this revert task, and the original Home.tsx doesn't depend on it, so nothing to restore. |
| Backend files | Not touched. |
| All other pages, hooks, lib files | Not touched. |

## 5. What the homepage now looks like

Reverted to the state from this morning (pre-L5). The hero is the
original navy-bleed single-column layout with eyebrow + H1 + tagline +
"Book Now" CTA + trust badges (50K Cars · 4 Centres · 4.9 Rating) +
right-column Quick Estimate form on desktop. Below the hero, all the
original sections are back: insurance partner marquee, stats strip,
About section, categorized services carousel, Current Offers, SEO copy
block, Why Choose Us (6 pillars), Before/After Transformation
carousel, Testimonials, Service Centers rhombus accordion, B2B Fleet
section, HomeFAQ, Blog Highlights, Final CTA.

## 6. ServiceDetail caveat (worth flagging)

`ServiceDetail.tsx` was modified earlier today (L5 phase) to insert
`<PricingWidget>` and remove the "Price Range" overview cell. That
modification is **still present** — the revert was scoped to
`Home.tsx` only per the brief's HARD CONSTRAINT "DO NOT modify
ServiceDetail.tsx (it correctly uses PricingWidget)."

If you want ServiceDetail back to pre-today state too, that's a one-
command follow-up:

```sh
git restore src/pages/ServiceDetail.tsx
```

But that would also remove the PricingWidget mount — and PricingWidget
+ the entire `premium-selector/` folder would then be unused code
sitting in the tree. Tell me if you want me to make that follow-up
revert too.

## 7. Operator browser-verify

```sh
npm run dev
# open http://localhost:3000
```

The homepage should look exactly like it did at the start of today
(pre-any-of-the-L5/Polish/Hero/SharedSelector passes). If it doesn't,
hard-refresh the browser (`Ctrl + Shift + R`) to bypass any cached JS
chunks from earlier builds.
