import { useState, useEffect, FormEvent, useMemo } from "react";
import {
  ArrowRight,
  ArrowLeft,
  AlertCircle,
  Calendar,
  Clock,
  MapPin,
  Mail,
  Phone,
  User,
  ShoppingCart,
  Lock,
  Shield,
  CheckCircle2,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import { useCart, useCheckout } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";
import { useBookingContext } from "../hooks/useBookingContext";
import { useServiceCenters } from "../hooks/useServiceCenters";
import { CheckoutSteps } from "./Cart";
import { FEATURES } from "../config/features";
import CheckoutComingSoon from "./CheckoutComingSoon";
import { ApiError, postPlaceOrder } from "../lib/api";
import CouponInput from "../components/CouponInput";
import VehicleBadge from "../components/VehicleBadge";
import {
  AFTERNOON_SLOTS,
  EVENING_SLOTS,
  MORNING_SLOTS,
  PREFERRED_TIME_OPTIONS,
} from "../types/api";
import { useQueryClient } from "@tanstack/react-query";

interface CheckoutProps {
  setCurrentPage: (page: string) => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

const NAME_REGEX = /^[A-Za-z][A-Za-z\s.'-]*$/;
const PHONE_REGEX = /^\d{10}$/;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const GST_PCT = 18;

export default function Checkout({ setCurrentPage, openAuth }: CheckoutProps) {
  // Phase 2.5a — checkoutFlow stays true; the dark-launch gate is
  // now a no-op (real backend ships). Kept for ops kill-switch.
  if (!FEATURES.checkoutFlow) {
    return <CheckoutComingSoon setCurrentPage={setCurrentPage} />;
  }

  const { items, subtotal, count, cart } = useCart();
  const { details, setDetails, resetDetails } = useCheckout();
  const { user, isAuthenticated, bootstrapped, setDefaults } = useAuth();
  const { state: booking } = useBookingContext();
  const { centers, isLoading: centersLoading } = useServiceCenters();
  const qc = useQueryClient();

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // Phase 2.5a — D-2.5a-1 slot UI state. Kept separate from
  // useCheckout's preferredTime so the canonical en-dash slot string
  // is stored as-is for both validation and submission.
  const [selectedSlot, setSelectedSlot] = useState<string>(
    PREFERRED_TIME_OPTIONS.includes(details.preferredTime as string)
      ? details.preferredTime
      : "",
  );

  // Phase 2.5a — service center is now a numeric id, but the legacy
  // useCheckout details.serviceCenter holds a slug ("moti-nagar"). We
  // reconcile by:
  //   - matching the existing slug against the API list to derive the id
  //   - storing the id in local state for submission
  //   - keeping the slug in useCheckout for backward-compat / draft rehydrate
  const [selectedCenterId, setSelectedCenterId] = useState<number | null>(null);

  // Pre-fill priority chain (preserved from prior phases):
  //   1. authenticated user data
  //   2. acr_checkout_v1 form draft (already in `details`)
  //   3. acr_booking_ctx_v1 — verified phone from Quick Estimate
  useEffect(() => {
    const updates: Partial<typeof details> = {};
    if (user) {
      if (!details.name && user.name) updates.name = user.name;
      if (!details.phone && user.phone) updates.phone = user.phone;
      if (!details.email && user.email) updates.email = user.email;
      if (!details.address && user.addresses && user.addresses.length > 0) {
        const def =
          user.addresses.find((a) => a.isDefault) || user.addresses[0];
        if (def) updates.address = def.address;
      }
      if (!details.serviceCenter && user.defaultLocation) {
        updates.serviceCenter = user.defaultLocation;
      }
    }
    if (!details.phone && !updates.phone && booking.phone) {
      updates.phone = booking.phone;
    }
    if (Object.keys(updates).length > 0) setDetails(updates);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user, booking.phone]);

  // Resolve service-center id from slug once centers load.
  useEffect(() => {
    if (selectedCenterId !== null) return;
    if (!centers.length) return;

    const draftSlug = details.serviceCenter || booking.location || "";
    if (draftSlug) {
      const match = centers.find((c) => c.slug === draftSlug);
      if (match) {
        setSelectedCenterId(match.id);
        return;
      }
    }
    // No prefill match — let user pick.
  }, [centers, details.serviceCenter, booking.location, selectedCenterId]);

  // ---------- Totals (Phase 2.5b — discount-aware) ----------
  // Cart subtotal is server-trusted. With a coupon applied, GST is
  // computed on (subtotal - discount) to match the backend's
  // CheckoutService::quote math; the place-order endpoint re-runs
  // the same calculation authoritatively.
  const cartDiscount = cart?.totals.discount ?? 0;
  const cartCoupon = cart?.totals.coupon ?? null;
  const subtotalAfterDiscount = Math.max(0, subtotal - cartDiscount);
  const gst = useMemo(
    () => Math.round(subtotalAfterDiscount * (GST_PCT / 100)),
    [subtotalAfterDiscount],
  );
  const total = subtotalAfterDiscount + gst;

  const handleChange = (
    field: keyof typeof details,
    value: string,
    sanitize?: (v: string) => string,
  ) => {
    const cleaned = sanitize ? sanitize(value) : value;
    setDetails({ [field]: cleaned });
    if (errors[field]) setErrors((er) => ({ ...er, [field]: "" }));
  };

  const handleSlotSelect = (slot: string) => {
    setSelectedSlot(slot);
    setDetails({ preferredTime: slot });
    if (errors.preferredTime) setErrors((er) => ({ ...er, preferredTime: "" }));
  };

  const handleCenterChange = (idStr: string) => {
    const id = idStr ? Number(idStr) : null;
    setSelectedCenterId(id);
    const slug = id ? centers.find((c) => c.id === id)?.slug ?? "" : "";
    setDetails({ serviceCenter: slug });
    if (errors.serviceCenter) setErrors((er) => ({ ...er, serviceCenter: "" }));
  };

  const validate = () => {
    const errs: Record<string, string> = {};
    if (!details.name.trim()) errs.name = "Full name is required";
    else if (!NAME_REGEX.test(details.name.trim()))
      errs.name = "Only alphabets are allowed";

    if (!details.phone) errs.phone = "Phone number is required";
    else if (!PHONE_REGEX.test(details.phone))
      errs.phone = "Enter exactly 10 digits";

    if (details.email && !EMAIL_REGEX.test(details.email))
      errs.email = "Enter a valid email";

    if (!details.preferredDate)
      errs.preferredDate = "Pick a preferred date";
    if (!selectedSlot)
      errs.preferredTime = "Please select a slot";
    else if (!PREFERRED_TIME_OPTIONS.includes(selectedSlot))
      errs.preferredTime = "Invalid slot selected";

    if (!selectedCenterId)
      errs.serviceCenter = "Choose a service center";

    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (items.length === 0) {
      setCurrentPage("cart");
      return;
    }
    if (!validate()) return;
    if (!selectedCenterId) return;
    if (submitting) return;

    setSubmitError(null);
    setSubmitting(true);

    try {
      const res = await postPlaceOrder({
        preferred_date: details.preferredDate,
        preferred_time: selectedSlot,
        service_center_id: selectedCenterId,
        address: details.address || null,
        notes: details.notes || null,
        name: details.name.trim(),
        phone: details.phone,
        email: details.email || null,
      });

      // Persist the user's new defaults so future bookings auto-fill.
      if (isAuthenticated) {
        const slug = centers.find((c) => c.id === selectedCenterId)?.slug || "";
        setDefaults({
          car: booking.car || undefined,
          location: slug || undefined,
        });
      }

      // Cart is now `converted` server-side — invalidate so the next
      // /cart fetch returns an empty active cart.
      qc.invalidateQueries({ queryKey: ["cart"] });
      qc.invalidateQueries({ queryKey: ["orders"] });

      // Reset the local checkout draft.
      resetDetails();

      // Navigate to confirmation, carrying the new order id in the
      // route state (App routes booking-confirmation-{id}).
      setCurrentPage(`booking-confirmation-${res.order.id}`);
    } catch (e) {
      const status = e instanceof ApiError ? e.status : 0;
      const msg =
        e instanceof ApiError
          ? e.message
          : "Something went wrong placing your order. Please try again.";

      if (status === 429) {
        setSubmitError(`${msg}`);
      } else if (status === 422) {
        setSubmitError(msg);
      } else if (status === 403) {
        setSubmitError(
          "Your phone is not verified. Please log in again to verify.",
        );
      } else {
        setSubmitError(msg);
      }
    } finally {
      setSubmitting(false);
    }
  };

  // Shorthand styles
  const fieldLabel =
    "text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-1.5 flex items-center gap-1";
  const fieldInput = (hasError?: string) =>
    `w-full bg-white border ${
      hasError ? "border-accent-dark" : "border-border"
    } p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900`;

  // ----- Empty cart guard -----
  if (count === 0) {
    return (
      <>
        <PageBanner
          title="Checkout"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            { label: "Cart", onClick: () => setCurrentPage("cart") },
            { label: "Checkout" },
          ]}
        />
        <div className="pb-14 pt-8">
          <div className="site-container">
            <CheckoutSteps current={2} setCurrentPage={setCurrentPage} />
            <div className="bg-white border border-border py-20 px-6 text-center mt-10 max-w-2xl mx-auto">
              <div className="w-16 h-16 bg-neutral-100 mx-auto mb-5 flex items-center justify-center">
                <ShoppingCart className="w-8 h-8 text-neutral-400" />
              </div>
              <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
                Nothing to <span className="text-primary">Checkout.</span>
              </h2>
              <p className="text-sm text-neutral-500 mb-6">
                Add services to your cart before proceeding to checkout.
              </p>
              <button
                onClick={() => setCurrentPage("services")}
                className="btn-ink btn-ink-primary px-8 py-4 text-xs font-black uppercase tracking-widest inline-flex items-center gap-2"
              >
                Browse Services <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </div>
          </div>
        </div>
      </>
    );
  }

  // ----- Hydration gate (Phase 2.5.3) -----
  // Until useAuth resolves the stored token, we don't know whether
  // the user is authenticated. Show a skeleton matching the page
  // chrome so a logged-in user doesn't see the "Login to Continue"
  // wall flash on hard-refresh.
  if (!bootstrapped) {
    return (
      <>
        <PageBanner
          title="Checkout"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            { label: "Cart", onClick: () => setCurrentPage("cart") },
            { label: "Checkout" },
          ]}
        />
        <div className="pb-14 pt-8">
          <div className="site-container">
            <CheckoutSteps current={2} setCurrentPage={setCurrentPage} />
            <CheckoutSkeleton />
          </div>
        </div>
      </>
    );
  }

  // ----- Auth guard -----
  if (!isAuthenticated) {
    return (
      <>
        <PageBanner
          title="Checkout"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            { label: "Cart", onClick: () => setCurrentPage("cart") },
            { label: "Checkout" },
          ]}
        />
        <div className="pb-14 pt-8">
          <div className="site-container">
            <CheckoutSteps current={2} setCurrentPage={setCurrentPage} />
            <div className="bg-white border border-border py-16 px-6 text-center mt-10 max-w-2xl mx-auto">
              <div className="w-14 h-14 bg-primary/10 mx-auto mb-4 flex items-center justify-center">
                <Lock className="w-7 h-7 text-primary" />
              </div>
              <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
                Login to <span className="text-primary">Continue.</span>
              </h2>
              <p className="text-sm text-neutral-500 mb-6 max-w-md mx-auto leading-relaxed">
                Login or create an account to complete your booking. Your
                details will be saved so you never have to fill them again.
              </p>
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button
                  onClick={() => openAuth("login", "checkout")}
                  className="btn-ink btn-ink-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2"
                >
                  Login <ArrowRight className="w-4 h-4 btn-arrow" />
                </button>
                <button
                  onClick={() => openAuth("signup", "checkout")}
                  className="bg-white border border-primary text-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest hover:bg-primary/5 transition-colors"
                >
                  Create Account
                </button>
              </div>
              <p className="text-[10px] text-neutral-400 mt-6 max-w-md mx-auto leading-relaxed">
                <Shield className="inline w-3 h-3 mr-1" />
                Phone & email OTP verification helps prevent fake bookings.
              </p>
            </div>
          </div>
        </div>
      </>
    );
  }

  return (
    <>
      <PageBanner
        title="Checkout"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Cart", onClick: () => setCurrentPage("cart") },
          { label: "Checkout" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container">
          <CheckoutSteps current={2} setCurrentPage={setCurrentPage} />

          <form
            onSubmit={onSubmit}
            noValidate
            className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 mt-10"
          >
            <div className="lg:col-span-2 space-y-6">
              {user && (
                <div className="bg-primary/5 border border-primary/20 px-4 py-3 flex items-center gap-3">
                  <CheckCircle2 className="w-5 h-5 text-primary shrink-0" />
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-black uppercase tracking-tighter text-neutral-900">
                      Booking as {user.name}
                    </p>
                    <p className="text-[10px] text-neutral-500 truncate">
                      Verified · {user.email || "no email"} · +91 {user.phone}
                    </p>
                  </div>
                </div>
              )}

              {/* Contact details */}
              <div className="bg-white border border-border p-5 sm:p-7">
                <h2 className="text-xl sm:text-2xl uppercase font-black text-neutral-900 mb-1 tracking-tighter">
                  CONTACT <span className="text-primary">DETAILS.</span>
                </h2>
                <p className="text-xs text-neutral-500 mb-5">
                  We'll use these to confirm your booking.
                </p>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className={fieldLabel}>
                      <User className="w-3 h-3" /> Full Name *
                    </label>
                    <input
                      type="text"
                      value={details.name}
                      onChange={(e) =>
                        handleChange("name", e.target.value, (v) =>
                          v.replace(/[^A-Za-z\s.'-]/g, ""),
                        )
                      }
                      placeholder="John Doe"
                      className={fieldInput(errors.name)}
                    />
                    <ErrorMsg msg={errors.name} />
                  </div>
                  <div>
                    <label className={fieldLabel}>
                      <Phone className="w-3 h-3" /> Phone Number *
                    </label>
                    <input
                      type="tel"
                      inputMode="numeric"
                      maxLength={10}
                      value={details.phone}
                      readOnly
                      className={`${fieldInput(errors.phone)} bg-neutral-50 cursor-not-allowed`}
                      title="Phone is your verified login identity and can't be changed here."
                    />
                    <ErrorMsg msg={errors.phone} />
                  </div>
                  <div className="sm:col-span-2">
                    <label className={fieldLabel}>
                      <Mail className="w-3 h-3" /> Email Address
                    </label>
                    <input
                      type="email"
                      value={details.email}
                      onChange={(e) => handleChange("email", e.target.value)}
                      placeholder="john@example.com (optional)"
                      className={fieldInput(errors.email)}
                    />
                    <ErrorMsg msg={errors.email} />
                  </div>
                </div>
              </div>

              {/* Service location & schedule */}
              <div className="bg-white border border-border p-5 sm:p-7">
                <h2 className="text-xl sm:text-2xl uppercase font-black text-neutral-900 mb-1 tracking-tighter">
                  SERVICE <span className="text-primary">SCHEDULE.</span>
                </h2>
                <p className="text-xs text-neutral-500 mb-5">
                  Choose your preferred date, time slot and service center.
                </p>

                <div className="space-y-5">
                  <div>
                    <label className={fieldLabel}>
                      <MapPin className="w-3 h-3" /> Service Address (optional)
                    </label>
                    <textarea
                      value={details.address}
                      onChange={(e) => handleChange("address", e.target.value)}
                      placeholder="House / Flat number, Street, Locality, City"
                      className={`${fieldInput(errors.address)} min-h-[70px]`}
                    />
                    <ErrorMsg msg={errors.address} />
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className={fieldLabel}>
                        <Calendar className="w-3 h-3" /> Preferred Date *
                      </label>
                      <input
                        type="date"
                        value={details.preferredDate}
                        min={new Date().toISOString().split("T")[0]}
                        onChange={(e) =>
                          handleChange("preferredDate", e.target.value)
                        }
                        className={fieldInput(errors.preferredDate)}
                      />
                      <ErrorMsg msg={errors.preferredDate} />
                    </div>
                    <div>
                      <label className={fieldLabel}>
                        <MapPin className="w-3 h-3" /> Service Center *
                      </label>
                      <select
                        value={selectedCenterId ?? ""}
                        onChange={(e) => handleCenterChange(e.target.value)}
                        disabled={centersLoading}
                        className={fieldInput(errors.serviceCenter)}
                      >
                        <option value="">
                          {centersLoading ? "Loading…" : "Choose a Service Center"}
                        </option>
                        {centers.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name} · {c.city}
                          </option>
                        ))}
                      </select>
                      <ErrorMsg msg={errors.serviceCenter} />
                    </div>
                  </div>

                  <div>
                    <label className={fieldLabel}>
                      <Clock className="w-3 h-3" /> Preferred Time Slot *
                    </label>
                    <div className="space-y-3 mt-1">
                      <SlotRow
                        label="MORNING"
                        slots={MORNING_SLOTS as unknown as readonly string[]}
                        selected={selectedSlot}
                        onSelect={handleSlotSelect}
                      />
                      <SlotRow
                        label="AFTERNOON"
                        slots={AFTERNOON_SLOTS as unknown as readonly string[]}
                        selected={selectedSlot}
                        onSelect={handleSlotSelect}
                      />
                      <SlotRow
                        label="EVENING"
                        slots={EVENING_SLOTS as unknown as readonly string[]}
                        selected={selectedSlot}
                        onSelect={handleSlotSelect}
                      />
                    </div>
                    <ErrorMsg msg={errors.preferredTime} />
                  </div>

                  <div>
                    <label className={fieldLabel}>Special Notes</label>
                    <textarea
                      value={details.notes}
                      onChange={(e) => handleChange("notes", e.target.value)}
                      placeholder="Anything we should know about your car or scheduling? (Optional)"
                      className={`${fieldInput()} min-h-[60px]`}
                    />
                  </div>
                </div>
              </div>

              <button
                type="button"
                onClick={() => setCurrentPage("cart")}
                className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-2"
              >
                <ArrowLeft className="w-3.5 h-3.5" /> Back to Cart
              </button>
            </div>

            {/* ORDER SUMMARY (sticky) */}
            <aside className="lg:sticky lg:self-start lg:top-28 space-y-4">
              {/* Phase 2.5.2 — vehicle context banner. Sourced from
                  the first cart item's `car` meta (set by Add-to-Cart
                  handlers); falls back to the synced booking ctx. */}
              <VehicleBadge
                variant="banner"
                vehicle={
                  items[0]?.car
                    ? {
                        brand_name: items[0].car.brand,
                        model_name: items[0].car.model,
                        fuel_name:  items[0].car.fuel,
                      }
                    : booking.car
                    ? {
                        brand_name: booking.car.brand,
                        model_name: booking.car.model,
                        fuel_name:  booking.car.fuel,
                      }
                    : null
                }
                serviceCenter={
                  selectedCenterId
                    ? centers.find((c) => c.id === selectedCenterId)?.name
                    : undefined
                }
              />
              <div className="bg-white border border-border shadow-xl">
                <div className="px-5 py-4 border-b border-border">
                  <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900">
                    ORDER <span className="text-primary">SUMMARY.</span>
                  </h3>
                </div>

                <div className="px-5 py-3 max-h-[200px] overflow-y-auto divide-y divide-border">
                  {items.map((item) => (
                    <div
                      key={item.id}
                      className="py-2.5 flex items-start justify-between gap-3"
                    >
                      <div className="min-w-0 flex-1">
                        <p className="text-xs font-black uppercase text-neutral-900 tracking-tighter truncate">
                          {item.title}
                        </p>
                        <p className="text-[10px] text-neutral-400">
                          Qty {item.qty}
                        </p>
                      </div>
                      <p className="text-sm font-bold text-neutral-900 shrink-0">
                        {item.price > 0 ? `₹${item.price * item.qty}` : "Quote"}
                      </p>
                    </div>
                  ))}
                </div>

                <div className="px-5 py-4 space-y-2.5 text-sm border-t border-border">
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-neutral-500">
                      Subtotal ({count} {count === 1 ? "item" : "items"})
                    </span>
                    <span className="font-bold text-neutral-900">
                      ₹{subtotal}
                    </span>
                  </div>
                  {cartDiscount > 0 && cartCoupon && (
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-primary">
                        Coupon ({cartCoupon.code})
                      </span>
                      <span className="font-bold text-primary">
                        − ₹{cartDiscount}
                      </span>
                    </div>
                  )}
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-neutral-500">GST ({GST_PCT}%)</span>
                    <span className="font-bold text-neutral-900">₹{gst}</span>
                  </div>
                </div>

                {/* Phase 2.5.1 — coupon input. Backend 501 until 2.5b. */}
                <div className="px-5 py-3 border-t border-border">
                  <CouponInput totals={cart?.totals} variant="summary" />
                </div>

                <div className="px-5 py-4 flex items-baseline justify-between border-t border-border">
                  <span className="text-sm font-bold uppercase tracking-tighter text-neutral-900">
                    Total
                  </span>
                  <p className="text-2xl font-black text-primary">₹{total}</p>
                </div>

                <div className="px-5 py-2 bg-neutral-50 border-t border-border">
                  <p className="text-[10px] text-neutral-500 leading-relaxed">
                    <Shield className="inline w-3 h-3 mr-1" />
                    Pay at the service center after work is complete. No advance
                    payment needed.
                  </p>
                </div>

                {submitError && (
                  <div className="px-5 py-3 bg-accent-dark/5 border-t border-accent-dark/30">
                    <p className="text-[11px] font-bold text-accent-dark flex items-start gap-1.5">
                      <AlertCircle className="w-3.5 h-3.5 mt-0.5 shrink-0" />
                      <span>{submitError}</span>
                    </p>
                  </div>
                )}

                <div className="px-5 py-4 border-t border-border">
                  <button
                    type="submit"
                    disabled={submitting}
                    className="btn-ink btn-ink-primary w-full py-4 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {submitting ? "Placing Order…" : "Place Order"}{" "}
                    {!submitting && <ArrowRight className="w-4 h-4 btn-arrow" />}
                  </button>
                </div>
              </div>
            </aside>
          </form>
        </div>
      </div>
    </>
  );
}

