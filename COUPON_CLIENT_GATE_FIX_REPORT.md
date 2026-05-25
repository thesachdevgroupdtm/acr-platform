# COUPON_CLIENT_GATE_FIX — the second gate: slider disabled Apply for guests

The prior pass removed the **backend apply-route** gate (`auth:sanctum` on
`POST /cart/coupon`). But the slider still showed **"Sign in to apply coupons."**
and the per-coupon **Apply** button stayed **disabled** for guests. This report
locates that second gate and removes it. Guest coupon preview now works; checkout
stays gated.

`tsc` → **2 pre-existing only** · `npm run build` → **clean** · smoke → **3/3**.

---

## 1. Where the gate + message were found (file:line + condition)

The message is **not a hard-coded frontend string** — that's why the first audit's
`grep "Sign in to apply" src/` came up empty. It's stamped by the **backend coupon
listing** and then **honored (rendered + used to disable Apply) by the slider**.

**(a) Message source — backend listing endpoint** the slider calls via
`useCoupons("cart")` → `GET /coupons?context=cart`:

`backend/app/Http/Controllers/Api/V1/Public/CouponsController.php:66-71`
```php
foreach ($list as $coupon) {
    if ($user === null) {                                  // guest (no Bearer token)
        $coupon->setAttribute('eligible', false);
        $coupon->setAttribute('ineligible_reason', 'Sign in to apply coupons.');
        continue;                                          // ← every coupon, for every guest
    }
    ...
```
For a guest, **every** coupon is returned `eligible=false` with
`ineligible_reason="Sign in to apply coupons."`.

**(b) The client-side gate that acts on it** — `src/components/CouponPickerModal.tsx`,
inside `CouponCard` (pre-fix):

| Line | Condition | Effect for a guest |
|---|---|---|
| 248 | `const eligible = coupon.eligible !== false;` | `eligible = false` |
| 249 | `const dim = !eligible && !isApplied;` | card dimmed (`opacity-60`) |
| 311 | `disabled={busy \|\| !eligible}` on the Apply button | **Apply disabled** |
| 319-323 | `{!eligible && coupon.ineligible_reason && ( <p>…{coupon.ineligible_reason}</p> )}` | renders **"Sign in to apply coupons."** |

So the per-card **Apply** was disabled and the message displayed because the listing
flagged guests ineligible.

**Not gates (checked, left alone):**
- The slider's **manual code input** (`onSubmitInput` → `tryApply(code,"input")` →
  `onApply` → `applyCoupon`) has **no** eligibility check — it already worked for guests.
- `src/components/CouponInput.tsx` — **no** auth/eligibility gate; it only opens the
  slider and renders `totals.coupon`. Nothing to change there.

---

## 2. What was removed / changed

One file, two edits — `src/components/CouponPickerModal.tsx`. The fix stops the slider
from honoring the backend's guest auth-nag, while keeping the eligibility UX for
signed-in users.

**Edit 1 — import auth state:**
```tsx
import { useAuth } from "../hooks/useAuth";
```

**Edit 2 — in `CouponCard`, neutralize the guest gate:**
```tsx
const { isAuthenticated } = useAuth();
const isApplied = appliedCode === coupon.code;
// Guest coupon preview: a not-signed-in visitor may tap Apply on any
// coupon to preview the discount. The backend listing stamps every
// coupon `eligible=false` + "Sign in to apply coupons." for guests
// (CouponsController); that auth nag is no longer honored here, so the
// Apply button stays enabled and the guest-capable apply endpoint does
// the real validation (active/expiry/min-order/applicability), surfacing
// any genuine error inline. For signed-in users the eligibility dimming
// + reasons (min order, already used, …) are kept exactly as before.
const eligible  = isAuthenticated ? coupon.eligible !== false : true;
const dim       = !eligible && !isApplied;
```

Resulting behavior:
- **Guest** → `eligible = true` → card not dimmed, `disabled={busy || !eligible}`
  becomes `disabled={busy}` (**enabled**), and `{!eligible && …}` is `false` so the
  **"Sign in to apply coupons." message is not rendered**. Tapping Apply calls the
  guest-capable `POST /cart/coupon`; valid → discount, invalid → inline validation error.
- **Signed-in** → `eligible = coupon.eligible !== false` (unchanged): real dimming,
  disabling, and reasons (min order / already used / not applicable) preserved.

No coupon logic touched (`useCart.applyCoupon`, `CouponService`, pricing all
unchanged). No slider layout change — only the `eligible` derivation. No new files,
no packages. The button/markup/animation are identical; only the gating boolean moved.

> Note: the backend string `'Sign in to apply coupons.'` (CouponsController:69) still
> exists in the API payload but is **no longer surfaced** for guests. It was left in
> place intentionally — the task scoped this fix to the client-side gate and "do NOT
> change coupon logic," and the apply route is already guest-capable, so removing the
> client honoring is sufficient to unblock guests. (A future cleanup could have the
> listing compute real guest eligibility against the X-Cart-Session cart, but that
> touches the eligibility pipeline and was out of scope here.)

---

## 3. Checkout gate confirmed untouched

`src/pages/Checkout.tsx` was **not modified**. The auth guard at `Checkout.tsx:359`
(`if (!isAuthenticated) { …Login to Continue… }`) is intact, and the backend
`checkout/quote`, `checkout/place-order`, and `user/orders` routes remain under
`auth:sanctum`. A guest who applies a coupon and proceeds to checkout still gets the
sign-in prompt. Only `src/components/CouponPickerModal.tsx` changed.

---

## 4. tsc / build / smoke

| Check | Result |
|---|---|
| `npx tsc --noEmit` | **2 pre-existing only** — `tests/e2e/brand-typography.spec.ts:121,137` (`HTMLElement \| SVGElement`). No new errors. |
| `npm run build` | **clean** (exit 0, built in ~5s) |
| `npx playwright test --project=smoke` | **3/3 passed** |

---

## 5. Verification

| Scenario | Expected | Result |
|---|---|---|
| Guest opens slider → taps **Apply** on a valid coupon | Discount applies, **no "sign in" message** | ✅ For guests `eligible=true` → button enabled, message not rendered; Apply → guest-capable `POST /cart/coupon` → cart totals update with the discount (rendered by `CouponInput`/`CarSidebar` from `totals.coupon`). |
| Guest applies an invalid / ineligible coupon | Validation error still shows | ✅ The apply endpoint runs the existing `CouponService::validate` and returns the reason (e.g. "Invalid coupon code.", "Minimum order ₹500 required…"); the slider shows it inline via `perCardError` / `generalError`. |
| Guest → checkout | Sign-in **still required** | ✅ `Checkout.tsx:359` gate untouched; checkout routes still `auth:sanctum`. |
| Signed-in user coupon flow | Unchanged | ✅ `eligible = coupon.eligible !== false` path preserved — dimming, disabling, and ineligible reasons (min order, already used) still shown. |

Behavior verified by code path inspection + the green tsc/build/smoke gates. The
live guest click-through (open slider → Apply → see discount; checkout → sign-in) is
the operator's final check.

---

### Summary of the two-gate picture
1. **Gate 1 (prior pass):** `auth:sanctum` on `POST /cart/coupon` — removed; apply
   endpoint is guest-capable.
2. **Gate 2 (this pass):** the slider disabled the per-coupon Apply button and showed
   "Sign in to apply coupons." because the `?context=cart` listing flags guests
   `eligible=false`. Fixed by not honoring that auth-eligibility flag for guests in
   `CouponPickerModal.tsx` — Apply enabled, message gone, real validation deferred to
   the (guest-capable) apply endpoint.
