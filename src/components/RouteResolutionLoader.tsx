/**
 * Phase 2.5.1 — render gate placeholder shown while App.tsx
 * resolves the initial currentPage from window.location.
 *
 * Workaround until Phase 3 router migration. The string-based
 * pseudo-routing in App.tsx initialises `currentPage='home'`,
 * which would briefly flash the Home page on a hard-refresh of
 * /checkout, /order-{id}, etc. Holding render until the URL is
 * parsed (one tick) eliminates that flash.
 *
 * Visual contract: a centered subdued spinner. The Header / Footer
 * shell is intentionally NOT rendered here — the gate is so short
 * that any layout chrome would itself flash. Empty layout for the
 * sub-100ms case is deliberate.
 */
export default function RouteResolutionLoader() {
  return (
    <div
      className="min-h-screen bg-white flex items-center justify-center"
      aria-busy="true"
      aria-label="Loading"
    >
      <div className="flex flex-col items-center gap-3">
        <div className="w-10 h-10 border-2 border-neutral-200 border-t-primary rounded-full animate-spin" />
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
          Loading
        </p>
      </div>
    </div>
  );
}
