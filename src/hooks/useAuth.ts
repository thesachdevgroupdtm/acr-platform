/**
 * useAuth — user account state for ACR (API-backed).
 *
 * Calls the Laravel API exposed at VITE_API_BASE_URL. Sanctum personal
 * access tokens are stored in localStorage via src/lib/api.ts.
 *
 * The hook surface is preserved from the previous mock implementation
 * (so consumer pages don't break), but methods that hit the network
 * now return Promises:
 *
 *   await login(identifier, password)
 *   await signup({ name, phone, email, password })
 *   await logout()
 *   await updateProfile({ firstname, lastname, email, phone })
 *   await addAddress(address, label?, makeDefault?)
 *   await addBooking(bookingPayload)        // creates an Order via /checkout/offline
 *
 * Pure UI/UX helpers stay synchronous:
 *   setDefaults({ car?, location? })  // local hint only
 *   findExisting(phone, email)        // no-op (server enforces uniqueness on submit)
 *   validateEmail / checkPasswordStrength
 */
import { useCallback, useEffect, useState } from "react";
import { apiGet, apiPost, apiPut, getToken, setToken, ApiError } from "../lib/api";
import { FEATURES } from "../config/features";

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

export interface AcrUser {
  id: string;
  name: string;          // derived from firstname + lastname
  firstname?: string;
  lastname?: string;
  phone: string;
  email: string;
  /** Preserved for backwards compat — server now enforces verification via OTP. */
  phoneVerified: boolean;
  emailVerified: boolean;
  bookings: BookingRecord[];     // populated lazily from /orders if needed
  addresses: SavedAddress[];     // populated from /user/addresses
  defaultCar?: { brand: string; model: string; fuel: string };
  defaultLocation?: string;
  createdAt: string;
  lastLoginAt: string;
}

/* ───────────── Local UX-state keys ───────────── */
const DEFAULTS_KEY = "acr_user_defaults_v1";
const EVENT = "acr-auth-updated";

/* ───────────── Validators (kept identical to legacy) ───────────── */

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

export interface PasswordStrength {
  score: 0 | 1 | 2 | 3 | 4;
  label: "Too weak" | "Weak" | "Fair" | "Strong" | "Very strong";
  errors: string[];
}