/**
 * Phase 2.5.2 (D-2.5.2-5) — horizontal slot row.
 *
 * The label is given a fixed `min-w-[100px]` on desktop so the
 * three rows align vertically. The button container takes the
 * remaining width via `flex-1`, and each button inside is also
 * `flex-1` so they split that remaining width equally — eliminating
 * the right-side whitespace that the user reported on 2.5.1.
 *
 * Mobile (<sm): label stacks above; buttons split 50/50 below.
 */
function SlotRow({
  label,
  slots,
  selected,
  onSelect,
}: {
  label: string;
  slots: readonly string[];
  selected: string;
  onSelect: (slot: string) => void;
}) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
      <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-500 sm:min-w-[100px] shrink-0">
        {label}
      </span>
      <div className="flex flex-1 gap-2">
        {slots.map((slot) => {
          const active = selected === slot;
          return (
            <button
              key={slot}
              type="button"
              onClick={() => onSelect(slot)}
              className={
                active
                  ? "btn-ink btn-ink-primary flex-1 px-3 py-2 text-[11px] font-black uppercase tracking-tighter whitespace-nowrap"
                  : "bg-white border border-border text-neutral-700 flex-1 px-3 py-2 text-[11px] font-bold uppercase tracking-tighter hover:border-primary hover:text-primary transition-colors whitespace-nowrap"
              }
            >
              {slot}
            </button>
          );
        })}
      </div>
    </div>
  );
}

