import { useNavigate } from "react-router-dom";
import { ChevronRight, ChevronLeft } from "lucide-react";

interface Crumb {
  label: string;
  to?: string;
}

interface Props {
  category?: string | null;
  title: string;
}

/**
 * Phase 4.5b-polish — article breadcrumbs.
 *
 * Desktop: full trail "Home › Explore › {Category} › {Title}".
 * Mobile: collapsed "‹ Back to Explore" chip — preserves real
 * estate where the trail would wrap clumsily on narrow screens.
 */
export default function SeoPageBreadcrumbs({ category, title }: Props) {
  const navigate = useNavigate();

  const crumbs: Crumb[] = [
    { label: "Home", to: "/" },
    { label: "Explore", to: "/explore" },
  ];
  if (category) {
    crumbs.push({
      label: category,
      to: `/explore?category=${encodeURIComponent(category)}`,
    });
  }
  crumbs.push({ label: title });

  return (
    <nav
      aria-label="Breadcrumb"
      data-testid="seo-page-breadcrumbs"
      className="bg-white border-b border-border"
    >
      <div className="site-container py-3">
        {/* Mobile: back-chip */}
        <button
          type="button"
          onClick={() => navigate("/explore")}
          className="md:hidden inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-widest text-neutral-600 hover:text-primary transition-colors"
        >
          <ChevronLeft className="w-3 h-3" />
          Back to Explore
        </button>

        {/* Desktop: full trail */}
        <ol className="hidden md:flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-neutral-500">
          {crumbs.map((c, i) => {
            const isLast = i === crumbs.length - 1;
            return (
              <li key={`${c.label}-${i}`} className="flex items-center gap-2">
                {c.to && !isLast ? (
                  <button
                    type="button"
                    onClick={() => navigate(c.to!)}
                    className="hover:text-primary transition-colors"
                  >
                    {c.label}
                  </button>
                ) : (
                  <span
                    className={
                      isLast
                        ? "text-neutral-900 truncate max-w-[40ch]"
                        : ""
                    }
                  >
                    {c.label}
                  </span>
                )}
                {!isLast && <ChevronRight className="w-3 h-3 text-neutral-300" />}
              </li>
            );
          })}
        </ol>
      </div>
    </nav>
  );
}
