import type * as React from "react";
import { useState, useEffect, useMemo } from "react";
import { motion, AnimatePresence } from "motion/react";
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
} from "lucide-react";
import {
  TESTIMONIALS,
  LOCATIONS,
} from "../data/businessData";
import { useBrands } from "../hooks/useVehicle";
import { useSubNavSync } from "../hooks/useSubNavSync";
import PageBanner from "../components/PageBanner";
import SeoHead from "../components/SeoHead";
import VehicleReplaceModal from "../components/VehicleReplaceModal";
import FAQAccordion from "../components/FAQAccordion";
import { CarSidebar } from "../components/car-sidebar";
import { useCart } from "../hooks/useCart";
import { VehicleConflictError, type VehicleConflictDetails } from "../lib/errors";
import { useAuth } from "../hooks/useAuth";
import { useBookingContext } from "../hooks/useBookingContext";
import {
  fetchCategoryDetail,
  type SubService as ApiSubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";

interface ServiceCategoryProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

// ---------- Constants ----------
// FORMS-1 STEP 2 — FUEL_TYPES removed with the bespoke inline car
// selector. Fuel options now come from the shared VehicleSelector
// (FuelStep) via the cart form's vehicle-change modal.

// Phase 2.5.9 — sub-nav lists EVERY visible content section in
// the page body, in render order. Adding gap-sections (Why Us /
// Brands / Why ACR) prevents the "stuck on previous section"
// drift the operator reported on 2.5.8 testing.
const SECTION_NAV = [
  { id: "overview",  label: "Overview" },
  { id: "pricing",   label: "Pricing" },
  { id: "services",  label: "Services" },
  { id: "why-us",    label: "Why Us" },     // 2.5.9 — was un-tracked
  { id: "process",   label: "Process" },
  { id: "reviews",   label: "Reviews" },
  { id: "faqs",      label: "FAQs" },
  { id: "brands",    label: "Brands" },     // 2.5.9 — was un-tracked
  { id: "why-acr",   label: "Why ACR" },    // 2.5.9 — was un-tracked
] as const;

// Site Header is `sticky top-0 z-[9999]` — top blue bar (~32px) + main (h-20=80px).
// Section nav stacks below the FULL header (~112px), not just the main bar.
// Phase 2.5.7 — the right-side aside uses `top: STICKY_OFFSET_PX + 68 = 180px`
// so it sits BELOW the 52px sub-nav strip with a 16px buffer.
const STICKY_OFFSET_PX = 112;

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

  // ---------- Section nav scroll spy (Phase 2.5.7 hard-fix) ----------
  // useSubNavSync queries `[data-subnav-section]` to find sections.
  // The rebindKey combines the categorySlug AND the loading state
  // so the IntersectionObserver re-binds:
  //   - when the user navigates between categories (DOM nodes get
  //     replaced even though slug list is unchanged), AND
  //   - when the page transitions from skeleton to loaded content
  //     (the `if (isLoadingDetail) return <Skeleton/>` early return
  //     means sections don't exist on first mount — without
  //     re-binding, the observer registers nothing and stays dead
  //     for the lifetime of the component).
  // The latter was the actual bug operator hit: the underline
  // stayed on OVERVIEW because the observer never observed the
  // real sections.
  const {
    activeSlug: activeSection,
    setActiveSlugManual,
    scrollToSection,
    navRef: subNavRef,
  } = useSubNavSync({
    stickyOffsetPx: STICKY_OFFSET_PX,
    rebindKey: `${categorySlug}:${detailQuery.isLoading ? "loading" : "ready"}`,
  });

  // ---------- Shared booking context (read-only on this page) ----------
  // FORMS-1 STEP 2 — the bespoke inline car-selector + OTP form that used
  // to own location/car/phone here is gone. The right-column CART form now
  // owns vehicle/location selection and writes the chosen vehicle into
  // booking context; this page READS it back so the price list and
  // add-to-cart stay in sync. No local mirrors, no sync effect.
  const bookingLocation = bookingCtx0.location || LOCATIONS[0]?.id || "";
  const bookingCar = bookingCtx0.car;

  // ---------- Vehicle data for page content (supported-brands copy) ----------
  // Only the brands query remains — the model/fuel queries lived in the
  // deleted inline selector. Brands feed the overview/SEO copy ("X, Y and
  // more", "{N}+ supported"), so the query stays here.
  const brandsQuery = useBrands();
  const apiBrandRows = brandsQuery.data?.brands ?? [];

  // ---------- Section nav active reset on category navigation ----------
  // When the user navigates between categories, snap the underline
  // to OVERVIEW immediately — the IntersectionObserver will reconcile
  // once the new sections render and observation kicks in.
  useEffect(() => {
    setActiveSlugManual("overview");
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [categorySlug]);

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
    LOCATIONS.find((l) => l.id === bookingLocation)?.name || "your area";

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
      a: `We operate certified service centres across ${cityWord} including ${LOCATIONS.slice(
        0,
        3
      )
        .map((l) => l.name)
        .join(
          ", "
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
      {/* Banner — title only, NO location appended (avoids wrapping/cropping) */}
      <PageBanner
        title={category.title}
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Services", href: "/services" },
          { label: category.title },
        ]}
      />

      {/* ──────────── STICKY SECTION NAV ──────────── */}
      <nav
        className="sticky z-30 bg-white border-b border-border"
        style={{ top: `${STICKY_OFFSET_PX}px` }}
      >
        <div className="site-container">
          <div
            ref={subNavRef as React.RefObject<HTMLDivElement>}
            className="flex gap-1 sm:gap-2 overflow-x-auto"
            style={{ scrollbarWidth: "none" }}
          >
            {/* Phase 2.5.5 — sub-nav is section-anchors only (D-2.5.5-1).
                The previous "CART (N)" link was redundant with the
                top-header cart icon and the contextual SmartMiniCart
                in the right sidebar; removed per UX audit. */}
            {SECTION_NAV.map((s) => (
              <button
                key={s.id}
                data-subnav-link={s.id}
                onClick={() => scrollToSection(s.id)}
                className={`text-[10px] sm:text-xs uppercase tracking-widest font-bold py-4 px-3 sm:px-5 whitespace-nowrap border-b-2 transition-colors shrink-0 ${
                  activeSection === s.id
                    ? "border-primary text-primary"
                    : "border-transparent text-neutral-500 hover:text-primary"
                }`}
              >
                {s.label}
              </button>
            ))}
          </div>
        </div>
      </nav>

      <div className="pb-14 pt-8">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
            {/* ──────────── MAIN CONTENT ──────────── */}
            <main className="lg:col-span-2 order-2 lg:order-1 space-y-12">
              {/* OVERVIEW */}
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

              {/* PRICING TABLE — with Add to Cart per row */}
              <section id="pricing" data-subnav-section="pricing" className="scroll-mt-40">
                <div className="flex items-baseline justify-between flex-wrap gap-2 mb-2">
                  <h2 className="section-heading">
                    {category.title}{" "}
                    <span className="section-heading-accent">PRICE LIST.</span>
                  </h2>
                  <p className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-neutral-400">
                    {selectedLocationName} · {new Date().getFullYear()}
                  </p>
                </div>

                {vehicleSelected && bookingCar && (
                  <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="bg-primary text-white px-4 py-3 mb-5 flex items-center gap-3"
                  >
                    <CheckCircle2 className="w-5 h-5 shrink-0" />
                    <p className="text-xs sm:text-sm font-bold tracking-tighter">
                      Prices personalised for{" "}
                      <span className="uppercase">
                        {bookingCar.brand} {bookingCar.model} ·{" "}
                        {bookingCar.fuel}
                      </span>{" "}
                      in {selectedLocationName}
                    </p>
                  </motion.div>
                )}

                {/* CTA banner shown until a complete vehicle is selected */}
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

                <div className="bg-white border border-border divide-y divide-border">
                  <div className="hidden sm:grid grid-cols-[1fr_auto_auto] gap-4 px-5 py-3 bg-neutral-50 text-[10px] font-bold uppercase tracking-widest text-neutral-400">
                    <span>Service Type</span>
                    <span className="text-right w-28">Price From</span>
                    <span className="text-right w-32">Action</span>
                  </div>

                  {subServices.map((sub) => {
                    const justAdded = addedFlash === String(sub.id);
                    // Reveal prices as soon as a complete vehicle is
                    // selected AND the API marks the category as priced
                    // for it. No OTP gate — OTP is only for checkout.
                    const showPrice = vehicleSelected && priceShowFromApi;
                    // Phase 2.3.3 — toggle add/remove on the same button.
                    // First click: addItem. Second click on the same row:
                    // remove the server cart line. The 1.8 s `justAdded`
                    // flash continues to bridge the visual gap between
                    // the click and the React Query refetch.
                    const cartItem = cartReady
                      ? findCartItem({
                          ref_id:   sub.id,
                          brand_id: bookingCar?.brand_id,
                          model_id: bookingCar?.model_id,
                          fuel_id:  bookingCar?.fuel_id,
                        })
                      : null;
                    const inCart = !!cartItem;
                    return (
                      <div
                        key={sub.id}
                        className="px-4 sm:px-5 py-4 grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 sm:gap-4 sm:items-center"
                      >
                        <div className="min-w-0">
                          <button
                            onClick={() =>
                              navigate(`/services/${category.slug}/${sub.slug}`)
                            }
                            className="text-left text-sm font-black uppercase text-neutral-900 tracking-tighter mb-0.5 hover:text-primary transition-colors"
                          >
                            {sub.title}
                          </button>
                          <p className="text-xs text-neutral-500 leading-relaxed line-clamp-2">
                            {sub.recommended_info ||
                              `Professional ${sub.title.toLowerCase()} with genuine parts and warranty.`}
                          </p>
                        </div>

                        {/* Price column — Phase 2.3.5 strict 4-state machine.
                            Never base_price. Vehicle-resolved or "Quote on
                            Inspection"; loading skeleton between. */}
                        <div className="sm:text-right sm:w-28">
                          {showPrice ? (
                            pricingLoading ? (
                              <div className="sm:ml-auto h-5 w-16 bg-neutral-200 animate-pulse rounded" />
                            ) : priceMap.has(sub.id) ? (
                              <>
                                <p className="text-base font-black text-neutral-900">
                                  ₹{priceMap.get(sub.id)}
                                </p>
                                <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                                  Onwards
                                </span>
                              </>
                            ) : (
                              <>
                                <p className="text-base font-black text-neutral-900">
                                  Quote
                                </p>
                                <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                                  On Inspection
                                </span>
                              </>
                            )
                          ) : (
                            // No OTP lock anymore — prices reveal on vehicle
                            // selection. !vehicle → invite selection.
                            <span className="text-[10px] uppercase tracking-widest font-bold text-neutral-400">
                              {vehicleSelected ? "On Inspection" : "Select car"}
                            </span>
                          )}
                        </div>

                        {/* Action column — Add to Cart once a vehicle is set; else CTA */}
                        <div className="sm:w-32 sm:text-right">
                          {showPrice ? (
                            <button
                              onClick={() =>
                                inCart && cartItem
                                  ? removeItem(String(cartItem.id))
                                  : handleAddToCart(sub)
                              }
                              // Phase 2.3.5 — ADDED hover matches BOOK
                              // NOW's btn-ink ink-sweep for visible
                              // feedback. ADD TO CART uses btn-ink-primary
                              // (sweep to primary-dark); ADDED uses
                              // btn-ink-outline (sweep fills primary,
                              // text turns white on hover). Both share
                              // identical box dimensions.
                              className={`btn-ink ${
                                inCart || justAdded
                                  ? "btn-ink-outline"
                                  : "btn-ink-primary"
                              } px-4 py-2 text-[10px] font-bold uppercase tracking-widest w-full sm:w-auto justify-center gap-1.5`}
                              aria-pressed={inCart}
                            >
                              {inCart || justAdded ? (
                                <>
                                  <CheckCircle2 className="w-3.5 h-3.5" /> Added
                                </>
                              ) : (
                                <>
                                  <ShoppingCart className="w-3.5 h-3.5" /> Add to
                                  Cart
                                </>
                              )}
                            </button>
                          ) : (
                            <button
                              onClick={() =>
                                vehicleSelected
                                  ? navigate(`/services/${category.slug}/${sub.slug}`)
                                  : window.scrollTo({ top: 0, behavior: "smooth" })
                              }
                              className="px-4 py-2 text-[10px] font-bold uppercase tracking-widest border border-primary text-primary hover:bg-primary hover:text-white transition-colors w-full sm:w-auto flex items-center justify-center gap-1.5"
                            >
                              {vehicleSelected ? "View Details" : "Select Your Car"}{" "}
                              <ArrowRight className="w-3.5 h-3.5" />
                            </button>
                          )}
                        </div>
                      </div>
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

              {/* BRANDS WE SERVICE */}
              <section
                id="brands"
                data-subnav-section="brands"
                className="scroll-mt-40"
              >
                <h2 className="section-heading mb-1.5">
                  BRANDS WE <span className="text-primary">SERVICE.</span>
                </h2>
                <p className="text-xs text-neutral-500 mb-5 max-w-xl leading-relaxed">
                  Authorised-grade {category.title.toLowerCase()} for every
                  major Indian and international car brand.
                </p>
                <div className="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3">
                  {brandsQuery.isLoading
                    ? Array.from({ length: 12 }).map((_, i) => (
                        <div
                          key={`bgsk-${i}`}
                          className="bg-neutral-100 border border-border aspect-square animate-pulse"
                        />
                      ))
                    : brandList.map((brand) => (
                        <div
                          key={brand}
                          className="bg-white border border-border p-3 sm:p-4 flex flex-col items-center justify-center text-center hover:border-primary transition-colors aspect-square"
                        >
                          <div className="w-8 h-8 sm:w-10 sm:h-10 bg-primary/10 text-primary flex items-center justify-center font-black text-base sm:text-lg uppercase tracking-tighter mb-1.5">
                            {brand.charAt(0)}
                          </div>
                          <span className="text-[9px] sm:text-[10px] font-bold uppercase tracking-tighter text-neutral-900 leading-tight">
                            {brand}
                          </span>
                        </div>
                      ))}
                </div>
              </section>

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
            </main>

            {/* ───── BOOKING SIDEBAR (cart form) — FORMS-1 STEP 2 ─────
                Category now shows the modular CART form (vehicle summary +
                services cart + coupon + checkout) instead of the bespoke
                inline car-selector + OTP that used to live here.
                currentService omitted → no "Add to cart" CTA and no
                auto-add; the form reflects the existing cart and its
                "Select your car" empty state opens the shared
                VehicleSelector (in-place), which writes the vehicle into
                booking context. This page reads that back (vehicleSelected)
                to reveal prices. The component renders its own sticky
                <aside> + fixed mobile bar, so it mounts as the right grid
                column directly. (The old sidebar trust-badge mini-card was
                dropped — the WHY CHOOSE section in the main column already
                carries the same trust content.) */}
            <CarSidebar
              categorySlug={categorySlug}
              stickyTopPx={STICKY_OFFSET_PX + 68}
              className="lg:order-2"
            />
          </div>
        </div>
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

