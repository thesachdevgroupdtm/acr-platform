import { useState, type FormEvent } from "react";
import { Tag, X, AlertCircle, CheckCircle2 } from "lucide-react";
import { useCart } from "../hooks/useCart";
import type { CartTotals } from "../types/api";
import CouponList from "./CouponList";

interface CouponInputProps {
  totals: CartTotals | undefined;
  /** Visual variant — `cart` for the standalone Cart page panel,
   *  `summary` for the slim Checkout order-summary slot. */
  variant?: "cart" | "summary";
}

/**
 * Phase 2.5.1 + 2.5.2 — manual coupon entry with pre-defined cards.
 *
 * Three visual blocks, in order:
 *   1. Manual code input + Apply button (always visible when no
 *      coupon applied).
 *   2. Pre-defined cards via <CouponList> — user clicks Apply on a
 *      card OR types into the input above. Both routes call the
 *      same `applyCoupon` mutation.
 *   3. Applied state — when cart.totals.coupon is non-null, the
 *      header shows the code + discount + Remove button.
 *
 * The /cart/coupon backend is 501 until Phase 2.5b. Both Apply
 * paths return a friendly "coupon system launching soon" message
 * and the UI stays in the "no coupon applied" state. When 2.5b
 * lands, the only required change is `useCart.applyCoupon` calling
 * the real endpoint — this component already reads server state
 * and renders the applied path correctly.
 */
export default function CouponInput({ totals, variant = "cart" }: CouponInputProps) {
  const { applyCoupon, removeCoupon } = useCart();
  const [code, setCode] = useState("");
  const [busy, setBusy] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [infoMessage, setInfoMessage] = useState<string | null>(null);

  const applied = totals?.coupon ?? null;

  const tryApply = async (rawCode: string) => {
    setErrorMessage(null);
    setInfoMessage(null);
    const trimmed = rawCode.trim().toUpperCase();
    if (!trimmed) {
      setErrorMessage("Enter a coupon code.");
      return;
    }
    setBusy(true);
    try {
      const res = await applyCoupon(trimmed);
      if (!res.success) {
        // 2.5.2 — the stub always lands here. Surface as an info
        // message (not an error) so the tone stays friendly.
        setInfoMessage(res.error);
      } else {
        // Real-backend success path (Phase 2.5b). Clear the input
        // since the applied state takes over below.
        setCode("");
      }
    } finally {
      setBusy(false);
    }
  };

  const onSubmitInput = async (e: FormEvent) => {
    e.preventDefault();
    await tryApply(code);
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
  // real backend. The Remove button reverts to the input + cards.
  if (applied) {
    return (
      <div className="space-y-3">
        <div
          className={
            variant === "cart"
              ? "bg-primary/5 border border-primary/30 p-4"
              : "bg-primary/5 border border-primary/30 p-3"
          }
        >
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

        <CouponList
          appliedCode={applied.code}
          disabled={busy}
          onApply={tryApply}
          variant={variant}
        />
      </div>
    );
  }

  // Empty state — input + Apply button + pre-defined cards.
  const inputWrapper =
    variant === "cart"
      ? "bg-neutral-50 border border-border p-4"
      : "bg-neutral-50 border border-border p-3";

  return (
    <div className="space-y-3">
      <form onSubmit={onSubmitInput} className={inputWrapper}>
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
            className="bg-neutral-900 text-white px-4 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-primary transition-colors disabled:opacity-60"
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

      <CouponList
        appliedCode={null}
        disabled={busy}
        onApply={tryApply}
        variant={variant}
      />
    </div>
  );
}
