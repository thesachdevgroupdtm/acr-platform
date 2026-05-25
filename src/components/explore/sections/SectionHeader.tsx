import type * as React from "react";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";

interface Props {
  /**
   * Section title. Two modes:
   *  - String  → auto-split into dual-color via Phase 4.7 pattern
   *              (last word becomes `section-heading-accent` + period).
   *  - JSX    → render verbatim (caller supplies their own
   *              `section-heading-accent` span).
   */
  title: React.ReactNode;
  viewAllHref?: string;
  viewAllTestId?: string;
  subhead?: string;
  onViewAllClick?: () => void;
}

/**
 * Phase 4.5.7 — shared section header used by every dedicated
 * section component.
 * Phase 4.7 — H2 typography uses canonical `.section-heading`
 * utility. When `title` is a string, the last whitespace-separated
 * token becomes the primary-blue accent with a trailing period;
 * `Brand Service` → `BRAND <span>SERVICE.</span>`. Callers can pass
 * a JSX fragment to override the auto-split when needed.
 */
export default function SectionHeader({
  title,
  viewAllHref,
  viewAllTestId,
  subhead,
  onViewAllClick,
}: Props) {
  return (
    <div className="flex items-end justify-between mb-6 pb-3 border-b-2 border-primary">
      <div>
        <h2 className="section-heading">
          {typeof title === "string" ? renderDualColor(title) : title}
        </h2>
        {subhead && (
          <p className="text-xs text-neutral-500 mt-1">{subhead}</p>
        )}
      </div>
      {viewAllHref && (
        <Link
          to={viewAllHref}
          data-testid={viewAllTestId ?? "section-view-all"}
          onClick={onViewAllClick}
          className="text-[10px] font-bold uppercase tracking-widest text-primary hover:underline inline-flex items-center gap-1 group"
        >
          View All <ArrowRight className="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
        </Link>
      )}
    </div>
  );
}

/**
 * Apply the Phase 4.7 dual-color pattern to a plain-string title.
 * Splits on the LAST whitespace; left side stays neutral, right
 * side becomes the primary-blue accent + trailing period.
 *
 * Edge cases:
 *   - Empty / undefined → render empty
 *   - Single-word title ("News") → entire word becomes the accent
 *   - Title that already ends in a period → keep as-is
 */
function renderDualColor(text: string): React.ReactNode {
  const trimmed = text.trim();
  if (!trimmed) return null;
  const lastSpace = trimmed.lastIndexOf(" ");
  let head = "";
  let tail = trimmed;
  if (lastSpace !== -1) {
    head = trimmed.slice(0, lastSpace);
    tail = trimmed.slice(lastSpace + 1);
  }
  const accent = tail.endsWith(".") ? tail : `${tail}.`;
  return (
    <>
      {head && <>{head} </>}
      <span className="section-heading-accent">{accent}</span>
    </>
  );
}
