/**
 * useCart — server-authoritative cart (Phase 2.3).
 *
 * Source of truth: the backend `/cart/*` endpoints. The browser holds
 * a guest UUID in `localStorage` only as a session identifier — the
 * cart contents themselves are never read from local storage. When a
 * sanctum bearer token is set on `api`, the cart resolves by user;
 * when not, the X-Cart-Session header carries the UUID.
 *
 * Public surface preserved for compile compatibility with existing
 * pages (Cart, Header, Services, ServiceCategory, ServiceDetail,
 * Checkout, Payment):
 *   { items, addItem, updateQty, removeItem, clearCart, subtotal, count }
 *
 * `addItem` accepts the legacy fields (serviceId, title, price,
 * categorySlug, car?, location?) AND optional brand_id/model_id/
 * fuel_id. When all three IDs are present the request includes a
 * structured `vehicle` block so the server can pick a vehicle-
 * specific price from `service_prices`. When IDs are missing the
 * server falls back to the service's `base_price` (or 422s if that
 * is also unset).
 *
 * Coupon mutations are stubbed to throw — the /cart/coupon
 * endpoints are 501 in this commit and light up in Phase 2.6.
 */
import { useCallback, useEffect, useMemo, useState } from "react";
import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import {
  ApiError,
  deleteCartItem as deleteCartItemApi,
  fetchCart,
  getToken,
  postCartItem,
  putCartItem,
} from "../lib/api";
import type {
  AddCartItemRequest,
  CartItemResource,
  CartResource,
} from "../types/api";

/* ─────────────────── Public types (legacy, preserved) ─────────────────── */

export interface CartItem {
  id: string;
  serviceId: string;
  title: string;
  price: number;
  qty: number;
  categorySlug: string;
  car?: { brand: string; model: string; fuel: string };
  location?: string;
}

/* ─────────────────── Session UUID ─────────────────── */

const SESSION_KEY = "acr_cart_session";

/* ─────────────────── Phase 2.3.1 — legacy localStorage purge ───────────────────
 * Pre-Phase-2.3 the hook persisted an `acr_cart_v1` array as the
 * cart's source of truth. Phase 2.3 made the server authoritative
 * and dropped every read/write of that key — but existing user
 * browsers still carry the stale entry, surfacing it in DevTools
 * Local Storage and confusing operators ("why are there cart items
 * here?"). This module-level cleanup deletes the key once per page
 * load. Idempotent: a second navigation no-ops because the key is
 * already gone. Safe to leave in indefinitely; can be removed once
 * we're confident no live user still has the legacy entry. */
if (typeof window !== "undefined") {
  try {
    if (window.localStorage.getItem("acr_cart_v1") !== null) {
      window.localStorage.removeItem("acr_cart_v1");
    }
  } catch {
    /* swallow — storage may be disabled */
  }
}

/** Returns a stable per-browser UUID, generating one on first call. */
function ensureSessionUuid(): string {
  if (typeof window === "undefined") return "";
  try {
    const existing = window.localStorage.getItem(SESSION_KEY);
    if (existing) return existing;
    const generated = generateUuid();
    window.localStorage.setItem(SESSION_KEY, generated);
    return generated;
  } catch {
    return generateUuid();
  }
}

function generateUuid(): string {
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
    return crypto.randomUUID();
  }
  // RFC4122 v4 fallback (only fires on ancient browsers/SSR)
  return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === "x" ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

/** Send X-Cart-Session only when there's no bearer token. */
function activeSessionUuid(): string | undefined {
  if (getToken()) return undefined;
  return ensureSessionUuid();
}

/* ─────────────────── Server → legacy mapping ─────────────────── */

function presentItem(api: CartItemResource): CartItem {
  const meta = (api.meta ?? {}) as Record<string, unknown>;
  const car = meta.car as CartItem["car"] | undefined;
  const location = typeof meta.location === "string" ? meta.location : undefined;

  return {
    id:           String(api.id),
    serviceId:    String(api.ref_id),
    title:        api.display_title || (typeof meta.title === "string" ? meta.title : ""),
    price:        api.unit_price_snapshot,
    qty:          api.quantity,
    categorySlug: api.category_slug ?? (typeof meta.category_slug === "string" ? meta.category_slug : ""),
    car,
    location,
  };
}

