import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { ArrowLeft, X } from "lucide-react";
import { useBookingContext } from "../../hooks/useBookingContext";
import type { CarBrand, CarModel, FuelType } from "../../lib/api";
import BrandGrid from "./BrandGrid";
import ModelGrid from "./ModelGrid";
import FuelGrid from "./FuelGrid";

/**
 * VehicleSelector — the ONE shared brand → model → fuel picker.
 *
 * GoMechanic-style 3-step flow, ACR theme. Renders its steps IN-PLACE
 * inside whatever container the parent mounts it in (Option X — no
 * center-screen modal, no overlay). The parent sets the box via
 * `className` (typically a fixed height); this component is a flex
 * column that fills it.
 *
 * Behaviour:
 *  - On fuel select → writes the full {brand,model,fuel}(+ids,slugs,
 *    segment) into useBookingContext, then calls onComplete. Does NOT
 *    navigate — navigation is the parent's job.
 *  - Brand change resets model+fuel; model change resets fuel (B-1/B-3).
 *  - "Can't find your car? Enter manually" free-text escape hatch.
 */
export interface VehicleSelection {
  brand: string;
  model: string;
  fuel: string;
  brand_id?: number;
  model_id?: number;
  fuel_id?: number;
  brand_slug?: string;
  model_slug?: string;
  fuel_slug?: string;
  segment?: string | null;
  /** MANUAL_ENTRY_FLOW (D-MAN-3) — how this selection was made.
   *  Omitted is treated as 'structured'. */
  entry_mode?: "structured" | "manual";
  /** Manual entry only — the 4 car text values (no phone; phone is the
   *  homepage form's own field). Used for /contact prefill + car summary. */
  manual_brand?: string;
  manual_model?: string;
  manual_fuel?: string;
  manual_year?: string;
}

interface Props {
  /** Fired after a complete selection (fuel pick or manual submit). */
  onComplete?: (selection: VehicleSelection) => void;
  /** Fired when the user backs out of step 1 (parent shows summary). */
  onClose?: () => void;
  /** Parent controls width/height/footprint. */
  className?: string;
  /** SIDEBAR_REPLICA — when false, the step-1 "X" (close) button is hidden.
   *  Used where the selector is the no-car state itself (CarSidebar), so there
   *  is nothing to close back to. Within-flow back arrows are unaffected.
   *  Defaults to true (modal/overlay usage keeps the X). */
  canClose?: boolean;
}

type Step = "brand" | "model" | "fuel" | "manual";

const nameOf = (e: { name?: string; title?: string }) =>
  e.name?.trim() || e.title?.trim() || "—";

