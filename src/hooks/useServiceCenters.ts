/**
 * useServiceCenters — Phase 2.5a (D-2.5a-2).
 * Phase 4.5c — surface the optional `seo` field from the response
 * so the customer ServiceCenters page can inject SeoHead. The hook
 * now caches the whole response object instead of just the array;
 * existing consumers reading `.centers` keep working unchanged.
 *
 * Reads the canonical service-center list from the backend
 * (/service-centers). Used by the Checkout page dropdown. The list
 * is small (4 rows) and rarely changes, so a 5-minute staleTime
 * keeps the cache warm across page navigations without forcing a
 * round-trip on every Checkout render.
 */
import { useQuery } from "@tanstack/react-query";
import { fetchServiceCenters } from "../lib/api";
import type { ServiceCentersResponse } from "../types/api";

export function useServiceCenters() {
  const q = useQuery<ServiceCentersResponse>({
    queryKey: ["service-centers"],
    queryFn: ({ signal }) => fetchServiceCenters(signal),
    staleTime: 5 * 60 * 1000,
  });

  return {
    centers: q.data?.service_centers ?? [],
    seo: q.data?.seo,
    isLoading: q.isLoading,
    isError: q.isError,
    error: q.error,
    refetch: () => { void q.refetch(); },
  };
}
