import type * as React from "react";
import { useState } from "react";
import {
  ArrowRight,
  Calendar,
  Clock,
  MapPin,
  Package,
  CheckCircle2,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import CancelOrderModal from "../components/CancelOrderModal";
import VehicleBadge from "../components/VehicleBadge";
import { useAuth } from "../hooks/useAuth";
import { useOrdersList, useCancelOrder } from "../hooks/useOrders";
import type { OrderResource, OrderStatus } from "../types/api";
import { FEATURES } from "../config/features";
import BookingsComingSoon from "./BookingsComingSoon";

interface MyBookingsProps {
  setCurrentPage: (page: string) => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

export default function MyBookings({
  setCurrentPage,
  openAuth,
}: MyBookingsProps) {
  // Phase 2.5a — bookingsList stays true; the dark-launch gate is now
  // a no-op (real /user/orders endpoint shipped).
  if (!FEATURES.bookingsList) {
    return <BookingsComingSoon setCurrentPage={setCurrentPage} openAuth={openAuth} />;
  }

  const { user, isAuthenticated, logout } = useAuth();
  const { orders, isLoading, isError } = useOrdersList({ per_page: 50 });
  const cancelMutation = useCancelOrder();
  const [cancelTarget, setCancelTarget] = useState<OrderResource | null>(null);
  const [cancelError, setCancelError] = useState<string | null>(null);

  const completed = orders.filter((o) => o.status === "completed").length;

  const openCancelModal = (order: OrderResource) => {
    setCancelError(null);
    setCancelTarget(order);
  };

  const closeCancelModal = () => {
    if (cancelMutation.isPending) return;
    setCancelTarget(null);
    setCancelError(null);
  };

  const submitCancel = async (reason: string | null) => {
    if (!cancelTarget) return;
    setCancelError(null);
    try {
      await cancelMutation.mutateAsync({ orderId: cancelTarget.id, reason });
      setCancelTarget(null);
    } catch (e) {
      setCancelError(
        e instanceof Error ? e.message : "Couldn't cancel — please try again.",
      );
    }
  };

  return (
    <>
      <PageBanner
        title="My Bookings"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "My Bookings" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container">
          {!isAuthenticated || !user ? (
            <NotLoggedIn openAuth={openAuth} setCurrentPage={setCurrentPage} />
          ) : (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10">
              {/* Sidebar */}
              <aside className="space-y-4 lg:sticky lg:self-start lg:top-28">
                <div className="bg-primary text-white p-6">
                  <div className="w-14 h-14 bg-white text-primary flex items-center justify-center text-2xl font-black mb-3">
                    {user.name.charAt(0).toUpperCase()}
                  </div>
                  <h3 className="text-lg font-black uppercase tracking-tighter">
                    {user.name}
                  </h3>
                  <p className="text-xs text-white/70 mb-1 truncate">
                    {user.email || "No email"}
                  </p>
                  <p className="text-xs text-white/70 mb-4">
                    +91 {user.phone}
                  </p>
                  <div className="grid grid-cols-2 gap-2 pt-4 border-t border-white/20 text-center">
                    <div>
                      <p className="text-2xl font-black">{orders.length}</p>
                      <p className="text-[9px] uppercase tracking-widest font-bold text-white/70">
                        Bookings
                      </p>
                    </div>
                    <div>
                      <p className="text-2xl font-black">{completed}</p>
                      <p className="text-[9px] uppercase tracking-widest font-bold text-white/70">
                        Completed
                      </p>
                    </div>
                  </div>
                </div>

                <button
                  onClick={() => {
                    if (confirm("Log out of your account?")) logout();
                  }}
                  className="w-full bg-white border border-border py-3 text-xs font-black uppercase tracking-widest text-neutral-700 hover:border-accent-dark hover:text-accent-dark transition-colors"
                >
                  Logout
                </button>
              </aside>

              {/* Bookings list */}
              <div className="lg:col-span-2 space-y-3">
                <div className="flex items-baseline justify-between mb-2">
                  <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter">
                    BOOKING <span className="text-primary">HISTORY.</span>
                  </h2>
                  <button
                    onClick={() => setCurrentPage("services")}
                    className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-1"
                  >
                    Book More <ArrowRight className="w-3 h-3" />
                  </button>
                </div>

                {isLoading ? (
                  <div className="bg-white border border-border py-12 text-center text-sm text-neutral-500">
                    Loading bookings…
                  </div>
                ) : isError ? (
                  <div className="bg-white border border-accent-dark/30 py-12 text-center text-sm text-accent-dark">
                    Couldn't load bookings.
                  </div>
                ) : orders.length === 0 ? (
                  <div className="bg-white border border-border py-16 px-6 text-center">
                    <div className="w-14 h-14 bg-neutral-100 mx-auto mb-4 flex items-center justify-center">
                      <Package className="w-7 h-7 text-neutral-400" />
                    </div>
                    <h3 className="text-lg font-black uppercase tracking-tighter text-neutral-900 mb-1">
                      No bookings yet
                    </h3>
                    <p className="text-xs text-neutral-500 mb-5 max-w-sm mx-auto">
                      Start by browsing our services. Every booking will appear
                      here.
                    </p>
                    <button
                      onClick={() => setCurrentPage("services")}
                      className="btn-ink btn-ink-primary px-6 py-3 text-xs font-black uppercase tracking-widest inline-flex items-center gap-2"
                    >
                      Browse Services <ArrowRight className="w-4 h-4 btn-arrow" />
                    </button>
                  </div>
                ) : (
                  orders.map((o) => (
                    <BookingCard
                      key={o.id}
                      order={o}
                      onView={() => setCurrentPage(`order-${o.id}`)}
                      onCancel={() => openCancelModal(o)}
                      cancelling={cancelMutation.isPending && cancelTarget?.id === o.id}
                    />
                  ))
                )}
              </div>
            </div>
          )}
        </div>
      </div>

      <CancelOrderModal
        open={cancelTarget !== null}
        orderNumber={cancelTarget?.order_number ?? ""}
        onConfirm={submitCancel}
        onClose={closeCancelModal}
        pending={cancelMutation.isPending}
        errorMessage={cancelError}
      />
    </>
  );
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

const BookingCard: React.FC<{
  order: OrderResource;
  onView: () => void;
  onCancel: () => void;
  cancelling: boolean;
}> = ({ order, onView, onCancel, cancelling }) => {
  const titles =
    order.items.map((i) => i.service_title_snapshot).filter(Boolean).join(", ") ||
    "—";
  const created = order.timestamps.created_at
    ? new Date(order.timestamps.created_at).toLocaleDateString("en-IN", {
        day: "numeric",
        month: "short",
        year: "numeric",
      })
    : "";

  return (
    <div className="bg-white border border-border">
      <div className="px-5 py-3 border-b border-border flex items-center justify-between gap-3 flex-wrap">
        <div>
          <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">
            Booking ID
          </p>
          <p className="text-sm font-black text-neutral-900 tracking-widest">
            {order.order_number}
          </p>
          {/* Phase 2.5.2 — vehicle context inline so the user can
              tell which car a booking is for at a glance. */}
          <VehicleBadge
            variant="compact"
            vehicle={order.vehicle_snapshot}
            className="mt-1"
          />
        </div>
        <div className="text-right">
          <span
            className={`inline-block px-2.5 py-1 text-[9px] font-bold uppercase tracking-widest ${statusBadge(order.status)}`}
          >
            {order.status.replace("_", " ")}
          </span>
          {created && (
            <p className="text-[10px] text-neutral-400 mt-1">Booked {created}</p>
          )}
        </div>
      </div>

      <div className="px-5 py-4 border-b border-border">
        <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5">
          Services
        </p>
        <ul className="space-y-1">
          {order.items.map((it) => (
            <li
              key={it.id}
              className="text-sm font-bold text-neutral-900 flex items-center justify-between gap-2"
            >
              <span className="flex items-center gap-2">
                <CheckCircle2 className="w-3.5 h-3.5 text-primary shrink-0" />
                {it.service_title_snapshot}
                {it.quantity > 1 && <span> × {it.quantity}</span>}
              </span>
              <span className="text-neutral-500 text-xs">
                ₹{it.line_total_snapshot}
              </span>
            </li>
          ))}
        </ul>
      </div>

      <div className="px-5 py-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <Cell icon={Calendar} label="Date" value={order.preferred_date || "TBC"} />
        <Cell icon={Clock} label="Time" value={order.preferred_time || "TBC"} />
        <Cell
          icon={MapPin}
          label="Center"
          value={order.service_center?.name || "—"}
        />
        <div>
          <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest">
            Total
          </p>
          <p className="text-sm font-black text-primary">₹{order.totals.total}</p>
        </div>
      </div>

      <div className="px-5 py-3 border-t border-border flex items-center justify-end gap-3 flex-wrap">
        {order.status === "pending" && (
          <button
            onClick={onCancel}
            disabled={cancelling}
            className="text-[10px] uppercase tracking-widest font-bold text-accent-dark hover:underline disabled:opacity-60"
          >
            {cancelling ? "Cancelling…" : "Cancel"}
          </button>
        )}
        <button
          onClick={onView}
          className="btn-ink btn-ink-primary px-4 py-2 text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5"
        >
          View Details <ArrowRight className="w-3 h-3 btn-arrow" />
        </button>
      </div>
    </div>
  );
};

function Cell({
  icon: Icon,
  label,
  value,
}: {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value: string;
}) {
  return (
    <div className="min-w-0">
      <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest flex items-center gap-1">
        <Icon className="w-3 h-3" /> {label}
      </p>
      <p className="text-xs font-bold text-neutral-900 truncate">{value}</p>
    </div>
  );
}

function NotLoggedIn({
  openAuth,
  setCurrentPage,
}: {
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
  setCurrentPage: (p: string) => void;
}) {
  return (
    <div className="bg-white border border-border py-20 px-6 text-center max-w-2xl mx-auto">
      <div className="w-14 h-14 bg-primary/10 mx-auto mb-4 flex items-center justify-center">
        <Package className="w-7 h-7 text-primary" />
      </div>
      <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
        Login to View <span className="text-primary">Bookings.</span>
      </h2>
      <p className="text-sm text-neutral-500 mb-6 max-w-md mx-auto leading-relaxed">
        Sign in to your account to see your booking history and track ongoing
        services.
      </p>
      <div className="flex flex-col sm:flex-row gap-3 justify-center">
        <button
          onClick={() => openAuth("login", "my-bookings")}
          className="btn-ink btn-ink-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2"
        >
          Login <ArrowRight className="w-4 h-4 btn-arrow" />
        </button>
        <button
          onClick={() => openAuth("signup", "my-bookings")}
          className="bg-white border border-primary text-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest hover:bg-primary/5 transition-colors"
        >
          Create Account
        </button>
        <button
          onClick={() => setCurrentPage("services")}
          className="text-[10px] uppercase tracking-widest font-bold text-neutral-500 hover:text-primary self-center"
        >
          Browse Services
        </button>
      </div>
    </div>
  );
}
