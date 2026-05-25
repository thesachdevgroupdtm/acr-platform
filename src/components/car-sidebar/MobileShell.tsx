import { useState, useEffect, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { AnimatePresence, motion } from "motion/react";
import { X, ArrowRight } from "lucide-react";

/**
 * CarSidebar mobile shell — fixed bottom bar + slide-up bottom sheet.
 * The sheet holds the SAME body the desktop card shows (vehicle header /
 * cart / summary, or the in-place VehicleSelector). Portaled to <body>;
 * Esc + backdrop dismiss; body scroll locked while open. lg:hidden.
 */
const inr = (n: number) => `₹${n.toLocaleString("en-IN")}`;

interface Props {
  itemCount: number;
  total: number;
  canCheckout: boolean;
  onCheckout: () => void;
  children: ReactNode;
}

export default function MobileShell({
  itemCount,
  total,
  canCheckout,
  onCheckout,
  children,
}: Props) {
  const [open, setOpen] = useState(false);
  const empty = itemCount === 0;

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setOpen(false);
    };
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = prev;
      window.removeEventListener("keydown", onKey);
    };
  }, [open]);

  return (
    <>
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-border shadow-2xl p-4 z-40 lg:hidden">
        <div className="flex items-center justify-between gap-3">
          <button
            type="button"
            onClick={() => setOpen(true)}
            className="flex-1 text-left min-h-12 flex flex-col justify-center"
            aria-label="Open booking summary"
          >
            <p className="text-[10px] uppercase tracking-widest font-bold text-neutral-500">
              {empty ? "Your booking" : `${itemCount} ${itemCount === 1 ? "service" : "services"}`}
            </p>
            <p className="font-black text-lg text-neutral-900 leading-tight tracking-tighter">
              {empty ? "Tap to start" : inr(total)}
            </p>
          </button>
          <button
            type="button"
            onClick={!empty && canCheckout ? onCheckout : () => setOpen(true)}
            className="btn-ink btn-ink-primary px-5 py-3 min-h-12 text-xs font-bold uppercase tracking-widest justify-center gap-1.5 whitespace-nowrap"
          >
            {!empty && canCheckout ? "Checkout" : "View"} <ArrowRight className="w-4 h-4 btn-arrow" />
          </button>
        </div>
      </div>

      {typeof document !== "undefined" &&
        createPortal(
          <AnimatePresence>
            {open && (
              <motion.div
                className="fixed inset-0 z-50 lg:hidden"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.2 }}
                role="dialog"
                aria-modal="true"
                aria-label="Your booking"
              >
                <button
                  type="button"
                  aria-label="Close booking summary"
                  onClick={() => setOpen(false)}
                  className="absolute inset-0 bg-black/50 w-full h-full cursor-default"
                />
                <motion.div
                  className="absolute bottom-0 left-0 right-0 bg-white max-h-[90vh] overflow-y-auto shadow-2xl border-t border-border"
                  initial={{ y: "100%" }}
                  animate={{ y: 0 }}
                  exit={{ y: "100%" }}
                  transition={{ duration: 0.25, ease: "easeOut" }}
                  onClick={(e) => e.stopPropagation()}
                >
                  <div className="flex items-center justify-between px-4 py-3 border-b border-border sticky top-0 bg-white z-10">
                    <h3 className="font-black uppercase tracking-tighter text-base text-neutral-900">
                      Your Booking
                    </h3>
                    <button
                      type="button"
                      onClick={() => setOpen(false)}
                      aria-label="Close"
                      className="w-9 h-9 flex items-center justify-center text-neutral-500 hover:text-primary transition-colors"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  </div>
                  <div className="pb-24">{children}</div>
                </motion.div>
              </motion.div>
            )}
          </AnimatePresence>,
          document.body,
        )}
    </>
  );
}
