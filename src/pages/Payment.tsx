import { useState, FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  ArrowRight,
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Smartphone,
  CreditCard,
  Building2,
  Banknote,
  ShoppingCart,
  Shield,
  Lock,
  X,
} from "lucide-react";
import PageBanner from "../components/PageBanner";
import { useCart, useCheckout } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";
import {
  LOCATIONS,
  OFFERS,
  pickBestOffer,
  computeCouponDiscount,
} from "../data/businessData";
import { CheckoutSteps } from "./Cart";
import { FEATURES } from "../config/features";
import CheckoutComingSoon from "./CheckoutComingSoon";

interface PaymentProps {
  setCurrentPage: (page: string) => void;
}

const GST_PCT = 18;

type PaymentMethod = "upi" | "card" | "netbanking" | "pos";

const METHODS: {
  id: PaymentMethod;
  label: string;
  desc: string;
  icon: typeof Smartphone;
}[] = [
  {
    id: "upi",
    label: "UPI",
    desc: "Pay instantly via any UPI app (GPay, PhonePe, Paytm)",
    icon: Smartphone,
  },
  {
    id: "card",
    label: "Credit / Debit Card",
    desc: "Visa, Mastercard, RuPay, American Express",
    icon: CreditCard,
  },
  {
    id: "netbanking",
    label: "Net Banking",
    desc: "All major Indian banks supported",
    icon: Building2,
  },
  {
    id: "pos",
    label: "Pay at Service Center",
    desc: "Cash, card or UPI on arrival — no advance needed",
    icon: Banknote,
  },
];

