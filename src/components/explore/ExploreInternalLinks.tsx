import { Link } from "react-router-dom";
import {
  ArrowRight,
  ChevronRight,
  Tag,
  MapPin,
  Wrench,
  ShieldCheck,
  IndianRupee,
  ArrowLeftRight,
  Newspaper,
  Paintbrush,
  Sparkles,
  Bolt,
  Sun,
  BookOpen,
  Lightbulb,
  Scale,
  AlertTriangle,
} from "lucide-react";
import type { ComponentType, SVGProps } from "react";
import type { ExploreCategoryBlock } from "../../lib/api";

interface Props {
  categories: ExploreCategoryBlock[];
  popularSlugs: string[];
}

/**
 * Phase 4.5 — Explore footer internal-link block.
 * Phase 4.5.4 — rewrite to 3-column rich footer (D-4.5.4-3):
 *   col 1 (4/12)  Browse by Category — icon + name + chevron rows
 *   col 2 (5/12)  Popular Searches  — chip cloud
 *   col 3 (3/12)  Why ACR? stats card with CTA
 *
 * Mobile: 3 columns stack. Quick-stats card stays full-width.
 *
 * Heroicons-from-spec are realised via lucide-react (the project's
 * existing icon library) since `@heroicons/react` is not installed
 * and HARD CONSTRAINTS forbid new packages.
 *
 * SEO benefit (carryforward from Phase 4.5): each /explore visit
 * still emits ~25-40 internal links to indexed targets.
 */

type IconCmp = ComponentType<SVGProps<SVGSVGElement>>;

/**
 * icon_name (string from seo_page_categories.icon_name) → lucide
 * component. Mirrors the map in ExploreCardFallback.tsx — the
 * categories table populates these as heroicon-style names.
 */
const ICON_BY_NAME: Record<string, IconCmp> = {
  "tag":                Tag,
  "map-pin":            MapPin,
  "wrench-screwdriver": Wrench,
  "wrench":             Wrench,
  "shield-check":       ShieldCheck,
  "currency-rupee":     IndianRupee,
  "arrows-right-left":  ArrowLeftRight,
  "newspaper":          Newspaper,
  "paint-brush":        Paintbrush,
  "sparkles":           Sparkles,
  "bolt":               Bolt,
  "sun":                Sun,
  "book-open":          BookOpen,
  "light-bulb":         Lightbulb,
  "scale":              Scale,
  "exclamation-triangle": AlertTriangle,
};

/** Slug-based fallback when `icon_name` is null (per D-4.5.4-6). */
const ICON_BY_SLUG: Record<string, IconCmp> = {
  "brand-service":     Wrench,
  "brand-services":    Wrench,
  "city-service":      MapPin,
  "city-services":     MapPin,
  "service-guide":     BookOpen,
  "cost-guide":        IndianRupee,
  "service-cost":      IndianRupee,
  "maintenance-tips":  Lightbulb,
  "comparison":        Scale,
  "emergency":         AlertTriangle,
  "ac-repair":         Sun,
  "battery":           Bolt,
  "denting-painting":  Paintbrush,
  "luxury-cars":       Sparkles,
  "insurance-guides":  ShieldCheck,
  "news":              Newspaper,
};

function getCategoryIcon(slug: string, iconName: string | null): IconCmp {
  if (iconName && ICON_BY_NAME[iconName]) return ICON_BY_NAME[iconName];
  if (ICON_BY_SLUG[slug]) return ICON_BY_SLUG[slug];
  return Tag;
}

export default function ExploreInternalLinks({ categories, popularSlugs }: Props) {
  if (categories.length === 0 && popularSlugs.length === 0) return null;

  const visibleCats   = categories.slice(0, 8);
  const visibleChips  = popularSlugs.slice(0, 15);

  return (
    <section
      data-section="internal-links"
      className="bg-neutral-900 text-white py-16 md:py-20"
    >
      <div className="site-container">
        <h2 className="section-heading !text-white mb-2">
          EXPLORE <span className="section-heading-accent">MORE.</span>
        </h2>
        <p className="text-xs text-neutral-400 mb-10">
          Browse by topic or jump into a popular guide.
        </p>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
          {/* ── Column 1: Browse by Category ── */}
          {visibleCats.length > 0 && (
            <div
              data-testid="footer-categories"
              className="lg:col-span-4"
            >
              <h3 className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-4">
                Browse by Category
              </h3>
              <ul className="space-y-1">
                {visibleCats.map((c) => {
                  const Icon = getCategoryIcon(c.slug, c.icon_name);
                  return (
                    <li key={c.slug}>
                      <Link
                        to={`/explore?category=${encodeURIComponent(c.slug)}`}
                        data-testid={`internal-cat-${c.slug}`}
                        className="group flex items-center justify-between p-3 rounded-md hover:bg-neutral-800 transition-colors"
                      >
                        <span className="flex items-center gap-3">
                          <Icon className="w-5 h-5 text-primary" aria-hidden="true" />
                          <span className="text-white text-sm font-medium">
                            {c.name}
                          </span>
                        </span>
                        <ChevronRight className="w-4 h-4 text-neutral-500 group-hover:text-primary group-hover:translate-x-1 transition-all" />
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>
          )}

          {/* ── Column 2: Popular Searches ── */}
          {visibleChips.length > 0 && (
            <div
              data-testid="footer-popular"
              className="lg:col-span-5"
            >
              <h3 className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-4">
                Popular Searches
              </h3>
              <div className="flex flex-wrap gap-2">
                {visibleChips.map((slug) => (
                  <Link
                    key={slug}
                    to={`/${slug}`}
                    data-testid={`internal-link-${slug}`}
                    className="px-4 py-2 rounded-md bg-neutral-800 text-neutral-200 text-xs font-medium hover:bg-neutral-700 hover:text-white border border-transparent hover:border-primary/60 transition-colors"
                  >
                    {slugToLabel(slug)}
                  </Link>
                ))}
              </div>
            </div>
          )}

          {/* ── Column 3: Why ACR? quick-stats card ── */}
          <div
            data-testid="footer-stats"
            className="lg:col-span-3"
          >
            <div className="bg-neutral-800 rounded-xl p-6 h-full flex flex-col">
              <h3 className="text-base font-black uppercase tracking-tighter text-white mb-5">
                Why ACR?
              </h3>
              <div className="space-y-4 flex-1">
                <Stat number="4" label="Centres across Delhi NCR" />
                <Stat number="1M+" label="Cars serviced" />
                <Stat number="100%" label="Self-owned multi-brand network" />
              </div>
              <Link
                to="/contact"
                data-testid="footer-cta-estimate"
                className="btn-ink btn-ink-primary mt-5 w-full justify-center px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest"
              >
                Get Estimate <ArrowRight className="w-4 h-4 btn-arrow" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function Stat({ number, label }: { number: string; label: string }) {
  return (
    <div>
      <div className="text-2xl font-black text-primary leading-none mb-1">
        {number}
      </div>
      <div className="text-[11px] text-neutral-400 uppercase tracking-wide leading-snug">
        {label}
      </div>
    </div>
  );
}

function slugToLabel(slug: string): string {
  return slug
    .split("-")
    .map((w) => (w.length <= 3 ? w.toUpperCase() : w.charAt(0).toUpperCase() + w.slice(1)))
    .join(" ");
}
