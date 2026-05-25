/**
 * Vehicle picker hooks. Strict order: brand → model → fuel.
 *
 *   useBrands()                  — always enabled
 *   useModels(brandId)           — disabled until brandId is set
 *   useFuels(modelSlug)          — disabled until a model is picked
 *
 * SELECTOR-CONVERGENCE: these now read the **L1 public** endpoints
 * (/api/v1/public/vehicles/*) instead of the legacy /vehicle/* ones.
 * L1 returns fully-qualified `hero_image_url`s, fixing the broken selector
 * images (legacy returned relative paths that 404'd on the frontend origin).
 *
 * Public API is UNCHANGED on purpose — same hook signatures and the same
 * `{ success, brands|models|fuels: [...] }` return shape — so every consumer
 * keeps working without edits:
 *   - the vehicle-selector grids (Brand/Model/Fuel),
 *   - EstimateProcess (useBrands + useModels(brandId)),
 *   - ServiceCategory (useBrands, reads brand.title).
 * The adapter maps L1's `hero_image_url` → the existing `image` field, so the
 * selector renders the full URL with no component change (no frontend URL
 * manipulation — the value is used verbatim).
 *
 * Shape differences bridged (D-CONV-3/4):
 *   - L1 scopes models by brand SLUG, but callers pass a numeric brandId —
 *     we resolve the slug from the cached brand list (useBrands).
 *   - Fuels are scoped to the picked MODEL (MODEL_FUEL_SCOPE): useFuels takes
 *     the model SLUG and reads /public/vehicles/models/{slug}/fuels, which
 *     returns only the fuels with a valid pricing combination for that model
 *     (falling back to the full active list if the model has no pricing).
 *     The query is disabled until a model slug is present.
 *
 * Pricing (usePricingFor → POST /api/v1/pricing) is untouched: it uses the
 * numeric ids stored in booking context, which are the same DB ids L1 returns.
 *
 * Caching: staleTime 5 min on every hook; keepPreviousData on useBrands ONLY
 * (stable top-level list — avoids a first-open flash). Removed from models/
 * fuels so a parent change shows a skeleton, not the previous parent's
 * children (FORMS-1 D-5 brand-bleed fix).
 */
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import type {
  BrandsResponse,
  CarBrand,
  CarModel,
  FuelsResponse,
  FuelType,
  ModelsResponse,
} from "../lib/api";
import {
  fetchBrands as fetchL1Brands,
  fetchFuelsForModel,
  fetchModelsForBrand,
  type PublicBrand,
  type PublicFuel,
  type PublicModel,
} from "../lib/publicVehicles";

const VEHICLE_STALE_MS = 5 * 60 * 1000; // 5 minutes

// ── L1 → legacy shape adapters. `name` fills both `title` (legacy key the
//    components read) and `name`; `hero_image_url` (full URL) fills `image`.
const toBrand = (b: PublicBrand): CarBrand => ({
  id: b.id,
  slug: b.slug,
  title: b.name,
  name: b.name,
  image: b.hero_image_url ?? null,
});

const toModel = (m: PublicModel): CarModel => ({
  id: m.id,
  brand_id: m.brand_id,
  slug: m.slug,
  title: m.name,
  name: m.name,
  segment: m.segment ?? null,
  image: m.hero_image_url ?? null,
});

const toFuel = (f: PublicFuel): FuelType => ({
  id: f.id,
  slug: f.slug,
  title: f.name,
  name: f.name,
  image: f.hero_image_url ?? null,
});

export function useBrands() {
  return useQuery<BrandsResponse>({
    queryKey: ["public-vehicles", "brands"],
    queryFn: async ({ signal }) => ({
      success: true,
      brands: (await fetchL1Brands(signal)).map(toBrand),
    }),
    staleTime: VEHICLE_STALE_MS,
    placeholderData: keepPreviousData,
  });
}

export function useModels(brandId: number | null | undefined) {
  // L1 scopes models by brand slug; resolve it from the cached brand list.
  const { data: brandsData } = useBrands();
  const brandSlug =
    typeof brandId === "number" && brandId > 0
      ? brandsData?.brands.find((b) => b.id === brandId)?.slug ?? null
      : null;

  return useQuery<ModelsResponse>({
    queryKey: ["public-vehicles", "models", brandSlug],
    queryFn: async ({ signal }) => ({
      success: true,
      models: (await fetchModelsForBrand(brandSlug as string, signal)).map(toModel),
    }),
    enabled: !!brandSlug,
    staleTime: VEHICLE_STALE_MS,
    // No keepPreviousData: on a brand change the model grid shows a skeleton,
    // not the previous brand's models.
  });
}

export function useFuels(modelSlug: string | null | undefined) {
  return useQuery<FuelsResponse>({
    // Scoped per model — keyed by slug so each model caches independently
    // and a model change refetches the right fuel list (no brand-bleed).
    queryKey: ["public-vehicles", "fuels", modelSlug ?? null],
    queryFn: async ({ signal }) => ({
      success: true,
      fuels: (await fetchFuelsForModel(modelSlug as string, signal)).map(toFuel),
    }),
    enabled: typeof modelSlug === "string" && modelSlug.length > 0,
    staleTime: VEHICLE_STALE_MS,
  });
}
