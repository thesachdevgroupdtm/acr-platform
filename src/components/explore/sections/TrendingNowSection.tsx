import type { ExploreCard as ExploreCardPayload } from "../../../lib/api";
import ExploreCard from "../ExploreCard";
import SectionHeader from "./SectionHeader";

interface Props {
  items: ExploreCardPayload[];
}

/**
 * Phase 4.5.7 — Trending Now section.
 *
 * 12-col 2-row mosaic with LARGE center (items[2]) flanked by
 * 2 SMALL stacked left + 2 SMALL stacked right.
 *
 *   ┌────────┬─────────────────────┬────────┐
 *   │ items0 │                     │ items1 │
 *   │ SMALL  │                     │ SMALL  │
 *   ├────────┤  items[2] LARGE     ├────────┤
 *   │ items3 │  cols 4-9, rows 1-2 │ items4 │
 *   │ SMALL  │                     │ SMALL  │
 *   └────────┴─────────────────────┴────────┘
 *
 * Mobile (<lg): stack with LARGE first via order-1.
 * Graceful 1/2/3/4-card degradation handled inline.
 *
 * Renders OUTSIDE Container 1 (full-width above search bar) per
 * the operator's mockup, so this section has NO sidebar widget
 * alongside.
 */
export default function TrendingNowSection({ items }: Props) {
  if (!items || items.length === 0) return null;

  const visible = items.slice(0, 5);
  const len = visible.length;

  // items[2] is the LARGE; with fewer than 3 items we promote
  // items[0] to the LARGE slot.
  const largeIdx = len >= 3 ? 2 : 0;
  const large = visible[largeIdx];

  // Slot priority order:
  //   slot 1 (left-top), slot 4 (right-top), slot 2 (left-bottom),
  //   slot 5 (right-bottom). For len === 3 we fill slots 1 + 4
  //   (one each side of LARGE).
  const small1 = len >= 2 ? visible[len === 2 ? 1 : 0] : null;
  const small4 = len >= 3 ? visible[1] : null;
  const small2 = len >= 4 ? visible[3] : null;
  const small5 = len >= 5 ? visible[4] : null;

  return (
    <section data-section="trending">
      <SectionHeader
        title={
          <>
            TRENDING <span className="section-heading-accent">NOW.</span>
          </>
        }
        subhead="Most-read this week."
      />

      {len === 1 ? (
        <div data-testid="explore-trending-grid" className="grid grid-cols-1 gap-4">
          <div data-slot="trending-large" className="aspect-[4/3] lg:aspect-[16/9]">
            <ExploreCard page={large} size="large" testIdPrefix="trending-card-" className="h-full" />
          </div>
        </div>
      ) : len === 2 ? (
        <div
          data-testid="explore-trending-grid"
          data-trending-count="2"
          className="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:auto-rows-[minmax(160px,auto)]"
        >
          <div
            data-slot="trending-large"
            className="order-1 lg:order-none lg:col-span-9 aspect-[4/3] lg:aspect-auto"
          >
            <ExploreCard page={large} size="large" testIdPrefix="trending-card-" className="h-full" />
          </div>
          {small1 && (
            <div
              data-slot="trending-small"
              className="order-2 lg:order-none lg:col-span-3 aspect-[16/9] lg:aspect-auto"
            >
              <ExploreCard page={small1} size="small" testIdPrefix="trending-card-" className="h-full" />
            </div>
          )}
        </div>
      ) : (
        <div
          data-testid="explore-trending-grid"
          data-trending-count={len}
          className="grid grid-cols-1 gap-4 lg:gap-6 lg:grid-cols-12 lg:grid-rows-2 lg:auto-rows-[minmax(160px,1fr)]"
        >
          <div
            data-slot="trending-large"
            className="order-1 lg:order-none lg:col-start-4 lg:col-end-10 lg:row-start-1 lg:row-end-3 aspect-[4/3] lg:aspect-auto"
          >
            <ExploreCard page={large} size="large" testIdPrefix="trending-card-" className="h-full" />
          </div>

          {small1 && (
            <div
              data-slot="trending-small"
              className="order-2 lg:order-none lg:col-start-1 lg:col-end-4 lg:row-start-1 lg:row-end-2 aspect-[16/9] lg:aspect-auto"
            >
              <ExploreCard page={small1} size="small" testIdPrefix="trending-card-" className="h-full" />
            </div>
          )}

          {small4 && (
            <div
              data-slot="trending-small"
              className="order-3 lg:order-none lg:col-start-10 lg:col-end-13 lg:row-start-1 lg:row-end-2 aspect-[16/9] lg:aspect-auto"
            >
              <ExploreCard page={small4} size="small" testIdPrefix="trending-card-" className="h-full" />
            </div>
          )}

          {small2 && (
            <div
              data-slot="trending-small"
              className="order-4 lg:order-none lg:col-start-1 lg:col-end-4 lg:row-start-2 lg:row-end-3 aspect-[16/9] lg:aspect-auto"
            >
              <ExploreCard page={small2} size="small" testIdPrefix="trending-card-" className="h-full" />
            </div>
          )}

          {small5 && (
            <div
              data-slot="trending-small"
              className="order-5 lg:order-none lg:col-start-10 lg:col-end-13 lg:row-start-2 lg:row-end-3 aspect-[16/9] lg:aspect-auto"
            >
              <ExploreCard page={small5} size="small" testIdPrefix="trending-card-" className="h-full" />
            </div>
          )}
        </div>
      )}
    </section>
  );
}
