import { useQuery } from "@tanstack/react-query";
import { apiGet } from "../../lib/api";

/**
 * Phase 4.5.3 — public master-data lookups for the
 * explore-sidebar lead form.
 *
 * Server-side cache is 1h (set in LookupController). Client-side
 * staleTime mirrors that — same data, no point re-fetching.
 */

export interface BrandRef {
  id: number;
  slug: string;
  name: string;
}

export interface ModelRef {
  id: number;
  slug: string;
  name: string;
  brand_id: number;
}

export interface ServiceRef {
  id: number;
  slug: string;
  name: string;
  category: { id: number; slug: string; name: string } | null;
}

interface LookupEnvelope<T> {
  data: T[];
}

const ONE_HOUR = 1000 * 60 * 60;

export function useBrands() {
  return useQuery({
    queryKey: ["lookups", "brands"],
    queryFn: ({ signal }) =>
      apiGet<LookupEnvelope<BrandRef>>("/lookups/brands", undefined, signal)
        .then((r) => r.data),
    staleTime: ONE_HOUR,
  });
}

export function useModels(brandId: number | null) {
  return useQuery({
    queryKey: ["lookups", "models", brandId],
    queryFn: ({ signal }) =>
      apiGet<LookupEnvelope<ModelRef>>(
        "/lookups/models",
        { brand_id: brandId! },
        signal,
      ).then((r) => r.data),
    enabled: !!brandId,
    staleTime: ONE_HOUR,
  });
}

export function useServices() {
  return useQuery({
    queryKey: ["lookups", "services"],
    queryFn: ({ signal }) =>
      apiGet<LookupEnvelope<ServiceRef>>("/lookups/services", undefined, signal)
        .then((r) => r.data),
    staleTime: ONE_HOUR,
  });
}
