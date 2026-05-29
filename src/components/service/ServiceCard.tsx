import type * as React from "react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  ArrowRight,
  Check,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Clock,
  ShoppingCart,
} from "lucide-react";
import ServiceMetaRow from "../ServiceMetaRow";
import ExploreCardFallback from "../explore/ExploreCardFallback";

/**
 * Phase 2c (D-2c-4) — the ONE shared service card.
 *
 * Extracted verbatim from the inline `<article>` that lived in
 * ServiceCategory.tsx (Layer 2). Now rendered identically by:
 *   - Layer 1 (/services active-category tabs),
 *   - Layer 2 (/category/:slug catalog).
 *
 * GoMechanic-style horizontal card, ACR skin:
 *   image/fallback + duration pill | title + ServiceMetaRow + inclusions
 *   preview (≤ the API's labels) + "+N more · View All" | price 4-state + CTA.
 *
 * The price 4-state + cart toggle LOGIC is unchanged and stays in the
 * parent (D-2c-5): the parent computes `showPrice / pricingLoading / price /
 * inCart / justAdded` and passes them in. This component is presentational
 * and emits `onAdd / onRemove / onViewDetail`.
 */

export interface ServiceCardServiceData {
  id: number;
  slug: string;
  title: string;
  image?: string | null;
  time_takes?: string | number | null;
  time_unit?: string | null;
  warrenty_info?: string | null;
  interval_info?: string | null;
  inclusions_preview?: { labels: string[]; total: number };
}

export interface ServiceCardProps {
  service: ServiceCardServiceData;
  categorySlug: string;
  categoryTitle: string;
  fallbackIcon: React.ComponentType<{ className?: string }>;
  /** Price 4-state inputs — computed by the parent (logic UNCHANGED). */
  vehicleSelected: boolean;
  showPrice: boolean;
  pricingLoading: boolean;
  price: number | null;
  /** Cart toggle state + handlers. */
  inCart: boolean;
  justAdded: boolean;
  onAdd: () => void;
  onRemove: () => void;
  /** Navigate to this service's detail page. */
  onViewDetail: () => void;
}

