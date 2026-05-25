/**
 * L1 public vehicle endpoints — selector data source (SELECTOR-CONVERGENCE).
 *
 * The vehicle SELECTOR (brand → model → fuel) reads from the L1 public
 * endpoints, which return fully-qualified `hero_image_url`s (full storage
 * URLs), unlike the legacy /vehicle/* endpoints which returned relative
 * paths that 404'd against the frontend origin.
 *
 *   GET /api/v1/public/vehicles/brands               → { data: PublicBrand[], meta }
 *   GET /api/v1/public/vehicles/brands/{slug}/models  → { data: PublicModel[], meta }  (by SLUG)
 *   GET /api/v1/public/vehicles/fuels                → { data: PublicFuel[],  meta }  (GLOBAL)
 *   GET /api/v1/public/vehicles/models/{slug}/fuels   → { data: PublicFuel[],  meta }  (scoped to one MODEL)
 *
 * Scope is strictly the selector's read path. Pricing (usePricingFor →
 * POST /api/v1/pricing), cart, booking and services data are unchanged.
 *
 * Reuses the existing `apiGet` (src/lib/api.ts) so base-URL resolution,
 * bearer token and error handling are identical to the rest of the app —
 * no new fetch/base config, no frontend URL manipulation.
 */
import { apiGet } from "./api";

export interface PublicBrand {
  id: number;
  name: string;
  slug: string;
  hero_image_url: string | null;
}

export interface PublicModel {
  id: number;
  name: string;
  slug: string;
  brand_id: number;
  segment?: string | null;
  hero_image_url: string | null;
}

export interface PublicFuel {
  id: number;
  name: string;
  slug: string;
  hero_image_url?: string | null;
}

interface Envelope<T> {
  data: T;
  meta?: { count: number };
}

/** GET /public/vehicles/brands — unwraps the { data } envelope. */
export async function fetchBrands(signal?: AbortSignal): Promise<PublicBrand[]> {
  const json = await apiGet<Envelope<PublicBrand[]>>("/public/vehicles/brands", undefined, signal);
  return json.data ?? [];
}

/** GET /public/vehicles/brands/{slug}/models — L1 scopes models by brand SLUG. */
export async function fetchModelsForBrand(brandSlug: string, signal?: AbortSignal): Promise<PublicModel[]> {
  const json = await apiGet<Envelope<PublicModel[]>>(
    `/public/vehicles/brands/${encodeURIComponent(brandSlug)}/models`,
    undefined,
    signal,
  );
  return json.data ?? [];
}

/** GET /public/vehicles/fuels — L1 returns a GLOBAL fuel list (not scoped). */
export async function fetchFuels(signal?: AbortSignal): Promise<PublicFuel[]> {
  const json = await apiGet<Envelope<PublicFuel[]>>("/public/vehicles/fuels", undefined, signal);
  return json.data ?? [];
}

/**
 * GET /public/vehicles/models/{slug}/fuels — MODEL_FUEL_SCOPE.
 * Only the fuels with a valid pricing combination for that model (backend
 * derives them from service_prices; falls back to the full active list if the
 * model has no pricing rows). Same PublicFuel shape as the global list.
 */
export async function fetchFuelsForModel(modelSlug: string, signal?: AbortSignal): Promise<PublicFuel[]> {
  const json = await apiGet<Envelope<PublicFuel[]>>(
    `/public/vehicles/models/${encodeURIComponent(modelSlug)}/fuels`,
    undefined,
    signal,
  );
  return json.data ?? [];
}
