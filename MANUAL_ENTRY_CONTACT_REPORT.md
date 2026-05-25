# MANUAL_ENTRY_CONTACT — Reroute manual vehicle-entry users to /contact (prefilled)

Manual entry ("Can't find your car? Enter manually") means we have **no
structured brand/model/fuel ids to price by** — so sending the user to the
service/pricing page produced a blank *"Select Your Car"* and reset everything.
Manual entry is now treated as a **lead**: it routes to **/contact** with the
phone + car **prefilled**. Structured Brand→Model→Fuel selection is **unchanged**
and still flows to pricing.

**tsc clean (2 pre-existing only) · vite build clean · smoke 3/3 · all manual
scenarios verified headless.** Additive booking-context fields only.

---

## 1. Audit (PART A)

| Item | Finding |
|---|---|
| **Manual UI / handler** | `VehicleSelector.tsx` → `ManualForm` (brand/model/fuel **text only, no phone**) → `submitManual()` → `finish({brand,model,fuel})`. No `*_id` fields produced. |
| **`finish()`** | The **single** car-write path: `update({car})` + `onComplete?.(sel)`. (Confirmed by grep — `ServiceCategory` only *reads* `car.brand_slug`; no other writer.) |
| **Proceed handlers** | (1) `HomeCarSelector.onCheckPrices` → validates car+10-digit phone → `navigate("/services")`. (2) `HomeCarSelector`/`CarSidebar` `VehicleSelector.onComplete` → just `setSelectorOpen(false)`. CarSidebar pricing gates on `hasVehicle = !!(brand_id && model_id && fuel_id)` → **manual cars (no ids) → blank "Select your car"** = the reported bug. |
| **Selector mount points** | `VehicleSelector` mounted **only** in `HomeCarSelector` + `CarSidebar` → two reroute sites cover everywhere (D-MAN-5). |
| **Contact form** | `Contact.tsx`, `formData{name,phone,carInfo,service,message}`. Phone field ← `phone`, "Car Model & Year" ← `carInfo`. Did **not** consume booking context. |
| **Booking context** | `BookingState{location,car,phone,otpVerified,pricesShown}`, localStorage key `acr_booking_ctx_v1`. |

---

## 2. Booking-context additive fields (PART B) — `src/hooks/useBookingContext.ts`

Added to `BookingState` (and `DEFAULT_STATE`), **no field renamed/removed**:
```ts
entry_mode: "structured" | "manual";   // default "structured"
manual_car_text?: string;               // manual only — "Tesla Model 3 Electric"
manual_phone?: string;                  // manual only — typed phone (digits)
```
Default `entry_mode:"structured"` preserves existing behaviour for every prior
user / fresh visitor.

---

## 3. Entry-mode marking (PART B) — `src/components/vehicle-selector/VehicleSelector.tsx`

`finish()` now writes `entry_mode` on **every** car write and clears `manual_*`
unless manual — so a fresh structured pick always resets the flag (D-MAN-6):
```ts
const { entry_mode = "structured", manual_car_text, manual_phone, ...car } = sel;
update({
  car: { ...car },
  entry_mode,
  manual_car_text: entry_mode === "manual" ? manual_car_text : undefined,
  manual_phone:    entry_mode === "manual" ? manual_phone    : undefined,
});
```
- **Structured** `pickFuel` → `finish({…ids/slugs/segment, entry_mode:"structured"})`.
- **Manual** `submitManual` → `finish({brand,model,fuel, entry_mode:"manual",
  manual_car_text:[b,m,f].join(" "), manual_phone: phone||undefined})`.
- `ManualForm` gained one **optional** phone input (`type=tel`, digit-sanitised,
  max 10) and the CTA reads **"Continue"**. The 3 existing fields are still
  required to enable it. (Selector flow/steps otherwise untouched.)

---

## 4. Reroute logic (PART C) — manual → /contact

`VehicleSelection` carries `entry_mode`, so each parent's `onComplete(sel)` branches:

**`HomeCarSelector.tsx`**
```ts
onComplete={(sel) => {
  setSelectorOpen(false);
  if (sel.entry_mode === "manual") { navigate("/contact"); return; }
  if (errors.car) setErrors((e) => ({ ...e, car: "" }));   // structured: unchanged
}}
```
Plus a defensive guard in `onCheckPrices` for a manual car persisted in context:
```ts
if (state.entry_mode === "manual") { navigate("/contact"); return; }
// …unchanged structured validation + navigate("/services")
```

