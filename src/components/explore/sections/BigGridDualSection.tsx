import type * as React from "react";
import { Link } from "react-router-dom";
import { ChevronLeft, ChevronRight, Star, Heart } from "lucide-react";
import type { ExploreCard as ExploreCardPayload, ExploreCategoryBlock } from "../../../lib/api";
import ExploreCardFallback from "../ExploreCardFallback";

interface Props {
  leftCategory: ExploreCategoryBlock | null;
  rightCategory: ExploreCategoryBlock | null;
  /**
   * Optional pool of fallback cards to pad each sub-section to 5
   * (1 featured + 4 children). Caller is expected to deduplicate
   * against all other slugs already on the page before passing.
   */
  fallbackPool?: ExploreCardPayload[];
}

/**
 * Phase 4.5.10 — Big Grid Dual section.
 *
 * Two sub-sections side-by-side, ASYMMETRIC layouts per reference
 * images:
 *   LEFT  — featured (full-bleed image with overlay) + 4 horizontal
 *           thumb rows (small thumbnail + author/date + 2-line title)
 *   RIGHT — featured (same style, heart icon vs star) + 2×2 grid of
 *           small image cards (image-on-top + author/date + title BELOW)
 *
 * 5 cards per sub-section, 10 total.
 *
 * Mobile: sub-sections stack vertically.
 * Hides entirely if both sub-sections fail their min-2-card threshold.
 */
export default function BigGridDualSection({
  leftCategory,
  rightCategory,
  fallbackPool = [],
}: Props) {
  // Build padded card lists for each sub-section. Both sub-sections
  // share the SAME pool; we mark slugs consumed by left so right
  // doesn't pick them again.
  const consumed = new Set<string>();

  const leftCards = buildSubSectionCards(leftCategory, fallbackPool, consumed, 5);
  const rightCards = buildSubSectionCards(rightCategory, fallbackPool, consumed, 5);

  const leftReady = leftCategory && leftCards.length >= 2;
  const rightReady = rightCategory && rightCards.length >= 2;

  if (!leftReady && !rightReady) return null;

  return (
    <section
      data-section="big-grid-dual"
      className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12"
    >
      {leftReady && (
        <BigGridSubSection
          category={leftCategory!}
          cards={leftCards}
          variant="thumb-rows"
          accentIcon="star"
        />
      )}
      {rightReady && (
        <BigGridSubSection
          category={rightCategory!}
          cards={rightCards}
          variant="grid-2x2"
          accentIcon="heart"
        />
      )}
    </section>
  );
}

/** Build [featured, ...4 children] from category + fallback pool. */
function buildSubSectionCards(
  category: ExploreCategoryBlock | null,
  pool: ExploreCardPayload[],
  consumed: Set<string>,
  target: number,
): ExploreCardPayload[] {
  if (!category) return [];

  const cards: ExploreCardPayload[] = [];
  const localSeen = new Set<string>();

  const push = (card: ExploreCardPayload) => {
    if (localSeen.has(card.slug)) return;
    if (consumed.has(card.slug)) return;
    localSeen.add(card.slug);
    consumed.add(card.slug);
    cards.push(card);
  };

  push(category.featured);
  category.items.forEach(push);
  for (const candidate of pool) {
    if (cards.length >= target) break;
    push(candidate);
  }

  return cards.slice(0, target);
}

/* ─────────── Sub-section ─────────── */

interface SubSectionProps {
  category: ExploreCategoryBlock;
  cards: ExploreCardPayload[];
  variant: "thumb-rows" | "grid-2x2";
  accentIcon: "star" | "heart";
}

function BigGridSubSection({ category, cards, variant, accentIcon }: SubSectionProps) {
  const featured = cards[0];
  const rest = cards.slice(1, 5);

  return (
    <div
      data-section="big-grid"
      data-category={category.slug}
      data-variant={variant}
      className="flex flex-col"
    >
      {/* Heading: blue title + prev/next arrows + thin blue underline */}
      <div className="flex items-center justify-between pb-2 mb-4 border-b border-primary/20">
        <Link
          to={`/explore?category=${encodeURIComponent(category.slug)}`}
          data-testid={`big-grid-viewall-${category.slug}`}
          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
          className="text-sm font-bold uppercase tracking-widest text-primary hover:underline"
        >
          {category.name}
        </Link>
        <div className="flex items-center gap-1 text-neutral-400">
          <ChevronLeft className="w-4 h-4" aria-hidden="true" />
          <span className="text-xs">/</span>
          <ChevronRight className="w-4 h-4" aria-hidden="true" />
        </div>
      </div>

      {/* Featured card */}
      {featured && <FeaturedCard card={featured} accentIcon={accentIcon} />}

      {/* Children: thumb-rows OR 2×2 grid */}
      {rest.length > 0 && (
        variant === "thumb-rows" ? (
          <div className="mt-4 divide-y divide-neutral-200">
            {rest.map((card) => (
              <ThumbRowCard key={card.slug} card={card} />
            ))}
          </div>
        ) : (
          <div className="mt-4 grid grid-cols-2 gap-3">
            {rest.map((card) => (
              <SmallImageCard key={card.slug} card={card} />
            ))}
          </div>
        )
      )}
    </div>
  );
}

