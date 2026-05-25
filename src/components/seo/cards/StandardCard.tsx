import type * as React from "react";
import { motion } from "motion/react";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import type { ExploreCardPayload } from "../../../lib/api";

interface Props {
  page: ExploreCardPayload;
  index: number;
}

/**
 * Phase 4.5b-polish — StandardCard.
 *
 * The default explore tile (renamed from ExploreCard).
 * Backwards-compatible: still emits `data-testid="explore-card-{slug}"`
 * so the Phase 4.5b/4.5b-fix Playwright specs continue passing.
 */
const StandardCard: React.FC<Props> = ({ page, index }) => {
  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, delay: Math.min(index, 6) * 0.04 }}
    >
      <Link
        to={`/${page.slug}`}
        data-testid={`explore-card-${page.slug}`}
        className="group block h-full bg-white border border-border p-6 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-0.5 transition-all duration-300"
      >
        {page.category && (
          <span className="inline-block bg-amber-50 text-amber-800 px-2 py-1 text-[8px] font-bold uppercase tracking-widest mb-3 group-hover:bg-primary group-hover:text-white transition-colors">
            {page.category}
          </span>
        )}

        <h2 className="text-base md:text-lg font-black uppercase tracking-tighter text-neutral-900 mb-3 leading-tight group-hover:text-primary transition-colors line-clamp-2">
          {page.title}
        </h2>

        {page.excerpt && (
          <p className="text-xs text-neutral-500 leading-relaxed line-clamp-3 mb-4">
            {page.excerpt}
          </p>
        )}

        {page.tags && page.tags.length > 0 && (
          <div className="flex flex-wrap gap-1 mb-4">
            {page.tags.slice(0, 3).map((tag) => (
              <span
                key={tag}
                className="text-[9px] bg-neutral-100 text-neutral-500 px-2 py-0.5 uppercase tracking-widest"
              >
                #{tag}
              </span>
            ))}
          </div>
        )}

        <span className="text-[10px] font-bold uppercase tracking-widest text-primary inline-flex items-center gap-1 group-hover:gap-2 transition-all">
          Read Article <ArrowRight className="w-3 h-3" />
        </span>
      </Link>
    </motion.div>
  );
};

export default StandardCard;
