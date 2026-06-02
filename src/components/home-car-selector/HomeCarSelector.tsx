import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { ChevronDown, MapPin, ArrowRight, AlertCircle, Star } from "lucide-react";
import { useBookingContext } from "../../hooks/useBookingContext";
import { useServiceCenters } from "../../hooks/useServiceCenters";
import { VehicleSelector } from "../vehicle-selector";

/**
 * Home car-selector shell (Component 3). Home's distinctive hero card —
 * location + SELECT YOUR CAR + mobile + CHECK PRICES FOR FREE — wrapping
 * the shared VehicleSelector IN-PLACE (Option X, no overlay).
 *
 * Redirect rule (operator): navigate to /services ONLY when the user
 * clicks CHECK PRICES with BOTH a selected car AND a valid 10-digit
 * mobile. Car-select alone does NOT redirect — it just fills the field.
 */
const inputBase =
  "w-full bg-white border border-border p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900";
const inputErr =
  "w-full bg-white border border-accent-dark p-3 text-sm focus:border-accent-dark outline-none transition-colors text-neutral-900";

export default function HomeCarSelector() {
  const navigate = useNavigate();
  const { state, update } = useBookingContext();
  const [selectorOpen, setSelectorOpen] = useState(false);
  const [phone, setPhone] = useState(state.phone || "");
  const [errors, setErrors] = useState<{ car?: string; phone?: string }>({});
  // B5-partial — service centers from the API (was static LOCATIONS).
  const { centers: serviceCenters } = useServiceCenters();

  // First-time hydration: default a location if none chosen yet. Waits
  // until the API list arrives so we don't lock in an empty default.
  useEffect(() => {
    if (!state.location && serviceCenters[0]?.slug) {
      update({ location: serviceCenters[0].slug });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [serviceCenters]);

  const car = state.car;
  // Car summary. Manual entries append the typed year (D-MAN-2), e.g.
  // "Tesla Cybertruck · Electric · 2024". Structured stays "Brand Model · Fuel".
  const carLabel = car
    ? [
        `${car.brand} ${car.model}`.trim(),
        car.fuel,
        state.entry_mode === "manual" ? state.manual_year : null,
      ]
        .filter(Boolean)
        .join(" · ")
    : "";
  const locName = serviceCenters.find((l) => l.slug === state.location)?.name || "your area";

  // D-MAN-4 — Check Price is enabled only when all three fields are filled:
  // Location + Car (structured or manual) + a valid 10-digit phone.
  const phoneValid = /^\d{10}$/.test(phone);
  const canCheck = !!state.location && !!car && phoneValid;

  const onPhoneChange = (v: string) => {
    setPhone(v.replace(/\D/g, "").slice(0, 10));
    if (errors.phone) setErrors((e) => ({ ...e, phone: "" }));
  };

  const onCheckPrices = () => {
    // Validate all three fields (button is also disabled until valid — this is
    // a guard for safety / non-JS).
    const errs: { car?: string; phone?: string } = {};
    if (!car) errs.car = "Please select your car";
    if (!phoneValid) errs.phone = "Enter a valid 10-digit mobile number";
    setErrors(errs);
    if (Object.keys(errs).length > 0) return;
    // Persist the phone (the form's 3rd field) so /contact (manual) and the
    // pricing flow can read it (D-MAN-5/6). THIS is where the manual reroute
    // happens — not on the manual "Continue".
    update({ phone });
    navigate(state.entry_mode === "manual" ? "/contact" : "/services");
  };

    // FIX2 — collapsed card sizes to its content (no large dead space
    // under the CTA/rating). Height is only reserved when the selector is
    // OPEN: the in-place VehicleSelector below carries its own h-[520px].
  return (
    <div className="bg-white border border-border p-5 sm:p-6 shadow-xl">
      {selectorOpen ? (
        // In-place selector — breaks out of the card padding to fill the
        // same footprint (B-4: card lg:min-h-[520px] == selector height).
        <div className="-mx-5 sm:-mx-6 -mt-5 sm:-mt-6 -mb-5 sm:-mb-6">
          <VehicleSelector
            className="h-[520px] max-h-[80vh]"
            onComplete={() => {
              // D-MAN-2 — both manual "Continue" and a structured pick just
              // close the picker and return to the form (the Car field then
              // shows the summary). NO navigation here; the manual → /contact
              // reroute happens on "Check Price".
              setSelectorOpen(false);
              if (errors.car) setErrors((e) => ({ ...e, car: "" }));
            }}
            onClose={() => setSelectorOpen(false)}
          />
        </div>
      ) : (
        <>
          <h3 className="text-lg sm:text-xl font-black uppercase tracking-tighter mb-1 text-neutral-900 leading-tight">
            EXPERIENCE THE BEST <span className="text-primary">CAR SERVICES</span> IN{" "}
            <span className="uppercase">{locName}</span>
          </h3>
          <p className="text-xs text-neutral-500 mb-5">
            Get instant quotes for your car service.
          </p>

          {/* Location */}
          <div className="mb-3">
            <div className="relative">
              <MapPin className="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              <select
                value={state.location}
                onChange={(e) => update({ location: e.target.value })}
                className={`${inputBase} pl-9 appearance-none cursor-pointer pr-9`}
              >
                {serviceCenters.map((l) => (
                  <option key={l.slug} value={l.slug}>
                    {l.name.toUpperCase()}
                  </option>
                ))}
              </select>
              <ChevronDown className="w-4 h-4 text-neutral-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>

          {/* Select your car */}
          <div className="mb-3">
            <button
              type="button"
              onClick={() => {
                if (errors.car) setErrors((e) => ({ ...e, car: "" }));
                setSelectorOpen(true);
              }}
              className={`${errors.car ? inputErr : inputBase} text-left flex items-center justify-between cursor-pointer ${
                car ? "" : "text-neutral-400"
              }`}
            >
              <span className="truncate uppercase tracking-tight">
                {carLabel || "SELECT YOUR CAR"}
              </span>
              <ChevronDown className="w-4 h-4 shrink-0 ml-2" />
            </button>
            {errors.car && (
              <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                <AlertCircle className="w-3 h-3" /> {errors.car}
              </p>
            )}
          </div>

          {/* Mobile */}
          <div className="mb-3">
            <input
              type="tel"
              inputMode="numeric"
              maxLength={10}
              value={phone}
              onChange={(e) => onPhoneChange(e.target.value)}
              placeholder="ENTER MOBILE NUMBER"
              className={errors.phone ? inputErr : inputBase}
            />
            {errors.phone && (
              <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                <AlertCircle className="w-3 h-3" /> {errors.phone}
              </p>
            )}
          </div>

          {/* CTA — enabled only when Location + Car + valid Phone are all set
              (D-MAN-4). Manual cars route to /contact, structured to pricing. */}
          <button
            type="button"
            onClick={onCheckPrices}
            disabled={!canCheck}
            aria-disabled={!canCheck}
            className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 mb-3 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Check Prices For Free <ArrowRight className="w-4 h-4 btn-arrow" />
          </button>

          {/* Trust strip */}
          <div className="pt-3 border-t border-border grid grid-cols-2 gap-3 text-center">
            <div>
              <p className="text-base font-black text-neutral-900 leading-none inline-flex items-center gap-1">
                <Star className="w-3.5 h-3.5 text-primary fill-current" /> 4.8
                <span className="text-xs text-neutral-400">/5</span>
              </p>
              <p className="text-[9px] uppercase tracking-widest font-bold text-neutral-400 mt-1">
                2,500+ Reviews
              </p>
            </div>
            <div className="border-l border-border">
              <p className="text-base font-black text-neutral-900 leading-none">10K+</p>
              <p className="text-[9px] uppercase tracking-widest font-bold text-neutral-400 mt-1">
                Happy Customers
              </p>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
