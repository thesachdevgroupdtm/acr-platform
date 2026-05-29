# BookingSidebar — Phase BS-2 Polish Report

Fixes the BS-1 coupon stub mistake and brings the sidebar in line with the GoMechanic visual pattern. No backend, hook, or `src/components/CouponInput.tsx` / `CouponPickerModal.tsx` changes — they were already correct and are now consumed directly.

---

## 1. Hook + component audit

### `src/components/CouponInput.tsx` (real component — UNCHANGED)
- Required props: `{ totals: CartTotals | undefined; variant?: "cart" | "summary" }`. Default variant is `"cart"`.
- Renders an "Apply Coupon" entry button when no coupon is applied; opens `<CouponPickerModal>` with the live `/coupons?context=cart` list + manual-code input.
- When a coupon is applied, renders an inline applied-state row with code, discount amount, and `×` remove button. Read state from `totals.coupon`.
- The BS-1 prompt's claim "No props needed" was wrong — `totals` is required. Cart.tsx uses `<CouponInput totals={totals} variant="cart" />`; Checkout.tsx uses `<CouponInput totals={cart?.totals} variant="summary" />`. The sidebar matches Checkout (slimmer slot).

### `useCart().cart.totals` shape (`CartTotals` from `src/types/api.ts:162`)
```ts
{
  subtotal: number;
  discount: number;
  coupon: { code: string; name: string; discount_amount: number } | null;
  tax: number;
  total: number;
}
```
**Important:** the applied coupon is **nested** under `totals.coupon`, not flat `coupon_code` / `coupon_name` as the prompt suggested. The display label uses `totals.coupon.code`.

### `useCoupons` + `useCart.applyCoupon` / `removeCoupon` (UNCHANGED)
- Already wired into `CouponInput` + `CouponPickerModal`. No re-wiring needed.

### `model_image_url`
- **Does not exist** on `state.car`, on `CarModel` API resources, or on `BookingCar`. Per D-BS2-4's "If field doesn't exist yet, KEEP letter fallback — don't add new API fields in this commit," we keep `BrandLogoFallback` and switch only to the compact 40×40 variant.

---

## 2. Stub deletion confirmed

```
$ rm src/components/booking-sidebar/components/CouponInput.tsx
$ ls src/components/booking-sidebar/components/
BookingSummary.tsx
CartItem.tsx
MobileBottomSheet.tsx
MobileStickyBar.tsx
ServicesCart.tsx
VehicleChangeModal.tsx
VehicleSummary.tsx
```
No dead-code stub remaining.

---

## 3. CouponInput integration in BookingSummary

`src/components/booking-sidebar/components/BookingSummary.tsx` now:
- Accepts `totals: CartTotals | undefined` as a prop instead of computing a single `total`.
- Reads `subtotal`, `discount`, `coupon`, `total` from server `totals`.
- Renders a **Coupon Discount (CODE)** line only when `totals.coupon && discount > 0`.
- Imports the real `CouponInput`:
  ```tsx
  import CouponInput from "../../CouponInput";
  …
  <CouponInput totals={totals} variant="summary" />
  ```
- Removed the in-line disabled input + "Coupons coming soon" caption entirely.

---

## 4. VehicleSummary — compact variant

`src/components/booking-sidebar/components/VehicleSummary.tsx`:
- BrandLogoFallback now renders in a 40×40 (`w-10 h-10 rounded-lg`) tile to the left of the name. No more hero-sized blue square.
- Right side: an uppercase, primary-coloured, `text-xs font-semibold tracking-widest` **Change** link. Single-word treatment matches GoMechanic's pattern.
- Segment chip stays inline beside the fuel label.
- Empty-vehicle card unchanged (already correct from BS-1).
- Code comment marks where the optional `model_image_url` hero image variant would slot in once the backend surfaces that field.

---

## 5. Empty-cart visual

`src/components/booking-sidebar/components/ServicesCart.tsx`:
- Replaced the small grey shopping-bag circle + two-line copy with the GoMechanic empty state:
  - `<ShoppingCart>` from lucide-react at `80×80`, `text-neutral-300`, `strokeWidth=1.25`, centered.
  - Single line of copy: **"Go ahead and book a service for your car."** (verbatim from D-BS2-5)
  - "Browse Services →" CTA with a `+` glyph kept compact below.

---

## 6. Trust strip placement

`src/components/booking-sidebar/BookingSidebar.tsx`:
- Trust strip appears **only when `items.length > 0`** — empty state stays clean (D-BS2-10).
- Position: between `ServicesCart` and `BookingSummary`, with `mt-5` above and the divider `mt-5 mb-4` below.
- Styling: `bg-neutral-50 text-neutral-700 text-xs py-2.5 px-3 rounded-lg flex items-center justify-center`.
- Copy: `✓ Genuine OEM parts · ✓ 6-month warranty` (two `CheckCircle2` icons in `text-primary`).
- Shared body between desktop card + mobile bottom sheet → trust strip appears in both surfaces automatically.

---

## 7. Continue button restyle + reassurance line

`BookingSummary.tsx`:
- Button classes: `btn-ink btn-ink-primary w-full min-h-14 py-3.5 px-4 text-sm font-semibold uppercase tracking-widest rounded-xl`.
- Inherits the recent site-wide hover-invert (white-on-hover + primary border) via the `.btn-ink-primary` rule.
- Below the button, a small reassurance line in `text-xs text-neutral-500`:
  > `₹{total} payable on service completion`
