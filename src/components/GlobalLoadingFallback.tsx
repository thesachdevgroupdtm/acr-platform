/**
 * Phase 2.6b — Suspense fallback for code-split route chunks.
 *
 * Shown only during the chunk-download window (typically <300ms on
 * a warm CDN, longer on a cold start or slow link). Once the chunk
 * resolves, the page-level skeleton (or the page itself) takes
 * over, so this component intentionally renders inside the
 * existing Header/Footer chrome — only the <main> body shows the
 * placeholder. That avoids a chrome-flash when navigating between
 * lazy routes.
 *
 * Visual contract matches RouteResolutionLoader (the Phase 2.5.1
 * gate that fires before this fallback): same primary-coloured
 * spinner, same "Loading" caption. The two loaders compose:
 *   1. Hard-refresh on /any-lazy-route → RouteResolutionLoader
 *      (URL parse) → GlobalLoadingFallback (chunk download) →
 *      page-level skeleton (data fetch) → page.
 *   2. Click-nav from another page → GlobalLoadingFallback
 *      (chunk download, if not cached) → page.
 *
 * The "Loading" caption is matched by the slow-chunk Playwright
 * test (tests/e2e/code-splitting.spec.ts), so the text is
 * load-bearing — do not remove without updating that test.
 */
export default function GlobalLoadingFallback() {
  return (
    <div
      className="min-h-[60vh] flex items-center justify-center"
      aria-busy="true"
      aria-label="Loading page"
      role="status"
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
