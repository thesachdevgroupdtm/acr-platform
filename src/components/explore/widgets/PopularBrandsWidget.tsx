import type * as React from "react";
import { Link } from "react-router-dom";
import type { ExplorePayload } from "../../../lib/api";

interface Props {
  payload: ExplorePayload;
}

/**
 * Phase 4.5.1 — sidebar "Popular brands".
 *
 * Derives brand chips from the page payload — finds the
 * Brand Service category block and lists its items as chips
 * pointing to /explore?category=brand-service. If no brand
 * pages exist, the widget renders nothing per spec D-4.5.1-6.
 *
 * No additional network call.
 */
const PopularBrandsWidget: React.FC<Props> = ({ payload }) => {
  const brandBlock = payload.categories.find((c) => c.slug === "brand-service");
  if (!brandBlock || brandBlock.items.length === 0) {
    return null;
  }

  // Pull at most 12 chips from the brand block (featured + items
  // de-duplicated by title's first word — typically the brand
  // name).
  const seen = new Set<string>();
  const chips: { slug: string; label: string }[] = [];
  const consider = [brandBlock.featured, ...brandBlock.items];

  for (const card of consider) {
    if (chips.length >= 12) break;
    // The first word of a Brand Service title is usually the
    // brand name (Audi, BMW, Mercedes, …) — lift it as the chip
    // label.
    const label = (card.title.split(" ")[0] ?? card.title).trim();
    if (!label || seen.has(label.toLowerCase())) continue;
    seen.add(label.toLowerCase());
    chips.push({ slug: card.slug, label });
  }

  if (chips.length === 0) return null;

  return (
    <aside
      data-testid="popular-brands-widget"
      className="bg-white border border-border p-5"
    >
      <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 mb-3">
        Popular brands
      </h3>
      <div className="flex flex-wrap gap-2">
        {chips.map((c) => (
          <Link
            key={c.slug}
            to="/explore?category=brand-service"
            data-testid={`brand-chip-${c.slug}`}
            className="text-[10px] font-bold uppercase tracking-widest bg-neutral-100 text-neutral-700 hover:bg-primary hover:text-white px-3 py-1.5 transition-colors"
          >
            {c.label}
          </Link>
        ))}
      </div>
    </aside>
  );
};

export default PopularBrandsWidget;
