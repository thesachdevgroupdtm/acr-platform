import type { ExploreCard as ExploreCardPayload } from "../../lib/api";
import ExploreCard from "./ExploreCard";

interface Props {
  pages: ExploreCardPayload[];
}

/**
 * Phase 4.5.2 — 5-card editorial mosaic (replaces the 4-card grid).
 *
 * Desktop layout (12-col, 2-row):
 *
 *   ┌────────┬───────────────────┬────────┐
 *   │   C1   │                   │   C4   │   row 1
 *   ├────────┤    C3 (LARGE)     ├────────┤
 *   │   C2   │   col 4-9, 2 rows │   C5   │   row 2
 *   └────────┴───────────────────┴────────┘
 *
 * Slot map:
 *   - Card 1: cols 1–3,  row 1     (top-left small)
 *   - Card 2: cols 1–3,  row 2     (bottom-left small)
 *   - Card 3: cols 4–9,  rows 1–2  (CENTER LARGE — 6 cols × 2 rows)
 *   - Card 4: cols 10–12, row 1    (top-right small)
 *   - Card 5: cols 10–12, row 2    (bottom-right small)
 *
 * Mobile (<lg): single column. Card 3 (LARGE) renders first
 * via `order-1`; the 4 small cards follow in a 2×2 grid.
 *
 * Graceful degradation by `pages.length`:
 *   ≥5  → all five slots
 *    4  → C1 + C2 + C3 + C4 (skip C5)
 *    3  → C1 + C3 + C4      (skip C2 + C5; LARGE keeps center)
 *   <3  → render null (parent's empty branch handles it)
 *
 * NO carousel, NO autoplay, NO drag, NO keyboard nav, NO
 * scroll-triggered animations — pure CSS grid.
 */
export default function ExploreFeaturedGrid({ pages }: Props) {
  if (!pages || pages.length < 3) return null;

  const has5 = pages.length >= 5;
  const has4 = pages.length >= 4;

  // Slot picks per the degradation rules.
  const slotC1 = pages[0];
  const slotC2 = pages.length >= 4 ? pages[1] : null;
  const slotC3 = has4 ? pages[2] : pages[1]; // LARGE always present
  const slotC4 = has4 ? pages[3] : pages[2];
  const slotC5 = has5 ? pages[4] : null;

  return (
    <section
      data-testid="explore-featured-grid"
      data-section="featured"
      className="bg-neutral-50 py-8 md:py-12"
    >
      <div className="site-container">
        <div className="grid grid-cols-1 gap-4 md:gap-6 lg:grid-cols-12 lg:grid-rows-2 lg:auto-rows-[260px]">
          {/* CENTER LARGE — first in DOM order so mobile gets it on top via order-1 */}
          {slotC3 && (
            <div
              data-slot="featured-large"
              className="order-1 lg:order-none lg:col-start-4 lg:col-end-10 lg:row-start-1 lg:row-end-3 aspect-[4/3] lg:aspect-auto"
            >
              <ExploreCard
                page={slotC3}
                size="large"
                testIdPrefix="featured-card-"
                className="h-full"
              />
            </div>
          )}

          {/* TOP-LEFT small */}
          {slotC1 && (
            <div
              data-slot="featured-small"
              className="order-2 lg:order-none lg:col-start-1 lg:col-end-4 lg:row-start-1 lg:row-end-2 aspect-[4/3] lg:aspect-auto"
            >
              <ExploreCard
                page={slotC1}
                size="small"
                testIdPrefix="featured-card-"
                className="h-full"
              />
            </div>
          )}

          {/* TOP-RIGHT small */}
          {slotC4 && (
            <div
              data-slot="featured-small"
              className="order-3 lg:order-none lg:col-start-10 lg:col-end-13 lg:row-start-1 lg:row-end-2 aspect-[4/3] lg:aspect-auto"
            >
              <ExploreCard
                page={slotC4}
                size="small"
                testIdPrefix="featured-card-"
                className="h-full"
              />
            </div>
          )}

          {/* BOTTOM-LEFT small */}
          {slotC2 && (
            <div
              data-slot="featured-small"
              className="order-4 lg:order-none lg:col-start-1 lg:col-end-4 lg:row-start-2 lg:row-end-3 aspect-[4/3] lg:aspect-auto"
            >
              <ExploreCard
                page={slotC2}
                size="small"
                testIdPrefix="featured-card-"
                className="h-full"
              />
            </div>
          )}

          {/* BOTTOM-RIGHT small */}
          {slotC5 && (
            <div
              data-slot="featured-small"
              className="order-5 lg:order-none lg:col-start-10 lg:col-end-13 lg:row-start-2 lg:row-end-3 aspect-[4/3] lg:aspect-auto"
            >
              <ExploreCard
                page={slotC5}
                size="small"
                testIdPrefix="featured-card-"
                className="h-full"
              />
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
