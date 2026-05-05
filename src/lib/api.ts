/**
 * Single canonical API client for the React frontend.
 *
 * Reads VITE_API_BASE_URL from env (e.g. http://127.0.0.1:8000/api/v1).
 * Attaches a bearer token from localStorage when present. Throws ApiError
 * on non-2xx with the parsed payload available for error UIs.
 *
 * Caching, dedup and stale-while-revalidate live one layer up — at React
 * Query (see src/hooks/* and src/main.tsx). This file does NOT cache.
 */

const TOKEN_KEY = "acr_api_token_v1";
const RAW_BASE = (import.meta.env.VITE_API_BASE_URL ?? "") as string;

/**
 * Resolves the API base URL with the following precedence:
 *
 *  1. If VITE_API_BASE_URL is set AND its hostname is non-local (e.g.
 *     production https://api.example.com), honor it verbatim.
 *  2. If VITE_API_BASE_URL is set AND its hostname is local (localhost,
 *     127.0.0.1, or a private LAN IP), REWRITE its hostname to match
 *     the current page's hostname. This means: open the React app via
 *     localhost / 127.0.0.1 / 192.168.x.x — the API call follows.
 *  3. If VITE_API_BASE_URL is empty, derive base from the current page:
 *     ${protocol}//${hostname}:8000/api/v1
 *
 * Rationale: Vite's dev server binds to 0.0.0.0 (see vite.config.ts),
 * so the same React app is reachable on multiple hostnames depending
 * on how the developer types the URL. A statically-pinned API hostname
 * breaks every variant except the one it matches.
 */
const LOCAL_RE =
  /^(localhost|127\.0\.0\.1|::1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+)$/i;

function resolveBaseUrl(raw: string): string {
  const trimmed = raw.replace(/\/+$/, "");

  if (typeof window === "undefined") {
    return trimmed; // SSR / Node — env is authoritative.
  }

  const pageHost = window.location.hostname;
  const pageProto = window.location.protocol;

  if (!trimmed) {
    return `${pageProto}//${pageHost}:8000/api/v1`;
  }

  try {
    const u = new URL(trimmed);
    const envIsLocal  = LOCAL_RE.test(u.hostname);
    const pageIsLocal = LOCAL_RE.test(pageHost);
    if (envIsLocal && pageIsLocal && u.hostname !== pageHost) {
      u.hostname = pageHost;
      return u.toString().replace(/\/+$/, "");
    }
  } catch {
    /* malformed env — fall through to using it raw */
  }

  return trimmed;
}

export const API_BASE_URL = resolveBaseUrl(RAW_BASE);

if (import.meta.env.DEV && typeof console !== "undefined") {
  // Demo-readiness — only print the resolved API target in dev.
  // Production builds keep the console clean.
  // eslint-disable-next-line no-console
  console.log(`[api] base = ${API_BASE_URL}`);
}

