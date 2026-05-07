import { useEffect, useRef, useState } from "react";
import type { FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { useNavigate } from "react-router-dom";
import {
  X,
  Mail,
  Phone,
  User,
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
  NAME_REGEX,
  PHONE_REGEX,
} from "../hooks/useAuth";
import { FEATURES } from "../config/features";
import type { OtpChannel } from "../types/api";

type Tab = "login" | "signup";
type Stage = "form" | "otp" | "done";

interface AuthModalProps {
  isOpen: boolean;
  defaultTab?: Tab;
  /**
   * URL path to navigate to on success (e.g. "/checkout",
   * "/my-bookings"). Phase 3B — was a page-key string under the
   * legacy shim; now consumed verbatim by useNavigate.
   */
  redirectTo?: string;
  onClose: () => void;
}

/**
 * Phase 2.1 — OTP-based auth modal.
 *
 * Two stages:
 *   form → submits {name?, phone, email?} → /auth/lead-capture or /auth/login
 *   otp  → submits {channel, destination, code} → /auth/verify-otp
 *
 * Per /PHASE2_CONTRACT.md §10 Frontend re-wiring + Decision D-C.
 * In dev mode (import.meta.env.DEV), the modal surfaces the hint that
 * any 4–6 digit code is accepted, and shows the dev_code returned by
 * the server when present.
 */
export default function AuthModal({
  isOpen,
  defaultTab = "login",
  redirectTo,
  onClose,
}: AuthModalProps) {
  const navigate = useNavigate();
  const auth = useAuth();
  const [tab, setTab] = useState<Tab>(defaultTab);
  const [stage, setStage] = useState<Stage>("form");

  // Form state
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [code, setCode] = useState("");

  // Pending state from lead-capture / login
  const [pendingChannel, setPendingChannel] = useState<OtpChannel>("phone");
  const [pendingDestination, setPendingDestination] = useState("");
  const [devCode, setDevCode] = useState<string | null>(null);

  // Per-action state
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);
  const codeInputRef = useRef<HTMLInputElement>(null);

  // Reset whenever the modal is opened or the tab is switched.
  useEffect(() => {
    if (!isOpen) return;
    setTab(defaultTab);
    setStage("form");
    setName("");
    setPhone("");
    setEmail("");
    setCode("");
    setError(null);
    setInfo(null);
    setDevCode(null);
  }, [isOpen, defaultTab]);

  // Auto-focus the OTP field when entering stage 2.
  useEffect(() => {
    if (stage === "otp") {
      const t = setTimeout(() => codeInputRef.current?.focus(), 50);
      return () => clearTimeout(t);
    }
  }, [stage]);

  const closeAndMaybeRedirect = () => {
    onClose();
    if (redirectTo) navigate(redirectTo);
  };

  // ── Coming-soon panel when feature flag is off ──
  if (!FEATURES.auth) {
    return (
      <AnimatePresence>
        {isOpen && (
          <div
            key="auth-modal-disabled"
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
              className="relative w-full max-w-md bg-white border border-border shadow-2xl p-8 sm:p-12 text-center"
            >
              <button
                onClick={onClose}
                aria-label="Close"
                className="absolute top-4 right-4 p-2 text-neutral-500 hover:text-neutral-900"
              >
                <X className="w-5 h-5" />
              </button>
              <div className="mx-auto w-14 h-14 bg-primary/10 text-primary flex items-center justify-center mb-6">
                <Shield className="w-7 h-7" />
              </div>
              <h2 className="text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-3">
                Accounts <span className="text-primary">Coming Soon.</span>
              </h2>
              <p className="text-sm text-neutral-600 leading-relaxed mb-8">
                Sign-up and login are being finalised.
              </p>
              <button
                onClick={onClose}
                className="btn-ink btn-ink-primary w-full py-4 text-sm font-black uppercase tracking-widest"
              >
                Continue browsing <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    );
  }

  // ── Stage handlers ──────────────────────────────────────────────────

  const submitForm = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setInfo(null);
    setSubmitting(true);
    try {
      if (tab === "signup") {
        if (!NAME_REGEX.test(name.trim())) {
          setError("Enter a valid name (letters only).");
          return;
        }
        if (!PHONE_REGEX.test(phone)) {
          setError("Phone must be exactly 10 digits.");
          return;
        }
        if (email) {
          const emailErr = validateEmail(email);
          if (emailErr) {
            setError(emailErr);
            return;
          }
        }
        const result = await auth.signUp({
          name: name.trim(),
          phone,
          email: email.trim() || undefined,
        });
        if (!result.success) {
          setError(result.error);
          return;
        }
        setPendingChannel(result.pending.otpSentTo);
        setPendingDestination(result.pending.destination);
        setDevCode(result.pending.devCode ?? null);
        setStage("otp");
      } else {
        if (!PHONE_REGEX.test(phone)) {
          setError("Phone must be exactly 10 digits.");
          return;
        }
        const result = await auth.logIn(phone);
        if (!result.success) {
          setError(result.error);
          return;
        }
        setPendingChannel(result.pending.otpSentTo);
        setPendingDestination(result.pending.destination);
        setDevCode(result.pending.devCode ?? null);
        setStage("otp");
      }
    } finally {
      setSubmitting(false);
    }
  };

  const submitOtp = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      if (!/^\d{4,6}$/.test(code)) {
        setError("Enter the 4–6 digit code.");
        return;
      }
      const result = await auth.verifyOtp({
        channel: pendingChannel,
        destination: pendingDestination,
        code,
      });
      if (!result.success) {
        setError(result.error);
        return;
      }
      setStage("done");
      setTimeout(closeAndMaybeRedirect, 600);
    } finally {
      setSubmitting(false);
    }
  };

  const resend = async () => {
    setError(null);
    setInfo(null);
    if (tab === "signup") {
      if (!NAME_REGEX.test(name.trim()) || !PHONE_REGEX.test(phone)) {
        setError("Reset and try the form again — fields look invalid.");
        return;
      }
      const r = await auth.signUp({ name: name.trim(), phone, email: email.trim() || undefined });
      if (!r.success) { setError(r.error); return; }
      setDevCode(r.pending.devCode ?? null);
      setInfo("New code sent.");
    } else {
      const r = await auth.logIn(phone);
      if (!r.success) { setError(r.error); return; }
      setDevCode(r.pending.devCode ?? null);
      setInfo("New code sent.");
    }
  };

  const inputBase = "w-full bg-white border border-border p-3 text-sm focus:border-primary outline-none";

  // ── Modal frame ─────────────────────────────────────────────────────
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
            className="relative w-full max-w-md bg-white border border-border shadow-2xl"
          >
            {/* Close */}
            <button
              onClick={onClose}
              aria-label="Close"
              className="absolute top-3 right-3 p-2 text-neutral-500 hover:text-neutral-900 z-10"
            >
              <X className="w-5 h-5" />
            </button>

            {/* Tabs (only visible on form stage) */}
            {stage === "form" && (
              <div className="px-5 sm:px-7 pt-5 pb-0 flex items-center gap-3 border-b border-border">
                <div className="flex-1 flex">
                  <button
                    onClick={() => setTab("login")}
                    className={`flex-1 py-3 text-xs font-black uppercase tracking-widest border-b-2 transition-colors ${
                      tab === "login"
                        ? "border-primary text-primary"
                        : "border-transparent text-neutral-500 hover:text-neutral-900"
                    }`}
                  >
                    Login
                  </button>
                  <button
                    onClick={() => setTab("signup")}
                    className={`flex-1 py-3 text-xs font-black uppercase tracking-widest border-b-2 transition-colors ${
                      tab === "signup"
                        ? "border-primary text-primary"
                        : "border-transparent text-neutral-500 hover:text-neutral-900"
                    }`}
                  >
                    Sign Up
                  </button>
                </div>
              </div>
            )}

            <div className="p-6 sm:p-8">
              {stage === "form" && (
                <form onSubmit={submitForm} className="space-y-4">
                  <div>
                    <h2 className="text-xl font-black uppercase tracking-tighter text-neutral-900 mb-1">
                      {tab === "signup" ? "Create your account" : "Welcome back"}
                    </h2>
                    <p className="text-xs text-neutral-500">
                      {tab === "signup"
                        ? "We'll send a one-time code to your phone."
                        : "Enter your phone — we'll send a one-time code."}
                    </p>
                  </div>

                  {tab === "signup" && (
                    <label className="block">
                      <span className="block text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1">
                        <User className="inline w-3 h-3 mr-1 -mt-0.5" /> Full name
                      </span>
                      <input
                        type="text"
                        autoFocus
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        className={inputBase}
                        placeholder="e.g. Aman Sharma"
                      />
                    </label>
                  )}

                  <label className="block">
                    <span className="block text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1">
                      <Phone className="inline w-3 h-3 mr-1 -mt-0.5" /> Phone (10 digits)
                    </span>
                    <input
                      type="tel"
                      inputMode="numeric"
                      autoFocus={tab === "login"}
                      value={phone}
                      onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))}
                      className={inputBase}
                      placeholder="9876543210"
                    />
                  </label>

                  {tab === "signup" && (
                    <label className="block">
                      <span className="block text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1">
                        <Mail className="inline w-3 h-3 mr-1 -mt-0.5" /> Email (optional)
                      </span>
                      <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className={inputBase}
                        placeholder="you@example.com"
                      />
                    </label>
                  )}

                  {error && (
                    <div className="flex items-center gap-2 px-3 py-2 bg-accent-dark/5 border border-accent-dark/40 text-xs font-bold uppercase tracking-widest text-accent-dark">
                      <AlertCircle className="w-3.5 h-3.5" /> {error}
                    </div>
                  )}

                  <button
                    type="submit"
                    disabled={submitting}
                    className="btn-ink btn-ink-primary w-full py-4 text-sm font-black uppercase tracking-widest disabled:opacity-50"
                  >
                    {submitting ? "Sending OTP…" : tab === "signup" ? "Create Account" : "Send OTP"}
                    <ArrowRight className="w-4 h-4 btn-arrow" />
                  </button>
                </form>
              )}

              {stage === "otp" && (
                <form onSubmit={submitOtp} className="space-y-4">
                  <button
                    type="button"
                    onClick={() => { setStage("form"); setCode(""); setError(null); }}
                    className="flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-neutral-500 hover:text-primary"
                  >
                    <ArrowLeft className="w-3 h-3" /> Back
                  </button>

                  <div>
                    <h2 className="text-xl font-black uppercase tracking-tighter text-neutral-900 mb-1">
                      Enter the code
                    </h2>
                    <p className="text-xs text-neutral-500">
                      OTP sent to <strong>{pendingDestination}</strong>.
                    </p>
                    {import.meta.env.DEV && (
                      <p className="mt-2 text-[10px] uppercase tracking-widest font-bold text-primary">
                        Dev mode: any 4-digit code is accepted.
                        {devCode && (
                          <>
                            {" "}Server returned: <code className="bg-neutral-100 px-1.5 py-0.5">{devCode}</code>
                          </>
                        )}
                      </p>
                    )}
                  </div>

                  <label className="block">
                    <span className="block text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1">
                      <KeyRound className="inline w-3 h-3 mr-1 -mt-0.5" /> One-time code
                    </span>
                    <input
                      ref={codeInputRef}
                      type="text"
                      inputMode="numeric"
                      autoComplete="one-time-code"
                      value={code}
                      onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                      className={`${inputBase} font-mono tracking-[0.4em] text-lg text-center`}
                      placeholder="••••••"
                    />
                  </label>

                  {error && (
                    <div className="flex items-center gap-2 px-3 py-2 bg-accent-dark/5 border border-accent-dark/40 text-xs font-bold uppercase tracking-widest text-accent-dark">
                      <AlertCircle className="w-3.5 h-3.5" /> {error}
                    </div>
                  )}
                  {info && !error && (
                    <div className="flex items-center gap-2 px-3 py-2 bg-primary/5 border border-primary/40 text-xs font-bold uppercase tracking-widest text-primary">
                      <CheckCircle2 className="w-3.5 h-3.5" /> {info}
                    </div>
                  )}

                  <button
                    type="submit"
                    disabled={submitting}
                    className="btn-ink btn-ink-primary w-full py-4 text-sm font-black uppercase tracking-widest disabled:opacity-50"
                  >
                    {submitting ? "Verifying…" : "Verify"}
                    <ArrowRight className="w-4 h-4 btn-arrow" />
                  </button>

                  <button
                    type="button"
                    onClick={resend}
                    className="block w-full text-[10px] font-black uppercase tracking-widest text-neutral-500 hover:text-primary"
                  >
                    Resend code
                  </button>
                </form>
              )}

              {stage === "done" && (
                <div className="py-10 text-center">
                  <div className="mx-auto w-14 h-14 bg-primary/10 text-primary flex items-center justify-center mb-6">
                    <CheckCircle2 className="w-7 h-7" />
                  </div>
                  <h2 className="text-xl font-black uppercase tracking-tighter text-neutral-900 mb-2">
                    You're in.
                  </h2>
                  <p className="text-xs text-neutral-500">Redirecting…</p>
                </div>
              )}
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
