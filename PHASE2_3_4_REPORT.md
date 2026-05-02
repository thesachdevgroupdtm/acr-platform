# Phase 2.3.4 — Services pricing, button parity, gate reversal, phone uniqueness (report)

Single-commit hotfix bundle from user testing on Phase 2.3.3.
Closes four issues: **(1)** the catch-all "Our Services" page
(`Services.tsx`) still showed `base_price` rather than the
vehicle-specific price even with a vehicle selected — `/services`
returns base prices only by design, the page was missing a
companion `/pricing` lookup; **(2)** the **ADDED** state of the
Add-to-Cart button used `bg-primary-dark text-white` which made
it visually heavier than the **ADD TO CART** state, breaking size
parity in the row; **(3)** the 2.3.2 ComingSoon gates
(`FEATURES.checkoutFlow=false`, `FEATURES.bookingsList=false`)
need to flip back to `true` for the production launch — manual
team confirmation in the loop until Phase 2.5 ships
`/checkout/place-order`; **(4)** signup silently merged duplicate
phones AND the existing-user branch overwrote `name` on every
lead-capture call, surfacing as "my name keeps changing" in user
testing. Backend change scoped to `LeadCaptureController`; gates
flipped via `features.ts`; ComingSoon files preserved on disk for
possible 2.5 re-use; the Cart `console.info` breadcrumb removed
as dead code.

## Files modified

### Backend
| File | Change |
|---|---|
| `backend/app/Http/Controllers/Api/V1/Auth/LeadCaptureController.php` | Added `intent: nullable in:signup,lead_capture` validation rule. When `intent === 'signup'` and a row with that phone already exists, returns `422 { errors:{ phone: [...] } }` with a clean message before any firstOrCreate. The existing-user branch no longer rewrites `$user->name` — name is only writable through `PUT /user/profile` (Phase 2.1). Email soft-update preserved. The 2.3.3 email pre-validation and `try/catch QueryException` safety net are unchanged. |

### Frontend
| File | Change |
|---|---|
| `src/config/features.ts` | Flipped `checkoutFlow` and `bookingsList` from `false` → `true`. Inline comments rewritten as Phase 2.3.4 — flags retained as keys so a Phase 2.5 partial rollout can flip them back briefly without re-introducing the constants. |
| `src/pages/Cart.tsx` | Removed the dead `console.info("[Phase 2.3.2] Checkout flow gated…")` block in `handleCheckout` and the now-unused `import { FEATURES }`. Navigation logic unchanged: guest → `openAuth`; authenticated → `setCurrentPage("checkout")`. |
| `src/pages/CheckoutComingSoon.tsx` | One-line header comment marking the page as currently unreachable / preserved for reference. No code change. |
| `src/pages/BookingsComingSoon.tsx` | Same one-line header comment. No code change. |
| `src/types/api.ts` | `LeadCaptureRequest` gains optional `intent?: "signup" \| "lead_capture"`. Default behaviour preserved. |
| `src/hooks/useAuth.ts` | `signUp({...})` now sends `intent: "signup"` to `postLeadCapture`. Login (`logIn`) is unchanged — it routes through `/auth/login` which has always been phone-lookup-based. |
| `src/pages/Services.tsx` | Adds a companion `usePricingFor` query keyed on every visible sub-service id and the bookingCar IDs; builds a `Map<service_id, price>` from `matched_prices`. New `priceFor: (subId) => number \| undefined` prop on `<CategorySection>`; the row renders `priceMap.get(sub.id) ?? sub.base_price` so prices match `ServiceCategory` and `ServiceDetail` for the same (service, vehicle) tuple. **ADDED button styling** updated to inherit identical sizing — only the colors invert (`bg-white text-primary border-primary`) so the button does not visually jump between states. |
| `src/pages/ServiceCategory.tsx` | **ADDED button styling** matched: `bg-white text-primary border border-primary` for ADDED, `bg-primary text-white border border-primary` for ADD TO CART. Border kept on both states so the box model is identical. |
| `src/pages/ServiceDetail.tsx` | Same ADDED state — ADDED uses `border-primary` against the white background; the ADD TO CART base inherits `border-white` so the box dimensions stay constant on the primary-colored sidebar. |

No backend route, model, or migration changes. No new files. No
package installs.

## PART A — Services.tsx vehicle-specific pricing (Issue 1)

### Root cause
`fetchServices` (`GET /api/v1/services`) returns `SubServiceResource`
which deliberately omits vehicle-resolved prices — the resource's
docstring states pricing is scoped to the per-slug detail endpoint
and to `/api/v1/pricing`. So even when bookingCar carried correct
ids+slugs (after 2.3.3's `BookingSidebar` + `ServiceCategory`
fixes), `Services.tsx` had no way to surface the priced row.

