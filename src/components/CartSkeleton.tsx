/**
 * Phase 2.6a-fix — Cart loading skeleton.
 *
 * Mirrors Cart.tsx's chrome (3 line-item rows on the left, order
 * summary card on the right) so a logged-in user with a populated
 * cart never sees the empty-state flash on hard refresh.
 *
 * Pulse blocks only — no real content. The grid layout matches the
 * `lg:grid-cols-3 gap-8` shape Cart.tsx uses for items + summary.
 */
export default function CartSkeleton() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-6 animate-pulse">
      {/* Items column (2 cols on lg) */}
      <div className="lg:col-span-2 space-y-4">
        {[0, 1, 2].map((i) => (
          <div
            key={i}
            className="bg-white border border-border p-4 sm:p-5 flex gap-4"
          >
            <div className="w-20 h-20 sm:w-24 sm:h-24 bg-neutral-200 flex-shrink-0" />
            <div className="flex-1 min-w-0 space-y-2">
              <div className="h-4 bg-neutral-200 w-3/4 max-w-[260px]" />
              <div className="h-3 bg-neutral-100 w-1/2 max-w-[180px]" />
              <div className="flex items-center justify-between pt-3">
                <div className="h-7 bg-neutral-200 w-24" />
                <div className="h-5 bg-neutral-200 w-16" />
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Summary column (1 col on lg, sticky-feeling card) */}
      <aside className="space-y-4">
        <div className="bg-white border border-border p-6 space-y-3">
          <div className="h-5 bg-neutral-200 w-32 mb-4" />
          <div className="h-4 bg-neutral-100 w-full" />
          <div className="h-4 bg-neutral-100 w-full" />
          <div className="h-px bg-border my-3" />
          <div className="h-6 bg-neutral-200 w-full" />
          <div className="h-12 bg-neutral-200 w-full mt-4" />
        </div>
        <div className="bg-white border border-border p-6 space-y-2">
          <div className="h-3 bg-neutral-100 w-24" />
          <div className="h-10 bg-neutral-200 w-full" />
        </div>
      </aside>
    </div>
  );
}
