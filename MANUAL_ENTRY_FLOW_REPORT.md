# MANUAL_ENTRY_FLOW — Fix homepage Check-Price form + manual car entry

Two bugs fixed on the homepage `Location | Car | Phone | [Check Price]` form:

- **BUG 1** — manual entry asked for brand+model+fuel **+ phone** (phone is a
  duplicate of the form's 3rd field). Manual entry now collects **car only**
  (brand, model, fuel, year — text), **no phone**.
- **BUG 2** — manual "Continue" jumped **straight to /contact**. Now "Continue"
  just **fills the Car field and returns to the form**; the `/contact` reroute
  happens on **"Check Price"** (after the user enters their phone).

**tsc clean (2 pre-existing only) · vite build clean · smoke 3/3 · all 7 manual
+ structured scenarios verified headless.** Booking-context changes additive
(prior iteration's `manual_car_text`/`manual_phone` replaced by the 4 separate
manual fields). Structured pricing flow unchanged.

---

## 1. Audit (PART A)

| Item | Finding |
|---|---|
| **Homepage form** | `src/components/home-car-selector/HomeCarSelector.tsx`, used **only** in `Home.tsx` (single instance → D-MAN-7 trivially satisfied). Fields: Location (`<select>`, auto-defaulted on mount), Car (button → `VehicleSelector`), Phone (`phone` local state). CTA = `onCheckPrices`. |
| **Manual entry** | `VehicleSelector.ManualForm` had **brand/model/fuel + phone**; `submitManual` required all of brand+model+fuel. |
| **BUG 2 source** | `HomeCarSelector`'s `VehicleSelector.onComplete` did `if (sel.entry_mode==='manual') navigate('/contact')` — i.e. redirected on "Continue". |
| **Check Price** | Button was always enabled; the prior manual guard navigated to `/contact` *before* validating/persisting phone. |
| **Contact form** | `src/pages/Contact.tsx`, `formData{name,phone,carInfo,service,message}`. Phone ← `phone`, "Car Model & Year" ← `carInfo`. Already consumes booking context. |
| **Booking context** | Had `entry_mode` + `manual_car_text` + `manual_phone` (prior iteration). |

---

## 2. Manual entry: phone removed, car-only fields (PART B / D-MAN-1)

`ManualForm` now has **4 text inputs** and **no phone**:
brand · model · fuel *(optional)* · year *(optional, 4-digit numeric)*.
`submitManual` requires **brand + model** (fuel + year optional — documented choice).

```ts
const ready = value.brand.trim() && value.model.trim();   // brand+model only
```

---

## 3. "Continue" returns to the form, no redirect (PART C / D-MAN-2)

`submitManual` → `finish(... entry_mode:"manual", manual_brand/model/fuel/year ...)`.
`finish()` writes to booking context and calls `onComplete()` — it **never
navigates**. `HomeCarSelector.onComplete` now simply closes the picker:

```tsx
onComplete={() => {
  setSelectorOpen(false);                 // return to form; NO navigation
  if (errors.car) setErrors((e) => ({ ...e, car: "" }));
}}
```

The Car field then shows the manual summary (D-MAN-2), e.g.
**"TESLA CYBERTRUCK · ELECTRIC · 2024"** — `carLabel` appends `manual_year` for
manual entries; structured stays `Brand Model · Fuel`.

---

## 4. Booking context (additive) (D-MAN-3)

```ts
entry_mode: "structured" | "manual";   // default "structured" (kept)
manual_brand?: string;                  // new
manual_model?: string;                  // new
manual_fuel?:  string;                  // new
manual_year?:  string;                  // new
```
`finish()` sets `entry_mode` on **every** car write and clears all `manual_*`
unless manual → a fresh structured pick resets the flag + fields (**D-MAN-8**).
Phone is **not** stored as a manual field — it is persisted into the existing
`phone` field on Check Price (per the D-MAN-6 note: "reuse a field").

---

## 5. Check Price: enable condition + redirect branch (PART D / D-MAN-4,5)

```tsx
const phoneValid = /^\d{10}$/.test(phone);
const canCheck = !!state.location && !!car && phoneValid;   // D-MAN-4
// …button: disabled={!canCheck}

const onCheckPrices = () => {
  // validate (guard; button also disabled) …
  update({ phone });                                        // persist for /contact + pricing
  navigate(state.entry_mode === "manual" ? "/contact" : "/services");  // D-MAN-5
};
```
The CTA is **disabled** until Location + Car + valid 10-digit Phone are all set.

---

## 6. Contact prefill — manual only (PART E / D-MAN-6)

`Contact.tsx`, mount-only + non-destructive:
```ts
if (state.entry_mode === "manual") {
  const composedCar = [manual_brand, manual_model, manual_fuel, manual_year].filter(Boolean).join(" ");
  setFormData((prev) => ({
    ...prev,
    phone:   prev.phone   || state.phone || "",   // form's 3rd field, stored on Check Price
    carInfo: prev.carInfo || composedCar,         // "Tesla Cybertruck Electric 2024"
  }));
}
```
Full Name, Service Required, Message left empty. `prev.x || …` never overwrites
anything the user has begun typing.

---

## 7. Structured flow confirmed unchanged

- `pickFuel` still writes the full `{ids, slugs, segment}` car; only
  `entry_mode:"structured"` is appended (which also clears any stale `manual_*`).
- `onCheckPrices` for structured = `update({phone})` → `navigate("/services")`,
  same as before.
- Headless proof (g/e): after a structured pick over a *stale manual* context,
  the context is `entry_mode:"structured"`, `manual_*` undefined, real ids
  `34/317/6`; Check Price → **/services** (no contact leak).

### Note — service-page sidebar (`CarSidebar`)
`CarSidebar` (Services/Category/Detail pages) has **no phone field and no Check
Price button**, so there is no "main form" to return to. Its manual `onComplete`
keeps routing to `/contact` (unchanged from prior work) — otherwise a price-less
manual car dead-ends on the id-gated empty state. BUG 2's "Continue returns to
the form" applies to the homepage form, which is the only surface with a
subsequent phone field.

---

## 8. tsc / build / smoke (PART F)

| Check | Result |
|---|---|
| `npx tsc --noEmit` | only the **2 pre-existing** `brand-typography.spec.ts` SVG-cast errors |
| `npx vite build` | **clean** (exit 0) |
| `npx playwright test --project=smoke` | **3/3 passed** |

---

## 9. Manual verification (headless Playwright, live dev server)

| # | Scenario | Result |
|---|---|---|
| **a** | Manual entry fields | 4 inputs (brand/model/fuel/year), **PHONE field absent** ✓ |
| **b** | Manual "Continue" | stays on **/** (no redirect); Car field shows "TESLA CYBERTRUCK · ELECTRIC · 2024"; LS `entry_mode:"manual"`, `manual_year:"2024"` ✓ |
| **c** | Phone enables CTA | Check Price **disabled** before phone, **enabled** after a 10-digit phone ✓ |
| **d** | Check Price (manual) | → **/contact**; Car Model & Year = "Tesla Cybertruck Electric 2024", Phone = "9876543210", Name empty ✓ |
| **e** | Structured + phone | Brand→Model→Fuel pick → fill phone → Check Price → **/services** ✓ |
| **f** | CTA gating | Check Price **disabled** with nothing filled ✓ |
| **g** | No leak | fresh structured pick after a stale manual context → `entry_mode:"structured"`, `manual_*` cleared, real ids `34/317/6` ✓ |

---

## 10. Deviations

1. **Refactored the prior iteration's manual context fields.** Replaced
   `manual_car_text` + `manual_phone` (added in the earlier MANUAL_ENTRY_CONTACT
   work, same uncommitted feature) with `manual_brand/model/fuel/year` per
   D-MAN-3. No production data uses these (localStorage, ephemeral); a stale
   pre-refactor `manual_car_text` is simply ignored.
2. **Check Price is `disabled` (not inline-validated) until all 3 fields valid**,
   per D-MAN-4 + verification (f). The previous inline car/phone error messages
   are retained as a non-JS safety guard inside the handler.
3. **`CarSidebar` (service pages) still routes manual → /contact on completion**
   (see §7) — it has no phone/Check-Price step to defer to; redesigning the
   sidebar is out of scope.
4. **Manual "fuel" + "year" are optional** (brand + model required) — keeps the
   lead form low-friction while still composing a useful "Car Model & Year".

## Constraints honoured

Structured pricing flow / APIs untouched · contact page only prefilled (not
redesigned) · picker steps unchanged (only manual fields + Continue behaviour) ·
booking-context changes additive · phone collected **once** (form's 3rd field),
never in manual entry · no packages installed · tsc 2 pre-existing only · smoke
3/3 · git left to operator (D-MAN-9).
