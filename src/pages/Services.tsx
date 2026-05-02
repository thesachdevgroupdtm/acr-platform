import { useEffect, useMemo, useState } from "react";
import type * as React from "react";
import { motion } from "motion/react";
import {
  ArrowRight,
  CheckCircle2,
  ShoppingCart,
  Lock,
  Calculator,
  Star,
  Shield,
  Clock,
  Sparkles,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import BookingSidebar from "../components/BookingSidebar";
import { useCart } from "../hooks/useCart";
import { useBookingContext } from "../hooks/useBookingContext";
import {
  fetchServices,
  type ServiceCategory as ApiServiceCategory,
  type CategorySubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";

interface ServicesProps {
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

// Header (~30px top blue + 80px main bar) + section nav (~52px) ≈ 132px
const STICKY_OFFSET_PX = 132;
const SECTION_NAV_OFFSET_PX = 112; // height of header alone

export default function Services({ setCurrentPage }: ServicesProps) {
  const { addItem, count, isInCart } = useCart();
  const { state: booking } = useBookingContext();

  // ---------- API: categories list (skeleton-first, never static) ----------
  // /services takes ids (brand_id/model_id/fuel_id) per backend contract.
  const carContext = useMemo(
    () => ({
      brand_id: booking.car?.brand_id ?? null,
      model_id: booking.car?.model_id ?? null,
      fuel_id: booking.car?.fuel_id ?? null,
    }),
    [booking.car]
  );
  const servicesQuery = useApiQuery(
    ["services", carContext],
    (signal) => fetchServices(carContext, signal)
  );
  const apiCategories: ApiServiceCategory[] =
    servicesQuery.data?.categories ?? [];
  const isLoadingCategories = servicesQuery.isLoading;

  // Active section for the sticky horizontal nav
  const [activeSection, setActiveSection] = useState<string>("");
  const [addedFlash, setAddedFlash] = useState<string | null>(null);

  useEffect(() => {
    if (!activeSection && apiCategories.length > 0) {
      setActiveSection(apiCategories[0].slug);
    }
  }, [apiCategories, activeSection]);

  // ---------- Section scroll-spy (binds once categories arrive) ----------
  useEffect(() => {
    if (apiCategories.length === 0) return;
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
    apiCategories.forEach((c) => {
      const el = document.getElementById(c.slug);
      if (el) observer.observe(el);
    });
    return () => observer.disconnect();
  }, [apiCategories]);

  // ---------- Helpers ----------
  const scrollToSection = (slug: string) => {
    const el = document.getElementById(slug);
    if (!el) return;
    const top =
      el.getBoundingClientRect().top + window.scrollY - (SECTION_NAV_OFFSET_PX + 60);
    window.scrollTo({ top, behavior: "smooth" });
    setActiveSection(slug);
  };

  // Set of category IDs that have at least one priced service for the
  // current vehicle. Comes from /services' `available_category_ids`,
  // which the backend computes when brand/model/fuel are supplied.
  // Replaces the previous per-card price_show derivation. Empty set
  // when no vehicle is selected — matches old behaviour.
  const availableCategoryIds = useMemo(
    () => new Set(servicesQuery.data?.available_category_ids ?? []),
    [servicesQuery.data?.available_category_ids]
  );

  const handleAddToCart = (sub: CategorySubService, categorySlug: string) => {
    addItem({
      serviceId: String(sub.id),
      title: sub.title,
      price: Number(sub.base_price) || 0,
      categorySlug,
      car: booking.car || undefined,
      location: booking.location || undefined,
      brand_id: booking.car?.brand_id,
      model_id: booking.car?.model_id,
      fuel_id:  booking.car?.fuel_id,
    });
    setAddedFlash(String(sub.id));
    setTimeout(() => setAddedFlash(null), 1800);
  };

  return (
    <>
      <PageBanner
        title="Our Services"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "All Services" },
        ]}
      />

      {/* ─────────── STICKY HORIZONTAL CATEGORY NAV ─────────── */}
      <nav
        className="sticky z-30 bg-white border-b border-border"
        style={{ top: `${SECTION_NAV_OFFSET_PX}px` }}
      >
        <div className="site-container">
          <div
            className="flex gap-1 sm:gap-2 overflow-x-auto"
            style={{ scrollbarWidth: "none" }}
          >
            {isLoadingCategories
              ? Array.from({ length: 6 }).map((_, i) => (
                  <div
                    key={`navsk-${i}`}
                    className="my-4 h-3 w-28 bg-neutral-200 animate-pulse rounded shrink-0"
                  />
                ))
              : apiCategories.map((c) => (
                  <button
                    key={c.id}
                    onClick={() => scrollToSection(c.slug)}
                    className={`text-[10px] sm:text-xs uppercase tracking-widest font-bold py-4 px-3 sm:px-5 whitespace-nowrap border-b-2 transition-colors shrink-0 ${
                      activeSection === c.slug
                        ? "border-primary text-primary"
                        : "border-transparent text-neutral-500 hover:text-primary"
                    }`}
                  >
                    {c.title}
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

      {/* ─────────── MAIN GRID: content + booking sidebar ─────────── */}
      <div className="pb-14 pt-8">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
            {/* ───── MAIN CONTENT ───── */}
            <main className="lg:col-span-2 order-2 lg:order-1 space-y-12">
              {/* Intro card */}
              <section className="bg-neutral-50 p-6 sm:p-8 border border-border">
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 mb-4 tracking-tighter">
                  CAR SERVICES{" "}
                  <span className="text-primary">AVAILABLE.</span>
                </h2>
                <p className="text-sm sm:text-base text-neutral-600 leading-relaxed">
                  ACR is your one-stop destination for everything your car
                  needs — from regular service to collision repair, AC service,
                  battery, denting & painting, and detailing. Pick your car &
                  location on the right to see exact prices, then add the
                  services you need to your cart and check out in one go.
                </p>
                {!booking.pricesShown && (
                  <div className="mt-5 bg-white border border-dashed border-primary/40 px-4 py-4 flex items-center justify-between gap-4 flex-wrap">
                    <div className="flex items-center gap-3 min-w-0">
                      <Calculator className="w-5 h-5 text-primary shrink-0" />
                      <p className="text-xs sm:text-sm font-bold text-neutral-700 tracking-tighter">
                        Select your car & location to unlock prices.
                      </p>
                    </div>
                    <button
                      onClick={() =>
                        window.scrollTo({ top: 0, behavior: "smooth" })
                      }
                      className="text-[10px] sm:text-xs font-black uppercase tracking-widest text-primary hover:underline flex items-center gap-1 shrink-0"
                    >
                      Check Price For Free{" "}
                      <ArrowRight className="w-3 h-3" />
                    </button>
                  </div>
                )}
                {booking.pricesShown && booking.car && (
                  <div className="mt-5 bg-primary text-white px-4 py-3 flex items-center gap-3">
                    <CheckCircle2 className="w-5 h-5 shrink-0" />
                    <p className="text-xs sm:text-sm font-bold tracking-tighter">
                      Showing prices for{" "}
                      <span className="uppercase">
                        {booking.car.brand} {booking.car.model} ·{" "}
                        {booking.car.fuel}
                      </span>
                    </p>
                  </div>
                )}
              </section>

              {/* Render each category as its own section — API-driven */}
              {isLoadingCategories &&
                Array.from({ length: 4 }).map((_, i) => (
                  <section
                    key={`cat-sk-${i}`}
                    className="bg-white border border-border p-6 animate-pulse"
                  >
                    <div className="h-7 w-48 bg-neutral-200 mb-4" />
                    <div className="h-4 w-72 bg-neutral-100 mb-6" />
                    <div className="space-y-3">
                      {Array.from({ length: 3 }).map((_, j) => (
                        <div key={j} className="h-12 bg-neutral-100" />
                      ))}
                    </div>
                  </section>
                ))}
              {!isLoadingCategories && servicesQuery.error && (
                <div className="bg-neutral-50 border border-accent-dark/40 p-6 text-sm text-accent-dark">
                  Could not load services: {servicesQuery.error}
                </div>
              )}
              {!isLoadingCategories &&
                apiCategories.map((category) => (
                  <CategorySection
                    key={category.id}
                    category={category}
                    pricesShown={booking.pricesShown}
                    pricesAvailableForCategory={availableCategoryIds.has(category.id)}
                    addedFlash={addedFlash}
                    isInCartFor={(subId) =>
                      isInCart({
                        ref_id:   subId,
                        brand_id: booking.car?.brand_id,
                        model_id: booking.car?.model_id,
                        fuel_id:  booking.car?.fuel_id,
                      })
                    }
                    onAddToCart={(sub) => handleAddToCart(sub, category.slug)}
                    onViewDetail={(subSlug) =>
                      setCurrentPage(`service-${category.slug}/${subSlug}`)
                    }
                    onViewCategory={() =>
                      setCurrentPage(`category-${category.slug}`)
                    }
                    onViewCart={() => setCurrentPage("cart")}
                  />
                ))}

              {/* Floating cart summary at the bottom */}
              {count > 0 && (
                <motion.div
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-neutral-50 border border-border p-4 flex items-center justify-between gap-4"
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

              {/* Trust strip */}
              <section className="bg-white border border-border p-6 sm:p-8 grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 text-center">
                <TrustItem icon={Shield} label="Certified Centres" />
                <TrustItem icon={Sparkles} label="Genuine OEM Parts" />
                <TrustItem icon={Clock} label="Fast Turnaround" />
                <TrustItem icon={Star} label="4.8★ Avg Rating" />
              </section>
            </main>

            {/* ───── BOOKING SIDEBAR ───── */}
            <aside className="order-1 lg:order-2">
              <BookingSidebar
                titleStart="EXPERIENCE THE BEST"
                titleAccent="CAR SERVICES"
                titleEnd="IN"
                stickyTopPx={STICKY_OFFSET_PX}
              />
            </aside>
          </div>
        </div>
      </div>
    </>
  );
}

// ─────────────────── Category Section ───────────────────

interface CategorySectionProps {
  category: ApiServiceCategory;          // sub-services arrive nested via Phase 1.6
  pricesShown: boolean;                  // user has unlocked prices via OTP
  pricesAvailableForCategory: boolean;   // backend says this category has prices for the vehicle
  addedFlash: string | null;
  /** Phase 2.3.2 — true when this sub is already in the server cart for the
   *  current vehicle selection. Drives the Add-to-Cart → View Cart toggle. */
  isInCartFor: (subId: number) => boolean;
  onAddToCart: (sub: CategorySubService) => void;
  onViewDetail: (subSlug: string) => void;
  onViewCategory: () => void;
  onViewCart: () => void;
}

const CategorySection: React.FC<CategorySectionProps> = ({
  category,
  pricesShown,
  pricesAvailableForCategory,
  addedFlash,
  isInCartFor,
  onAddToCart,
  onViewDetail,
  onViewCategory,
  onViewCart,
}) => {
  const subs: CategorySubService[] = category.services ?? [];

  if (subs.length === 0) return null;

  return (
    <section
      id={category.slug}
      data-section="pricing"
      className="scroll-mt-40"
    >
      <div className="flex items-baseline justify-between flex-wrap gap-2 mb-1">
        <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter">
          {category.title.split(" ")[0]}{" "}
          <span className="text-primary">
            {category.title.split(" ").slice(1).join(" ") || "."}
          </span>
        </h2>
        <button
          onClick={onViewCategory}
          className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-1"
        >
          View Details <ArrowRight className="w-3 h-3" />
        </button>
      </div>
      <p className="text-xs sm:text-sm text-neutral-500 mb-4 leading-relaxed">
        {category.description}
      </p>

      <div className="bg-white border border-border divide-y divide-border">
        <div className="hidden sm:grid grid-cols-[1fr_auto_auto] gap-4 px-5 py-3 bg-neutral-50 text-[10px] font-bold uppercase tracking-widest text-neutral-400">
          <span>Service Type</span>
          <span className="text-right w-28">Price From</span>
          <span className="text-right w-32">Action</span>
        </div>

        {subs.map((sub) => {
          const justAdded = addedFlash === String(sub.id);
          // Pricing is API-only. Show price strictly when the user has
          // confirmed via OTP AND the backend marks this category as
          // priced for the chosen vehicle (available_category_ids).
          const showPrice = pricesShown && pricesAvailableForCategory;
          const inCart = isInCartFor(sub.id);
          return (
            <div
              key={sub.id}
              className="px-4 sm:px-5 py-4 grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 sm:gap-4 sm:items-center"
            >
              <div className="min-w-0">
                <button
                  onClick={() => onViewDetail(sub.slug)}
                  className="text-left text-sm font-black uppercase text-neutral-900 tracking-tighter mb-0.5 hover:text-primary transition-colors"
                >
                  {sub.title}
                </button>
              </div>

              {/* Price column — only renders prices the API actually returned */}
              <div className="sm:text-right sm:w-28">
                {showPrice ? (
                  <>
                    <p className="text-base font-black text-neutral-900">
                      {sub.base_price ? `₹${sub.base_price}` : "Quote"}
                    </p>
                    <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                      {sub.base_price ? "Onwards" : "On Inspection"}
                    </span>
                  </>
                ) : (
                  <div className="flex items-center sm:justify-end gap-1.5">
                    <Lock className="w-3 h-3 text-neutral-400" />
                    <span className="text-[10px] uppercase tracking-widest font-bold text-neutral-400">
                      Hidden
                    </span>
                  </div>
                )}
              </div>

              {/* Action column */}
              <div className="sm:w-32 sm:text-right">
                {showPrice ? (
                  <button
                    onClick={() => (inCart ? onViewCart() : onAddToCart(sub))}
                    className={`px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-colors w-full sm:w-auto flex items-center justify-center gap-1.5 ${
                      inCart || justAdded
                        ? "bg-primary-dark text-white"
                        : "bg-primary text-white hover:bg-primary-dark"
                    }`}
                  >
                    {inCart ? (
                      <>
                        <CheckCircle2 className="w-3.5 h-3.5" /> View Cart
                      </>
                    ) : justAdded ? (
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
                      window.scrollTo({ top: 0, behavior: "smooth" })
                    }
                    className="px-4 py-2 text-[10px] font-bold uppercase tracking-widest border border-primary text-primary hover:bg-primary hover:text-white transition-colors w-full sm:w-auto flex items-center justify-center gap-1.5"
                  >
                    Check Price <ArrowRight className="w-3.5 h-3.5" />
                  </button>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}

function TrustItem({
  icon: Icon,
  label,
}: {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
}) {
  return (
    <div className="flex flex-col items-center gap-2">
      <div className="bg-primary/5 p-3">
        <Icon className="w-5 h-5 text-primary" />
      </div>
      <p className="text-[10px] sm:text-xs font-black uppercase tracking-tighter text-neutral-900 leading-tight">
        {label}
      </p>
    </div>
  );
}
