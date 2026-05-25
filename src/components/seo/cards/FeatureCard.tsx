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
 * Phase 4.5b-polish — FeatureCard.
 *
 * 4:3 image-on-top tile with content overlaid in the lower
 * gradient. Smaller than HeroCard but visually richer than
 * StandardCard. Used as the lead tile in Section 3 (Trending)
 * and Section 4 (By Brand).
 */
const FeatureCard: React.FC<Props> = ({ page, index = 0 }) => {
  const fallbackImage =
    "https://placehold.co/800x600/1a1a1a/f59e0b?text=ACR";
  const image = page.og_image || fallbackImage;

  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.35, delay: Math.min(index, 4) * 0.05 }}
    >
      <Link
        to={`/${page.slug}`}
        data-testid={`feature-card-${page.slug}`}
        className="group relative block overflow-hidden bg-neutral-900 aspect-[4/3] h-full"
      >
        <img
          src={image}
          alt={page.title}
          className="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-90 group-hover:scale-105 transition-all duration-700"
          referrerPolicy="no-referrer"
          loading="lazy"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/40 to-transparent pointer-events-none" />

        <div className="relative h-full flex flex-col justify-end p-6 md:p-8 text-white">
          {page.category && (
            <span className="inline-block self-start bg-primary text-white px-2 py-1 text-[8px] font-bold uppercase tracking-widest mb-3">
              {page.category}
            </span>
          )}

          <h2 className="text-lg md:text-xl lg:text-2xl font-black uppercase tracking-tighter leading-tight mb-2 group-hover:text-amber-200 transition-colors line-clamp-3">
            {page.title}
          </h2>

          {page.excerpt && (
            <p className="text-xs md:text-sm text-neutral-300 leading-relaxed line-clamp-2 mb-4">
              {page.excerpt}
            </p>
          )}

          <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-widest text-white group-hover:gap-2 transition-all">
            Read Article <ArrowRight className="w-3 h-3" />
          </span>
        </div>
      </Link>
    </motion.div>
  );
};

export default FeatureCard;
