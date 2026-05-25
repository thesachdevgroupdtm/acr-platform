import { useMemo, useState } from "react";
import type * as React from "react";
import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import {
  ArrowRight,
  CheckCircle2,
  ShoppingCart,
  Car,
  Star,
  Shield,
  Clock,
  Sparkles,
  Phone,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import { CarSidebar } from "../components/car-sidebar";
import VehicleReplaceModal from "../components/VehicleReplaceModal";
import { useCart } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";
import { VehicleConflictError, type VehicleConflictDetails } from "../lib/errors";
import { useBookingContext } from "../hooks/useBookingContext";
import { LOCATIONS, BUSINESS_INFO } from "../data/businessData";
import {
  fetchServices,
  type ServiceCategory as ApiServiceCategory,
  type CategorySubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import { useSubNavSync } from "../hooks/useSubNavSync";

interface ServicesProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

// Phase 2.5.7 — sticky chrome stack:
//   • Header: ~32px top blue bar + 80px main bar = 112px
//   • Sub-nav strip below header: ~52px
//   • Buffer: 16px
// SECTION_NAV_OFFSET_PX (112) positions the sub-nav directly under
// the header. STICKY_OFFSET_PX (180) positions the right-side
// booking sidebar BELOW the sub-nav so it never slips under the
// sticky chrome on scroll-up. The pre-2.5.7 value of 132 was
// computed as header+buffer only; the sub-nav was overlapping the
// top of the sidebar.
const STICKY_OFFSET_PX = 180;
const SECTION_NAV_OFFSET_PX = 112; // height of header alone (sub-nav sits at this offset)

export default function Services(_props: ServicesProps) {
  const navigate = useNavigate();
  const { addItem, count, findCartItem, removeItem, replaceVehicleInCart, isLoading: cartLoading } = useCart();
  const { bootstrapped } = useAuth();
  // Phase 2.6a-fix — `cartReady` gates ADDED-badge derivation on
  // service rows. Without it the badge briefly resolves to "not in
  // cart" on hard refresh (because findCartItem returns nothing
  // while cartQuery is pending) and then flips to ADDED — the
  // 0→ADDED flicker the operator reported.
  const cartReady = bootstrapped && !cartLoading;
  const [vehicleConflict, setVehicleConflict] = useState<VehicleConflictDetails | null>(null);
  const [replacing, setReplacing] = useState(false);
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

  // ---------- Sub-nav sync (Phase 2.5.6 + 2.5.7) ----------
  // IntersectionObserver-driven scroll-spy + auto-scroll the
  // horizontal sub-nav. Sections in the page body are matched by
  // `data-subnav-section` attribute (set in the JSX below).
  // rebindKey toggles when categories arrive from the API so the
  // observer rebinds on the now-rendered sections.
  // See src/hooks/useSubNavSync.ts.
  const {
    activeSlug: activeSection,
    scrollToSection,
    navRef,
  } = useSubNavSync({
    stickyOffsetPx: SECTION_NAV_OFFSET_PX,
    rebindKey: `services:${apiCategories.length}`,
  });

  const [addedFlash, setAddedFlash] = useState<string | null>(null);

  // Set of category IDs that have at least one priced service for the
  // current vehicle. Comes from /services' `available_category_ids`,
  // which the backend computes when brand/model/fuel are supplied.
  // Replaces the previous per-card price_show derivation. Empty set
  // when no vehicle is selected — matches old behaviour.
  const availableCategoryIds = useMemo(
    () => new Set(servicesQuery.data?.available_category_ids ?? []),
    [servicesQuery.data?.available_category_ids]
  );

  // Phase 2.6a — vehicle-specific prices arrive INLINE on each
  // SubServiceResource (`vehicle_price`). The 4-state machine from
  // 2.3.5 still applies — driven by `priceFor` below:
  //   no-vehicle → "Check Price" CTA
  //   loading    → skeleton bar (servicesQuery.isFetching)
  //   price      → ₹{vehicle_price}
  //   no-price   → "Quote on Inspection" (vehicle context sent but
  //                service has no service_prices row)
  // The pre-2.6a parallel POST /pricing call is gone — the bulk
  // resolution happens server-side inside ServiceController@index.
  const vehicleSelected = !!(
    booking.car?.brand_id && booking.car?.model_id && booking.car?.fuel_id
  );
  const priceMap = useMemo(() => {
    const m = new Map<number, number>();
    for (const c of apiCategories) {
      for (const s of c.services ?? []) {
        if (s.vehicle_price != null) {
          const num = Number(s.vehicle_price);
          if (Number.isFinite(num)) m.set(s.id, num);
        }
      }
    }
    return m;
  }, [apiCategories]);
  const pricingLoading = vehicleSelected && servicesQuery.isLoading;

  // Phone number used by the "Call Now" CTA on rows where the service
  // has no pre-defined price (priceState.kind === "no-price" — backend
  // returned no service_prices row for the chosen vehicle, e.g. quote-
  // on-inspection services). Picks the user's selected service centre
  // when available; falls back to the business default otherwise.
  const locationPhone = useMemo(() => {
    const loc = LOCATIONS.find((l) => l.id === booking.location);
    return loc?.phone || BUSINESS_INFO.phone;
  }, [booking.location]);

  const handleAddToCart = async (sub: CategorySubService, categorySlug: string) => {
    try {
      await addItem({
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
    } catch (err) {
      if (err instanceof VehicleConflictError) {
        setVehicleConflict(err.details);
      }
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

  return (
    <>
      <PageBanner
        title="Our Services"
        breadcrumbs={[
          { label: "Home", onClick: () => navigate("/") },
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
            ref={navRef as React.RefObject<HTMLDivElement>}
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
                    data-subnav-link={c.slug}
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
            {/* Phase 2.5.5 — sub-nav is category-anchors only (D-2.5.5-1).
                The previous "CART (N)" link was a redundant cart entry
                point; the global header icon and the contextual
                SmartMiniCart in the right sidebar own that role. */}
          </div>
        </div>
      </nav>

      {/* ─────────── MAIN GRID: content + booking sidebar ─────────── */}
      <div className="pb-14 pt-8">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
            {/* ───── MAIN CONTENT ───── */}
            <main className="lg:col-span-2 order-2 lg:order-1 space-y-12">
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
                    hasVehicle={vehicleSelected}
                    pricesAvailableForCategory={availableCategoryIds.has(category.id)}
                    priceStateFor={(subId) => {
                      if (!vehicleSelected) return { kind: "no-vehicle" };
                      if (pricingLoading) return { kind: "loading" };
                      const v = priceMap.get(subId);
                      return v != null
                        ? { kind: "price", value: v }
                        : { kind: "no-price" };
                    }}
                    addedFlash={addedFlash}
                    cartItemFor={(subId) =>
                      cartReady
                        ? findCartItem({
                            ref_id:   subId,
                            brand_id: booking.car?.brand_id,
                            model_id: booking.car?.model_id,
                            fuel_id:  booking.car?.fuel_id,
                          })
                        : null
                    }
                    onAddToCart={(sub) => handleAddToCart(sub, category.slug)}
                    onRemoveFromCart={(itemId) => removeItem(String(itemId))}
                    onViewDetail={(subSlug) =>
                      navigate(`/services/${category.slug}/${subSlug}`)
                    }
                    onViewCategory={() =>
                      navigate(`/category/${category.slug}`)
                    }
                    locationPhone={locationPhone}
                  />
                ))}

              {/* Phase 2.5.5 — mid-page cart summary strip removed per
                  UX audit (D-2.5.5-2). Same role moved to the
                  contextual SmartMiniCart in the right sidebar. */}

              {/* Trust strip */}
              <section className="bg-white border border-border p-6 sm:p-8 grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 text-center">
                <TrustItem icon={Shield} label="Certified Centres" />
                <TrustItem icon={Sparkles} label="Genuine OEM Parts" />
                <TrustItem icon={Clock} label="Fast Turnaround" />
                <TrustItem icon={Star} label="4.8★ Avg Rating" />
              </section>
            </main>

            {/* ───── CAR SIDEBAR (shared cart form) ─────
                REBUILD-VEHICLE — the ONE CarSidebar every service page
                mounts (identical width/layout). currentService omitted →
                no auto-add; shows existing cart or the "Select your car"
                empty state whose button opens the VehicleSelector in-place
                (no center modal). Prices reveal on hasVehicle. */}
            <CarSidebar
              stickyTopPx={STICKY_OFFSET_PX}
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

// ─────────────────── Category Section ───────────────────

/**
 * Phase 2.3.5 — discriminated union driving the row's price column.
 * Computed once by the parent so the row's render is atomic and
 * doesn't flash through intermediate states.
 */
type PriceState =
  | { kind: "no-vehicle" }
  | { kind: "loading" }
  | { kind: "price"; value: number }
  | { kind: "no-price" };

interface CategorySectionProps {
  category: ApiServiceCategory;          // sub-services arrive nested via Phase 1.6
  hasVehicle: boolean;                   // a complete brand+model+fuel is selected
  pricesAvailableForCategory: boolean;   // backend says this category has prices for the vehicle
  /** Phase 2.3.5 — strict 4-state price status; never base_price. */
  priceStateFor: (subId: number) => PriceState;
  addedFlash: string | null;
  /** Phase 2.3.3 — returns the matching CartItemResource (with its
   *  server `id`) when this sub is already in the cart for the current
   *  vehicle selection, else null. Drives the toggle add/remove behavior. */
  cartItemFor: (subId: number) => { id: number } | null;
  onAddToCart: (sub: CategorySubService) => void;
  onRemoveFromCart: (cartItemId: number) => void;
  onViewDetail: (subSlug: string) => void;
  onViewCategory: () => void;
  /** Phone number to dial when a row's priceState is "no-price"
   *  (Quote-on-Inspection). Resolved by the parent from the booking
   *  context's selected service centre, with a business fallback. */
  locationPhone: string;
}

const CategorySection: React.FC<CategorySectionProps> = ({
  category,
  hasVehicle,
  pricesAvailableForCategory,
  priceStateFor,
  addedFlash,
  cartItemFor,
  onAddToCart,
  onRemoveFromCart,
  onViewDetail,
  onViewCategory,
  locationPhone,
}) => {
  const subs: CategorySubService[] = category.services ?? [];

  if (subs.length === 0) return null;

  return (
    <section
      id={category.slug}
      data-subnav-section={category.slug}
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
          // Pricing is API-only. Reveal prices as soon as a complete
          // vehicle is selected (hasVehicle) AND the backend marks this
          // category as priced for that vehicle (available_category_ids).
          // No OTP gate — phone/OTP is only required at checkout.
          const showPrice = hasVehicle && pricesAvailableForCategory;
          const cartItem = cartItemFor(sub.id);
          const inCart = !!cartItem;
          // Phase 2.3.5 — strict 4-state machine. Never base_price.
          const priceState = priceStateFor(sub.id);
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

              {/* Price column — vehicle-specific only (Phase 2.3.5). */}
              <div className="sm:text-right sm:w-28">
                {showPrice ? (
                  priceState.kind === "loading" ? (
                    <div className="sm:ml-auto h-5 w-16 bg-neutral-200 animate-pulse rounded" />
                  ) : priceState.kind === "price" ? (
                    <>
                      <p className="text-base font-black text-neutral-900">
                        ₹{priceState.value}
                      </p>
                      <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                        Onwards
                      </span>
                    </>
                  ) : (
                    // 'no-price' — vehicle resolved, no priced row matched.
                    <>
                      <p className="text-base font-black text-neutral-900">
                        Quote
                      </p>
                      <span className="text-[9px] uppercase tracking-widest font-bold text-neutral-400">
                        On Inspection
                      </span>
                    </>
                  )
                ) : !hasVehicle ? (
                  // No vehicle yet — invite selection (NOT a paywall lock).
                  <div className="flex items-center sm:justify-end gap-1.5">
                    <Car className="w-3 h-3 text-neutral-400" />
                    <span className="text-[10px] uppercase tracking-widest font-bold text-neutral-400">
                      Select car
                    </span>
                  </div>
                ) : (
                  // Vehicle selected but this category has no priced row.
                  <span className="text-[10px] uppercase tracking-widest font-bold text-neutral-400">
                    On Inspection
                  </span>
                )}
              </div>

              {/* Action column */}
              <div className="sm:w-32 sm:text-right">
                {showPrice && priceState.kind === "no-price" ? (
                  // Quote-on-Inspection — no fixed cart-eligible price.
                  // Replace Add-to-Cart with Call Now so the customer
                  // can ring the chosen service centre for a quote /
                  // booking. `tel:` opens the dialer on mobile and
                  // hands off to the OS handler on desktop.
                  <a
                    href={`tel:${locationPhone}`}
                    className="btn-ink btn-ink-primary px-3 py-2 min-h-[48px] text-[10px] font-bold uppercase tracking-widest w-full justify-center gap-1.5 whitespace-nowrap"
                    aria-label={`Call ${locationPhone} for ${sub.title} quote`}
                  >
                    <Phone className="w-3.5 h-3.5" /> Call Now
                  </a>
                ) : showPrice ? (
                  <button
                    onClick={() =>
                      inCart && cartItem
                        ? onRemoveFromCart(cartItem.id)
                        : onAddToCart(sub)
                    }
                    // Phase 2.3.5 — same btn-ink ink-sweep treatment as
                    // ServiceCategory + ServiceDetail for cross-page
                    // consistency. ADDED hover paints bg-primary with
                    // white text; ADD TO CART hover paints
                    // bg-primary-dark.
                    className={`btn-ink ${
                      inCart || justAdded
                        ? "btn-ink-outline"
                        : "btn-ink-primary"
                    } px-3 py-2 min-h-[48px] text-[10px] font-bold uppercase tracking-widest w-full justify-center gap-1.5 whitespace-nowrap`}
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
                ) : !hasVehicle ? (
                  // No vehicle — CTA scrolls to the booking sidebar whose
                  // "Select your car" opens the shared selector modal.
                  <button
                    onClick={() =>
                      window.scrollTo({ top: 0, behavior: "smooth" })
                    }
                    className="px-3 py-2 min-h-[48px] text-[10px] font-bold uppercase tracking-widest border border-primary text-primary hover:bg-primary hover:text-white transition-colors w-full flex items-center justify-center gap-1.5 whitespace-nowrap"
                  >
                    Select Your Car <ArrowRight className="w-3.5 h-3.5" />
                  </button>
                ) : (
                  // Vehicle selected, no priced row — ring the centre.
                  <a
                    href={`tel:${locationPhone}`}
                    className="btn-ink btn-ink-primary px-3 py-2 min-h-[48px] text-[10px] font-bold uppercase tracking-widest w-full justify-center gap-1.5 whitespace-nowrap"
                    aria-label={`Call ${locationPhone} for ${sub.title} quote`}
                  >
                    <Phone className="w-3.5 h-3.5" /> Call Now
                  </a>
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
