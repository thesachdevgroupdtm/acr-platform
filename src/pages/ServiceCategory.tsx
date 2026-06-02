import { useState, useMemo } from "react";
import { motion } from "motion/react";
import { useNavigate, useParams } from "react-router-dom";
import {
  CheckCircle2,
  ArrowRight,
  ArrowLeft,
  Shield,
  Clock,
  Zap,
  Star,
  X,
  Search,
  Calculator,
  Wrench,
  ChevronDown,
  MapPin,
  Droplet,
  Fuel,
  Wind,
  BatteryCharging,
  AlertCircle,
  RefreshCw,
  ShoppingCart,
  User,
  Lock,
  Check,
  Snowflake,
  Disc3,
  Paintbrush,
  Sparkles,
  Cog,
  Lightbulb,
  ClipboardCheck,
} from "lucide-react";
import { TESTIMONIALS } from "../data/businessData";
import { useServiceCenters } from "../hooks/useServiceCenters";
import { useBrands } from "../hooks/useVehicle";
import SeoHead from "../components/SeoHead";
import VehicleReplaceModal from "../components/VehicleReplaceModal";
import FAQAccordion from "../components/FAQAccordion";
import { useCart } from "../hooks/useCart";
import { VehicleConflictError, type VehicleConflictDetails } from "../lib/errors";
import { useAuth } from "../hooks/useAuth";
import { useBookingContext } from "../hooks/useBookingContext";
import {
  fetchCategoryDetail,
  type SubService as ApiSubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import SectionHeading from "../components/layout/SectionHeading";
import ServiceCard from "../components/service/ServiceCard";
import { categoryIcon } from "../components/service/categoryIcon";

interface ServiceCategoryProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

// ---------- Constants ----------
// FORMS-1 STEP 2 — FUEL_TYPES removed with the bespoke inline car
// selector. Fuel options now come from the shared VehicleSelector
// (FuelStep) via the cart form's vehicle-change modal.

// Phase 2d (D-2d-4b) — the in-page section-nav scroller + its scrollspy
// (SECTION_NAV / useSubNavSync / sticky-offset constants) were removed.
// The shell's cross-category bar is the only nav now.
// Phase 2c — fallback icon per category lives in
// src/components/service/categoryIcon.ts (shared with Layer 1).

export default function ServiceCategory({
  openEstimate,
}: ServiceCategoryProps) {
  const navigate = useNavigate();
  // /category/:slug route — slug is the category slug.
  const { slug: categorySlug = "" } = useParams<{ slug: string }>();
  // ---------- API: category detail (skeleton-first; no static fallback) ----------
  const { state: bookingCtx0 } = useBookingContext();
  // /services/{slug} takes brand/model/fuel SLUGS per backend contract.
  const carSlugs = useMemo(
    () => ({
      brand: bookingCtx0.car?.brand_slug ?? null,
      model: bookingCtx0.car?.model_slug ?? null,
      fuel: bookingCtx0.car?.fuel_slug ?? null,
    }),
    [bookingCtx0.car]
  );
  const detailQuery = useApiQuery(
    ["category-detail", categorySlug, carSlugs],
    (signal) => fetchCategoryDetail(categorySlug, carSlugs, signal)
  );
  const apiCategory = detailQuery.data?.category ?? null;
  const apiSubServices: ApiSubService[] = detailQuery.data?.services ?? [];
  const priceShowFromApi = Boolean(detailQuery.data?.price_show);
  const isLoadingDetail = detailQuery.isLoading;

  // Phase 2.6a — vehicle prices arrive INLINE on each service in the
  // /services/{slug} response (`vehicle_price` field). The pre-2.6a
  // parallel POST /pricing call is gone; the backend resolved the
  // price map itself using the brand/model/fuel slugs in the GET
  // query. priceMap still drives the 4-state machine in the price
  // column.
  const vehicleSelected = !!(
    bookingCtx0.car?.brand_id &&
    bookingCtx0.car?.model_id &&
    bookingCtx0.car?.fuel_id
  );
  const priceMap = useMemo(() => {
    const m = new Map<number, number>();
    for (const s of apiSubServices) {
      if (s.vehicle_price != null) {
        const num = Number(s.vehicle_price);
        if (Number.isFinite(num)) m.set(s.id, num);
      }
    }
    return m;
  }, [apiSubServices]);
  const pricingLoading = vehicleSelected && detailQuery.isLoading;
  // For sections that need a stable display object — never null in render below;
  // skeleton path returns early when no category resolved.
  const category = apiCategory;

  // ---------- Cart ----------
  const { addItem, count, isInCart, findCartItem, removeItem, replaceVehicleInCart, isLoading: cartLoading } = useCart();
  const [addedFlash, setAddedFlash] = useState<string | null>(null);
  const [vehicleConflict, setVehicleConflict] = useState<VehicleConflictDetails | null>(null);
  const [replacing, setReplacing] = useState(false);

  // ---------- Auth (drives phone prefill + OTP skip) ----------
  const { user, isAuthenticated, bootstrapped } = useAuth();
  // Phase 2.6a-fix — `cartReady` gates ADDED-badge derivation; see
  // Services.tsx for the why.
  const cartReady = bootstrapped && !cartLoading;

  // ---------- Shared booking context (read-only on this page) ----------
  // FORMS-1 STEP 2 — the bespoke inline car-selector + OTP form that used
  // to own location/car/phone here is gone. The right-column CART form now
  // owns vehicle/location selection and writes the chosen vehicle into
  // booking context; this page READS it back so the price list and
  // add-to-cart stay in sync. No local mirrors, no sync effect.
  // B5-partial — service centers from the API (was static LOCATIONS).
  const { centers: serviceCenters } = useServiceCenters();
  const bookingLocation = bookingCtx0.location || serviceCenters[0]?.slug || "";
  const bookingCar = bookingCtx0.car;

  // ---------- Vehicle data for page content (supported-brands copy) ----------
  // Only the brands query remains — the model/fuel queries lived in the
  // deleted inline selector. Brands feed the overview/SEO copy ("X, Y and
  // more", "{N}+ supported"), so the query stays here.
  const brandsQuery = useBrands();
  const apiBrandRows = brandsQuery.data?.brands ?? [];

  // FORMS-1 STEP 2 — the logged-in pre-verify effect and the local→context
  // sync effect were both removed with the inline selector. The cart form's
  // shared selector writes vehicle/location to context directly, and auth
  // prefill of the vehicle now happens inside the shared selector
  // (useSelectorState seeds from booking context / saved car).

  if (isLoadingDetail) {
    return (
      <div className="pt-8 pb-24">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div className="lg:col-span-2 space-y-8">
              <div className="h-8 w-1/2 bg-neutral-200 animate-pulse" />
              <div className="bg-white border border-border p-6 space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                  <div key={i} className="h-12 bg-neutral-100 animate-pulse" />
                ))}
              </div>
            </div>
            <aside className="space-y-4">
              <div className="bg-white border border-border p-6 h-[420px] animate-pulse" />
            </aside>
          </div>
        </div>
      </div>
    );
  }

  if (!category) {
    return (
      <div className="p-20 text-center">
        {detailQuery.error ? `Error: ${detailQuery.error}` : "Category not found."}
      </div>
    );
  }

  const subServices = apiSubServices;

  // ---------- Derived helpers ----------
  const selectedLocationName =
    serviceCenters.find((l) => l.slug === bookingLocation)?.name || "your area";

  const cityWord = "Delhi NCR";
  // Brand names — pure API. Empty during initial load; the modal/list
  // rendering branches handle skeletons explicitly.
  const brandList = apiBrandRows.map((b) => b.title);

  // scrollToSection now provided by useSubNavSync above.

  // ---------- Add-to-cart handler with brief flash feedback ----------
  const handleAddToCart = async (sub: ApiSubService) => {
    try {
      await addItem({
        serviceId: String(sub.id),
        title: sub.title,
        price: Number(sub.price) || 0,
        categorySlug: category.slug,
        car: bookingCar || undefined,
        location: selectedLocationName,
        brand_id: bookingCar?.brand_id,
        model_id: bookingCar?.model_id,
        fuel_id:  bookingCar?.fuel_id,
      });
      setAddedFlash(String(sub.id));
      window.setTimeout(() => setAddedFlash(null), 1800);
    } catch (err) {
      if (err instanceof VehicleConflictError) {
        setVehicleConflict(err.details);
      }
      // Other errors already logged by useCart.
    }
  };

  const confirmReplaceVehicle = async () => {
    if (!vehicleConflict) return;
    setReplacing(true);
    try {
      await replaceVehicleInCart(vehicleConflict.pendingItem);
      setVehicleConflict(null);
    } finally {
      setReplacing(false);
    }
  };

  // FORMS-1 STEP 2 — all booking-card + car-selector handlers (onPhoneChange,
  // sendOtp, verifyOtp, checkPrices, openCarSelector, selectBrand/Model/Fuel,
  // carBack, …) were deleted with the inline form. Vehicle selection now
  // happens in the shared VehicleSelector launched from the cart form.

  // ---------- Static page content ----------
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
      desc: `Detailed assessment of ${category.title.toLowerCase()} requirements with computerised diagnostics.`,
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

  const faqs = [
    {
      q: `How long does ${category.title.toLowerCase()} take in ${selectedLocationName}?`,
      a: `Most ${category.title.toLowerCase()} jobs are completed the same day at our ${selectedLocationName} centre. Exact time depends on car make, model and parts required — our advisors share a precise turnaround once the vehicle is inspected.`,
    },
    {
      q: `What is the ${category.title.toLowerCase()} cost in ${selectedLocationName}?`,
      a: `${category.title} starts from competitive market rates at our ${selectedLocationName} centre. Final cost depends on car make, model, parts grade (OEM vs aftermarket) and any additional repairs needed. We share a transparent quote upfront — what you're quoted is exactly what you pay.`,
    },
    {
      q: "Is there a warranty on the work?",
      a: `Yes — every ${category.title.toLowerCase()} job carries a written warranty card. If any issue arises within the warranty period we will diagnose and resolve it free of charge at any of our service centres in ${cityWord}.`,
    },
    {
      q: "Do you use genuine spare parts?",
      a: `Absolutely. We strictly use 100% genuine OEM and OES parts for every ${category.title.toLowerCase()} job, sourced through authorised channels — never grey market — ensuring the highest quality and longest service life for your car.`,
    },
    {
      q: "Can I claim insurance for this repair?",
      a: `Yes. We have direct tie-ups with all major insurance providers and offer a fully cashless facility for covered ${category.title.toLowerCase()} services at our ${selectedLocationName} centre.`,
    },
    {
      q: `Where can I find ${category.title.toLowerCase()} near me in ${selectedLocationName}?`,
      a: `We operate certified service centres across ${cityWord} including ${(
        serviceCenters.length > 0
          ? serviceCenters.slice(0, 3).map((l) => l.name).join(", ")
          : "Moti Nagar, Gurugram, Noida"
      )} and more. Free pickup and drop is available within our service radius.`,
    },
    {
      q: "Is pickup and drop available?",
      a: "Yes, we provide complimentary pickup and drop service across our service radius. Just let us know your preferred slot when booking — our driver collects the vehicle, returns it after service is complete and shares live status updates throughout.",
    },
  ];

  return (
    <>
      {/* Phase 4.5c — category-level SEO via cascade. Admin-edited
          meta on the ServiceCategory record wins; otherwise site
          defaults render through getSeoData(). */}
      {detailQuery.data?.seo && <SeoHead seo={detailQuery.data.seo} />}
      {/* Phase 2b-cont — center content only. ServicesShell owns the
          PageBanner, the sticky cross-category bar, the grid and the single
          CarSidebar. Phase 2d (D-2d-4b) — the in-page section-nav scroller
          was removed; the shell's cross-category bar is the only nav. */}
      <div className="space-y-12">
              {/* SERVICE CATALOG — GoMechanic-style cards lead the page (Phase 2 D-2-6). */}
              <section id="pricing" data-subnav-section="pricing" className="scroll-mt-40">
                <div className="flex items-baseline justify-between flex-wrap gap-2 mb-4">
                  <SectionHeading className="mb-0">{`${category.title} Services`}</SectionHeading>
                  <p className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-neutral-400">
                    {subServices.length} services · {selectedLocationName}
                  </p>
                </div>

                {/* D-2d-3 — the "Prices personalised for {CAR}" blue pill was
                    removed (the CarSidebar already shows the selected car).
                    Only the select-your-car nudge remains, until a vehicle
                    is picked. */}
                {!vehicleSelected && (
                  <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="bg-neutral-50 border border-dashed border-primary/40 px-4 py-4 mb-5 flex items-center justify-between gap-4 flex-wrap"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <Calculator className="w-5 h-5 text-primary shrink-0" />
                      <p className="text-xs sm:text-sm font-bold text-neutral-700 tracking-tighter">
                        Select your car & location to see exact prices.
                      </p>
                    </div>
                    <button
                      onClick={() => {
                        // Scroll to the cart-form sidebar; its "Select your
                        // car" empty state opens the shared selector modal.
                        window.scrollTo({ top: 0, behavior: "smooth" });
                      }}
                      className="btn-ink btn-ink-primary px-5 py-2.5 text-[10px] sm:text-xs font-black uppercase tracking-widest flex items-center gap-2 shrink-0"
                    >
                      Select Your Car{" "}
                      <ArrowRight className="w-3.5 h-3.5 btn-arrow" />
                    </button>
                  </motion.div>
                )}

                <div className="space-y-4">
                  {subServices.map((sub) => {
                    // Price 4-state + cart logic UNCHANGED (D-2-6 / D-2c-5);
                    // computed here, the shared ServiceCard just renders it.
                    const showPrice = vehicleSelected && priceShowFromApi;
                    const cartItem = cartReady
                      ? findCartItem({
                          ref_id:   sub.id,
                          brand_id: bookingCar?.brand_id,
                          model_id: bookingCar?.model_id,
                          fuel_id:  bookingCar?.fuel_id,
                        })
                      : null;
                    return (
                      <ServiceCard
                        key={sub.id}
                        service={sub}
                        categorySlug={category.slug}
                        categoryTitle={category.title}
                        fallbackIcon={categoryIcon(category.slug)}
                        vehicleSelected={vehicleSelected}
                        showPrice={showPrice}
                        pricingLoading={pricingLoading}
                        price={priceMap.get(sub.id) ?? null}
                        inCart={!!cartItem}
                        justAdded={addedFlash === String(sub.id)}
                        onAdd={() => handleAddToCart(sub)}
                        onRemove={() => cartItem && removeItem(String(cartItem.id))}
                        onViewDetail={() => navigate(`/services/${category.slug}/${sub.slug}`)}
                      />
                    );
                  })}
                </div>

                <p className="text-[10px] text-neutral-400 mt-3 italic">
                  * Prices may vary based on car model, fuel type and parts
                  required. Final quote provided after vehicle inspection.
                </p>

                {/* Phase 2.5.5 — the mid-page "X service in your cart"
                    strip lived here (D-2.5.5-2). Removed per UX audit;
                    the contextual SmartMiniCart in the right sidebar
                    now owns this surface, and the page flows directly
                    from price-list to "Services Included". */}
              </section>

              {/* OVERVIEW — demoted below the catalog (Phase 2 D-2-9). */}
              <section
                id="overview"
                data-subnav-section="overview"
                className="bg-neutral-50 p-6 sm:p-8 border border-border scroll-mt-40"
              >
                <h2 className="section-heading mb-5">
                  {category.title.split(" ")[0]}{" "}
                  <span className="text-primary">
                    {category.title.split(" ").slice(1).join(" ") || "OVERVIEW."}
                  </span>
                </h2>
                <p className="text-sm sm:text-base text-neutral-600 leading-relaxed mb-6">
                  Professional{" "}
                  <strong className="text-neutral-900">{category.title}</strong>{" "}
                  by certified technicians using genuine OEM parts. Our{" "}
                  {category.title.toLowerCase()} workshop in{" "}
                  {selectedLocationName} combines factory-grade equipment with
                  skilled craftsmanship to deliver work that lasts. We service
                  every major Indian and international car brand —{" "}
                  {brandList.slice(0, 5).join(", ")} and more.
                </p>

                <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 pb-6 border-b border-border">
                  <div>
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Services
                    </h4>
                    <p className="text-base font-black text-neutral-900">
                      {subServices.length}+ Options
                    </p>
                  </div>
                  <div>
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Brands
                    </h4>
                    <p className="text-base font-black text-neutral-900">
                      {brandList.length}+ Supported
                    </p>
                  </div>
                  <div className="col-span-2 sm:col-span-1">
                    <h4 className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">
                      Warranty
                    </h4>
                    <p className="text-base font-black text-neutral-900">
                      Standard Terms
                    </p>
                  </div>
                </div>
              </section>

              {/* SERVICES INCLUDED */}
              <section id="services" data-subnav-section="services" className="scroll-mt-40">
                <h2 className="section-heading mb-5">
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
              <section
                id="why-us"
                data-subnav-section="why-us"
                className="scroll-mt-40"
              >
                <h2 className="section-heading mb-5">
                  WHY <span className="text-primary">CHOOSE US.</span>
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
              <section id="process" data-subnav-section="process" className="scroll-mt-40">
                <h2 className="section-heading mb-5">
                  HOW IT <span className="text-primary">WORKS.</span>
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

              {/* CUSTOMER REVIEWS */}
              <section id="reviews" data-subnav-section="reviews" className="scroll-mt-40">
                <h2 className="section-heading mb-5">
                  CUSTOMER <span className="text-primary">REVIEWS.</span>
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {TESTIMONIALS.slice(0, 2).map((t, i) => (
                    <div
                      key={i}
                      className="bg-white p-5 sm:p-6 border border-border"
                    >
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

              {/* FAQs */}
              <section id="faqs" data-subnav-section="faqs" className="scroll-mt-40">
                <h2 className="section-heading mb-5">
                  COMMON <span className="text-primary">QUESTIONS.</span>
                </h2>
                <FAQAccordion faqs={faqs} />
              </section>

              {/* Phase 2d (D-2d-4a) — "Brands We Service" section removed.
                  (brandsQuery/brandList still feed the Overview + Why-ACR copy.) */}

              {/* LOCATION-BASED CONTENT */}
              <section
                id="why-acr"
                data-subnav-section="why-acr"
                className="bg-neutral-50 p-6 sm:p-8 border border-border scroll-mt-40"
              >
                <h2 className="text-xl sm:text-2xl uppercase font-black text-neutral-900 mb-3 tracking-tighter">
                  Why Choose ACR for{" "}
                  <span className="text-primary">
                    {category.title} in {selectedLocationName}.
                  </span>
                </h2>
                <div className="text-sm text-neutral-600 leading-relaxed space-y-3">
                  <p>
                    Driving in {selectedLocationName} puts unique demands on
                    your car. Long commutes, dust and stop-start city traffic
                    accelerate wear on critical systems — which is why{" "}
                    {category.title.toLowerCase()} from a workshop that knows{" "}
                    {cityWord} conditions matters. Our {selectedLocationName}{" "}
                    centre is staffed by certified technicians, equipped with
                    factory-grade tools and stocks 100% genuine OEM parts
                    on-site.
                  </p>
                  <p>
                    Whether you drive a {brandList[0] ?? "popular Indian"}, a{" "}
                    {brandList[1] ?? "mid-segment"}, or a premium European
                    brand, our advisors share a transparent quote upfront —
                    what you're quoted is exactly what you pay. Every job is
                    backed by a written warranty card and a 50-point
                    post-service quality check before delivery.
                  </p>
                  <p>
                    Visit any of our{" "}
                    <button
                      onClick={() => navigate("/service-centers")}
                      className="text-primary font-bold hover:underline"
                    >
                      certified service centres in {cityWord}
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
                </div>
              </section>
      </div>

      <VehicleReplaceModal
        open={vehicleConflict !== null}
        details={vehicleConflict}
        onConfirm={confirmReplaceVehicle}
        onClose={() => setVehicleConflict(null)}
        pending={replacing}
      />
    </>
  );
}