- The reassurance only renders when `total > 0` (no `₹0 payable` noise during empty states).

---

## 8. Brand colour audit

Searched the BS-2 surface for off-brand tokens — none of these appear in the new code:
- ❌ `acr-blue` (operator's shorthand — the actual token is `primary`)
- ❌ `red-`, `orange-`, `cyan-`, `sky-`

Tokens used:
- `bg-white` (card surface)
- `border-neutral-200` (subtle borders)
- `text-neutral-900` (primary text)
- `text-neutral-600` (secondary text)
- `text-neutral-500` / `text-neutral-400` (tertiary captions)
- `text-primary` / `bg-primary` / `bg-primary/5` / `bg-primary/10` (the ACR Blue #1F4FA3 — CTAs + links + chip backgrounds)
- `bg-neutral-50` (empty-state + trust-strip card backgrounds)

The site-wide hover-invert (white-on-hover for primary buttons) added in the previous task applies to the new Continue CTA automatically.

---

## 9. Verification

| Check | Result |
|---|---|
| `npx tsc --noEmit` | 2 pre-existing baseline errors only (brand-typography tests) |
| `npm run build` | clean — 8.52 s |
| `assets/index-*.js` | 195.60 kB → **195.64 kB** (+40 B, basically flat) |
| `assets/ServiceDetail-*.js` | 51.94 kB → **52.15 kB** (+210 B for the trust strip + slightly larger BookingSummary) |
| `npx playwright test tests/e2e/smoke.spec.ts` | **3/3 pass** in 11.6 s |

---

## 10. Manual smoke checklist (operator)

The implementation is ready for visual verification at:

- `/services/car-care-detailing/car-wash` (or any priced service)
- Desktop ≥1024px: confirms full sidebar
- Mobile ≤375px: confirms sticky bar → bottom sheet flow

Expected:
1. Right column shows compact 40×40 brand tile + `{brand} {model}` + fuel + segment chip + uppercase **Change** link.
2. Cart row already populated with the current service (auto-add).
3. Trust strip "✓ Genuine OEM parts · ✓ 6-month warranty" beneath the cart.
4. Summary block shows `Subtotal (1 service)` + `Total`. **No** disabled "Coupons coming soon" input.
5. Beneath totals: an **Apply Coupon** button. Click → `CouponPickerModal` opens (same as Cart.tsx) with real eligible coupons.
6. Apply a coupon → modal closes, sidebar gains a `Coupon Discount (CODE) − ₹X` line in primary-blue, and the Total drops accordingly.
7. Click the `×` in the applied-coupon row → discount line disappears, Total restores.
8. Continue button is full-width, taller, rounded, uppercase. Hovers to white-on-blue. Reassurance line below shows `₹X,XXX payable on service completion`.
9. Empty cart (remove the only item): big outline ShoppingCart glyph + "Go ahead and book a service for your car." copy. Trust strip is gone, BookingSummary is gone.
10. Mobile: bottom sticky bar shows count + total + Continue. Tap → bottom sheet opens with the same coupon flow working end-to-end.

---

## 11. Deviations from BS-2 prompt

| # | Prompt assumption | Reality | Resolution |
|---|---|---|---|
| 1 | `<CouponInput />` with **no props** | Component requires `totals: CartTotals \| undefined` (and accepts a `variant` for slim layout) | Passed `totals={totals}` and `variant="summary"`. |
| 2 | `totals.coupon_code` + `totals.coupon_name` (flat) | Actual: `totals.coupon` is a nested `{ code, name, discount_amount } \| null` | Read `totals.coupon.code` for the discount-line label; show line when `totals.coupon && discount > 0`. |
| 3 | Use `bg-acr-blue` / `hover:bg-acr-blue-700` | These tokens don't exist in `tailwind.config` | Used the project's existing `primary` token (resolves to ACR Blue `#1F4FA3`) via `.btn-ink-primary`. Same colour, real class. |
| 4 | "If model_image_url, hero image variant" | Field doesn't exist anywhere | Kept BrandLogoFallback in 40×40 compact mode per the prompt's own fallback clause. Comment marks the slot for when the backend surfaces it. |
| 5 | "Read totals from useCart() directly OR accept totals prop" | Implementation choice | Passed `totals` from the orchestrator to `BookingSummary` — keeps the leaf component pure-presentational and easier to test in isolation. |

No backend changes. No hook changes. No edits to `src/components/CouponInput.tsx`, `CouponPickerModal.tsx`, `useCoupons.ts`, `useCart.ts`, Cart.tsx, Checkout.tsx. No new packages. No new routes.

---

## 12. Files modified (this phase)

```
src/components/booking-sidebar/
├── BookingSidebar.tsx                    (totals from server + trust strip + body spacing)
└── components/
    ├── BookingSummary.tsx                (rewritten: real coupon + server totals + restyled CTA)
    ├── VehicleSummary.tsx                (compact 40×40 logo + uppercase CHANGE link)
    ├── ServicesCart.tsx                  (GoMechanic empty state)
    └── CouponInput.tsx                   (DELETED — was the BS-1 stub)
```

5 files touched (1 deleted, 4 edited). 0 new files.

---

Stop point: operator visually verifies against the GoMechanic reference and confirms the coupon flow against the live backend.
