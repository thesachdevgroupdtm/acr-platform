import type * as React from "react";
import { motion } from "motion/react";
import { Link } from "react-router-dom";
import type { ExploreCardPayload } from "../../../lib/api";

interface Props {
  page: ExploreCardPayload;
  index?: number;
}

/**
 * Phase 4.5b-polish — CompactCard.
 *
 * Vertical mini-tile sized for horizontal rails. Image on top
 * (16:9 aspect), title + category chip below — no excerpt, no
 * "Read More" CTA (the whole card is the CTA). Optimised for
 * scrolling rails where cards compete for attention.
 */
const CompactCard: React.FC<Props> = ({ page, index = 0 }) => {
  const fallbackImage =
    "https://placehold.co/640x360/1a1a1a/f59e0b?text=ACR";
  const image = page.og_image || fallbackImage;

  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, delay: Math.min(index, 6) * 0.04 }}
      className="snap-start"
    >
      <Link
        to={`/${page.slug}`}
        data-testid={`compact-card-${page.slug}`}
        className="group block w-[240px] md:w-[260px] flex-shrink-0 bg-white border border-border hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 transition-all duration-300 overflow-hidden"
      >
        <div className="relative aspect-[16/9] bg-neutral-100 overflow-hidden">
          <img
            src={image}
            alt={page.title}
            className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            referrerPolicy="no-referrer"
            loading="lazy"
          />
        </div>
        <div className="p-4">
          {page.category && (
            <span className="inline-block bg-amber-50 text-amber-800 px-2 py-0.5 text-[8px] font-bold uppercase tracking-widest mb-2">
              {page.category}
            </span>
          )}
          <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 leading-tight line-clamp-2 group-hover:text-primary transition-colors">
            {page.title}
          </h3>
        </div>
      </Link>
    </motion.div>
  );
};

export default CompactCard;
