import type * as React from "react";
import { useMemo } from "react";
import { useNavigate, useParams } from "react-router-dom";
import {
  ArrowLeft,
  ArrowRight,
  Check,
  CheckCircle2,
  Clock,
  ClipboardCheck,
  PlusCircle,
  Wrench,
  Gauge,
  Star,
  Truck,
  ShieldCheck,
  PackageCheck,
  CalendarCheck,
} from "lucide-react";
import { TESTIMONIALS } from "../data/businessData";
import SeoHead from "../components/SeoHead";
import FAQAccordion from "../components/FAQAccordion";
import ServiceMetaRow from "../components/ServiceMetaRow";
import { groupInclusions } from "../lib/inclusions";
import { fetchServiceDetail, type ServiceInclusionItem } from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import { useBookingContext } from "../hooks/useBookingContext";

/**
 * Phase 2b-cont (D-2b-7) — Layer-3 service detail, REBUILT.
 *
 * GoMechanic structure, ACR skin (blue #1F4FA3 accents, navy #0E2A5C dark
 * band, Montserrat H2, Inter body — ZERO GoMechanic red/grey). The page
 * renders CENTER CONTENT ONLY into ServicesShell's <Outlet/>; the shell
 * owns the PageBanner (hero = service.image → dark gradient when null),
 * the sticky cross-category bar, the grid and the single CarSidebar. The
 * sidebar owns the live price + Add-to-Cart for this service, so the
 * detail body is purely informational.
 *
 * Content order:
 *   1. Highlight strip — What's Included / Also Includes / Timelines,
 *      derived from grouped inclusion counts + duration/interval.
 *   2. Intro — back link + title + ServiceMetaRow(detail) (duration ·
 *      warranty · interval(Recommended) · static Free Pickup & Drop).
 *   3. What's Included? — REAL service.inclusions via groupInclusions():
 *      Essential + Performance as image cards (image → fallback tile when
 *      null), Additional as a blue-checkmark list. Empty groups hidden.
 *   4. Steps-After-Booking band — Deep Navy #0E2A5C, static.
 *   5. Reviews / FAQ / Related / top-links — static.
 *
 * The detail query uses the SAME React Query key as ServicesShell
 * (["service-detail", cat, svc, carIds]) so the two dedupe to one request.
 */

interface ServiceDetailProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

const CITY_WORD = "Delhi NCR";

// Per-group metadata for the image-card sections (Essential / Performance).
// The icon is the fallback glyph shown when an inclusion has no image
// (every inclusion is image-null in acr_v3 today → the fallback path is
// the common one). ACR blue on a faint blue tile — no GoMechanic grey.
const IMAGE_GROUP_META: Record<
  string,
  { icon: React.ComponentType<{ className?: string }>; blurb: string }
> = {
  Essential: {
    icon: Wrench,
    blurb: "Core items inspected, replaced or topped up on every visit.",
  },
  Performance: {
    icon: Gauge,
    blurb: "Performance and longevity boosters included at no extra cost.",
  },
};

// Static post-booking journey (D-2b-7 navy band). No live data.
const BOOKING_STEPS: ReadonlyArray<{
  icon: React.ComponentType<{ className?: string }>;
  title: string;
  desc: string;
}> = [
  { icon: CalendarCheck, title: "Book Online", desc: "Pick the service and a slot that suits you." },
  { icon: Truck, title: "Free Pickup", desc: "Doorstep vehicle collection across Delhi NCR." },
  { icon: Wrench, title: "Expert Service", desc: "Certified technicians, 100% genuine OEM parts." },
  { icon: ShieldCheck, title: "Quality Check", desc: "Multi-point inspection before hand-back." },
  { icon: PackageCheck, title: "Doorstep Delivery", desc: "Car returned with a written warranty card." },
];

