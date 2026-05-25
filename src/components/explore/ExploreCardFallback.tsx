import {
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
} from "lucide-react";
import type { ComponentType, SVGProps } from "react";

/**
 * Phase 4.5.1 — no-image card fallback.
 *
 * Replaces the previous "giant text fill" rendering (where a card
 * without hero_image_url showed an h1-sized category name). New
 * design: dark slate gradient + small heroicon top-left + category
 * badge + restrained title + subtle "ACR" watermark bottom-right.
 *
 * Color palette per Phase 4.5 D-4.5-11: ACR primary blue +
 * neutral-900 graphite + white. No fashion accents.
 */

interface Props {
  category?: { slug: string | null; name: string } | null;
  iconName?: string | null;
  title: string;
  className?: string;
}

/**
 * Heroicon-name → lucide-react component map. Spec D-4.5.1-3 says
 * the icon comes from `seo_page_categories.icon_name`. Phase 4.5
 * seeded categories with names like 'tag', 'wrench-screwdriver',
 * etc. — map them to the closest lucide equivalents.
 */
type IconCmp = ComponentType<SVGProps<SVGSVGElement>>;

const ICON_MAP: Record<string, IconCmp> = {
  "tag":                 Tag,
  "map-pin":             MapPin,
  "wrench-screwdriver":  Wrench,
  "wrench":              Wrench,
  "shield-check":        ShieldCheck,
  "currency-rupee":      IndianRupee,
  "arrows-right-left":   ArrowLeftRight,
  "newspaper":           Newspaper,
  "paint-brush":         Paintbrush,
  "sparkles":            Sparkles,
  "bolt":                Bolt,
  "sun":                 Sun,
};

const DEFAULT_ICON = BookOpen;

export default function ExploreCardFallback({
  category,
  iconName,
  title,
  className = "",
}: Props) {
  const Icon = (iconName && ICON_MAP[iconName]) || DEFAULT_ICON;

  return (
    <div
      data-testid="explore-card-fallback"
      className={`relative w-full h-full bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 overflow-hidden ${className}`}
    >
      {/* Subtle ACR-blue accent bleed in the top-left corner */}
      <div className="pointer-events-none absolute -top-16 -left-16 w-48 h-48 rounded-full bg-primary/20 blur-3xl" />

      {/* Top-left: heroicon + category badge */}
      <div className="absolute top-4 left-4 right-4 flex items-start gap-3 z-10">
        <span className="inline-flex items-center justify-center w-8 h-8 bg-primary/15 text-primary rounded-sm shrink-0">
          <Icon className="w-5 h-5" aria-hidden="true" />
        </span>
        {category?.name && (
          <span className="inline-block bg-primary text-white px-2 py-1 text-[8px] font-bold uppercase tracking-widest">
            {category.name}
          </span>
        )}
      </div>

      {/* Bottom: restrained title overlay */}
      <div className="absolute inset-x-0 bottom-0 p-4 md:p-5 z-10 bg-gradient-to-t from-slate-950/80 to-transparent">
        <p className="text-white text-sm md:text-base font-medium leading-snug line-clamp-3">
          {title}
        </p>
      </div>

      {/* ACR watermark, low opacity, bottom-right */}
      <span className="pointer-events-none absolute bottom-2 right-3 text-[10px] font-black uppercase tracking-widest text-white/15 z-0">
        ACR
      </span>
    </div>
  );
}
