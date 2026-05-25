import type * as React from "react";
import { Link } from "react-router-dom";
import { TrendingUp } from "lucide-react";
import type { ExplorePayload } from "../../../lib/api";

interface Props {
  payload: ExplorePayload;
}

/**
 * Phase 4.5.1 — sidebar "Related topics".
 *
 * Lists 5 most-trending categories (from payload.categories,
 * which is already ordered by category position with non-empty
 * blocks). Click a topic → /explore?category={slug}.
 */
const RelatedTopicsWidget: React.FC<Props> = ({ payload }) => {
  const topics = payload.categories.slice(0, 5);
  if (topics.length === 0) return null;

  return (
    <aside
      data-testid="related-topics-widget"
      className="bg-white border border-border p-5"
    >
      <div className="flex items-center gap-2 mb-3">
        <TrendingUp className="w-4 h-4 text-primary" />
        <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900">
          Related topics
        </h3>
      </div>
      <ul className="space-y-2">
        {topics.map((c) => (
          <li key={c.slug}>
            <Link
              to={`/explore?category=${encodeURIComponent(c.slug)}`}
              data-testid={`related-topic-${c.slug}`}
              className="text-xs text-neutral-700 hover:text-primary transition-colors inline-flex items-center gap-2"
            >
              <span className="w-1 h-1 bg-primary" /> {c.name}
            </Link>
          </li>
        ))}
      </ul>
    </aside>
  );
};

export default RelatedTopicsWidget;
