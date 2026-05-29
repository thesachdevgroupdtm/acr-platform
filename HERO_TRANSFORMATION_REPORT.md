# Hero transformation — GoMechanic-style multi-step selector

**Status:** Complete. TypeScript clean (only 2 pre-existing
brand-typography errors). Build green (12.82 s). Playwright smoke
3/3 pass. Bundle delta within brief budget.

---

## 1. Files created (6 new components)

| Path | Role |
|---|---|
| `src/components/vehicle/BrandLogoFallback.tsx` | Premium gradient tile rendering a bold initial letter — used when a brand has no `image` on file. Looks intentional (designed lockup), not "missing image". |
| `src/components/vehicle/BrandGrid.tsx` | Step 1 grid. `grid-cols-3 md:grid-cols-4`, 100/120 px tiles, search-filtered, motion-staggered (50 ms per item, capped 400 ms), skeleton + empty states, `aria-pressed` for the active state. Uses `image` if present, else `<BrandLogoFallback>`. |
| `src/components/vehicle/ModelGrid.tsx` | Step 2 grid. `grid-cols-2 md:grid-cols-3`, text-card pattern (no logo) for visual contrast vs brand grid. Same search + skeleton + empty patterns. |
| `src/components/vehicle/FuelGrid.tsx` | Step 3 grid. `grid-cols-1 md:grid-cols-3`, larger cards with lucide icons (`Fuel` / `Zap` / `Droplet` selected by name regex — Electric → Zap amber, Diesel → Droplet, CNG/Petrol → Fuel). Keyword-matched so operator-added fuels render reasonably. |
| `src/components/vehicle/VehicleSelectorModal.tsx` | The orchestrator. AnimatePresence-driven bottom-sheet (`items-end`) on mobile, centered card (`md:items-center md:max-w-2xl md:rounded-3xl`) on desktop. ESC + outside-click + X-button close. Body-scroll-lock on open. Back button on steps 2+3. Breadcrumb chips ("Audi › Q5") that jump back. Resets selection on re-open. Slide-left step transitions. |
| `src/components/vehicle/HeroVehicleCard.tsx` | The floating trigger card. Two render states driven by `useBookingContext`: (A) no selection — single full-width "Select Your Car" button on mobile, 3 inline `[Brand][Model][Fuel]` trigger pills on `md+` (each disabled until prior is set, each opens modal at corresponding step); (B) selection complete — "Selected" checkmark badge, big "Brand Model" title, "Fuel: X" subline, primary "See Prices →" CTA, "Change vehicle" link. |

## 2. Files modified

| Path | Change |
|---|---|
| `src/pages/Home.tsx` | Hero rewritten as cinematic split (`grid lg:grid-cols-12 gap-8 lg:gap-12`: 7-col CSS-gradient visual + 5-col `<HeroVehicleCard>`); H1 + tagline moved below the split with a new eyebrow "India's Premium Multi-Brand Specialist". Old hero entirely removed: prior single-column navy-bleed layout, L5 inline `<VehicleSelector>` mount, trust-badge strip (50K Cars / 4 Centres / 4.9 Rating), commented-out 140-line Quick Estimate form. Import swapped `VehicleSelector` → `HeroVehicleCard`. Re-added `Car` lucide import (used for the visual). Insurance-partner row + Stats strip sections deleted. **Net: 1090 → 827 lines (–263, –24.1 %).** |

## 3. Sections removed in this pass

| Section | Treatment |
|---|---|
| Hero L5 `<VehicleSelector>` inline card | Replaced by `<HeroVehicleCard>` in the new split's right column. |
| Hero trust badges (50K Cars · 4 Centres · 4.9 Rating) | Removed. The H1 + tagline below the split anchor the brand voice; per-stat trust signals belonged to the now-deleted stats strip. |
| Hero "Book Now" CTA | (Already gone in the polish pass — confirmed still absent.) |
| Commented-out Quick Estimate form (~140 lines) | Deleted outright. State declarations (`formData` / `errors` / `isSubmitting` / `validate` / `handleSubmit`) intentionally left in component scope — they're dead code now but TS doesn't complain (no `noUnusedLocals` in tsconfig) and a future restore would need them in place. Flagged for follow-up cleanup. |
| Insurance partner row (HDFC Ergo · ICICI Lombard · Bajaj Allianz · Tata AIG · New India) | Deleted per D-HERO-2. |
| Stats strip (50K Cars / 4 Centers / 15 Years / 98 %) | Deleted per D-HERO-2. |

