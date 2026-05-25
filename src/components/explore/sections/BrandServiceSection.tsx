import type { ExploreCategoryBlock } from "../../../lib/api";
import ExploreCard from "../ExploreCard";
import SectionHeader from "./SectionHeader";

interface Props {
  category: ExploreCategoryBlock;
}

/**
 * Phase 4.5.7 — Brand Service section.
 * Phase 4.5.8 — Right-column cards switched to horizontal
 *                rectangles. The previous `lg:auto-rows-[minmax(140px,1fr)]`
 *                grid let `aspect-square` on the small ExploreCard
 *                Link push each cell to width-tall (~400px square),
 *                which then forced the LARGE column to stretch to
 *                match and left big white space inside the LARGE's
 *                text panel.
 *
 *                New layout uses a FIXED-ROW grid
 *                (`lg:auto-rows-[180px]`) — each right cell is
 *                exactly 180px tall at ~500px wide, so the aspect-
 *                square hint is naturally bypassed by the explicit
 *                row height. Right cards become visibly landscape
 *                rectangles, LARGE total height = 3 × 180 = 540px
 *                (image aspect-[16/9] ≈ 326px + text panel ≈ 214px).
 *
 *   ┌──────────────────────────┬──────────────┐  ↑
 *   │                          │ rect 180px   │  │
 *   │   LARGE-stacked          ├──────────────┤  │ 540px
 *   │   cols 1-7, rows 1-3     │ rect 180px   │  │
 *   │   image-top + text panel ├──────────────┤  │
 *   │                          │ rect 180px   │  │
 *   └──────────────────────────┴──────────────┘  ↓
 *
 * Mobile: stacks via order-1..4. Each right card mobile gets
 * `aspect-[16/9]` so it still has visible height without the lg
 * grid row sizing.
 */
export default function BrandServiceSection({ category }: Props) {
  const right = category.items.slice(0, 3);

  return (
    <section
      data-section="brand-service"
      data-category={category.slug}
    >
      <SectionHeader
        title={category.name}
        viewAllHref={`/explore?category=${encodeURIComponent(category.slug)}`}
        viewAllTestId={`explore-category-viewall-${category.slug}`}
        onViewAllClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      />

      {right.length === 0 ? (
        <div className="grid grid-cols-1 gap-4 md:gap-6">
          <ExploreCard
            page={category.featured}
            size="large-stacked"
            testIdPrefix="category-feature-"
          />
        </div>
      ) : (
        <div
          data-variant-grid="brand-service"
          className="grid grid-cols-1 gap-4 md:gap-6 lg:grid-cols-12 lg:grid-rows-[repeat(3,180px)]"
        >
          {/* LARGE-stacked — cols 1-7, rows 1-3 (total 540px tall) */}
          <div
            data-slot="brand-large"
            className="order-1 lg:order-none lg:col-start-1 lg:col-end-8 lg:row-start-1 lg:row-end-4"
          >
            <ExploreCard
              page={category.featured}
              size="large-stacked"
              testIdPrefix="category-feature-"
              className="h-full"
            />
          </div>

          {/* Right-column SMALL × N — cols 8-12, one per row (each 180px tall) */}
          {right.map((card, idx) => (
            <div
              key={card.slug}
              data-slot="brand-small"
              className={smallCellClass(idx, right.length)}
            >
              <ExploreCard
                page={card}
                size="small"
                testIdPrefix="category-small-"
                className="h-full"
              />
            </div>
          ))}
        </div>
      )}
    </section>
  );
}

const ORDER_RIGHT = ["order-2", "order-3", "order-4"] as const;

/**
 * Each right cell is a single fixed-height grid row. With
 * `lg:auto-rows-[180px]` on the parent, the cell is exactly 180px
 * tall at ~500px wide — naturally horizontal-rectangular.
 *
 * Mobile cells get an explicit `aspect-[16/9]` so they have
 * visible height when the grid collapses to a single column.
 */
function smallCellClass(idx: number, total: number): string {
  const orderCls = ORDER_RIGHT[idx] ?? "order-5";
  const base = `${orderCls} lg:order-none aspect-[16/9] lg:aspect-auto lg:col-start-8 lg:col-end-13`;

  if (total === 1) return `${base} lg:row-start-1 lg:row-end-4`;
  if (total === 2) {
    return idx === 0
      ? `${base} lg:row-start-1 lg:row-end-3`
      : `${base} lg:row-start-2 lg:row-end-4`;
  }
  if (idx === 0) return `${base} lg:row-start-1 lg:row-end-2`;
  if (idx === 1) return `${base} lg:row-start-2 lg:row-end-3`;
  return `${base} lg:row-start-3 lg:row-end-4`;
}
