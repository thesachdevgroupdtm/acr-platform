import type * as React from "react";
import { motion } from "motion/react";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import type { ExploreCardPayload } from "../../../lib/api";

interface Props {
  page: ExploreCardPayload;
  index?: number;
}

/**
 * Phase 4.5b-polish — HorizontalCard.
 *
 * Image-left content-right tile sized for the Service Guides
 * section and the Continue Reading rail on /:slug. On mobile,
 * collapses to image-on-top + content-below.
 */
const HorizontalCard: React.FC<Props> = ({ page, index = 0 }) => {
  const fallbackImage =
    "https://placehold.co/640x480/1a1a1a/f59e0b?text=ACR";
  const image = page.og_image || fallbackImage;

  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3, delay: Math.min(index, 4) * 0.06 }}
    >
      <Link
        to={`/${page.slug}`}
        data-testid={`horizontal-card-${page.slug}`}
        className="group flex flex-col md:flex-row bg-white border border-border hover:border-primary/40 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
      >
        <div className="relative w-full md:w-2/5 aspect-[4/3] md:aspect-auto bg-neutral-100 overflow-hidden flex-shrink-0">
          <img
            src={image}
            alt={page.title}
            className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            referrerPolicy="no-referrer"
            loading="lazy"
          />
        </div>

        <div className="flex-1 p-5 md:p-6 flex flex-col justify-center">
          {page.category && (
            <span className="inline-block self-start bg-amber-50 text-amber-800 px-2 py-1 text-[8px] font-bold uppercase tracking-widest mb-3">
              {page.category}
            </span>
          )}

          <h3 className="text-base md:text-lg font-black uppercase tracking-tighter text-neutral-900 mb-2 leading-tight group-hover:text-primary transition-colors line-clamp-2">
            {page.title}
          </h3>

          {page.excerpt && (
            <p className="text-xs text-neutral-500 leading-relaxed line-clamp-2 mb-3">
              {page.excerpt}
            </p>
          )}

          <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-widest text-primary group-hover:gap-2 transition-all">
            Read More <ArrowRight className="w-3 h-3" />
          </span>
        </div>
      </Link>
    </motion.div>
  );
};

export default HorizontalCard;
