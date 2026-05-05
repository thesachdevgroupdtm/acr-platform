import { motion, AnimatePresence } from "motion/react";
import { X, LogOut } from "lucide-react";

interface LogoutConfirmModalProps {
  open: boolean;
  onConfirm: () => void;
  onClose: () => void;
  pending?: boolean;
}

/**
 * Phase 2.6a (D-2.6a-5) — themed logout confirmation.
 * Replaces the native window.confirm("Log out of your account?")
 * that shipped in MyBookings. Same shell as CancelOrderModal so the
 * site's modal vocabulary stays consistent.
 */
export default function LogoutConfirmModal({
  open,
  onConfirm,
  onClose,
  pending,
}: LogoutConfirmModalProps) {
  return (
    <AnimatePresence>
      {open && (
        <div
          key="logout-confirm-modal"
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

              <div className="w-12 h-12 bg-primary/10 text-primary flex items-center justify-center mb-4">
                <LogOut className="w-6 h-6" />
              </div>

              <h2 className="text-xl sm:text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-2">
                Log out of <span className="text-primary">your account?</span>
              </h2>

              <p className="text-sm text-neutral-600 leading-relaxed">
                You'll need to sign in again to view your bookings and saved
                vehicle. Items in your cart will be kept on this device.
              </p>
            </div>

            <div className="px-7 sm:px-9 py-4 border-t border-border flex flex-col sm:flex-row-reverse gap-2 sm:gap-3">
              <button
                onClick={onConfirm}
                disabled={pending}
                className="flex-1 px-6 py-3.5 text-xs font-black uppercase tracking-widest bg-primary text-white hover:bg-primary-dark transition-colors disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap"
              >
                {pending ? "Logging out…" : "Log Out"}
              </button>
              <button
                onClick={onClose}
                disabled={pending}
                className="flex-1 px-6 py-3.5 text-xs font-black uppercase tracking-widest bg-white border border-border text-neutral-700 hover:border-primary hover:text-primary transition-colors disabled:opacity-50 whitespace-nowrap"
              >
                Stay Signed In
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
