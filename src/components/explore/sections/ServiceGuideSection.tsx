import type * as React from "react";
import { Link } from "react-router-dom";
import { Clock } from "lucide-react";
import type { ExploreCard as ExploreCardPayload, ExploreCategoryBlock } from "../../../lib/api";
import ExploreCardFallback from "../ExploreCardFallback";
import SectionHeader from "./SectionHeader";

interface Props {
  category: ExploreCategoryBlock;
  /**
   * Optional fallback pool to pad the bottom 3-col row when the
   * category itself doesn't have 3 items. Caller filters out
   * already-used slugs before passing.
   */
  fallbackPool?: ExploreCardPayload[];
}

/**
 * Phase 4.5.7 — Service Guide section.
 *
 * Top row: wide horizontal feature card — image LEFT (md:w-1/2)
 * + title/excerpt/reading-time text panel RIGHT. Operator's
 * preferred editorial design (preserved from Phase 4.5.6).
 *
 * Bottom row: 3-col grid of bordered SmallCards. Pads from the
 * fallbackPool when the category has fewer than 3 items so the
 * 3rd grid slot doesn't render empty.
 */
export default function ServiceGuideSection({ category, fallbackPool = [] }: Props) {
  // Pad bottom-row items to 3, skipping featured + duplicates.
  const seen = new Set<string>([category.featured.slug]);
  const bottom: ExploreCardPayload[] = [];
  const push = (card: ExploreCardPayload) => {
    if (seen.has(card.slug)) return;
    seen.add(card.slug);
    bottom.push(card);
  };

  category.items.forEach((c) => {
    if (bottom.length < 3) push(c);
  });
  for (const candidate of fallbackPool) {
    if (bottom.length >= 3) break;
    push(candidate);
  }

  return (
    <section
      data-section="service-guide"
      data-category={category.slug}
    >
      <SectionHeader
        title={category.name}
        viewAllHref={`/explore?category=${encodeURIComponent(category.slug)}`}
        viewAllTestId={`explore-category-viewall-${category.slug}`}
        onViewAllClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      />

      <div className="space-y-4 md:space-y-6">
        <FeatureWideCard card={category.featured} />
        {bottom.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {bottom.map((card) => (
              <BorderedSmallCard key={card.slug} card={card} />
            ))}
          </div>
        )}
      </div>
    </section>
  );
}

/**
 * Operator-preferred wide feature card — image LEFT half +
 * title + excerpt + reading time on RIGHT half.
 */
function FeatureWideCard({ card }: { card: ExploreCardPayload }) {
  const hasImage = !!card.hero_image_url;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`category-feature-${card.slug}`}
      className="group flex flex-col md:flex-row bg-white border border-border hover:border-primary/40 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
    >
      <div className="relative w-full md:w-1/2 aspect-[16/9] bg-neutral-900 overflow-hidden flex-shrink-0">
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
      </div>
      <div className="flex-1 p-6 md:p-8 flex flex-col justify-center">
        <h3 className="text-lg md:text-xl font-black uppercase tracking-tighter text-neutral-900 mb-3 leading-tight group-hover:text-primary transition-colors">
          {card.title}
        </h3>
        {card.excerpt && (
          <p className="text-sm text-neutral-500 leading-relaxed line-clamp-3 mb-4">
            {card.excerpt}
          </p>
        )}
        <span className="inline-flex items-center gap-1 text-[10px] uppercase tracking-widest font-bold text-neutral-400">
          <Clock className="w-3 h-3" /> {card.reading_time_minutes} min read
        </span>
      </div>
    </Link>
  );
}

/**
 * Bordered image-on-top card with title/meta below — used in the
 * Service Guide bottom 3-col grid.
 */
const BorderedSmallCard: React.FC<{ card: ExploreCardPayload }> = ({ card }) => {
  const hasImage = !!card.hero_image_url;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`category-small-${card.slug}`}
      className="group block h-full bg-white border border-border hover:border-primary/40 hover:-translate-y-0.5 hover:shadow-md hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
    >
      <div className="relative aspect-[16/9] bg-neutral-900 overflow-hidden">
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
      </div>
      <div className="p-4">
        <h4 className="text-xs font-black uppercase tracking-tighter text-neutral-900 leading-tight line-clamp-2 group-hover:text-primary transition-colors">
          {card.title}
        </h4>
        <span className="mt-2 inline-flex items-center gap-1 text-[9px] uppercase tracking-widest font-bold text-neutral-400">
          <Clock className="w-3 h-3" /> {card.reading_time_minutes} min
        </span>
      </div>
    </Link>
  );
};
