import { Car, MapPin } from "lucide-react";

export interface VehicleBadgeData {
  brand_name?: string | null;
  model_name?: string | null;
  fuel_name?: string | null;
}

interface VehicleBadgeProps {
  vehicle: VehicleBadgeData | null | undefined;
  /** Optional service-center display name to show alongside the vehicle. */
  serviceCenter?: string | null;
  /**
   * - `compact`: single-line, all-caps separator dots — for embedding next to
   *   cart-line item titles or order-card metadata.
   * - `detailed`: brand+model big, fuel + center as subline — for OrderDetail.
   * - `banner`: card-style block with a "SERVICING" eyebrow — for the
   *   right-side Checkout summary above totals.
   */
  variant?: "compact" | "detailed" | "banner";
  className?: string;
}

/**
 * Phase 2.5.2 (D-2.5.2-3) — shared vehicle context renderer used
 * across Cart / Checkout / BookingConfirmation / OrderDetail /
 * MyBookings. The user reported that vehicle context was implicit
 * across the order flow; this component makes it explicit and
 * stylistically consistent.
 *
 * Returns null when there's nothing useful to render — callers can
 * mount unconditionally and let this component decide.
 */
export default function VehicleBadge({
  vehicle,
  serviceCenter,
  variant = "compact",
  className = "",
}: VehicleBadgeProps) {
  if (!vehicle) return null;

  const brand = vehicle.brand_name?.trim() || "";
  const model = vehicle.model_name?.trim() || "";
  const fuel  = vehicle.fuel_name?.trim()  || "";
  const carLine = [brand, model].filter(Boolean).join(" ");
  if (!carLine && !fuel) return null;

  if (variant === "banner") {
    return (
      <div
        className={`bg-primary/5 border border-primary/20 p-4 ${className}`}
      >
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1.5 flex items-center gap-1">
          <Car className="w-3 h-3" /> Servicing
        </p>
        <p className="text-sm font-black uppercase tracking-tighter text-neutral-900">
          {carLine || "—"}
          {fuel && (
            <span className="text-neutral-500 font-bold"> · {fuel}</span>
          )}
        </p>
        {serviceCenter && (
          <p className="text-[11px] text-neutral-500 mt-1 flex items-center gap-1">
            <MapPin className="w-3 h-3" /> {serviceCenter}
          </p>
        )}
      </div>
    );
  }

  if (variant === "detailed") {
    return (
      <div className={className}>
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-1 flex items-center gap-1">
          <Car className="w-3 h-3" /> Vehicle
        </p>
        <p className="text-base font-black uppercase tracking-tighter text-neutral-900">
          {carLine || "—"}
        </p>
        <p className="text-xs text-neutral-500 mt-0.5">
          {fuel && <span>{fuel}</span>}
          {fuel && serviceCenter && <span> · </span>}
          {serviceCenter && <span>{serviceCenter}</span>}
        </p>
      </div>
    );
  }

  // compact — single line, dot-separated, used inline.
  const parts = [carLine, fuel].filter(Boolean).join(" · ");
  return (
    <p
      className={`text-[11px] font-bold uppercase tracking-widest text-neutral-500 ${className}`}
    >
      {parts}
      {serviceCenter && <span> · {serviceCenter}</span>}
    </p>
  );
}
