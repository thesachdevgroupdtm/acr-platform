# SELECTOR-DENSITY — GoMechanic-parity visual pass (Brand → Model → Fuel)

Purely visual density pass on the shared `VehicleSelector` grids. Flow, APIs, and
selection→booking→pricing logic are unchanged. Borderless image-first cells, larger
logos/images/icons, tighter grid rhythm — ACR palette only (no GoMechanic colors).

**tsc clean (2 pre-existing only) · build clean · smoke 3/3.** Frontend styling only;
4 files touched: `BrandGrid.tsx`, `ModelGrid.tsx`, `FuelGrid.tsx`, `VehicleSelector.tsx`.

---

## 1. Audit — current vs reference divergences

| Aspect | Before (dashboard-like) | Reference (GoMechanic density) |
|---|---|---|
| Cell chrome | permanent `border border-border`; active = heavy `bg-primary text-white` box | borderless; transient tint only |
| Brand logo | `w-10 h-10` (40px) | ~80-90px |
| Model image | `w-10 h-10` (40px) | ~100-110px (dominant) |
| Fuel icon | `w-6 h-6` (24px) | ~70-80px |
| Grid | always `grid-cols-3`, `gap-2`/`gap-3` (8-12px) | 3-col desktop / 2-col mobile, gap-x ~16-20 / gap-y ~20-24 |
| Labels | `text-[11px] uppercase tracking-tighter font-bold` | ~14px, natural case, medium, #111 |
| Header title | `text-sm` | ~18px bold |

## 2. Chrome removal (D-DENS-1) — before/after, all 3 grids

Cell class is now (shared per grid):
```
flex flex-col items-center justify-center gap-2 p-2 min-h-[…] rounded-lg border text-center transition-colors
  default:  border-transparent  hover:border-primary/30 hover:bg-primary/5
  selected: border-primary bg-primary/5
```
- **No** permanent border/box/shadow/bg around items. A `border-transparent` placeholder
  keeps dimensions stable so hover/select adds the thin border with **zero layout shift**.
- Selected/hover = subtle ACR-blue tint + thin border (transient), not a heavy white-on-blue box.

## 3. Image/icon sizing (D-DENS-2)

| Step | Before | After | Fallback |
|---|---|---|---|
| Brand logo | 40px box, `object-contain` | **`w-20 h-20` (80px)**, `max-w/h-full object-contain` | first-letter `text-3xl font-bold text-primary` |
| Model image | 40px | **`h-20` × `max-w-[112px]` (~110px wide)**, object-contain | `Car` icon `w-12 h-12` |
| Fuel icon/image | 24-32px | **`w-[72px] h-[72px]`** area; icon `w-14 h-14` (~56px) / image fills 72px | fuel `lucide` icon (Zap/Droplet/Wind/Fuel) |

`hero_image_url` rendering + the model/fuel image-vs-fallback logic from the prior fix
are preserved — only sizes changed.

## 4. Grid / spacing / modal (D-DENS-3/4/5)

- Grid: **`grid-cols-2 sm:grid-cols-3`** (2-col mobile, 3-col desktop) on all 3 steps.
- Gaps: **`gap-x-4 gap-y-5`** (16px column, 20px row).
- Label-to-image gap: `gap-2` (8px) inside the cell.
- Search: **`py-3` (~46px)**, `rounded-lg`, magnifier `w-5 h-5` left, `space-y-4` (16px) to grid.
- Body padding stays `p-4` (16px) — already in spec range; header/footer compact (`py-3`).
- Equal cell heights via `min-h` (brand 124 / model 132 / fuel 112) + `justify-center`;
  skeletons use the same `min-h` so the data swap doesn't reflow.
- Header title bumped to `text-base sm:text-lg font-black`, dropped uppercase (cleaner
  hierarchy). Modal width/height is parent-controlled (Home card / CarSidebar) and was
  not touched, per the "only edit selector styling" constraint.

## 5. Selection state — ACR colors confirmed (D-DENS-6)

Selected = `border-primary` (**ACR Blue #1F4FA3**) + `bg-primary/5` (light blue tint).
Hover = `hover:border-primary/30 hover:bg-primary/5`. Labels `text-neutral-900` (near
#111). **No GoMechanic colors** — uses the project's `primary` (ACR Blue) + `neutral`
tokens only. `transition-colors` only (no new animations).

## 6. tsc / build / smoke

- `npx tsc --noEmit` → only the 2 pre-existing `brand-typography.spec.ts` errors.
- `npx vite build` → clean (exit 0).
- `npx playwright test --project=smoke` → **3/3 passed**.

## 7. How close to reference now

- **Brand:** borderless cells, 80px logos (letter-fallback at 3xl), 3-col desktop /
  2-col mobile, ~16/20px gaps → dense, image-first; ~3 rows visible in the card.
- **Model:** ~110px-wide images dominate, tight 14px labels, large car-icon fallback.
- **Fuel:** ~72px icon/image, compact, no dead space.
- Header compact (bold ~18px title + small step crumb), taller rounded search close to
  the grid. Matches GoMechanic **density + hierarchy**; styling stays ACR-branded.

## 8. Deviations

- **Header/search/body live in `VehicleSelector`**; per-item cells in the three grids.
  All chrome/sizing edits are in those 4 files. The outer modal width/height is set by
  the host (Home card `h-[520px]` / CarSidebar) and left untouched (constraint:
  only-selector-styling) — proportions already fit ~3 rows above the fold.
- **Labels dropped uppercase** and use natural case + `font-medium text-sm` (commerce
  reference) instead of the prior `text-[11px] uppercase tracking-tighter`. Color uses
  the project `text-neutral-900` token (≈ brand #111111) for consistency.
- **Fuel grid is `2-col mobile / 3-col desktop`** (was 2-col); with the usual 2-4 fuels
  this reads as a tidy single dense row.
- No flow/logic/API/animation changes; `hero_image_url` + image/fallback logic preserved.