const ServiceCard: React.FC<ServiceCardProps> = ({
  service,
  categorySlug,
  categoryTitle,
  fallbackIcon: FallbackIcon,
  vehicleSelected,
  showPrice,
  pricingLoading,
  price,
  inCart,
  justAdded,
  onAdd,
  onRemove,
  onViewDetail,
}) => {
  const preview = service.inclusions_preview ?? { labels: [], total: 0 };
  const durationLabel =
    service.time_takes != null && String(service.time_takes).trim() !== ""
      ? `${service.time_takes}${service.time_unit ? " " + service.time_unit : ""}`
      : null;

  // Phase 2e (D-2e-4) — in-place inclusions expand. The preview now ships ALL
  // labels (lean), so "+N more · View All" toggles the rest inline (no detail
  // navigation). Collapsed shows the first COLLAPSED; the title still routes.
  const [expanded, setExpanded] = useState(false);
  const COLLAPSED = 4;
  const labels = preview.labels;
  const head = labels.slice(0, COLLAPSED);
  const tail = labels.slice(COLLAPSED);

  return (
    <article className="bg-white border border-border hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 transition-all flex flex-col sm:flex-row overflow-hidden">
      {/* Image / fallback — images are 0% populated (D-2-4). */}
      <div className="relative w-full sm:w-44 lg:w-52 shrink-0 aspect-[16/10] sm:aspect-auto bg-neutral-900">
        {service.image ? (
          <img
            src={service.image}
            alt={service.title}
            loading="lazy"
            referrerPolicy="no-referrer"
            className="absolute inset-0 w-full h-full object-cover"
          />
        ) : (
          <ExploreCardFallback
            category={{ slug: categorySlug, name: categoryTitle }}
            icon={FallbackIcon}
            title={service.title}
          />
        )}
        {durationLabel && (
          <span className="absolute bottom-2 left-2 z-10 inline-flex items-center gap-1 bg-neutral-950/70 text-white px-2 py-1 text-[10px] font-bold uppercase tracking-widest">
            <Clock className="w-3 h-3" /> {durationLabel}
          </span>
        )}
      </div>

      {/* Body — title + meta row + inclusions preview */}
      <div className="flex-1 min-w-0 p-4 sm:p-5 flex flex-col">
        <button onClick={onViewDetail} className="text-left">
          <h3 className="text-sm sm:text-base font-black uppercase tracking-tighter text-neutral-900 hover:text-primary transition-colors">
            {service.title}
          </h3>
        </button>

        <ServiceMetaRow
          className="mt-1.5"
          timeTakes={service.time_takes}
          timeUnit={service.time_unit}
          warranty={service.warrenty_info}
          interval={service.interval_info}
        />

        {head.length > 0 && (
          <ul className="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5">
            {head.map((label, i) => (
              <li
                key={i}
                className="flex items-start gap-1.5 text-xs text-neutral-700"
              >
                <Check className="w-3.5 h-3.5 text-primary shrink-0 mt-0.5" />
                <span className="truncate">{label}</span>
              </li>
            ))}
          </ul>
        )}

        {/* Remaining inclusions — revealed IN PLACE on expand (D-2e-4). */}
        <AnimatePresence initial={false}>
          {expanded && tail.length > 0 && (
            <motion.ul
              key="more"
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: "auto", opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
              className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 overflow-hidden mt-1.5"
            >
              {tail.map((label, i) => (
                <li
                  key={i}
                  data-testid="inclusion-extra"
                  className="flex items-start gap-1.5 text-xs text-neutral-700"
                >
                  <Check className="w-3.5 h-3.5 text-primary shrink-0 mt-0.5" />
                  <span className="leading-snug">{label}</span>
                </li>
              ))}
            </motion.ul>
          )}
        </AnimatePresence>

        {tail.length > 0 && (
          <button
            type="button"
            onClick={() => setExpanded((e) => !e)}
            aria-expanded={expanded}
            className="mt-2 self-start text-[11px] font-black uppercase tracking-widest text-primary hover:underline inline-flex items-center gap-1"
          >
            {expanded ? (
              <>
                Show less <ChevronUp className="w-3 h-3" />
              </>
            ) : (
              <>
                +{tail.length} more · View All <ChevronDown className="w-3 h-3" />
              </>
            )}
          </button>
        )}
      </div>

      {/* Price + CTA — existing 4-state machine, logic unchanged */}
      <div className="sm:w-44 shrink-0 p-4 sm:p-5 border-t sm:border-t-0 sm:border-l border-border flex sm:flex-col items-center sm:items-stretch justify-between sm:justify-center gap-3">
        <div className="sm:text-center">
          {showPrice ? (
            pricingLoading ? (
              <div className="h-6 w-20 bg-neutral-200 animate-pulse rounded sm:mx-auto" />
            ) : price != null ? (
              <>
                <p className="text-lg font-black text-neutral-900">₹{price}</p>
                <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                  Onwards
                </span>
              </>
            ) : (
              <>
                <p className="text-base font-black text-neutral-900">Quote</p>
                <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                  On Inspection
                </span>
              </>
            )
          ) : (
            <span className="text-[10px] uppercase tracking-widest font-bold text-neutral-400">
              {vehicleSelected ? "On Inspection" : "Select car"}
            </span>
          )}
        </div>

        {showPrice ? (
          <button
            onClick={() => (inCart ? onRemove() : onAdd())}
            className={`btn-ink ${
              inCart || justAdded ? "btn-ink-outline" : "btn-ink-primary"
            } px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest w-full justify-center gap-1.5`}
            aria-pressed={inCart}
          >
            {inCart || justAdded ? (
              <>
                <CheckCircle2 className="w-3.5 h-3.5" /> Added
              </>
            ) : (
              <>
                <ShoppingCart className="w-3.5 h-3.5" /> Add to Cart
              </>
            )}
          </button>
        ) : (
          <button
            onClick={() =>
              vehicleSelected
                ? onViewDetail()
                : window.scrollTo({ top: 0, behavior: "smooth" })
            }
            className="px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest border border-primary text-primary hover:bg-primary hover:text-white transition-colors w-full flex items-center justify-center gap-1.5"
          >
            {vehicleSelected ? "View Details" : "Select Your Car"}
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        )}
      </div>
    </article>
  );
};

export default ServiceCard;
