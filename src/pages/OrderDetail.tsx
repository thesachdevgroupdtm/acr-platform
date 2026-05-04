import type * as React from "react";
import { useState } from "react";
import {
  ArrowLeft,
  Calendar,
  Clock,
  MapPin,
  Banknote,
  Package,
  CheckCircle2,
  AlertCircle,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import CancelOrderModal from "../components/CancelOrderModal";
import VehicleBadge from "../components/VehicleBadge";
import { useOrderDetail, useCancelOrder } from "../hooks/useOrders";
import { useAuth } from "../hooks/useAuth";
import type { OrderStatus } from "../types/api";

interface OrderDetailProps {
  orderId: number;
  setCurrentPage: (page: string) => void;
}

function statusBadge(status: OrderStatus): string {
  switch (status) {
    case "pending":     return "bg-amber-500 text-white";
    case "confirmed":   return "bg-primary text-white";
    case "in_service":  return "bg-indigo-600 text-white";
    case "completed":   return "bg-neutral-900 text-white";
    case "cancelled":   return "bg-neutral-400 text-white";
    default:            return "bg-neutral-300 text-neutral-700";
  }
}

/**
 * Phase 2.5a — full-detail order view. Reachable via the
 * `order-{id}` route key from MyBookings or the booking confirmation
 * page. The cancel CTA is gated to status='pending' (D-2.5a-5).
 */
export default function OrderDetail({
  orderId,
  setCurrentPage,
}: OrderDetailProps) {
  const { order, isLoading, isError } = useOrderDetail(orderId);
  const { bootstrapped } = useAuth();
  const cancelMutation = useCancelOrder();
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelError, setCancelError] = useState<string | null>(null);

  const handleCancel = async (reason: string | null) => {
    if (!order) return;
    setCancelError(null);
    try {
      await cancelMutation.mutateAsync({ orderId: order.id, reason });
      setShowCancelModal(false);
    } catch (e) {
      setCancelError(e instanceof Error ? e.message : "Couldn't cancel");
    }
  };

  const closeCancelModal = () => {
    if (cancelMutation.isPending) return;
    setShowCancelModal(false);
    setCancelError(null);
  };

  // Phase 2.5.3 — show the skeleton both while React Query is
  // fetching AND while useAuth is still bootstrapping. Otherwise a
  // hard-refresh of /order/{id} with a token that hasn't been
  // validated yet briefly renders the "We couldn't load that order"
  // error state if the cached query fires before the token is
  // confirmed valid.
  if (isLoading || !bootstrapped) {
    return (
      <>
        <PageBanner
          title="Order Details"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            {
              label: "My Bookings",
              onClick: () => setCurrentPage("my-bookings"),
            },
            { label: "Details" },
          ]}
        />
        <div className="pb-14 pt-8">
          <div className="site-container max-w-3xl">
            <OrderDetailSkeleton />
          </div>
        </div>
      </>
    );
  }

  if (isError || !order) {
    return (
      <>
        <PageBanner
          title="Order Details"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            {
              label: "My Bookings",
              onClick: () => setCurrentPage("my-bookings"),
            },
            { label: "Details" },
          ]}
        />
        <div className="py-16 text-center max-w-xl mx-auto">
          <p className="text-sm text-neutral-500 mb-6">
            We couldn't load that order.
          </p>
          <button
            onClick={() => setCurrentPage("my-bookings")}
            className="btn-ink btn-ink-primary px-6 py-3 text-xs font-black uppercase tracking-widest"
          >
            Back to My Bookings
          </button>
        </div>
      </>
    );
  }

  const v = order.vehicle_snapshot;

  return (
    <>
      <PageBanner
        title={order.order_number}
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          {
            label: "My Bookings",
            onClick: () => setCurrentPage("my-bookings"),
          },
          { label: order.order_number },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container max-w-3xl">
          <button
            onClick={() => setCurrentPage("my-bookings")}
            className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-2 mb-5"
          >
            <ArrowLeft className="w-3.5 h-3.5" /> Back to My Bookings
          </button>

          {/* Header */}
          <div className="bg-white border border-border p-5 sm:p-7">
            <div className="flex items-start justify-between gap-4 flex-wrap">
              <div>
                <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">
                  Booking ID
                </p>
                <h1 className="text-2xl sm:text-3xl font-black uppercase tracking-tighter text-neutral-900">
                  {order.order_number}
                </h1>
              </div>
              <span
                className={`px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest ${statusBadge(order.status)}`}
              >
                {order.status.replace("_", " ")}
              </span>
            </div>
          </div>

          {/* Phase 2.5.2 — prominent vehicle section via shared
              VehicleBadge for consistency with the rest of the
              checkout/order flow. */}
          {v && (
            <div className="bg-white border border-border mt-4 p-5 sm:p-6">
              <VehicleBadge
                variant="detailed"
                vehicle={v}
                serviceCenter={order.service_center?.name ?? null}
              />
            </div>
          )}

          {/* Items */}
          <div className="bg-white border border-border mt-4">
            <div className="px-5 py-4 border-b border-border">
              <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900">
                SERVICES <span className="text-primary">BOOKED.</span>
              </h3>
            </div>
            <ul className="divide-y divide-border">
              {order.items.map((it) => (
                <li
                  key={it.id}
                  className="px-5 py-3 flex items-center justify-between gap-3"
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <CheckCircle2 className="w-4 h-4 text-primary shrink-0" />
                    <span className="text-sm font-bold text-neutral-900 truncate">
                      {it.service_title_snapshot}
                    </span>
                    {it.quantity > 1 && (
                      <span className="text-xs text-neutral-500">
                        × {it.quantity}
                      </span>
                    )}
                  </div>
                  <span className="text-sm font-bold text-neutral-900 shrink-0">
                    ₹{it.line_total_snapshot}
                  </span>
                </li>
              ))}
            </ul>
          </div>

          {/* Schedule + center + address */}
          <div className="bg-white border border-border mt-4 p-5 sm:p-6 space-y-3 text-sm">
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
            <Row icon={Clock} label="Preferred Time" value={order.preferred_time} />
            {order.address && (
              <Row icon={MapPin} label="Service Address" value={order.address} />
            )}
            {order.notes && (
              <Row icon={Package} label="Notes" value={order.notes} />
            )}
            {order.timestamps.cancelled_reason && (
              <Row
                icon={AlertCircle}
                label="Cancellation reason"
                value={order.timestamps.cancelled_reason}
              />
            )}
          </div>

          {/* Totals + payment */}
          <div className="bg-white border border-border mt-4">
            <div className="px-5 py-4 space-y-2 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-neutral-500">Subtotal</span>
                <span className="font-bold text-neutral-900">
                  ₹{order.totals.subtotal}
                </span>
              </div>
              {order.totals.discount > 0 && (
                <div className="flex items-center justify-between">
                  <span className="text-primary">
                    {order.totals.coupon
                      ? `Coupon (${order.totals.coupon.code})`
                      : "Discount"}
                  </span>
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
                  Payment status: {order.payment_status}. Pay by cash, card, or UPI on collection.
                </p>
              </div>
            </div>
          </div>

          {/* Cancel CTA */}
          {order.status === "pending" && (
            <div className="mt-6 flex justify-end">
              <button
                onClick={() => setShowCancelModal(true)}
                className="bg-white border border-accent-dark text-accent-dark px-5 py-3 text-xs font-black uppercase tracking-widest hover:bg-accent-dark hover:text-white transition-colors"
              >
                Cancel Booking
              </button>
            </div>
          )}
        </div>
      </div>

      <CancelOrderModal
        open={showCancelModal}
        orderNumber={order.order_number}
        onConfirm={handleCancel}
        onClose={closeCancelModal}
        pending={cancelMutation.isPending}
        errorMessage={cancelError}
      />
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

/**
 * Phase 2.5.3 — auth-hydration / data-loading skeleton matching the
 * OrderDetail layout: header card, vehicle row, services list,
 * schedule rows, totals + payment notice.
 */
function OrderDetailSkeleton() {
  return (
    <>
      <div className="h-3 w-32 bg-neutral-200 animate-pulse mb-5" />

      <div className="bg-white border border-border p-5 sm:p-7">
        <div className="flex items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="h-2 w-20 bg-neutral-200 animate-pulse" />
            <div className="h-7 w-44 bg-neutral-200 animate-pulse" />
          </div>
          <div className="h-6 w-24 bg-neutral-200 animate-pulse" />
        </div>
      </div>

      <div className="bg-white border border-border mt-4 p-5 sm:p-6 space-y-2">
        <div className="h-3 w-20 bg-neutral-200 animate-pulse" />
        <div className="h-5 w-2/3 bg-neutral-200 animate-pulse" />
        <div className="h-3 w-1/2 bg-neutral-200 animate-pulse" />
      </div>

      <div className="bg-white border border-border mt-4">
        <div className="px-5 py-4 border-b border-border">
          <div className="h-5 w-44 bg-neutral-200 animate-pulse" />
        </div>
        <div className="divide-y divide-border">
          {[0, 1, 2].map((i) => (
            <div key={i} className="px-5 py-3 flex items-center justify-between">
              <div className="h-4 w-1/2 bg-neutral-100 animate-pulse" />
              <div className="h-4 w-16 bg-neutral-100 animate-pulse" />
            </div>
          ))}
        </div>
      </div>

      <div className="bg-white border border-border mt-4 p-5 sm:p-6 space-y-3">
        {[0, 1, 2, 3].map((i) => (
          <div key={i} className="space-y-1">
            <div className="h-2 w-24 bg-neutral-200 animate-pulse" />
            <div className="h-4 w-3/4 bg-neutral-100 animate-pulse" />
          </div>
        ))}
      </div>

      <div className="bg-white border border-border mt-4 px-5 py-4 space-y-2">
        <div className="h-3 bg-neutral-100 animate-pulse" />
        <div className="h-3 bg-neutral-100 animate-pulse" />
        <div className="h-6 w-24 bg-neutral-200 animate-pulse" />
      </div>
    </>
  );
}
