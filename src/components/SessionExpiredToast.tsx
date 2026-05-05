import { useEffect, useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { AlertCircle, X } from "lucide-react";

/**
 * Phase 2.6a (D-2.6a-6) — global session-expired toast.
 *
 * Listens for the `acr-session-expired` CustomEvent dispatched by
 * `src/lib/api.ts` whenever a 401 lands. Renders a single toast in
 * the bottom-right corner that auto-dismisses after 6s. Cheaper
 * than a full toast framework — we only have this one notification
 * channel today; can grow into a stack later when more events appear.
 */
const VISIBLE_MS = 6000;

export default function SessionExpiredToast() {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const handle = () => {
      setOpen(true);
      const t = window.setTimeout(() => setOpen(false), VISIBLE_MS);
      return () => window.clearTimeout(t);
    };
    window.addEventListener("acr-session-expired", handle);
    return () => window.removeEventListener("acr-session-expired", handle);
  }, []);

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          initial={{ opacity: 0, y: 16, scale: 0.96 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={{ opacity: 0, y: 16, scale: 0.96 }}
          transition={{ duration: 0.2, ease: "easeOut" }}
          className="fixed bottom-5 right-5 z-[10001] max-w-sm bg-neutral-900 text-white border border-neutral-800 shadow-2xl"
          role="alert"
        >
          <div className="flex items-start gap-3 px-4 py-3.5">
            <div className="flex-shrink-0 w-8 h-8 bg-accent-dark/20 text-accent-dark flex items-center justify-center">
              <AlertCircle className="w-4 h-4" />
            </div>
            <div className="flex-1 pt-0.5">
              <p className="text-[11px] font-black uppercase tracking-widest text-accent-dark mb-0.5">
                Session expired
              </p>
              <p className="text-xs text-neutral-300 leading-relaxed">
                Please sign in again to continue.
              </p>
            </div>
            <button
              onClick={() => setOpen(false)}
              aria-label="Dismiss"
              className="flex-shrink-0 p-1 text-neutral-500 hover:text-white"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
