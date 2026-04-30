import { useEffect, useMemo, useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  CheckCircle2,
  ArrowRight,
  ArrowLeft,
  Search,
  ChevronDown,
  MapPin,
  Droplet,
  Fuel,
  Wind,
  BatteryCharging,
  AlertCircle,
  X,
  User,
} from "lucide-react";
import { LOCATIONS, CAR_DATA } from "../data/businessData";
import { useBookingContext } from "../data/useBookingContext";
import { useAuth } from "../data/useAuth";

/**
 * BookingSidebar — the GoMechanic-style sticky booking card.
 *
 * Drives `useBookingContext` directly so that any page hosting this
 * sidebar (Services, ServiceCategory, etc.) shares the same state.
 *
 * Props let the host page customise the title and the offset from the
 * top of the viewport when sticky.
 */

interface BookingSidebarProps {
  /** First-line title segment, e.g. "EXPERIENCE THE BEST" */
  titleStart?: string;
  /** Italic primary-coloured middle word, e.g. "CAR SERVICES" */
  titleAccent?: string;
  /** Trailing word, e.g. "IN" — selected location is appended after */
  titleEnd?: string;
  /** Sticky top offset in px (header + section nav). Default 132. */
  stickyTopPx?: number;
}

export default function BookingSidebar({
  titleStart = "EXPERIENCE THE BEST",
  titleAccent = "CAR SERVICES",
  titleEnd = "IN",
  stickyTopPx = 132,
}: BookingSidebarProps) {
  const { state, update } = useBookingContext();
  const { user, isAuthenticated } = useAuth();

  // ---------- Local UI state ----------
  // OTP step is local (one-shot UX flow) — verified outcome lives in context.
  const [otpStep, setOtpStep] = useState<"phone" | "otp" | "done">(() =>
    state.otpVerified ? "done" : "phone"
  );
  const [otpValue, setOtpValue] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Car selector modal
  const [showCarSelector, setShowCarSelector] = useState(false);
  const [carStep, setCarStep] = useState<1 | 2 | 3>(1);
  const [pendingCar, setPendingCar] = useState<{
    brand: string;
    model: string;
  }>({ brand: "", model: "" });
  const [carSearch, setCarSearch] = useState("");

  // ---------- Auth: auto-fill verified phone & defaults ----------
  useEffect(() => {
    if (isAuthenticated && user) {
      const patch: Parameters<typeof update>[0] = {};
      if (!state.phone) patch.phone = user.phone;
      if (!state.otpVerified) patch.otpVerified = true;
      if (!state.car && user.defaultCar) patch.car = user.defaultCar;
      if (!state.location && user.defaultLocation)
        patch.location = user.defaultLocation;
      if (Object.keys(patch).length > 0) update(patch);
      setOtpStep("done");
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated, user]);

  // First-time hydration: set a default location if none chosen yet
  useEffect(() => {
    if (!state.location && LOCATIONS[0]?.id) {
      update({ location: LOCATIONS[0].id });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ---------- Derived values ----------
  const selectedLocationName =
    LOCATIONS.find((l) => l.id === state.location)?.name || "your area";

  const selectedCarLabel = state.car
    ? `${state.car.brand} ${state.car.model}, ${state.car.fuel}`
    : "";

  const brandList = Object.keys(CAR_DATA);

  // ---------- Handlers ----------
  const onPhoneChange = (val: string) => {
    const cleaned = val.replace(/\D/g, "").slice(0, 10);
    update({ phone: cleaned });
    if (errors.phone) setErrors((e) => ({ ...e, phone: "" }));
    if (otpStep !== "phone") {
      setOtpStep("phone");
      update({ otpVerified: false });
    }
  };

  const handleSendOtp = () => {
    const errs: Record<string, string> = {};
    if (!state.location) errs.location = "Please select a location";
    if (!state.car) errs.car = "Please select your car";
    if (!state.phone) errs.phone = "Phone number is required";
    else if (!/^\d{10}$/.test(state.phone))
      errs.phone = "Enter a valid 10-digit number";
    setErrors(errs);
    if (Object.keys(errs).length > 0) return;
    setOtpStep("otp");
    setOtpValue("");
  };

  const handleVerifyOtp = () => {
    if (!/^\d{4,6}$/.test(otpValue)) {
      setErrors({ otp: "Enter the 4-6 digit OTP" });
      return;
    }
    setErrors({});
    setOtpStep("done");
    update({ otpVerified: true });
  };

  const handleCheckPrices = () => {
    if (!state.otpVerified) return;
    update({ pricesShown: true });
    // Smooth-scroll to the pricing section if any host page exposes it
    const el =
      document.getElementById("pricing") ||
      document.querySelector('[data-section="pricing"]');
    if (el) {
      const top =
        (el as HTMLElement).getBoundingClientRect().top +
        window.scrollY -
        (stickyTopPx + 16);
      window.scrollTo({ top, behavior: "smooth" });
    }
  };

  // ---------- Car selector helpers ----------
  const openCarSelector = () => {
    setShowCarSelector(true);
    setCarStep(1);
    setPendingCar({ brand: "", model: "" });
    setCarSearch("");
  };
  const closeCarSelector = () => {
    setShowCarSelector(false);
    setCarSearch("");
  };
  const selectBrand = (brand: string) => {
    setPendingCar({ brand, model: "" });
    setCarStep(2);
    setCarSearch("");
  };
  const selectModel = (model: string) => {
    setPendingCar((p) => ({ ...p, model }));
    setCarStep(3);
    setCarSearch("");
  };
  const selectFuel = (fuel: string) => {
    update({
      car: { brand: pendingCar.brand, model: pendingCar.model, fuel },
    });
    if (errors.car) setErrors((e) => ({ ...e, car: "" }));
    closeCarSelector();
  };
  const carBack = () => {
    if (carStep === 1) closeCarSelector();
    else if (carStep === 2) setCarStep(1);
    else setCarStep(2);
    setCarSearch("");
  };

  const filteredBrands = useMemo(
    () =>
      brandList.filter((b) =>
        b.toLowerCase().includes(carSearch.toLowerCase())
      ),
    [brandList, carSearch]
  );
  const modelList =
    pendingCar.brand && CAR_DATA[pendingCar.brand]
      ? CAR_DATA[pendingCar.brand]
      : [];
  const filteredModels = useMemo(
    () =>
      modelList.filter((m) =>
        m.toLowerCase().includes(carSearch.toLowerCase())
      ),
    [modelList, carSearch]
  );

  const FUELS = [
    { id: "Petrol", icon: Droplet },
    { id: "Diesel", icon: Fuel },
    { id: "CNG", icon: Wind },
    { id: "Electric", icon: BatteryCharging },
  ];

  // ---------- Styles ----------
  const inputBase =
    "w-full bg-white border border-border p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900";
  const inputError =
    "w-full bg-white border border-accent-dark p-3 text-sm focus:border-accent-dark outline-none transition-colors text-neutral-900";

  return (
    <>
      <div
        className="bg-white border border-border p-5 sm:p-6 shadow-xl lg:sticky lg:self-start"
        style={{ top: `${stickyTopPx}px` }}
      >
        <h3 className="text-lg sm:text-xl font-black uppercase tracking-tighter mb-1 text-neutral-900 leading-tight">
          {titleStart}{" "}
          <span className="text-primary italic">{titleAccent}</span> {titleEnd}{" "}
          <span className="uppercase">{selectedLocationName}</span>
        </h3>
        <p className="text-xs text-neutral-500 mb-5">
          Get instant quotes for your car service.
        </p>

        {/* Logged-in identity (replaces phone input when authed) */}
        {isAuthenticated && user && (
          <div className="mb-3 bg-primary/5 border border-primary/20 px-3 py-2.5 flex items-center gap-2">
            <User className="w-4 h-4 text-primary shrink-0" />
            <div className="min-w-0 flex-1">
              <p className="text-xs font-black uppercase text-neutral-900 tracking-tighter truncate">
                {user.name}
              </p>
              <p className="text-[10px] text-neutral-500 truncate">
                +91 {user.phone} · Verified
              </p>
            </div>
          </div>
        )}

        {/* Location */}
        <div className="mb-3">
          <div className="relative">
            <MapPin className="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            <select
              value={state.location}
              onChange={(e) => {
                update({ location: e.target.value });
                if (errors.location)
                  setErrors((er) => ({ ...er, location: "" }));
              }}
              className={`${
                errors.location ? inputError : inputBase
              } pl-9 appearance-none cursor-pointer pr-9`}
            >
              {LOCATIONS.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.name.toUpperCase()}
                </option>
              ))}
            </select>
            <ChevronDown className="w-4 h-4 text-neutral-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
          {errors.location && (
            <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" /> {errors.location}
            </p>
          )}
        </div>

        {/* Car selector */}
        <div className="mb-3">
          <button
            type="button"
            onClick={openCarSelector}
            className={`w-full text-left ${
              errors.car ? inputError : inputBase
            } flex items-center justify-between cursor-pointer ${
              state.car ? "" : "text-neutral-400"
            }`}
          >
            <span className="truncate uppercase tracking-tight">
              {selectedCarLabel || "SELECT YOUR CAR"}
            </span>
            <ChevronDown className="w-4 h-4 shrink-0 ml-2" />
          </button>
          {errors.car && (
            <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" /> {errors.car}
            </p>
          )}
        </div>

        {/* Phone — hide when logged-in user has verified phone */}
        {!isAuthenticated && (
          <div className="mb-3">
            <input
              type="tel"
              inputMode="numeric"
              maxLength={10}
              value={state.phone}
              onChange={(e) => onPhoneChange(e.target.value)}
              placeholder="ENTER MOBILE NUMBER"
              className={errors.phone ? inputError : inputBase}
            />
            {errors.phone && (
              <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                <AlertCircle className="w-3 h-3" /> {errors.phone}
              </p>
            )}
          </div>
        )}

        {/* OTP step */}
        {!isAuthenticated && otpStep === "otp" && (
          <div className="mb-3 bg-neutral-50 border border-border p-3">
            <input
              type="text"
              inputMode="numeric"
              maxLength={6}
              value={otpValue}
              onChange={(e) => {
                setOtpValue(e.target.value.replace(/\D/g, "").slice(0, 6));
                if (errors.otp) setErrors((er) => ({ ...er, otp: "" }));
              }}
              placeholder="ENTER OTP"
              className={`w-full bg-white border ${
                errors.otp ? "border-accent-dark" : "border-border"
              } p-3 text-sm text-center tracking-[0.5em] font-bold focus:border-primary outline-none mb-2`}
            />
            <p className="text-[10px] text-neutral-500 mb-2">
              OTP sent to +91 {state.phone}.{" "}
              <span className="text-primary font-bold">Demo: any 4+ digits work.</span>
            </p>
            {errors.otp && (
              <p className="text-[10px] font-bold text-accent-dark mb-2 flex items-center gap-1">
                <AlertCircle className="w-3 h-3" /> {errors.otp}
              </p>
            )}
            <button
              onClick={handleVerifyOtp}
              className="w-full bg-neutral-900 text-white py-2.5 text-[10px] font-bold uppercase tracking-widest hover:bg-primary transition-colors"
            >
              Verify OTP
            </button>
          </div>
        )}

        {/* Action button */}
        {!isAuthenticated && otpStep === "phone" && (
          <button
            onClick={handleSendOtp}
            className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 mb-3"
          >
            Send OTP <ArrowRight className="w-4 h-4 btn-arrow" />
          </button>
        )}

        {(isAuthenticated || otpStep === "done") && (
          <button
            onClick={handleCheckPrices}
            disabled={!state.otpVerified}
            className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 mb-3 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            Check Prices For Free{" "}
            <ArrowRight className="w-4 h-4 btn-arrow" />
          </button>
        )}

        {/* Trust strip */}
        <div className="pt-3 border-t border-border grid grid-cols-2 gap-3 text-center">
          <div>
            <p className="text-base font-black text-neutral-900 leading-none">
              4.8<span className="text-xs text-neutral-400">/5</span>
            </p>
            <p className="text-[9px] uppercase tracking-widest font-bold text-neutral-400 mt-1">
              2,500+ Reviews
            </p>
          </div>
          <div className="border-l border-border">
            <p className="text-base font-black text-neutral-900 leading-none">
              10K+
            </p>
            <p className="text-[9px] uppercase tracking-widest font-bold text-neutral-400 mt-1">
              Happy Customers
            </p>
          </div>
        </div>
      </div>

      {/* ──────── CAR SELECTOR MODAL ──────── */}
      <AnimatePresence>
        {showCarSelector && (
          <div className="fixed inset-0 z-[10000] flex items-center justify-center p-3 sm:p-5">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={closeCarSelector}
              className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
            />
            <motion.div
              initial={{ opacity: 0, y: 30, scale: 0.96 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 30, scale: 0.96 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
              className="relative w-full max-w-xl bg-white border border-border shadow-2xl flex flex-col h-[560px] max-h-[92vh]"
            >
              {/* Modal header */}
              <div className="px-5 py-4 border-b border-border flex items-center gap-3 shrink-0">
                <button
                  onClick={carBack}
                  className="w-8 h-8 flex items-center justify-center text-neutral-500 hover:text-primary transition-colors"
                >
                  {carStep === 1 ? (
                    <X className="w-5 h-5" />
                  ) : (
                    <ArrowLeft className="w-5 h-5" />
                  )}
                </button>
                <div className="flex-1 min-w-0">
                  <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">
                    Step {carStep} of 3
                  </p>
                  <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 truncate">
                    {carStep === 1 && "Select Manufacturer"}
                    {carStep === 2 && "Select Model"}
                    {carStep === 3 && "Select Fuel Type"}
                  </h3>
                </div>
              </div>

              {/* Search */}
              {carStep < 3 && (
                <div className="px-5 py-3 border-b border-border shrink-0">
                  <div className="relative">
                    <Search className="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                      type="text"
                      value={carSearch}
                      onChange={(e) => setCarSearch(e.target.value)}
                      placeholder={
                        carStep === 1
                          ? "Search manufacturer..."
                          : "Search model..."
                      }
                      className="w-full bg-neutral-50 border border-border pl-9 pr-3 py-2 text-sm focus:border-primary outline-none"
                    />
                  </div>
                </div>
              )}

              {/* Step 1: Brand grid */}
              {carStep === 1 && (
                <div className="flex-1 overflow-y-auto p-4">
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    {filteredBrands.map((brand) => (
                      <button
                        key={brand}
                        onClick={() => selectBrand(brand)}
                        className="bg-neutral-50 border border-border p-4 hover:border-primary hover:bg-primary/5 transition-colors flex flex-col items-center gap-2"
                      >
                        <div className="w-10 h-10 bg-white border border-border flex items-center justify-center text-lg font-black text-primary">
                          {brand.charAt(0)}
                        </div>
                        <p className="text-[11px] font-bold uppercase tracking-tighter text-neutral-900 text-center leading-tight">
                          {brand}
                        </p>
                      </button>
                    ))}
                    <button
                      onClick={() => selectBrand("Other")}
                      className="bg-white border border-dashed border-border p-4 hover:border-primary transition-colors flex flex-col items-center gap-2"
                    >
                      <div className="w-10 h-10 bg-neutral-50 flex items-center justify-center text-base font-black text-neutral-400">
                        +
                      </div>
                      <p className="text-[11px] font-bold uppercase tracking-tighter text-neutral-500 text-center leading-tight">
                        Other
                      </p>
                    </button>
                  </div>
                </div>
              )}

              {/* Step 2: Models */}
              {carStep === 2 && pendingCar.brand !== "Other" && (
                <div className="flex-1 overflow-y-auto p-4">
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    {filteredModels.map((model) => (
                      <button
                        key={model}
                        onClick={() => selectModel(model)}
                        className="bg-neutral-50 border border-border p-4 hover:border-primary hover:bg-primary/5 transition-colors text-center"
                      >
                        <p className="text-[11px] font-bold uppercase tracking-tighter text-neutral-900 leading-tight">
                          {model}
                        </p>
                      </button>
                    ))}
                    <button
                      onClick={() => selectModel("Other")}
                      className="bg-white border border-dashed border-border p-4 hover:border-primary transition-colors text-center"
                    >
                      <p className="text-[11px] font-bold uppercase tracking-tighter text-neutral-500 leading-tight">
                        Other
                      </p>
                    </button>
                  </div>
                </div>
              )}

              {/* Step 2: free-text fallback if brand was "Other" */}
              {carStep === 2 && pendingCar.brand === "Other" && (
                <div className="flex-1 overflow-y-auto p-5">
                  <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-2">
                    Enter your car model
                  </label>
                  <input
                    type="text"
                    value={carSearch}
                    onChange={(e) => setCarSearch(e.target.value)}
                    placeholder="e.g. Civic Type R"
                    className="w-full bg-white border border-border p-3 text-sm focus:border-primary outline-none mb-4"
                  />
                  <button
                    onClick={() =>
                      carSearch.trim() && selectModel(carSearch.trim())
                    }
                    disabled={!carSearch.trim()}
                    className="btn-ink btn-ink-primary w-full py-3 text-xs font-black uppercase tracking-widest disabled:opacity-50"
                  >
                    Continue
                  </button>
                </div>
              )}

              {/* Step 3: Fuel */}
              {carStep === 3 && (
                <div className="flex-1 overflow-y-auto p-4">
                  <div className="grid grid-cols-2 gap-3">
                    {FUELS.map((f) => {
                      const Icon = f.icon;
                      return (
                        <button
                          key={f.id}
                          onClick={() => selectFuel(f.id)}
                          className="bg-neutral-50 border border-border p-5 hover:border-primary hover:bg-primary/5 transition-colors flex flex-col items-center gap-2"
                        >
                          <Icon className="w-6 h-6 text-primary" />
                          <p className="text-xs font-black uppercase tracking-tighter text-neutral-900">
                            {f.id}
                          </p>
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Footer crumb */}
              <div className="px-5 py-3 border-t border-border bg-neutral-50 shrink-0 text-[10px] uppercase tracking-widest font-bold text-neutral-500 truncate">
                {pendingCar.brand && (
                  <>
                    Brand: <span className="text-primary">{pendingCar.brand}</span>
                  </>
                )}
                {pendingCar.model && (
                  <>
                    {" "}
                    · Model:{" "}
                    <span className="text-primary">{pendingCar.model}</span>
                  </>
                )}
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </>
  );
}