export default function Payment({ setCurrentPage }: PaymentProps) {
  // Phase 2.3.2 — same gate as Checkout. Defensive: this page is
  // technically unreachable when Checkout shows ComingSoon, but if
  // a user lands here directly we route them through the same
  // explanation rather than a dead fake-payment form.
  if (!FEATURES.checkoutFlow) {
    return <CheckoutComingSoon setCurrentPage={setCurrentPage} />;
  }

  const { items, subtotal, count, clearCart } = useCart();
  const { details, resetDetails } = useCheckout();
  const { user, addBooking } = useAuth();

  const [method, setMethod] = useState<PaymentMethod>("upi");
  const [upiId, setUpiId] = useState("");
  const [cardNumber, setCardNumber] = useState("");
  const [cardExpiry, setCardExpiry] = useState("");
  const [cardCvv, setCardCvv] = useState("");
  const [cardName, setCardName] = useState("");
  const [bank, setBank] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isProcessing, setIsProcessing] = useState(false);
  const [confirmed, setConfirmed] = useState(false);
  const [bookingId, setBookingId] = useState("");

  // ---------- Coupon-aware totals (synced from Cart via useCheckout) ----------
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

  // ---------- Empty cart guard ----------
  if (count === 0 && !confirmed) {
    return (
      <>
        <PageBanner
          title="Payment"
          breadcrumbs={[
            { label: "Home", onClick: () => setCurrentPage("home") },
            { label: "Cart", onClick: () => setCurrentPage("cart") },
            { label: "Payment" },
          ]}
        />
        <div className="pb-14 pt-8">
          <div className="site-container">
            <CheckoutSteps current={3} setCurrentPage={setCurrentPage} />
            <div className="bg-white border border-border py-20 px-6 text-center mt-10 max-w-2xl mx-auto">
              <div className="w-16 h-16 bg-neutral-100 mx-auto mb-5 flex items-center justify-center">
                <ShoppingCart className="w-8 h-8 text-neutral-400" />
              </div>
              <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
                Nothing to <span className="text-primary">Pay For.</span>
              </h2>
              <p className="text-sm text-neutral-500 mb-6">
                Your cart is empty. Add services first.
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

  // ---------- Validation per method ----------
  const validate = (): boolean => {
    const errs: Record<string, string> = {};
    if (method === "upi") {
      if (!upiId.trim()) errs.upi = "Enter your UPI ID";
      else if (!/^[\w.\-]+@[\w]+$/.test(upiId.trim()))
        errs.upi = "Enter a valid UPI ID (e.g. name@bank)";
    } else if (method === "card") {
      const digits = cardNumber.replace(/\s/g, "");
      if (!digits) errs.cardNumber = "Card number is required";
      else if (!/^\d{16}$/.test(digits))
        errs.cardNumber = "Enter a 16-digit card number";
      if (!cardExpiry) errs.cardExpiry = "Expiry date is required";
      else if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry))
        errs.cardExpiry = "Format MM/YY";
      if (!cardCvv) errs.cardCvv = "CVV is required";
      else if (!/^\d{3,4}$/.test(cardCvv))
        errs.cardCvv = "Enter a 3-4 digit CVV";
      if (!cardName.trim())
        errs.cardName = "Name on card is required";
    } else if (method === "netbanking") {
      if (!bank) errs.bank = "Select your bank";
    }
    // For POS no extra fields
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  // ---------- Submit handler ----------
  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!validate()) return;
    setIsProcessing(true);
    // Calls /checkout/offline on the server, which converts the cart into
    // an Order and returns the invoice number (ACR######).
    const id = await addBooking({
      items: items.map((i) => ({
        title: i.title,
        qty: i.qty,
        price: i.price,
      })),
      subtotal,
      gst,
      total,
      serviceCenter:
        LOCATIONS.find((l) => l.id === details.serviceCenter)?.name ||
        details.serviceCenter,
      preferredDate: details.preferredDate,
      preferredTime: details.preferredTime,
      address: details.address,
      paymentMethod: METHODS.find((m) => m.id === method)?.label || method,
      notes: details.notes,
    });
    setBookingId(id);
    setIsProcessing(false);
    setConfirmed(true);
  };

  const onCloseSuccess = () => {
    clearCart();
    resetDetails();
    setCurrentPage("home");
  };

  const fieldLabel =
    "text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-1.5";
  const fieldInput = (hasError?: string) =>
    `w-full bg-white border ${
      hasError ? "border-accent-dark" : "border-border"
    } p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900`;

  // ---------- Display data ----------
  const serviceCenterName =
    LOCATIONS.find((l) => l.id === details.serviceCenter)?.name ||
    details.serviceCenter ||
    "Nearest centre";

  return (
    <>
      <PageBanner
        title="Payment"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Cart", onClick: () => setCurrentPage("cart") },
          { label: "Checkout", onClick: () => setCurrentPage("checkout") },
          { label: "Payment" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container">
          <CheckoutSteps current={3} setCurrentPage={setCurrentPage} />

          <form
            onSubmit={onSubmit}
            noValidate
            className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 mt-10"
          >
            {/* PAYMENT METHODS */}
            <div className="lg:col-span-2 space-y-6">
              <div className="bg-white border border-border p-5 sm:p-7">
                <h2 className="text-xl sm:text-2xl uppercase font-black text-neutral-900 mb-1 tracking-tighter">
                  PAYMENT <span className="text-primary">METHOD.</span>
                </h2>
                <p className="text-xs text-neutral-500 mb-5 flex items-center gap-1.5">
                  <Lock className="w-3 h-3" /> All payments are encrypted and
                  secure
                </p>

                {/* Methods list */}
                <div className="space-y-2.5 mb-5">
                  {METHODS.map((m) => {
                    const Icon = m.icon;
                    const selected = method === m.id;
                    return (
                      <label
                        key={m.id}
                        className={`flex items-start gap-3 p-4 border cursor-pointer transition-colors ${
                          selected
                            ? "border-primary bg-primary/5"
                            : "border-border hover:border-primary/40 bg-white"
                        }`}
                      >
                        <input
                          type="radio"
                          name="method"
                          value={m.id}
                          checked={selected}
                          onChange={() => {
                            setMethod(m.id);
                            setErrors({});
                          }}
                          className="sr-only"
                        />
                        <div
                          className={`w-5 h-5 border-2 shrink-0 mt-0.5 flex items-center justify-center transition-colors ${
                            selected
                              ? "border-primary bg-primary"
                              : "border-neutral-300 bg-white"
                          }`}
                        >
                          {selected && (
                            <div className="w-2 h-2 bg-white" />
                          )}
                        </div>
                        <div
                          className={`p-2 shrink-0 ${
                            selected ? "bg-primary/10" : "bg-neutral-50"
                          }`}
                        >
                          <Icon
                            className={`w-5 h-5 ${
                              selected ? "text-primary" : "text-neutral-500"
                            }`}
                          />
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-black uppercase text-neutral-900 tracking-tighter">
                            {m.label}
                          </p>
                          <p className="text-xs text-neutral-500 mt-0.5 leading-relaxed">
                            {m.desc}
                          </p>
                        </div>
                      </label>
                    );
                  })}
                </div>

                {/* Method-specific inputs */}
                <div className="border-t border-border pt-5">
                  {method === "upi" && (
                    <div>
                      <label className={fieldLabel}>UPI ID *</label>
                      <input
                        type="text"
                        value={upiId}
                        onChange={(e) => {
                          setUpiId(e.target.value);
                          if (errors.upi)
                            setErrors((er) => ({ ...er, upi: "" }));
                        }}
                        placeholder="yourname@bank"
                        className={fieldInput(errors.upi)}
                      />
                      <ErrorMsg msg={errors.upi} />
                    </div>
                  )}

                  {method === "card" && (
                    <div className="space-y-4">
                      <div>
                        <label className={fieldLabel}>Card Number *</label>
                        <input
                          type="text"
                          inputMode="numeric"
                          value={cardNumber}
                          onChange={(e) => {
                            // Format as XXXX XXXX XXXX XXXX
                            const v = e.target.value
                              .replace(/\D/g, "")
                              .slice(0, 16);
                            const formatted = v.replace(/(.{4})/g, "$1 ").trim();
                            setCardNumber(formatted);
                            if (errors.cardNumber)
                              setErrors((er) => ({
                                ...er,
                                cardNumber: "",
                              }));
                          }}
                          placeholder="1234 5678 9012 3456"
                          className={fieldInput(errors.cardNumber)}
                        />
                        <ErrorMsg msg={errors.cardNumber} />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className={fieldLabel}>Expiry *</label>
                          <input
                            type="text"
                            value={cardExpiry}
                            onChange={(e) => {
                              const v = e.target.value
                                .replace(/\D/g, "")
                                .slice(0, 4);
                              const formatted =
                                v.length > 2
                                  ? `${v.slice(0, 2)}/${v.slice(2)}`
                                  : v;
                              setCardExpiry(formatted);
                              if (errors.cardExpiry)
                                setErrors((er) => ({
                                  ...er,
                                  cardExpiry: "",
                                }));
                            }}
                            placeholder="MM/YY"
                            className={fieldInput(errors.cardExpiry)}
                          />
                          <ErrorMsg msg={errors.cardExpiry} />
                        </div>
                        <div>
                          <label className={fieldLabel}>CVV *</label>
                          <input
                            type="text"
                            inputMode="numeric"
                            maxLength={4}
                            value={cardCvv}
                            onChange={(e) => {
                              const v = e.target.value
                                .replace(/\D/g, "")
                                .slice(0, 4);
                              setCardCvv(v);
                              if (errors.cardCvv)
                                setErrors((er) => ({
                                  ...er,
                                  cardCvv: "",
                                }));
                            }}
                            placeholder="123"
                            className={fieldInput(errors.cardCvv)}
                          />
                          <ErrorMsg msg={errors.cardCvv} />
                        </div>
                      </div>
                      <div>
                        <label className={fieldLabel}>Name on Card *</label>
                        <input
                          type="text"
                          value={cardName}
                          onChange={(e) => {
                            setCardName(
                              e.target.value.replace(/[^A-Za-z\s.]/g, "")
                            );
                            if (errors.cardName)
                              setErrors((er) => ({
                                ...er,
                                cardName: "",
                              }));
                          }}
                          placeholder="JOHN DOE"
                          className={`${fieldInput(errors.cardName)} uppercase`}
                        />
                        <ErrorMsg msg={errors.cardName} />
                      </div>
                    </div>
                  )}

                  {method === "netbanking" && (
                    <div>
                      <label className={fieldLabel}>Select Bank *</label>
                      <select
                        value={bank}
                        onChange={(e) => {
                          setBank(e.target.value);
                          if (errors.bank)
                            setErrors((er) => ({ ...er, bank: "" }));
                        }}
                        className={fieldInput(errors.bank)}
                      >
                        <option value="">Choose your bank</option>
                        <option value="hdfc">HDFC Bank</option>
                        <option value="icici">ICICI Bank</option>
                        <option value="sbi">State Bank of India</option>
                        <option value="axis">Axis Bank</option>
                        <option value="kotak">Kotak Mahindra Bank</option>
                        <option value="yes">Yes Bank</option>
                        <option value="pnb">Punjab National Bank</option>
                        <option value="bob">Bank of Baroda</option>
                        <option value="other">Other</option>
                      </select>
                      <ErrorMsg msg={errors.bank} />
                    </div>
                  )}

                  {method === "pos" && (
                    <div className="bg-neutral-50 border border-border p-4">
                      <p className="text-sm text-neutral-600 leading-relaxed">
                        <strong className="text-neutral-900">
                          No payment required now.
                        </strong>{" "}
                        Pay at our service center on arrival via cash, card,
                        or UPI. Your booking is confirmed once you click below.
                      </p>
                    </div>
                  )}
                </div>
              </div>

              {/* Booking summary card (shown to user before payment) */}
              <div className="bg-white border border-border p-5 sm:p-7">
                <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900 mb-3">
                  BOOKING <span className="text-primary">DETAILS.</span>
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                  <SummaryRow label="Name" value={details.name || "-"} />
                  <SummaryRow
                    label="Phone"
                    value={details.phone ? `+91 ${details.phone}` : "-"}
                  />
                  <SummaryRow
                    label="Email"
                    value={details.email || "-"}
                  />
                  <SummaryRow
                    label="Service Center"
                    value={serviceCenterName}
                  />
                  <SummaryRow
                    label="Date"
                    value={details.preferredDate || "-"}
                  />
                  <SummaryRow
                    label="Time"
                    value={
                      details.preferredTime
                        ? `${details.preferredTime.replace("-", " – ")} hrs`
                        : "-"
                    }
                  />
                  <div className="sm:col-span-2">
                    <SummaryRow
                      label="Address"
                      value={details.address || "-"}
                    />
                  </div>
                </div>
              </div>

              <button
                type="button"
                onClick={() => setCurrentPage("checkout")}
                className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-2"
              >
                <ArrowLeft className="w-3.5 h-3.5" /> Back to Checkout
              </button>
            </div>

            {/* PAYMENT TOTAL (sticky) */}
            <aside className="lg:sticky lg:self-start lg:top-28 space-y-4">
              <div className="bg-white border border-border shadow-xl">
                <div className="px-5 py-4 border-b border-border">
                  <h3 className="text-base font-black uppercase tracking-tighter text-neutral-900">
                    PAYMENT <span className="text-primary">TOTAL.</span>
                  </h3>
                </div>

                <div className="px-5 py-3 max-h-[180px] overflow-y-auto divide-y divide-border">
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
                      <span className="text-primary">
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
                    Total Payable
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
                    disabled={isProcessing}
                    className="btn-ink btn-ink-primary w-full py-4 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-60"
                  >
                    {isProcessing
                      ? "Processing..."
                      : method === "pos"
                      ? "Confirm Booking"
                      : `Pay ₹${total}`}{" "}
                    {!isProcessing && (
                      <ArrowRight className="w-4 h-4 btn-arrow" />
                    )}
                  </button>
                  <p className="text-[10px] text-neutral-400 mt-2 text-center flex items-center justify-center gap-1">
                    <Shield className="w-3 h-3" /> Secure payment · 256-bit SSL
                  </p>
                </div>
              </div>
            </aside>
          </form>
        </div>
      </div>

      {/* ──────────── BOOKING CONFIRMED POPUP ──────────── */}
      <AnimatePresence>
        {confirmed && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-5">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={onCloseSuccess}
              className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
            />

            <motion.div
              initial={{ opacity: 0, y: 30, scale: 0.96 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 30, scale: 0.96 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
              className="relative w-full max-w-xl bg-white border border-border shadow-2xl flex flex-col max-h-[88vh]"
            >
              <button
                onClick={onCloseSuccess}
                aria-label="Close"
                className="absolute top-4 right-4 w-9 h-9 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors z-30"
              >
                <X className="w-5 h-5" />
              </button>

              <div className="flex-1 overflow-y-auto">
                <div className="px-5 sm:px-8 pt-12 pb-7 flex flex-col items-center w-full max-w-md mx-auto">
                  <motion.div
                    initial={{ scale: 0, rotate: -180 }}
                    animate={{ scale: 1, rotate: 0 }}
                    transition={{
                      delay: 0.1,
                      type: "spring",
                      stiffness: 200,
                      damping: 15,
                    }}
                    className="w-16 h-16 bg-primary flex items-center justify-center shadow-lg mb-5 shrink-0"
                  >
                    <CheckCircle2 className="w-8 h-8 text-white" />
                  </motion.div>

                  <motion.h3
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.25 }}
                    className="text-2xl sm:text-3xl font-black uppercase tracking-tighter text-neutral-900 text-center mb-2"
                  >
                    Booking Confirmed
                  </motion.h3>

                  <motion.p
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.32 }}
                    className="text-sm text-neutral-600 text-center mb-5"
                  >
                    Thank you{" "}
                    <span className="font-bold text-neutral-900">
                      {details.name}
                    </span>
                    ! Your booking has been confirmed.
                  </motion.p>

                  <motion.div
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.4 }}
                    className="w-full bg-neutral-50 border border-border divide-y divide-border mb-3"
                  >
                    <div className="px-4 py-3 flex flex-col gap-1">
                      <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                        Services Booked
                      </span>
                      <p className="text-sm font-semibold text-neutral-900 leading-snug break-words">
                        {items
                          .map(
                            (i) =>
                              `${i.title}${i.qty > 1 ? ` × ${i.qty}` : ""}`
                          )
                          .join(", ")}
                      </p>
                    </div>
                    <div className="px-4 py-3 flex flex-col gap-1">
                      <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                        Service Center
                      </span>
                      <p className="text-sm font-semibold text-neutral-900">
                        {serviceCenterName}
                      </p>
                    </div>
                    <div className="px-4 py-3 grid grid-cols-2 gap-3">
                      <div>
                        <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                          Date
                        </span>
                        <p className="text-sm font-semibold text-neutral-900">
                          {details.preferredDate || "TBC"}
                        </p>
                      </div>
                      <div>
                        <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                          Time
                        </span>
                        <p className="text-sm font-semibold text-neutral-900">
                          {details.preferredTime
                            ? `${details.preferredTime.replace(
                                "-",
                                " – "
                              )} hrs`
                            : "TBC"}
                        </p>
                      </div>
                    </div>
                    <div className="px-4 py-3 flex items-center justify-between">
                      <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                        Payment
                      </span>
                      <span className="text-sm font-bold text-neutral-900">
                        {method === "pos"
                          ? "Pay at Center"
                          : `₹${total} · ${
                              METHODS.find((m) => m.id === method)?.label
                            }`}
                      </span>
                    </div>
                  </motion.div>

                  <motion.div
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.5 }}
                    className="w-full flex items-center justify-between bg-primary/5 border border-primary/20 px-4 py-3 mb-6"
                  >
                    <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                      Booking ID
                    </span>
                    <span className="text-base sm:text-lg font-black text-primary tracking-widest">
                      {bookingId}
                    </span>
                  </motion.div>

                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 0.62 }}
                    className="w-full flex flex-col gap-2"
                  >
                    <button
                      onClick={() => {
                        clearCart();
                        resetDetails();
                        setCurrentPage("my-bookings");
                      }}
                      className="w-full bg-primary text-white py-4 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-primary-dark transition-colors font-sans"
                    >
                      View My Bookings
                    </button>
                    <button
                      onClick={onCloseSuccess}
                      className="w-full bg-white border border-border text-neutral-700 py-3 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 hover:border-neutral-900 transition-colors font-sans"
                    >
                      Back to Home
                    </button>
                  </motion.div>
                </div>
              </div>

              <div className="bg-neutral-50 border-t border-border px-5 py-3 flex flex-wrap justify-center gap-x-6 gap-y-1.5 text-[9px] font-black uppercase tracking-widest text-muted shrink-0">
                <div className="flex items-center gap-2">
                  <Shield className="w-3 h-3 text-neutral-400" /> Secure
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle2 className="w-3 h-3 text-neutral-400" />{" "}
                  Confirmed
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </>
  );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="min-w-0">
      <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-0.5">
        {label}
      </p>
      <p className="text-sm font-bold text-neutral-900 break-words">{value}</p>
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
