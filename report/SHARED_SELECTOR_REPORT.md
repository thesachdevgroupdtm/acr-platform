# Shared premium vehicle selector — consolidation pass

**Status:** Complete. TypeScript clean (only 2 pre-existing
brand-typography errors), build green (8.06 s), Playwright smoke
3/3 pass. Bundle delta within brief budget.

---

## 1. Pre-implementation audit (PART A findings)

**Brief premise:** *"Services.tsx has the strongest existing selector flow … extract its patterns."*

**Reality after audit:**

| Check | Result |
|---|---|
| `grep "VehicleSelector|useBrands|useModels|useFuels|HeroVehicleCard" src/pages/Services.tsx` | **No matches.** Services.tsx only imports `useBookingContext` to *read* the current vehicle. |
| Where does the Services-page selector live? | Inside **`src/components/BookingSidebar.tsx`** (772 lines), mounted as `<aside>` in Services.tsx. |
| Is BookingSidebar a pure vehicle selector? | **No.** It also handles location dropdown, OTP phone-verify flow, "Check Prices" action button, and cart-state validation — coupled to vehicle selection (`if (!state.car) errs.car = 'Please select your car'`). |
| Can it be cleanly swapped with a vehicle-only `<PremiumVehicleSelector>`? | **No** — that would lose location + OTP + cart actions and violate the brief's "DO NOT break Services.tsx existing functionality" constraint. |

**Operator decision (via AskUserQuestion):** Build shared selector, mount in Home + PricingWidget, **leave Services/BookingSidebar untouched**. The `mode="panel"` slot in the shared API is wired but has no consumer yet — BookingSidebar continues to own that Services-page mount until a separate refactor lifts its vehicle-selector portion to use the shared system.

