/**
 * Phase 4.5    — Suspense fallback for /explore.
 * Phase 4.5.7  — Updated to mirror the operator's hand-drawn
 *                final blueprint section structure exactly.
 *
 * Per D-4.5-10: there is NO old/new transition. /explore loads
 * straight into ExploreEditorial; this is the only fallback.
 */

export default function ExploreSkeleton() {
  return (
    <div data-testid="explore-skeleton">
      {/* PageBanner */}
      <div className="relative h-[40vh] min-h-[300px] bg-neutral-800 mb-12 overflow-hidden">
        <div className="site-container h-full flex flex-col justify-center">
          <div className="h-3 w-40 bg-neutral-700 animate-pulse mb-6" />
          <div className="h-10 md:h-14 w-64 bg-neutral-700 animate-pulse" />
        </div>
      </div>

      {/* Phase 4.5.8 — Featured grid placeholder removed alongside
          the ExploreFeaturedGrid usage in ExploreEditorial. Trending
          Now (below) is now the single editorial mosaic. */}

      {/* Trending Now — full-width 5-card mosaic with LARGE center */}
      <section className="bg-neutral-50 py-12 md:py-16">
        <div className="site-container">
          <div className="h-8 w-48 bg-neutral-200 animate-pulse mb-6" />
          <div className="grid grid-cols-1 gap-4 lg:gap-6 lg:grid-cols-12 lg:grid-rows-2 lg:auto-rows-[minmax(160px,1fr)]">
            <div className="order-1 lg:order-none lg:col-start-4 lg:col-end-10 lg:row-start-1 lg:row-end-3 aspect-[4/3] lg:aspect-auto bg-neutral-200 animate-pulse" />
            <div className="order-2 lg:order-none lg:col-start-1 lg:col-end-4 lg:row-start-1 lg:row-end-2 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
            <div className="order-3 lg:order-none lg:col-start-10 lg:col-end-13 lg:row-start-1 lg:row-end-2 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
            <div className="order-4 lg:order-none lg:col-start-1 lg:col-end-4 lg:row-start-2 lg:row-end-3 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
            <div className="order-5 lg:order-none lg:col-start-10 lg:col-end-13 lg:row-start-2 lg:row-end-3 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
          </div>
        </div>
      </section>

      {/* Search bar */}
      <section className="bg-white border-b border-border">
        <div className="site-container py-4">
          <div className="h-12 w-full max-w-[600px] mx-auto bg-neutral-100 animate-pulse" />
        </div>
      </section>

      {/* Container 1 placeholder — Brand Service + City Service main; aside */}
      <section className="bg-white py-12">
        <div className="site-container">
          <div className="grid grid-cols-12 gap-8">
            <div className="col-span-12 lg:col-span-8 space-y-12">
              {/* Brand Service — 1 LARGE-stacked + 3 SMALL right */}
              <div>
                <div className="h-7 w-40 bg-neutral-200 animate-pulse mb-6" />
                <div className="grid grid-cols-1 gap-4 md:gap-6 lg:grid-cols-12 lg:grid-rows-3 lg:auto-rows-[minmax(140px,1fr)]">
                  <div className="lg:col-start-1 lg:col-end-8 lg:row-start-1 lg:row-end-4 bg-white border border-border overflow-hidden">
                    <div className="aspect-[16/10] bg-neutral-200 animate-pulse" />
                    <div className="p-5 lg:p-6 space-y-3">
                      <div className="h-6 w-3/4 bg-neutral-200 animate-pulse" />
                      <div className="h-3 w-full bg-neutral-100 animate-pulse" />
                      <div className="h-3 w-5/6 bg-neutral-100 animate-pulse" />
                    </div>
                  </div>
                  <div className="lg:col-start-8 lg:col-end-13 lg:row-start-1 lg:row-end-2 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
                  <div className="lg:col-start-8 lg:col-end-13 lg:row-start-2 lg:row-end-3 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
                  <div className="lg:col-start-8 lg:col-end-13 lg:row-start-3 lg:row-end-4 aspect-[16/9] lg:aspect-auto bg-neutral-200 animate-pulse" />
                </div>
              </div>

              {/* City Service — 4×2 equal-card grid */}
              <div>
                <div className="h-7 w-40 bg-neutral-200 animate-pulse mb-6" />
                <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                  {[0, 1, 2, 3, 4, 5, 6, 7].map((i) => (
                    <div key={i} className="bg-white border border-border overflow-hidden">
                      <div className="aspect-[16/10] bg-neutral-200 animate-pulse" />
                      <div className="p-3 space-y-2">
                        <div className="h-3 w-4/5 bg-neutral-200 animate-pulse" />
                        <div className="h-3 w-3/5 bg-neutral-100 animate-pulse" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="hidden lg:block col-span-4 space-y-6">
              <div className="h-72 bg-neutral-100 animate-pulse" />
              <div className="h-64 bg-neutral-100 animate-pulse" />
            </div>
          </div>
        </div>
      </section>

      {/* Rail */}
      <section className="bg-neutral-50 py-12">
        <div className="site-container">
          <div className="h-7 w-48 bg-neutral-200 animate-pulse mb-6" />
          <div className="flex gap-4 overflow-hidden">
            {[0, 1, 2, 3, 4].map((i) => (
              <div
                key={i}
                className="w-[280px] flex-shrink-0 aspect-[16/9] bg-neutral-200 animate-pulse"
              />
            ))}
          </div>
        </div>
      </section>

      {/* Phase 4.5.10 — Big Grid Dual placeholder (2 sub-sections
          side-by-side; left = featured + 4 thumb rows; right =
          featured + 2x2 image grid). Lives inside Container 2 in
          the real page; here we approximate the silhouette. */}
      <section className="bg-white py-12">
        <div className="site-container">
          <div className="grid grid-cols-12 gap-8">
            <div className="col-span-12 lg:col-span-8 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
              {/* LEFT — featured + 4 thumb rows */}
              <div className="space-y-3">
                <div className="h-4 w-32 bg-primary/20 animate-pulse mb-2" />
                <div className="aspect-[16/10] bg-neutral-200 animate-pulse" />
                {[0, 1, 2, 3].map((i) => (
                  <div key={i} className="flex gap-3 py-2">
                    <div className="w-20 h-16 flex-shrink-0 bg-neutral-200 animate-pulse rounded-sm" />
                    <div className="flex-1 space-y-2">
                      <div className="h-2 w-2/5 bg-neutral-100 animate-pulse" />
                      <div className="h-3 w-4/5 bg-neutral-200 animate-pulse" />
                    </div>
                  </div>
                ))}
              </div>

              {/* RIGHT — featured + 2x2 image grid */}
              <div className="space-y-3">
                <div className="h-4 w-32 bg-primary/20 animate-pulse mb-2" />
                <div className="aspect-[16/10] bg-neutral-200 animate-pulse" />
                <div className="grid grid-cols-2 gap-3">
                  {[0, 1, 2, 3].map((i) => (
                    <div key={i} className="bg-white border border-border overflow-hidden">
                      <div className="aspect-[16/10] bg-neutral-200 animate-pulse" />
                      <div className="p-3 space-y-1.5">
                        <div className="h-2 w-2/5 bg-neutral-100 animate-pulse" />
                        <div className="h-3 w-4/5 bg-neutral-200 animate-pulse" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="hidden lg:block col-span-4 space-y-6">
              <div className="h-48 bg-neutral-100 animate-pulse" />
              <div className="h-32 bg-neutral-100 animate-pulse" />
              <div className="h-40 bg-neutral-100 animate-pulse" />
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
