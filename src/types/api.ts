/**
 * Phase 2 — typed shapes for the new API surface.
 *
 * Mirrors the resources defined in /PHASE2_CONTRACT.md §4 / §9.
 * Domain-grouped; only auth/user types are populated in Phase 2.1.
 * Cart, Order, Coupon, ServiceCenter, etc. land in 2.3 onwards.
 */

/* ───────────── Auth + User ───────────── */

/**
 * Address shape — placeholder in 2.1; fully populated by Phase 2.2.
 * UserResource.default_address references this so the type can be
 * imported without forward-decl gymnastics.
 */
export interface AddressResource {
  id: number;
  label: string;
  line1: string;
  line2: string | null;
  city: string;
  state: string;
  pincode: string;
  landmark: string | null;
  is_default: boolean;
}

export interface UserResource {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  is_verified_phone: boolean;
  is_verified_email: boolean;
  role: "customer" | "admin";
  default_address: AddressResource | null;
  created_at: string;
  last_login_at: string | null;
}

/* ───────────── Auth requests + responses ───────────── */

export type OtpChannel = "phone" | "email";

export interface LeadCaptureRequest {
  name: string;
  phone: string;
  email?: string;
}

export interface LeadCaptureResponse {
  success: true;
  pending_user_id: number;
  otp_sent_to: OtpChannel;
  /** APP_DEBUG-only; in dev mode the plaintext OTP comes back so smoke tests can chain. */
  dev_code?: string;
}

export interface SendOtpRequest {
  channel: OtpChannel;
  destination: string;
}

export interface SendOtpResponse {
  success: true;
  expires_at: string;
  dev_code?: string;
}

export interface LoginRequest {
  phone: string;
}

/** Login returns the same shape as lead-capture — both stage an OTP. */
export type LoginResponse = LeadCaptureResponse;

export interface VerifyOtpRequest {
  channel: OtpChannel;
  destination: string;
  code: string;
}

export interface VerifyOtpResponse {
  success: true;
  token: string;
  user: UserResource;
}

export interface ProfileResponse {
  user: UserResource;
}

export interface UpdateProfileRequest {
  name?: string;
  email?: string | null;
}
