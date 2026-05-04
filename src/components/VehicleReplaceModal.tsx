import { motion, AnimatePresence } from "motion/react";
import { X, ArrowRight, AlertTriangle } from "lucide-react";
import { vehicleLabel, type VehicleConflictDetails } from "../lib/errors";

interface VehicleReplaceModalProps {
  open: boolean;
  details: VehicleConflictDetails | null;
  onConfirm: () => void;
  onClose: () => void;
  pending?: boolean;
}

/**
 * Phase 2.5.1 (D-2.5.1-1) — themed prompt for one-vehicle-per-cart
 * conflict. Triggered when a user adds a service for vehicle X to
 * a cart that already holds rows for vehicle Y. Confirm clears
 * the cart and replays the pending add via
 * `useCart.replaceVehicleInCart`.
 *
 * Visual contract matches AuthModal: full-viewport overlay, white
 * card, X close in top-right, primary + outline button row.
 */
export default function VehicleReplaceModal({
  open,
  details,
  onConfirm,
  onClose,
  pending,
}: VehicleReplaceModalProps) {
  return (
    <AnimatePresence>
      {open && details && (
        <div
          key="vehicle-replace-modal"
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
            className="relative w-full max-w-md bg-white border border-border shadow-2xl p-7 sm:p-9"
          >
            <button
              onClick={onClose}
              disabled={pending}
              aria-label="Close"
              className="absolute top-4 right-4 p-2 text-neutral-500 hover:text-neutral-900 disabled:opacity-50"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="w-12 h-12 bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
              <AlertTriangle className="w-6 h-6" />
            </div>

            <h2 className="text-xl sm:text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-2">
              Different Vehicle <span className="text-primary">in Cart.</span>
            </h2>

            <p className="text-sm text-neutral-600 leading-relaxed mb-6">
              Your cart has services for{" "}
              <strong className="text-neutral-900">
                {vehicleLabel(details.existingVehicle)}
              </strong>
              . To add this service we need to replace those with services
              for{" "}
              <strong className="text-neutral-900">
                {vehicleLabel(details.newVehicle)}
              </strong>
              . Continue?
            </p>

            <div className="flex flex-col sm:flex-row-reverse gap-2 sm:gap-3">
              <button
                onClick={onConfirm}
                disabled={pending}
                className="btn-ink btn-ink-primary flex-1 py-3.5 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {pending ? "Replacing…" : "Replace Cart"}
                {!pending && <ArrowRight className="w-4 h-4 btn-arrow" />}
              </button>
              <button
                onClick={onClose}
                disabled={pending}
                className="bg-white border border-border flex-1 py-3.5 text-xs font-black uppercase tracking-widest text-neutral-700 hover:border-primary hover:text-primary transition-colors disabled:opacity-50"
              >
                Keep Existing Cart
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