export default function VehicleSelector({ onComplete, onClose, className = "", canClose = true }: Props) {
  const { update } = useBookingContext();
  const [step, setStep] = useState<Step>("brand");
  const [brand, setBrand] = useState<CarBrand | null>(null);
  const [model, setModel] = useState<CarModel | null>(null);
  const [manual, setManual] = useState({ brand: "", model: "", fuel: "", year: "" });

  const finish = (sel: VehicleSelection) => {
    const { entry_mode = "structured", manual_brand, manual_model, manual_fuel, manual_year, ...car } = sel;
    // B-3 — persist to booking context. entry_mode is set on EVERY car write,
    // so a fresh structured pick always resets the manual flag + clears the
    // manual_* fields (D-MAN-8, no leak into a later structured attempt).
    update({
      car: { ...car },
      entry_mode,
      manual_brand: entry_mode === "manual" ? manual_brand : undefined,
      manual_model: entry_mode === "manual" ? manual_model : undefined,
      manual_fuel: entry_mode === "manual" ? manual_fuel : undefined,
      manual_year: entry_mode === "manual" ? manual_year : undefined,
    });
    onComplete?.(sel);
  };

  const pickBrand = (b: CarBrand) => {
    setBrand(b);
    setModel(null); // reset children
    setStep("model");
  };
  const pickModel = (m: CarModel) => {
    setModel(m);
    setStep("fuel");
  };
  const pickFuel = (f: FuelType) => {
    if (!brand || !model) return;
    finish({
      brand: nameOf(brand),
      model: nameOf(model),
      fuel: nameOf(f),
      brand_id: brand.id,
      model_id: model.id,
      fuel_id: f.id,
      brand_slug: brand.slug,
      model_slug: model.slug,
      fuel_slug: f.slug,
      segment: model.segment ?? null,
      entry_mode: "structured",
    });
  };
  const submitManual = () => {
    const b = manual.brand.trim();
    const m = manual.model.trim();
    const f = manual.fuel.trim();
    const y = manual.year.trim();
    if (!b || !m) return; // brand + model required; fuel + year optional (D-MAN-1)
    // Manual entry has no structured ids → flag it 'manual'. "Continue" only
    // saves the car + returns to the form (D-MAN-2); the /contact reroute now
    // happens on the form's "Check Price", NOT here. NO phone is collected
    // here — phone is the homepage form's own 3rd field.
    finish({
      brand: b,
      model: m,
      fuel: f, // may be empty (optional)
      entry_mode: "manual",
      manual_brand: b,
      manual_model: m,
      manual_fuel: f || undefined,
      manual_year: y || undefined,
    });
  };

  const back = () => {
    if (step === "brand") onClose?.();
    else if (step === "model") {
      setStep("brand");
      setBrand(null);
      setModel(null);
    } else if (step === "fuel") {
      setStep("model");
      setModel(null);
    } else {
      setStep("brand"); // manual → list
    }
  };

  const stepNum = step === "model" ? 2 : step === "fuel" ? 3 : 1;
  const stepLabel =
    step === "manual"
      ? "Enter your car"
      : step === "brand"
      ? "Select manufacturer"
      : step === "model"
      ? "Choose model"
      : "Choose fuel type";
  const crumb =
    step === "model" && brand
      ? nameOf(brand)
      : step === "fuel" && brand && model
      ? `${nameOf(brand)} · ${nameOf(model)}`
      : "";

  return (
    <div className={`flex flex-col bg-white ${className}`}>
      {/* Header — progress + breadcrumb + back/close */}
      <div className="px-4 py-3 border-b border-border flex items-center gap-3 shrink-0">
        {(step !== "brand" || canClose) && (
          <button
            type="button"
            onClick={back}
            aria-label={step === "brand" ? "Close" : "Back"}
            className="w-8 h-8 flex items-center justify-center text-neutral-500 hover:text-primary transition-colors shrink-0"
          >
            {step === "brand" ? <X className="w-5 h-5" /> : <ArrowLeft className="w-5 h-5" />}
          </button>
        )}
        <div className="min-w-0 flex-1">
          <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
            {step === "manual" ? "Manual entry" : `${stepNum}/3`}
            {crumb && <span className="text-primary"> · {crumb}</span>}
          </p>
          <h3 className="text-base sm:text-lg font-black tracking-tight text-neutral-900 truncate">
            {stepLabel}
          </h3>
        </div>
      </div>

      {/* Body — one step at a time, fills remaining height */}
      <div className="flex-1 overflow-y-auto p-4">
        <AnimatePresence mode="wait">
          <motion.div
            key={step}
            initial={{ opacity: 0, x: 12 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -12 }}
            transition={{ duration: 0.2 }}
          >
            {step === "brand" && <BrandGrid selectedId={brand?.id} onSelect={pickBrand} />}
            {step === "model" && brand && (
              <ModelGrid brandId={brand.id} selectedId={model?.id} onSelect={pickModel} />
            )}
            {step === "fuel" && brand && model && (
              <FuelGrid modelSlug={model.slug} onSelect={pickFuel} />
            )}
            {step === "manual" && (
              <ManualForm value={manual} onChange={setManual} onSubmit={submitManual} />
            )}
          </motion.div>
        </AnimatePresence>
      </div>

      {/* Footer — manual escape hatch (ported from the old inline selectors) */}
      <div className="px-4 py-3 border-t border-border bg-neutral-50 shrink-0">
        <button
          type="button"
          onClick={() => setStep(step === "manual" ? "brand" : "manual")}
          className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
        >
          {step === "manual" ? "← Back to manufacturer list" : "Can't find your car? Enter manually"}
        </button>
      </div>
    </div>
  );
}

function ManualForm({
  value,
  onChange,
  onSubmit,
}: {
  value: { brand: string; model: string; fuel: string; year: string };
  onChange: (v: { brand: string; model: string; fuel: string; year: string }) => void;
  onSubmit: () => void;
}) {
  const input =
    "w-full bg-white border border-border p-3 text-sm text-neutral-900 focus:border-primary outline-none";
  // D-MAN-1 — car details only (NO phone; phone is the form's own field).
  // Brand + Model required; Fuel + Year optional.
  const ready = value.brand.trim() && value.model.trim();
  return (
    <div className="space-y-3">
      <p className="text-xs text-neutral-500">
        Not in the list? Enter your car details below and continue — you'll add your
        number on the form, and our team will confirm exact pricing.
      </p>
      <input
        className={input}
        placeholder="BRAND (e.g. Tesla)"
        value={value.brand}
        onChange={(e) => onChange({ ...value, brand: e.target.value })}
      />
      <input
        className={input}
        placeholder="MODEL (e.g. Cybertruck)"
        value={value.model}
        onChange={(e) => onChange({ ...value, model: e.target.value })}
      />
      <input
        className={input}
        placeholder="FUEL (optional, e.g. Electric)"
        value={value.fuel}
        onChange={(e) => onChange({ ...value, fuel: e.target.value })}
      />
      <input
        type="text"
        inputMode="numeric"
        maxLength={4}
        className={input}
        placeholder="YEAR (optional, e.g. 2024)"
        value={value.year}
        onChange={(e) => onChange({ ...value, year: e.target.value.replace(/\D/g, "").slice(0, 4) })}
      />
      <button
        type="button"
        onClick={onSubmit}
        disabled={!ready}
        className="btn-ink btn-ink-primary w-full py-3 text-xs font-black uppercase tracking-widest disabled:opacity-50"
      >
        Continue
      </button>
    </div>
  );
}