function ErrorMsg({ msg }: { msg?: string }) {
  if (!msg) return null;
  return (
    <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
      <AlertCircle className="w-3 h-3" /> {msg}
    </p>
  );
}

/**
 * Phase 2.5.3 — auth-hydration skeleton matching the Checkout
 * left-form / right-summary two-column layout. Shown for the
 * brief window between mount and useAuth resolving the stored
 * token. Pulses use the codebase's standard
 * `bg-neutral-200 animate-pulse` vocabulary.
 */
function CheckoutSkeleton() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 mt-10">
      <div className="lg:col-span-2 space-y-6">
        <div className="bg-white border border-border p-5 sm:p-7 space-y-4">
          <div className="h-6 w-1/2 bg-neutral-200 animate-pulse" />
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="h-12 bg-neutral-100 animate-pulse" />
            <div className="h-12 bg-neutral-100 animate-pulse" />
            <div className="sm:col-span-2 h-12 bg-neutral-100 animate-pulse" />
          </div>
        </div>
        <div className="bg-white border border-border p-5 sm:p-7 space-y-4">
          <div className="h-6 w-1/2 bg-neutral-200 animate-pulse" />
          <div className="h-20 bg-neutral-100 animate-pulse" />
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="h-12 bg-neutral-100 animate-pulse" />
            <div className="h-12 bg-neutral-100 animate-pulse" />
          </div>
          <div className="space-y-2">
            <div className="h-9 bg-neutral-100 animate-pulse" />
            <div className="h-9 bg-neutral-100 animate-pulse" />
            <div className="h-9 bg-neutral-100 animate-pulse" />
          </div>
        </div>
      </div>
      <aside className="space-y-4">
        <div className="bg-primary/5 border border-primary/10 p-4">
          <div className="h-3 w-20 bg-neutral-200 animate-pulse mb-2" />
          <div className="h-5 w-2/3 bg-neutral-200 animate-pulse" />
        </div>
        <div className="bg-white border border-border">
          <div className="px-5 py-4 border-b border-border h-12 bg-neutral-50 animate-pulse" />
          <div className="px-5 py-3 space-y-3">
            <div className="h-4 bg-neutral-100 animate-pulse" />
            <div className="h-4 bg-neutral-100 animate-pulse" />
          </div>
          <div className="px-5 py-4 border-t border-border space-y-2">
            <div className="h-4 bg-neutral-100 animate-pulse" />
            <div className="h-4 bg-neutral-100 animate-pulse" />
            <div className="h-6 w-24 bg-neutral-200 animate-pulse" />
          </div>
          <div className="px-5 py-4 border-t border-border">
            <div className="h-12 bg-neutral-200 animate-pulse" />
          </div>
        </div>
      </aside>
    </div>
  );
}
