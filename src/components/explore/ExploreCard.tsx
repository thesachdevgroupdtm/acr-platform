import type * as React from "react";
import { Link } from "react-router-dom";
import { Clock } from "lucide-react";
import type { ExploreCard as ExploreCardPayload } from "../../lib/api";
import ExploreCardFallback from "./ExploreCardFallback";

/**
 * Phase 4.5.1 — single source of truth for explore card rendering.
 * Phase 4.5.2 — entrance animations stripped per D-4.5.2-3.
 *   Hover lift + image scale (Tailwind CSS) remain.
 * Phase 4.5.6 — added `large-stacked` variant for Brand Service.
 *   Image on top + white panel below with title + description +
 *   reading-time. Distinct from `large` (full-bleed overlay).
 */

type Size = "large" | "large-stacked" | "medium" | "small" | "compact" | "wide";

interface Props {
  page: ExploreCardPayload;
  size?: Size;
  className?: string;
  testIdPrefix?: string;
}

const SIZE_CFG: Record<
  Size,
  { titleClasses: string; ratio: string; layout: "stack" | "horizontal" | "stacked-card" }
> = {
  large:          { titleClasses: "text-lg md:text-2xl line-clamp-3",  ratio: "aspect-[4/3]",   layout: "stack" },
  "large-stacked":{ titleClasses: "text-xl lg:text-2xl line-clamp-2",  ratio: "aspect-[16/9]",  layout: "stacked-card" },
  medium:         { titleClasses: "text-sm md:text-base line-clamp-2", ratio: "aspect-[16/10]", layout: "stack" },
  small:          { titleClasses: "text-xs md:text-sm line-clamp-2",   ratio: "aspect-square",  layout: "stack" },
  compact:        { titleClasses: "text-xs line-clamp-2",              ratio: "aspect-[16/9]",  layout: "stack" },
  wide:           { titleClasses: "text-base md:text-lg line-clamp-2", ratio: "aspect-[16/9]",  layout: "horizontal" },
};

