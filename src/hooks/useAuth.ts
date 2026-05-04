/**
 * useAuth — user account state for ACR (OTP-based per Phase 2.1).
 *
 * Auth flow (per /PHASE2_CONTRACT.md §5.1 + §6.5):
 *   1. signUp({name, phone, email?})  → POST /auth/lead-capture
 *      → returns { pendingUserId, otpSentTo }
 *   2. logIn(phone)                   → POST /auth/login
 *      → returns { pendingUserId, otpSentTo }
 *   3. verifyOtp({channel,destination,code}) → POST /auth/verify-otp
 *      → returns { token, user }; stores token; sets user.
 *   4. logOut()                       → POST /auth/logout (when authenticated)
 *
 * Other consumer surface (Cart/Checkout/Payment/Header/MyBookings):
 *   user, isAuthenticated, bootstrapped, logout (alias of logOut),
 *   setDefaults({car?,location?}), addAddress(...) [gated by 2.2],
 *   addBooking(...) [gated by 2.5], BookingRecord type, validateEmail,
 *   NAME_REGEX, PHONE_REGEX.
 *
 * Password-based methods removed in this commit per /PHASE2_CONTRACT.md
 * §11 Assumption 15 (OTP-only auth, no passwords ever).
 */
import { useCallback, useEffect, useState } from "react";
import {
  ApiError,
  deleteAddress as deleteAddressApi,
  fetchAddresses,
  fetchProfile,
  postAddress,
  postCartMerge,
  postLeadCapture,
  postLogin,
  postLogout,
  postVerifyOtp,
  putAddress,
  putProfile,
  getToken,
  setToken,
} from "../lib/api";
import { FEATURES } from "../config/features";
import type {
  AddressInput,
  AddressResource,
  LeadCaptureResponse,
  LoginResponse,
  OtpChannel,
  UserResource,
  VerifyOtpResponse,
} from "../types/api";

/* ───────────────── Types ───────────────── */

export interface SavedAddress {
  id: string;
  label: string;
  address: string;
  isDefault: boolean;
}

export interface BookingRecord {
  id: string;
  createdAt: string;
  items: { title: string; qty: number; price: number }[];
  subtotal: number;
  gst: number;
  total: number;
  status: "confirmed" | "completed" | "cancelled";
  serviceCenter: string;
  preferredDate: string;
  preferredTime: string;
  address: string;
  paymentMethod: string;
  notes?: string;
}

/**
 * AcrUser is the consumer-facing shape. Maintained for compatibility
 * with existing pages (Cart, Checkout, Header, MyBookings, etc.) that
 * read user.name / user.phone / user.bookings / user.addresses.
 *
 * In Phase 2.1, bookings and addresses arrive as empty arrays —
 * server endpoints land in 2.5 (orders) and 2.2 (addresses).
 */
export interface AcrUser {
  id: string;
  name: string;
  phone: string;
  email: string;
  phoneVerified: boolean;
  emailVerified: boolean;
  bookings: BookingRecord[];
  addresses: SavedAddress[];
  defaultCar?: { brand: string; model: string; fuel: string };
  defaultLocation?: string;
  createdAt: string;
  lastLoginAt: string;
}

/* ───────────── Local UX-state keys ───────────── */
const DEFAULTS_KEY = "acr_user_defaults_v1";
const EVENT = "acr-auth-updated";

/* ───────────── Validators ───────────── */

