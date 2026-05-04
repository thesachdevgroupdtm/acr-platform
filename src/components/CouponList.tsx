import { Tag } from "lucide-react";
import { OFFERS } from "../data/businessData";
import type { CartTotals } from "../types/api";

interface CouponListProps {
  /** Currently-applied coupon (server cart's totals.coupon). */
  appliedCode: string | null;
  /** Disable Apply buttons while a mutation is in flight. */
  disabled?: boolean;
  /** Click handler — caller wires to useCart.applyCoupon. */
  onApply: (code: string) => void;
  variant?: "cart" | "summary";
}

/**
 * Phase 2.5.2 (D-2.5.2-2) — pre-defined coupon cards.
 *
 * Restores the pre-2.5.1 "browse offers" experience but with the
 * 2.5.1 manual-only behaviour: nothing auto-applies, user must
 * click Apply on a specific card. The card list is currently
 * sourced from the local OFFERS constant (src/data/businessData);
 * Phase 2.5b will swap this for a `GET /coupons` endpoint without
 * touching the consumer pages.
 *
 * Visuals match the existing Offers card vocabulary so the surface
 * looks intentional once 2.5b lights up the backend.
 */
export default function CouponList({
  appliedCode,
  disabled,
  onApply,
  variant = "cart",
}: CouponListProps) {
  if (OFFERS.length === 0) return null;

  const wrapperPadding = variant === "cart" ? "p-4" : "p-3";

  return (
    <div className={`bg-white border border-border ${wrapperPadding}`}>
      <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-2 flex items-center gap-1.5">
        <Tag className="w-3 h-3" /> Available Coupons
      </p>
      <ul className="space-y-2">
        {OFFERS.map((offer) => {
          const isApplied = appliedCode === offer.code;
          return (
            <li
              key={offer.id}
              className={
                isApplied
                  ? "border border-primary bg-primary/5 p-3"
                  : "border border-border p-3 hover:border-primary transition-colors"
              }
            >
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2 flex-wrap">
                    <p className="text-xs font-black uppercase tracking-tighter text-neutral-900">
                      {offer.code}
                    </p>
                    {offer.badge && (
                      <span className="bg-primary text-white text-[8px] font-bold uppercase tracking-widest px-1.5 py-0.5">
                        {offer.badge}
                      </span>
                    )}
                  </div>
                  <p className="text-[11px] text-neutral-600 leading-relaxed mt-1">
                    {offer.description}
                  </p>
                  {(offer.minOrder || offer.validUntil) && (
                    <p className="text-[10px] text-neutral-400 mt-1">
                      {offer.minOrder ? `Min order ₹${offer.minOrder}` : ""}
                      {offer.minOrder && offer.validUntil ? " · " : ""}
                      {offer.validUntil ? `Valid till ${offer.validUntil}` : ""}
                    </p>
                  )}
                </div>
                {isApplied ? (
                  <span className="text-[10px] font-black uppercase tracking-widest text-primary shrink-0">
                    Applied
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() => onApply(offer.code)}
                    disabled={disabled}
                    className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Apply
                  </button>
                )}
              </div>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
