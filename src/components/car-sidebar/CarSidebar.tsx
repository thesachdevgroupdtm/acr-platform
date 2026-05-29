import { useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  Car,
  ShoppingCart,
  Plus,
  CheckCircle2,
  X,
  ArrowRight,
} from "lucide-react";
import { useCart } from "../../hooks/useCart";
import { useBookingContext } from "../../hooks/useBookingContext";
import { useModels } from "../../hooks/useVehicle";
import { VehicleSelector } from "../vehicle-selector";
import CouponInput from "../CouponInput";
import MobileShell from "./MobileShell";

/**
 * CarSidebar — the ONE cart form on every service page (Services,
 * Category, ServiceDetail). GoMechanic-style, ACR theme.
 *
 * Three states inside one footprint:
 *   A) no vehicle      → "Select your car" empty state
 *   B) vehicle + empty → car header + CHANGE + "book a service" prompt
 *   C) vehicle + cart  → car header + rows + subtotal + coupon + checkout
 *
 * The vehicle selector renders IN-PLACE (Option X) — it replaces the
 * sidebar body, same width/footprint, no center modal. Identical width
 * on all pages because every service page mounts THIS component.
 */
const inr = (n: number) => `₹${n.toLocaleString("en-IN")}`;

interface CurrentService {
  id: number;
  title: string;
  slug?: string;
  base_price?: number | string | null;
}

export interface CarSidebarProps {
  /** ServiceDetail passes the page's service → an explicit "Add to cart"
   *  CTA appears (NO auto-add). Services/Category pass null/omit → no CTA. */
  currentService?: CurrentService | null;
  vehiclePrice?: number | null;
  categorySlug?: string;
  stickyTopPx?: number;
  className?: string;
}

