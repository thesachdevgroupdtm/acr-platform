/**
 * useBookingContext — shared booking state that survives navigation
 * between the ServiceCategory (parent) and ServiceDetail (child) pages.
 *
 * The user fills location/car/phone/OTP ONCE on the parent category page,
 * and the same details auto-appear in the sidebar of any child service
 * page they drill into. Backed by localStorage so it also survives
 * page refreshes.
 */
import { useEffect, useState, useCallback } from "react";

export interface BookingCar {
  brand: string;          // human-readable label (back-compat)
  model: string;
  fuel: string;
  /** API ids — populated by the brand/model/fuel pickers; used for
   *  /search/fuels and price lookups in /services and /services/{slug}. */
  brand_id?: number;
  model_id?: number;
  fuel_id?: number;
  /** Slug fields — populated when reading vehicle from URL params. */
  brand_slug?: string;
  model_slug?: string;
  fuel_slug?: string;
}

export interface BookingState {
  location: string; // location id (service center)
  car: BookingCar | null;
  phone: string; // 10 digits (or empty)
  otpVerified: boolean;
  pricesShown: boolean; // true once Check Prices was clicked
}

const STORAGE_KEY = "acr_booking_ctx_v1";
const EVENT = "acr-booking-ctx-updated";

const DEFAULT_STATE: BookingState = {
  location: "",
  car: null,
  phone: "",
  otpVerified: false,
  pricesShown: false,
};

function readState(): BookingState {
  try {
    if (typeof window === "undefined") return { ...DEFAULT_STATE };
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return { ...DEFAULT_STATE };
    const parsed = JSON.parse(raw) as Partial<BookingState>;
    return { ...DEFAULT_STATE, ...parsed };
  } catch {
    return { ...DEFAULT_STATE };
  }
}

function writeState(state: BookingState) {
  try {
    if (typeof window === "undefined") return;
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    window.dispatchEvent(new Event(EVENT));
  } catch {
    /* swallow */
  }
}

export function useBookingContext() {
  const [state, setState] = useState<BookingState>(() => readState());

  useEffect(() => {
    const refresh = () => setState(readState());
    const onLocal = () => refresh();
    const onStorage = (e: StorageEvent) => {
      if (e.key === STORAGE_KEY) refresh();
    };
    window.addEventListener(EVENT, onLocal);
    window.addEventListener("storage", onStorage);
    return () => {
      window.removeEventListener(EVENT, onLocal);
      window.removeEventListener("storage", onStorage);
    };
  }, []);

  const update = useCallback((patch: Partial<BookingState>) => {
    setState((prev) => {
      const next = { ...prev, ...patch };
      writeState(next);
      return next;
    });
  }, []);

  const reset = useCallback(() => {
    writeState({ ...DEFAULT_STATE });
    setState({ ...DEFAULT_STATE });
  }, []);

  return { state, update, reset };
}
