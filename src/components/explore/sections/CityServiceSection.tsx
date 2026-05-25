import type * as React from "react";
import { Link } from "react-router-dom";
import { Clock } from "lucide-react";
import type { ExploreCard as ExploreCardPayload, ExploreCategoryBlock } from "../../../lib/api";
import ExploreCardFallback from "../ExploreCardFallback";
import SectionHeader from "./SectionHeader";

interface Props {
  category: ExploreCategoryBlock;
  /**
   * Optional pool of fallback cards to pad the 4×2 grid when
   * `category` doesn't have enough items. Caller is expected to
   * filter out slugs already used elsewhere on the page before
   * passing the pool, so this component just appends until the
   * grid fills.
   */
  fallbackPool?: ExploreCardPayload[];
}

/**
 * Phase 4.5.7 — City Service section.
 *
 * 4-column × 2-row grid of EQUALLY-SIZED cards (8 total per the
 * operator's mockup). Each card: image-on-top with category badge
 * + reading-time overlay + bordered white panel below with title +
 * 1-line excerpt + reading-time meta.
 *
 * Mobile: collapses to 1-col stack; tablet: 2-col; desktop: 4-col.
 *
 * Graceful degradation: renders whatever's available; if the pool
 * is exhausted the trailing slots simply don't render (no empty
 * grid cells reserved).
 */
export default function CityServiceSection({ category, fallbackPool = [] }: Props) {
  // Merge featured + items, then pad with fallback pool until 8.
  // Skip duplicates (featured slug appearing in fallback pool, etc.).
  const all: ExploreCardPayload[] = [];
  const seen = new Set<string>();

  const push = (card: ExploreCardPayload) => {
    if (seen.has(card.slug)) return;
    seen.add(card.slug);
    all.push(card);
  };

  push(category.featured);
  category.items.forEach(push);
  for (const candidate of fallbackPool) {
    if (all.length >= 8) break;
    push(candidate);
  }

  const cards = all.slice(0, 8);

  if (cards.length === 0) return null;

  return (
    <section
      data-section="city-service"
      data-category={category.slug}
    >
      <SectionHeader
        title={category.name}
        viewAllHref={`/explore?category=${encodeURIComponent(category.slug)}`}
        viewAllTestId={`explore-category-viewall-${category.slug}`}
        onViewAllClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      />

      <div
        data-variant-grid="city-service"
        className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
      >
        {cards.map((card) => (
          <CityServiceCard key={card.slug} card={card} />
        ))}
      </div>
    </section>
  );
}

/**
 * City Service card — image-on-top + bordered white panel below
 * with title + 1-line description + reading-time. Compact
 * variant designed for the 4-col grid (smaller than
 * `large-stacked` at full width).
 */
const CityServiceCard: React.FC<{ card: ExploreCardPayload }> = ({ card }) => {
  const hasImage = !!card.hero_image_url;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`category-grid-${card.slug}`}
      className="group flex flex-col h-full bg-white border border-border hover:border-primary/40 hover:-translate-y-0.5 hover:shadow-md hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
    >
      <div className="relative aspect-[16/10] bg-neutral-900 overflow-hidden">
        {hasImage ? (
          <img
            src={card.hero_image_url ?? ""}
            alt={card.title}
            className="absolute inset-0 w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
            referrerPolicy="no-referrer"
            loading="lazy"
          />
        ) : (
          <ExploreCardFallback
            category={card.category}
            iconName={card.category?.icon_name}
            title={card.title}
          />
        )}
        {hasImage && card.category && (
          <span className="absolute top-2 left-2 inline-block bg-primary text-white px-2 py-0.5 text-[8px] font-bold uppercase tracking-widest z-10">
            {card.category.name}
          </span>
        )}
      </div>
      <div className="p-3 flex-1 flex flex-col">
        <h4 className="text-xs font-bold uppercase tracking-tight text-neutral-900 leading-tight line-clamp-2 group-hover:text-primary transition-colors mb-2">
          {card.title}
        </h4>
        {card.excerpt && (
          <p className="text-[11px] text-neutral-500 leading-snug line-clamp-2 mb-2">
            {card.excerpt}
          </p>
        )}
        <span className="mt-auto inline-flex items-center gap-1 text-[9px] uppercase tracking-widest font-bold text-neutral-400">
          <Clock className="w-3 h-3" /> {card.reading_time_minutes} min
        </span>
      </div>
    </Link>
  );
};