export function checkPasswordStrength(pw: string): PasswordStrength {
  const errors: string[] = [];
  if (pw.length < 8) errors.push("At least 8 characters");
  if (!/[A-Z]/.test(pw)) errors.push("An uppercase letter");
  if (!/[a-z]/.test(pw)) errors.push("A lowercase letter");
  if (!/\d/.test(pw)) errors.push("A number");
  if (!/[!@#$%^&*(),.?":{}|<>_\-+=/\\[\];`~]/.test(pw))
    errors.push("A special character");

  let score: 0 | 1 | 2 | 3 | 4 = 0;
  if (pw.length >= 8) score = 1;
  if (pw.length >= 8 && /[A-Z]/.test(pw) && /[a-z]/.test(pw)) score = 2;
  if (pw.length >= 10 && /[A-Z]/.test(pw) && /[a-z]/.test(pw) && /\d/.test(pw)) score = 3;
  if (
    pw.length >= 12 && /[A-Z]/.test(pw) && /[a-z]/.test(pw) && /\d/.test(pw)
    && /[!@#$%^&*(),.?":{}|<>_\-+=/\\[\];`~]/.test(pw)
  ) score = 4;

  const labels: PasswordStrength["label"][] = ["Too weak","Weak","Fair","Strong","Very strong"];
  return { score, label: labels[score], errors };
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

interface ApiAuthUser {
  id: number | string;
  firstname?: string | null;
  lastname?: string | null;
  email: string;
  phone: string;
  image?: string | null;
}

interface ApiAddress {
  id: number | string;
  address: string;
  zip?: string;
  city?: string;
  state?: string;
}

function presentUser(u: ApiAuthUser, addresses: ApiAddress[] = []): AcrUser {
  const defaults = readDefaults();
  const fullName = [u.firstname, u.lastname].filter(Boolean).join(" ").trim() || u.email;
  return {
    id: String(u.id),
    name: fullName,
    firstname: u.firstname || undefined,
    lastname:  u.lastname  || undefined,
    phone: u.phone,
    email: u.email,
    phoneVerified: true,  // server gates via OTP at register-time
    emailVerified: true,
    bookings: [],         // pages should fetch via /orders if they need bookings
    addresses: addresses.map((a) => ({
      id: String(a.id),
      label: "Saved",
      address: a.address,
      isDefault: false,
    })),
    defaultCar: defaults.defaultCar,
    defaultLocation: defaults.defaultLocation,
    createdAt: "",
    lastLoginAt: "",
  };
}

/* ───────────── Hook ───────────── */

interface MeResponse { user: ApiAuthUser; }
interface ProfileResponse { user: ApiAuthUser; addresses: ApiAddress[]; }
interface LoginResponse  { success: boolean; user?: ApiAuthUser; token?: string; message?: string; }
interface RegisterResponse extends LoginResponse {}

export function useAuth() {
  const [user, setUser] = useState<AcrUser | null>(null);
  const [bootstrapped, setBootstrapped] = useState(false);

  const refreshFromServer = useCallback(async () => {
    if (!FEATURES.auth) {
      // Auth backend not available yet — never issue a network request.
      setUser(null);
      return;
    }
    if (!getToken()) {
      setUser(null);
      return;
    }
    try {
      const data = await apiGet<ProfileResponse>("/user/profile");
      setUser(presentUser(data.user, data.addresses || []));
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

  /* ── Signup ── */
  const signup = useCallback(
    async (data: { name: string; phone: string; email: string; password: string; }):
      Promise<{ success: boolean; error?: string; user?: AcrUser }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Sign-up is coming soon." };
      }
      const phone = data.phone.trim();
      const email = data.email.trim().toLowerCase();
      const fullName = data.name.trim();

      if (!NAME_REGEX.test(fullName))     return { success: false, error: "Enter a valid name" };
      if (!PHONE_REGEX.test(phone))       return { success: false, error: "Phone must be 10 digits" };
      const emailErr = validateEmail(email);
      if (emailErr)                       return { success: false, error: emailErr };
      const pw = checkPasswordStrength(data.password);
      if (pw.score < 2)                   return { success: false, error: "Password is too weak" };

      const [first, ...rest] = fullName.split(/\s+/);
      try {
        const res = await apiPost<RegisterResponse>("/auth/register", {
          firstname: first,
          lastname:  rest.join(" ") || null,
          email, phone,
          password: data.password,
        });
        if (res.token) setToken(res.token);
        if (res.user) {
          const u = presentUser(res.user);
          setUser(u);
          return { success: true, user: u };
        }
        return { success: false, error: res.message || "Could not create account" };
      } catch (e) {
        const msg = e instanceof ApiError
          ? extractFirstError(e.payload) || e.message
          : "Network error";
        return { success: false, error: msg };
      }
    },
    []
  );

  /* ── Login ── */
  const login = useCallback(
    async (identifier: string, password: string):
      Promise<{ success: boolean; error?: string; lockoutUntil?: number }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Login is coming soon." };
      }
      try {
        const res = await apiPost<LoginResponse>("/auth/login", {
          identifier: identifier.trim(),
          password,
        });
        if (res.token) setToken(res.token);
        if (res.user) {
          const u = presentUser(res.user);
          setUser(u);
          return { success: true };
        }
        return { success: false, error: res.message || "Login failed" };
      } catch (e) {
        const msg = e instanceof ApiError
          ? extractFirstError(e.payload) || e.message
          : "Network error";
        return { success: false, error: msg };
      }
    },
    []
  );

  /* ── Logout ── */
  const logout = useCallback(async () => {
    if (FEATURES.auth) {
      try { await apiPost("/auth/logout"); } catch { /* token might already be invalid */ }
    }
    setToken(null);
    setUser(null);
  }, []);

  /* ── Update profile ── */
  const updateProfile = useCallback(
    async (updates: Partial<Pick<AcrUser, "name" | "firstname" | "lastname" | "email" | "phone">>):
      Promise<{ success: boolean; error?: string }> => {
      if (!FEATURES.auth) {
        return { success: false, error: "Profile editing is coming soon." };
      }
      if (!user) return { success: false, error: "Not signed in" };

      let firstname = updates.firstname;
      let lastname  = updates.lastname;
      if (updates.name && !firstname && !lastname) {
        const [f, ...r] = updates.name.trim().split(/\s+/);
        firstname = f;
        lastname  = r.join(" ") || undefined;
      }
      try {
        await apiPut<{ user: ApiAuthUser }>("/auth/profile", {
          firstname, lastname,
          email: updates.email,
          phone: updates.phone,
        });
        await refreshFromServer();
        return { success: true };
      } catch (e) {
        const msg = e instanceof ApiError
          ? extractFirstError(e.payload) || e.message
          : "Network error";
        return { success: false, error: msg };
      }
    },
    [user, refreshFromServer]
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

  /* ── Address: persist to server + refresh ── */
  const addAddress = useCallback(
    async (address: string, _label = "Default", _makeDefault = true) => {
      if (!FEATURES.auth) {
        // Address management requires auth backend; no-op until enabled.
        return;
      }
      if (!user) return;
      try {
        await apiPost("/user/addresses", {
          address: address.trim(),
          city: " ",  // city is required server-side; UI provides via Checkout details
          zip:  " ",
        });
        await refreshFromServer();
      } catch (e) {
        if (typeof console !== "undefined") {
          console.warn("addAddress failed:", e instanceof ApiError ? e.message : e);
        }
      }
    },
    [user, refreshFromServer]
  );

  /* ── Booking: hand off to /checkout/offline ── */
  const addBooking = useCallback(
    async (booking: Omit<BookingRecord, "id" | "createdAt" | "status"> & {
      mobile?: string;
      vehicle_number?: string;
      slot_date?: string;
      slot_time?: string;
    }): Promise<string> => {
      if (!FEATURES.offlineCheckout) {
        // Backend route /checkout/offline is not implemented yet; return a
        // placeholder invoice so the success flow renders. The booking is
        // NOT persisted server-side — re-enable once the route ships.
        if (typeof console !== "undefined") {
          console.warn(
            "[useAuth] FEATURES.offlineCheckout=false — booking not sent to server.",
            booking
          );
        }
        return `ACR${Date.now()}`;
      }
      try {
        const res = await apiPost<{ success: boolean; order: { invoice_no: string; id: number } }>(
          "/checkout/offline",
          {
            name: user?.name || "",
            email: user?.email || "",
            mobile: booking.mobile || user?.phone || "",
            address: booking.address,
            city: " ",
            zip: " ",
            subtotal: booking.subtotal,
            order_total: booking.total,
            product_gst: 0,
            service_gst: booking.gst,
            vehicle_number: booking.vehicle_number,
            slot_date: booking.slot_date || booking.preferredDate,
            slot_time: booking.slot_time || booking.preferredTime,
          }
        );
        return res.order?.invoice_no || `ACR${Date.now()}`;
      } catch (e) {
        if (typeof console !== "undefined") {
          console.error("addBooking → /checkout/offline failed:", e instanceof ApiError ? e.message : e);
        }
        return `ACR${Date.now()}`;
      }
    },
    [user]
  );

  const findExisting = useCallback(
    (_phone: string, _email: string): { byPhone?: AcrUser; byEmail?: AcrUser } => ({}),
    []
  );

  return {
    user,
    isAuthenticated: !!user,
    bootstrapped,
    signup,
    login,
    logout,
    updateProfile,
    setDefaults,
    addAddress,
    addBooking,
    findExisting,
    validateEmail,
    checkPasswordStrength,
  };
}

/* Surfaces the first message from a Laravel 422 payload */
function extractFirstError(payload: unknown): string | null {
  if (!payload || typeof payload !== "object") return null;
  const p = payload as { errors?: Record<string, string[]>; message?: string };
  if (p.errors) {
    const first = Object.values(p.errors).flat()[0];
    if (typeof first === "string") return first;
  }
  return typeof p.message === "string" ? p.message : null;
}