/* ─────────────────── Hook ─────────────────── */

export function useCart() {
  const qc = useQueryClient();

  // Re-key the query whenever the auth token flips so a login/logout
  // immediately refetches the right cart (user vs. guest).
  const token = useTokenFlag();

  const cartQuery = useQuery<CartResource>({
    queryKey: ["cart", token ? "user" : "guest"],
    queryFn: async () => (await fetchCart(activeSessionUuid())).cart,
    staleTime: 30_000,
  });

  const cart = cartQuery.data;

  const items: CartItem[] = useMemo(
    () => (cart ? cart.items.map(presentItem) : []),
    [cart],
  );

  const subtotal = cart?.totals.subtotal ?? 0;
  const count    = cart?.item_count ?? 0;

  const invalidate = useCallback(() => {
    qc.invalidateQueries({ queryKey: ["cart"] });
  }, [qc]);

  const addMutation = useMutation({
    mutationFn: async (req: AddCartItemRequest) => {
      const res = await postCartItem(req, activeSessionUuid());
      return res.cart;
    },
    onSuccess: (newCart) => {
      qc.setQueryData(["cart", token ? "user" : "guest"], newCart);
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, body }: { id: number; body: { quantity?: number; vehicle?: AddCartItemRequest["vehicle"] } }) => {
      const res = await putCartItem(id, body, activeSessionUuid());
      return res.cart;
    },
    onSuccess: (newCart) => {
      qc.setQueryData(["cart", token ? "user" : "guest"], newCart);
    },
  });

  const removeMutation = useMutation({
    mutationFn: async (id: number) => {
      const res = await deleteCartItemApi(id, activeSessionUuid());
      return res.cart;
    },
    onSuccess: (newCart) => {
      qc.setQueryData(["cart", token ? "user" : "guest"], newCart);
    },
  });

  /* ─────── Legacy-shape mutations the rest of the app calls ─────── */

  /**
   * Adds a service to the cart. Pass `brand_id`/`model_id`/`fuel_id`
   * (all three) for a priced add; otherwise the server uses the
   * service's `base_price` and 422s if that is unset.
   */
  const addItem = useCallback(
    (item: Omit<CartItem, "id" | "qty"> & {
      qty?: number;
      brand_id?: number;
      model_id?: number;
      fuel_id?: number;
    }) => {
      const refId = Number(item.serviceId);
      if (!Number.isFinite(refId) || refId <= 0) return;

      const vehicle =
        item.brand_id && item.model_id && item.fuel_id
          ? { brand_id: item.brand_id, model_id: item.model_id, fuel_id: item.fuel_id }
          : undefined;

      const meta: Record<string, unknown> = {
        title:         item.title,
        category_slug: item.categorySlug,
      };
      if (item.car) meta.car = item.car;
      if (item.location) meta.location = item.location;

      void addMutation.mutateAsync({
        kind:     "service",
        ref_id:   refId,
        quantity: item.qty ?? 1,
        vehicle,
        meta,
      }).catch((e) => {
        if (typeof console !== "undefined") {
          const msg = e instanceof ApiError ? e.message : String(e);
          console.warn("[useCart] addItem failed:", msg);
        }
      });
    },
    [addMutation],
  );

  const updateQty = useCallback(
    (id: string, qty: number) => {
      const numericId = Number(id);
      if (!Number.isFinite(numericId) || numericId <= 0) return;
      void updateMutation.mutateAsync({ id: numericId, body: { quantity: Math.max(1, qty) } }).catch(() => {});
    },
    [updateMutation],
  );

  const removeItem = useCallback(
    (id: string) => {
      const numericId = Number(id);
      if (!Number.isFinite(numericId) || numericId <= 0) return;
      void removeMutation.mutateAsync(numericId).catch(() => {});
    },
    [removeMutation],
  );

  /**
   * Clears the cart by deleting every item sequentially. The server
   * will land a `/cart` DELETE in 2.4 alongside merge — for now this
   * is the fastest correct path with the existing endpoints.
   */
  const clearCart = useCallback(async () => {
    if (!cart) return;
    for (const it of cart.items) {
      try {
        await deleteCartItemApi(it.id, activeSessionUuid());
      } catch { /* swallow — best-effort */ }
    }
    invalidate();
  }, [cart, invalidate]);

  /* ─────── Coupons (501 until 2.6) ─────── */

  const applyCoupon = useCallback(async (_code: string): Promise<{ success: false; error: string }> => {
    return { success: false, error: "Coupons are coming soon (Phase 2.6)." };
  }, []);

  const removeCoupon = useCallback(async (): Promise<{ success: false; error: string }> => {
    return { success: false, error: "Coupons are coming soon (Phase 2.6)." };
  }, []);

  return {
    items,
    addItem,
    updateQty,
    removeItem,
    clearCart,
    subtotal,
    count,
    /** Server-side cart resource (use directly when you need richer fields). */
    cart,
    /** Coupon stubs — will return 501-equivalent until 2.6. */
    applyCoupon,
    removeCoupon,
    isLoading: cartQuery.isLoading,
    isError:   cartQuery.isError,
  };
}