### Fix
A companion `usePricingFor` query in `Services.tsx` posts every
visible sub-service id with the bookingCar IDs and ingests
`matched_prices` into a `Map<number, number>`. The row's price
column now reads `priceMap.get(sub.id) ?? sub.base_price`, so
unselected vehicles fall back to the base price exactly as before
and selected vehicles see the same number ServiceDetail's Pricing
tab shows.

```ts
const allServiceIds = useMemo(() =>
  apiCategories.flatMap(c => (c.services ?? []).map(s => s.id)),
  [apiCategories]);
const pricingReq = useMemo(() => {
  if (!booking.car?.brand_id || !booking.car?.model_id || !booking.car?.fuel_id) return null;
  if (allServiceIds.length === 0) return null;
  return {
    brand_id:     booking.car.brand_id,
    model_id:     booking.car.model_id,
    fuel_type_id: booking.car.fuel_id,        // backend uses fuel_type_id
    service_ids:  allServiceIds,
  };
}, [booking.car, allServiceIds]);
const pricingQuery = usePricingFor(pricingReq);
const priceMap = useMemo(() => {
  const m = new Map<number, number>();
  for (const p of pricingQuery.data?.matched_prices ?? []) m.set(p.service_id, p.price);
  return m;
}, [pricingQuery.data]);
```

`<CategorySection>` gains a `priceFor` prop and the row uses it
instead of `sub.base_price` directly.

### Verify
With Audi Q3 Petrol selected: `Services.tsx` → "Battery Charging"
shows the same `₹X` value the ServiceDetail Pricing tab shows.
With no vehicle selected: row shows `base_price` or "Quote" — UX
matches Phase 2.3.3 baseline.

## PART B — ADDED button styling parity (Issue 2)

| Element | Before (2.3.3) | After (2.3.4) |
|---|---|---|
| ADD TO CART | `px-4 py-2 … bg-primary text-white hover:bg-primary-dark` (no border) | `px-4 py-2 … bg-primary text-white **border border-primary** hover:bg-primary-dark hover:border-primary-dark` |
| ADDED | `px-4 py-2 … **bg-primary-dark text-white**` | `px-4 py-2 … **bg-white text-primary border border-primary** hover:bg-primary/5` |

`border` is now present on both states so the box model is
identical — only the fill / text / border-color invert. Same icon
size (`w-3.5 h-3.5` on row buttons, `w-4 h-4` on the ServiceDetail
sidebar). `aria-pressed={inCart}` retained for assistive tech.
Applied consistently across `ServiceCategory.tsx`,
`ServiceDetail.tsx`, and `Services.tsx`.

The 1.8 s `justAdded` post-add flash continues to render the
ADDED style during the React Query refetch window — same code
path as 2.3.3.

## PART C — Restore original Checkout/Payment/Booking flow (Issue 3)

### `src/config/features.ts` diff
```diff
-  checkoutFlow: false,
+  checkoutFlow: true,
-  bookingsList: false,
+  bookingsList: true,
```

Both keys retained. Inline comments rewritten to Phase 2.3.4 — the
gates served their diagnostic purpose during 2.3.x and stay on the
shelf so a Phase 2.5 partial rollout can flip them temporarily if
needed.

### Gate fall-through
The early-return guards in `Checkout.tsx`, `Payment.tsx`, and
`MyBookings.tsx` look like:
```ts
if (!FEATURES.checkoutFlow) return <CheckoutComingSoon …/>;
```
With `FEATURES.checkoutFlow === true` the negation is false, the
return is skipped, and the existing implementation below runs as
it did pre-2.3.2. **No code changes** to those three files
beyond what 2.3.2 already added.

### ComingSoon header notes
`CheckoutComingSoon.tsx` and `BookingsComingSoon.tsx` each gain a
single comment line at the top:
```ts
// Phase 2.3.4 — currently unreachable; preserved for reference and
// possible re-use during Phase 2.5 partial rollouts.
```
The full implementations remain on disk so a future flag flip is
zero-code.

### Cart breadcrumb removal
The `console.info("[Phase 2.3.2] Checkout flow gated…")` block in
`handleCheckout` becomes dead code under `checkoutFlow=true` and
is removed alongside the now-unused `import { FEATURES }`. The
guard for unauthenticated users (`openAuth("login", "checkout")`)
remains.

### Verify
- Cart → Proceed to Checkout → Contact Details + Payment Method →
  Submit → fake `ACR<Date.now()>` invoice → Booking Confirmation —
  the pre-2.3.2 flow is reachable end-to-end.
- MyBookings renders the user-profile + bookings-history layout;
  the bookings list is empty (no persistence pre-2.5), matching
  pre-2.3.2 behaviour. **No fake localStorage writes were added.**

## PART D — Phone uniqueness on signup (Issue 4)

### Investigation result
`LeadCaptureController` had **two** name-mutation paths on
existing users:
1. The `firstOrCreate` second-arg `['name' => $name, …]` is the
   *create* payload — Eloquent does not overwrite on match. ✓