export default function CarSidebar({
  currentService = null,
  vehiclePrice,
  categorySlug = "",
  stickyTopPx = 96,
  className = "",
}: CarSidebarProps) {
  const navigate = useNavigate();
  const { items, cart, removeItem, addItem, findCartItem } = useCart();
  const { state } = useBookingContext();
  const [selectorOpen, setSelectorOpen] = useState(false);
  const [justAdded, setJustAdded] = useState(false);

  const car = state.car;
  const hasVehicle = !!(car?.brand_id && car?.model_id && car?.fuel_id);

  // SIDEBAR_REPLICA (D-SIDE-3) — model hero photo for STATE 2. Derived from the
  // existing useModels(brandId) cache (the same data the selector fetched when
  // the car was picked) — no booking-context change. Fallback: car silhouette.
  const { data: modelsData } = useModels(hasVehicle ? car!.brand_id ?? null : null);
  const modelPhoto = hasVehicle
    ? modelsData?.models.find((m) => m.id === car!.model_id)?.image ?? null
    : null;

  const totals = cart?.totals;
  const total = totals?.total ?? 0;
  const canCheckout = hasVehicle && items.length > 0;

  // FIX2 — NO mount-time auto-add. The detail-page service is added to the
  // cart ONLY when the user clicks "Add to cart" below (explicit, like the
  // Services/Category list rows). Empty cart stays empty until that click.
  const currentItem =
    currentService && hasVehicle
      ? findCartItem({
          ref_id: currentService.id,
          brand_id: car?.brand_id,
          model_id: car?.model_id,
          fuel_id: car?.fuel_id,
        })
      : null;
  const currentInCart = !!currentItem;

  const onAddCurrent = async () => {
    if (!currentService) return;
    if (!hasVehicle) {
      setSelectorOpen(true); // gate: pick a car first — never add price-less
      return;
    }
    if (currentInCart && currentItem) {
      removeItem(String(currentItem.id)); // toggle-remove, matches list rows
      return;
    }
    try {
      await addItem({
        serviceId: String(currentService.id),
        title: currentService.title,
        price:
          vehiclePrice != null
            ? vehiclePrice
            : typeof currentService.base_price === "number"
            ? currentService.base_price
            : Number(currentService.base_price) || 0,
        categorySlug,
        car: car ? { brand: car.brand, model: car.model, fuel: car.fuel } : undefined,
        location: state.location || undefined,
        brand_id: car?.brand_id,
        model_id: car?.model_id,
        fuel_id: car?.fuel_id,
      });
      setJustAdded(true);
      window.setTimeout(() => setJustAdded(false), 1800);
    } catch {
      // VehicleConflictError is unlikely here (cart shares the context
      // vehicle); swallow — the list-row flow owns the replace prompt.
    }
  };

  // SIDEBAR_REPLICA — STATE 1 (no car) IS the selector embedded inline (the
  // GoMechanic brand picker). STATE 2 (car picked) shows the summary below.
  // CHANGE re-opens the selector over STATE 2.
  const showSelector = selectorOpen || !hasVehicle;

  // Shared body — desktop card + mobile sheet render the same thing.
  const body = showSelector ? (
    <VehicleSelector
      className="h-[460px]"
      // No car yet → the selector is the whole no-car state, so there's
      // nothing to close back to (hide the step-1 "X"). CHANGE (hasVehicle)
      // keeps it so the user can back out to the summary.
      canClose={hasVehicle}
      onComplete={(sel) => {
        setSelectorOpen(false);
        // MANUAL_ENTRY_CONTACT (D-MAN-3/5) — manual entry has no structured
        // ids to price by; reroute to the contact lead form instead of
        // leaving a price-less car in the sidebar. Structured pick →
        // unchanged (just close; pricing shows inline as before).
        if (sel.entry_mode === "manual") navigate("/contact");
      }}
      onClose={() => setSelectorOpen(false)}
    />
  ) : (
    <div className="p-4 space-y-3">
      {/* STATE 2 — large centered model photo + name/fuel + CHANGE, LUXURY
          badge top-right. Square, compact (SIDEBAR_REPLICA_FIX D-FIX-1..4). */}
      <div className="relative">
        {car!.segment ? (
          <span className="absolute top-0 right-0 z-10 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-widest bg-primary/10 text-primary border border-primary/30">
            {car!.segment}
          </span>
        ) : null}
        <div className="flex items-center justify-center mb-2">
          {modelPhoto ? (
            <img
              src={modelPhoto}
              alt={`${car!.brand} ${car!.model}`}
              className="w-full max-w-[240px] max-h-[180px] object-contain"
              referrerPolicy="no-referrer"
            />
          ) : (
            // Fallback (D-FIX-2) — the existing car silhouette at photo size.
            <Car className="text-neutral-300" style={{ width: 120, height: 120 }} strokeWidth={1.1} />
          )}
        </div>
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-2 min-w-0">
            {/* Thin navy accent bar — sharp rectangle (D-FIX-1/4). */}
            <span className="w-1 self-stretch min-h-[1.25rem] bg-[#0E2A5C] shrink-0" aria-hidden="true" />
            <p className="min-w-0 text-base font-black text-neutral-900 tracking-tight leading-tight truncate">
              {car!.brand} {car!.model}
              <span className="font-bold text-neutral-500"> · {car!.fuel}</span>
            </p>
          </div>
          <button
            type="button"
            onClick={() => setSelectorOpen(true)}
            className="shrink-0 text-[13px] font-black uppercase tracking-widest text-primary hover:underline"
          >
            Change
          </button>
        </div>
      </div>

      {/* (GoMechanic-replica: GENUINE OEM · warranty trust strip removed.) */}

      {/* FIX2 — explicit "Add to cart" for the current detail-page
          service (replaces the removed silent auto-add). Toggles to
          "Added" and removes on second click, matching the list rows. */}
      {currentService && (
        <button
          type="button"
          onClick={onAddCurrent}
          className={`btn-ink ${
            currentInCart || justAdded ? "btn-ink-outline" : "btn-ink-primary"
          } w-full py-3 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-1.5`}
          aria-pressed={currentInCart}
        >
          {currentInCart || justAdded ? (
            <>
              <CheckCircle2 className="w-4 h-4" /> Added
            </>
          ) : (
            <>
              <ShoppingCart className="w-4 h-4" /> Add to Cart
            </>
          )}
        </button>
      )}

      <div className="border-t border-border" />

      {/* Cart rows (C) or empty-cart prompt (B) */}
      {items.length === 0 ? (
            <div className="text-center py-6 bg-neutral-50 border border-border px-4">
              <ShoppingCart className="text-neutral-300 mx-auto mb-4" style={{ width: 64, height: 64 }} strokeWidth={1.25} />
              <p className="text-sm font-bold text-neutral-700 mb-4 leading-snug">
                Go ahead and book a service for your car.
              </p>
              <button
                type="button"
                onClick={() => navigate("/services")}
                className="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
              >
                <Plus className="w-3.5 h-3.5" /> Browse Services
              </button>
            </div>
          ) : (
            // D-FIX-6 — cap the items list + internal scroll so a growing cart
            // never pushes the summary/checkout below the fold (the button
            // stays reachable without scrolling to the page footer).
            <div className="divide-y divide-border max-h-[220px] overflow-y-auto">
              {items.map((it) => (
                <div key={it.id} className="flex items-center gap-3 py-3">
                  <CheckCircle2 className="w-4 h-4 text-primary shrink-0" />
                  <p
                    className="flex-1 min-w-0 text-xs font-black uppercase text-neutral-900 tracking-tighter truncate"
                    title={it.title}
                  >
                    {it.title}
                  </p>
                  <span className="text-sm font-black text-neutral-900 tracking-tighter whitespace-nowrap">
                    {inr(it.price)}
                  </span>
                  <button
                    type="button"
                    onClick={() => removeItem(it.id)}
                    aria-label={`Remove ${it.title}`}
                    className="w-7 h-7 flex items-center justify-center text-neutral-400 hover:text-primary hover:bg-primary/5 shrink-0 transition-colors"
                  >
                    <X className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>
          )}

          {/* Summary + coupon + checkout (C) */}
          {items.length > 0 && (
            <>
              <div className="border-t border-border" />
              <div className="space-y-4">
                <div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-bold text-neutral-900">
                      Subtotal{" "}
                      <span className="text-neutral-500 font-normal">
                        ({items.length} {items.length === 1 ? "service" : "services"})
                      </span>
                    </span>
                    <span className="text-base font-black text-neutral-900 tracking-tighter">
                      {inr(totals?.subtotal ?? 0)}
                    </span>
                  </div>
                  <p className="text-xs text-neutral-500 mt-0.5">Extra charges may apply</p>
                </div>

                {totals?.coupon && (totals?.discount ?? 0) > 0 && (
                  <div className="flex items-center justify-between text-primary border-t border-border pt-3">
                    <span className="text-xs font-bold uppercase tracking-widest">
                      Coupon Discount <span className="text-primary/70">({totals.coupon.code})</span>
                    </span>
                    <span className="text-sm font-black">−{inr(totals.discount)}</span>
                  </div>
                )}

                {/* Reuse Phase 2.5b coupon UI as-is */}
                <CouponInput totals={totals} variant="summary" />

                <button
                  type="button"
                  onClick={() => navigate("/checkout")}
                  disabled={!canCheckout}
                  className="w-full flex items-center justify-between gap-3 px-5 py-4 bg-primary text-white border border-primary hover:bg-white hover:text-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary disabled:hover:text-white"
                  aria-disabled={!canCheckout}
                >
                  <span className="text-base font-black tracking-tighter">{inr(total)}</span>
                  <span className="text-sm font-black uppercase tracking-widest inline-flex items-center gap-1.5">
                    Checkout <ArrowRight className="w-4 h-4" />
                  </span>
                </button>
              </div>
            </>
          )}
    </div>
  );

  return (
    <>
      {/* Desktop sticky aside — fixed min-height so the in-place selector
          fills the SAME footprint as the collapsed card (B-4). */}
      <aside
        data-testid="car-sidebar"
        className={`hidden lg:block lg:sticky lg:self-start ${className}`}
        style={{ top: stickyTopPx }}
      >
        <div className="bg-white border border-border shadow-sm overflow-hidden lg:min-h-[460px]">
          {body}
        </div>
      </aside>

      {/* Mobile sticky bar + bottom sheet (same body). */}
      <MobileShell
        itemCount={items.length}
        total={total}
        canCheckout={canCheckout}
        onCheckout={() => navigate("/checkout")}
      >
        {body}
      </MobileShell>
    </>
  );
}