export class ApiError extends Error {
  status: number;
  payload: unknown;
  constructor(status: number, message: string, payload?: unknown) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

/* ───────────── Token helpers ───────────── */

export function getToken(): string | null {
  try {
    return typeof window === "undefined"
      ? null
      : window.localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

export function setToken(token: string | null) {
  try {
    if (typeof window === "undefined") return;
    if (token) window.localStorage.setItem(TOKEN_KEY, token);
    else window.localStorage.removeItem(TOKEN_KEY);
    window.dispatchEvent(new Event("acr-token-updated"));
  } catch {
    /* swallow */
  }
}

/* ───────────── Low-level request ───────────── */

type Method = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
type Query = Record<string, string | number | boolean | undefined | null>;

interface ApiOptions {
  method?: Method;
  body?: unknown;
  query?: Query;
  signal?: AbortSignal;
  /** When true, do NOT clear the auth token on 401. Lets pages handle it. */
  allowUnauthorized?: boolean;
  /** Pass a FormData/File body — skips JSON serialization + Content-Type. */
  multipart?: boolean;
  /** Per-request headers (e.g. X-Cart-Session for guest carts). */
  headers?: Record<string, string>;
}

function buildUrl(path: string, query?: Query): string {
  const cleanPath = path.startsWith("/") ? path : `/${path}`;
  const url = new URL(
    path.startsWith("http") ? path : `${API_BASE_URL}${cleanPath}`,
    typeof window !== "undefined" ? window.location.origin : "http://localhost"
  );
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      if (v === undefined || v === null || v === "") continue;
      url.searchParams.set(k, String(v));
    }
  }
  return url.toString();
}

export async function api<T = unknown>(
  path: string,
  opts: ApiOptions = {}
): Promise<T> {
  const { method = "GET", body, query, signal, allowUnauthorized, multipart, headers: extra } = opts;

  const headers: Record<string, string> = { Accept: "application/json" };
  if (body !== undefined && !multipart) headers["Content-Type"] = "application/json";

  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  if (extra) {
    for (const [k, v] of Object.entries(extra)) {
      if (v !== undefined && v !== null) headers[k] = v;
    }
  }

  if (!API_BASE_URL && !path.startsWith("http")) {
    throw new ApiError(
      0,
      "VITE_API_BASE_URL is not set. Create .env.local with VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1 and restart `npm run dev`."
    );
  }

  const fullUrl = buildUrl(path, query);

  const res = await fetch(fullUrl, {
    method,
    headers,
    body: body === undefined ? undefined : multipart ? (body as BodyInit) : JSON.stringify(body),
    signal,
    credentials: "omit",
  });

  let payload: unknown = null;
  const ct = res.headers.get("content-type") || "";
  if (ct.includes("application/json")) {
    try {
      payload = await res.json();
    } catch {
      payload = null;
    }
  } else {
    try {
      payload = await res.text();
    } catch {
      payload = null;
    }
  }

  if (!res.ok) {
    if (res.status === 401 && !allowUnauthorized) {
      // Token expired or invalid — wipe so the next render shows logged-out state
      setToken(null);
      // Phase 2.6a — broadcast a session-expired event so a single
      // app-level toast can render once, instead of every page that
      // catches 401 having to surface its own banner. The Toast
      // component (mounted in App.tsx) listens for this on window.
      if (typeof window !== "undefined") {
        window.dispatchEvent(new CustomEvent("acr-session-expired"));
      }
    }
    const message =
      (payload && typeof payload === "object" && "message" in payload
        ? String((payload as { message: unknown }).message)
        : null) || `Request failed with ${res.status}`;
    throw new ApiError(res.status, message, payload);
  }

  return payload as T;
}

/* ───────────── Convenience methods ───────────── */

export const apiGet = <T = unknown>(path: string, query?: Query, signal?: AbortSignal) =>
  api<T>(path, { method: "GET", query, signal });

export const apiPost = <T = unknown>(path: string, body?: unknown, signal?: AbortSignal) =>
  api<T>(path, { method: "POST", body, signal });

export const apiPut = <T = unknown>(path: string, body?: unknown, signal?: AbortSignal) =>
  api<T>(path, { method: "PUT", body, signal });

export const apiDelete = <T = unknown>(path: string, body?: unknown, signal?: AbortSignal) =>
  api<T>(path, { method: "DELETE", body, signal });

export const apiUpload = <T = unknown>(path: string, formData: FormData, signal?: AbortSignal) =>
  api<T>(path, { method: "POST", body: formData, multipart: true, signal });

/* ───────────── Typed response shapes ───────────── */

export interface CarBrand {
  id: number;
  slug: string;
  title: string;
  name?: string;
  image?: string | null;
}
export interface CarModel {
  id: number;
  brand_id?: number;
  slug: string;
  title: string;
  name?: string;
  image?: string | null;
}
export interface FuelType {
  id: number;
  slug: string;
  title: string;
  name?: string;
}

export interface ServiceCategory {
  id: number;
  slug: string;
  title: string;
  description?: string | null;
  image?: string | null;
  image_1?: string | null;
  icon_image?: string | null;
  /**
   * Nested sub-services — present on /home and /services list responses
   * (Phase 1.6). Absent on per-slug detail responses where the top-level
   * `services` array carries the full ServiceResource shape instead.
   */
  services?: CategorySubService[];
}

/**
 * Lean sub-service shape returned nested under categories on /home and
 * /services. Mirrors backend SubServiceResource. Use the full SubService
 * type for the per-slug detail response (which carries vehicle-resolved
 * price, warrenty/recommended/notes, etc.).
 */
export interface CategorySubService {
  id: number;
  slug: string;
  name: string;
  title: string;
  base_price: number | string | null;
  /** Phase 2.6a — non-null only when the request carried brand_id /
   * model_id / fuel_id AND service_prices had a row. */
  vehicle_price?: number | string | null;
  /** Phase 2.6a — `vehicle_price ?? base_price`; the value to display. */
  effective_price?: number | string | null;
  image: string | null;
  time_takes: string | number | null;
  time_unit: string | null;
}

export interface SubService {
  id: number;
  sc_id: number;
  category_id?: number;
  slug: string;
  title: string;
  image?: string | null;
  description?: string | null;
  warrenty_info?: string | null;
  recommended_info?: string | null;
  note?: string | null;
  time_takes?: string | number | null;
  time_takes_option?: string | null;
  time_unit?: string | null;
  price?: number | string | null;
  base_price?: number | string | null;
  /** Phase 2.6a — null when no vehicle context was passed. */
  vehicle_price?: number | string | null;
  /** Phase 2.6a — `vehicle_price ?? base_price`. */
  effective_price?: number | string | null;
  category_detail?: ServiceCategory;
}

export interface SeoPayload {
  title?: string | null;
  description?: string | null;
  keywords?: string | null;
  canonical?: string | null;
  og?: {
    title?: string | null;
    description?: string | null;
    type?: string | null;
    url?: string | null;
    image?: string | null;
    site_name?: string | null;
  };
  twitter?: {
    card?: string | null;
    title?: string | null;
    description?: string | null;
    image?: string | null;
  };
}

export interface ServiceCenter {
  id: number;
  name?: string;
  address?: string;
  image?: string;
  phone_number?: string;
}

export interface HomeResponse {
  success: boolean;
  service_categories: ServiceCategory[];
  car_brands: CarBrand[];
  car_models: CarModel[];
  service_centers: ServiceCenter[];
  offer_slider?: unknown[];
  tabular_offers?: unknown[];
  service_packages?: unknown[];
  featured_products?: unknown[];
  faqs?: unknown[];
  brand_logo_slider?: unknown[];
  membership_package?: unknown[];
  home_page_setting?: Record<string, unknown> | null;
  settings?: Record<string, unknown>;
  seo?: SeoPayload;
}

export interface BrandsResponse  { success: boolean; brands: CarBrand[] }
export interface ModelsResponse  { success: boolean; models: CarModel[] }
export interface FuelsResponse   { success: boolean; fuels: FuelType[] }

export interface ServicesResponse {
  success: boolean;
  categories: ServiceCategory[];
  available_category_ids?: number[];
  brand?: CarBrand | null;
  model?: CarModel | null;
  fuel?: FuelType | null;
  seo?: SeoPayload;
}

export interface CategoryDetailResponse {
  success: boolean;
  category: ServiceCategory;
  services: SubService[];
  price_show?: number | boolean;
  price_list?: string | null;
  brand?: CarBrand | null;
  model?: CarModel | null;
  fuel?: FuelType | null;
  faqs?: Array<{ id: number; question?: string; answer?: string }>;
  faq_contents?: string | null;
  seo?: SeoPayload;
}

export interface ServiceDetailResponse {
  success: boolean;
  service: SubService;
  category: ServiceCategory;
  related?: SubService[];
  price_show?: number | boolean;
  vehicle_price?: number | null;
  vehicle_package_id?: number | null;
  brand?: CarBrand | null;
  model?: CarModel | null;
  fuel?: FuelType | null;
  seo?: SeoPayload;
}

export interface PricingRequest {
  brand_id: number;
  model_id: number;
  fuel_type_id: number;
  service_id?: number;
  service_ids?: number[];
}
export interface PricingResponse {
  success: boolean;
  brand_id: number;
  model_id: number;
  fuel_type_id: number;
  requested_ids: number[];
  matched_prices: Array<{ service_id: number; price: number }>;
  total: number;
}

export interface PageSection {
  id: number;
  page_id: number;
  type: string;
  content: Record<string, unknown> | null;
  position: number;
}
export interface PageResponse {
  success: boolean;
  page: {
    id: number;
    slug: string;
    title: string;
    seo_title?: string | null;
    seo_description?: string | null;
    seo_keywords?: string | null;
    sections: PageSection[];
  };
  seo?: SeoPayload;
}

/* ───────────── Endpoint helpers ─────────────
 * These are thin wrappers around the typed endpoints. Callers should
 * import them from the domain hooks (src/hooks/*) rather than calling
 * directly — the hooks layer adds React Query caching.
 */

export const fetchHome = (signal?: AbortSignal) =>
  apiGet<HomeResponse>("/home", undefined, signal);

export const fetchServices = (
  q?: { brand_id?: number | null; model_id?: number | null; fuel_id?: number | null },
  signal?: AbortSignal
) => apiGet<ServicesResponse>("/services", q ?? undefined, signal);

export const fetchCategoryDetail = (
  slug: string,
  q?: { brand?: string | null; model?: string | null; fuel?: string | null },
  signal?: AbortSignal
) => apiGet<CategoryDetailResponse>(`/services/${slug}`, q ?? undefined, signal);

export const fetchServiceDetail = (
  categorySlug: string,
  serviceSlug: string,
  q?: { brand_id?: number | null; model_id?: number | null; fuel_id?: number | null },
  signal?: AbortSignal
) =>
  apiGet<ServiceDetailResponse>(
    `/services/${categorySlug}/${serviceSlug}`,
    q ?? undefined,
    signal
  );

export const fetchBrands = (signal?: AbortSignal) =>
  apiGet<BrandsResponse>("/vehicle/brands", undefined, signal);

export const fetchModels = (brandId: number | string, signal?: AbortSignal) =>
  apiGet<ModelsResponse>("/vehicle/models", { brand_id: brandId }, signal);

export const fetchFuels = (
  brandId: number | string | null = null,
  modelId: number | string | null = null,
  signal?: AbortSignal
) =>
  apiGet<FuelsResponse>(
    "/vehicle/fuels",
    { brand_id: brandId, model_id: modelId },
    signal
  );

export const postPricing = (req: PricingRequest, signal?: AbortSignal) =>
  apiPost<PricingResponse>("/pricing", req, signal);

export const fetchPage = (slug: string, signal?: AbortSignal) =>
  apiGet<PageResponse>(`/pages/${slug}`, undefined, signal);

/* ───────────── Phase 2.1 — Auth + User ─────────────
 * The 7 endpoints from /PHASE2_CONTRACT.md §5.1. Typed via the
 * interfaces in src/types/api.ts (kept separate so domain types
 * don't bloat this module).
 */
import type {
  AddCartItemRequest,
  AddressInput,
  AddressResponse,
  AddressesResponse,
  CartResponse,
  CheckoutQuoteRequest,
  CheckoutQuoteResponse,
  CouponsListResponse,
  LeadCaptureRequest,
  LeadCaptureResponse,
  LoginRequest,
  LoginResponse,
  OrderResponse,
  OrdersListResponse,
  PlaceOrderRequest,
  ProfileResponse,
  SendOtpRequest,
  SendOtpResponse,
  ServiceCentersResponse,
  UpdateCartItemRequest,
  UpdateProfileRequest,
  VerifyOtpRequest,
  VerifyOtpResponse,
} from "../types/api";

export const postLeadCapture = (req: LeadCaptureRequest, signal?: AbortSignal) =>
  apiPost<LeadCaptureResponse>("/auth/lead-capture", req, signal);

export const postSendOtp = (req: SendOtpRequest, signal?: AbortSignal) =>
  apiPost<SendOtpResponse>("/auth/send-otp", req, signal);

/**
 * Phase 2.4 — verify-otp may carry an X-Cart-Session header so the
 * server's cart-merge hook fires server-side before the token is
 * issued. Caller passes the current guest UUID (from
 * localStorage); a missing UUID means no-merge attempt. The
 * useAuth hook already does a defensive postCartMerge after this
 * call resolves, so even if the header is dropped (proxy strip,
 * CORS quirk) the cart still merges via the explicit endpoint.
 */
export const postVerifyOtp = (
  req: VerifyOtpRequest,
  guestSessionUuid?: string | null,
  signal?: AbortSignal,
) =>
  api<VerifyOtpResponse>("/auth/verify-otp", {
    method: "POST",
    body: req,
    signal,
    headers: guestSessionUuid ? { "X-Cart-Session": guestSessionUuid } : undefined,
  });

export const postLogin = (req: LoginRequest, signal?: AbortSignal) =>
  apiPost<LoginResponse>("/auth/login", req, signal);

export const postLogout = (signal?: AbortSignal) =>
  apiPost<{ success: true }>("/auth/logout", undefined, signal);

export const fetchProfile = (signal?: AbortSignal) =>
  apiGet<ProfileResponse>("/user/profile", undefined, signal);

export const putProfile = (req: UpdateProfileRequest, signal?: AbortSignal) =>
  apiPut<ProfileResponse>("/user/profile", req, signal);

/* ───────────── Phase 2.2 — Addresses ───────────── */

export const fetchAddresses = (signal?: AbortSignal) =>
  apiGet<AddressesResponse>("/user/addresses", undefined, signal);

export const postAddress = (input: AddressInput, signal?: AbortSignal) =>
  apiPost<AddressResponse>("/user/addresses", input, signal);

export const putAddress = (
  id: number,
  input: Partial<AddressInput>,
  signal?: AbortSignal
) => apiPut<AddressResponse>(`/user/addresses/${id}`, input, signal);

export const deleteAddress = (id: number, signal?: AbortSignal) =>
  apiDelete<{ success: true }>(`/user/addresses/${id}`, undefined, signal);

/* ───────────── Phase 2.3 — Cart ─────────────
 * Cart endpoints accept either a Bearer token (sanctum) or an
 * X-Cart-Session UUID header (guest). When a Bearer token is
 * present the api() helper auto-attaches it; for guests we pass
 * the session UUID through `headers`. Callers (useCart) decide
 * which path applies on every request.
 */

function cartHeaders(sessionUuid?: string | null): Record<string, string> | undefined {
  if (!sessionUuid) return undefined;
  return { "X-Cart-Session": sessionUuid };
}

export const fetchCart = (sessionUuid?: string | null, signal?: AbortSignal) =>
  api<CartResponse>("/cart", { method: "GET", signal, headers: cartHeaders(sessionUuid) });

export const postCartItem = (
  req: AddCartItemRequest,
  sessionUuid?: string | null,
  signal?: AbortSignal,
) =>
  api<CartResponse>("/cart/items", { method: "POST", body: req, signal, headers: cartHeaders(sessionUuid) });

export const putCartItem = (
  id: number,
  req: UpdateCartItemRequest,
  sessionUuid?: string | null,
  signal?: AbortSignal,
) =>
  api<CartResponse>(`/cart/items/${id}`, { method: "PUT", body: req, signal, headers: cartHeaders(sessionUuid) });

export const deleteCartItem = (
  id: number,
  sessionUuid?: string | null,
  signal?: AbortSignal,
) =>
  api<CartResponse>(`/cart/items/${id}`, { method: "DELETE", signal, headers: cartHeaders(sessionUuid) });

export const postCartCoupon = (
  code: string,
  sessionUuid?: string | null,
  signal?: AbortSignal,
) =>
  api<CartResponse>("/cart/coupon", { method: "POST", body: { code }, signal, headers: cartHeaders(sessionUuid) });

export const deleteCartCoupon = (sessionUuid?: string | null, signal?: AbortSignal) =>
  api<CartResponse>("/cart/coupon", { method: "DELETE", signal, headers: cartHeaders(sessionUuid) });

/**
 * Phase 2.4 — POST /cart/merge.
 *
 * Merges a guest cart (identified by `guestSessionUuid`) into the
 * authenticated user's cart. Sanctum-required; the api() helper
 * auto-attaches the bearer token. Body carries the guest UUID.
 *
 * The OTP-verify path (verifyOtp) also performs an automatic
 * merge via the X-Cart-Session header — both paths converge on
 * the same idempotent server-side service, so calling this
 * explicitly after login is safe (no-op when the guest cart was
 * already merged at OTP-verify time).
 */
export const postCartMerge = (guestSessionUuid: string, signal?: AbortSignal) =>
  apiPost<CartResponse>("/cart/merge", { guest_session_uuid: guestSessionUuid }, signal);

/* ───────────── Phase 2.5a — Service centers / Checkout / Orders ───────────── */

export const fetchServiceCenters = (signal?: AbortSignal) =>
  apiGet<ServiceCentersResponse>("/service-centers", undefined, signal);

export const postCheckoutQuote = (req: CheckoutQuoteRequest, signal?: AbortSignal) =>
  apiPost<CheckoutQuoteResponse>("/checkout/quote", req, signal);

export const postPlaceOrder = (req: PlaceOrderRequest, signal?: AbortSignal) =>
  apiPost<OrderResponse>("/checkout/place-order", req, signal);

export const fetchOrders = (
  params?: { page?: number; per_page?: number; status?: string },
  signal?: AbortSignal,
) => apiGet<OrdersListResponse>("/user/orders", params as Record<string, string | number | undefined>, signal);

export const fetchOrder = (orderId: number, signal?: AbortSignal) =>
  apiGet<OrderResponse>(`/user/orders/${orderId}`, undefined, signal);

export const postCancelOrder = (
  orderId: number,
  reason?: string | null,
  signal?: AbortSignal,
) =>
  apiPost<OrderResponse>(
    `/user/orders/${orderId}/cancel`,
    reason ? { reason } : {},
    signal,
  );

/* ───────────── Phase 2.5b — Coupons (public listing) ───────────── */

/**
 * GET /coupons. `context=marketing` returns featured coupons for the
 * /coupons landing page. `context=cart` adds per-coupon eligibility
 * flags computed against the authenticated user's active cart.
 */
export const fetchCoupons = (
  context: "marketing" | "cart" = "marketing",
  signal?: AbortSignal,
) => apiGet<CouponsListResponse>("/coupons", { context }, signal);
