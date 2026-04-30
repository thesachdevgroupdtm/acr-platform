import { useState, useRef, Fragment } from "react";
import type * as React from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  CheckCircle2,
  ArrowRight,
  X,
  Shield,
  Clock,
  Zap,
  UploadCloud,
  Check,
  AlertCircle,
} from "lucide-react";
import { LOCATIONS, CAR_DATA } from "../data/businessData";

interface EstimateProcessProps {
  onClose: () => void;
  initialService?: string;
  isCorporate?: boolean;
}

// ---------- Validation helpers ----------
// Same rules can be reused in any other form on the site for global consistency.
const NAME_REGEX = /^[A-Za-z][A-Za-z\s.'-]*$/;
const PHONE_REGEX = /^\d{10}$/;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const validateName = (v: string) => {
  if (!v.trim()) return "Full name is required";
  if (v.trim().length < 2) return "Enter at least 2 characters";
  if (!NAME_REGEX.test(v.trim())) return "Only alphabets are allowed";
  return "";
};
const validatePhone = (v: string) => {
  if (!v) return "Phone number is required";
  if (!PHONE_REGEX.test(v)) return "Enter exactly 10 digits";
  return "";
};
const validateEmail = (v: string, required = false) => {
  if (!v) return required ? "Email is required" : "";
  if (!EMAIL_REGEX.test(v)) return "Enter a valid email address";
  return "";
};

// ---------- Step Indicator ----------
interface StepDef {
  num: number;
  label: string;
}

function StepIndicator({
  steps,
  current,
}: {
  steps: StepDef[];
  current: number;
}) {
  return (
    <div className="flex items-start justify-between gap-1 sm:gap-2 max-w-md mx-auto">
      {steps.map((s, idx) => {
        const isActive = current === s.num;
        const isComplete = current > s.num;
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
          <Fragment key={s.num}>
            <div className="flex flex-col items-center gap-2 shrink-0 w-14 sm:w-20">
              <div
                className={`w-5 h-5 flex items-center justify-center text-[11px] font-black border transition-colors ${stateColor}`}
                aria-current={isActive ? "step" : undefined}
              >
                {isComplete ? <Check className="w-4 h-4" /> : s.num}
              </div>
              <span
                className={`text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-center leading-tight ${labelColor}`}
              >
                {s.label}
              </span>
            </div>

            {idx < steps.length - 1 && (
              <div className="flex-1 h-px bg-neutral-200 mt-4 relative overflow-hidden min-w-[20px]">
                <motion.div
                  className="absolute inset-0 bg-primary origin-left"
                  initial={false}
                  animate={{ scaleX: isComplete ? 1 : 0 }}
                  transition={{ duration: 0.4, ease: "easeOut" }}
                />
              </div>
            )}
          </Fragment>
        );
      })}
    </div>
  );
}

// ---------- Reusable Field Wrapper ----------
function Field({
  label,
  required,
  error,
  children,
}: {
  label: string;
  required?: boolean;
  error?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-1.5">
      <label className="text-[9px] font-bold uppercase tracking-widest text-neutral-400 flex items-center gap-1">
        {label} {required && <span className="text-accent-dark">*</span>}
      </label>
      {children}
      {error && (
        <p className="text-[10px] font-bold text-accent-dark flex items-center gap-1 mt-1">
          <AlertCircle className="w-3 h-3 shrink-0" /> {error}
        </p>
      )}
    </div>
  );
}

// Common input styles — error variant adds accent-dark border
const inputBase =
  "w-full bg-neutral-50 border p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900";
const inputBorder = (hasError?: string) =>
  hasError ? "border-accent-dark" : "border-border";

// ---------- Shared button row classes (consistent across every step) ----------
const primaryBtn =
  "bg-primary text-white py-4 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-50 hover:bg-primary-dark transition-colors font-sans";
