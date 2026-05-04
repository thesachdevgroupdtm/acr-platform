/**
 * Phase 2.5.1 — typed domain errors thrown by the cart hook so
 * page-level callers can branch on instanceof rather than parsing
 * messages.
 *
 * Currently a single error: `VehicleConflictError`. The cart now
 * holds at most one vehicle (D-2.5.1-1); when a user tries to add
 * a service for a different vehicle than the one already in the
 * cart, useCart.addItem throws VehicleConflictError carrying the
 * existing vehicle, the new vehicle, and the pending request so
 * the caller can prompt and (on confirm) replay via
 * useCart.replaceVehicleInCart.
 */
import type { AddCartItemRequest } from "../types/api";

export interface VehicleSummary {
  brand_id: number | null;
  model_id: number | null;
  fuel_id: number | null;
  brand_name?: string | null;
  model_name?: string | null;
  fuel_name?: string | null;
}

export interface VehicleConflictDetails {
  existingVehicle: VehicleSummary;
  newVehicle: VehicleSummary;
  /** Replay this through `replaceVehicleInCart` when the user confirms. */
  pendingItem: AddCartItemRequest;
}

export class VehicleConflictError extends Error {
  details: VehicleConflictDetails;

  constructor(details: VehicleConflictDetails) {
    super("Cart contains items for a different vehicle.");
    this.name = "VehicleConflictError";
    this.details = details;
  }
}

/** Render a human-readable label for a vehicle summary. */
export function vehicleLabel(v: VehicleSummary | null | undefined): string {
  if (!v) return "this vehicle";
  return [v.brand_name, v.model_name, v.fuel_name].filter(Boolean).join(" ").trim()
    || "this vehicle";
}