**`CarSidebar.tsx`** (service/category/detail pages — D-MAN-5)
```ts
onComplete={(sel) => {
  setSelectorOpen(false);
  if (sel.entry_mode === "manual") navigate("/contact");  // structured: just close
}}
```

---

## 5. Contact prefill (PART D) — `src/pages/Contact.tsx`

Now reads `useBookingContext()` and prefills **on mount only, non-destructively**:
```ts
useEffect(() => {
  if (state.entry_mode !== "manual") return;
  setFormData((prev) => ({
    ...prev,
    phone:   prev.phone   || state.manual_phone    || "",
    carInfo: prev.carInfo || state.manual_car_text || "",
  }));
}, []); // mount only
```
- Phone Number ← `manual_phone`; Car Model & Year ← `manual_car_text`.
- `prev.x || …` means anything the user has already typed is **never** overwritten.
- Full Name, Service Required, Message left empty for the user.

---

## 6. Structured flow confirmed unchanged

- `pickFuel` still writes the full `{ids, slugs, segment}` car; only `entry_mode:"structured"` was appended.
- The structured branch of every `onComplete`/`onCheckPrices` is byte-for-byte the prior code — the manual `if` returns *before* it.
- Headless proof (scenario d): after a structured pick the context has real ids `brand_id=34, model_id=317, fuel_id=6`, `entry_mode="structured"`, and Check Prices → **/services** (not /contact).

### Required supporting fix (deviation — see §9)
The localStorage write in `useBookingContext.update()` was moved **out of the
`setState` updater to a synchronous read-merge-write**. Reason: `finish()` calls
`update()` then the parent calls `navigate("/contact")` in the same tick, which
unmounts `VehicleSelector`; React then **drops the queued setState updater**,
taking its `writeState` side-effect with it — so the manual data never reached
localStorage (verified: context showed `car:null, entry_mode:"structured"`).
Writing synchronously fixes this and is strictly more reliable for all callers
(shape unchanged; same-tick updates still chain because the write is immediate).

---

## 7. tsc / build / smoke (PART E)

| Check | Result |
|---|---|
| `npx tsc --noEmit` | only the **2 pre-existing** `brand-typography.spec.ts` SVG-cast errors |
| `npx vite build` | **clean** (exit 0) |
| `npx playwright test --project=smoke` | **3/3 passed** (re-run after the `useBookingContext` change) |

---

## 8. Manual verification (headless Playwright, against the live dev server)

| Scenario | Result |
|---|---|
| **(b)** Home → "Enter manually" → Tesla / Model 3 / Electric / 9876543210 → Continue | → **/contact**; LS `entry_mode:"manual"`, `manual_car_text:"Tesla Model 3 Electric"`, `manual_phone:"9876543210"`. **Car Model & Year** = "Tesla Model 3 Electric", **Phone** = "9876543210", **Name/Message empty** ✓ |
| **(c)** /services (CarSidebar) → "Select Vehicle" → manual Lucid / Air / Electric / 9123456780 → Continue | → **/contact**; Car Model & Year = "Lucid Air Electric", Phone = "9123456780" ✓ |
| **(a)+(d)** Stale manual context → fresh structured Brand→Model→Fuel pick | `entry_mode` reset to **"structured"**, real ids `34/317/6`, `manual_*` cleared (undefined); Check Prices → **/services** ✓ (no manual leak, pricing unchanged) |
| **(e)** Blank "Select Your Car" for manual users | **Gone** — manual users land on /contact (prefilled), never the id-less pricing path ✓ |

---

## 9. Deviations

1. **`useBookingContext.update()` made synchronous** (see §6). Necessary for the
   feature to work at all (navigate-on-same-tick was dropping the manual write).
   Behaviour-preserving for existing callers; booking-context **shape unchanged**.
2. **Added one optional phone field** to the manual form and renamed its CTA
   "Use This Car" → **"Continue"**. This is within manual-entry handling (the
   verification flow expects "type car + phone"); the brand/model/fuel steps and
   the structured flow are untouched.
3. **Did not clear the manual flag on contact mount.** D-MAN-6's no-leak
   guarantee is provided by `finish()` resetting `entry_mode` on the next
   structured pick; leaving the manual context intact keeps the prefill
   idempotent across a /contact refresh.

## Constraints honoured

Structured pricing flow + pricing logic/APIs untouched · contact page only
prefilled (not redesigned) · selector flow only rerouted (one optional field
added) · booking-context changes additive only · no packages installed · tests &
smoke green (3/3) · tsc only the 2 pre-existing errors · git left to operator (D-MAN-7).