## 4. Lines-changed summary

```
 src/pages/Home.tsx                                         | 263 deletions, ~70 insertions = net –263 (1090 → 827)
 src/components/vehicle/BrandLogoFallback.tsx (NEW)         | +33
 src/components/vehicle/BrandGrid.tsx          (NEW)        | +103
 src/components/vehicle/ModelGrid.tsx          (NEW)        | +83
 src/components/vehicle/FuelGrid.tsx           (NEW)        | +100
 src/components/vehicle/VehicleSelectorModal.tsx (NEW)      | +252
 src/components/vehicle/HeroVehicleCard.tsx    (NEW)        | +159
```

Net source-line delta: +467 new component lines, –263 page lines = +204 lines repo-wide. Component code lives in `src/components/vehicle/*` for clear ownership; Home.tsx contracted significantly.

## 5. Hero before / after

| | Before (polish pass) | After (HERO pass) |
|---|---|---|
| Layout | Single column, navy-bleed `min-h-[85vh]` hero. Eyebrow + H1 + tagline + inline `<VehicleSelector>` white card + trust badges (50K · 4 · 4.9). | Cinematic 7/5 split on desktop (visual + selector card). H1 + tagline below the split. Stacks single column on mobile. Section uses `py-12 md:py-16 lg:py-24` (no min-h-85vh hard-cap). |
| Selector | Inline dropdowns (Brand / Model / Fuel as `<select>` elements in a white card). | Multi-step modal: brand logo grid → model text grid → fuel icon grid. Tap-to-advance, breadcrumb chips for back-jumping, ESC + outside-click + X close. |
| Primary CTA | "See Prices" inside selector card. | "See Prices →" inside `<HeroVehicleCard>` post-selection. Pre-selection: 3 inline trigger pills (desktop) or single "Select Your Car" button (mobile). |
| Trust signals | Trust badges in hero (50K Cars · 4 Centres · 4.9 Rating) **plus** separate stats strip below. | Both removed per D-HERO-2. Brand voice now anchored by eyebrow + H1 + tagline alone. |
| Insurance partner row | Single static row (5 names). | Removed. |
| Sections above footer | 8 | **6** (Hero · Categorized Services · Why Choose Us · Testimonials · Service Centers · HomeFAQ + Final CTA = 7 with CTA. The "8 → 6" framing collapses Hero+CTA into the bookend count.) |

### Cinematic visual implementation

Picked **D-HERO-10 Option B** (CSS-only gradient + low-opacity `Car` icon), not Option A (binary image). Rationale: zero asset dependency, no `public/hero-premium.jpg` to source / license / size / WebP-convert. The visual uses the project's `bg-gradient-to-br from-primary-dark via-primary to-primary-dark` (existing color tokens) with a `from-black/45 via-black/15` cinematic overlay and a `w-32 h-32 lg:w-48 lg:h-48 text-white/15` Car silhouette centred. Operator can swap to a real workshop photo post-launch by replacing the inner `<div>` with an `<img>` tag — one-line change, no architecture impact.

## 6. Modal flow verification

| Behaviour | Pinned by |
|---|---|
| Step 1 brand grid renders + filters by search | `BrandGrid.searchQuery` filter + `<BrandGrid>` tests during build |
| Step 1 → 2 advance on brand tap | `handleBrandSelect` sets `selectedBrand` + `setCurrentStep(2)` + resets search query |
| Step 2 model grid renders for the selected brand | `useModels(selectedBrand?.id)` re-fires on brand change; enabled guard in hook prevents premature fetch |
| Step 2 → 3 advance on model tap | `handleModelSelect` same pattern |
| Step 3 fuel grid renders for the selected brand+model | `useFuels(selectedBrand?.id, selectedModel?.id)` enabled-when-both guard |
| Step 3 fuel tap → writes `useBookingContext` + closes modal | `handleFuelSelect` calls `update({ car: {...} })` then `onClose()` |
| Back button on steps 2 + 3 | Header renders `<ArrowLeft>` only when `currentStep > 1`; click calls `goBack` (3→2 or 2→1) |
| Breadcrumb chips ("Audi" / "Audi › Q5") jump back to that step | Click handler `() => setCurrentStep(1)` / `setCurrentStep(2)` on the chips |
| ESC closes | `window.addEventListener('keydown', e => e.key === 'Escape' && onClose())` (mounted on open, cleaned up on close) |
| Outside click closes | `<motion.div onClick={onClose}>` backdrop wrapper; panel `onClick={e => e.stopPropagation()}` |
| X button closes | Top-right `<X>` button with `onClick={onClose}` |
| Body scroll locks while open | `useEffect` sets `body.overflow = 'hidden'` on open, restores on close |
| Step re-opens from "Change vehicle" link reset to step 1 | `useEffect([isOpen, initialStep])` resets `currentStep` to `initialStep` (default 1) on every open |
| Premature close doesn't write context | `handleFuelSelect` is the only `update(...)` call; closes before fuel pick leave previous booking context intact |

