# COUPON_COMPACT — slim single-line coupon strip in the sidebar

The "Apply Coupon" area in the service-page sidebar was a **2-line block (~64px)**
that inflated the card height. It's now a **slim single-line strip (~39px)** in
both states. Existing component optimized — **no new files**, **no coupon logic
changed** (the side slider + apply/remove stay exactly as-is).

**tsc clean (2 pre-existing only) · vite build clean · smoke 3/3.**

---

## 1. Audit

| Item | Finding |
|---|---|
| Coupon UI | `src/components/CouponInput.tsx`, mounted in the sidebar at `CarSidebar.tsx:295` as `<CouponInput variant="summary" />`. |
| Variants | `summary` → **sidebar + Checkout** order-summary; `cart` → standalone Cart page panel. |
| Not-applied (was) | `variant="summary"` → `bg-neutral-50 p-3 border` **2-line** button: Tag icon + "Apply Coupon" + "Browse offers or enter a code" subtitle + ArrowRight ≈ **64px**. |
| Applied (was) | `bg-primary/5 border p-3` **2-line**: Sparkles + code + "− ₹X off" + ✕ ≈ **60px**. |
| Slider + logic | Click opens the **separate** `CouponPickerModal` (manual-code input + featured-offer list). Apply/remove via **`useCart().applyCoupon / removeCoupon`**. All reused, untouched. |
| Corners/colors | Already square (no radius) + ACR `text-primary` — kept. |

Confirmed the slider and apply/remove logic are separate and reusable.

---

## 2. Slim strip — both states (`variant="summary"` only)

Only the `summary` variant was changed (sidebar + Checkout summary); the `cart`
variant (Cart page) is left exactly as before.

**Not applied** — one compact row (`px-3 py-2.5`, ~40px), opens the existing slider:
```
[tag] Apply Coupon ......................... Apply →
```
```tsx
<button onClick={() => setOpen(true)} className="w-full bg-neutral-50 border border-border flex items-center gap-2 px-3 py-2.5 hover:border-primary ...">
  <Tag .../> <span class="flex-1 truncate ...">Apply Coupon</span>
  <span class="text-[11px] font-black uppercase text-primary">Apply <ArrowRight/></span>
</button>
```
(Dropped the "Browse offers or enter a code" subtitle that made it 2 lines.)

**Applied** — same slim row (`px-3 py-2.5`), reusing existing remove logic:
```
[✦] FIRST10 −₹500 ........................... Remove
```
```tsx
<div className="flex items-center gap-2 bg-primary/5 border border-primary/30 px-3 py-2.5">
  <Sparkles/> <p class="flex-1 truncate">{code}<span class="text-primary">−₹{discount_amount}</span></p>
  <button aria-label="Remove coupon" onClick={handleRemove} class="text-[11px] font-black uppercase text-primary">Remove</button>
</div>
```
`handleRemove` → existing `useCart().removeCoupon()` (unchanged). Remove-error line
kept (only shows on failure).

---

## 3. Height before / after (`summary` variant)

| State | Before | After |
|---|---|---|
| Not applied | ~64px (2 lines, `p-3`) | **39px** (measured) — single line, `px-3 py-2.5` |
| Applied | ~60px (2 lines, `p-3`) | **~39px** — identical single-line layout/padding |

The shorter strip removes ~25px from the sidebar, helping the checkout-reachability
work in the sidebar replica pass.

---

## 4. Existing slider / logic reused (unchanged)

- `CouponPickerModal` — opened on click, **not modified**.
- `useCart().applyCoupon / removeCoupon` — **not modified**.
- Pricing/discount calculation — **not modified** (read from `totals.coupon`).
- `cart` variant (Cart page) — **not modified**.
Only the `summary` render markup in `CouponInput.tsx` changed.

---

## 5. tsc / build / smoke

| Check | Result |
|---|---|
| `npx tsc --noEmit` | only the **2 pre-existing** `brand-typography.spec.ts` errors |
| `npx vite build` | **clean** (exit 0) |
| `npx playwright test --project=smoke` | **3/3 passed** |

Manual (headless, on `/services` with a car + items in cart):
- a. Coupon area is a **slim single line — measured 39px** (was ~64px) ✓
- b. Tapping it **opens the existing coupon slider** (ENTER COUPON input + offers list) ✓
- e. Sidebar visibly shorter (−~25px) ✓
- f. **Square corners + ACR colors** (`text-primary` action link, no radius) ✓
- c/d. Applied row + Remove: the applied strip uses the **identical** `px-3 py-2.5`
  single-line layout (≈39px) and the existing `removeCoupon()`. **Live application
  is gated behind login** — the picker returns *"Sign in to apply coupons"* for a
  guest (matches the test suite, which applies FIRST10 on an authenticated cart),
  so the applied row was verified by construction + code rather than a guest E2E
  click. Operator (logged in) confirms c/d.
- g. Pricing untouched — discount still read from `totals.coupon`, no calc changes.

---

## 6. Deviations

1. **Scope = `summary` variant only.** The change applies to the sidebar **and**
   the Checkout order-summary (both use `variant="summary"`) — a consistent slim
   improvement. The Cart-page `variant="cart"` panel is left unchanged.
2. **Applied-state E2E not clicked as a guest.** Coupon apply requires auth
   ("Sign in to apply coupons"); the applied strip shares the empty strip's exact
   single-line padding, so it's verified structurally + by build/typecheck, with
   live apply/remove left for the operator's logged-in check.
3. **CTA label "Apply"** (with chevron) on the right of the not-applied row, vs the
   old full-width arrow — clearer single-line affordance, same click → slider.

## Constraints honoured

No new files · coupon fetch/apply/remove + side slider unchanged · pricing/cart
logic unchanged · single-line slim strip both states · square corners + ACR colors ·
no packages · tsc 2 pre-existing only · smoke 3/3 · git left to operator (D-COUP-5).
