import type * as React from "react";
import { useMemo } from "react";
import {
  CheckCircle2,
  ArrowRight,
  Calendar,
  Clock,
  MapPin,
  Banknote,
  Package,
  Home,
  Car,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import { useOrderDetail } from "../hooks/useOrders";

interface BookingConfirmationProps {
  orderId: number;
  setCurrentPage: (page: string) => void;
}

/**
 * Phase 2.5a — real booking confirmation page. The previous fake
 * `ACR<timestamp>` flow lived inside Payment.tsx; this page replaces
 * it by reading the just-placed order from the backend.
 *
 * Reachable via the route key `booking-confirmation-{id}` from
 * App.tsx after a successful Checkout.placeOrder.
 */
export default function BookingConfirmation({
  orderId,
  setCurrentPage,
}: BookingConfirmationProps) {
  const { order, isLoading, isError } = useOrderDetail(orderId);

  const titles = useMemo(
    () =>
      (order?.items ?? [])
        .map((i) => i.service_title_snapshot)
        .filter(Boolean)
        .join(", "),
    [order],
  );

  if (isLoading) {
    return (
      <>
        <PageBanner
          title="Booking Confirmed"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            { label: "Booking Confirmed" },
          ]}
        />
        <div className="py-20 text-center text-sm text-neutral-500">
          Loading your booking…
        </div>
      </>
    );
  }

  if (isError || !order) {
    return (
      <>
        <PageBanner
          title="Booking Confirmed"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            { label: "Booking Confirmed" },
          ]}
        />
        <div className="py-16 text-center max-w-xl mx-auto">
          <p className="text-sm text-neutral-500 mb-6">
            We couldn't load that booking. It may belong to a different account.
          </p>
          <button
            onClick={() => setCurrentPage("my-bookings")}
            className="btn-ink btn-ink-primary px-6 py-3 text-xs font-black uppercase tracking-widest"
          >
            View My Bookings
          </button>
        </div>
      </>
    );
  }

  return (
    <>
      <PageBanner
        title="Booking Confirmed"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Booking Confirmed" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container max-w-3xl">
          {/* Hero */}
          <div className="bg-white border border-primary/20 p-8 sm:p-12 text-center">
            <div className="w-16 h-16 bg-primary/10 mx-auto mb-5 flex items-center justify-center rounded-full">
              <CheckCircle2 className="w-9 h-9 text-primary" />
            </div>
            <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-2">
              Booking ID
            </p>
            <h1 className="text-3xl sm:text-4xl font-black uppercase text-neutral-900 tracking-tighter mb-3">
              {order.order_number}
            </h1>
            <p className="text-sm text-neutral-500 max-w-md mx-auto leading-relaxed">
              Thanks{order.name_snapshot ? `, ${order.name_snapshot}` : ""}!
              Your booking is confirmed. We'll reach out before the service
              window to confirm pickup details.
            </p>
          </div>

          {/* Summary card */}
          <div className="bg-white border border-border mt-6">
            <div className="px-5 py-4 border-b border-border">
              <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900">
                BOOKING <span className="text-primary">DETAILS.</span>
              </h3>
            </div>

            <div className="px-5 py-4 space-y-3 text-sm">
              {/* Phase 2.5.2 — vehicle row above services. Reads
                  order.vehicle_snapshot frozen at placement time. */}
              {(() => {
                const v = order.vehicle_snapshot;
                if (!v) return null;
                const carLine = [v.brand_name, v.model_name].filter(Boolean).join(" ");
                if (!carLine && !v.fuel_name) return null;
                return (
                  <Row
                    icon={Car}
                    label="Vehicle"
                    value={
                      v.fuel_name
                        ? `${carLine || "—"} · ${v.fuel_name}`
                        : carLine || "—"
                    }
                  />
                );
              })()}
              <Row
                icon={Package}
                label="Services"
                value={titles || "—"}
              />
              {order.service_center && (
                <Row
                  icon={MapPin}
                  label="Service Center"
                  value={`${order.service_center.name} — ${order.service_center.address}`}
                />
              )}
              <Row
                icon={Calendar}
                label="Preferred Date"
                value={order.preferred_date ?? "TBC"}
              />
              <Row
                icon={Clock}
                label="Preferred Time"
                value={order.preferred_time}
              />
              {order.address && (
                <Row icon={MapPin} label="Service Address" value={order.address} />
              )}
              {order.notes && (
                <Row icon={Package} label="Notes" value={order.notes} />
              )}
            </div>

            <div className="px-5 py-4 border-t border-border space-y-2 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-neutral-500">Subtotal</span>
                <span className="font-bold text-neutral-900">
                  ₹{order.totals.subtotal}
                </span>
              </div>
              {/* Phase 2.5.1 — conditional coupon line. Renders once
                  Phase 2.5b populates the discount on placement. */}
              {order.totals.discount > 0 && (
                <div className="flex items-center justify-between">
                  <span className="text-primary">Coupon Applied</span>
                  <span className="font-bold text-primary">
                    − ₹{order.totals.discount}
                  </span>
                </div>
              )}
              <div className="flex items-center justify-between">
                <span className="text-neutral-500">GST</span>
                <span className="font-bold text-neutral-900">
                  ₹{order.totals.tax}
                </span>
              </div>
              <div className="flex items-center justify-between pt-2 border-t border-border">
                <span className="text-base font-bold uppercase tracking-tighter text-neutral-900">
                  Total
                </span>
                <span className="text-xl font-black text-primary">
                  ₹{order.totals.total}
                </span>
              </div>
            </div>

            <div className="px-5 py-4 bg-neutral-50 border-t border-border flex items-start gap-2">
              <Banknote className="w-4 h-4 text-primary mt-0.5 shrink-0" />
              <div>
                <p className="text-xs font-bold text-neutral-900">
                  Pay at Service Center
                </p>
                <p className="text-[11px] text-neutral-500 leading-relaxed">
                  No advance payment required. Pay by cash, card, or UPI when
                  you collect your vehicle.
                </p>
              </div>
            </div>
          </div>

          {/* CTA row */}
          <div className="flex flex-col sm:flex-row gap-3 mt-6">
            <button
              onClick={() => setCurrentPage("my-bookings")}
              className="btn-ink btn-ink-primary flex-1 py-4 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2"
            >
              View My Bookings <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
            <button
              onClick={() => setCurrentPage("home")}
              className="bg-white border border-border flex-1 py-4 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2 text-neutral-700 hover:border-primary hover:text-primary transition-colors"
            >
              <Home className="w-4 h-4" /> Back to Home
            </button>
          </div>
        </div>
      </div>
    </>
  );
}

function Row({
  icon: Icon,
  label,
  value,
}: {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value: string;
}) {
  return (
    <div className="flex items-start gap-3">
      <Icon className="w-4 h-4 text-primary mt-0.5 shrink-0" />
      <div className="min-w-0">
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
          {label}
        </p>
        <p className="text-sm text-neutral-900 break-words">{value}</p>
      </div>
    </div>
  );
}
