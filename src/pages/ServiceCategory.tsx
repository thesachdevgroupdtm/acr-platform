import { useState, useEffect, useMemo } from "react";
import { motion, AnimatePresence } from "motion/react";
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
import { useBrands, useModels, useFuels } from "../hooks/useVehicle";
import PageBanner from "../components/PageBanner";
import VehicleReplaceModal from "../components/VehicleReplaceModal";
import { useCart } from "../hooks/useCart";
import { VehicleConflictError, type VehicleConflictDetails } from "../lib/errors";
import { useAuth } from "../hooks/useAuth";
import { useBookingContext } from "../hooks/useBookingContext";
import {
  fetchCategoryDetail,
  type SubService as ApiSubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import { usePricingFor } from "../hooks/usePricing";

interface ServiceCategoryProps {
  categorySlug: string;
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

// ---------- Constants ----------
const FUEL_TYPES = [
  { id: "petrol", name: "Petrol", icon: Droplet },
  { id: "diesel", name: "Diesel", icon: Fuel },
  { id: "cng", name: "CNG", icon: Wind },
  { id: "electric", name: "Electric", icon: BatteryCharging },
] as const;

const SECTION_NAV = [
  { id: "overview", label: "Overview" },
  { id: "pricing", label: "Pricing" },
  { id: "services", label: "Services" },
  { id: "process", label: "Process" },
  { id: "reviews", label: "Reviews" },
  { id: "faqs", label: "FAQs" },
] as const;

// Site Header is `sticky top-0 z-[9999]` — top blue bar (~32px) + main (h-20=80px).
// Section nav stacks below the FULL header (~112px), not just the main bar.
const STICKY_OFFSET_PX = 112;

export default function ServiceCategory({
  categorySlug,
  setCurrentPage,
  openEstimate,
}: ServiceCategoryProps) {
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

  // Phase 2.3.5 — POST /pricing in parallel for explicit
  // matched_prices, so per-service "no priced row" can be detected
  // without trusting `sub.price` (which the backend silently falls
  // back to base_price when no row matches). priceMap drives the
  // 4-state machine in the price column.
  const subServiceIds = useMemo(
    () => apiSubServices.map((s) => s.id),
    [apiSubServices]
  );
  const vehicleSelected = !!(
    bookingCtx0.car?.brand_id &&
    bookingCtx0.car?.model_id &&
    bookingCtx0.car?.fuel_id
  );
  const pricingReq = useMemo(() => {
    if (!vehicleSelected || subServiceIds.length === 0) return null;
    return {
      brand_id:     bookingCtx0.car!.brand_id!,
      model_id:     bookingCtx0.car!.model_id!,
      fuel_type_id: bookingCtx0.car!.fuel_id!,
      service_ids:  subServiceIds,
    };
  }, [vehicleSelected, bookingCtx0.car, subServiceIds]);
  const pricingQuery = usePricingFor(pricingReq);
  const priceMap = useMemo(() => {
    const m = new Map<number, number>();
    for (const p of pricingQuery.data?.matched_prices ?? []) {
      m.set(p.service_id, p.price);
    }
    return m;
  }, [pricingQuery.data]);
  const pricingLoading =
    vehicleSelected && pricingQuery.isFetching && pricingQuery.data === undefined;
  // For sections that need a stable display object — never null in render below;
  // skeleton path returns early when no category resolved.
  const category = apiCategory;

  // ---------- Cart ----------
  const { addItem, count, isInCart, findCartItem, removeItem, replaceVehicleInCart } = useCart();
  const [addedFlash, setAddedFlash] = useState<string | null>(null);
  const [vehicleConflict, setVehicleConflict] = useState<VehicleConflictDetails | null>(null);
  const [replacing, setReplacing] = useState(false);

  // ---------- Auth (drives phone prefill + OTP skip) ----------
  const { user, isAuthenticated } = useAuth();

  // ---------- Section nav scroll spy ----------
  const [activeSection, setActiveSection] = useState<string>("overview");

  // ---------- Shared booking context (syncs with ServiceDetail child page) ----------
  // The user fills location/car/phone ONCE here on the parent category page
  // and the same details auto-flow to any child service page they drill into.
  const { state: bookingCtx, update: updateBookingCtx } = useBookingContext();

  // ---------- Sticky Booking Card state ----------
  // These are local mirrors of the shared context. We hydrate from context
  // on mount and push every change back to context so child pages stay synced.
  const [bookingLocation, setBookingLocation] = useState<string>(
    bookingCtx.location || LOCATIONS[0]?.id || ""
  );
  const [bookingCar, setBookingCar] = useState<{
    brand: string;
    model: string;
    fuel: string;
    /** Phase 2.3.3 — IDs/slugs captured by the in-page picker so the
     *  /services/{slug} query receives proper vehicle context and the
     *  resulting prices match ServiceDetail's Pricing tab. */
    brand_id?: number;
    model_id?: number;
    fuel_id?: number;
    brand_slug?: string;
    model_slug?: string;
    fuel_slug?: string;
  } | null>(bookingCtx.car);
  const [bookingPhone, setBookingPhone] = useState(bookingCtx.phone || "");
  const [otpSent, setOtpSent] = useState(bookingCtx.otpVerified);
  const [otpValue, setOtpValue] = useState("");
  const [otpVerified, setOtpVerified] = useState(bookingCtx.otpVerified);
  const [bookingErrors, setBookingErrors] = useState<Record<string, string>>(
    {}
  );
  const [pricesShown, setPricesShown] = useState(bookingCtx.pricesShown);

  // ---------- Car Selector Modal state ----------
  const [showCarSelector, setShowCarSelector] = useState(false);
  const [carStep, setCarStep] = useState<1 | 2 | 3>(1);
  const [pendingCar, setPendingCar] = useState<{
    brand: string;
    brandId: number | null;
    brandSlug: string | null;
    model: string;
    modelId: number | null;
    modelSlug: string | null;
  }>({ brand: "", brandId: null, brandSlug: null, model: "", modelId: null, modelSlug: null });
  const [carSearch, setCarSearch] = useState("");

  // ---------- Vehicle picker — pure API via React Query ----------
  const brandsQuery = useBrands();
  const modelsQuery = useModels(
    showCarSelector && carStep === 2 ? pendingCar.brandId : null
  );
  const fuelsQuery = useFuels(
    showCarSelector && carStep === 3 ? pendingCar.brandId : null,
    showCarSelector && carStep === 3 ? pendingCar.modelId : null,
  );
  const apiBrandRows = brandsQuery.data?.brands ?? [];
  const apiModelRows = modelsQuery.data?.models ?? [];
  const apiFuelRows  = fuelsQuery.data?.fuels   ?? [];

  // ---------- Section nav scroll spy ----------
  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort(
            (a, b) => a.boundingClientRect.top - b.boundingClientRect.top
          );
        if (visible[0]) setActiveSection(visible[0].target.id);
      },
      { rootMargin: "-30% 0px -60% 0px", threshold: 0 }
    );
    SECTION_NAV.forEach((s) => {
      const el = document.getElementById(s.id);
      if (el) observer.observe(el);
    });
    return () => observer.disconnect();
  }, [categorySlug]);

  // ---------- Pre-verify booking card for logged-in users ----------
  // The user has already passed phone+email OTP at signup. Skip the OTP step
  // and just trust their verified phone. They click "Check Prices" directly.
  useEffect(() => {
    if (isAuthenticated && user) {
      setBookingPhone(user.phone);
      setOtpSent(true);
      setOtpVerified(true);
      // Auto-fill saved car & location if user has them
      if (user.defaultCar && !bookingCar) setBookingCar(user.defaultCar);
      if (user.defaultLocation && !bookingCtx.location)
        setBookingLocation(user.defaultLocation);
    }
    // bookingCar/bookingCtx.location intentionally omitted - one-time defaults
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated, user]);

  // ---------- Sync local booking state → shared context ----------
  // Every booking-state change is mirrored to the context so any child
  // service page rendered next will see the same details.
  useEffect(() => {
    updateBookingCtx({
      location: bookingLocation,
      car: bookingCar,
      phone: bookingPhone,
      otpVerified,
      pricesShown,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [bookingLocation, bookingCar, bookingPhone, otpVerified, pricesShown]);

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
  const selectedCarLabel = bookingCar
    ? `${bookingCar.brand} ${bookingCar.model}, ${bookingCar.fuel}`
    : "Select Your Car";

  const cityWord = "Delhi NCR";
  // Brand names — pure API. Empty during initial load; the modal/list
  // rendering branches handle skeletons explicitly.
  const brandList = apiBrandRows.map((b) => b.title);

  const scrollToSection = (id: string) => {
    const el = document.getElementById(id);
    if (!el) return;
    const top =
      el.getBoundingClientRect().top + window.scrollY - (STICKY_OFFSET_PX + 60);
    window.scrollTo({ top, behavior: "smooth" });
  };

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

  // ---------- Booking handlers ----------
  const onPhoneChange = (val: string) => {
    const digits = val.replace(/\D/g, "").slice(0, 10);
    setBookingPhone(digits);
    if (bookingErrors.phone)
      setBookingErrors((e) => ({ ...e, phone: "" }));
    if (otpSent) {
      setOtpSent(false);
      setOtpValue("");
      setOtpVerified(false);
    }
  };

  const sendOtp = () => {
    const errs: Record<string, string> = {};
    if (!bookingLocation) errs.location = "Please select a location";
    if (!bookingCar) errs.car = "Please select your car";
    if (!bookingPhone) errs.phone = "Phone number is required";
    else if (!/^\d{10}$/.test(bookingPhone))
      errs.phone = "Enter exactly 10 digits";
    setBookingErrors(errs);
    if (Object.keys(errs).length > 0) return;
    setOtpSent(true);
    setOtpValue("");
    setOtpVerified(false);
  };

  const verifyOtp = () => {
    if (otpValue.length < 4) {
      setBookingErrors({ otp: "Enter the OTP sent to your phone" });
      return;
    }
    setOtpVerified(true);
    setBookingErrors({});
  };

  const checkPrices = () => {
    if (!otpVerified) return;
    setPricesShown(true);
    setTimeout(() => scrollToSection("pricing"), 50);
  };

  // ---------- Car Selector handlers ----------
  const openCarSelector = () => {
    setShowCarSelector(true);
    setCarStep(1);
    setCarSearch("");
    if (bookingErrors.car) setBookingErrors((e) => ({ ...e, car: "" }));
  };
  const closeCarSelector = () => {
    setShowCarSelector(false);
    setCarStep(1);
    setCarSearch("");
  };
  const selectBrand = (brand: string, brandId: number | null = null) => {
    const row = apiBrandRows.find((b) => b.id === brandId);
    setPendingCar({
      brand,
      brandId,
      brandSlug: row?.slug ?? null,
      model: "",
      modelId: null,
      modelSlug: null,
    });
    setCarStep(2);
    setCarSearch("");
  };
  const selectModel = (model: string) => {
    const row = apiModelRows.find((m) => (m.title || m.name) === model);
    setPendingCar({
      ...pendingCar,
      model,
      modelId: row?.id ?? null,
      modelSlug: row?.slug ?? null,
    });
    setCarStep(3);
    setCarSearch("");
  };
  const selectFuel = (fuel: string) => {
    // Phase 2.3.3 — capture fuel_id and fuel_slug from the API row so
    // bookingCtx.car carries the data /services/{slug} needs to resolve
    // vehicle-specific prices. Fallback: case-insensitive match against
    // the static FUEL_TYPES list when the API is slow / unavailable.
    const row = apiFuelRows.find(
      (f) => (f.title || f.name)?.toLowerCase() === fuel.toLowerCase()
    );
    setBookingCar({
      brand: pendingCar.brand,
      model: pendingCar.model,
      fuel,
      ...(pendingCar.brandId  != null ? { brand_id:  pendingCar.brandId  } : {}),
      ...(pendingCar.modelId  != null ? { model_id:  pendingCar.modelId  } : {}),
      ...(row?.id             != null ? { fuel_id:   row.id              } : {}),
      ...(pendingCar.brandSlug         ? { brand_slug: pendingCar.brandSlug } : {}),
      ...(pendingCar.modelSlug         ? { model_slug: pendingCar.modelSlug } : {}),
      ...(row?.slug                    ? { fuel_slug: row.slug             } : {}),
    });
    closeCarSelector();
  };
  const carBack = () => {
    if (carStep === 1) closeCarSelector();
    else setCarStep((s) => (s - 1) as 1 | 2 | 3);
    setCarSearch("");
  };

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

  // ---------- Filtered lists for car selector ----------
  const filteredBrands = brandList.filter((b) =>
    b.toLowerCase().includes(carSearch.toLowerCase())
  );
  const filteredModels = apiModelRows
    .map((m) => m.title)
    .filter((m) => m.toLowerCase().includes(carSearch.toLowerCase()));
  const filteredFuels = FUEL_TYPES.filter((f) =>
    f.name.toLowerCase().includes(carSearch.toLowerCase())
  );

  // ---------- Booking-card field styles ----------
  const bcInput =
    "w-full bg-white border border-border p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900 font-bold uppercase tracking-tighter";
  const bcInputErr =
    "w-full bg-white border border-accent-dark p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900 font-bold uppercase tracking-tighter";

  return (
    <>
      {/* Banner — title only, NO location appended (avoids wrapping/cropping) */}
      <PageBanner
        title={category.title}
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Services", onClick: () => setCurrentPage("services") },
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
            className="flex gap-1 sm:gap-2 overflow-x-auto"
            style={{ scrollbarWidth: "none" }}
          >
            {SECTION_NAV.map((s) => (
              <button
                key={s.id}
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
            {count > 0 && (
              <button
                onClick={() => setCurrentPage("cart")}
                className="ml-auto flex items-center gap-2 text-[10px] sm:text-xs uppercase tracking-widest font-bold py-4 px-3 sm:px-5 text-primary whitespace-nowrap shrink-0"
              >
                <ShoppingCart className="w-4 h-4" /> Cart ({count})
              </button>
            )}
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
                className="bg-neutral-50 p-6 sm:p-8 border border-border scroll-mt-40"
              >
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
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
              <section id="pricing" className="scroll-mt-40">
                <div className="flex items-baseline justify-between flex-wrap gap-2 mb-2">
                  <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900">
                    {category.title}{" "}
                    <span className="text-primary">PRICE LIST.</span>
                  </h2>
                  <p className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-neutral-400">
                    {selectedLocationName} · {new Date().getFullYear()}
                  </p>
                </div>

                {pricesShown && bookingCar && (
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

                {/* CTA banner shown when user hasn't completed Check Price yet */}
                {!pricesShown && (
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
                        // Scroll to the booking sidebar at the top of the page
                        window.scrollTo({ top: 0, behavior: "smooth" });
                      }}
                      className="btn-ink btn-ink-primary px-5 py-2.5 text-[10px] sm:text-xs font-black uppercase tracking-widest flex items-center gap-2 shrink-0"
                    >
                      Check Price For Free{" "}
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
                    // Show prices ONLY when user passed OTP AND API marks
                    // the category as priced for the chosen vehicle.
                    const showPrice = pricesShown && priceShowFromApi;
                    // Phase 2.3.3 — toggle add/remove on the same button.
                    // First click: addItem. Second click on the same row:
                    // remove the server cart line. The 1.8 s `justAdded`
                    // flash continues to bridge the visual gap between
                    // the click and the React Query refetch.
                    const cartItem = findCartItem({
                      ref_id:   sub.id,
                      brand_id: bookingCar?.brand_id,
                      model_id: bookingCar?.model_id,
                      fuel_id:  bookingCar?.fuel_id,
                    });
                    const inCart = !!cartItem;
                    return (
                      <div
                        key={sub.id}
                        className="px-4 sm:px-5 py-4 grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 sm:gap-4 sm:items-center"
                      >
                        <div className="min-w-0">
                          <button
                            onClick={() =>
                              setCurrentPage(
                                `service-${category.slug}/${sub.slug}`
                              )
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
                            <div className="flex items-center sm:justify-end gap-1.5">
                              <Lock className="w-3 h-3 text-neutral-400" />
                              <span className="text-[10px] uppercase tracking-widest font-bold text-neutral-400">
                                Hidden
                              </span>
                            </div>
                          )}
                        </div>

                        {/* Action column — Add to Cart only after Check Price; else CTA */}
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
                                window.scrollTo({ top: 0, behavior: "smooth" })
                              }
                              className="px-4 py-2 text-[10px] font-bold uppercase tracking-widest border border-primary text-primary hover:bg-primary hover:text-white transition-colors w-full sm:w-auto flex items-center justify-center gap-1.5"
                            >
                              Check Price{" "}
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

                {count > 0 && pricesShown && (
                  <motion.div
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="mt-5 bg-neutral-50 border border-border p-4 flex items-center justify-between gap-4"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <ShoppingCart className="w-5 h-5 text-primary shrink-0" />
                      <p className="text-sm font-bold text-neutral-900 tracking-tighter truncate">
                        {count} {count === 1 ? "service" : "services"} in your
                        cart
                      </p>
                    </div>
                    <button
                      onClick={() => setCurrentPage("cart")}
                      className="bg-primary text-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest hover:bg-primary-dark transition-colors flex items-center gap-2 shrink-0"
                    >
                      View Cart <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  </motion.div>
                )}
              </section>

              {/* SERVICES INCLUDED */}
              <section id="services" className="scroll-mt-40">
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
              <section id="process" className="scroll-mt-40">
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
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
              <section id="reviews" className="scroll-mt-40">
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-5">
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
              <section id="faqs" className="scroll-mt-40">
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

              {/* BRANDS WE SERVICE */}
              <section>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-1.5">
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
              <section className="bg-neutral-50 p-6 sm:p-8 border border-border">
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
                </div>
              </section>
            </main>

            {/* ──────────── STICKY BOOKING CARD (RIGHT) ──────────── */}
            <aside
              className="order-1 lg:order-2 lg:sticky lg:self-start space-y-5"
              style={{ top: `${STICKY_OFFSET_PX + 60}px` }}
            >
              <div className="bg-white p-5 sm:p-6 border border-border shadow-xl">
                <h2 className="text-xl sm:text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-1 leading-tight">
                  Experience The Best{" "}
                  <span className="text-primary italic">{category.title}</span>{" "}
                  in {selectedLocationName}
                </h2>
                <p className="text-xs text-neutral-500 mb-4">
                  Get instant quotes for your car service.
                </p>

                {/* STEP 1 — Location */}
                <div className="mb-3">
                  <div className="relative">
                    <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" />
                    <select
                      value={bookingLocation}
                      onChange={(e) => {
                        setBookingLocation(e.target.value);
                        if (bookingErrors.location)
                          setBookingErrors((er) => ({ ...er, location: "" }));
                      }}
                      className={`${
                        bookingErrors.location ? bcInputErr : bcInput
                      } pl-9 appearance-none cursor-pointer`}
                    >
                      {LOCATIONS.map((loc) => (
                        <option key={loc.id} value={loc.id}>
                          {loc.name}
                        </option>
                      ))}
                    </select>
                    <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" />
                  </div>
                  {bookingErrors.location && (
                    <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                      <AlertCircle className="w-3 h-3" />{" "}
                      {bookingErrors.location}
                    </p>
                  )}
                </div>

                {/* STEP 2 — Select Your Car */}
                <div className="mb-3">
                  <button
                    type="button"
                    onClick={openCarSelector}
                    className={`${
                      bookingErrors.car ? bcInputErr : bcInput
                    } text-left flex items-center justify-between gap-2`}
                  >
                    <span
                      className={
                        bookingCar
                          ? "text-neutral-900 truncate"
                          : "text-neutral-400 truncate"
                      }
                    >
                      {selectedCarLabel}
                    </span>
                    <ChevronDown className="w-4 h-4 text-neutral-400 shrink-0" />
                  </button>
                  {bookingErrors.car && (
                    <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                      <AlertCircle className="w-3 h-3" /> {bookingErrors.car}
                    </p>
                  )}
                </div>

                {/* STEP 3 — Phone (or logged-in user identity) */}
                {isAuthenticated && user ? (
                  <div className="mb-3 bg-primary/5 border border-primary/20 px-3 py-2.5 flex items-center gap-2">
                    <User className="w-4 h-4 text-primary shrink-0" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs font-black uppercase text-neutral-900 tracking-tighter truncate">
                        {user.name}
                      </p>
                      <p className="text-[10px] text-neutral-500 truncate">
                        +91 {user.phone} · Verified
                      </p>
                    </div>
                  </div>
                ) : (
                  <div className="mb-3">
                    <input
                      type="tel"
                      inputMode="numeric"
                      maxLength={10}
                      value={bookingPhone}
                      onChange={(e) => onPhoneChange(e.target.value)}
                      placeholder="ENTER MOBILE NUMBER"
                      className={
                        bookingErrors.phone ? bcInputErr : bcInput
                      }
                    />
                    {bookingErrors.phone && (
                      <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                        <AlertCircle className="w-3 h-3" />{" "}
                        {bookingErrors.phone}
                      </p>
                    )}
                  </div>
                )}

                {/* STEP 3b — OTP */}
                {otpSent && !otpVerified && (
                  <motion.div
                    initial={{ opacity: 0, height: 0 }}
                    animate={{ opacity: 1, height: "auto" }}
                    className="mb-3 overflow-hidden"
                  >
                    <input
                      type="text"
                      inputMode="numeric"
                      maxLength={6}
                      value={otpValue}
                      onChange={(e) => {
                        const v = e.target.value
                          .replace(/\D/g, "")
                          .slice(0, 6);
                        setOtpValue(v);
                        if (bookingErrors.otp)
                          setBookingErrors((er) => ({ ...er, otp: "" }));
                      }}
                      placeholder="ENTER OTP"
                      className={`${
                        bookingErrors.otp ? bcInputErr : bcInput
                      } text-center tracking-[0.5em]`}
                    />
                    <p className="text-[10px] text-neutral-400 mt-1">
                      OTP sent to +91 {bookingPhone}.{" "}
                      <button
                        onClick={() => {
                          setOtpSent(false);
                          setOtpValue("");
                        }}
                        className="text-primary font-bold uppercase tracking-widest hover:underline"
                      >
                        Change
                      </button>
                    </p>
                    {bookingErrors.otp && (
                      <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                        <AlertCircle className="w-3 h-3" />{" "}
                        {bookingErrors.otp}
                      </p>
                    )}
                  </motion.div>
                )}

                {/* STEP 4 — CTA */}
                {!otpSent && (
                  <button
                    onClick={sendOtp}
                    className="w-full bg-primary text-white py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-primary-dark transition-colors"
                  >
                    Send OTP <ArrowRight className="w-4 h-4" />
                  </button>
                )}
                {otpSent && !otpVerified && (
                  <button
                    onClick={verifyOtp}
                    className="w-full bg-primary text-white py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-primary-dark transition-colors"
                  >
                    Verify OTP <CheckCircle2 className="w-4 h-4" />
                  </button>
                )}
                {otpVerified && (
                  <motion.button
                    initial={{ scale: 0.96 }}
                    animate={{ scale: 1 }}
                    onClick={checkPrices}
                    className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2"
                  >
                    {pricesShown
                      ? "Re-check Prices For Free"
                      : "Check Prices For Free"}{" "}
                    <ArrowRight className="w-4 h-4 btn-arrow" />
                  </motion.button>
                )}

                {/* Trust strip */}
                <div className="grid grid-cols-2 gap-3 pt-4 mt-5 border-t border-border">
                  <div className="text-center border-r border-border pr-3">
                    <div className="flex items-center justify-center gap-1 mb-1 text-primary">
                      <Star className="w-4 h-4 fill-current" />
                      <span className="text-base font-black text-neutral-900">
                        4.8
                        <span className="text-xs text-neutral-400">/5</span>
                      </span>
                    </div>
                    <p className="text-[9px] text-neutral-400 uppercase tracking-widest font-bold leading-tight">
                      Based on 2,500+ Reviews
                    </p>
                  </div>
                  <div className="text-center">
                    <p className="text-base font-black text-neutral-900 mb-1">
                      10K<span className="text-primary">+</span>
                    </p>
                    <p className="text-[9px] text-neutral-400 uppercase tracking-widest font-bold leading-tight">
                      Happy Customers
                    </p>
                  </div>
                </div>
              </div>

              {/* CART SUMMARY card (only when items exist) */}
              {count > 0 && (
                <motion.button
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
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
                </motion.button>
              )}

              {/* TRUST BADGES */}
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

      {/* ──────────── CAR SELECTOR MODAL ──────────── */}
      <AnimatePresence>
        {showCarSelector && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-5">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={closeCarSelector}
              className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
            />

            <motion.div
              initial={{ opacity: 0, y: 30, scale: 0.96 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 30, scale: 0.96 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
              className="relative w-full max-w-xl bg-white border border-border shadow-2xl flex flex-col h-[560px] max-h-[88vh]"
            >
              <div className="px-5 sm:px-6 py-4 border-b border-border flex items-center gap-3 shrink-0">
                <button
                  onClick={carBack}
                  aria-label="Back"
                  className="w-8 h-8 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                >
                  <ArrowLeft className="w-5 h-5" />
                </button>
                <h3 className="text-base sm:text-lg font-black uppercase tracking-tighter text-neutral-900 flex-1">
                  {carStep === 1 && "Select Manufacturer"}
                  {carStep === 2 && "Select Model"}
                  {carStep === 3 && "Select Fuel Type"}
                </h3>
                <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400 shrink-0">
                  Step {carStep} of 3
                </span>
                <button
                  onClick={closeCarSelector}
                  aria-label="Close"
                  className="w-8 h-8 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="px-5 sm:px-6 pt-4 pb-3 shrink-0">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                  <input
                    type="text"
                    value={carSearch}
                    onChange={(e) => setCarSearch(e.target.value)}
                    placeholder={
                      carStep === 1
                        ? "Search Brands"
                        : carStep === 2
                        ? "Search Models"
                        : "Search Fuel Type"
                    }
                    className="w-full bg-neutral-50 border border-border pl-9 pr-3 py-2.5 text-sm focus:border-primary outline-none"
                  />
                </div>
              </div>

              <div className="flex-1 overflow-y-auto px-5 sm:px-6 pb-5">
                {carStep === 1 && (
                  brandsQuery.isLoading ? (
                    <div className="grid grid-cols-3 gap-3">
                      {Array.from({ length: 9 }).map((_, i) => (
                        <div
                          key={`mb-sk-${i}`}
                          className="bg-neutral-100 border border-border aspect-square animate-pulse"
                        />
                      ))}
                    </div>
                  ) : brandsQuery.isError ? (
                    <div className="border border-accent-dark/40 bg-accent-dark/5 p-6 text-center">
                      <AlertCircle className="w-5 h-5 text-accent-dark mx-auto mb-2" />
                      <p className="text-xs font-bold uppercase tracking-widest text-accent-dark mb-3">
                        Couldn't load brands
                      </p>
                      <button
                        onClick={() => brandsQuery.refetch()}
                        className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline inline-flex items-center gap-1"
                      >
                        <RefreshCw className="w-3 h-3" /> Retry
                      </button>
                    </div>
                  ) : apiBrandRows.length === 0 ? (
                    <div className="text-center py-8 text-xs font-bold uppercase tracking-widest text-neutral-500">
                      No brands available — please contact support.
                    </div>
                  ) : (
                    <div className="grid grid-cols-3 gap-3">
                      {filteredBrands.map((brand) => {
                        const row = apiBrandRows.find((b) => b.title === brand);
                        return (
                          <button
                            key={brand}
                            onClick={() => selectBrand(brand, row?.id ?? null)}
                            className="bg-white border border-border p-3 sm:p-4 flex flex-col items-center justify-center text-center hover:border-primary hover:bg-primary/5 transition-colors aspect-square"
                          >
                            <div className="w-10 h-10 sm:w-12 sm:h-12 bg-primary/10 text-primary flex items-center justify-center font-black text-lg sm:text-xl uppercase tracking-tighter mb-2">
                              {brand.charAt(0)}
                            </div>
                            <span className="text-[10px] sm:text-xs font-bold uppercase tracking-tighter text-neutral-900 leading-tight">
                              {brand}
                            </span>
                          </button>
                        );
                      })}
                      <button
                        onClick={() => selectBrand("Other", null)}
                        className="bg-white border border-dashed border-border p-3 sm:p-4 flex flex-col items-center justify-center text-center hover:border-primary hover:bg-primary/5 transition-colors aspect-square"
                      >
                        <div className="w-10 h-10 sm:w-12 sm:h-12 bg-neutral-100 text-neutral-500 flex items-center justify-center font-black text-lg sm:text-xl uppercase tracking-tighter mb-2">
                          ?
                        </div>
                        <span className="text-[10px] sm:text-xs font-bold uppercase tracking-tighter text-neutral-900 leading-tight">
                          Other
                        </span>
                      </button>
                      {filteredBrands.length === 0 && carSearch && (
                        <div className="col-span-3 text-center py-8 text-sm text-neutral-400">
                          No brands match "{carSearch}". Tap{" "}
                          <button
                            onClick={() => selectBrand("Other", null)}
                            className="text-primary font-bold underline"
                          >
                            Other
                          </button>{" "}
                          to continue.
                        </div>
                      )}
                    </div>
                  )
                )}

                {carStep === 2 && pendingCar.brand !== "Other" && (
                  modelsQuery.isLoading ? (
                    <div className="grid grid-cols-3 gap-3">
                      {Array.from({ length: 6 }).map((_, i) => (
                        <div
                          key={`mm-sk-${i}`}
                          className="bg-neutral-100 border border-border min-h-[110px] animate-pulse"
                        />
                      ))}
                    </div>
                  ) : modelsQuery.isError ? (
                    <div className="border border-accent-dark/40 bg-accent-dark/5 p-6 text-center">
                      <AlertCircle className="w-5 h-5 text-accent-dark mx-auto mb-2" />
                      <p className="text-xs font-bold uppercase tracking-widest text-accent-dark mb-3">
                        Couldn't load models
                      </p>
                      <button
                        onClick={() => modelsQuery.refetch()}
                        className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline inline-flex items-center gap-1"
                      >
                        <RefreshCw className="w-3 h-3" /> Retry
                      </button>
                    </div>
                  ) : apiModelRows.length === 0 ? (
                    <div className="text-center py-8 text-xs font-bold uppercase tracking-widest text-neutral-500">
                      No models available for {pendingCar.brand} — please contact support.
                    </div>
                  ) : (
                    <div className="grid grid-cols-3 gap-3">
                      {filteredModels.map((model) => (
                        <button
                          key={model}
                          onClick={() => selectModel(model)}
                          className="bg-white border border-border p-3 flex flex-col items-center justify-center text-center hover:border-primary hover:bg-primary/5 transition-colors min-h-[110px]"
                        >
                          <div className="w-12 h-8 bg-neutral-100 mb-2 flex items-center justify-center text-[8px] font-bold uppercase text-neutral-400 tracking-widest">
                            CAR
                          </div>
                          <span className="text-[10px] sm:text-xs font-bold uppercase tracking-tighter text-neutral-900 leading-tight">
                            {model}
                          </span>
                        </button>
                      ))}
                      <button
                        onClick={() => selectModel("Other")}
                        className="bg-white border border-dashed border-border p-3 flex flex-col items-center justify-center text-center hover:border-primary hover:bg-primary/5 transition-colors min-h-[110px]"
                      >
                        <div className="w-12 h-8 bg-neutral-100 mb-2 flex items-center justify-center text-base font-black text-neutral-500">
                          ?
                        </div>
                        <span className="text-[10px] sm:text-xs font-bold uppercase tracking-tighter text-neutral-900 leading-tight">
                          Other
                        </span>
                      </button>
                    </div>
                  )
                )}

                {carStep === 2 && pendingCar.brand === "Other" && (
                  <div className="space-y-3">
                    <p className="text-sm text-neutral-500">
                      Enter your car model below.
                    </p>
                    <input
                      type="text"
                      value={carSearch}
                      onChange={(e) => setCarSearch(e.target.value)}
                      placeholder="e.g. Renault Kwid"
                      className="w-full bg-neutral-50 border border-border p-3 text-sm focus:border-primary outline-none"
                    />
                    <button
                      onClick={() =>
                        selectModel(carSearch.trim() || "Custom")
                      }
                      disabled={!carSearch.trim()}
                      className="w-full bg-primary text-white py-3 text-xs font-black uppercase tracking-widest disabled:opacity-50 hover:bg-primary-dark transition-colors"
                    >
                      Continue
                    </button>
                  </div>
                )}

                {carStep === 3 && (
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {filteredFuels.map((f) => {
                      const Icon = f.icon;
                      return (
                        <button
                          key={f.id}
                          onClick={() => selectFuel(f.name)}
                          className="bg-white border border-border p-4 flex flex-col items-center justify-center text-center hover:border-primary hover:bg-primary/5 transition-colors min-h-[120px]"
                        >
                          <div className="bg-primary/10 p-3 mb-2">
                            <Icon className="w-6 h-6 text-primary" />
                          </div>
                          <span className="text-xs font-bold uppercase tracking-tighter text-neutral-900">
                            {f.name}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>

              <div className="bg-neutral-50 border-t border-border px-5 sm:px-6 py-3 shrink-0">
                <p className="text-[10px] uppercase tracking-widest font-bold text-neutral-400 truncate">
                  {pendingCar.brand &&
                    `Selected: ${pendingCar.brand}${
                      pendingCar.model ? ` · ${pendingCar.model}` : ""
                    }`}
                  {!pendingCar.brand && "Pick your manufacturer to continue"}
                </p>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

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
