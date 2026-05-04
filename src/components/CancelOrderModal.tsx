import { useEffect, useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { X, AlertCircle, AlertTriangle } from "lucide-react";

interface CancelOrderModalProps {
  open: boolean;
  orderNumber: string;
  onConfirm: (reason: string | null) => void;
  onClose: () => void;
  pending?: boolean;
  /** Optional error message returned by the cancel mutation. */
  errorMessage?: string | null;
}

const REASON_MAX = 255;

/**
 * Phase 2.5.1 (D-2.5.1-4) — themed cancel-booking confirmation.
 * Replaces the native window.confirm() + window.prompt() pair that
 * shipped with Phase 2.5a's MyBookings + OrderDetail. The modal:
 *   - Has a danger-toned primary button.
 *   - Carries an optional reason textarea (max 255 chars, server cap).
 *   - Does NOT submit on Enter — only the explicit button click.
 *   - Resets reason when reopened (defensive against stale text from
 *     a previous open).
 */
export default function CancelOrderModal({
  open,
  orderNumber,
  onConfirm,
  onClose,
  pending,
  errorMessage,
}: CancelOrderModalProps) {
  const [reason, setReason] = useState("");

  useEffect(() => {
    if (open) setReason("");
  }, [open]);

  const handleSubmit = () => {
    onConfirm(reason.trim() ? reason.trim() : null);
  };

  return (
    <AnimatePresence>
      {open && (
        <div
          key="cancel-order-modal"
          className="fixed inset-0 z-[10000] flex items-center justify-center p-3 sm:p-5"
        >
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={pending ? undefined : onClose}
            className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
          />
          <motion.div
            initial={{ opacity: 0, y: 30, scale: 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 30, scale: 0.96 }}
            transition={{ duration: 0.25, ease: "easeOut" }}
            className="relative w-full max-w-md bg-white border border-border shadow-2xl"
          >
            <div className="px-7 sm:px-9 pt-7 sm:pt-9 pb-4">
              <button
                onClick={onClose}
                disabled={pending}
                aria-label="Close"
                className="absolute top-4 right-4 p-2 text-neutral-500 hover:text-neutral-900 disabled:opacity-50"
              >
                <X className="w-5 h-5" />
              </button>

              <div className="w-12 h-12 bg-accent-dark/10 text-accent-dark flex items-center justify-center mb-4">
                <AlertTriangle className="w-6 h-6" />
              </div>

              <h2 className="text-xl sm:text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-2">
                Cancel <span className="text-primary">Booking?</span>
              </h2>

              <p className="text-sm text-neutral-600 leading-relaxed mb-4">
                You are about to cancel booking{" "}
                <strong className="text-neutral-900">{orderNumber}</strong>.
                This action cannot be undone.
              </p>

              <label className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-1.5 block">
                Reason (optional)
              </label>
              <textarea
                value={reason}
                onChange={(e) => setReason(e.target.value.slice(0, REASON_MAX))}
                placeholder="Why are you cancelling? Helps us improve."
                disabled={pending}
                maxLength={REASON_MAX}
                className="w-full bg-white border border-border p-3 text-sm focus:border-primary outline-none min-h-[80px] disabled:bg-neutral-50"
              />
              <p className="text-[10px] text-neutral-400 mt-1 text-right">
                {reason.length}/{REASON_MAX}
              </p>

              {errorMessage && (
                <p className="text-[11px] font-bold text-accent-dark flex items-center gap-1.5 mt-2">
                  <AlertCircle className="w-3.5 h-3.5" /> {errorMessage}
                </p>
              )}
            </div>

            <div className="px-7 sm:px-9 py-4 border-t border-border flex flex-col sm:flex-row-reverse gap-2 sm:gap-3">
              <button
                onClick={handleSubmit}
                disabled={pending}
                className="flex-1 py-3.5 text-xs font-black uppercase tracking-widest bg-accent-dark text-white hover:bg-accent-dark/90 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {pending ? "Cancelling…" : "Confirm Cancellation"}
              </button>
              <button
                onClick={onClose}
                disabled={pending}
                className="flex-1 py-3.5 text-xs font-black uppercase tracking-widest bg-white border border-border text-neutral-700 hover:border-primary hover:text-primary transition-colors disabled:opacity-50"
              >
                Keep Booking
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
