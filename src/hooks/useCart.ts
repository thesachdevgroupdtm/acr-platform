/**
 * useCart — global cart state for ACR.
 *
 * - Source of truth (UI):  localStorage (same as before — zero UX change).
 * - When the user is authenticated, items are mirrored to the server via
 *   POST /cart/sync so /checkout/* endpoints can transform them into orders.
 *   The mirror is best-effort and debounced; failures don't block UX.
 *
 * Public hook signature unchanged: items, addItem, updateQty, removeItem,
 * clearCart, subtotal, count.
 */
import { useEffect, useRef, useState } from "react";
import { apiPost, getToken } from "../lib/api";
import { FEATURES } from "../config/features";

export interface CartItem {
  /** Unique row id — `serviceId-timestamp` so duplicates can coexist if needed. */
  id: string;
  serviceId: string;
  title: string;
  /** Per-unit price in INR. May be 0 if "quote-only". */
  price: number;
  qty: number;
  categorySlug: string;
  /** Optional context captured at add-time (from booking card on category). */
  car?: { brand: string; model: string; fuel: string };
  location?: string;
}

const KEY = "acr_cart_v1";
const EVENT = "acr-cart-updated";

const safeRead = (): CartItem[] => {
  try {
    if (typeof window === "undefined") return [];
    const raw = window.localStorage.getItem(KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
};

const safeWrite = (items: CartItem[]) => {
  try {
    if (typeof window === "undefined") return;
    window.localStorage.setItem(KEY, JSON.stringify(items));
    window.dispatchEvent(new Event(EVENT));
  } catch {
    /* swallow — storage may be disabled */
  }
};

export function useCart() {
  const [items, setItems] = useState<CartItem[]>(() => safeRead());

  useEffect(() => {
    // Same-tab updates (other instances of this hook)
    const onLocal = () => setItems(safeRead());
    // Cross-tab updates (other browser tabs)
    const onStorage = (e: StorageEvent) => {
      if (e.key === KEY) setItems(safeRead());
    };
    window.addEventListener(EVENT, onLocal);
    window.addEventListener("storage", onStorage);
    return () => {
      window.removeEventListener(EVENT, onLocal);
      window.removeEventListener("storage", onStorage);
    };
  }, []);

  // ── Best-effort server mirror (only when feature flag on AND authenticated) ──
  const syncTimer = useRef<number | undefined>(undefined);
  useEffect(() => {
    if (!FEATURES.cartSync) return;       // /cart/sync route not implemented yet
    if (!getToken() || items.length === 0) return;
    if (syncTimer.current) window.clearTimeout(syncTimer.current);
    syncTimer.current = window.setTimeout(() => {
      const payload = items
        .map((i) => {
          const sid = Number(i.serviceId);
          if (!Number.isFinite(sid) || sid <= 0) return null;
          return { service_id: sid, qty: i.qty };
        })
        .filter(Boolean);
      if (payload.length === 0) return;
      apiPost("/cart/sync", { items: payload }).catch((e) => {
        if (typeof console !== "undefined") {
          console.warn("Cart sync failed:", e?.message || e);
        }
        /* server cart will re-sync on next change */
      });
    }, 600);
    return () => {
      if (syncTimer.current) window.clearTimeout(syncTimer.current);
    };
  }, [items]);

  // Add an item; if the same serviceId is already in the cart, bump qty by 1.
  const addItem = (
    item: Omit<CartItem, "id" | "qty"> & { qty?: number }
  ) => {
    const current = safeRead();
    const existing = current.find((i) => i.serviceId === item.serviceId);
    if (existing) {
      existing.qty += item.qty ?? 1;
      // Refresh contextual fields (latest car/location wins)
      if (item.car) existing.car = item.car;
      if (item.location) existing.location = item.location;
      safeWrite(current);
      return;
    }
    const next: CartItem = {
      id: `${item.serviceId}-${Date.now()}`,
      serviceId: item.serviceId,
      title: item.title,
      price: item.price,
      qty: item.qty ?? 1,
      categorySlug: item.categorySlug,
      car: item.car,
      location: item.location,
    };
    safeWrite([...current, next]);
  };

  const updateQty = (id: string, qty: number) => {
    const next = safeRead()
      .map((i) => (i.id === id ? { ...i, qty: Math.max(1, qty) } : i))
      .filter((i) => i.qty > 0);
    safeWrite(next);
  };

  const removeItem = (id: string) => {
    safeWrite(safeRead().filter((i) => i.id !== id));
  };

  const clearCart = () => safeWrite([]);

  const subtotal = items.reduce((sum, i) => sum + (i.price || 0) * i.qty, 0);
  const count = items.reduce((sum, i) => sum + i.qty, 0);

  return { items, addItem, updateQty, removeItem, clearCart, subtotal, count };
}

// ---------- Checkout details (separate slice, same persistence pattern) ----------

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
  const [details, setDetailsState] = useState<CheckoutDetails>(() =>
    readCheckout()
  );

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