const ExploreCard: React.FC<Props> = ({
  page,
  size = "medium",
  className = "",
  testIdPrefix = "explore-card-",
}) => {
  const cfg = SIZE_CFG[size];
  const hasImage = !!page.hero_image_url;

  /* ─── stacked-card layout: image on top, text panel below (Brand Service LARGE) ─── */
  if (cfg.layout === "stacked-card") {
    return (
      <div className={`h-full ${className}`}>
        <Link
          to={`/${page.slug}`}
          data-testid={`${testIdPrefix}${page.slug}`}
          data-card-size={size}
          className="group flex flex-col h-full bg-white border border-border hover:border-primary/40 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
        >
          {/* Image on top with category badge top-left + reading time bottom-left overlays */}
          <div className={`relative ${cfg.ratio} bg-neutral-900 overflow-hidden flex-shrink-0`}>
            {hasImage ? (
              <img
                src={page.hero_image_url ?? ""}
                alt={page.title}
                className="absolute inset-0 w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                referrerPolicy="no-referrer"
                loading="lazy"
              />
            ) : (
              <ExploreCardFallback
                category={page.category}
                iconName={page.category?.icon_name}
                title={page.title}
              />
            )}
            {hasImage && page.category && (
              <span className="absolute top-3 left-3 inline-block bg-primary text-white px-2 py-1 text-[8px] font-bold uppercase tracking-widest z-10">
                {page.category.name}
              </span>
            )}
            {hasImage && (
              <span className="absolute bottom-3 left-3 inline-flex items-center gap-1 bg-neutral-950/70 text-white px-2 py-1 text-[10px] uppercase tracking-widest font-bold z-10">
                <Clock className="w-3 h-3" /> {page.reading_time_minutes} min
              </span>
            )}
          </div>

          {/* Text panel below */}
          <div className="flex-1 p-5 lg:p-6 flex flex-col">
            <h3 className={`${cfg.titleClasses} font-bold uppercase tracking-tight text-neutral-900 leading-tight mb-3 group-hover:text-primary transition-colors`}>
              {page.title}
            </h3>
            {page.excerpt && (
              <p className="text-sm text-neutral-600 leading-relaxed line-clamp-2 mb-3">
                {page.excerpt}
              </p>
            )}
            <div className="mt-3 inline-flex items-center gap-1 text-xs text-neutral-500 uppercase tracking-wide font-bold">
              <Clock className="w-3 h-3" /> {page.reading_time_minutes} min read
            </div>
          </div>
        </Link>
      </div>
    );
  }

  /* ─── horizontal layout (existing `wide` size) ─── */
  if (cfg.layout === "horizontal") {
    return (
      <div className={className}>
        <Link
          to={`/${page.slug}`}
          data-testid={`${testIdPrefix}${page.slug}`}
          data-card-size={size}
          className="group flex flex-col md:flex-row h-full bg-white border border-border hover:border-primary/40 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
        >
          <div className="relative md:w-2/5 w-full aspect-[16/9] md:aspect-auto bg-neutral-900 overflow-hidden flex-shrink-0">
            {hasImage ? (
              <img
                src={page.hero_image_url ?? ""}
                alt={page.title}
                className="absolute inset-0 w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                referrerPolicy="no-referrer"
                loading="lazy"
              />
            ) : (
              <ExploreCardFallback
                category={page.category}
                iconName={page.category?.icon_name}
                title={page.title}
              />
            )}
          </div>
          <div className="flex-1 p-5 md:p-6 flex flex-col justify-center">
            {page.category && (
              <span className="inline-block self-start bg-primary text-white px-2 py-1 text-[8px] font-bold uppercase tracking-widest mb-2">
                {page.category.name}
              </span>
            )}
            <h3 className={`${cfg.titleClasses} font-black uppercase tracking-tighter text-neutral-900 leading-tight mb-2 group-hover:text-primary transition-colors`}>
              {page.title}
            </h3>
            {page.excerpt && size === "wide" && (
              <p className="text-xs text-neutral-500 leading-relaxed line-clamp-2 mb-2">
                {page.excerpt}
              </p>
            )}
            <span className="inline-flex items-center gap-1 text-[10px] uppercase tracking-widest font-bold text-neutral-400">
              <Clock className="w-3 h-3" /> {page.reading_time_minutes} min read
            </span>
          </div>
        </Link>
      </div>
    );
  }

  /* ─── stack layout: full-bleed image overlay (existing default) ─── */
  return (
    <div className={`h-full ${className}`}>
      <Link
        to={`/${page.slug}`}
        data-testid={`${testIdPrefix}${page.slug}`}
        data-card-size={size}
        className={`group relative block w-full h-full bg-neutral-900 overflow-hidden hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 ${cfg.ratio}`}
      >
        {hasImage ? (
          <>
            <img
              src={page.hero_image_url ?? ""}
              alt={page.title}
              className="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-95 group-hover:scale-[1.03] transition-all duration-500"
              referrerPolicy="no-referrer"
              loading="lazy"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/45 to-transparent pointer-events-none" />
            <div className="relative h-full flex flex-col justify-end p-4 md:p-5 text-white">
              {page.category && (
                <span className="inline-block self-start bg-primary text-white px-2 py-1 text-[8px] font-bold uppercase tracking-widest mb-2">
                  {page.category.name}
                </span>
              )}
              <h3 className={`${cfg.titleClasses} font-black uppercase tracking-tighter leading-tight mb-1 group-hover:text-white/90 transition-colors`}>
                {page.title}
              </h3>
              <span className="inline-flex items-center gap-1 text-[10px] uppercase tracking-widest font-bold text-neutral-200">
                <Clock className="w-3 h-3" /> {page.reading_time_minutes} min
              </span>
            </div>
          </>
        ) : (
          <ExploreCardFallback
            category={page.category}
            iconName={page.category?.icon_name}
            title={page.title}
          />
        )}
      </Link>
    </div>
  );
};

export default ExploreCard;
