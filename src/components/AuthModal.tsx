import { useEffect, useMemo, useRef, useState } from "react";
import type * as React from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  X,
  Mail,
  Phone,
  User,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  ArrowLeft,
  AlertCircle,
  CheckCircle2,
  Shield,
  KeyRound,
} from "lucide-react";
import {
  useAuth,
  validateEmail,
  checkPasswordStrength,
  NAME_REGEX,
  PHONE_REGEX,
} from "../data/useAuth";

type Tab = "login" | "signup";

interface AuthModalProps {
  isOpen: boolean;
  defaultTab?: Tab;
  /** Page slug to navigate to on success. Optional. */
  redirectTo?: string;
  setCurrentPage: (page: string) => void;
  onClose: () => void;
}

// Anti-bot constants — adjustable centrally.
const MIN_DWELL_MS = 2500; // user must spend ≥ 2.5s on signup form before submit
const OTP_RESEND_COOLDOWN_S = 30;

export default function AuthModal({
  isOpen,
  defaultTab = "login",
  redirectTo,
  setCurrentPage,
  onClose,
}: AuthModalProps) {
  const auth = useAuth();
  const [tab, setTab] = useState<Tab>(defaultTab);

  // Reset tab when re-opened with a different default
  useEffect(() => {
    if (isOpen) setTab(defaultTab);
  }, [isOpen, defaultTab]);

  const handleSuccess = () => {
    onClose();
    if (redirectTo) setCurrentPage(redirectTo);
  };

  return (
    <AnimatePresence>
      {isOpen && (
        <div
          key="auth-modal"
          className="fixed inset-0 z-[10000] flex items-center justify-center p-3 sm:p-5"
        >
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm"
          />

          <motion.div
            initial={{ opacity: 0, y: 30, scale: 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 30, scale: 0.96 }}
            transition={{ duration: 0.25, ease: "easeOut" }}
            className="relative w-full max-w-md bg-white border border-border shadow-2xl flex flex-col h-[640px] max-h-[92vh]"
          >
          {/* Header tabs */}
          <div className="px-5 sm:px-7 pt-5 pb-0 flex items-center gap-3 shrink-0 border-b border-border">
            <div className="flex-1 flex">
              <button
                onClick={() => setTab("login")}
                className={`flex-1 py-3 text-xs font-black uppercase tracking-widest border-b-2 transition-colors ${
                  tab === "login"
                    ? "border-primary text-primary"
                    : "border-transparent text-neutral-400 hover:text-neutral-700"
                }`}
              >
                Login
              </button>
              <button
                onClick={() => setTab("signup")}
                className={`flex-1 py-3 text-xs font-black uppercase tracking-widest border-b-2 transition-colors ${
                  tab === "signup"
                    ? "border-primary text-primary"
                    : "border-transparent text-neutral-400 hover:text-neutral-700"
                }`}
              >
                Sign Up
              </button>
            </div>
            <button
              onClick={onClose}
              aria-label="Close"
              className="w-9 h-9 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors -mr-2 mb-1"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Content (scrolls) */}
          <div className="flex-1 overflow-y-auto">
            {tab === "login" ? (
              <LoginPanel
                onSwitchToSignup={() => setTab("signup")}
                onSuccess={handleSuccess}
                login={auth.login}
              />
            ) : (
              <SignupPanel
                onSwitchToLogin={() => setTab("login")}
                onSuccess={handleSuccess}
                signup={auth.signup}
                findExisting={auth.findExisting}
              />
            )}
          </div>

          {/* Footer trust strip */}
          <div className="bg-neutral-50 border-t border-border px-5 py-3 flex flex-wrap justify-center gap-x-6 gap-y-1.5 text-[9px] font-black uppercase tracking-widest text-neutral-400 shrink-0">
            <div className="flex items-center gap-2">
              <Shield className="w-3 h-3" /> Secure
            </div>
            <div className="flex items-center gap-2">
              <Lock className="w-3 h-3" /> Encrypted
            </div>
            <div className="flex items-center gap-2">
              <CheckCircle2 className="w-3 h-3" /> No Spam
            </div>
          </div>
        </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

// ────────────────────────────── LOGIN ──────────────────────────────

function LoginPanel({
  onSwitchToSignup,
  onSuccess,
  login,
}: {
  onSwitchToSignup: () => void;
  onSuccess: () => void;
  login: ReturnType<typeof useAuth>["login"];
}) {
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [showPw, setShowPw] = useState(false);
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    if (!identifier.trim()) {
      setError("Enter your phone number or email");
      return;
    }
    if (!password) {
      setError("Enter your password");
      return;
    }
    setSubmitting(true);
    const res = await login(identifier, password);
    setSubmitting(false);
    if (!res.success) {
      setError(res.error || "Login failed");
      return;
    }
    onSuccess();
  };

  return (
    <form onSubmit={onSubmit} noValidate className="px-5 sm:px-7 py-6">
      <h2 className="text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-1">
        Welcome <span className="text-primary">Back.</span>
      </h2>
      <p className="text-xs text-neutral-500 mb-6">
        Login to track bookings and skip filling forms each time.
      </p>

      <Field
        label="Phone or Email"
        icon={<User className="w-3 h-3" />}
        value={identifier}
        onChange={setIdentifier}
        placeholder="10-digit phone or email address"
        autoComplete="username"
      />

      <PasswordField
        label="Password"
        value={password}
        onChange={setPassword}
        show={showPw}
        toggleShow={() => setShowPw((s) => !s)}
        autoComplete="current-password"
      />

      <div className="flex items-center justify-between mb-4">
        <div className="text-[10px] text-neutral-400 uppercase tracking-widest font-bold">
          Forgot password?{" "}
          <button
            type="button"
            onClick={() =>
              alert(
                "For password recovery please contact support at +91 XXXXX XXXXX"
              )
            }
            className="text-primary hover:underline"
          >
            Contact Support
          </button>
        </div>
      </div>

      {error && (
        <div className="bg-accent-dark/5 border border-accent-dark/30 px-3 py-2 mb-4 flex items-start gap-2">
          <AlertCircle className="w-4 h-4 text-accent-dark shrink-0 mt-0.5" />
          <p className="text-xs text-accent-dark font-bold leading-relaxed">
            {error}
          </p>
        </div>
      )}

      <button
        type="submit"
        disabled={submitting}
        className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-60"
      >
        {submitting ? "Logging in..." : "Login"}{" "}
        {!submitting && <ArrowRight className="w-4 h-4 btn-arrow" />}
      </button>

      <p className="text-center text-xs text-neutral-500 mt-5">
        New to ACR?{" "}
        <button
          type="button"
          onClick={onSwitchToSignup}
          className="text-primary font-bold hover:underline"
        >
          Create an account
        </button>
      </p>
    </form>
  );
}

// ────────────────────────────── SIGNUP ──────────────────────────────

interface SignupForm {
  name: string;
  phone: string;
  email: string;
  password: string;
  confirmPassword: string;
  // Hidden field (honeypot). Real users never fill this; bots usually do.
  website: string;
}

function SignupPanel({
  onSwitchToLogin,
  onSuccess,
  signup,
  findExisting,
}: {
  onSwitchToLogin: () => void;
  onSuccess: () => void;
  signup: ReturnType<typeof useAuth>["signup"];
  findExisting: ReturnType<typeof useAuth>["findExisting"];
}) {
  const [step, setStep] = useState<1 | 2>(1);
  const [form, setForm] = useState<SignupForm>({
    name: "",
    phone: "",
    email: "",
    password: "",
    confirmPassword: "",
    website: "",
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [showPw, setShowPw] = useState(false);
  const [showConfirmPw, setShowConfirmPw] = useState(false);

  // ── Anti-bot: enforce minimum dwell time on the form ──
  const formMountedAt = useRef<number>(Date.now());
  useEffect(() => {
    formMountedAt.current = Date.now();
  }, []);

  // ── Anti-bot: simple math challenge that re-rolls each mount ──
  const mathChallenge = useMemo(() => {
    const a = Math.floor(Math.random() * 9) + 1;
    const b = Math.floor(Math.random() * 9) + 1;
    return { a, b, answer: a + b };
  }, []);
  const [mathInput, setMathInput] = useState("");

  // ── OTP state ──
  const [phoneOtp, setPhoneOtp] = useState("");
  const [emailOtp, setEmailOtp] = useState("");
  const [resendIn, setResendIn] = useState(0);
  const [submitting, setSubmitting] = useState(false);

  // Real-time inline duplicate detection (informational, doesn't block typing)
  const [duplicateHint, setDuplicateHint] = useState<string>("");
  useEffect(() => {
    if (PHONE_REGEX.test(form.phone) && validateEmail(form.email) === null) {
      const ex = findExisting(form.phone, form.email);
      if (ex.byPhone)
        setDuplicateHint(
          "This phone is already registered. Try logging in instead."
        );
      else if (ex.byEmail)
        setDuplicateHint(
          "This email is already registered. Try logging in instead."
        );
      else setDuplicateHint("");
    } else {
      setDuplicateHint("");
    }
  }, [form.phone, form.email, findExisting]);

  // OTP resend cooldown timer
  useEffect(() => {
    if (resendIn <= 0) return;
    const t = window.setTimeout(() => setResendIn((s) => s - 1), 1000);
    return () => window.clearTimeout(t);
  }, [resendIn]);

  const update = <K extends keyof SignupForm>(k: K, v: SignupForm[K]) => {
    setForm((f) => ({ ...f, [k]: v }));
    if (errors[k]) setErrors((er) => ({ ...er, [k]: "" }));
  };

  const pwStrength = checkPasswordStrength(form.password);

  // ── Step 1: validate details, send OTPs (mocked), advance ──
  const continueToOtp = (e: React.FormEvent) => {
    e.preventDefault();
    const errs: Record<string, string> = {};

    // Honeypot — invisible to humans, attractive to bots
    if (form.website.trim() !== "") {
      errs.bot = "Submission blocked";
    }

    // Dwell time — bots typically submit instantly
    if (Date.now() - formMountedAt.current < MIN_DWELL_MS) {
      errs.bot = "Please take a moment to fill out the form";
    }

    if (!form.name.trim() || !NAME_REGEX.test(form.name.trim()))
      errs.name = "Enter your full name (alphabets only)";

    if (!PHONE_REGEX.test(form.phone))
      errs.phone = "Enter a 10-digit Indian mobile number";

    const emailErr = validateEmail(form.email);
    if (emailErr) errs.email = emailErr;

    if (pwStrength.score < 2)
      errs.password = "Password is too weak. " + pwStrength.errors.join(", ");

    if (form.password !== form.confirmPassword)
      errs.confirmPassword = "Passwords don't match";

    if (mathInput.trim() !== String(mathChallenge.answer))
      errs.math = "Incorrect answer to the verification";

    // Server-style uniqueness check (mock)
    if (!errs.phone && !errs.email) {
      const ex = findExisting(form.phone, form.email);
      if (ex.byPhone) errs.phone = "This phone is already registered";
      if (ex.byEmail) errs.email = "This email is already registered";
    }

    setErrors(errs);
    if (Object.keys(errs).length > 0) return;

    // Move to OTP step. In production this is where you'd call:
    //   POST /auth/send-otp { phone, email }
    setStep(2);
    setResendIn(OTP_RESEND_COOLDOWN_S);
    setPhoneOtp("");
    setEmailOtp("");
  };

  // ── Step 2: verify OTPs, create account ──
  const finalizeSignup = async (e: React.FormEvent) => {
    e.preventDefault();
    const errs: Record<string, string> = {};
    // Mock: accept any 4–6 digit OTP. In production both digits AND code value
    // would be validated server-side against what was sent.
    if (!/^\d{4,6}$/.test(phoneOtp))
      errs.phoneOtp = "Enter the 4-6 digit OTP sent to your phone";
    if (!/^\d{4,6}$/.test(emailOtp))
      errs.emailOtp = "Enter the 4-6 digit OTP sent to your email";
    setErrors(errs);
    if (Object.keys(errs).length > 0) return;

    setSubmitting(true);
    const res = await signup({
      name: form.name,
      phone: form.phone,
      email: form.email,
      password: form.password,
    });
    setSubmitting(false);
    if (!res.success) {
      // Surface error and bounce back to step 1 if it's a uniqueness issue
      setErrors({ submit: res.error || "Could not create account" });
      return;
    }
    onSuccess();
  };

  const resendOtp = () => {
    setResendIn(OTP_RESEND_COOLDOWN_S);
    setPhoneOtp("");
    setEmailOtp("");
  };

  return (
    <div>
      {step === 1 && (
        <form onSubmit={continueToOtp} noValidate className="px-5 sm:px-7 py-6">
          <h2 className="text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-1">
            Create <span className="text-primary">Account.</span>
          </h2>
          <p className="text-xs text-neutral-500 mb-5">
            One-time setup — book faster every time after this.
          </p>

          {/* Honeypot — visually hidden but accessible label-less for bots to scrape.
              Real humans never see or fill this field. */}
          <div
            aria-hidden="true"
            style={{
              position: "absolute",
              left: "-9999px",
              top: "auto",
              width: 1,
              height: 1,
              overflow: "hidden",
            }}
          >
            <label>
              Website
              <input
                type="text"
                tabIndex={-1}
                autoComplete="off"
                value={form.website}
                onChange={(e) => update("website", e.target.value)}
              />
            </label>
          </div>

          <Field
            label="Full Name *"
            icon={<User className="w-3 h-3" />}
            value={form.name}
            onChange={(v) =>
              update("name", v.replace(/[^A-Za-z\s.'-]/g, ""))
            }
            placeholder="John Doe"
            error={errors.name}
            autoComplete="name"
          />

          <Field
            label="Phone Number *"
            icon={<Phone className="w-3 h-3" />}
            value={form.phone}
            onChange={(v) => update("phone", v.replace(/\D/g, "").slice(0, 10))}
            placeholder="10-digit mobile number"
            error={errors.phone}
            inputMode="numeric"
            maxLength={10}
            autoComplete="tel"
          />

          <Field
            label="Email *"
            icon={<Mail className="w-3 h-3" />}
            value={form.email}
            onChange={(v) => update("email", v)}
            placeholder="you@example.com"
            error={errors.email}
            type="email"
            autoComplete="email"
          />

          {duplicateHint && !errors.phone && !errors.email && (
            <button
              type="button"
              onClick={onSwitchToLogin}
              className="text-[10px] font-bold text-primary uppercase tracking-widest hover:underline -mt-2 mb-3 flex items-center gap-1"
            >
              <AlertCircle className="w-3 h-3" /> {duplicateHint}
            </button>
          )}

          <PasswordField
            label="Password *"
            value={form.password}
            onChange={(v) => update("password", v)}
            show={showPw}
            toggleShow={() => setShowPw((s) => !s)}
            error={errors.password}
            autoComplete="new-password"
          />

          {/* Password strength meter */}
          {form.password && (
            <div className="-mt-2 mb-3">
              <div className="grid grid-cols-4 gap-1 mb-1.5">
                {[1, 2, 3, 4].map((n) => (
                  <div
                    key={n}
                    className={`h-1 transition-colors ${
                      pwStrength.score >= n
                        ? n <= 2
                          ? "bg-accent-dark"
                          : "bg-primary"
                        : "bg-neutral-200"
                    }`}
                  />
                ))}
              </div>
              <div className="flex items-center justify-between gap-2">
                <span
                  className={`text-[10px] font-bold uppercase tracking-widest ${
                    pwStrength.score < 2 ? "text-accent-dark" : "text-primary"
                  }`}
                >
                  {pwStrength.label}
                </span>
                {pwStrength.errors.length > 0 && (
                  <span className="text-[9px] text-neutral-400 truncate">
                    Add: {pwStrength.errors.slice(0, 2).join(", ")}
                  </span>
                )}
              </div>
            </div>
          )}

          <PasswordField
            label="Confirm Password *"
            value={form.confirmPassword}
            onChange={(v) => update("confirmPassword", v)}
            show={showConfirmPw}
            toggleShow={() => setShowConfirmPw((s) => !s)}
            error={errors.confirmPassword}
            autoComplete="new-password"
          />

          {/* Math captcha */}
          <div className="bg-neutral-50 border border-border p-3 mb-4">
            <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5">
              Verification *
            </label>
            <div className="flex items-center gap-3">
              <span className="text-sm font-bold text-neutral-900">
                What is {mathChallenge.a} + {mathChallenge.b}?
              </span>
              <input
                type="text"
                inputMode="numeric"
                maxLength={2}
                value={mathInput}
                onChange={(e) => {
                  setMathInput(e.target.value.replace(/\D/g, "").slice(0, 2));
                  if (errors.math) setErrors((er) => ({ ...er, math: "" }));
                }}
                className={`w-16 bg-white border ${
                  errors.math ? "border-accent-dark" : "border-border"
                } px-2 py-1 text-sm text-center font-bold focus:border-primary outline-none`}
              />
            </div>
            {errors.math && (
              <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
                <AlertCircle className="w-3 h-3" /> {errors.math}
              </p>
            )}
          </div>

          {errors.bot && (
            <div className="bg-accent-dark/5 border border-accent-dark/30 px-3 py-2 mb-4 flex items-start gap-2">
              <AlertCircle className="w-4 h-4 text-accent-dark shrink-0 mt-0.5" />
              <p className="text-xs text-accent-dark font-bold">{errors.bot}</p>
            </div>
          )}

          <button
            type="submit"
            className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2"
          >
            Continue <ArrowRight className="w-4 h-4 btn-arrow" />
          </button>

          <p className="text-center text-xs text-neutral-500 mt-5">
            Already have an account?{" "}
            <button
              type="button"
              onClick={onSwitchToLogin}
              className="text-primary font-bold hover:underline"
            >
              Log in
            </button>
          </p>
        </form>
      )}

      {step === 2 && (
        <form
          onSubmit={finalizeSignup}
          noValidate
          className="px-5 sm:px-7 py-6"
        >
          <button
            type="button"
            onClick={() => setStep(1)}
            className="text-[10px] font-bold text-neutral-500 uppercase tracking-widest hover:text-primary mb-4 flex items-center gap-1"
          >
            <ArrowLeft className="w-3 h-3" /> Back
          </button>

          <h2 className="text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-1">
            Verify <span className="text-primary">OTPs.</span>
          </h2>
          <p className="text-xs text-neutral-500 mb-5">
            We've sent verification codes to your phone and email.
          </p>

          <div className="bg-primary/5 border border-primary/20 px-3 py-2 mb-4 flex items-start gap-2">
            <KeyRound className="w-4 h-4 text-primary shrink-0 mt-0.5" />
            <p className="text-[11px] text-neutral-700 leading-relaxed">
              For demo: <strong>any 4-6 digit code</strong> works. Production
              would issue real OTPs server-side.
            </p>
          </div>

          {/* Phone OTP */}
          <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5">
            <Phone className="w-3 h-3 inline mr-1" /> Phone OTP — sent to +91{" "}
            {form.phone}
          </label>
          <input
            type="text"
            inputMode="numeric"
            maxLength={6}
            value={phoneOtp}
            onChange={(e) => {
              setPhoneOtp(e.target.value.replace(/\D/g, "").slice(0, 6));
              if (errors.phoneOtp)
                setErrors((er) => ({ ...er, phoneOtp: "" }));
            }}
            placeholder="ENTER OTP"
            className={`w-full bg-white border ${
              errors.phoneOtp ? "border-accent-dark" : "border-border"
            } p-3 text-sm text-center tracking-[0.5em] font-bold focus:border-primary outline-none mb-1`}
          />
          {errors.phoneOtp && (
            <p className="text-[10px] font-bold text-accent-dark mb-3 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" /> {errors.phoneOtp}
            </p>
          )}

          {/* Email OTP */}
          <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 mt-4">
            <Mail className="w-3 h-3 inline mr-1" /> Email OTP — sent to{" "}
            {form.email}
          </label>
          <input
            type="text"
            inputMode="numeric"
            maxLength={6}
            value={emailOtp}
            onChange={(e) => {
              setEmailOtp(e.target.value.replace(/\D/g, "").slice(0, 6));
              if (errors.emailOtp)
                setErrors((er) => ({ ...er, emailOtp: "" }));
            }}
            placeholder="ENTER OTP"
            className={`w-full bg-white border ${
              errors.emailOtp ? "border-accent-dark" : "border-border"
            } p-3 text-sm text-center tracking-[0.5em] font-bold focus:border-primary outline-none mb-1`}
          />
          {errors.emailOtp && (
            <p className="text-[10px] font-bold text-accent-dark mb-3 flex items-center gap-1">
              <AlertCircle className="w-3 h-3" /> {errors.emailOtp}
            </p>
          )}

          <div className="text-[10px] text-neutral-400 uppercase tracking-widest font-bold mt-3 mb-5">
            Didn't receive?{" "}
            {resendIn > 0 ? (
              <span>Resend in {resendIn}s</span>
            ) : (
              <button
                type="button"
                onClick={resendOtp}
                className="text-primary hover:underline"
              >
                Resend OTP
              </button>
            )}
          </div>

          {errors.submit && (
            <div className="bg-accent-dark/5 border border-accent-dark/30 px-3 py-2 mb-4 flex items-start gap-2">
              <AlertCircle className="w-4 h-4 text-accent-dark shrink-0 mt-0.5" />
              <p className="text-xs text-accent-dark font-bold">
                {errors.submit}
              </p>
            </div>
          )}

          <button
            type="submit"
            disabled={submitting}
            className="btn-ink btn-ink-primary w-full py-3.5 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-60"
          >
            {submitting ? "Creating account..." : "Verify & Create Account"}{" "}
            {!submitting && <CheckCircle2 className="w-4 h-4" />}
          </button>
        </form>
      )}
    </div>
  );
}

// ──────────────────── Reusable form fields ────────────────────

function Field({
  label,
  icon,
  value,
  onChange,
  placeholder,
  error,
  type = "text",
  inputMode,
  maxLength,
  autoComplete,
}: {
  label: string;
  icon?: React.ReactNode;
  value: string;
  onChange: (v: string) => void;
  placeholder?: string;
  error?: string;
  type?: string;
  inputMode?: "numeric" | "text" | "email";
  maxLength?: number;
  autoComplete?: string;
}) {
  return (
    <div className="mb-4">
      <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 flex items-center gap-1">
        {icon}
        <span>{label}</span>
      </label>
      <input
        type={type}
        inputMode={inputMode}
        maxLength={maxLength}
        autoComplete={autoComplete}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className={`w-full bg-white border ${
          error ? "border-accent-dark" : "border-border"
        } p-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900`}
      />
      {error && (
        <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
          <AlertCircle className="w-3 h-3" /> {error}
        </p>
      )}
    </div>
  );
}

function PasswordField({
  label,
  value,
  onChange,
  show,
  toggleShow,
  error,
  autoComplete,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  show: boolean;
  toggleShow: () => void;
  error?: string;
  autoComplete?: string;
}) {
  return (
    <div className="mb-4">
      <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1.5 flex items-center gap-1">
        <Lock className="w-3 h-3" />
        <span>{label}</span>
      </label>
      <div className="relative">
        <input
          type={show ? "text" : "password"}
          autoComplete={autoComplete}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder="••••••••"
          className={`w-full bg-white border ${
            error ? "border-accent-dark" : "border-border"
          } p-3 pr-11 text-sm focus:border-primary outline-none transition-colors text-neutral-900`}
        />
        <button
          type="button"
          onClick={toggleShow}
          aria-label={show ? "Hide password" : "Show password"}
          className="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center text-neutral-500 hover:text-neutral-900"
        >
          {show ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
        </button>
      </div>
      {error && (
        <p className="text-[10px] font-bold text-accent-dark mt-1 flex items-center gap-1">
          <AlertCircle className="w-3 h-3" /> {error}
        </p>
      )}
    </div>
  );
}
