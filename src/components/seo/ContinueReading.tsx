import { useQuery } from "@tanstack/react-query";
import { motion } from "motion/react";
import { fetchExplore, type ExploreCardPayload } from "../../lib/api";
import HorizontalCard from "./cards/HorizontalCard";

interface Props {
  currentSlug: string;
  category?: string | null;
}

/**
 * Phase 4.5b-polish — "Continue Reading" rail.
 *
 * Distinct from RelatedArticlesGrid:
 *   - Different layout (HorizontalCards stacked, not 3-column tiles)
 *   - Different selection (newest in same category, not the
 *     category+tag relevance score from getRelatedPages)
 *   - Position (below RelatedArticlesGrid, immediately above
 *     the footer) so a reader who's reached the bottom has a
 *     fallback path to keep going.
 */
export default function ContinueReading({ currentSlug, category }: Props) {
  const query = useQuery({
    queryKey: ["continue-reading", category],
    queryFn: ({ signal }) =>
      fetchExplore(
        category ? { category, per_page: 4 } : { per_page: 4 },
        signal,
      ),
    staleTime: 5 * 60 * 1000,
    enabled: !!currentSlug,
  });

  const pages = (query.data?.data ?? []).filter(
    (p: ExploreCardPayload) => p.slug !== currentSlug,
  );
  if (pages.length === 0) return null;

  return (
    <motion.section
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-50px" }}
      transition={{ duration: 0.4 }}
      data-testid="continue-reading"
      className="bg-neutral-50 py-12 md:py-16"
    >
      <div className="site-container">
        <h2 className="section-heading mb-2">
          CONTINUE <span className="section-heading-accent">READING.</span>
        </h2>
        <p className="text-xs text-neutral-500 mb-8">
          More from the same category.
        </p>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          {pages.slice(0, 3).map((p, i) => (
            <HorizontalCard key={p.id} page={p} index={i} />
          ))}
        </div>
      </div>
    </motion.section>
  );
}