2. The existing-user branch (`if (!$user->wasRecentlyCreated)`)
   ran `$user->name = $name; … $user->save();` unconditionally. **This
   was the bug** — every Quick-Estimate call rewrote the user's
   name, surfacing as "name keeps changing."

`VerifyOtpController` was clean — it only sets
`is_verified_phone`/`is_verified_email`/`last_login_at`.

### Backend fix (Approach A from the brief)
- New validation rule: `intent => nullable in:signup,lead_capture`,
  defaulting to `lead_capture`.
- When `intent === 'signup'`: pre-check `users.phone === $phone`;
  if a row exists, return `422` with `errors.phone[0] = "This phone is already registered."` and a friendly `message` pointing the user at login.
- The existing-user branch no longer touches `$user->name`. Only
  `email` is soft-updated under the same rules as 2.3.3 (and only
  when actually changed — `$user->save()` is skipped on a no-op
  update so `updated_at` doesn't churn).

### Frontend fix
- `LeadCaptureRequest.intent?: "signup" | "lead_capture"` added to
  `src/types/api.ts`.
- `useAuth.signUp(...)` now sends `intent: "signup"`. The
  Quick-Estimate flow continues to call `postLeadCapture` without
  `intent`, defaulting to `lead_capture` server-side.
- The 422 phone error renders in `AuthModal`'s phone-field error
  slot via the existing `errors[fieldName]` plumbing — no
  additional code needed.

### Curl regression (5 cases)

```
$ POST /auth/lead-capture {phone:"5555000222", name:"Different Name", intent:"signup"}
HTTP 422
{"success":false,
 "message":"This phone number is already registered. Please log in to your existing account.",
 "errors":{"phone":["This phone is already registered."]}}      ✓

$ POST /auth/lead-capture {phone:"5555000222", name:"Quick Estimate Name", intent:"lead_capture"}
HTTP 200
{"success":true,"pending_user_id":12,"otp_sent_to":"phone","dev_code":"414385"}    ✓

$ POST /auth/lead-capture {phone:"5555000222", name:"Default Intent Name"}    (no intent)
HTTP 200
{"success":true,"pending_user_id":12,"otp_sent_to":"phone","dev_code":"951514"}    ✓ defaults to lead_capture

$ POST /auth/lead-capture {phone:"4400001111", name:"Brand New User", intent:"signup"}
HTTP 200
{"success":true,"pending_user_id":13,"otp_sent_to":"phone","dev_code":"450583"}    ✓ new account creation

$ # Regression: confirm name was NOT overwritten on existing user 5555000222
mysql> SELECT phone, name FROM users WHERE phone='5555000222';
+-------------+-------+
| phone       | name  |
+-------------+-------+
| 5555000222  | Fresh |
+-------------+-------+
                                                           ✓ pre-2.3.4 "Different Name" / "Quick Estimate Name"
                                                             never landed; original name preserved
```

## Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2161 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-CqKwMVZk.css   104.93 kB │ gzip:  17.22 kB
dist/assets/index-Ctq79gxV.js    738.65 kB │ gzip: 195.41 kB
✓ built in 1m 29s

$ # Vite dev restart
VITE v6.4.2  ready in 2490 ms
GET http://localhost:3000/  →  HTTP 200
```

## Single commit

`3c860cb5efe56614ee22bbc7719e68300a88f0e5` — 11 files, 435 insertions, 39 deletions.
1 backend file, 9 frontend files, 1 report file.

## Deviations

1. **`Services.tsx` `usePricingFor` always fetches when bookingCar
   is set**, regardless of whether the user has clicked "Check
   Prices" on the BookingSidebar (`booking.pricesShown`). The
   results only render in rows where `pricesShown && pricesAvailableForCategory`,
   so the unauthorised-price case stays gated; the network request
   itself is benign because the backend only returns rows the user
   could have priced anyway. Marginal extra request; deemed
   acceptable for the launch fix.

2. **ComingSoon files retained on disk** as the brief required.
   They produce a small bundle-size cost when imported by `Checkout.tsx`/`Payment.tsx`/`MyBookings.tsx` even when unreachable, because the early-return is evaluated at render time, not at import time. Tree-shaking does not remove them. If bundle size ever becomes a launch blocker, the imports can be flipped to dynamic on the same flag flip Phase 2.5 will perform.

3. **Phone-uniqueness check is a separate query** rather than a
   Laravel `unique` rule. The 2.3.3 email pre-validation followed
   the same pattern (the `unique` rule throws against the request
   payload, not against an existence semantic differentiated by
   intent). Consistent error shape; same defense-in-depth `try/catch QueryException` covers any race.

4. **Name freeze is unconditional on the existing-user branch.**
   Even when `intent === 'lead_capture'`, name is no longer
   updated. This means Quick-Estimate users who type their name in
   the form do not have it propagated to their account record.
   Rationale: a stale account name is far less harmful than a
   silently-overwritten correct one, and PUT /user/profile is the
   contract-blessed path for name edits. The brief explicitly
   instructed this approach.