const secondaryBtn =
  "border border-border py-4 text-[10px] font-bold uppercase tracking-widest text-neutral-900 hover:bg-neutral-50 transition-colors font-sans";

export default function EstimateProcess({
  onClose,
  initialService = "",
  isCorporate = false,
}: EstimateProcessProps) {
  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    email: "",
    location: "",
    make: "",
    model: "",
    regNumber: "",
    year: "",
    serviceTypes: initialService ? [initialService] : ([] as string[]),
    description: "",
    companyName: "",
    contactPerson: "",
    fleetSize: "",
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [files, setFiles] = useState<File[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [bookingId, setBookingId] = useState("");

  const SERVICE_OPTIONS = [
    "Accident Repair",
    "Denting & Painting",
    "Regular Service",
    "AC Service",
    "Ceramic Coating",
    "Windshield Replacement",
    "Mechanical Work",
  ];

  // Brand list comes from the data layer (real Indian-market brands).
  const CAR_BRANDS = Object.keys(CAR_DATA);
  // Model list depends on the currently selected brand.
  const availableModels =
    formData.make && formData.make !== "Other" && CAR_DATA[formData.make]
      ? CAR_DATA[formData.make]
      : [];

  // Steps definitions for the indicator
  const personalSteps: StepDef[] = [
    { num: 1, label: "Personal" },
    { num: 2, label: "Vehicle" },
    { num: 3, label: "Service" },
    { num: 4, label: "Photos" },
  ];
  const corporateSteps: StepDef[] = [
    { num: 1, label: "Company" },
    { num: 2, label: "Fleet" },
  ];
  const stepsForIndicator = isCorporate ? corporateSteps : personalSteps;

  const totalFormSteps = isCorporate ? 2 : 4;
  const isSuccessStep =
    (!isCorporate && step === 5) || (isCorporate && step === 3);
  const showProgress = !isSuccessStep;

  // ---------- Step Validators ----------
  const validateStep1Personal = (): boolean => {
    const errs: Record<string, string> = {};
    const nameErr = validateName(formData.name);
    if (nameErr) errs.name = nameErr;
    const phoneErr = validatePhone(formData.phone);
    if (phoneErr) errs.phone = phoneErr;
    const emailErr = validateEmail(formData.email, false);
    if (emailErr) errs.email = emailErr;
    if (!formData.location) errs.location = "Please select a service center";
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const validateStep2Vehicle = (): boolean => {
    const errs: Record<string, string> = {};
    if (!formData.make) errs.make = "Please select a car brand";
    if (!formData.model) errs.model = "Please select a car model";
    if (formData.year && !/^(19|20)\d{2}$/.test(formData.year))
      errs.year = "Enter a valid 4-digit year";
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const validateStep3Service = (): boolean => {
    const errs: Record<string, string> = {};
    if (formData.serviceTypes.length === 0)
      errs.serviceTypes = "Select at least one service";
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const validateCorporateStep1 = (): boolean => {
    const errs: Record<string, string> = {};
    if (!formData.companyName.trim())
      errs.companyName = "Company name is required";
    const cpErr = validateName(formData.contactPerson);
    if (cpErr) errs.contactPerson = cpErr;
    const phoneErr = validatePhone(formData.phone);
    if (phoneErr) errs.phone = phoneErr;
    const emailErr = validateEmail(formData.email, true);
    if (emailErr) errs.email = emailErr;
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const validateCorporateStep2 = (): boolean => {
    const errs: Record<string, string> = {};
    if (!formData.fleetSize) errs.fleetSize = "Please select fleet size";
    if (!formData.description.trim())
      errs.description = "Please describe your fleet requirements";
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleNext = () => {
    let ok = false;
    if (isCorporate) {
      if (step === 1) ok = validateCorporateStep1();
    } else {
      if (step === 1) ok = validateStep1Personal();
      else if (step === 2) ok = validateStep2Vehicle();
      else if (step === 3) ok = validateStep3Service();
      else ok = true; // step 4 (photos) is optional
    }
    if (ok) {
      setErrors({});
      setStep((s) => s + 1);
    }
  };

  const handleBack = () => {
    setErrors({});
    setStep((s) => s - 1);
  };

  const handleServiceToggle = (service: string) => {
    setFormData((prev) => ({
      ...prev,
      serviceTypes: prev.serviceTypes.includes(service)
        ? prev.serviceTypes.filter((s) => s !== service)
        : [...prev.serviceTypes, service],
    }));
    if (errors.serviceTypes)
      setErrors((e) => ({ ...e, serviceTypes: "" }));
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      setFiles((prev) => [...prev, ...Array.from(e.target.files!)]);
    }
  };

  // Numeric-only handler for phone (strips non-digits, caps at 10)
  const onPhoneChange = (val: string) => {
    const digits = val.replace(/\D/g, "").slice(0, 10);
    setFormData((p) => ({ ...p, phone: digits }));
    if (errors.phone) setErrors((e) => ({ ...e, phone: "" }));
  };

  // Letters-only handler for name fields
  const onNameChange = (key: "name" | "contactPerson", val: string) => {
    const cleaned = val.replace(/[^A-Za-z\s.'-]/g, "");
    setFormData((p) => ({ ...p, [key]: cleaned }));
    if (errors[key]) setErrors((e) => ({ ...e, [key]: "" }));
  };

  const handleSubmit = () => {
    let ok = true;
    if (isCorporate && step === 2) ok = validateCorporateStep2();
    if (!ok) return;
    setIsSubmitting(true);
    setTimeout(() => {
      setBookingId(`ACR${Math.floor(10000 + Math.random() * 90000)}`);
      setIsSubmitting(false);
      setStep((s) => s + 1);
    }, 1500);
  };

  // ---------- Render Steps ----------
  // Each step uses the SAME outer pattern:
  //   <motion.div className="flex flex-col flex-1">
  //     <div className="space-y-5"> ...form content... </div>
  //     <div className="mt-auto pt-5"> ...buttons... </div>
  //   </motion.div>
  // This guarantees identical top spacing AND keeps the Back/Next button row
  // pinned to the bottom of the modal regardless of how much form content
  // a particular step contains. Modal height stays fixed across all steps.

  const renderCorporateForm = () => {
    if (step === 1) {
      return (
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -20 }}
          className="flex flex-col flex-1"
        >
          <div className="space-y-5">
            <div className="space-y-1">
              <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900">
                Corporate Details
              </h3>
              <p className="text-sm text-neutral-500">
                Provide your company information to setup a fleet account.
              </p>
            </div>

            <div className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Field label="Company Name" required error={errors.companyName}>
                  <input
                    type="text"
                    value={formData.companyName}
                    onChange={(e) => {
                      setFormData({ ...formData, companyName: e.target.value });
                      if (errors.companyName)
                        setErrors((er) => ({ ...er, companyName: "" }));
                    }}
                    className={`${inputBase} ${inputBorder(errors.companyName)}`}
                    placeholder="Acme Corp"
                  />
                </Field>
                <Field
                  label="Contact Person"
                  required
                  error={errors.contactPerson}
                >
                  <input
                    type="text"
                    value={formData.contactPerson}
                    onChange={(e) =>
                      onNameChange("contactPerson", e.target.value)
                    }
                    className={`${inputBase} ${inputBorder(
                      errors.contactPerson
                    )}`}
                    placeholder="John Doe"
                  />
                </Field>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Field label="Phone Number" required error={errors.phone}>
                  <input
                    type="tel"
                    inputMode="numeric"
                    maxLength={10}
                    value={formData.phone}
                    onChange={(e) => onPhoneChange(e.target.value)}
                    className={`${inputBase} ${inputBorder(errors.phone)}`}
                    placeholder="10-digit number"
                  />
                </Field>
                <Field label="Email Address" required error={errors.email}>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => {
                      setFormData({ ...formData, email: e.target.value });
                      if (errors.email)
                        setErrors((er) => ({ ...er, email: "" }));
                    }}
                    className={`${inputBase} ${inputBorder(errors.email)}`}
                    placeholder="john@acme.com"
                  />
                </Field>
              </div>
            </div>
          </div>

          <div className="mt-auto pt-5">
            <button onClick={handleNext} className={`w-full ${primaryBtn}`}>
              Next Step <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        </motion.div>
      );
    }

    if (step === 2) {
      return (
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -20 }}
          className="flex flex-col flex-1"
        >
          <div className="space-y-5">
            <div className="space-y-1">
              <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900">
                Fleet & Services
              </h3>
              <p className="text-sm text-neutral-500">
                Tell us about your fleet requirements.
              </p>
            </div>

            <div className="space-y-4">
              <Field label="Fleet Size" required error={errors.fleetSize}>
                <select
                  value={formData.fleetSize}
                  onChange={(e) => {
                    setFormData({ ...formData, fleetSize: e.target.value });
                    if (errors.fleetSize)
                      setErrors((er) => ({ ...er, fleetSize: "" }));
                  }}
                  className={`${inputBase} ${inputBorder(errors.fleetSize)}`}
                >
                  <option value="">Select Fleet Size</option>
                  <option value="1-5">1-5 Vehicles</option>
                  <option value="6-20">6-20 Vehicles</option>
                  <option value="21-50">21-50 Vehicles</option>
                  <option value="50+">50+ Vehicles</option>
                </select>
              </Field>

              <Field
                label="Service Requirement Summary"
                required
                error={errors.description}
              >
                <textarea
                  value={formData.description}
                  onChange={(e) => {
                    setFormData({ ...formData, description: e.target.value });
                    if (errors.description)
                      setErrors((er) => ({ ...er, description: "" }));
                  }}
                  className={`${inputBase} ${inputBorder(
                    errors.description
                  )} min-h-[100px]`}
                  placeholder="Briefly describe what your fleet needs..."
                />
              </Field>
            </div>
          </div>

          <div className="mt-auto pt-5 flex gap-3">
            <button onClick={handleBack} className={`flex-1 ${secondaryBtn}`}>
              Back
            </button>
            <button
              disabled={isSubmitting}
              onClick={handleSubmit}
              className={`flex-[2] ${primaryBtn}`}
            >
              {isSubmitting ? "Submitting..." : "Submit Inquiry"}{" "}
              <CheckCircle2 className="w-4 h-4" />
            </button>
          </div>
        </motion.div>
      );
    }
  };

  const renderStep = () => {
    if (isCorporate && step <= 2) return renderCorporateForm();

    switch (step) {
      // --------- Step 1: Personal ---------
      case 1:
        return (
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            className="flex flex-col flex-1"
          >
            <div className="space-y-5">
              <div className="space-y-1">
                <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900">
                  Personal Details
                </h3>
                <p className="text-sm text-neutral-500">
                  Provide your contact information so we can reach you.
                </p>
              </div>

              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Field label="Full Name" required error={errors.name}>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={(e) => onNameChange("name", e.target.value)}
                      className={`${inputBase} ${inputBorder(errors.name)}`}
                      placeholder="John Doe"
                    />
                  </Field>
                  <Field label="Phone Number" required error={errors.phone}>
                    <input
                      type="tel"
                      inputMode="numeric"
                      maxLength={10}
                      value={formData.phone}
                      onChange={(e) => onPhoneChange(e.target.value)}
                      className={`${inputBase} ${inputBorder(errors.phone)}`}
                      placeholder="10-digit number"
                    />
                  </Field>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Field label="Email Address" error={errors.email}>
                    <input
                      type="email"
                      value={formData.email}
                      onChange={(e) => {
                        setFormData({ ...formData, email: e.target.value });
                        if (errors.email)
                          setErrors((er) => ({ ...er, email: "" }));
                      }}
                      className={`${inputBase} ${inputBorder(errors.email)}`}
                      placeholder="john@example.com"
                    />
                  </Field>
                  <Field
                    label="Preferred Location"
                    required
                    error={errors.location}
                  >
                    <select
                      value={formData.location}
                      onChange={(e) => {
                        setFormData({ ...formData, location: e.target.value });
                        if (errors.location)
                          setErrors((er) => ({ ...er, location: "" }));
                      }}
                      className={`${inputBase} ${inputBorder(errors.location)}`}
                    >
                      <option value="">Select a Service Center</option>
                      {LOCATIONS.map((loc) => (
                        <option key={loc.id} value={loc.id}>
                          {loc.name}
                        </option>
                      ))}
                    </select>
                  </Field>
                </div>
              </div>
            </div>

            <div className="mt-auto pt-5">
              <button onClick={handleNext} className={`w-full ${primaryBtn}`}>
                Next Step <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          </motion.div>
        );

      // --------- Step 2: Vehicle (Brand → Model dependent dropdown) ---------
      case 2:
        return (
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            className="flex flex-col flex-1"
          >
            <div className="space-y-5">
              <div className="space-y-1">
                <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900">
                  Vehicle Details
                </h3>
                <p className="text-sm text-neutral-500">
                  Provide details about your car.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Field label="Car Brand" required error={errors.make}>
                  <select
                    value={formData.make}
                    onChange={(e) => {
                      // Reset model when brand changes — never leave a stale
                      // model attached to a different brand.
                      setFormData({
                        ...formData,
                        make: e.target.value,
                        model: "",
                      });
                      if (errors.make)
                        setErrors((er) => ({ ...er, make: "" }));
                    }}
                    className={`${inputBase} ${inputBorder(errors.make)}`}
                  >
                    <option value="">Select Brand</option>
                    {CAR_BRANDS.map((brand) => (
                      <option key={brand} value={brand}>
                        {brand}
                      </option>
                    ))}
                    <option value="Other">Other</option>
                  </select>
                </Field>

                <Field label="Car Model" required error={errors.model}>
                  {formData.make && formData.make !== "Other" ? (
                    <select
                      value={formData.model}
                      disabled={!formData.make}
                      onChange={(e) => {
                        setFormData({ ...formData, model: e.target.value });
                        if (errors.model)
                          setErrors((er) => ({ ...er, model: "" }));
                      }}
                      className={`${inputBase} ${inputBorder(
                        errors.model
                      )} disabled:opacity-50 disabled:cursor-not-allowed`}
                    >
                      <option value="">Select Model</option>
                      {availableModels.map((m) => (
                        <option key={m} value={m}>
                          {m}
                        </option>
                      ))}
                      <option value="Other">Other</option>
                    </select>
                  ) : (
                    <input
                      type="text"
                      value={formData.model}
                      disabled={!formData.make}
                      onChange={(e) => {
                        setFormData({ ...formData, model: e.target.value });
                        if (errors.model)
                          setErrors((er) => ({ ...er, model: "" }));
                      }}
                      className={`${inputBase} ${inputBorder(
                        errors.model
                      )} disabled:opacity-50 disabled:cursor-not-allowed`}
                      placeholder={
                        formData.make
                          ? "Enter model name"
                          : "Select brand first"
                      }
                    />
                  )}
                </Field>

                <Field label="Registration Number" error={errors.regNumber}>
                  <input
                    type="text"
                    value={formData.regNumber}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        regNumber: e.target.value.toUpperCase(),
                      })
                    }
                    className={`${inputBase} ${inputBorder(
                      errors.regNumber
                    )} uppercase`}
                    placeholder="HR 26 XX 0000"
                  />
                </Field>

                <Field label="Year of Purchase" error={errors.year}>
                  <input
                    type="text"
                    inputMode="numeric"
                    maxLength={4}
                    value={formData.year}
                    onChange={(e) => {
                      const v = e.target.value.replace(/\D/g, "").slice(0, 4);
                      setFormData({ ...formData, year: v });
                      if (errors.year)
                        setErrors((er) => ({ ...er, year: "" }));
                    }}
                    className={`${inputBase} ${inputBorder(errors.year)}`}
                    placeholder="e.g. 2022"
                  />
                </Field>
              </div>
            </div>

            <div className="mt-auto pt-5 flex gap-3">
              <button onClick={handleBack} className={`flex-1 ${secondaryBtn}`}>
                Back
              </button>
              <button onClick={handleNext} className={`flex-[2] ${primaryBtn}`}>
                Next Step <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          </motion.div>
        );

      // --------- Step 3: Services (compact checkbox list) ---------
      case 3:
        return (
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            className="flex flex-col flex-1"
          >
            <div className="space-y-5">
              <div className="space-y-1">
                <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900">
                  Service Required
                </h3>
                <p className="text-sm text-neutral-500">
                  Select one or more services needed.
                </p>
              </div>

              {/* Compact checkbox grid — 2 columns, equal spacing */}
              <div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-2">
                  {SERVICE_OPTIONS.map((item) => {
                    const isSelected = formData.serviceTypes.includes(item);
                    return (
                      <button
                        key={item}
                        type="button"
                        onClick={() => handleServiceToggle(item)}
                        className={`flex items-center gap-1 text-[12px] font-semibold text-left transition-colors ${isSelected
                          ? "border-primary bg-primary/5 text-primary"
                          : "border-border hover:border-primary/40 text-neutral-700 bg-white"
                          }`}
                      >
                        <span
                          className={`w-4 h-4 shrink-0 border flex items-center justify-center transition-colors ${isSelected
                            ? "bg-primary border-primary"
                            : "bg-white border-neutral-400"
                            }`}
                        >
                          {isSelected && (
                            <Check
                              className="w-3 h-3 text-white"
                              strokeWidth={3}
                            />
                          )}
                        </span>
                        <span className="flex-1 truncate">{item}</span>
                      </button>
                    );
                  })}
                </div>

                {errors.serviceTypes && (
                  <p className="text-[10px] font-bold text-accent-dark flex items-center gap-1 mt-2">
                    <AlertCircle className="w-3 h-3" /> {errors.serviceTypes}
                  </p>
                )}
              </div>

              <Field
                label="Damage / Issue Description (Optional)"
                error={errors.description}
              >
                <textarea
                  value={formData.description}
                  onChange={(e) =>
                    setFormData({ ...formData, description: e.target.value })
                  }
                  className={`${inputBase} ${inputBorder(
                    errors.description
                  )} min-h-[56px]`}
                  placeholder="Describe the problem..."
                />
              </Field>
            </div>

            <div className="mt-auto pt-5 flex gap-3">
              <button onClick={handleBack} className={`flex-1 ${secondaryBtn}`}>
                Back
              </button>
              <button onClick={handleNext} className={`flex-[2] ${primaryBtn}`}>
                Next Step <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          </motion.div>
        );

      // --------- Step 4: Photos ---------
      case 4:
        return (
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            className="flex flex-col flex-1"
          >
            <div className="space-y-5">
              <div className="space-y-1">
                <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900">
                  Media Upload
                </h3>
                <p className="text-sm text-neutral-500">
                  Upload photos for a precise estimate (Optional).
                </p>
              </div>

              <div
                onClick={() => fileInputRef.current?.click()}
                className="border-2 border-dashed border-border p-6 text-center space-y-2.5 hover:border-primary transition-colors cursor-pointer group bg-neutral-50"
              >
                <div className="w-12 h-12 bg-white flex items-center justify-center mx-auto group-hover:scale-110 transition-transform border border-border shadow-sm">
                  <UploadCloud className="w-6 h-6 text-neutral-400 group-hover:text-primary" />
                </div>
                <div>
                  <p className="font-bold uppercase tracking-tighter text-sm text-neutral-900">
                    Drag & Drop or Click
                  </p>
                  <p className="text-[9px] text-neutral-400 mt-1 uppercase tracking-widest">
                    Supports PNG, JPG, JPEG (Max 10MB)
                  </p>
                </div>
                <input
                  type="file"
                  multiple
                  accept="image/*"
                  ref={fileInputRef}
                  onChange={handleFileChange}
                  className="hidden"
                />
              </div>

              {files.length > 0 && (
                <div className="flex gap-2 overflow-x-auto pb-2">
                  {files.map((f, i) => (
                    <div
                      key={i}
                      className="shrink-0 relative h-12 border border-border overflow-hidden bg-neutral-100 flex items-center justify-center px-4 max-w-[120px]"
                    >
                      <span className="text-[9px] font-bold truncate text-neutral-500">
                        {f.name}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="mt-auto pt-5 flex gap-3">
              <button
                disabled={isSubmitting}
                onClick={handleBack}
                className={`flex-1 ${secondaryBtn}`}
              >
                Back
              </button>
              <button
                disabled={isSubmitting}
                onClick={handleSubmit}
                className={`flex-[2] ${primaryBtn}`}
              >
                {isSubmitting ? "Submitting..." : "Submit Inquiry"}{" "}
                <CheckCircle2 className="w-4 h-4" />
              </button>
            </div>
          </motion.div>
        );

      // --------- Success Screen ---------
      // Both step==5 (personal flow) and step==3 (corporate flow) land here
      case 5:
      case 3:
        return renderSuccess();
    }
  };

  // ---------- Success Popup ----------
  // Clean rectangular layout with strict vertical hierarchy:
  //   icon → title → greeting → details box → booking-id box → button
  // Each block has explicit spacing — no flowing sentences that wrap awkwardly.
  const renderSuccess = () => {
    const displayName = isCorporate ? formData.contactPerson : formData.name;
    const carDisplay =
      [formData.make, formData.model].filter(Boolean).join(" ") ||
      "Your vehicle";
    const serviceDisplay = isCorporate
      ? "Fleet Services"
      : formData.serviceTypes.length > 0
        ? formData.serviceTypes.join(", ")
        : "The requested service";

    return (
      <motion.div
        key="success"
        initial={{ opacity: 0, scale: 0.96 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.35, ease: "easeOut" }}
        className="flex flex-col items-center justify-center flex-1 w-full max-w-md mx-auto py-2"
      >
        {/* Icon */}
        <motion.div
          initial={{ scale: 0, rotate: -180 }}
          animate={{ scale: 1, rotate: 0 }}
          transition={{
            delay: 0.1,
            type: "spring",
            stiffness: 200,
            damping: 15,
          }}
          className="w-15 h-15 bg-primary flex items-center justify-center shadow-lg mb-6 shrink-0"
        >
          <CheckCircle2 className="w-10 h-10 text-white" />
        </motion.div>

        {/* Title */}
        <motion.h3
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.25 }}
          className="text-2xl sm:text-3xl font-black uppercase tracking-tighter text-neutral-900 text-center mb-3"
        >
          Booking Confirmed
        </motion.h3>

        {/* Greeting */}
        <motion.p
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.32 }}
          className="text-sm text-neutral-600 text-center mb-5"
        >
          Thank you{" "}
          <span className="font-bold text-neutral-900">{displayName}</span>!
        </motion.p>

        {/* Details box — labelled rows, no awkward wrapping */}
        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="w-full bg-neutral-50 border border-border divide-y divide-border mb-4"
        >
          <div className="px-4 py-3 flex flex-col gap-1">
            <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
              Your Request For
            </span>
            <p className="text-sm font-semibold text-neutral-900 leading-snug break-words">
              {serviceDisplay}
            </p>
          </div>
          {!isCorporate && (
            <div className="px-4 py-3 flex flex-col gap-1">
              <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                For Vehicle
              </span>
              <p className="text-sm font-semibold text-neutral-900 break-words">
                {carDisplay}
              </p>
            </div>
          )}
        </motion.div>

        {/* Booking ID box */}
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

        {/* Action button */}
        <motion.button
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.62 }}
          onClick={onClose}
          className={`w-full ${primaryBtn}`}
        >
          Back to Website
        </motion.button>
      </motion.div>
    );
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-5">
      {/* Backdrop */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
      />

      {/*
        Modal — FIXED, COMPACT HEIGHT.
        h-[560px] guarantees identical dimensions across every step (Step 1
        through Step 4 / corporate Step 1-2 / Success) and stays small enough
        that the modal never crops on common laptop viewports. max-h-[88vh]
        keeps it bounded on shorter screens. max-w-xl narrows the shell so it
        feels like a "regular" form card rather than a full sheet.
        Internal layout uses flex column so the header (progress) and footer
        (trust badges) are pinned (shrink-0) while only the middle content
        area scrolls if a particular step's content overflows.
      */}
      <motion.div
        initial={{ opacity: 0, y: 30, scale: 0.98 }}
        animate={{ opacity: 1, y: 0, scale: 1 }}
        exit={{ opacity: 0, y: 30, scale: 0.98 }}
        transition={{ duration: 0.25, ease: "easeOut" }}
        className="relative w-full max-w-xl bg-white border border-border shadow-2xl flex flex-col h-[516px]"
      >
        {/* Close icon — fixed top-right with proper inset */}
        <button
          onClick={onClose}
          aria-label="Close"
          className="absolute top-4 right-4 w-9 h-9 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors z-30"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Progress / Step Indicator — pinned, never touches edge */}
        {showProgress && (
          <div className="pt-5 pb-3 px-5 sm:px-8 border-b border-neutral-100 shrink-0">
            <div className="flex items-center justify-between mb-3 pr-10">
              <span className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                Step {step} of {totalFormSteps}
              </span>
              <span className="text-[9px] font-bold uppercase tracking-widest text-primary">
                {stepsForIndicator[step - 1]?.label}
              </span>
            </div>
            <StepIndicator steps={stepsForIndicator} current={step} />
          </div>
        )}

        {/* Content area — only this scrolls.
            min-h-full + flex-col on the inner wrapper lets each step's
            motion.div fill remaining height, which keeps the button row
            pinned at the bottom and gives the success popup a centered
            anchor inside the fixed-height shell. */}
        <div className="flex-1 overflow-y-auto">
          <div className="px-5 sm:px-8 py-3 min-h-full flex flex-col">
            <AnimatePresence mode="wait">
              <motion.div key={step} className="flex flex-col flex-1 min-h-0">
                {renderStep()}
              </motion.div>
            </AnimatePresence>
          </div>
        </div>

        {/* Trust footer — pinned bottom, hidden on success */}
        {showProgress && (
          <div className="bg-neutral-50 border-t border-border px-5 py-3 flex flex-wrap justify-center gap-x-6 gap-y-1.5 text-[9px] font-black uppercase tracking-widest text-muted shrink-0">
            <div className="flex items-center gap-2">
              <Shield className="w-3 h-3 text-neutral-400" /> Secure Data
            </div>
            <div className="flex items-center gap-2">
              <Clock className="w-3 h-3 text-neutral-400" /> 15 Min Response
            </div>
            <div className="flex items-center gap-2">
              <Zap className="w-3 h-3 text-neutral-400" /> Expert Review
            </div>
          </div>
        )}
      </motion.div>
    </div>
  );
}
