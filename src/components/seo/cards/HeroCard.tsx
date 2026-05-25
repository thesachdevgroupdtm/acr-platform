import type * as React from "react";
import { motion } from "motion/react";
import { Link } from "react-router-dom";
import { ArrowRight, Clock } from "lucide-react";
import type { ExploreCardPayload } from "../../../lib/api";

interface Props {
  page: ExploreCardPayload;
}

/**
 * Phase 4.5b-polish — HeroCard.
 *
 * Full-width featured tile at the top of /explore. Image fills
 * the card; dark gradient overlay anchors the title text. Title
 * scales 2xl → 4xl across breakpoints. One per page (the most
 * recent featured article).
 *
 * Renders gracefully without an og_image (falls back to a dark
 * solid).
 *
 * data-testid="hero-card" and `hero-card-{slug}` so tests can
 * target both the section AND the specific page rendered.
 */
const HeroCard: React.FC<Props> = ({ page }) => {
  const fallbackImage =
    "https://placehold.co/1600x900/1a1a1a/f59e0b?text=ACR";
  const image = page.og_image || fallbackImage;

  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, ease: "easeOut" }}
      data-testid="hero-card"
    >
      <Link
        to={`/${page.slug}`}
        data-testid={`hero-card-${page.slug}`}
        className="group relative block overflow-hidden bg-neutral-900 aspect-[16/9] sm:aspect-[16/8] md:aspect-[16/7]"
      >
        <img
          src={image}
          alt={page.title}
          className="absolute inset-0 w-full h-full object-cover opacity-70 group-hover:opacity-80 group-hover:scale-105 transition-all duration-700"
          referrerPolicy="no-referrer"
          loading="eager"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/60 to-transparent pointer-events-none" />

        <div className="relative h-full flex flex-col justify-end p-6 md:p-10 lg:p-14 text-white">
          <div className="flex items-center gap-3 mb-4">
            <span className="inline-block bg-primary text-white px-3 py-1 text-[10px] font-bold uppercase tracking-widest">
              Featured
            </span>
            {page.category && (
              <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-300">
                {page.category}
              </span>
            )}
          </div>

          <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-tighter leading-none max-w-3xl mb-4 group-hover:text-amber-200 transition-colors">
            {page.title}
          </h1>

          {page.excerpt && (
            <p className="text-sm md:text-base text-neutral-300 leading-relaxed max-w-2xl mb-6 line-clamp-2">
              {page.excerpt}
            </p>
          )}

          <div className="flex items-center gap-4">
            <span className="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-white group-hover:gap-3 transition-all">
              Read Article <ArrowRight className="w-4 h-4" />
            </span>
            <span className="hidden md:inline-flex items-center gap-1 text-[10px] text-neutral-400 uppercase tracking-widest">
              <Clock className="w-3 h-3" /> 5 min read
            </span>
          </div>
        </div>
      </Link>
    </motion.div>
  );
};

export default HeroCard;
