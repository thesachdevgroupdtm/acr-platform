import type * as React from "react";
import { useState, useEffect, type FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { X, Tag, AlertCircle, CheckCircle2, Sparkles } from "lucide-react";
import { useCoupons } from "../hooks/useCoupons";
import type { CouponResource } from "../types/api";

interface CouponPickerModalProps {
  open: boolean;
  onClose: () => void;
  /** Current applied coupon code (from cart.totals.coupon.code). */
  appliedCode: string | null;
  /**
   * Apply handler — caller wires to useCart.applyCoupon. Returns
   * `{ success: true }` to signal close-on-success, or
   * `{ success: false, error }` to surface the message inline.
   */
  onApply: (code: string) => Promise<{ success: true } | { success: false; error: string }>;
}

/**
 * Phase 2.5b (D-2.5b-2) — modal-based coupon picker.
 *
 * Renders the public coupon list with per-coupon eligibility flags
 * (?context=cart) so the user can see exactly which codes apply
 * and why others don't. Two entry paths share a single onApply:
 *
 *   1. Manual code input at the top (free-form code).
 *   2. "Apply" button on each eligible card.
 *
 * On success, the modal auto-closes; the parent's mutation has
 * already updated React Query, so the Cart/Checkout panel snaps
 * to the applied state in the same render.
 *
 * Visual contract: matches AuthModal / VehicleReplaceModal /
 * CancelOrderModal — fixed-overlay with neutral-900/95 backdrop,
 * white card, X close in top-right.
 */
export default function CouponPickerModal({
  open,
  onClose,
  appliedCode,
  onApply,
}: CouponPickerModalProps) {
  const { coupons, isLoading, refetch } = useCoupons("cart");
  const [code, setCode] = useState("");
  const [busyCode, setBusyCode] = useState<string | null>(null);
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [perCardError, setPerCardError] = useState<Record<string, string>>({});

  // Reset state and refetch eligibility every time the modal opens.
  useEffect(() => {
    if (open) {
      setCode("");
      setBusyCode(null);
      setGeneralError(null);
      setPerCardError({});
      refetch();
    }
  }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

  const tryApply = async (rawCode: string, scope: "input" | string) => {
    const trimmed = rawCode.trim().toUpperCase();
    if (!trimmed) {
      setGeneralError("Enter a coupon code.");
      return;
    }
    setBusyCode(scope === "input" ? "__input__" : trimmed);
    setGeneralError(null);
    setPerCardError({});
    try {
      const res = await onApply(trimmed);
      if (res.success === true) {
        onClose();
      } else {
        const errMsg = res.error;
        if (scope === "input") {
          setGeneralError(errMsg);
        } else {
          setPerCardError({ [trimmed]: errMsg });
        }
      }
    } finally {
      setBusyCode(null);
    }
  };

  const onSubmitInput = (e: FormEvent) => {
    e.preventDefault();
    void tryApply(code, "input");
  };

  return (
    <AnimatePresence>
      {open && (
        <div
          key="coupon-picker-modal"
          className="fixed inset-0 z-[10000] flex items-center justify-center p-3 sm:p-5"
        >
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={busyCode ? undefined : onClose}
            className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
          />
          <motion.div
            initial={{ opacity: 0, y: 30, scale: 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 30, scale: 0.96 }}
            transition={{ duration: 0.25, ease: "easeOut" }}
            className="relative w-full max-w-lg bg-white border border-border shadow-2xl flex flex-col max-h-[90vh]"
          >
            {/* Header */}
            <div className="px-6 py-4 border-b border-border flex items-center justify-between shrink-0">
              <div className="flex items-center gap-2">
                <Sparkles className="w-5 h-5 text-primary" />
                <h2 className="text-lg font-black uppercase tracking-tighter text-neutral-900">
                  Available <span className="text-primary">Coupons.</span>
                </h2>
              </div>
              <button
                onClick={onClose}
                disabled={!!busyCode}
                aria-label="Close"
                className="p-1.5 text-neutral-500 hover:text-neutral-900 disabled:opacity-50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Manual code entry */}
            <form
              onSubmit={onSubmitInput}
              className="px-6 py-4 border-b border-border shrink-0 bg-neutral-50"
            >
              <label className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-1.5 flex items-center gap-1.5">
                <Tag className="w-3 h-3" /> Have a different code?
              </label>
              <div className="flex gap-2">
                <input
                  type="text"
                  value={code}
                  onChange={(e) => {
                    setCode(e.target.value.toUpperCase());
                    if (generalError) setGeneralError(null);
                  }}
                  placeholder="ENTER CODE"
                  disabled={!!busyCode}
                  className={`flex-1 bg-white border ${
                    generalError ? "border-accent-dark" : "border-border"
                  } px-3 py-2 text-sm uppercase tracking-widest font-bold focus:border-primary outline-none disabled:bg-neutral-50`}
                />
                <button
                  type="submit"
                  disabled={!!busyCode}
                  className="bg-neutral-900 text-white px-5 py-2 text-[10px] font-black uppercase tracking-widest hover:bg-primary transition-colors disabled:opacity-60"
                >
                  {busyCode === "__input__" ? "…" : "Apply"}
                </button>
              </div>
              {generalError && (
                <p className="text-[10px] font-bold text-accent-dark mt-1.5 flex items-center gap-1">
                  <AlertCircle className="w-3 h-3" /> {generalError}
                </p>
              )}
            </form>

            {/* Coupon list */}
            <div className="flex-1 overflow-y-auto p-4 space-y-3">
              {isLoading ? (
                <div className="text-center py-12 text-sm text-neutral-500">
                  Loading coupons…
                </div>
              ) : coupons.length === 0 ? (
                <div className="text-center py-12 text-sm text-neutral-500">
                  No coupons available right now.
                </div>
              ) : (
                coupons.map((c) => (
                  <CouponCard
                    key={c.id}
                    coupon={c}
                    appliedCode={appliedCode}
                    busy={busyCode === c.code}
                    error={perCardError[c.code]}
                    onApply={() => void tryApply(c.code, c.code)}
                  />
                ))
              )}
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

interface CouponCardProps {
  coupon: CouponResource;
  appliedCode: string | null;
  busy: boolean;
  error?: string;
  onApply: () => void;
}

const CouponCard: React.FC<CouponCardProps> = ({
  coupon,
  appliedCode,
  busy,
  error,
  onApply,
}) => {
  const isApplied = appliedCode === coupon.code;
  const eligible = coupon.eligible !== false; // marketing context omits the flag
  const dim = !eligible && !isApplied;

  const conditions: string[] = [];
  if (coupon.min_order_value > 0) {
    conditions.push(`Min order ₹${coupon.min_order_value}`);
  }
  if (coupon.max_discount !== null) {
    conditions.push(`Max ₹${coupon.max_discount} off`);
  }
  if (coupon.expiry_date) {
    conditions.push(`Valid till ${coupon.expiry_date}`);
  }

  return (
    <div
      className={`border p-4 transition-colors ${
        isApplied
          ? "border-primary bg-primary/5"
          : dim
          ? "border-border bg-neutral-50 opacity-70"
          : "border-border hover:border-primary"
      }`}
    >
      <div className="flex items-start justify-between gap-3 mb-1">
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2 flex-wrap">
            <p className="text-sm font-black uppercase tracking-tighter text-neutral-900">
              {coupon.code}
            </p>
            {coupon.badge && (
              <span className="bg-primary text-white text-[8px] font-bold uppercase tracking-widest px-1.5 py-0.5">
                {coupon.badge}
              </span>
            )}
          </div>
          <p className="text-xs font-bold text-neutral-700 mt-0.5">{coupon.name}</p>
        </div>
        {isApplied ? (
          <span className="text-[10px] font-black uppercase tracking-widest text-primary inline-flex items-center gap-1 shrink-0">
            <CheckCircle2 className="w-3.5 h-3.5" /> Applied
          </span>
        ) : (
          <button
            type="button"
            onClick={onApply}
            disabled={busy || !eligible}
            className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
          >
            {busy ? "Applying…" : "Apply"}
          </button>
        )}
      </div>
      <p className="text-[11px] text-neutral-500 leading-relaxed mb-2">
        {coupon.description}
      </p>
      {conditions.length > 0 && (
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
          {conditions.join(" · ")}
        </p>
      )}
      {!eligible && coupon.ineligible_reason && (
        <p className="text-[10px] font-bold text-accent-dark mt-2 flex items-center gap-1">
          <AlertCircle className="w-3 h-3" /> {coupon.ineligible_reason}
        </p>
      )}
      {error && (
        <p className="text-[10px] font-bold text-accent-dark mt-2 flex items-center gap-1">
          <AlertCircle className="w-3 h-3" /> {error}
        </p>
      )}
    </div>
  );
};
