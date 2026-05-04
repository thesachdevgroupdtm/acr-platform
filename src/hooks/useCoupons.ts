/**
 * Phase 2.5b — useCoupons.
 *
 * Two contexts:
 *   - 'marketing' (default): featured / active / non-expired coupons,
 *     used by /coupons landing page and the picker modal's "browse all"
 *     state.
 *   - 'cart': same filter plus per-coupon eligibility (eligible /
 *     ineligible_reason fields). Used by CouponPickerModal so the
 *     user sees why a card is dimmed.
 *
 * staleTime 2 min — the list rarely changes; per-coupon eligibility
 * recomputes on each request so a cart change still surfaces a
 * fresh eligibility map by invalidating ['coupons','cart'].
 */
import { useQuery } from "@tanstack/react-query";
import { fetchCoupons } from "../lib/api";
import type { CouponResource } from "../types/api";

export function useCoupons(context: "marketing" | "cart" = "marketing") {
  const q = useQuery<CouponResource[]>({
    queryKey: ["coupons", context],
    queryFn: async ({ signal }) => (await fetchCoupons(context, signal)).coupons,
    staleTime: 2 * 60 * 1000,
  });

  return {
    coupons: q.data ?? [],
    isLoading: q.isLoading,
    isFetching: q.isFetching,
    isError: q.isError,
    error: q.error,
    refetch: () => { void q.refetch(); },
  };
}