export const NAME_REGEX = /^[A-Za-z][A-Za-z\s.'-]{1,}$/;
export const PHONE_REGEX = /^\d{10}$/;
export const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const DISPOSABLE_EMAIL_DOMAINS = new Set([
  "tempmail.com", "throwaway.email", "10minutemail.com", "guerrillamail.com",
  "mailinator.com", "temp-mail.org", "fakeinbox.com", "trashmail.com",
  "yopmail.com", "getnada.com", "maildrop.cc", "mintemail.com",
  "tempinbox.com", "sharklasers.com", "spam4.me",
]);

export function validateEmail(email: string): string | null {
  if (!email) return "Email is required";
  const normalized = email.trim().toLowerCase();
  if (!EMAIL_REGEX.test(normalized)) return "Enter a valid email address";
  const domain = normalized.split("@")[1];
  if (DISPOSABLE_EMAIL_DOMAINS.has(domain))
    return "Disposable email addresses are not allowed";
  return null;
}

/* ───────────── Helpers ───────────── */

function readDefaults(): { defaultCar?: AcrUser["defaultCar"]; defaultLocation?: string } {
  try {
    if (typeof window === "undefined") return {};
    const raw = window.localStorage.getItem(DEFAULTS_KEY);
    return raw ? (JSON.parse(raw) as ReturnType<typeof readDefaults>) : {};
  } catch {
    return {};
  }
}

function writeDefaults(d: { defaultCar?: AcrUser["defaultCar"]; defaultLocation?: string }) {
  try {
    if (typeof window === "undefined") return;
    window.localStorage.setItem(DEFAULTS_KEY, JSON.stringify(d));
    window.dispatchEvent(new Event(EVENT));
  } catch { /* swallow */ }
}

/** Flattens an AddressResource into the legacy `SavedAddress` shape used
 *  by Cart/Checkout/Header. The full resource is also exposed via
 *  `useAuth().addresses` for callers that need structured access. */
function flattenAddress(a: AddressResource): SavedAddress {
  const lines = [a.line1, a.line2, a.city, a.state, a.pincode]
    .filter((s): s is string => Boolean(s && s.trim().length))
    .join(", ");
  return {
    id:        String(a.id),
    label:     a.label,
    address:   lines,
    isDefault: a.is_default,
  };
}

/** Maps the API UserResource (Phase 2.1) into AcrUser (consumer shape).
 *  When the caller has loaded a fuller address list, pass `serverList`
 *  so the consumer-facing `addresses` array reflects every row, not just
 *  default. */
function presentUser(api: UserResource, serverList?: AddressResource[]): AcrUser {
  const defaults = readDefaults();
  const addresses: SavedAddress[] = serverList
    ? serverList.map(flattenAddress)
    : api.default_address
      ? [flattenAddress(api.default_address)]
      : [];
  return {
    id:             String(api.id),
    name:           api.name,
    phone:          api.phone,
    email:          api.email ?? "",
    phoneVerified:  api.is_verified_phone,
    emailVerified:  api.is_verified_email,
    bookings:       [],                            // Phase 2.5
    addresses,
    defaultCar:     defaults.defaultCar,
    defaultLocation:defaults.defaultLocation,
    createdAt:      api.created_at,
    lastLoginAt:    api.last_login_at ?? "",
  };
}

/* ───────────── Hook ───────────── */

export interface PendingOtp {
  pendingUserId: number;
  otpSentTo: OtpChannel;
  destination: string;     // phone or email — needed for verifyOtp call
  /** Returned by the server in dev/staging only (APP_DEBUG=true). */
  devCode?: string;
}

export function useAuth() {
  const [user, setUser] = useState<AcrUser | null>(null);
  const [bootstrapped, setBootstrapped] = useState(false);

  const refreshFromServer = useCallback(async () => {
    if (!FEATURES.auth) {
      setUser(null);
      return;
    }
    if (!getToken()) {
      setUser(null);
      return;
    }
    try {
      const data = await fetchProfile();
      setUser(presentUser(data.user));
    } catch (e) {
      if (e instanceof ApiError && e.status === 401) {
        setToken(null);
        setUser(null);
      }
    }
  }, []);

  useEffect(() => {
    refreshFromServer().finally(() => setBootstrapped(true));
    const onTokenUpdate = () => { void refreshFromServer(); };
    window.addEventListener("acr-token-updated", onTokenUpdate);
    window.addEventListener(EVENT, onTokenUpdate);
    return () => {
      window.removeEventListener("acr-token-updated", onTokenUpdate);
      window.removeEventListener(EVENT, onTokenUpdate);
    };
  }, [refreshFromServer]);

  /* ── Sign up — stage one of two ──
   * Calls /auth/lead-capture; the server upserts the user and dispatches
   * an OTP. The caller MUST follow up with verifyOtp() using the same
   * phone as `destination`. */
  const signUp = useCallback(
    async (input: { name: string; phone: string; email?: string }):
      Promise<{ success: true; pending: PendingOtp } | { success: false; error: string }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Sign-up is coming soon." };
      }
      const phone = input.phone.trim();
      const name  = input.name.trim();
      const email = input.email?.trim().toLowerCase();

      if (!NAME_REGEX.test(name))     return { success: false, error: "Enter a valid name." };
      if (!PHONE_REGEX.test(phone))   return { success: false, error: "Phone must be 10 digits." };
      if (email) {
        const emailErr = validateEmail(email);
        if (emailErr) return { success: false, error: emailErr };
      }

      try {
        // Phase 2.3.4 — explicit `signup` intent so the server
        // applies strict phone-uniqueness validation. Returning
        // 422 with a clean phone-field error when the number is
        // already on file replaces the pre-2.3.4 silent merge that
        // also overwrote the existing account's name.
        const res: LeadCaptureResponse = await postLeadCapture({
          name,
          phone,
          email,
          intent: "signup",
        });
        return {
          success: true,
          pending: {
            pendingUserId: res.pending_user_id,
            otpSentTo:     res.otp_sent_to,
            destination:   phone,
            devCode:       res.dev_code,
          },
        };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    []
  );

  /* ── Log in — stage one of two ──
   * Phone-only entry; backend triggers the OTP send and returns pending state. */
  const logIn = useCallback(
    async (phone: string):
      Promise<{ success: true; pending: PendingOtp } | { success: false; error: string }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Login is coming soon." };
      }
      const trimmed = phone.trim();
      if (!PHONE_REGEX.test(trimmed)) {
        return { success: false, error: "Phone must be 10 digits." };
      }
      try {
        const res: LoginResponse = await postLogin({ phone: trimmed });
        return {
          success: true,
          pending: {
            pendingUserId: res.pending_user_id,
            otpSentTo:     res.otp_sent_to,
            destination:   trimmed,
            devCode:       res.dev_code,
          },
        };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    []
  );

  /* ── Verify OTP — stage two of two ──
   * On success: stores the Sanctum token via setToken() and sets `user`.
   * The Header / Cart / etc. immediately re-render in the authenticated state. */
  const verifyOtp = useCallback(
    async (input: { channel: OtpChannel; destination: string; code: string }):
      Promise<{ success: true; user: AcrUser } | { success: false; error: string }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Verification is coming soon." };
      }
      try {
        // Phase 2.4 — pass the current guest cart UUID through the
        // X-Cart-Session header. The server's verify-otp hook
        // merges the guest cart into the user cart server-side
        // BEFORE returning the token, so the freshly-authenticated
        // session sees its pre-login items immediately.
        const guestUuid =
          typeof window !== "undefined"
            ? window.localStorage.getItem("acr_cart_session")
            : null;

        const res: VerifyOtpResponse = await postVerifyOtp(input, guestUuid);
        setToken(res.token);
        const u = presentUser(res.user);
        setUser(u);

        // Defense in depth: if the header was stripped en route (CORS
        // quirk, edge proxy) the explicit /cart/merge call brings
        // any orphaned guest cart over. The server-side service is
        // idempotent — a second merge of the same UUID is a no-op
        // because the guest cart is already 'converted'.
        if (guestUuid) {
          postCartMerge(guestUuid).catch((err) => {
            // Non-blocking — login already succeeded.
            // eslint-disable-next-line no-console
            console.warn("[Phase 2.4] Cart merge after OTP failed", err);
          });
        }

        return { success: true, user: u };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    []
  );

  /* ── Log out ── */
  const logOut = useCallback(async () => {
    if (FEATURES.auth && getToken()) {
      try { await postLogout(); } catch { /* token might already be invalid */ }
    }
    setToken(null);
    setUser(null);
  }, []);

  /* Backwards-compat alias for existing consumers (Header, MyBookings). */
  const logout = logOut;

  /* ── Update profile (name / email — phone is server-side immutable) ── */
  const updateProfile = useCallback(
    async (updates: { name?: string; email?: string | null }):
      Promise<{ success: true } | { success: false; error: string }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Profile editing is coming soon." };
      }
      try {
        const res = await putProfile(updates);
        setUser(presentUser(res.user));
        return { success: true };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    []
  );

  /* ── UX hint defaults (stay local) ── */
  const setDefaults = useCallback(
    (defaults: { car?: AcrUser["defaultCar"]; location?: string }) => {
      const current = readDefaults();
      const next = {
        defaultCar:      defaults.car      ?? current.defaultCar,
        defaultLocation: defaults.location ?? current.defaultLocation,
      };
      writeDefaults(next);
      setUser((prev) => (prev ? { ...prev, ...next } : prev));
    },
    []
  );

  /* ── Addresses (Phase 2.2) ──
   * The consumer-facing surface mirrors the four endpoints in
   * /PHASE2_CONTRACT.md §5.2. After a successful mutation we re-pull
   * `/user/profile` so `default_address` on the cached UserResource
   * stays in sync — that's what Header/Checkout read for the default. */

  /** Lists all addresses from the server. Returns the raw API rows so
   *  callers (e.g. a future address picker) can render the full set. */
  const listAddresses = useCallback(
    async ():
      Promise<{ success: true; addresses: AddressResource[] } | { success: false; error: string }> => {
      if (!FEATURES.auth) return { success: false, error: "Auth disabled." };
      try {
        const res = await fetchAddresses();
        // Refresh the consumer-facing user object with the full list.
        const profile = await fetchProfile();
        setUser(presentUser(profile.user, res.addresses));
        return { success: true, addresses: res.addresses };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    []
  );

  /** Creates a new address. Caller passes structured fields; the server
   *  enforces the "exactly one default per user" invariant. */
  const addAddress = useCallback(
    async (input: AddressInput):
      Promise<{ success: true; address: AddressResource } | { success: false; error: string }> => {
      if (!FEATURES.auth) return { success: false, error: "Auth disabled." };
      try {
        const res = await postAddress(input);
        await listAddresses();   // refreshes both user.addresses and default_address
        return { success: true, address: res.address };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    [listAddresses]
  );

  const updateAddress = useCallback(
    async (id: number, patch: Partial<AddressInput>):
      Promise<{ success: true; address: AddressResource } | { success: false; error: string }> => {
      if (!FEATURES.auth) return { success: false, error: "Auth disabled." };
      try {
        const res = await putAddress(id, patch);
        await listAddresses();
        return { success: true, address: res.address };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    [listAddresses]
  );

  const deleteAddress = useCallback(
    async (id: number):
      Promise<{ success: true } | { success: false; error: string }> => {
      if (!FEATURES.auth) return { success: false, error: "Auth disabled." };
      try {
        await deleteAddressApi(id);
        await listAddresses();
        return { success: true };
      } catch (e) {
        return { success: false, error: extractError(e) };
      }
    },
    [listAddresses]
  );

  /* ── Booking: gated to Phase 2.5 ── */
  const addBooking = useCallback(
    async (_booking: Omit<BookingRecord, "id" | "createdAt" | "status">):
      Promise<string> => {
      if (typeof console !== "undefined") {
        console.warn(
          "[useAuth] addBooking is gated until Phase 2.5 (offlineCheckout). " +
          "Returning placeholder invoice; nothing persisted server-side."
        );
      }
      return `ACR${Date.now()}`;
    },
    []
  );

  return {
    user,
    isAuthenticated: !!user,
    bootstrapped,
    signUp,
    logIn,
    verifyOtp,
    logOut,
    logout,
    updateProfile,
    setDefaults,
    listAddresses,
    addAddress,
    updateAddress,
    deleteAddress,
    addBooking,
  };
}

/** Pulls the most useful error string out of an ApiError or fallback. */
function extractError(e: unknown): string {
  if (e instanceof ApiError) {
    const payload = e.payload as { errors?: Record<string, string[]>; message?: string } | null;
    if (payload?.errors) {
      const first = Object.values(payload.errors).flat()[0];
      if (typeof first === "string") return first;
    }
    return payload?.message ?? e.message;
  }
  return "Network error. Please try again.";
}