export default function ServiceDetail({ openEstimate }: ServiceDetailProps) {
  const navigate = useNavigate();
  const { category: categorySlug = "", service: serviceSlug = "" } = useParams<{
    category: string;
    service: string;
  }>();

  // Same query KEY as ServicesShell → React Query dedupes to one request.
  // The sidebar (in the shell) and this page therefore share one fetch.
  const { state: booking } = useBookingContext();
  const carIds = useMemo(
    () => ({
      brand_id: booking.car?.brand_id ?? null,
      model_id: booking.car?.model_id ?? null,
      fuel_id: booking.car?.fuel_id ?? null,
    }),
    [booking.car]
  );
  const detailQuery = useApiQuery(
    ["service-detail", categorySlug, serviceSlug, carIds],
    (signal) => fetchServiceDetail(categorySlug, serviceSlug, carIds, signal)
  );

  if (detailQuery.isLoading) {
    return (
      <div className="space-y-6 animate-pulse">
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div className="h-20 bg-neutral-100 border border-border" />
          <div className="h-20 bg-neutral-100 border border-border" />
          <div className="h-20 bg-neutral-100 border border-border" />
        </div>
        <div className="h-8 w-2/3 bg-neutral-200" />
        <div className="h-4 w-full bg-neutral-100" />
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="h-40 bg-neutral-100 border border-border" />
          ))}
        </div>
      </div>
    );
  }

  const category = detailQuery.data?.category ?? null;
  const service = detailQuery.data?.service ?? null;

  if (!category || !service) {
    return (
      <div className="p-20 text-center">
        {detailQuery.error ? `Error: ${detailQuery.error}` : "Service not found."}
      </div>
    );
  }

  // ---------- Grouped inclusions (REAL data, D-2b-7) ----------
  const grouped = groupInclusions(service.inclusions);
  const imageGroups = grouped.filter(
    (g) => g.group === "Essential" || g.group === "Performance"
  );
  const additionalGroup = grouped.find((g) => g.group === "Additional");

  const allInclusions = service.inclusions ?? [];
  const essentialCount = grouped.find((g) => g.group === "Essential")?.items.length ?? 0;
  const performanceCount = grouped.find((g) => g.group === "Performance")?.items.length ?? 0;
  const additionalCount = additionalGroup?.items.length ?? 0;
  const coreCount = essentialCount + performanceCount;

  const durationVal =
    service.time_takes != null && String(service.time_takes).trim() !== ""
      ? `${service.time_takes}${service.time_unit ? " " + service.time_unit : ""}`
      : null;

  // ---------- Highlight strip cells (derived; empty cells omitted) ----------
  const highlights: Array<{
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    value: string;
    sub?: string;
  }> = [];
  if (allInclusions.length > 0) {
    highlights.push({
      icon: ClipboardCheck,
      label: "What's Included",
      value: `${coreCount || allInclusions.length} Services`,
      sub: "Inspected, replaced or topped up",
    });
  }
  if (additionalCount > 0) {
    highlights.push({
      icon: PlusCircle,
      label: "Also Includes",
      value: `${additionalCount} Add-on${additionalCount === 1 ? "" : "s"}`,
      sub: additionalGroup?.items[0]?.label,
    });
  }
  if (durationVal || service.interval_info) {
    highlights.push({
      icon: Clock,
      label: "Timelines",
      value: durationVal ?? "Flexible",
      sub: service.interval_info ?? undefined,
    });
  }

  const timeUnitWord = service.time_unit || service.time_takes_option || "hours";
  const faqs = [
    {
      q: `How long does ${service.title} take?`,
      a: durationVal
        ? `${service.title} is typically completed within ${durationVal} at our certified ${CITY_WORD} workshops. Exact time depends on car make, model and component condition.`
        : `Time varies with the vehicle's condition. For most cars, ${service.title.toLowerCase()} is completed the same day.`,
    },
    {
      q: `What is the ${service.title.toLowerCase()} cost?`,
      a: `${service.title} pricing depends on car make, model and fuel. Select your car in the booking panel to see your exact, vehicle-specific price — what you're quoted upfront is what you pay.`,
    },
    {
      q: "Is there a warranty on this service?",
      a: service.warrenty_info
        ? `Yes — ${service.title} carries ${service.warrenty_info.toLowerCase()}. Every job is backed by a written warranty card issued at delivery.`
        : `Yes, every service carries a standard warranty. Our advisor shares the exact terms when you book.`,
    },
    {
      q: "Do you use genuine spare parts?",
      a: `Absolutely. We use 100% genuine OEM/OES parts for every ${category.title.toLowerCase()} job, sourced through authorised channels — each with a manufacturer warranty.`,
    },
    {
      q: "Is pickup and drop available?",
      a: `Yes — complimentary doorstep pickup and drop is available across our ${CITY_WORD} service radius. Choose your preferred slot when booking.`,
    },
  ];

  return (
    <>
      {/* Phase 4.5c — service-level flat SEO via cascade. */}
      {detailQuery.data?.seo && <SeoHead seo={detailQuery.data.seo} />}

      <div className="space-y-12">
        {/* ─────────── 1. HIGHLIGHT STRIP ─────────── */}
        {highlights.length > 0 && (
          <div
            className={`grid grid-cols-1 ${
              highlights.length >= 3
                ? "sm:grid-cols-3"
                : highlights.length === 2
                ? "sm:grid-cols-2"
                : ""
            } gap-3`}
          >
            {highlights.map((h) => {
              const Icon = h.icon;
              return (
                <div
                  key={h.label}
                  className="flex items-start gap-3 bg-white border border-border p-4"
                >
                  <span className="bg-primary/5 p-2.5 shrink-0">
                    <Icon className="w-5 h-5 text-primary" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
                      {h.label}
                    </p>
                    <p className="text-base font-black text-neutral-900 tracking-tighter leading-tight">
                      {h.value}
                    </p>
                    {h.sub && (
                      <p className="text-[11px] text-neutral-500 leading-snug line-clamp-2 mt-0.5">
                        {h.sub}
                      </p>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {/* ─────────── 2. INTRO: back link + title + meta row ─────────── */}
        <div>
          <button
            onClick={() => navigate(`/category/${categorySlug}`)}
            className="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest text-primary hover:underline mb-3"
          >
            <ArrowLeft className="w-3.5 h-3.5" /> All {category.title}
          </button>
          <h2 className="text-2xl sm:text-3xl font-black uppercase tracking-tighter text-neutral-900 mb-3">
            {service.title}
          </h2>
          <p className="text-sm sm:text-base text-neutral-600 leading-relaxed mb-5 max-w-2xl">
            {service.description?.trim()
              ? service.description
              : `Professional ${service.title} by certified technicians using genuine OEM parts. Our ${category.title.toLowerCase()} workshops in ${CITY_WORD} combine factory-grade equipment with skilled craftsmanship to deliver work that lasts.`}
          </p>
          <ServiceMetaRow
            variant="detail"
            timeTakes={service.time_takes}
            timeUnit={service.time_unit}
            warranty={service.warrenty_info}
            interval={service.interval_info}
            freePickup
          />
        </div>

        {/* ─────────── 3. WHAT'S INCLUDED? (grouped, real data) ─────────── */}
        {(imageGroups.length > 0 || (additionalGroup && additionalGroup.items.length > 0)) && (
          <section className="space-y-8">
            <div>
              <h2 className="section-heading mb-1.5">
                WHAT'S <span className="text-primary">INCLUDED?</span>
              </h2>
              <p className="text-xs text-neutral-500 uppercase tracking-widest font-bold">
                {allInclusions.length} {allInclusions.length === 1 ? "item" : "items"} ·{" "}
                {service.title}
              </p>
            </div>

            {/* Essential + Performance → image cards (fallback tile when null) */}
            {imageGroups.map((g) => {
              const meta = IMAGE_GROUP_META[g.group];
              const FallbackIcon = meta?.icon ?? Wrench;
              return (
                <div key={g.group}>
                  <div className="flex items-baseline gap-3 mb-3">
                    <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900">
                      {g.group}
                    </h3>
                    <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
                      {g.items.length} {g.items.length === 1 ? "item" : "items"}
                    </span>
                  </div>
                  {meta?.blurb && (
                    <p className="text-xs text-neutral-500 leading-relaxed mb-4 max-w-2xl">
                      {meta.blurb}
                    </p>
                  )}
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                    {g.items.map((item) => (
                      <InclusionImageCard
                        key={item.id}
                        item={item}
                        FallbackIcon={FallbackIcon}
                      />
                    ))}
                  </div>
                </div>
              );
            })}

            {/* Additional → blue-checkmark list */}
            {additionalGroup && additionalGroup.items.length > 0 && (
              <div>
                <div className="flex items-baseline gap-3 mb-3">
                  <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900">
                    Additional
                  </h3>
                  <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
                    {additionalGroup.items.length}{" "}
                    {additionalGroup.items.length === 1 ? "item" : "items"}
                  </span>
                </div>
                <ul className="bg-white border border-border divide-y divide-border">
                  {additionalGroup.items.map((item) => (
                    <li
                      key={item.id}
                      className="flex items-start gap-2.5 px-4 py-3 text-sm text-neutral-700"
                    >
                      <Check className="w-4 h-4 text-primary shrink-0 mt-0.5" />
                      <span className="leading-snug">{item.label}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </section>
        )}

        {/* ─────────── 4. STEPS-AFTER-BOOKING (navy band) ─────────── */}
        <section className="bg-[#0E2A5C] text-white p-6 sm:p-8">
          <h2 className="text-xl sm:text-2xl font-black uppercase tracking-tighter mb-1.5">
            What Happens <span className="text-[#3D86E0]">After You Book.</span>
          </h2>
          <p className="text-xs text-white/60 uppercase tracking-widest font-bold mb-6">
            Doorstep to doorstep · {CITY_WORD}
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {BOOKING_STEPS.map((step, i) => {
              const Icon = step.icon;
              return (
                <div key={step.title} className="relative">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="bg-white/10 p-2 shrink-0">
                      <Icon className="w-5 h-5 text-[#3D86E0]" />
                    </span>
                    <span className="text-2xl font-black text-white/15">0{i + 1}</span>
                  </div>
                  <h4 className="text-sm font-black uppercase tracking-tighter mb-1">
                    {step.title}
                  </h4>
                  <p className="text-[11px] text-white/60 leading-snug">{step.desc}</p>
                </div>
              );
            })}
          </div>
        </section>

        {/* ─────────── 5. CTA (blue — matches app, no orange) ─────────── */}
        <section className="bg-primary text-white p-6 sm:p-8">
          <div className="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-5 items-center">
            <div>
              <h3 className="text-xl sm:text-2xl font-black uppercase tracking-tighter mb-1.5">
                Get an Instant Quote for {service.title}
              </h3>
              <p className="text-white/80 text-xs sm:text-sm leading-relaxed">
                15-minute response · Genuine parts · Warranty included · Free pickup &amp; drop
              </p>
            </div>
            <button
              onClick={() => openEstimate?.(false, service.title)}
              className="btn-ink btn-ink-white px-7 py-3.5 font-black uppercase tracking-tighter text-sm whitespace-nowrap flex items-center justify-center gap-2"
            >
              Get Estimate <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
          </div>
        </section>

        {/* ─────────── 6. CUSTOMER REVIEWS (static) ─────────── */}
        <section>
          <h2 className="section-heading mb-5">
            CUSTOMER <span className="text-primary">REVIEWS.</span>
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {TESTIMONIALS.slice(0, 2).map((t, i) => (
              <div key={i} className="bg-white p-5 sm:p-6 border border-border">
                <div className="flex items-center gap-1 mb-3 text-primary">
                  {[...Array(t.rating)].map((_, idx) => (
                    <Star key={idx} className="w-4 h-4 fill-current" />
                  ))}
                </div>
                <p className="text-sm text-neutral-600 italic mb-4 leading-relaxed">
                  "{t.text}"
                </p>
                <div className="flex items-center gap-3">
                  <div className="w-9 h-9 bg-neutral-100 flex items-center justify-center font-black text-neutral-900 text-sm">
                    {t.initials}
                  </div>
                  <div className="font-bold text-neutral-900 uppercase tracking-widest text-[10px]">
                    {t.name}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* ─────────── 7. FAQs ─────────── */}
        <section>
          <h2 className="section-heading mb-5">
            COMMON <span className="text-primary">QUESTIONS.</span>
          </h2>
          <FAQAccordion faqs={faqs} />
        </section>

        {/* ─────────── 8. RELATED SERVICES (from API) ─────────── */}
        {(detailQuery.data?.related ?? []).filter((s) => s.id !== service.id).length > 0 && (
          <section>
            <h2 className="section-heading mb-5">
              RECOMMENDED <span className="text-primary">SERVICES.</span>
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {(detailQuery.data?.related ?? [])
                .filter((s) => s.id !== service.id)
                .slice(0, 4)
                .map((related) => (
                  <button
                    key={related.id}
                    onClick={() => navigate(`/services/${category.slug}/${related.slug}`)}
                    className="text-left bg-neutral-50 p-5 border border-border hover:border-primary transition-all group"
                  >
                    <h4 className="text-base font-black uppercase text-neutral-900 mb-1 group-hover:text-primary transition-colors tracking-tighter">
                      {related.title}
                    </h4>
                    <p className="text-xs text-neutral-500 mb-3 leading-relaxed line-clamp-2">
                      {related.recommended_info || "Highly recommended complement."}
                    </p>
                    <span className="text-[10px] font-bold text-primary uppercase tracking-widest flex items-center gap-2">
                      Explore{" "}
                      <ArrowRight className="w-3 h-3 group-hover:translate-x-1 transition-transform" />
                    </span>
                  </button>
                ))}
            </div>
          </section>
        )}

        {/* ─────────── 9. EXPLORE / TOP LINKS (static) ─────────── */}
        <section className="bg-neutral-50 p-6 sm:p-7 border border-border">
          <h3 className="text-base font-black uppercase text-neutral-900 mb-2 tracking-tighter">
            EXPLORE <span className="text-primary">RELATED.</span>
          </h3>
          <p className="text-sm text-neutral-600 leading-relaxed">
            Browse our complete range of{" "}
            <button
              onClick={() => navigate(`/category/${categorySlug}`)}
              className="text-primary font-bold hover:underline"
            >
              {category.title} services
            </button>
            , visit any of our{" "}
            <button
              onClick={() => navigate("/service-centers")}
              className="text-primary font-bold hover:underline"
            >
              certified service centres in {CITY_WORD}
            </button>
            , or{" "}
            <button
              onClick={() => navigate("/contact")}
              className="text-primary font-bold hover:underline"
            >
              contact our advisors
            </button>{" "}
            — we respond within 15 minutes.
          </p>
        </section>
      </div>
    </>
  );
}

/**
 * Image card for an Essential/Performance inclusion. Renders the
 * inclusion image when present; otherwise an on-brand fallback tile
 * (faint-blue surface + ACR-blue group icon). Caption below.
 */
const InclusionImageCard: React.FC<{
  item: ServiceInclusionItem;
  FallbackIcon: React.ComponentType<{ className?: string }>;
}> = ({ item, FallbackIcon }) => {
  return (
    <div className="border border-border bg-white overflow-hidden hover:border-primary/40 transition-colors">
      <div className="aspect-[4/3] relative bg-primary/5 flex items-center justify-center overflow-hidden">
        {item.image ? (
          <img
            src={item.image}
            alt={item.label}
            loading="lazy"
            referrerPolicy="no-referrer"
            className="absolute inset-0 w-full h-full object-cover"
          />
        ) : (
          <FallbackIcon className="w-9 h-9 text-primary/60" />
        )}
        <CheckCircle2 className="absolute top-2 right-2 w-4 h-4 text-primary bg-white rounded-full" />
      </div>
      <div className="p-3">
        <p className="text-xs font-bold text-neutral-900 leading-snug tracking-tight line-clamp-2">
          {item.label}
        </p>
      </div>
    </div>
  );
};