/* ─────────────────── Token-flag hook ─────────────────── */
/**
 * Returns true if a bearer token is currently set. Listens to the
 * `acr-token-updated` event fired by setToken() so the cart query
 * key flips when the user logs in/out.
 */
function useTokenFlag(): boolean {
  const [hasToken, setHasToken] = useState(!!getToken());
  useEffect(() => {
    const onUpdate = () => setHasToken(!!getToken());
    window.addEventListener("acr-token-updated", onUpdate);
    return () => window.removeEventListener("acr-token-updated", onUpdate);
  }, []);
  return hasToken;
}

/* ─────────────────── useCheckout (unchanged from Phase 2.1) ─────────────────── */

export interface CheckoutDetails {
  name: string;
  phone: string;
  email: string;
  address: string;
  preferredDate: string;
  preferredTime: string;
  serviceCenter: string;
  notes: string;
  /** Coupon code applied to the cart (null/empty = none / auto-pick best). */
  couponCode: string;
}

// Phase 2.5 review: consider sessionStorage instead of localStorage
// for PII (name/phone/email persist across sessions today); or clear
// on logout. CheckoutDetails currently holds only safe form-prefill
// fields — no cart line data — so cart server-truth is unaffected.
const CHECKOUT_KEY = "acr_checkout_v1";
const CHECKOUT_EVENT = "acr-checkout-updated";
const EMPTY_CHECKOUT: CheckoutDetails = {
  name: "",
  phone: "",
  email: "",
  address: "",
  preferredDate: "",
  preferredTime: "",
  serviceCenter: "",
  notes: "",
  couponCode: "",
};

const readCheckout = (): CheckoutDetails => {
  try {
    if (typeof window === "undefined") return EMPTY_CHECKOUT;
    const raw = window.localStorage.getItem(CHECKOUT_KEY);
    return raw ? { ...EMPTY_CHECKOUT, ...JSON.parse(raw) } : EMPTY_CHECKOUT;
  } catch {
    return EMPTY_CHECKOUT;
  }
};

const writeCheckout = (details: CheckoutDetails) => {
  try {
    if (typeof window === "undefined") return;
    window.localStorage.setItem(CHECKOUT_KEY, JSON.stringify(details));
    window.dispatchEvent(new Event(CHECKOUT_EVENT));
  } catch {
    /* swallow */
  }
};

export function useCheckout() {
  const [details, setDetailsState] = useState<CheckoutDetails>(() => readCheckout());

  useEffect(() => {
    const onLocal = () => setDetailsState(readCheckout());
    window.addEventListener(CHECKOUT_EVENT, onLocal);
    return () => window.removeEventListener(CHECKOUT_EVENT, onLocal);
  }, []);

  const setDetails = (next: Partial<CheckoutDetails>) => {
    const merged = { ...readCheckout(), ...next };
    writeCheckout(merged);
    setDetailsState(merged);
  };

  const resetDetails = () => {
    writeCheckout(EMPTY_CHECKOUT);
    setDetailsState(EMPTY_CHECKOUT);
  };

  return { details, setDetails, resetDetails };
}
