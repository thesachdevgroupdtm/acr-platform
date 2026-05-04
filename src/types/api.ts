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

export interface AppliedCouponSummary {
  code: string;
  name: string;
  discount_amount: number;
}

export interface CartTotals {
  subtotal: number;
  discount: number;
  coupon: AppliedCouponSummary | null;
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

/* ───────────── Coupons (Phase 2.5b) ───────────── */

export type CouponDiscountType = "percent" | "flat";

export interface CouponResource {
  id: number;
  code: string;
  name: string;
  description: string;
  discount_type: CouponDiscountType;
  discount_value: number;
  max_discount: number | null;
  min_order_value: number;
  expiry_date: string | null;
  badge: string | null;
  /** Present only when fetched with ?context=cart. */
  eligible?: boolean;
  /** Present only when fetched with ?context=cart and !eligible. */
  ineligible_reason?: string;
}

export interface CouponsListResponse {
  coupons: CouponResource[];
}

export interface CartCouponApplyRequest {
  code: string;
}

/* ───────────── Service centers (Phase 2.5a) ───────────── */

export interface ServiceCenterResource {
  id: number;
  slug: string;
  name: string;
  address: string;
  phone: string;
  email: string | null;
  city: string;
  state: string;
  pincode: string;
  latitude: number | null;
  longitude: number | null;
}

export interface ServiceCentersResponse {
  service_centers: ServiceCenterResource[];
}

/* ───────────── Orders / Checkout (Phase 2.5a) ───────────── */

export type OrderStatus =
  | "pending"
  | "confirmed"
  | "in_service"
  | "completed"
  | "cancelled";

export type PaymentStatus = "pending" | "paid" | "failed" | "refunded";

export type PaymentMethod =
  | "cash_at_center"
  | "upi"
  | "card"
  | "wallet"
  | "other";

export type PaymentTransactionStatus =
  | "pending"
  | "succeeded"
  | "failed"
  | "refunded";

export interface PaymentTransactionResource {
  id: number;
  method: PaymentMethod;
  status: PaymentTransactionStatus;
  amount: number;
  gateway_txn_id: string | null;
  paid_at: string | null;
  refunded_at: string | null;
  refunded_amount: number | null;
  created_at: string | null;
}

export interface OrderItemVehicle {
  brand_id: number | null;
  brand_name?: string | null;
  model_id: number | null;
  model_name?: string | null;
  fuel_id: number | null;
  fuel_name?: string | null;
}

export interface OrderItemResource {
  id: number;
  service_id: number | null;
  package_id: number | null;
  product_id: number | null;
  service_title_snapshot: string;
  quantity: number;
  unit_price_snapshot: number;
  line_total_snapshot: number;
  vehicle: OrderItemVehicle | null;
  meta: Record<string, unknown> | null;
}

export interface OrderVehicleSnapshot {
  brand_id?: number | null;
  brand_name?: string | null;
  brand_slug?: string | null;
  model_id?: number | null;
  model_name?: string | null;
  model_slug?: string | null;
  fuel_id?: number | null;
  fuel_name?: string | null;
  fuel_slug?: string | null;
}

export interface OrderTimestamps {
  placed_at: string | null;
  confirmed_at: string | null;
  in_service_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  cancelled_reason: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface OrderResource {
  id: number;
  order_number: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  name_snapshot: string;
  phone_snapshot: string;
  email_snapshot: string | null;
  address: string | null;
  notes: string | null;
  vehicle_snapshot: OrderVehicleSnapshot;
  preferred_date: string | null;
  preferred_time: string;
  service_center: ServiceCenterResource | null;
  items: OrderItemResource[];
  payments: PaymentTransactionResource[];
  totals: {
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    /** Phase 2.5b — applied coupon snapshot, null when no coupon. */
    coupon?: AppliedCouponSummary | null;
  };
  timestamps: OrderTimestamps;
}

export interface OrderResponse {
  order: OrderResource;
}

export interface OrdersListResponse {
  orders: OrderResource[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface CheckoutQuoteRequest {
  preferred_date: string;
  preferred_time: string;
  service_center_id: number;
  address?: string | null;
  notes?: string | null;
  name?: string;
  phone?: string;
  email?: string | null;
  coupon_code?: string | null;
}

export interface CheckoutQuoteResponse {
  quote: {
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    gst_pct: number;
    items: Array<{
      service_id: number | null;
      title: string;
      quantity: number;
      unit_price: number;
      line_total: number;
    }>;
    breakdown_lines: Array<{ label: string; value: number }>;
  };
}

export interface PlaceOrderRequest {
  preferred_date: string;
  preferred_time: string;
  service_center_id: number;
  address?: string | null;
  notes?: string | null;
  name: string;
  phone: string;
  email?: string | null;
  coupon_code?: string | null;
}

/**
 * D-2.5a-1 — locked 6-slot list. Mirrors
 * CheckoutController::PREFERRED_TIME_OPTIONS on the backend. The
 * en-dash (U+2013) is the canonical separator on both ends.
 */
export const PREFERRED_TIME_OPTIONS: readonly string[] = [
  "09:00 AM – 11:00 AM",
  "11:00 AM – 01:00 PM",
  "01:00 PM – 03:00 PM",
  "03:00 PM – 05:00 PM",
  "05:00 PM – 07:00 PM",
  "07:00 PM – 09:00 PM",
] as const;

export const MORNING_SLOTS = [
  PREFERRED_TIME_OPTIONS[0],
  PREFERRED_TIME_OPTIONS[1],
] as const;
export const AFTERNOON_SLOTS = [
  PREFERRED_TIME_OPTIONS[2],
  PREFERRED_TIME_OPTIONS[3],
] as const;
export const EVENING_SLOTS = [
  PREFERRED_TIME_OPTIONS[4],
  PREFERRED_TIME_OPTIONS[5],
] as const;
