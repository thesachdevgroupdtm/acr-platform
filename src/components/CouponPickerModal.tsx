import type * as React from "react";
import { useState, useEffect, type FormEvent } from "react";
import { createPortal } from "react-dom";
import { motion, AnimatePresence } from "motion/react";
import { ArrowLeft, AlertCircle, CheckCircle2, Tag } from "lucide-react";
import { useCoupons } from "../hooks/useCoupons";
import { useAuth } from "../hooks/useAuth";
import type { CouponResource } from "../types/api";
import ApiErrorState from "./ApiErrorState";
import EmptyState from "./EmptyState";

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
 * Coupon picker — side-slider variant (GoMechanic pattern).
 *
 * Slides in from the right over a dimmed overlay; full viewport
 * height; ~420px wide on desktop / full-width on mobile. Header has
 * a single back-arrow + "Apply Coupon" title; body has a manual-code
 * input row, then the "Available Offers" list with dashed-border
 * code chips and per-card APPLY actions.
 *
 * Portaled to <body> so parent stacking contexts (sticky sidebars,
 * sticky page header) can never crop the panel — that was the
 * cropping bug the operator reported.
 */
export default function CouponPickerModal({
  open,
  onClose,
  appliedCode,
  onApply,
}: CouponPickerModalProps) {
  const { coupons, isLoading, isError, refetch } = useCoupons("cart");
  const [code, setCode] = useState("");
  const [busyCode, setBusyCode] = useState<string | null>(null);
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [perCardError, setPerCardError] = useState<Record<string, string>>({});

  // Reset state + refetch every time the panel opens.
  useEffect(() => {
    if (open) {
      setCode("");
      setBusyCode(null);
      setGeneralError(null);
      setPerCardError({});
      refetch();
    }
  }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

  // Body-scroll lock + Esc-to-close while open.
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape" && !busyCode) onClose();
    };
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = prev;
      window.removeEventListener("keydown", onKey);
    };
  }, [open, onClose, busyCode]);

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
      } else if (scope === "input") {
        setGeneralError(res.error);
      } else {
        setPerCardError({ [trimmed]: res.error });
      }
    } finally {
      setBusyCode(null);
    }
  };

  const onSubmitInput = (e: FormEvent) => {
    e.preventDefault();
    void tryApply(code, "input");
  };

  if (typeof document === "undefined") return null;

  return createPortal(
    <AnimatePresence>
      {open && (
        <div
          key="coupon-picker-slider"
          className="fixed inset-0 z-[10000]"
          role="dialog"
          aria-modal="true"
          aria-label="Apply coupon"
        >
          {/* Dim overlay — left of the panel. Click to close. */}
          <motion.button
            type="button"
            aria-label="Close"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            onClick={busyCode ? undefined : onClose}
            className="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm cursor-default"
          />

          {/* Side panel — slides in from the right. */}
          <motion.aside
            initial={{ x: "100%" }}
            animate={{ x: 0 }}
            exit={{ x: "100%" }}
            transition={{ duration: 0.28, ease: [0.4, 0, 0.2, 1] }}
            className="absolute top-0 right-0 h-full w-full sm:w-[420px] bg-neutral-50 shadow-2xl flex flex-col border-l border-border"
          >
            {/* Header — sticky white strip with back-arrow + title. */}
            <div className="px-4 py-4 bg-white border-b border-border flex items-center gap-3 shrink-0">
              <button
                onClick={onClose}
                disabled={!!busyCode}
                aria-label="Close"
                className="w-8 h-8 flex items-center justify-center text-neutral-700 hover:text-primary disabled:opacity-50"
              >
                <ArrowLeft className="w-5 h-5" />
              </button>
              <h2 className="text-sm font-black uppercase tracking-tighter text-neutral-900">
                Apply Coupon
              </h2>
            </div>

            {/* Manual code entry */}
            <form
              onSubmit={onSubmitInput}
              className="px-4 py-3 bg-white border-b border-border shrink-0"
            >
              <div className="flex items-center gap-2 border border-border bg-white px-3 py-2.5 focus-within:border-primary transition-colors">
                <Tag className="w-3.5 h-3.5 text-neutral-400 shrink-0" />
                <input
                  type="text"
                  value={code}
                  onChange={(e) => {
                    setCode(e.target.value.toUpperCase());
                    if (generalError) setGeneralError(null);
                  }}
                  placeholder="ENTER COUPON"
                  disabled={!!busyCode}
                  className="flex-1 bg-transparent text-sm uppercase tracking-widest font-bold outline-none placeholder:text-neutral-400 disabled:opacity-60"
                />
                <button
                  type="submit"
                  disabled={!!busyCode || code.trim() === ""}
                  className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                >
                  {busyCode === "__input__" ? "…" : "Apply"}
                </button>
              </div>
              {generalError && (
                <p className="text-[10px] font-bold text-accent-dark mt-2 flex items-center gap-1">
                  <AlertCircle className="w-3 h-3" /> {generalError}
                </p>
              )}
            </form>

            {/* Available Offers */}
            <div className="flex-1 overflow-y-auto">
              <p className="px-4 pt-4 pb-2 text-[11px] font-black uppercase tracking-widest text-neutral-500">
                Available Offers
              </p>
              <div className="px-4 pb-6 space-y-3">
                {isLoading ? (
                  <div className="text-center py-12 text-sm text-neutral-500">
                    Loading coupons…
                  </div>
                ) : isError ? (
                  <ApiErrorState
                    message="Couldn't load coupons."
                    detail="Check your connection and retry."
                    onRetry={refetch}
                    data-testid="coupon-picker-error"
                  />
                ) : coupons.length === 0 ? (
                  <EmptyState
                    title="No coupons available"
                    hint="Check back soon — new offers drop regularly."
                  />
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
            </div>
          </motion.aside>
        </div>
      )}
    </AnimatePresence>,
    document.body,
  );
}

interface CouponCardProps {
  coupon: CouponResource;
  appliedCode: string | null;
  busy: boolean;
  error?: string;
  onApply: () => void;
}

/**
 * Side-slider card variant. White card, offer copy on top, dashed-
 * border code chip + APPLY text-button on the bottom row — the
 * GoMechanic tear-off ticket pattern adapted to ACR's sharp surfaces.
 */
const CouponCard: React.FC<CouponCardProps> = ({
  coupon,
  appliedCode,
  busy,
  error,
  onApply,
}) => {
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

  const conditions: string[] = [];
  if (coupon.min_order_value > 0)  conditions.push(`Min order ₹${coupon.min_order_value}`);
  if (coupon.max_discount !== null) conditions.push(`Max ₹${coupon.max_discount} off`);
  if (coupon.expiry_date)           conditions.push(`Valid till ${coupon.expiry_date}`);

  return (
    <div
      className={`bg-white border p-4 ${
        isApplied
          ? "border-primary"
          : dim
          ? "border-border opacity-60"
          : "border-border hover:border-primary transition-colors"
      }`}
    >
      {/* Brand mark + offer name */}
      <div className="flex items-start gap-2 mb-1">
        <div className="w-7 h-7 bg-primary/10 text-primary flex items-center justify-center shrink-0">
          <Tag className="w-3.5 h-3.5" />
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2 flex-wrap">
            <p className="text-sm font-black uppercase tracking-tighter text-neutral-900">
              {coupon.name}
            </p>
            {coupon.badge && (
              <span className="bg-primary text-white text-[8px] font-bold uppercase tracking-widest px-1.5 py-0.5">
                {coupon.badge}
              </span>
            )}
          </div>
        </div>
      </div>

      {coupon.description && (
        <p className="text-xs text-neutral-600 leading-relaxed mb-3">
          {coupon.description}
        </p>
      )}

      {conditions.length > 0 && (
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-3">
          {conditions.join(" · ")}
        </p>
      )}

      <div className="border-t border-dashed border-border pt-3 flex items-center justify-between gap-3">
        {/* Dashed-border tear-off code chip */}
        <span className="border border-dashed border-primary/50 text-primary px-3 py-1.5 text-xs font-black uppercase tracking-widest">
          {coupon.code}
        </span>

        {isApplied ? (
          <span className="text-[10px] font-black uppercase tracking-widest text-primary inline-flex items-center gap-1">
            <CheckCircle2 className="w-3.5 h-3.5" /> Applied
          </span>
        ) : (
          <button
            type="button"
            onClick={onApply}
            disabled={busy || !eligible}
            className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
          >
            {busy ? "Applying…" : "Apply"}
          </button>
        )}
      </div>

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
