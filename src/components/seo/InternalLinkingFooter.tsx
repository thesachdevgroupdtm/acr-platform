import { useQuery } from "@tanstack/react-query";
import { motion } from "motion/react";
import { Link } from "react-router-dom";
import { fetchExplorePayload } from "../../lib/api";

/**
 * Phase 4.5 — internal-linking footer for /:slug pages.
 *
 * Renders below RelatedArticles. Two clusters per spec #32:
 *   - Categories: every active category, links to /explore?category={slug}
 *   - Popular pages: top 12 by view_count, links to /:slug
 *
 * Reuses the cached Explore payload (60s server-side cache),
 * so this component is essentially free in network terms.
 */
export default function InternalLinkingFooter() {
  const query = useQuery({
    queryKey: ["explore-payload"],
    queryFn: ({ signal }) => fetchExplorePayload(signal),
    staleTime: 60 * 1000,
  });

  if (!query.data) return null;

  const { categories, rails } = query.data;
  const popular = rails.trending_searches.slice(0, 12);

  if (categories.length === 0 && popular.length === 0) return null;

  return (
    <motion.section
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-50px" }}
      transition={{ duration: 0.4 }}
      data-testid="internal-linking-footer"
      className="bg-neutral-900 text-white py-12 md:py-16"
    >
      <div className="site-container">
        <h2 className="section-heading !text-white mb-2">
          EXPLORE <span className="section-heading-accent">MORE.</span>
        </h2>
        <p className="text-xs text-neutral-400 mb-8">
          Browse by topic or jump into a popular guide.
        </p>

        <div className="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-10">
          {categories.length > 0 && (
            <div>
              <h3 className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-3">
                By Category
              </h3>
              <ul className="space-y-2">
                {categories.map((c) => (
                  <li key={c.slug}>
                    <Link
                      to={`/explore?category=${encodeURIComponent(c.slug)}`}
                      data-testid={`footer-cat-${c.slug}`}
                      className="text-sm text-neutral-200 hover:text-primary transition-colors inline-flex items-center gap-2"
                    >
                      <span className="w-1 h-1 bg-primary" /> {c.name}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {popular.length > 0 && (
            <div>
              <h3 className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-3">
                Popular Now
              </h3>
              <div className="flex flex-wrap gap-2">
                {popular.map((p) => (
                  <Link
                    key={p.slug}
                    to={`/${p.slug}`}
                    data-testid={`footer-link-${p.slug}`}
                    className="text-[11px] font-bold uppercase tracking-widest bg-white/5 text-neutral-200 hover:bg-primary hover:text-white px-3 py-1.5 transition-colors"
                  >
                    {slugToLabel(p.slug)}
                  </Link>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </motion.section>
  );
}

function slugToLabel(slug: string): string {
  return slug
    .split("-")
    .map((w) => (w.length <= 3 ? w.toUpperCase() : w.charAt(0).toUpperCase() + w.slice(1)))
    .join(" ");
}
