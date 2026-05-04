import type * as React from "react";
import {
  ShoppingCart,
  Minus,
  Plus,
  Trash2,
  ArrowRight,
  ArrowLeft,
  Shield,
  Clock,
  CheckCircle2,
  Tag,
  Lock,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import CouponInput from "../components/CouponInput";
import { useCart } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";

interface CartProps {
  setCurrentPage: (page: string) => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

const SERVICE_CHARGE_PCT = 0; // free service charge for now

export default function Cart({ setCurrentPage, openAuth }: CartProps) {
  const { items, updateQty, removeItem, subtotal, count, clearCart, cart } =
    useCart();
  const { isAuthenticated } = useAuth();

  // Phase 2.5.1 (D-2.5.1-5) — coupon state is server-authoritative.
  // No more local auto-apply against the OFFERS constant; the
  // CouponInput component reads cart.totals.coupon directly. While
  // the /cart/coupon backend is 501 (until 2.5b), totals.coupon
  // stays null and totals.discount stays 0.
  const totals = cart?.totals;
  const effectiveDiscount = totals?.discount ?? 0;
  const effectiveCoupon = totals?.coupon ?? null;

  // Phase 2.3.3 — GST removed from Cart per contract Decision D-B.
  // Cart is pre-tax; GST renders at Checkout.
  const serviceCharge = Math.round(subtotal * (SERVICE_CHARGE_PCT / 100));
  const subtotalAfterDiscount = Math.max(0, subtotal - effectiveDiscount);
  const total = subtotalAfterDiscount + serviceCharge;

  // Gate: checkout requires an authenticated account (anti-fake-lead).
  // Guests get prompted to login or sign up before proceeding.
  const handleCheckout = () => {
    if (!isAuthenticated) {
      openAuth("login", "checkout");
      return;
    }
    setCurrentPage("checkout");
  };



  return (
    <>
      <PageBanner
        title="Your Cart"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Cart" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container">
          {/* Step indicator: Cart → Checkout → Payment */}
          <CheckoutSteps current={1} setCurrentPage={setCurrentPage} />

          {items.length === 0 ? (
            <EmptyCart setCurrentPage={setCurrentPage} />
          ) : (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 mt-10">
              {/* ITEMS */}
              <div className="lg:col-span-2 space-y-3">
                <div className="flex items-baseline justify-between mb-2">
                  <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900">
                    CART <span className="text-primary">ITEMS.</span>
                  </h2>
                  <button
                    onClick={() => {
                      if (
                        confirm(
                          "Remove all items from your cart? This cannot be undone."
                        )
                      ) {
                        clearCart();
                      }
                    }}
                    className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-accent-dark hover:underline"
                  >
                    Clear Cart
                  </button>
                </div>

                {items.map((item) => (
                  <div
                    key={item.id}
                    className="bg-white border border-border p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 sm:gap-4"
                  >
                    <div className="min-w-0">
                      <h3 className="text-base font-black uppercase text-neutral-900 tracking-tighter mb-1">
                        {item.title}
                      </h3>
                      <div className="flex flex-wrap gap-x-3 gap-y-1 text-[10px] uppercase tracking-widest font-bold text-neutral-400 mb-3">
                        {item.car && (
                          <span>
                            {item.car.brand} {item.car.model} · {item.car.fuel}
                          </span>
                        )}
                        {item.location && <span>· {item.location}</span>}
                        {!item.car && !item.location && (
                          <span>Standard Service</span>
                        )}
                      </div>

                      {/* Qty + remove */}
                      <div className="flex items-center gap-3">
                        <div className="flex items-center border border-border">
                          <button
                            onClick={() =>
                              updateQty(item.id, item.qty - 1)
                            }
                            disabled={item.qty <= 1}
                            aria-label="Decrease quantity"
                            className="w-8 h-8 flex items-center justify-center hover:bg-neutral-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                          >
                            <Minus className="w-3.5 h-3.5" />
                          </button>
                          <span className="w-8 text-center text-sm font-black text-neutral-900">
                            {item.qty}
                          </span>
                          <button
                            onClick={() =>
                              updateQty(item.id, item.qty + 1)
                            }
                            aria-label="Increase quantity"
                            className="w-8 h-8 flex items-center justify-center hover:bg-neutral-100 transition-colors"
                          >
                            <Plus className="w-3.5 h-3.5" />
                          </button>
                        </div>
                        <button
                          onClick={() => removeItem(item.id)}
                          className="flex items-center gap-1 text-[10px] uppercase tracking-widest font-bold text-neutral-500 hover:text-accent-dark transition-colors"
                        >
                          <Trash2 className="w-3.5 h-3.5" /> Remove
                        </button>
                      </div>
                    </div>

                    {/* Price */}
                    <div className="sm:text-right sm:min-w-[120px]">
                      <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-0.5">
                        {item.qty > 1 ? `${item.qty} × ₹${item.price}` : "Price"}
                      </p>
                      <p className="text-xl font-black text-neutral-900">
                        {item.price > 0
                          ? `₹${item.price * item.qty}`
                          : "Quote"}
                      </p>
                    </div>
                  </div>
                ))}

                {/* Continue Shopping */}
                <button
                  onClick={() => setCurrentPage("services")}
                  className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-2 mt-4"
                >
                  <ArrowLeft className="w-3.5 h-3.5" /> Continue Shopping
                </button>
              </div>

              {/* ORDER SUMMARY (sticky) */}
              <aside className="lg:sticky lg:self-start lg:top-28 space-y-4">
                <div className="bg-white border border-border shadow-xl">
                  <div className="px-5 py-4 border-b border-border">
                    <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900">
                      ORDER <span className="text-primary">SUMMARY.</span>
                    </h3>
                  </div>

                  <div className="px-5 py-4 space-y-2.5 text-sm border-b border-border">
                    <Row
                      label={`Subtotal (${count} ${
                        count === 1 ? "item" : "items"
                      })`}
                      value={`₹${subtotal}`}
                    />
                    {effectiveDiscount > 0 && effectiveCoupon && (
                      <Row
                        label={
                          <span className="flex items-center gap-1.5 text-primary">
                            <Tag className="w-3 h-3" />
                            Discount ({effectiveCoupon.code})
                          </span>
                        }
                        value={
                          <span className="text-primary font-bold">
                            − ₹{effectiveDiscount}
                          </span>
                        }
                      />
                    )}
                    {serviceCharge > 0 && (
                      <Row
                        label="Service Charge"
                        value={`₹${serviceCharge}`}
                      />
                    )}
                    {/* GST line removed — see Decision D-B comment above. */}
                  </div>

                  <div className="px-5 py-4 flex items-baseline justify-between">
                    <span className="text-sm font-bold uppercase tracking-tighter text-neutral-900">
                      Total
                    </span>
                    <div className="text-right">
                      <p className="text-2xl font-black text-primary">
                        ₹{total}
                      </p>
                      {effectiveDiscount > 0 && (
                        <p className="text-[10px] text-primary font-bold uppercase tracking-widest">
                          You saved ₹{effectiveDiscount}
                        </p>
                      )}
                    </div>
                  </div>

                  <div className="px-5 pb-5">
                    <button
                      onClick={handleCheckout}
                      className="btn-ink btn-ink-primary w-full py-4 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2"
                    >
                      {isAuthenticated ? (
                        <>
                          Proceed to Checkout{" "}
                          <ArrowRight className="w-4 h-4 btn-arrow" />
                        </>
                      ) : (
                        <>
                          <Lock className="w-4 h-4" /> Login to Checkout
                        </>
                      )}
                    </button>
                    {!isAuthenticated && (
                      <p className="text-[10px] text-neutral-500 text-center mt-2">
                        New here?{" "}
                        <button
                          onClick={() => openAuth("signup", "checkout")}
                          className="text-primary font-bold hover:underline"
                        >
                          Create account
                        </button>{" "}
                        — saves your details.
                      </p>
                    )}
                  </div>
                </div>

                {/* Phase 2.5.1 — coupon input (manual; backend 501 until 2.5b). */}
                <CouponInput totals={totals} variant="cart" />

                {/* Trust strip */}
                <div className="bg-white p-5 border border-border space-y-3">
                  <TrustRow icon={Shield} text="Secure Booking" />
                  <TrustRow icon={CheckCircle2} text="Genuine OEM Parts" />
                  <TrustRow icon={Clock} text="15-Min Response" />
                </div>
              </aside>
            </div>
          )}
        </div>
      </div>
    </>
  );
}

// ---------- Sub components ----------

function Row({
  label,
  value,
}: {
  label: React.ReactNode;
  value: React.ReactNode;
}) {
  return (
    <div className="flex items-center justify-between gap-2">
      <span className="text-neutral-500">{label}</span>
      <span className="font-bold text-neutral-900">{value}</span>
    </div>
  );
}

function TrustRow({
  icon: Icon,
  text,
}: {
  icon: React.ComponentType<{ className?: string }>;
  text: string;
}) {
  return (
    <div className="flex items-center gap-3">
      <div className="bg-primary/5 p-2 shrink-0">
        <Icon className="w-4 h-4 text-primary" />
      </div>
      <span className="text-xs font-bold uppercase text-neutral-900 tracking-tighter">
        {text}
      </span>
    </div>
  );
}

function EmptyCart({
  setCurrentPage,
}: {
  setCurrentPage: (p: string) => void;
}) {
  return (
    <div className="bg-white border border-border py-20 px-6 text-center mt-10">
      <div className="w-16 h-16 bg-neutral-100 mx-auto mb-5 flex items-center justify-center">
        <ShoppingCart className="w-8 h-8 text-neutral-400" />
      </div>
      <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
        Your Cart is <span className="text-primary">Empty.</span>
      </h2>
      <p className="text-sm text-neutral-500 mb-8 max-w-md mx-auto leading-relaxed">
        Browse our service categories and add the services your car needs.
        We'll handle the rest.
      </p>
      <button
        onClick={() => setCurrentPage("services")}
        className="btn-ink btn-ink-primary px-8 py-4 text-xs font-black uppercase tracking-widest inline-flex items-center gap-2"
      >
        Browse Services <ArrowRight className="w-4 h-4 btn-arrow" />
      </button>
    </div>
  );
}

// ---------- Step indicator (shared visual across Cart/Checkout/Payment) ----------

export function CheckoutSteps({
  current,
  setCurrentPage,
}: {
  current: 1 | 2 | 3;
  setCurrentPage: (p: string) => void;
}) {
  const steps = [
    { num: 1, label: "Cart", page: "cart" },
    { num: 2, label: "Checkout", page: "checkout" },
    { num: 3, label: "Payment", page: "payment" },
  ];
  return (
    <div className="flex items-center justify-center max-w-md mx-auto gap-1 sm:gap-2">
      {steps.map((s, idx) => {
        const isActive = current === s.num;
        const isComplete = current > s.num;
        const isClickable = isComplete; // only previous steps clickable
        const stateColor = isActive
          ? "bg-primary text-white border-primary"
          : isComplete
          ? "bg-primary text-white border-primary"
          : "bg-white text-neutral-400 border-border";
        const labelColor = isActive
          ? "text-primary"
          : isComplete
          ? "text-neutral-700"
          : "text-neutral-400";
        return (
          <div key={s.num} className="flex items-center gap-1 sm:gap-2 flex-1">
            <button
              type="button"
              disabled={!isClickable}
              onClick={() => isClickable && setCurrentPage(s.page)}
              className={`flex flex-col items-center gap-1.5 shrink-0 ${
                isClickable ? "cursor-pointer" : "cursor-default"
              }`}
            >
              <div
                className={`w-8 h-8 flex items-center justify-center text-[11px] font-black border transition-colors ${stateColor}`}
              >
                {isComplete ? <CheckCircle2 className="w-4 h-4" /> : s.num}
              </div>
              <span
                className={`text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-center leading-tight ${labelColor}`}
              >
                {s.label}
              </span>
            </button>
            {idx < steps.length - 1 && (
              <div
                className={`flex-1 h-px ${
                  current > s.num ? "bg-primary" : "bg-neutral-200"
                } mt-[-18px]`}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}
