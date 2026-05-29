import type * as React from "react";
import { useMemo, useState } from "react";
import { useNavigate, useOutletContext } from "react-router-dom";
import { motion } from "motion/react";
import {
  ArrowRight,
  Calculator,
  Shield,
  Sparkles,
  Clock,
  Star,
} from "lucide-react";
import VehicleReplaceModal from "../components/VehicleReplaceModal";
import SectionHeading from "../components/layout/SectionHeading";
import ServiceCard from "../components/service/ServiceCard";
import { categoryIcon } from "../components/service/categoryIcon";
import { useCart } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";
import { VehicleConflictError, type VehicleConflictDetails } from "../lib/errors";
import { useBookingContext } from "../hooks/useBookingContext";
import { fetchCategoryDetail, type SubService } from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import type { ServicesShellContext } from "../layouts/ServicesShell";

interface ServicesProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

/**
 * Phase 2c (D-2c-1/3/6) — Layer 1 (/services) is now an ACTIVE-CATEGORY TAB
 * view inside ServicesShell. The shell's sticky cross-category bar selects
 * `activeTab` (client state; URL stays /services → the shell + sidebar never
 * remount → instant tab switch). This page renders ONLY the active category's
 * shared ServiceCard list + a "View full page →" link; the old all-categories
 * vertical dump is gone (the long-page problem).
 *
 * It fetches the active category through the SAME `fetchCategoryDetail` call
 * (and React Query key) that Layer 2 uses → identical data, price 4-state and
 * card, plus a warm cache so "View full page →" is instant.
 */
export default function Services(_props: ServicesProps) {
  const navigate = useNavigate();
  // The shell owns the active tab (single source of truth for the one bar).
  const { activeTab } = useOutletContext<ServicesShellContext>();

  const { state: booking } = useBookingContext();
  const {
    addItem,
    findCartItem,
    removeItem,
    replaceVehicleInCart,
    isLoading: cartLoading,
  } = useCart();
  const { bootstrapped } = useAuth();
  // cartReady gates ADDED-badge derivation (avoids the 0→ADDED flicker).
  const cartReady = bootstrapped && !cartLoading;

  const [addedFlash, setAddedFlash] = useState<string | null>(null);
  const [vehicleConflict, setVehicleConflict] = useState<VehicleConflictDetails | null>(null);
  const [replacing, setReplacing] = useState(false);

  // Same endpoint + key as Layer 2 (/services/{slug} takes brand/model/fuel
  // SLUGS) → shared React Query cache, identical price logic + cards.
  const carSlugs = useMemo(
    () => ({
      brand: booking.car?.brand_slug ?? null,
      model: booking.car?.model_slug ?? null,
      fuel: booking.car?.fuel_slug ?? null,
    }),
    [booking.car]
  );
  const detailQuery = useApiQuery(
    ["category-detail", activeTab, carSlugs],
    (signal) => fetchCategoryDetail(activeTab, carSlugs, signal),
    { enabled: !!activeTab }
  );
  const category = detailQuery.data?.category ?? null;
  const subServices: SubService[] = detailQuery.data?.services ?? [];
  const priceShowFromApi = Boolean(detailQuery.data?.price_show);

  const vehicleSelected = !!(
    booking.car?.brand_id && booking.car?.model_id && booking.car?.fuel_id
  );
  // Phase 2.6a — vehicle prices arrive INLINE on each service. Price 4-state
  // logic UNCHANGED (D-2c-5).
  const priceMap = useMemo(() => {
    const m = new Map<number, number>();
    for (const s of subServices) {
      if (s.vehicle_price != null) {
        const num = Number(s.vehicle_price);
        if (Number.isFinite(num)) m.set(s.id, num);
      }
    }
    return m;
  }, [subServices]);
  const pricingLoading = vehicleSelected && detailQuery.isLoading;

  const handleAddToCart = async (sub: SubService) => {
    if (!category) return;
    try {
      await addItem({
        serviceId: String(sub.id),
        title: sub.title,
        price: Number(sub.price) || 0,
        categorySlug: category.slug,
        car: booking.car || undefined,
        location: booking.location || undefined,
        brand_id: booking.car?.brand_id,
        model_id: booking.car?.model_id,
        fuel_id:  booking.car?.fuel_id,
      });
      setAddedFlash(String(sub.id));
      window.setTimeout(() => setAddedFlash(null), 1800);
    } catch (err) {
      if (err instanceof VehicleConflictError) setVehicleConflict(err.details);
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

  // First visit to a tab → brief skeleton; cached tabs swap instantly.
  const showSkeleton = !activeTab || (detailQuery.isLoading && !category);

  return (
    <>
      {/* Phase 2c — center content = the ACTIVE category's cards only. The
          shell owns the PageBanner, the sticky cross-category bar (which sets
          the active tab here), the grid and the single CarSidebar. */}
      <div className="space-y-10">
        {showSkeleton ? (
          <div className="space-y-4">
            <div className="h-8 w-1/2 bg-neutral-200 animate-pulse" />
            {Array.from({ length: 4 }).map((_, i) => (
              <div
                key={i}
                className="h-32 bg-neutral-100 border border-border animate-pulse"
              />
            ))}
          </div>
        ) : !category ? (
          <div className="bg-neutral-50 border border-border p-6 text-sm text-neutral-600">
            {detailQuery.error
              ? `Could not load services: ${detailQuery.error}`
              : "No services found for this category."}
          </div>
        ) : (
          <section>
            <div className="flex items-baseline justify-between flex-wrap gap-2 mb-2">
              <SectionHeading className="mb-0">{`${category.title} Services`}</SectionHeading>
              <button
                onClick={() => navigate(`/category/${category.slug}`)}
                className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-1 shrink-0"
              >
                View full page <ArrowRight className="w-3 h-3" />
              </button>
            </div>
            {category.description && (
              <p className="text-xs sm:text-sm text-neutral-500 mb-4 leading-relaxed">
                {category.description}
              </p>
            )}

            {/* D-2d-3 — the "Prices personalised for {CAR}" blue pill was
                removed (the CarSidebar already shows the selected car). Only
                the select-your-car nudge remains, until a vehicle is picked. */}
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
                  onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
                  className="btn-ink btn-ink-primary px-5 py-2.5 text-[10px] sm:text-xs font-black uppercase tracking-widest flex items-center gap-2 shrink-0"
                >
                  Select Your Car <ArrowRight className="w-3.5 h-3.5 btn-arrow" />
                </button>
              </motion.div>
            )}

            {/* Shared ServiceCard list (identical to Layer 2). */}
            <div className="space-y-4">
              {subServices.map((sub) => {
                // Price 4-state + cart logic UNCHANGED (D-2c-5).
                const showPrice = vehicleSelected && priceShowFromApi;
                const cartItem = cartReady
                  ? findCartItem({
                      ref_id:   sub.id,
                      brand_id: booking.car?.brand_id,
                      model_id: booking.car?.model_id,
                      fuel_id:  booking.car?.fuel_id,
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
          </section>
        )}

        {/* Trust strip — genuinely global, kept (D-2c-6). */}
        <section className="bg-white border border-border p-6 sm:p-8 grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 text-center">
          <TrustItem icon={Shield} label="Certified Centres" />
          <TrustItem icon={Sparkles} label="Genuine OEM Parts" />
          <TrustItem icon={Clock} label="Fast Turnaround" />
          <TrustItem icon={Star} label="4.8★ Avg Rating" />
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
