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
  deleteCartCoupon as deleteCartCouponApi,
  deleteCartItem as deleteCartItemApi,
  fetchCart,
  getToken,
  postCartCoupon as postCartCouponApi,
  postCartItem,
  putCartItem,
} from "../lib/api";
import type {
  AddCartItemRequest,
  CartItemResource,
  CartResource,
} from "../types/api";
import { VehicleConflictError } from "../lib/errors";

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

  /**
   * Phase 2.3.2 — Add-to-Cart button toggle (Bug A).
   * Phase 2.5.1 (D-2.5.1-1) — match on (kind, ref_id) only. The cart
   * now holds at most one vehicle, so the vehicle tuple is constant
   * across all rows; including it in the toggle key was creating
   * spurious "not in cart" states whenever the user changed
   * vehicle. Callers may still pass brand_id/model_id/fuel_id for
   * source-compat — they're ignored.
   */
  const isInCart = useCallback(
    (q: {
      kind?: CartItemResource["kind"];
      ref_id: number;
      brand_id?: number | null;
      model_id?: number | null;
      fuel_id?: number | null;
    }): boolean => {
      if (!cart) return false;
      const targetKind = q.kind ?? "service";
      return cart.items.some(
        (it) => it.kind === targetKind && it.ref_id === q.ref_id,
      );
    },
    [cart],
  );

  /**
   * Phase 2.3.3 — toggle-remove companion. Phase 2.5.1 — same key
   * narrowing as `isInCart` (kind + ref_id only).
   */
  const findCartItem = useCallback(
    (q: {
      kind?: CartItemResource["kind"];
      ref_id: number;
      brand_id?: number | null;
      model_id?: number | null;
      fuel_id?: number | null;
    }): CartItemResource | null => {
      if (!cart) return null;
      const targetKind = q.kind ?? "service";
      return (
        cart.items.find(
          (it) => it.kind === targetKind && it.ref_id === q.ref_id,
        ) ?? null
      );
    },
    [cart],
  );

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
   *
   * Phase 2.5.1 (D-2.5.1-1) — one-vehicle-per-cart enforcement.
   * Throws `VehicleConflictError` (from src/lib/errors) when the
   * cart already holds rows for a different vehicle than the
   * incoming request. Callers must catch this and prompt the user
   * via `<VehicleReplaceModal>`; on confirm, replay the request
   * through `replaceVehicleInCart`.
   *
   * Returns a Promise so callers can `await` and `try/catch`.
   * Existing fire-and-forget callers (no await) continue to work —
   * a swallowed VehicleConflictError just leaves the cart unchanged,
   * which is the correct fallback if the page hasn't been migrated
   * to the prompt flow yet.
   */
  const addItem = useCallback(
    async (item: Omit<CartItem, "id" | "qty"> & {
      qty?: number;
      brand_id?: number;
      model_id?: number;
      fuel_id?: number;
    }): Promise<void> => {
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

      const req: AddCartItemRequest = {
        kind:     "service",
        ref_id:   refId,
        quantity: item.qty ?? 1,
        vehicle,
        meta,
      };

      // One-vehicle-per-cart conflict check. Looks up the cart's
      // first vehicle-bearing row; if its (brand,model,fuel) tuple
      // differs from the request's, throw and let the caller
      // prompt. A request without a vehicle never conflicts (gets
      // base-price treatment server-side); a cart line without a
      // vehicle never conflicts either.
      if (cart && vehicle) {
        const existing = cart.items.find(
          (it) =>
            it.vehicle?.brand_id != null &&
            it.vehicle?.model_id != null &&
            it.vehicle?.fuel_id != null,
        );
        if (existing && existing.vehicle) {
          const sameVehicle =
            existing.vehicle.brand_id === vehicle.brand_id &&
            existing.vehicle.model_id === vehicle.model_id &&
            existing.vehicle.fuel_id === vehicle.fuel_id;
          if (!sameVehicle) {
            throw new VehicleConflictError({
              existingVehicle: {
                brand_id: existing.vehicle.brand_id,
                model_id: existing.vehicle.model_id,
                fuel_id:  existing.vehicle.fuel_id,
                // Pull human names from cart-item meta when present —
                // the server resource only carries IDs.
                brand_name: (existing.meta?.car as { brand?: string } | undefined)?.brand ?? null,
                model_name: (existing.meta?.car as { model?: string } | undefined)?.model ?? null,
                fuel_name:  (existing.meta?.car as { fuel?:  string } | undefined)?.fuel  ?? null,
              },
              newVehicle: {
                brand_id: vehicle.brand_id,
                model_id: vehicle.model_id,
                fuel_id:  vehicle.fuel_id,
                brand_name: item.car?.brand ?? null,
                model_name: item.car?.model ?? null,
                fuel_name:  item.car?.fuel  ?? null,
              },
              pendingItem: req,
            });
          }
        }
      }

      try {
        await addMutation.mutateAsync(req);
      } catch (e) {
        if (typeof console !== "undefined") {
          const msg = e instanceof ApiError ? e.message : String(e);
          console.warn("[useCart] addItem failed:", msg);
        }
        throw e;
      }
    },
    [addMutation, cart],
  );

  /**
   * Phase 2.5.1 — replay an `AddCartItemRequest` after the user has
   * confirmed a vehicle replacement. Sequentially deletes existing
   * items, then posts the new one. There's no /cart wipe endpoint
   * yet (Phase 2.3 deviation #5); N×DELETE is correct and idempotent.
   */
  const replaceVehicleInCart = useCallback(
    async (pendingItem: AddCartItemRequest): Promise<void> => {
      if (!cart) {
        await addMutation.mutateAsync(pendingItem);
        return;
      }
      // Best-effort sequential clear; an individual delete failure
      // shouldn't block the eventual add — the next /cart fetch
      // will reflect whatever survived.
      for (const it of cart.items) {
        try {
          await deleteCartItemApi(it.id, activeSessionUuid());
        } catch {
          /* swallow; see comment above */
        }
      }
      await addMutation.mutateAsync(pendingItem);
      qc.invalidateQueries({ queryKey: ["cart"] });
    },
    [cart, addMutation, qc],
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

  /* ─────── Coupons (Phase 2.5b — real backend) ───────
   *
   * Both mutations hit the real /cart/coupon endpoints. The
   * server validates (CouponService::validate) and replaces the
   * cart's coupon_id under last-apply-wins; the response carries
   * the updated CartResource so React Query reads the new totals
   * straight away.
   *
   * Errors are surfaced via the same `{success:false, error}`
   * envelope the 2.5.1 stub used so existing UI consumers
   * (CouponInput, CouponPickerModal) compile unchanged.
   */
  const applyCouponMutation = useMutation({
    mutationFn: async (code: string) => {
      const res = await postCartCouponApi(code, activeSessionUuid());
      return res.cart;
    },
    onSuccess: (newCart) => {
      qc.setQueryData(["cart", token ? "user" : "guest"], newCart);
    },
  });

  const removeCouponMutation = useMutation({
    mutationFn: async () => {
      const res = await deleteCartCouponApi(activeSessionUuid());
      return res.cart;
    },
    onSuccess: (newCart) => {
      qc.setQueryData(["cart", token ? "user" : "guest"], newCart);
    },
  });

  const applyCoupon = useCallback(
    async (
      code: string,
    ): Promise<{ success: true } | { success: false; error: string }> => {
      try {
        await applyCouponMutation.mutateAsync(code.trim().toUpperCase());
        return { success: true };
      } catch (e) {
        const msg = e instanceof ApiError ? e.message : "Couldn't apply coupon. Please try again.";
        return { success: false, error: msg };
      }
    },
    [applyCouponMutation],
  );

  const removeCoupon = useCallback(
    async (): Promise<{ success: true } | { success: false; error: string }> => {
      try {
        await removeCouponMutation.mutateAsync();
        return { success: true };
      } catch (e) {
        const msg = e instanceof ApiError ? e.message : "Couldn't remove coupon. Please try again.";
        return { success: false, error: msg };
      }
    },
    [removeCouponMutation],
  );

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
    /** Phase 2.3.2 — Add-to-Cart button toggle helper (Bug A). */
    isInCart,
    /** Phase 2.3.3 — companion lookup so callers can toggle-remove via
     *  `removeItem(findCartItem(...).id)` on a second click. */
    findCartItem,
    /** Phase 2.5.1 — replay an add request after the user confirms a
     *  vehicle replacement. Wipes existing items, then posts. */
    replaceVehicleInCart,
    /** Coupon stubs — will return 501-equivalent until 2.5b. */
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

/* ─────────────────── useCheckout (Phase 2.5.1 — couponCode removed) ───────────────────
 *
 * Phase 2.5.1 (D-2.5.1-5) — `couponCode` was removed from this
 * draft because the legacy auto-apply path it powered is gone.
 * Coupon state now lives on the server cart (via the eventual
 * Phase 2.5b /cart/coupon endpoints); the frontend reads
 * cart.totals.coupon directly and never persists a code locally.
 *
 * `readCheckout` strips the legacy `couponCode` field on every
 * read, so existing browsers carrying it will see it disappear
 * silently after one render. Eventually safe to remove the strip
 * once we're confident no live user has a stale draft.
 */
export interface CheckoutDetails {
  name: string;
  phone: string;
  email: string;
  address: string;
  preferredDate: string;
  preferredTime: string;
  serviceCenter: string;
  notes: string;
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
};

const readCheckout = (): CheckoutDetails => {
  try {
    if (typeof window === "undefined") return EMPTY_CHECKOUT;
    const raw = window.localStorage.getItem(CHECKOUT_KEY);
    if (!raw) return EMPTY_CHECKOUT;
    // One-time strip of the legacy couponCode field (Phase 2.5.1).
    const parsed = JSON.parse(raw) as Record<string, unknown>;
    if ("couponCode" in parsed) {
      delete parsed.couponCode;
    }
    return { ...EMPTY_CHECKOUT, ...(parsed as Partial<CheckoutDetails>) };
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
