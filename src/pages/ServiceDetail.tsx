import { useMemo } from "react";
import {
  CheckCircle2,
  ArrowRight,
  Shield,
  Clock,
  Zap,
  Star,
  Search,
  Calculator,
  Wrench,
  ShoppingCart,
  MapPin,
  Car,
  Lock,
  Phone,
} from "lucide-react";
import {
  TESTIMONIALS,
  LOCATIONS,
} from "../data/businessData";
import PageBanner from "../components/PageBanner";
import { useCart } from "../hooks/useCart";
import { useBookingContext } from "../hooks/useBookingContext";
import { useAuth } from "../hooks/useAuth";
import { fetchServiceDetail } from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";

interface ServiceDetailProps {
  categorySlug: string;
  serviceSlug: string;
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function ServiceDetail({
  categorySlug,
  serviceSlug,
  setCurrentPage,
  openEstimate,
}: ServiceDetailProps) {
  const { addItem, count, findCartItem, removeItem } = useCart();
  // Pull synced booking state from parent ServiceCategory page
  const { state: booking } = useBookingContext();
  const { user, isAuthenticated } = useAuth();

  // ---------- API: service detail (skeleton-first) ----------
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
    (signal) =>
      fetchServiceDetail(categorySlug, serviceSlug, carIds, signal)
  );