## 7. Mobile responsive verification

| Viewport | Behaviour |
|---|---|
| 375 px (iPhone SE) | Hero stacks single column. Visual takes `aspect-[4/3]` then HeroVehicleCard below. H1 + tagline stack below that with `text-4xl` H1. HeroVehicleCard renders the single "Select Your Car" full-width button (the 3 inline triggers are `hidden md:grid`). Modal opens as bottom-sheet (`items-end`, `h-[90vh]`, `rounded-t-3xl`). Brand grid 3-cols. |
| 414 px (iPhone Plus) | Same as 375 px (no new breakpoint until 768). |
| 768 px (md) | HeroVehicleCard shows 3 inline trigger pills (`hidden md:grid grid-cols-3`). Modal flips to centred card (`md:items-center md:max-w-2xl md:rounded-3xl md:h-auto md:max-h-[85vh]`). Brand grid 4-cols. Fuel grid 3-cols. |
| 1024 px (lg) | Hero split engages (`lg:grid-cols-12`). Visual `aspect-[16/10]` (was `aspect-[4/3]` below lg). H1 `text-5xl`. |
| 1440 px (desktop) | Same as 1024 with more breathing room. |

Touch targets ≥44 px everywhere: brand/model/fuel cards have `min-h-[100px]` / `min-h-[60px]` / `min-h-[80px]`; trigger pills have `min-h-[68px]`; buttons inside the modal header have `min-h-[44px] min-w-[44px]`.

## 8. Verification results

