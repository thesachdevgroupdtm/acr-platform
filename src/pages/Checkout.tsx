import { useState, useEffect, FormEvent } from "react";
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
  Tag,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import { useCart, useCheckout } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";
import { useBookingContext } from "../hooks/useBookingContext";
import {
  LOCATIONS,
  OFFERS,
  pickBestOffer,
  computeCouponDiscount,
} from "../data/businessData";
import { CheckoutSteps } from "./Cart";
import { FEATURES } from "../config/features";
import CheckoutComingSoon from "./CheckoutComingSoon";

interface CheckoutProps {
  setCurrentPage: (page: string) => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

const NAME_REGEX = /^[A-Za-z][A-Za-z\s.'-]*$/;
const PHONE_REGEX = /^\d{10}$/;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const GST_PCT = 18;

export default function Checkout({ setCurrentPage, openAuth }: CheckoutProps) {
  // Phase 2.3.2 — gate the pre-2.5 client-side fake checkout flow.
  // Real /checkout/place-order ships in Phase 2.5; until then the
  // ComingSoon notice is shown so users don't get a fake invoice.
  if (!FEATURES.checkoutFlow) {
    return <CheckoutComingSoon setCurrentPage={setCurrentPage} />;
  }

  const { items, subtotal, count } = useCart();
  const { details, setDetails } = useCheckout();
  const { user, isAuthenticated, setDefaults } = useAuth();
  // Pull synced booking context — gives us the car the user already chose
  // on the parent service category page, so we can save it to their profile.
  const { state: booking } = useBookingContext();
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Phase 2.3.2 — prefill priority chain (Bug B):
  //   1. useAuth().user            (server-verified PII; highest fidelity)
  //   2. acr_checkout_v1 (`details`) (last form draft — already loaded into state)
  //   3. acr_booking_ctx_v1.phone   (verified phone from Quick Estimate OTP)
  //
  // We only set fields that are EMPTY in `details` so the user's edits
  // survive a re-render. Once a field is populated from any source, the
  // user controls it.
  useEffect(() => {
    const updates: Partial<typeof details> = {};

    // Priority 1 — authenticated user wins for any empty field.
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

    // Priority 3 fallback — Quick Estimate captured a verified phone but
    // the user is not logged in. Auth wins above; this only applies when
    // user.phone is unavailable AND draft is empty.
    if (!details.phone && !updates.phone && booking.phone) {
      updates.phone = booking.phone;
    }

    if (Object.keys(updates).length > 0) setDetails(updates);
    // setDetails is stable from useCheckout
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user, booking.phone]);


  // ---------- Coupon-aware totals (synced from Cart via useCheckout) ----------
  // The Cart writes the chosen coupon code into the shared checkout details,
  // so here we just resolve and apply it. Falling back to the auto best-offer
  // means even users who skipped the coupon panel still get a discount.
  const isFirstTime = !user || user.bookings.length === 0;
  const cartCategorySlugs = Array.from(
    new Set(items.map((i) => i.categorySlug).filter(Boolean))
  ) as string[];
  const manualCoupon = details.couponCode
    ? OFFERS.find((c) => c.code === details.couponCode) || null
    : null;
  const manualDiscount = manualCoupon
    ? computeCouponDiscount(manualCoupon, {
        subtotal,
        cartCategorySlugs,
        isFirstTime,
      })
    : 0;
  const auto = pickBestOffer(OFFERS, {
    subtotal,
    cartCategorySlugs,
    isFirstTime,
  });
  const effectiveCoupon =
    manualCoupon && manualDiscount > 0 ? manualCoupon : auto?.coupon || null;
  const effectiveDiscount =
    manualCoupon && manualDiscount > 0
      ? manualDiscount
      : auto?.discount || 0;

  const subtotalAfterDiscount = Math.max(0, subtotal - effectiveDiscount);
  const gst = Math.round(subtotalAfterDiscount * (GST_PCT / 100));
  const total = subtotalAfterDiscount + gst;

  const handleChange = (
    field: keyof typeof details,
    value: string,
    sanitize?: (v: string) => string
  ) => {
    const cleaned = sanitize ? sanitize(value) : value;
    setDetails({ [field]: cleaned });
    if (errors[field]) setErrors((er) => ({ ...er, [field]: "" }));
  };

  const validate = () => {
    const errs: Record<string, string> = {};
    if (!details.name.trim()) errs.name = "Full name is required";
    else if (!NAME_REGEX.test(details.name.trim()))
      errs.name = "Only alphabets are allowed";

    if (!details.phone) errs.phone = "Phone number is required";
    else if (!PHONE_REGEX.test(details.phone))
      errs.phone = "Enter exactly 10 digits";

    if (!details.email) errs.email = "Email is required";
    else if (!EMAIL_REGEX.test(details.email))
      errs.email = "Enter a valid email";

    if (!details.address.trim()) errs.address = "Service address is required";
    if (!details.preferredDate)
      errs.preferredDate = "Pick a preferred date";
    if (!details.preferredTime)
      errs.preferredTime = "Pick a preferred time slot";
    if (!details.serviceCenter)
      errs.serviceCenter = "Choose a service center";

    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (items.length === 0) {
      setCurrentPage("cart");
      return;
    }
    if (validate()) {
      // Persist car + service-center + address to the user's profile so
      // next time these auto-fill — true "fill once, never again".
      if (isAuthenticated) {
        setDefaults({
          car: booking.car || undefined,
          location: details.serviceCenter,
        });
        // Address persistence is gated to Phase 2.5 — the proper address
        // picker UI lands with the order/checkout flow. The /user/addresses
        // CRUD is live (Phase 2.2) but Checkout's free-form single-string
        // address can't be split into the structured fields the API
        // expects, so we no-op here until the picker is wired in.
      }
      setCurrentPage("payment");
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
                Browse Services{" "}
                <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </div>
          </div>
        </div>
      </>
    );
  }

  // ----- Auth guard -----
  // Checkout is gated behind authentication: this is the strongest
  // anti-fake-lead protection (verified phone + email + password = real account).
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
            {/* FORM */}
            <div className="lg:col-span-2 space-y-6">
              {/* Logged in identity banner */}
              {user && (
                <div className="bg-primary/5 border border-primary/20 px-4 py-3 flex items-center gap-3">
                  <CheckCircle2 className="w-5 h-5 text-primary shrink-0" />
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-black uppercase tracking-tighter text-neutral-900">
                      Booking as {user.name}
                    </p>
                    <p className="text-[10px] text-neutral-500 truncate">
                      Verified · {user.email} · +91 {user.phone}
                    </p>
                  </div>
                </div>
              )}

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
                          v.replace(/[^A-Za-z\s.'-]/g, "")
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
                      onChange={(e) =>
                        handleChange("phone", e.target.value, (v) =>
                          v.replace(/\D/g, "").slice(0, 10)
                        )
                      }
                      placeholder="10-digit number"
                      className={fieldInput(errors.phone)}
                    />
                    <ErrorMsg msg={errors.phone} />
                  </div>
                  <div className="sm:col-span-2">
                    <label className={fieldLabel}>
                      <Mail className="w-3 h-3" /> Email Address *
                    </label>
                    <input
                      type="email"
                      value={details.email}
                      onChange={(e) => handleChange("email", e.target.value)}
                      placeholder="john@example.com"
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
                  Choose your preferred date, time and service center.
                </p>

                <div className="space-y-4">
                  <div>
                    <label className={fieldLabel}>
                      <MapPin className="w-3 h-3" /> Service Address *
                    </label>
                    <textarea
                      value={details.address}
                      onChange={(e) =>
                        handleChange("address", e.target.value)
                      }
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
                        <Clock className="w-3 h-3" /> Preferred Time *
                      </label>
                      <select
                        value={details.preferredTime}
                        onChange={(e) =>
                          handleChange("preferredTime", e.target.value)
                        }
                        className={fieldInput(errors.preferredTime)}
                      >
                        <option value="">Select Time Slot</option>
                        <option value="9-11">9 AM – 11 AM</option>
                        <option value="11-13">11 AM – 1 PM</option>
                        <option value="13-15">1 PM – 3 PM</option>
                        <option value="15-17">3 PM – 5 PM</option>
                        <option value="17-19">5 PM – 7 PM</option>
                      </select>
                      <ErrorMsg msg={errors.preferredTime} />
                    </div>
                  </div>

                  <div>
                    <label className={fieldLabel}>
                      <MapPin className="w-3 h-3" /> Service Center *
                    </label>
                    <select
                      value={details.serviceCenter}
                      onChange={(e) =>
                        handleChange("serviceCenter", e.target.value)
                      }
                      className={fieldInput(errors.serviceCenter)}
                    >
                      <option value="">Choose a Service Center</option>
                      {LOCATIONS.map((loc) => (
                        <option key={loc.id} value={loc.id}>
                          {loc.name}
                        </option>
                      ))}
                    </select>
                    <ErrorMsg msg={errors.serviceCenter} />
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

              {/* Back button */}
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
              <div className="bg-white border border-border shadow-xl">
                <div className="px-5 py-4 border-b border-border">
                  <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900">
                    ORDER <span className="text-primary">SUMMARY.</span>
                  </h3>
                </div>

                {/* Items list */}
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
                        {item.price > 0
                          ? `₹${item.price * item.qty}`
                          : "Quote"}
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
                  {effectiveDiscount > 0 && effectiveCoupon && (
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-primary flex items-center gap-1.5">
                        <Tag className="w-3 h-3" />
                        Discount ({effectiveCoupon.code})
                      </span>
                      <span className="font-bold text-primary">
                        − ₹{effectiveDiscount}
                      </span>
                    </div>
                  )}
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-neutral-500">GST ({GST_PCT}%)</span>
                    <span className="font-bold text-neutral-900">₹{gst}</span>
                  </div>
                </div>

                <div className="px-5 py-4 flex items-baseline justify-between border-t border-border">
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
                    type="submit"
                    className="btn-ink btn-ink-primary w-full py-4 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2"
                  >
                    Proceed to Payment{" "}
                    <ArrowRight className="w-4 h-4 btn-arrow" />
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

function ErrorMsg({ msg }: { msg?: string }) {
  if (!msg) return null;
  return (
    <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
      <AlertCircle className="w-3 h-3" /> {msg}
    </p>
  );
}
