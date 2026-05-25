import { useState } from "react";
import { Sparkles, Tag, X, ArrowRight } from "lucide-react";
import { useCart } from "../hooks/useCart";
import type { CartTotals } from "../types/api";
import CouponPickerModal from "./CouponPickerModal";

interface CouponInputProps {
  totals: CartTotals | undefined;
  /** `cart` for the standalone Cart page panel; `summary` for the
   *  slim Checkout order-summary slot. */
  variant?: "cart" | "summary";
}

/**
 * Phase 2.5b — coupon entry surface.
 *
 * Two states:
 *
 *   1. No coupon applied: a single "Apply Coupon" button. Click
 *      opens <CouponPickerModal> which lists all featured coupons
 *      with eligibility flags + a manual-code input.
 *
 *   2. Coupon applied: an inline applied-state row showing
 *      "[sparkle] CODE — ₹X off [×]". The × calls
 *      useCart.removeCoupon().
 *
 * The pre-2.5b inline-cards layout is gone — coupons live in the
 * modal so the cart/checkout panels stay compact. The applied
 * state is read from server cart (cart.totals.coupon), so the
 * surface stays correct across page navigations and refresh.
 */
export default function CouponInput({ totals, variant = "cart" }: CouponInputProps) {
  const { applyCoupon, removeCoupon } = useCart();
  const [open, setOpen] = useState(false);
  const [removing, setRemoving] = useState(false);
  const [removeError, setRemoveError] = useState<string | null>(null);

  const applied = totals?.coupon ?? null;

  const handleRemove = async () => {
    setRemoveError(null);
    setRemoving(true);
    try {
      const res = await removeCoupon();
      if (!res.success) setRemoveError(res.error);
    } finally {
      setRemoving(false);
    }
  };

  if (applied) {
    // COUPON_COMPACT (D-COUP-3) — `summary` (sidebar/checkout) is a slim
    // single-line strip: code + discount on the left, Remove on the right.
    if (variant === "summary") {
      return (
        <div>
          <div className="flex items-center gap-2 bg-primary/5 border border-primary/30 px-3 py-2.5">
            <Sparkles className="w-4 h-4 text-primary shrink-0" />
            <p className="flex-1 min-w-0 text-xs font-black uppercase text-neutral-900 tracking-tighter truncate">
              {applied.code}
              <span className="ml-1.5 font-bold normal-case tracking-normal text-primary">
                −₹{applied.discount_amount}
              </span>
            </p>
            <button
              onClick={handleRemove}
              disabled={removing}
              aria-label="Remove coupon"
              className="shrink-0 text-[11px] font-black uppercase tracking-widest text-primary hover:underline disabled:opacity-50"
            >
              Remove
            </button>
          </div>
          {removeError && (
            <p className="text-[10px] font-bold text-accent-dark mt-1">{removeError}</p>
          )}
        </div>
      );
    }
    // `cart` (standalone Cart page) — unchanged.
    return (
      <div className="bg-primary/5 border border-primary/30 p-4">
        <div className="flex items-center gap-2.5">
          <Sparkles className="w-4 h-4 text-primary shrink-0" />
          <div className="flex-1 min-w-0">
            <p className="text-xs font-black uppercase text-neutral-900 tracking-tighter truncate">
              {applied.code}
            </p>
            <p className="text-[11px] text-primary font-bold">
              − ₹{applied.discount_amount} off
            </p>
          </div>
          <button
            onClick={handleRemove}
            disabled={removing}
            aria-label="Remove coupon"
            className="text-neutral-400 hover:text-accent-dark p-1.5 disabled:opacity-50"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
        {removeError && (
          <p className="text-[10px] font-bold text-accent-dark mt-1.5">{removeError}</p>
        )}
      </div>
    );
  }

  // Empty state — opens the existing picker modal/slider on click.
  // COUPON_COMPACT (D-COUP-1) — `summary` is a slim single-line row.
  return (
    <>
      {variant === "summary" ? (
        <button
          type="button"
          onClick={() => setOpen(true)}
          className="w-full bg-neutral-50 border border-border flex items-center gap-2 px-3 py-2.5 hover:border-primary transition-colors group text-left"
        >
          <Tag className="w-4 h-4 text-primary shrink-0" />
          <span className="flex-1 min-w-0 text-xs font-black uppercase tracking-tighter text-neutral-900 truncate">
            Apply Coupon
          </span>
          <span className="shrink-0 inline-flex items-center gap-1 text-[11px] font-black uppercase tracking-widest text-primary">
            Apply
            <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
          </span>
        </button>
      ) : (
        <button
          type="button"
          onClick={() => setOpen(true)}
          className="w-full bg-neutral-50 p-4 border border-border flex items-center gap-3 hover:border-primary transition-colors group text-left"
        >
          <Tag className="w-4 h-4 text-primary shrink-0" />
          <div className="flex-1 min-w-0">
            <p className="text-xs font-black uppercase tracking-tighter text-neutral-900">
              Apply Coupon
            </p>
            <p className="text-[10px] text-neutral-500">Browse offers or enter a code</p>
          </div>
          <ArrowRight className="w-4 h-4 text-primary group-hover:translate-x-1 transition-transform" />
        </button>
      )}

      <CouponPickerModal
        open={open}
        onClose={() => setOpen(false)}
        appliedCode={null}
        onApply={applyCoupon}
      />
    </>
  );
}
