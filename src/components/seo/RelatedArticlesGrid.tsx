import { motion } from "motion/react";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import type { SeoPageRelated } from "../../lib/api";

interface Props {
  related: SeoPageRelated[];
}

/**
 * Phase 4.5b-fix — premium related-articles grid.
 *
 * Mirrors the CmsPage 3-column "service tiles" pattern: bordered
 * cards, hover-shadow, primary-color hover state on the title +
 * arrow icon. Each card is clickable in its entirety (Link wraps
 * the whole tile) so the hit target is generous.
 */
export default function RelatedArticlesGrid({ related }: Props) {
  if (related.length === 0) {
    return null;
  }

  return (
    <section className="mt-20 pt-16 border-t border-border">
      <div className="flex items-baseline justify-between mb-10">
        <h2 className="section-heading">
          RELATED <span className="section-heading-accent">ARTICLES.</span>
        </h2>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {related.map((rp, i) => (
          <motion.div
            key={rp.slug}
            initial={{ opacity: 0, y: 12 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, amount: 0.3 }}
            transition={{ duration: 0.3, delay: i * 0.06 }}
          >
            <Link
              to={`/${rp.slug}`}
              className="group block h-full bg-white border border-border p-6 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-0.5 transition-all duration-300"
            >
              {rp.category && (
                <span className="inline-block bg-neutral-100 text-neutral-600 px-2 py-1 text-[8px] font-bold uppercase tracking-widest mb-3 group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                  {rp.category}
                </span>
              )}
              <h3 className="text-base md:text-lg font-black uppercase tracking-tighter text-neutral-900 mb-3 leading-tight group-hover:text-primary transition-colors line-clamp-2">
                {rp.title}
              </h3>
              {rp.excerpt && (
                <p className="text-xs text-neutral-500 leading-relaxed line-clamp-3 mb-4">
                  {rp.excerpt}
                </p>
              )}
              <span className="text-[10px] font-bold uppercase tracking-widest text-primary inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                Read More <ArrowRight className="w-3 h-3" />
              </span>
            </Link>
          </motion.div>
        ))}
      </div>
    </section>
  );
}
