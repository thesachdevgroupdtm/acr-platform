import { ChevronLeft } from "lucide-react";
import { Link } from "react-router-dom";

/**
 * Lightweight breadcrumb. Rendered as a thin strip above the
 * PageBanner (between the main header and the banner) per operator
 * direction. Semantic `<nav><ol>` with `aria-current="page"` on the
 * final crumb.
 *
 * Each crumb supports either `href` (react-router `<Link>`) or
 * `onClick` (button) for backward compatibility with legacy consumers
 * that still pass `onClick: () => navigate(...)` (CmsPage,
 * SeoPageView). New consumers should prefer `href`.
 *
 * Two mobile modes:
 *   - 'full' (default): renders the full chain on every viewport.
 *   - 'back':           desktop full chain; mobile collapses to a
 *                       single "‹ Parent" link using items[n-2].
 */

export interface BreadcrumbItem {
  label: string;
  href?: string;
  onClick?: () => void;
}

interface BreadcrumbProps {
  items: BreadcrumbItem[];
  mobileMode?: "full" | "back";
  className?: string;
}

export default function Breadcrumb({
  items,
  mobileMode = "full",
  className = "",
}: BreadcrumbProps) {
  if (!items || items.length === 0) return null;

  const lastIdx = items.length - 1;
  const mobileBackTarget = items[items.length - 2];

  return (
    <nav aria-label="Breadcrumb" className={`text-sm ${className}`}>
      <ol
        className={`items-center gap-2 text-neutral-600 ${
          mobileMode === "back" ? "hidden lg:flex" : "flex flex-wrap"
        }`}
      >
        {items.map((item, idx) => {
          const isLast = idx === lastIdx;
          return (
            <li key={idx} className="flex items-center gap-2">
              {!isLast && item.href ? (
                <Link
                  to={item.href}
                  className="hover:text-primary transition-colors"
                >
                  {item.label}
                </Link>
              ) : !isLast && item.onClick ? (
                <button
                  type="button"
                  onClick={item.onClick}
                  className="hover:text-primary transition-colors cursor-pointer"
                >
                  {item.label}
                </button>
              ) : (
                <span
                  className={isLast ? "text-neutral-900 font-medium" : ""}
                  aria-current={isLast ? "page" : undefined}
                >
                  {item.label}
                </span>
              )}
              {!isLast && (
                <span
                  className="text-neutral-300 select-none"
                  role="presentation"
                  aria-hidden="true"
                >
                  ›
                </span>
              )}
            </li>
          );
        })}
      </ol>

      {mobileMode === "back" && mobileBackTarget && (
        <div className="lg:hidden">
          {mobileBackTarget.href ? (
            <Link
              to={mobileBackTarget.href}
              className="inline-flex items-center gap-1 text-neutral-600 hover:text-primary transition-colors min-h-[36px]"
            >
              <ChevronLeft className="w-4 h-4" />
              {mobileBackTarget.label}
            </Link>
          ) : mobileBackTarget.onClick ? (
            <button
              type="button"
              onClick={mobileBackTarget.onClick}
              className="inline-flex items-center gap-1 text-neutral-600 hover:text-primary transition-colors min-h-[36px]"
            >
              <ChevronLeft className="w-4 h-4" />
              {mobileBackTarget.label}
            </button>
          ) : (
            <span className="inline-flex items-center gap-1 text-neutral-600 min-h-[36px]">
              <ChevronLeft className="w-4 h-4" />
              {mobileBackTarget.label}
            </span>
          )}
        </div>
      )}
    </nav>
  );
}