/* ─────────── Featured card (top of each sub-section) ─────────── */

const FeaturedCard: React.FC<{
  card: ExploreCardPayload;
  accentIcon: "star" | "heart";
}> = ({ card, accentIcon }) => {
  const hasImage = !!card.hero_image_url;
  const AccentIcon = accentIcon === "star" ? Star : Heart;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`big-grid-feature-${card.slug}`}
      className="group relative block w-full aspect-[16/10] bg-neutral-900 overflow-hidden hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300"
    >
      {hasImage ? (
        <img
          src={card.hero_image_url ?? ""}
          alt={card.title}
          className="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-[1.02] transition-all duration-500"
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

      {/* Bottom dark gradient overlay (covers bottom 60%) */}
      <div className="absolute inset-x-0 bottom-0 h-3/5 bg-gradient-to-t from-neutral-950 via-neutral-950/70 to-transparent pointer-events-none" />

      {/* Top-left category badge */}
      {card.category && (
        <span className="absolute top-3 left-3 inline-block bg-primary text-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest z-10">
          {card.category.name}
        </span>
      )}

      {/* Top-right bookmark/star/heart icon */}
      <button
        type="button"
        aria-label="Bookmark"
        onClick={(e) => { e.preventDefault(); e.stopPropagation(); }}
        className="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-8 h-8 bg-white/15 hover:bg-white/30 backdrop-blur-sm rounded-sm transition-colors"
      >
        <AccentIcon className="w-4 h-4 text-white" aria-hidden="true" />
      </button>

      {/* Bottom overlay: author + date + title */}
      <div className="absolute inset-x-0 bottom-0 p-5 z-10 text-white">
        <p className="text-[11px] text-neutral-200 mb-1">
          {authorLabel()} · {formatDate(card.published_at)}
        </p>
        <h4 className="text-base md:text-lg font-bold leading-snug line-clamp-2 group-hover:text-white/95 transition-colors">
          {card.title}
        </h4>
      </div>
    </Link>
  );
};

/* ─────────── Thumb-row card (LEFT sub-section children) ─────────── */

const ThumbRowCard: React.FC<{ card: ExploreCardPayload }> = ({ card }) => {
  const hasImage = !!card.hero_image_url;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`big-grid-row-${card.slug}`}
      className="group flex items-start gap-3 py-3 hover:bg-neutral-50 transition-colors -mx-2 px-2 rounded-sm"
    >
      <div className="relative w-20 h-16 flex-shrink-0 bg-neutral-900 overflow-hidden rounded-sm">
        {hasImage ? (
          <img
            src={card.hero_image_url ?? ""}
            alt={card.title}
            className="absolute inset-0 w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-300"
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
      <div className="flex-1 min-w-0">
        <p className="text-[11px] text-neutral-500 mb-0.5">
          <span className="font-medium text-neutral-700">{authorLabel()}</span>
          <span className="ml-2">{formatDate(card.published_at)}</span>
        </p>
        <h5 className="text-sm font-bold text-neutral-900 leading-snug line-clamp-2 group-hover:text-primary transition-colors">
          {card.title}
        </h5>
      </div>
    </Link>
  );
};

/* ─────────── Small image card (RIGHT sub-section 2×2 grid) ─────────── */

const SmallImageCard: React.FC<{ card: ExploreCardPayload }> = ({ card }) => {
  const hasImage = !!card.hero_image_url;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`big-grid-cell-${card.slug}`}
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
      </div>
      <div className="p-3 flex-1 flex flex-col">
        <p className="text-[10px] text-neutral-500 mb-1">
          <span className="font-medium text-neutral-700">{authorLabel()}</span>
          <span className="ml-2">{formatDate(card.published_at)}</span>
        </p>
        <h5 className="text-xs font-bold text-neutral-900 leading-snug line-clamp-2 group-hover:text-primary transition-colors">
          {card.title}
        </h5>
      </div>
    </Link>
  );
};

/* ─────────── Helpers ─────────── */

/**
 * Backend ExploreCard payload has no `author` field. Per D-4.5.10-7
 * default to "ACR Editorial" for every card in this section.
 */
function authorLabel(): string {
  return "ACR Editorial";
}

/**
 * Format a Laravel ISO date string as "DD MMMM YYYY" (e.g.
 * "16 April 2026"). Returns "" when input is missing or invalid
 * so the caller can suppress the dot separator if needed.
 */
function formatDate(iso?: string | null): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  const day = d.getDate();
  const month = d.toLocaleString("en-US", { month: "long" });
  const year = d.getFullYear();
  return `${day} ${month} ${year}`;
}
