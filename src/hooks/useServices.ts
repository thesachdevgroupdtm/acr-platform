/**
 * Services + categories hooks (API-only; no static fallback).
 *
 *   useServiceCategories(vehicleIds?)             → /services
 *   useCategoryDetail(slug, vehicleSlugs?)        → /services/{slug}
 *   useServiceDetail(catSlug, svcSlug, vIds?)     → /services/{cat}/{svc}
 *
 * Note: useAllSubServices was deleted in Phase 1.6 — sub-services now
 * arrive nested under each category in /home and /services responses,
 * so the parallel-fetch hook is no longer needed.
 */
import { useQuery } from "@tanstack/react-query";
import {
  fetchCategoryDetail,
  fetchServiceDetail,
  fetchServices,
  type CategoryDetailResponse,
  type ServiceDetailResponse,
  type ServicesResponse,
} from "../lib/api";

export interface VehicleIdQuery {
  brand_id?: number | null;
  model_id?: number | null;
  fuel_id?: number | null;
}
export interface VehicleSlugQuery {
  brand?: string | null;
  model?: string | null;
  fuel?: string | null;
}

export function useServiceCategories(vehicle?: VehicleIdQuery) {
  return useQuery<ServicesResponse>({
    queryKey: ["services", vehicle ?? null],
    queryFn: ({ signal }) => fetchServices(vehicle ?? undefined, signal),
  });
}

export function useCategoryDetail(slug: string, vehicle?: VehicleSlugQuery) {
  return useQuery<CategoryDetailResponse>({
    queryKey: ["category-detail", slug, vehicle ?? null],
    queryFn: ({ signal }) => fetchCategoryDetail(slug, vehicle ?? undefined, signal),
    enabled: !!slug,
  });
}

export function useServiceDetail(
  categorySlug: string,
  serviceSlug: string,
  vehicle?: VehicleIdQuery
) {
  return useQuery<ServiceDetailResponse>({
    queryKey: ["service-detail", categorySlug, serviceSlug, vehicle ?? null],
    queryFn: ({ signal }) =>
      fetchServiceDetail(categorySlug, serviceSlug, vehicle ?? undefined, signal),
    enabled: !!categorySlug && !!serviceSlug,
  });
}
