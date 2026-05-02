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
  /**
   * Phase 2.3.4 — caller-declared intent. Default 'lead_capture'
   * preserves Quick-Estimate / soft-merge semantics. 'signup'
   * activates strict phone uniqueness server-side: if the phone is
   * already on file, the server returns 422 instead of merging.
   */
  intent?: "signup" | "lead_capture";
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

/* ───────────── Addresses (Phase 2.2) ───────────── */

/**
 * Body for POST /user/addresses and PUT /user/addresses/{id}.
 * On POST: line1/city/state/pincode are required. On PUT all fields
 * are optional (PATCH-style); empty body returns 422.
 */
export interface AddressInput {
  label?: string;
  line1: string;
  line2?: string | null;
  city: string;
  state: string;
  /** 6 digits, regex /^\d{6}$/ on the server. */
  pincode: string;
  landmark?: string | null;
  is_default?: boolean;
}

export interface AddressesResponse {
  addresses: AddressResource[];
}

export interface AddressResponse {
  address: AddressResource;
}

/* ───────────── Cart (Phase 2.3) ───────────── */

/**
 * Per /PHASE2_CONTRACT.md §4.3 / §9. Only `service` is reachable in
 * 2.3; `package` and `product` light up in 2.6 alongside their
 * tables. The frontend should hard-route on `kind === 'service'`
 * for the foreseeable future.
 */
export type CartItemKind = "service" | "package" | "product";

export interface CartItemResource {
  id: number;
  kind: CartItemKind;
  ref_id: number;
  display_title: string;
  category_slug: string | null;
  image: string | null;
  unit_price_snapshot: number;
  quantity: number;
  line_total: number;
  vehicle: { brand_id: number | null; model_id: number | null; fuel_id: number | null } | null;
  meta: Record<string, unknown> | null;
}

export interface CartTotals {
  subtotal: number;
  discount: number;
  coupon: { code: string; type: string; value: number } | null;
  tax: number;
  total: number;
}

export interface CartResource {
  id: number;
  status: "active" | "converted" | "abandoned";
  currency: string;
  expires_at: string | null;
  item_count: number;
  items: CartItemResource[];
  totals: CartTotals;
  is_user_cart: boolean;
}

export interface CartResponse {
  cart: CartResource;
}

export interface AddCartItemRequest {
  kind: CartItemKind;
  ref_id: number;
  quantity?: number;
  vehicle?: { brand_id: number; model_id: number; fuel_id: number };
  meta?: Record<string, unknown>;
}

export interface UpdateCartItemRequest {
  quantity?: number;
  vehicle?: { brand_id: number; model_id: number; fuel_id: number };
}
