/**
 * usePricing — POST /api/v1/pricing
 *
 * Returns the cached price for a brand × model × fuel × service[s] tuple.
 * Mutation form is preferred (one quote per user action), but a query
 * variant is also exposed for situations where the inputs are stable
 * and you want auto-fetch + cache.
 */
import { useMutation, useQuery } from "@tanstack/react-query";
import { postPricing, type PricingRequest, type PricingResponse } from "../lib/api";

/**
 * Imperative quote — call .mutateAsync(req) when the user picks/changes
 * the cart or vehicle. Returns the API response or throws on error.
 */
export function usePricingQuote() {
  return useMutation<PricingResponse, Error, PricingRequest>({
    mutationFn: (req) => postPricing(req),
  });
}

/**
 * Reactive quote — auto-fetches when all required IDs are present.
 * Use this when the page just needs to render a price and you want
 * caching keyed by (brand, model, fuel, services).
 */
export function usePricingFor(req: PricingRequest | null | undefined) {
  return useQuery<PricingResponse>({
    queryKey: ["pricing", req ?? null],
    queryFn: () => postPricing(req as PricingRequest),
    enabled:
      !!req &&
      typeof req.brand_id === "number" &&
      typeof req.model_id === "number" &&
      typeof req.fuel_type_id === "number" &&
      (typeof req.service_id === "number" || (Array.isArray(req.service_ids) && req.service_ids.length > 0)),
  });
}
