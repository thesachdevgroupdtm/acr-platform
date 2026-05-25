import type * as React from "react";
import { Link } from "react-router-dom";
import { Clock } from "lucide-react";
import type { ExploreCard as ExploreCardPayload } from "../../../lib/api";

interface Props {
  /** Top 5 pages by view_count — caller passes the slice. */
  pages: ExploreCardPayload[];
}

/**
 * Phase 4.5.1 — sidebar "Top picks".
 *
 * Numbered list (01-05). Each row is fully linked to /:slug.
 * Caller (ExploreEditorial) passes a slice of
 * `payload.rails.most_read_week` so this widget makes no
 * additional network call.
 */
const TopPicksWidget: React.FC<Props> = ({ pages }) => {
  const top5 = pages.slice(0, 5);
  if (top5.length === 0) return null;

  return (
    <aside
      data-testid="top-picks-widget"
      className="bg-white border border-border p-5"
    >
      <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 mb-4">
        Top picks
      </h3>
      <ol className="space-y-3">
        {top5.map((p, i) => (
          <li key={p.slug}>
            <Link
              to={`/${p.slug}`}
              data-testid={`top-pick-${p.slug}`}
              className="group flex gap-3 items-start hover:bg-neutral-50 -mx-1 px-1 py-1 transition-colors"
            >
              <span className="text-2xl font-black text-primary/40 leading-none w-8 shrink-0 group-hover:text-primary transition-colors">
                {String(i + 1).padStart(2, "0")}
              </span>
              <div className="flex-1 min-w-0">
                <p className="text-xs font-bold text-neutral-900 leading-snug line-clamp-2 group-hover:text-primary transition-colors">
                  {p.title}
                </p>
                <span className="mt-1 inline-flex items-center gap-1 text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                  <Clock className="w-3 h-3" /> {p.reading_time_minutes} min
                </span>
              </div>
            </Link>
          </li>
        ))}
      </ol>
    </aside>
  );
};

export default TopPicksWidget;