  if (detailQuery.isLoading) {
    return (
      <div className="pt-8 pb-24">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 animate-pulse">
            <div className="lg:col-span-2 space-y-6">
              <div className="h-8 w-2/3 bg-neutral-200" />
              <div className="h-4 w-full bg-neutral-100" />
              <div className="h-72 bg-neutral-100" />
            </div>
            <div className="bg-white border border-border h-[420px] bg-neutral-50" />
          </div>
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

  // Booking can only be added to cart once user has completed Check Price
  // on the parent category page (same gate as the parent page itself).
  const canBook = booking.pricesShown;
  const selectedLocationName =
    LOCATIONS.find((l) => l.id === booking.location)?.name || "your area";

  // Phase 2.3.3 — toggle add/remove on the same button. First click:
  // addItem; second click: remove the server cart line. The 1.8 s
  // post-add flash bridges the visual gap to the React Query refetch.
  const cartItem = findCartItem({
    ref_id:   service.id,
    brand_id: booking.car?.brand_id,
    model_id: booking.car?.model_id,
    fuel_id:  booking.car?.fuel_id,
  });
  const inCart = !!cartItem;

  // Phase 2.3.5 — strict vehicle-only price state machine. Use the
  // top-level `vehicle_price` from /services/{cat}/{slug} response,
  // NOT `service.price` (which silently falls back to base_price
  // server-side when no priced row matches and would re-introduce
  // the flicker). The 4 states map 1:1 to the sidebar UI below.
  type PriceState =
    | { kind: "no-vehicle" }
    | { kind: "loading" }
    | { kind: "price"; value: number }
    | { kind: "no-price" };
  const vehicleSelected = !!(
    booking.car?.brand_id && booking.car?.model_id && booking.car?.fuel_id
  );
  const detailLoading = vehicleSelected && detailQuery.isLoading;
  const vehiclePrice =
    typeof detailQuery.data?.vehicle_price === "number"
      ? detailQuery.data.vehicle_price
      : null;
  const priceState: PriceState = !vehicleSelected
    ? { kind: "no-vehicle" }
    : detailLoading
    ? { kind: "loading" }
    : vehiclePrice != null
    ? { kind: "price", value: vehiclePrice }
    : { kind: "no-price" };

  // ---------- Page-level constants (no location-tied content) ----------
  const cityWord = "Delhi NCR";
  const priceDisplay =
    priceState.kind === "price"
      ? `Starting at ₹${priceState.value}`
      : "Get Custom Quote";

  const handleAddToCart = () => {
    addItem({
      serviceId: String(service.id),
      title: service.title,
      // Phase 2.3.5 — addItem's `price` is a legacy display hint;
      // the server re-snapshots authoritatively from service_prices
      // on every POST /cart/items. We pass the resolved vehicle
      // price when available; the backend ignores it for pricing.
      price: priceState.kind === "price" ? priceState.value : 0,
      categorySlug: category.slug,
      car: booking.car || undefined,
      location: selectedLocationName,
      brand_id: booking.car?.brand_id,
      model_id: booking.car?.model_id,
      fuel_id:  booking.car?.fuel_id,
    });
  };

  const goToParentForBooking = () => {
    // Navigate user back to the parent category page where the full
    // booking flow lives (location + car + phone + OTP).
    setCurrentPage(`category-${categorySlug}`);
  };

  const serviceIncludes = [
    {
      icon: Search,
      title: "Inspection",
      desc: "Detailed multi-point check by certified technicians",
    },
    {
      icon: Shield,
      title: "OEM Parts",
      desc: "100% genuine OEM/OES with manufacturer warranty",
    },
    {
      icon: Calculator,
      title: "Diagnostic Scan",
      desc: "Computerised scan tool diagnostics on every car",
    },
    {
      icon: Zap,
      title: "Performance Test",
      desc: "Road-ready functional and safety verification",
    },
    {
      icon: CheckCircle2,
      title: "Quality Report",
      desc: "Itemised post-service report shared digitally",
    },
    {
      icon: Star,
      title: "Warranty Card",
      desc: "Written warranty issued with every job",
    },
  ];

  const benefits = [
    {
      icon: Shield,
      title: "Improved Safety",
      desc: "Restored performance with safety-first inspection on every visit.",
    },
    {
      icon: Star,
      title: "Premium Quality",
      desc: "Manufacturer-grade OEM parts with extended warranty backing.",
    },
    {
      icon: Zap,
      title: "Performance Boost",
      desc: "Optimised functionality and longer service intervals.",
    },
    {
      icon: Clock,
      title: "Cost-Effective",
      desc: "Transparent pricing, free re-inspection, no hidden charges.",
    },
  ];

  const processSteps = [
    {
      icon: Search,
      title: "Inspection",
      desc: `Detailed assessment of ${service.title.toLowerCase()} requirements with computerised diagnostics.`,
    },
    {
      icon: Calculator,
      title: "Estimation",
      desc: "Transparent cost breakdown — upfront pricing, no surprise charges.",
    },
    {
      icon: Wrench,
      title: "Execution",
      desc: "Precision implementation by certified technicians using top-grade equipment.",
    },
    {
      icon: CheckCircle2,
      title: "Quality Check",
      desc: "Multi-point quality inspection and final functional road tests before delivery.",
    },
  ];

  const timeUnit = service.time_takes_option || "Hour";
  const timeUnitPlural =
    service.time_takes && Number(service.time_takes) > 1
      ? `${timeUnit}s`
      : timeUnit;

  const faqs = [
    {
      q: `How long does ${service.title} take?`,
      a: service.time_takes
        ? `${service.title} is typically completed within ${service.time_takes} ${timeUnitPlural} at our certified service centres. Exact time depends on car make, model and component condition.`
        : `Time varies based on the vehicle's specific condition. For most cars, ${service.title.toLowerCase()} can be completed the same day.`,
    },
    {
      q: `What is the ${service.title.toLowerCase()} cost?`,
      // Phase 2.3.5 — FAQ answer references the resolved vehicle
      // price when available, otherwise omits the number entirely.
      // We never render base_price here either.
      a: priceState.kind === "price"
        ? `${service.title} starts at ₹${priceState.value}. The final ${category.title.toLowerCase()} cost depends on car make, model and parts grade. We share a transparent quote upfront.`
        : `${service.title} pricing depends on car make, model and parts required. We offer transparent, upfront quotes at all our centres.`,
    },
    {
      q: "Is there a warranty on this service?",
      a: service.warrenty_info
        ? `Yes — ${service.title} carries ${service.warrenty_info.toLowerCase()}. Every job is backed by a written warranty card issued at delivery.`
        : `Yes, every service we perform carries a standard warranty. Our advisor will share exact terms when you visit.`,
    },
    {
      q: "Do you use genuine spare parts?",
      a: `Absolutely. We strictly use 100% genuine OEM and OES parts for every ${category.title.toLowerCase()} job, sourced through authorised channels. Each part comes with a manufacturer warranty.`,
    },
    {
      q: "Can I claim insurance for this repair?",
      a: `Yes. We have direct tie-ups with major insurance providers and offer a fully cashless facility for covered ${category.title.toLowerCase()} services.`,
    },
    {
      q: `Where can I find ${service.title.toLowerCase()} near me?`,
      a: `We operate certified service centres across ${cityWord}. Find your nearest centre on the service centres page or book online — we will route your vehicle to the closest available bay.`,
    },
    {
      q: "Is pickup and drop available?",
      a: "Yes, we provide complimentary pickup and drop service across our service radius. Just let us know your preferred slot when booking.",
    },
  ];

  const heroImage =
    "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=1200";

  return (
    <>
      {/* Banner — service title only, NO location appended */}
      <PageBanner
        title={service.title}
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          {
            label: category.title,
            onClick: () => setCurrentPage(`category-${categorySlug}`),
          },
          { label: service.title },
        ]}
        label={category.title}
        backgroundImage={heroImage}
      >
        <p className="text-xl text-white/80 leading-relaxed mb-6 max-w-2xl">
          {category.description}
        </p>
      </PageBanner>

      <div className="pb-14 pt-8">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
            {/* Main Content */}
            <main className="lg:col-span-2 space-y-12">
              {/* OVERVIEW */}
              <section className="bg-neutral-50 p-6 sm:p-8 border border-border">
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  SERVICE <span className="text-primary">OVERVIEW.</span>
                </h2>
                <p className="text-sm sm:text-base text-neutral-600 leading-relaxed mb-6">
                  Professional{" "}
                  <strong className="text-neutral-900">{service.title}</strong>{" "}
                  by certified technicians using genuine OEM parts. Our{" "}
                  {category.title.toLowerCase()} workshops in {cityWord}{" "}
                  combine factory-grade equipment with skilled craftsmanship to
                  deliver work that lasts.
                </p>

                <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6 pb-6 border-b border-border">
                  <div>
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Time Required
                    </h4>
                    <p className="text-base font-black text-neutral-900">
                      {service.time_takes
                        ? `${service.time_takes} ${timeUnitPlural}`
                        : "Varies"}
                    </p>
                  </div>
                  <div>
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Price Range
                    </h4>
                    <p className="text-base font-black text-neutral-900">
                      {priceDisplay}
                    </p>
                  </div>
                  <div className="col-span-2 sm:col-span-1">
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Warranty
                    </h4>
                    <p className="text-base font-black text-neutral-900">
                      {service.warrenty_info || "Standard Terms"}
                    </p>
                  </div>
                </div>

                {service.recommended_info && (
                  <div>
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Recommended When
                    </h4>
                    <p className="text-sm text-neutral-700 leading-relaxed">
                      {service.recommended_info}
                    </p>
                  </div>
                )}
              </section>

              {/* SERVICES INCLUDED */}
              <section>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  SERVICES <span className="text-primary">INCLUDED.</span>
                </h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                  {serviceIncludes.map((item, i) => {
                    const Icon = item.icon;
                    return (
                      <div
                        key={i}
                        className="bg-white p-5 border border-border hover:border-primary transition-colors"
                      >
                        <div className="bg-primary/5 p-2.5 inline-flex mb-3">
                          <Icon className="w-5 h-5 text-primary" />
                        </div>
                        <h4 className="text-sm font-black uppercase text-neutral-900 mb-1 tracking-tighter">
                          {item.title}
                        </h4>
                        <p className="text-xs text-neutral-500 leading-relaxed">
                          {item.desc}
                        </p>
                      </div>
                    );
                  })}
                </div>
              </section>

              {/* WHY CHOOSE */}
              <section>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  WHY CHOOSE <span className="text-primary">THIS SERVICE.</span>
                </h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                  {benefits.map((b, i) => {
                    const Icon = b.icon;
                    return (
                      <div
                        key={i}
                        className="bg-white p-5 border border-border flex gap-4 hover:border-primary transition-colors"
                      >
                        <div className="bg-primary/5 p-2.5 shrink-0 self-start">
                          <Icon className="w-5 h-5 text-primary" />
                        </div>
                        <div>
                          <h4 className="text-sm font-black uppercase text-neutral-900 mb-1 tracking-tighter">
                            {b.title}
                          </h4>
                          <p className="text-xs text-neutral-500 leading-relaxed">
                            {b.desc}
                          </p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </section>

              {/* PROCESS */}
              <section>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  THE <span className="text-primary">PROCESS.</span>
                </h2>
                <div className="space-y-3">
                  {processSteps.map((step, i) => {
                    const Icon = step.icon;
                    return (
                      <div
                        key={i}
                        className="bg-white border border-border p-4 sm:p-5 flex gap-4 items-start hover:border-primary transition-colors"
                      >
                        <div className="text-2xl font-black text-primary/30 shrink-0 w-9">
                          0{i + 1}
                        </div>
                        <div className="bg-primary/5 p-2.5 shrink-0">
                          <Icon className="w-5 h-5 text-primary" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <h4 className="text-base font-black uppercase mb-1 text-neutral-900 tracking-tighter">
                            {step.title}
                          </h4>
                          <p className="text-sm text-neutral-500 leading-relaxed">
                            {step.desc}
                          </p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </section>

              {/* CTA STRIP */}
              <section className="bg-primary text-white p-6 sm:p-8">
                <div className="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-5 items-center">
                  <div>
                    <h3 className="text-xl sm:text-2xl font-black uppercase tracking-tighter mb-1.5">
                      Get Instant Quote for {service.title}
                    </h3>
                    <p className="text-white/80 text-xs sm:text-sm leading-relaxed">
                      {priceDisplay} · 15-min response · Genuine parts ·
                      Warranty included
                    </p>
                  </div>
                  <button
                    onClick={() => openEstimate?.(false, service.title)}
                    className="btn-ink btn-ink-white px-7 py-3.5 font-black uppercase tracking-tighter text-sm whitespace-nowrap flex items-center justify-center gap-2"
                  >
                    Get Estimate{" "}
                    <ArrowRight className="w-4 h-4 btn-arrow" />
                  </button>
                </div>
              </section>

              {/* REAL RESULTS */}
              <section>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-1.5">
                  REAL <span className="text-primary">RESULTS.</span>
                </h2>
                <p className="text-[10px] sm:text-xs text-neutral-500 uppercase tracking-widest font-bold mb-5">
                  Actual customer result · {service.title}
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="relative aspect-video overflow-hidden border border-border">
                    <img
                      src={heroImage}
                      className="w-full h-full object-cover grayscale"
                      alt={`Before ${service.title}`}
                      referrerPolicy="no-referrer"
                    />
                    <div className="absolute top-3 left-3 bg-white/90 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-neutral-900">
                      Before
                    </div>
                  </div>
                  <div className="relative aspect-video overflow-hidden border border-primary">
                    <img
                      src={heroImage}
                      className="w-full h-full object-cover"
                      alt={`After ${service.title}`}
                      referrerPolicy="no-referrer"
                    />
                    <div className="absolute top-3 right-3 bg-primary px-3 py-1 text-[10px] font-black uppercase tracking-widest text-white">
                      After
                    </div>
                  </div>
                </div>
              </section>

              {/* FAQs */}
              <section>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  COMMON <span className="text-primary">QUESTIONS.</span>
                </h2>
                <div className="space-y-3">
                  {faqs.map((faq, i) => (
                    <div
                      key={i}
                      className="bg-white p-5 sm:p-6 border border-border"
                    >
                      <h4 className="text-base sm:text-lg font-black uppercase mb-2 flex items-start gap-3 text-neutral-900 tracking-tighter">
                        <MessageSquare className="text-primary w-5 h-5 mt-0.5 shrink-0" />
                        <span>{faq.q}</span>
                      </h4>
                      <p className="text-sm text-neutral-600 leading-relaxed pl-8">
                        {faq.a}
                      </p>
                    </div>
                  ))}
                </div>
              </section>

              {/* INTERNAL LINKS */}
              <section className="bg-neutral-50 p-6 sm:p-7 border border-border">
                <h3 className="text-base font-black uppercase text-neutral-900 mb-2 tracking-tighter">
                  EXPLORE <span className="text-primary">RELATED.</span>
                </h3>
                <p className="text-sm text-neutral-600 leading-relaxed">
                  Browse our complete range of{" "}
                  <button
                    onClick={() =>
                      setCurrentPage(`category-${categorySlug}`)
                    }
                    className="text-primary font-bold hover:underline"
                  >
                    {category.title} services
                  </button>
                  , visit any of our{" "}
                  <button
                    onClick={() => setCurrentPage("service-centers")}
                    className="text-primary font-bold hover:underline"
                  >
                    certified service centres in {cityWord}
                  </button>
                  , or{" "}
                  <button
                    onClick={() => setCurrentPage("contact")}
                    className="text-primary font-bold hover:underline"
                  >
                    contact our advisors
                  </button>{" "}
                  — we respond within 15 minutes.
                </p>
              </section>

              {/* CUSTOMER REVIEWS */}
              <section className="pt-12 border-t border-border">
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  CUSTOMER <span className="text-primary">REVIEWS.</span>
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {TESTIMONIALS.slice(0, 2).map((testimonial, i) => (
                    <div
                      key={i}
                      className="bg-white p-5 sm:p-6 border border-border"
                    >
                      <div className="flex items-center gap-1 mb-3 text-primary">
                        {[...Array(testimonial.rating)].map((_, idx) => (
                          <Star key={idx} className="w-4 h-4 fill-current" />
                        ))}
                      </div>
                      <p className="text-sm text-neutral-600 italic mb-4 leading-relaxed">
                        "{testimonial.text}"
                      </p>
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 bg-neutral-100 flex items-center justify-center font-black text-neutral-900 text-sm">
                          {testimonial.initials}
                        </div>
                        <div className="font-bold text-neutral-900 uppercase tracking-widest text-[10px]">
                          {testimonial.name}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </section>

              {/* RECOMMENDED SERVICES */}
              <section className="pt-12 border-t border-border">
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
                  RECOMMENDED{" "}
                  <span className="text-primary">SERVICES.</span>
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {(detailQuery.data?.related ?? [])
                    .filter((s) => s.id !== service.id)
                    .slice(0, 2)
                    .map((related) => (
                      <div
                        key={related.id}
                        onClick={() =>
                          setCurrentPage(
                            `service-${category.slug}/${related.slug}`
                          )
                        }
                        className="bg-neutral-50 p-5 border border-border hover:border-primary transition-all cursor-pointer group"
                      >
                        <h4 className="text-base font-black uppercase text-neutral-900 mb-1 group-hover:text-primary transition-colors tracking-tighter">
                          {related.title}
                        </h4>
                        <p className="text-xs text-neutral-500 mb-3 leading-relaxed">
                          {related.recommended_info ||
                            "Highly recommended complement."}
                        </p>
                        <span className="text-[10px] font-bold text-primary uppercase tracking-widest flex items-center gap-2">
                          Explore{" "}
                          <ArrowRight className="w-3 h-3 group-hover:translate-x-1 transition-transform" />
                        </span>
                      </div>
                    ))}
                </div>
              </section>
            </main>

            {/* Sidebar */}
            <aside className="space-y-6 lg:sticky lg:top-32 lg:self-start">
              {/* Synced booking context card — shows the SAME details
                  the user already filled on the parent category page.
                  No re-asking for car / location / phone. */}
              <div className="bg-white border border-border p-5 sm:p-6 shadow-xl">
                <h3 className="text-lg sm:text-xl font-black uppercase tracking-tighter mb-1 text-neutral-900">
                  EXPERIENCE THE BEST{" "}
                  <span className="text-primary italic">{service.title}</span>{" "}
                  IN <span className="uppercase">{selectedLocationName}</span>
                </h3>
                <p className="text-xs text-neutral-500 mb-5">
                  Your booking details — auto-filled from your previous
                  selection.
                </p>

                {/* User identity (when logged in) */}
                {isAuthenticated && user && (
                  <div className="mb-3 bg-primary/5 border border-primary/20 px-3 py-2 flex items-center gap-2">
                    <CheckCircle2 className="w-4 h-4 text-primary shrink-0" />
                    <div className="min-w-0 flex-1">
                      <p className="text-[11px] font-black uppercase text-neutral-900 tracking-tighter truncate">
                        {user.name}
                      </p>
                      <p className="text-[10px] text-neutral-500 truncate">
                        +91 {user.phone} · Verified
                      </p>
                    </div>
                  </div>
                )}

                {/* Location */}
                <div className="mb-2.5 bg-neutral-50 border border-border px-3 py-2.5 flex items-start gap-2">
                  <MapPin className="w-4 h-4 text-primary shrink-0 mt-0.5" />
                  <div className="min-w-0 flex-1">
                    <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest">
                      Location
                    </p>
                    <p className="text-xs font-bold text-neutral-900 truncate">
                      {booking.location ? selectedLocationName : "Not selected"}
                    </p>
                  </div>
                </div>

                {/* Car */}
                <div className="mb-2.5 bg-neutral-50 border border-border px-3 py-2.5 flex items-start gap-2">
                  <Car className="w-4 h-4 text-primary shrink-0 mt-0.5" />
                  <div className="min-w-0 flex-1">
                    <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest">
                      Your Car
                    </p>
                    <p className="text-xs font-bold text-neutral-900 truncate">
                      {booking.car
                        ? `${booking.car.brand} ${booking.car.model} · ${booking.car.fuel}`
                        : "Not selected"}
                    </p>
                  </div>
                </div>

                {/* Phone — only show if not logged in (logged-in users see name above) */}
                {!isAuthenticated && (
                  <div className="mb-4 bg-neutral-50 border border-border px-3 py-2.5 flex items-start gap-2">
                    <Phone className="w-4 h-4 text-primary shrink-0 mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest">
                        Phone
                      </p>
                      <p className="text-xs font-bold text-neutral-900 truncate">
                        {booking.phone
                          ? `+91 ${booking.phone}${
                              booking.otpVerified ? " · Verified" : ""
                            }`
                          : "Not entered"}
                      </p>
                    </div>
                  </div>
                )}

                {/* If user hasn't completed Check Price, send them to parent */}
                {!canBook ? (
                  <button
                    onClick={goToParentForBooking}
                    className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2"
                  >
                    Check Price For Free{" "}
                    <ArrowRight className="w-4 h-4 btn-arrow" />
                  </button>
                ) : (
                  <button
                    onClick={() => setCurrentPage(`category-${categorySlug}`)}
                    className="text-[10px] font-bold uppercase tracking-widest text-primary hover:underline w-full text-center py-2"
                  >
                    Edit details ↑
                  </button>
                )}
              </div>

              {/* Pricing / Add-to-Cart card — gated until user completes Check Price */}
              <div className="bg-primary p-6 text-white shadow-2xl">
                <h3 className="text-xl sm:text-2xl font-black uppercase mb-1 tracking-tighter">
                  ESTIMATE
                </h3>
                {canBook ? (
                  <>
                    {/* Phase 2.3.5 — strict 4-state machine; never base_price. */}
                    {priceState.kind === "loading" ? (
                      <div className="h-10 sm:h-12 w-32 bg-white/20 animate-pulse rounded mb-1" />
                    ) : priceState.kind === "price" ? (
                      <>
                        <p className="text-3xl sm:text-4xl font-black mb-1">
                          ₹{priceState.value}
                        </p>
                        <p className="text-[10px] text-white/70 uppercase tracking-widest mb-5">
                          Starting price · Final after inspection
                        </p>
                      </>
                    ) : priceState.kind === "no-price" ? (
                      <>
                        <p className="text-2xl sm:text-3xl font-black mb-1">
                          Quote on Inspection
                        </p>
                        <p className="text-[10px] text-white/70 uppercase tracking-widest mb-5">
                          Final after inspection
                        </p>
                      </>
                    ) : (
                      <>
                        <p className="text-2xl sm:text-3xl font-black mb-1">
                          Select Your Car
                        </p>
                        <p className="text-[10px] text-white/70 uppercase tracking-widest mb-5">
                          Pick brand · model · fuel to see your price
                        </p>
                      </>
                    )}
                  </>
                ) : (
                  <>
                    <div className="flex items-baseline gap-2 mb-1">
                      <Lock className="w-5 h-5 text-white/60" />
                      <p className="text-2xl sm:text-3xl font-black text-white/60">
                        Hidden
                      </p>
                    </div>
                    <p className="text-[10px] text-white/70 uppercase tracking-widest mb-5">
                      Complete Check Price to view
                    </p>
                  </>
                )}
                <ul className="space-y-2.5 mb-6">
                  <li className="flex items-center gap-2 font-bold uppercase text-xs">
                    <CheckCircle2 className="w-4 h-4" /> Genuine OEM Parts
                  </li>
                  <li className="flex items-center gap-2 font-bold uppercase text-xs">
                    <CheckCircle2 className="w-4 h-4" /> Expert Technicians
                  </li>
                  <li className="flex items-center gap-2 font-bold uppercase text-xs">
                    <CheckCircle2 className="w-4 h-4" /> Quality Assured
                  </li>
                  <li className="flex items-center gap-2 font-bold uppercase text-xs">
                    <CheckCircle2 className="w-4 h-4" /> Warranty Included
                  </li>
                </ul>

                {canBook ? (
                  <>
                    <button
                      onClick={() =>
                        inCart && cartItem
                          ? removeItem(String(cartItem.id))
                          : handleAddToCart()
                      }
                      // Phase 2.3.5 — ADDED state inherits the BOOK NOW
                      // button's `btn-ink btn-ink-white` ink-sweep hover
                      // so the two sit-side-by-side and feel identical
                      // on hover. Base ADDED look is white-on-blue with
                      // a primary border; sweep paints `bg-neutral-100`
                      // on hover for visible feedback. Add-to-Cart uses
                      // the same btn-ink-white base for visual parity
                      // (no ADDED border so it reads as the primary CTA).
                      className={`btn-ink btn-ink-white w-full py-3.5 font-black uppercase tracking-tighter text-sm justify-center gap-2 mb-3 ${
                        inCart ? "border border-primary" : ""
                      }`}
                      aria-pressed={inCart}
                    >
                      {inCart ? (
                        <>
                          <CheckCircle2 className="w-4 h-4" /> Added
                        </>
                      ) : (
                        <>
                          <ShoppingCart className="w-4 h-4" /> Add to Cart
                        </>
                      )}
                    </button>
                    <button
                      onClick={() => openEstimate?.(false, service.title)}
                      className="btn-ink btn-ink-white w-full py-3.5 font-black uppercase tracking-tighter text-sm flex items-center justify-center gap-2"
                    >
                      Book Now <ArrowRight className="w-4 h-4 btn-arrow" />
                    </button>
                  </>
                ) : (
                  <button
                    onClick={goToParentForBooking}
                    className="w-full bg-white text-primary py-3.5 font-black uppercase tracking-tighter text-sm flex items-center justify-center gap-2 hover:bg-white/90 transition-colors"
                  >
                    Check Price First{" "}
                    <ArrowRight className="w-4 h-4" />
                  </button>
                )}
              </div>

              {/* Cart access — only when items exist */}
              {count > 0 && (
                <button
                  onClick={() => setCurrentPage("cart")}
                  className="w-full bg-white border border-primary p-4 flex items-center justify-between hover:bg-primary/5 transition-colors group"
                >
                  <div className="flex items-center gap-3">
                    <div className="bg-primary/5 p-2">
                      <ShoppingCart className="w-5 h-5 text-primary" />
                    </div>
                    <div className="text-left">
                      <p className="text-xs font-black uppercase text-neutral-900 tracking-tighter">
                        View Cart
                      </p>
                      <p className="text-[10px] text-neutral-500">
                        {count} {count === 1 ? "service" : "services"} added
                      </p>
                    </div>
                  </div>
                  <ArrowRight className="w-4 h-4 text-primary group-hover:translate-x-1 transition-transform" />
                </button>
              )}

              {/* Trust badges */}
              <div className="bg-white p-5 sm:p-6 border border-border shadow-xl">
                <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-4">
                  Why Trust Us
                </h4>
                <div className="space-y-3.5">
                  <div className="flex items-center gap-3">
                    <div className="bg-primary/5 p-2 shrink-0">
                      <Shield className="text-primary w-5 h-5" />
                    </div>
                    <div className="min-w-0">
                      <h5 className="text-xs font-black uppercase text-neutral-900 tracking-tighter">
                        Certified Centre
                      </h5>
                      <p className="text-[10px] text-neutral-500">
                        ISO 9001:2015
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="bg-primary/5 p-2 shrink-0">
                      <CheckCircle2 className="text-primary w-5 h-5" />
                    </div>
                    <div className="min-w-0">
                      <h5 className="text-xs font-black uppercase text-neutral-900 tracking-tighter">
                        Genuine Parts
                      </h5>
                      <p className="text-[10px] text-neutral-500">
                        100% OEM/OES
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="bg-primary/5 p-2 shrink-0">
                      <Clock className="text-primary w-5 h-5" />
                    </div>
                    <div className="min-w-0">
                      <h5 className="text-xs font-black uppercase text-neutral-900 tracking-tighter">
                        Fast Turnaround
                      </h5>
                      <p className="text-[10px] text-neutral-500">
                        Most repairs in 48 hrs
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </>
  );
}

function MessageSquare(props: { className?: string }) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
  );
}