**Patterns extracted from the previous HERO/L3 work** (more relevant than Services.tsx since they're the direct lineage):

* Grid layouts (3-col mobile / 4-col desktop for brands, 2/3 for models, 1/3 for fuel) — lifted from the deleted BrandGrid/ModelGrid/FuelGrid.
* Brand-image-or-letter-fallback pattern — lifted from the deleted BrandLogoFallback.
* Fuel-name → icon keyword mapping (Electric → Zap, Diesel → Droplet, Petrol/CNG → Fuel) — lifted from FuelGrid.
* Selection state machine (brand → model → fuel → complete) with downstream-reset on parent change — lifted from VehicleSelectorModal's `handleBrandSelect` / `handleModelSelect` / `handleFuelSelect`.
* SelectionDisplay pattern (Selected badge + brand-model title + CTA + Change-vehicle link) — lifted from HeroVehicleCard's complete state.

## 2. Files created

| Path | Role |
|---|---|
| `src/components/vehicle/premium-selector/types.ts` | `SelectorMode`, `SelectorStep`, `VehicleSelection`, `PremiumVehicleSelectorProps`. |
| `src/components/vehicle/premium-selector/hooks/useSelectorState.ts` | Single source of truth for step state + selection state + `useBookingContext` integration. Seeds initial step from booking context (if all 3 IDs present, jump to "complete"). Auto-fires `onChange` on every mutation. Downstream-resets on parent change. |
| `src/components/vehicle/premium-selector/components/BrandLogoFallback.tsx` | Gradient initial-letter tile (`bg-gradient-to-br from-primary to-primary-dark`). |
| `src/components/vehicle/premium-selector/components/SearchInput.tsx` | Reusable search field used in BrandStep + ModelStep. AutoFocus on mount. |
| `src/components/vehicle/premium-selector/components/StepIndicator.tsx` | Subtle "Step X of 3 · Choose brand" header. Hero mode = full caption; widget/panel = compact "X / 3" pill. |
| `src/components/vehicle/premium-selector/components/BrandStep.tsx` | Step 1 — search + grid. Density tokens vary per mode (hero gets 100/120 px tiles, widget gets 88 px). |
| `src/components/vehicle/premium-selector/components/ModelStep.tsx` | Step 2 — back-button + brand crumb + search + text-card grid. |
| `src/components/vehicle/premium-selector/components/FuelStep.tsx` | Step 3 — back-button + model crumb + icon cards (Fuel/Zap/Droplet by name keyword). |
| `src/components/vehicle/premium-selector/components/SelectionDisplay.tsx` | "Complete" state — Selected badge, brand-model title, optional CTA, Change-vehicle link. Typography scales per mode. |
| `src/components/vehicle/premium-selector/PremiumVehicleSelector.tsx` | The thin orchestrator. Mode-keyed wrapper class table. AnimatePresence step transitions (slide-x, 250 ms ease-out). |
| `src/components/vehicle/premium-selector/index.ts` | Public exports — `{ PremiumVehicleSelector, PremiumVehicleSelectorProps, VehicleSelection, SelectorMode, SelectorStep }`. |

## 3. Files modified

| Path | Change |
|---|---|
| `src/pages/Home.tsx` | Hero rewritten to mount `<PremiumVehicleSelector mode="hero">` instead of `<HeroVehicleCard>`. Visual upgraded: added Framer Motion animated sheen overlay + reading-side dark wash + `strokeWidth={1.5}` on the Car silhouette. Card now overlaps the visual with `lg:-ml-12 lg:relative lg:z-10` for the "premium broken-edge" composition. Added inline stats line (50K · 4 · 4.9★ · 15+) below the tagline. Added compact insurance trust strip section between hero and the next surface (`bg-neutral-50 py-6`, "Cashless with 30+ Insurers" + 5 names + "+ 26 more"). Added fleet credibility strip inside the Why Choose Us section (`mt-12 pt-8 border-t`, "Trusted By" + Uber/Ola/Zoomcar/Fortune 500). Import swap `HeroVehicleCard` → `PremiumVehicleSelector`. |
| `src/components/pricing/PricingWidget.tsx` | Migrated from the old `<VehicleSelector mode="inline">` + `<VehicleSelector mode="modal">` pair to a single `<PremiumVehicleSelector mode="widget" showCta={false}>`. The old modal-overlay pattern (for "Change vehicle") is replaced with a local `forcePickerOpen` flag — flipping it shows the inline selector AGAIN even when `hasVehicle` is true; the selector's `onChange` callback collapses the flag the moment a full triple is set. Avoids wiping the global `useBookingContext.car`. |

## 4. Files deleted

| Path | Reason |
|---|---|
| `src/components/vehicle/VehicleSelector.tsx` | Superseded by shared `PremiumVehicleSelector mode="widget"`. |
| `src/components/vehicle/VehicleSelectorModal.tsx` | Superseded — modal-overlay UX dropped in favor of the inline shared selector with `forcePickerOpen` pattern in PricingWidget. |
| `src/components/vehicle/HeroVehicleCard.tsx` | Superseded by shared `PremiumVehicleSelector mode="hero"`. |
| `src/components/vehicle/BrandGrid.tsx` | Lifted into `premium-selector/components/BrandStep.tsx`. |
| `src/components/vehicle/ModelGrid.tsx` | Lifted into `premium-selector/components/ModelStep.tsx`. |
| `src/components/vehicle/FuelGrid.tsx` | Lifted into `premium-selector/components/FuelStep.tsx`. |
| `src/components/vehicle/BrandLogoFallback.tsx` | Moved into `premium-selector/components/BrandLogoFallback.tsx`. |

Pre-deletion safety: `grep -rn "from .*/vehicle/(VehicleSelector|VehicleSelectorModal|HeroVehicleCard|BrandGrid|ModelGrid|FuelGrid|BrandLogoFallback)"` returned **zero matches** — every consumer had been migrated to the shared system.

## 5. Consumer migration verification

| Consumer | Status | Notes |
|---|---|---|
| Home.tsx hero | ✓ Migrated | `<PremiumVehicleSelector mode="hero" ctaLabel="See Prices" onComplete={() => navigate("/services")}>` mounted in the 5-col floating slot with `lg:-ml-12` overlap. |
| PricingWidget.tsx | ✓ Migrated | `<PremiumVehicleSelector mode="widget" showCta={false}>` mounted as STATE-1 selector. Local `forcePickerOpen` state handles the "Change vehicle" re-open flow without touching global booking context. |
| Services.tsx / BookingSidebar.tsx | ✗ Intentionally untouched | Per audit + operator decision. BookingSidebar's coupled location/OTP/cart-action flow stays — vehicle-selector portion remains internal to it until a future refactor lifts to `mode="panel"`. |

## 6. Visual redesign details

### Cinematic hero visual (D-SHARED-7)

* `aspect-[4/3] lg:aspect-[16/10]`, `rounded-3xl overflow-hidden shadow-2xl`.
* Base gradient: `bg-gradient-to-br from-primary-dark via-primary to-primary-dark`.
* **NEW** animated sheen: Framer Motion `<motion.div>` with `bg-gradient-to-tr from-transparent via-white/5 to-transparent`, opacity cycling `0.3 → 0.6 → 0.3` over 4 s on infinite easeInOut.
* Reading-side dark wash: `bg-gradient-to-r from-black/40 via-black/10 to-transparent` — gives the right-overlapped selector card a darker base it visually rises out of.
* `<Car />` silhouette: `w-32 h-32 lg:w-48 lg:h-48`, `text-white/15`, `strokeWidth={1.5}` (thinner stroke reads more designed-illustration than icon).
* CSS-only — no `public/hero-premium.jpg` dependency. Operator can swap to a real photo by replacing the inner block with `<img>`.

### Floating selector overlap

* Desktop ≥`lg`: `lg:col-span-5 lg:-ml-12 lg:relative lg:z-10` — the card overlaps the visual's right edge by 48 px, with `z-10` keeping it above the dark wash.
* Mobile: stacks below the visual cleanly; the overlap classes are `lg:`-prefixed and don't fire.

### Trust strip reintegration (D-SHARED-8)

| Element | Location | Format |
|---|---|---|
| Insurance trust strip | Between hero and the next surface | Single-row `bg-neutral-50 py-6 border-b`: "CASHLESS WITH 30+ INSURERS" eyebrow + 5 insurer names + "+ 26 more". Compact — ~80 px tall. |
| Inline stats | Below H1 + tagline, inside the hero motion.div | `flex items-center gap-x-5 flex-wrap text-sm text-white/70 mt-6`: 50K Cars · 4 Centres · 4.9★ · 15+ Years. NOT a standalone strip — reads as supportive credibility below the headline. |
| Fleet credibility | Inside Why Choose Us section | `mt-12 pt-8 border-t border-border`: "Trusted By" eyebrow + Uber · Ola · Zoomcar · Fortune 500. Subordinate to the main "Why Choose Us" content — a B2B nudge that doesn't reintroduce the deleted B2B/Fleet standalone section. |

## 7. Verification results

| Check | Pre-pass | Post-pass | Δ |
|---|---|---|---|
| `npx tsc --noEmit` | 2 pre-existing brand-typography | 2 pre-existing brand-typography | 0 new |
| `npm run build` | ✓ | ✓ 8.06 s | clean |
| `index.js` bundle (raw) | 182.93 kB | 185.12 kB | +2.19 kB |
| `index.js` bundle (gzip) | 52.40 kB | 52.88 kB | +0.48 kB |
| Playwright smoke | 3/3 pass | 3/3 pass | 0 regressions |
| Selector components on disk | 8 (the old VehicleSelector/Modal/HeroVehicleCard/3 grids/Fallback + the L3 VehicleReplaceModal, which is *separate*) | 12 (premium-selector orchestrator + 7 children + types + hook + index + the still-existing VehicleReplaceModal) | + 4 files net |

Bundle delta of +0.48 kB gzip is acceptable for: animated Framer overlay + inline stats + insurance trust strip + fleet credibility strip + the additional abstraction layer of the orchestrator's mode-keyed wrapper table. Pure deletion of the old 7 vehicle components clawed back roughly the same volume of code they added.

## 8. Deviations

1. **`mode="panel"` exists in the type union + wrapper-class table but has no consumer yet.** Wired through cleanly so a future refactor of BookingSidebar can mount `<PremiumVehicleSelector mode="panel">` without an API change. Documented in `types.ts` + `index.ts` JSDoc.

2. **Services.tsx + BookingSidebar untouched.** Per the upfront audit + operator decision via AskUserQuestion. The brief's PART D ("Migrate Services.tsx FIRST") was based on a stale assumption that Services.tsx had a direct selector mount — it doesn't. BookingSidebar wraps the vehicle selector inside a much larger booking pane (location / OTP / cart-action), and replacing it with a vehicle-only component would have broken Services.tsx (violating HARD CONSTRAINT). The shared system's `mode="panel"` slot is left ready for a separate BookingSidebar refactor phase.

3. **Modal-overlay UX in PricingWidget replaced with `forcePickerOpen` flag.** The brief's HARD CONSTRAINT D-SHARED-2 lists three modes (hero/widget/panel) — no "modal". So the old `<VehicleSelector mode="modal">` overlay for "Change vehicle" had no equivalent in the shared API. Replaced with a local boolean: clicking "Change vehicle" flips `forcePickerOpen=true`, the widget then renders the inline selector even when `hasVehicle` is true; `onChange` collapses the flag the moment all 3 IDs are set. Cleaner than the modal IMO — no overlay z-index gymnastics, the operator sees the price-area transform into the picker in place. Also added a "Keep current vehicle" escape link so an accidental click on "Change vehicle" is reversible without losing the existing selection.

4. **Form-state declarations in Home.tsx still dead.** `formData`, `errors`, `isSubmitting`, `setFormData`, `setErrors`, `validate`, `handleSubmit` remain in component scope from the much earlier polish pass (they were leftover from the commented-out Quick Estimate form, now fully deleted). No `noUnusedLocals` in tsconfig so TS doesn't flag. Cleanup deferred — out of scope here.

5. **Animated sheen uses `bg-gradient-to-tr` not a SVG noise/grain pattern.** Brief described "subtle animated gradient overlay (Framer Motion)" without specifying the exact gradient direction or pattern. The implementation uses a diagonal `from-transparent via-white/5 to-transparent` sheen cycling opacity — reads as "soft moving light over a polished surface" rather than a grain effect. Operator can tune the angle / intensity / cadence with one className change.

## 9. Operator browser-verify checklist

```sh
npm run dev
# open http://localhost:3000
```

**Hero — desktop ≥1024 px:**
- [ ] Cinematic 7/5 split. Left: navy-gradient panel with subtle animated sheen + Car silhouette. Right: floating white selector card overlapping the visual's right edge by ~48 px.
- [ ] Selector card shows "Step 1 of 3 · Choose brand" header + search field + brand grid.
- [ ] Below the split: orange eyebrow → FLAWLESS Restoration H1 → tagline → **inline stats** ("50,000+ Cars Serviced · 4 Centres in NCR · 4.9★ Rating · 15+ Years").

**Hero — mobile <1024 px:**
- [ ] Single column. Visual `aspect-[4/3]` → selector card (no overlap, stacks naturally) → H1+tagline+stats.

**Below hero:**
- [ ] **Insurance trust strip**: light-gray row, "CASHLESS WITH 30+ INSURERS" + 5 insurer names + "+ 26 more". Compact (~80 px).
- [ ] Service categories carousel section (existing, untouched).

**Why Choose Us section:**
- [ ] 4 pillars (Master Technicians · 100% Genuine OEM · Zero Hidden Costs · Cashless Insurance) — unchanged from polish pass.
- [ ] **NEW Trusted By strip** below the pillars: `border-t pt-8`, "Trusted By" eyebrow + Uber · Ola · Zoomcar · Fortune 500.

**Selector flow (any mount):**
- [ ] Tap a brand tile → step transitions to model grid; back arrow + brand crumb at top.
- [ ] Tap a model → step transitions to fuel grid; back arrow + model crumb.
- [ ] Tap a fuel → SelectionDisplay shows: Selected badge + "Brand Model" title + "Fuel: X" subline + "See Prices →" CTA + "Change vehicle" link.
- [ ] localStorage `acr_booking_ctx_v1` populated with the chosen triple (brand_id/model_id/fuel_id + slugs + names).
- [ ] Click "See Prices" → navigates to `/services` (in hero mount) OR fires the parent's `onComplete` handler.
- [ ] Click "Change vehicle" → returns to step 1 with selection cleared.

**PricingWidget (on ServiceDetail page):**
- [ ] When no vehicle in booking context → STATE 1 shows the inline `<PremiumVehicleSelector mode="widget">`.
- [ ] When vehicle picked → STATE 3 shows ₹X,XXX + Book button + Change vehicle link.
- [ ] Click "Change vehicle" → widget re-renders STATE 1 with the inline selector. Booking context still populated globally (other pages unaffected).
- [ ] Pick a new fuel inside the re-opened selector → widget collapses back to STATE 3 (price refreshed).
- [ ] Click "Keep current vehicle" link below the re-opened selector → reverts back to STATE 3 without changing.

**Smoke confirmation:**
- [ ] `npx playwright test --project=smoke` prints `3 passed`.
- [ ] No console errors anywhere on the homepage or service-detail pages.
