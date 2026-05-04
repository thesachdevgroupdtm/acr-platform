/**
 * useServiceCenters — Phase 2.5a (D-2.5a-2).
 *
 * Reads the canonical service-center list from the backend
 * (/service-centers). Used by the Checkout page dropdown. The list
 * is small (4 rows) and rarely changes, so a 5-minute staleTime
 * keeps the cache warm across page navigations without forcing a
 * round-trip on every Checkout render.
 */
import { useQuery } from "@tanstack/react-query";
import { fetchServiceCenters } from "../lib/api";
import type { ServiceCenterResource } from "../types/api";

export function useServiceCenters() {
  const q = useQuery<ServiceCenterResource[]>({
    queryKey: ["service-centers"],
    queryFn: async ({ signal }) => (await fetchServiceCenters(signal)).service_centers,
    staleTime: 5 * 60 * 1000,
  });

  return {
    centers: q.data ?? [],
    isLoading: q.isLoading,
    isError: q.isError,
    error: q.error,
    refetch: () => { void q.refetch(); },
  };
}
