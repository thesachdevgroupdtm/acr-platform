import { useState, type FormEvent } from "react";
import { Tag, X, AlertCircle, CheckCircle2 } from "lucide-react";
import { useCart } from "../hooks/useCart";
import type { CartTotals } from "../types/api";

interface CouponInputProps {
  totals: CartTotals | undefined;
  /** Visual variant — `cart` for the standalone Cart page panel,
   *  `summary` for the slim Checkout order-summary slot. */
  variant?: "cart" | "summary";
}

/**
 * Phase 2.5.1 (D-2.5.1-5) — themed coupon input that replaces the
 * pre-existing auto-apply path.
 *
 * Two visual states:
 *  - No coupon on server cart: renders an input + Apply button.
 *  - Coupon present on server cart: renders the applied state with
 *    the discount amount and a Remove (×) button.
 *
 * Apply / Remove call into useCart (which currently returns a
 * "coming soon" message — Phase 2.5b lights up the real backend).
 * The user-visible UI flow is the wired-final shape so that 2.5b
 * lands as a backend-only change.
 */
export default function CouponInput({ totals, variant = "cart" }: CouponInputProps) {
  const { applyCoupon, removeCoupon } = useCart();
  const [code, setCode] = useState("");
  const [busy, setBusy] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [infoMessage, setInfoMessage] = useState<string | null>(null);

  const applied = totals?.coupon ?? null;

  const onApply = async (e: FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setInfoMessage(null);
    const trimmed = code.trim().toUpperCase();
    if (!trimmed) {
      setErrorMessage("Enter a coupon code.");
      return;
    }
    setBusy(true);
    try {
      const res = await applyCoupon(trimmed);
      if (!res.success) {
        // 2.5.1 — the stub always lands here. Surface as an info
        // message (not an error) so the tone stays friendly.
        setInfoMessage(res.error);
      }
    } finally {
      setBusy(false);
    }
  };

  const onRemove = async () => {
    setErrorMessage(null);
    setInfoMessage(null);
    setBusy(true);
    try {
      await removeCoupon();
    } finally {
      setBusy(false);
    }
  };

  // Applied state — only reachable once Phase 2.5b lights up the
  // real backend. Kept here so the surface is correct day-1 of 2.5b.
  if (applied) {
    return (
      <div className={
        variant === "cart"
          ? "bg-primary/5 border border-primary/30 p-4"
          : "bg-primary/5 border border-primary/30 p-3"
      }>
        <div className="flex items-start gap-3">
          <CheckCircle2 className="w-5 h-5 text-primary shrink-0 mt-0.5" />
          <div className="flex-1 min-w-0">
            <div className="flex items-center justify-between gap-2 flex-wrap">
              <p className="text-xs font-black uppercase text-neutral-900 tracking-tighter">
                {applied.code}
              </p>
              <p className="text-xs font-bold text-primary">
                − ₹{totals?.discount ?? 0}
              </p>
            </div>
            <button
              onClick={onRemove}
              disabled={busy}
              className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 hover:text-accent-dark mt-1 inline-flex items-center gap-1 disabled:opacity-50"
            >
              <X className="w-3 h-3" /> Remove
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Empty state — input + Apply button.
  const wrapper =
    variant === "cart"
      ? "bg-neutral-50 border border-border p-4"
      : "bg-neutral-50 border border-border p-3";

  return (
    <form onSubmit={onApply} className={wrapper}>
      <label className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-1.5 flex items-center gap-1.5">
        <Tag className="w-3 h-3" /> Have a coupon?
      </label>
      <div className="flex gap-2">
        <input
          type="text"
          value={code}
          onChange={(e) => {
            setCode(e.target.value.toUpperCase());
            if (errorMessage) setErrorMessage(null);
            if (infoMessage) setInfoMessage(null);
          }}
          placeholder="ENTER CODE"
          disabled={busy}
          className={`flex-1 bg-white border ${
            errorMessage ? "border-accent-dark" : "border-border"
          } px-3 py-2 text-sm uppercase tracking-widest font-bold focus:border-primary outline-none disabled:bg-neutral-50`}
        />
        <button
          type="submit"
          disabled={busy}
          className="bg-neutral-900 text-white px-4 text-[10px] font-bold uppercase tracking-widest hover:bg-primary transition-colors disabled:opacity-60"
        >
          {busy ? "…" : "Apply"}
        </button>
      </div>
      {errorMessage && (
        <p className="text-[10px] font-bold text-accent-dark mt-1.5 flex items-center gap-1">
          <AlertCircle className="w-3 h-3" /> {errorMessage}
        </p>
      )}
      {infoMessage && (
        <p className="text-[11px] text-neutral-600 leading-relaxed mt-1.5">
          {infoMessage}
        </p>
      )}
    </form>
  );
}
