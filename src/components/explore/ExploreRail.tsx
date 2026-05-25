import { useEffect, useRef, useState } from "react";
import type * as React from "react";
import { Link } from "react-router-dom";
import { ChevronLeft, ChevronRight, Clock } from "lucide-react";
import type { ExploreCard } from "../../lib/api";
import ExploreCardFallback from "./ExploreCardFallback";
import SectionHeading from "../layout/SectionHeading";

interface Props {
  title: string;
  items: ExploreCard[];
}

const AUTO_SCROLL_PX_PER_TICK = 1;
const AUTO_SCROLL_INTERVAL_MS = 50;

/**
 * Phase 4.5 — horizontal auto-scroll rail.
 *
 * Per D-4.5-4: exactly 2 rails on /explore (Trending Searches +
 * Most Read This Week). Per D-4.5-14: auto-scroll only on
 * desktop; mobile uses native touch-drag. Pauses on hover.
 *
 * The wheel-to-horizontal handler is intentionally subtle: it
 * only intercepts vertical-wheel events when the rail is
 * actively scrollable AND its bounding rect is on-screen, so
 * page scroll keeps working when the cursor isn't over the rail.
 */
export default function ExploreRail({ title, items }: Props) {
  const ref = useRef<HTMLDivElement | null>(null);
  const [paused, setPaused] = useState(false);
  const [overflowing, setOverflowing] = useState(false);
  const [isDesktop, setIsDesktop] = useState(false);

  /* Track media-query for desktop autoplay gate (D-4.5-14). */
  useEffect(() => {
    const mq = window.matchMedia("(min-width: 1024px)");
    const update = () => setIsDesktop(mq.matches);
    update();
    mq.addEventListener("change", update);
    return () => mq.removeEventListener("change", update);
  }, []);

  /* Detect overflow to gate the arrow buttons. */
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const update = () => setOverflowing(el.scrollWidth > el.clientWidth + 8);
    update();
    const ro = new ResizeObserver(update);
    ro.observe(el);
    return () => ro.disconnect();
  }, [items]);

  /* Auto-scroll loop (desktop only). */
  useEffect(() => {
    if (!isDesktop || paused || !overflowing) return;
    const el = ref.current;
    if (!el) return;
    const id = window.setInterval(() => {
      // Stop at the right edge — D-4.5-4 says no loop.
      if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 1) return;
      el.scrollLeft += AUTO_SCROLL_PX_PER_TICK;
    }, AUTO_SCROLL_INTERVAL_MS);
    return () => window.clearInterval(id);
  }, [isDesktop, paused, overflowing, items]);

  const scrollBy = (px: number) => {
    ref.current?.scrollBy({ left: px, behavior: "smooth" });
  };

  if (items.length === 0) return null;

  return (
    <section
      data-section="rail"
      data-rail-title={title}
      className="bg-neutral-50 py-12 md:py-14"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
    >
      <div className="site-container">
        {/* Header */}
        <div className="flex items-end justify-between mb-6 pb-3 border-b-2 border-primary">
          <SectionHeading>{title}</SectionHeading>
          {overflowing && isDesktop && (
            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={() => scrollBy(-300)}
                aria-label="Scroll rail left"
                className="w-9 h-9 inline-flex items-center justify-center border border-border bg-white hover:border-primary hover:text-primary transition-colors"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <button
                type="button"
                onClick={() => scrollBy(300)}
                aria-label="Scroll rail right"
                className="w-9 h-9 inline-flex items-center justify-center border border-border bg-white hover:border-primary hover:text-primary transition-colors"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          )}
        </div>

        {/* Rail */}
        <div
          ref={ref}
          className="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 [&::-webkit-scrollbar]:hidden [scrollbar-width:none]"
          data-testid={`explore-rail-${title.toLowerCase().replace(/\s+/g, "-")}`}
        >
          {items.map((card) => (
            <RailCard key={card.slug} card={card} />
          ))}
        </div>
      </div>
    </section>
  );
}

const RailCard: React.FC<{ card: ExploreCard }> = ({ card }) => {
  const hasImage = !!card.hero_image_url;
  return (
    <Link
      to={`/${card.slug}`}
      data-testid={`rail-card-${card.slug}`}
      className="group flex-shrink-0 w-[220px] md:w-[280px] snap-start bg-white border border-border hover:border-primary/40 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
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
        {card.category && (
          <span className="inline-block bg-primary text-white px-2 py-0.5 text-[8px] font-bold uppercase tracking-widest mb-2">
            {card.category.name}
          </span>
        )}
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