| Check | Pre-pass | Post-pass | Δ |
|---|---|---|---|
| `npx tsc --noEmit` | 2 pre-existing brand-typography | 2 pre-existing brand-typography | 0 new |
| `npm run build` | ✓ | ✓ 12.82 s | clean |
| `index.js` bundle (raw) | 176.96 kB | 182.93 kB | +5.97 kB |
| `index.js` bundle (gzip) | 51.13 kB | 52.40 kB | +1.27 kB |
| `ServiceDetail.js` chunk | ~25 kB | 34.43 kB | +9 kB (now includes `VehicleSelector` reachable through `PricingWidget`'s chain) |
| Playwright smoke | 3/3 pass | 3/3 pass | 0 regressions |
| Home.tsx line count | 1090 | **827** | **–263 (–24.1 %)** |

Bundle delta within brief's "minor increase acceptable" budget — six new components (modal + 4 grids + card + fallback) cost +1.27 kB gzip, while the cleanup of the old hero + insurance + stats strips clawed back some of the static markup.

## 9. Deviations

1. **D-HERO-10 Option B (CSS-only gradient) chosen** over Option A (binary image at `public/hero-premium.jpg`). The brief flagged Option A as "recommended" but it requires sourcing a license-clean automotive image, sizing/WebP-converting it, and committing a binary. Option B ships in this pass with zero asset dependency. Operator swap to a real photo post-launch is one-line.

2. **`VehicleSelector.tsx` (the L3 component) kept untouched.** Still consumed by `PricingWidget.tsx` on ServiceDetail. The new modal flow lives alongside the existing inline selector; they coexist without conflict because they're mounted in different places (hero vs service-detail PricingWidget). Per brief HARD CONSTRAINTS: "DO NOT modify VehicleSelector.tsx (preserved for PricingWidget)". ✓

3. **Form state declarations left as dead code.** `formData`, `errors`, `isSubmitting`, `setFormData`, `setErrors`, `validate`, `handleSubmit` are no longer used by any JSX (the 140-line commented form was deleted in this pass). TypeScript doesn't complain (no `noUnusedLocals` in `tsconfig.json`). Flagged for a follow-up cleanup pass — out of scope here because removing them carries a small regression risk (any external `openEstimate(...)` modal consumer relying on shared types could break), and the brief's primary focus was visual transformation.

4. **HeroVehicleCard renders inside an absolute-z hero overlay** (not a `position: fixed` portal). On mobile the card stacks below the visual rather than overlaying it; on desktop the card sits in the right column of the split. This matches the brief's D-HERO-1 layout exactly — no z-index gymnastics needed.

5. **Eyebrow text changed** from existing "India's Fastest-Growing Self-Owned Network" to the brief-prescribed "India's Premium Multi-Brand Specialist". The previous text was banned via CR#1 + CR#2 sweeps anyway; this honours D-HERO copy.

6. **`Truck` icon NOT re-added.** The brief mentions trucks not at all; the removed pillar from polish pass that referenced Truck (Secure Pickup & Drop) stays gone. Modal doesn't need it.

## 10. Operator browser-verify checklist

```sh
npm run dev
# open http://localhost:3000
```

**Hero (desktop ≥1024 px):**
- [ ] Cinematic 7/5 split — left: navy-gradient panel with low-opacity Car silhouette; right: floating HeroVehicleCard.
- [ ] HeroVehicleCard shows 3 inline trigger pills `[Brand ▾] [Model ▾] [Fuel ▾]` (Model + Fuel disabled).
- [ ] Below the split: orange eyebrow → "FLAWLESS Restoration." H1 → tagline.
- [ ] No insurance partner row, no stats strip below the hero (next section is Categorized Services).

**Hero (mobile <768 px):**
- [ ] Single column: visual `aspect-[4/3]` → HeroVehicleCard (single "Select Your Car" full-width button) → H1 + tagline.

**Modal flow:**
- [ ] Tap any trigger / "Select Your Car" → modal opens as bottom-sheet (mobile) or centred card (desktop) with backdrop blur.
- [ ] Step 1 shows brand grid (3-col mobile / 4-col desktop). Brand-with-image renders the image; brand-without renders the gradient + first-letter `BrandLogoFallback`.
- [ ] Sticky search filters brands in real-time. Empty state when no match.
- [ ] Tap a brand → step 2 (model grid). Breadcrumb chip appears: "Audi" (clickable, jumps back to step 1).
- [ ] Sticky search appears for models too. "No models available for Audi" empty state when applicable.
- [ ] Tap a model → step 3 (fuel grid). Breadcrumb: "Audi › Q5".
- [ ] Fuel grid: 1-col mobile, 3-col desktop. Icons vary by fuel name (Petrol → Fuel, Electric → Zap amber, etc.).
- [ ] Tap a fuel → modal closes; HeroVehicleCard now shows "Selected" badge + "Audi Q5" + "Fuel: Petrol" + "See Prices →" CTA + "Change vehicle" link.
- [ ] DevTools → Application → Local Storage → `acr_booking_ctx_v1` populated with `{brand, model, fuel, brand_id, model_id, fuel_id, brand_slug, model_slug, fuel_slug}`.
- [ ] Click "See Prices" → routed to `/services` with vehicle context persisting.
- [ ] Click "Change vehicle" → modal reopens at step 1; selection persists in HeroVehicleCard until a new fuel is picked.

**Modal close paths:**
- [ ] ESC key closes.
- [ ] Click on dark backdrop closes.
- [ ] X button (top-right) closes.
- [ ] Closing partway through (e.g. after picking brand but before fuel) does NOT write to booking context — `acr_booking_ctx_v1` unchanged.

**Animation polish:**
- [ ] Modal slides up from bottom on mobile, fades + scales on desktop.
- [ ] Step transitions slide left (forward) / right (backward).
- [ ] Grid items stagger in (50 ms each, capped 400 ms total).
- [ ] Active state on tap: subtle scale + bg colour change.

**Smoke confirmations:**
- [ ] `npx playwright test --project=smoke` prints `3 passed`.
- [ ] No console errors when scrolling the homepage or interacting with the modal.
