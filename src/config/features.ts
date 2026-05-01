/**
 * Feature flags for surfaces whose backend doesn't exist yet.
 *
 * The canonical /api/v1/* surface (per AUDIT_REPORT.md §4) covers home,
 * services, vehicle picker, pricing, pages, and CSV import. Auth, user
 * profile, addresses, cart sync, and offline checkout have no backend
 * route — calls to them return 404 today.
 *
 * Setting a flag to `false` makes the corresponding hook return a stable
 * "feature disabled" state without issuing a network request, and tells
 * consumer UI to render a coming-soon / hidden control instead of a
 * broken form.
 *
 * Flip a flag to `true` only AFTER the matching backend route is in
 * production and the typed fetcher has been added to src/lib/api.ts.
 */
export const FEATURES = {
  /** /auth/login, /auth/register, /auth/logout, /auth/profile, /user/profile, /user/addresses */
  auth: false,
  /** /cart/sync — best-effort server mirror of the local cart */
  cartSync: false,
  /** /checkout/offline — turn cart into an order (Pay-on-service flow) */
  offlineCheckout: false,
} as const;

export type FeatureFlag = keyof typeof FEATURES;

/** Sentinel error thrown by gated mutation paths. UI should `instanceof`-check. */
export class FeatureDisabledError extends Error {
  feature: FeatureFlag;
  constructor(feature: FeatureFlag, message?: string) {
    super(message ?? `Feature "${feature}" is disabled in this build.`);
    this.feature = feature;
    this.name = "FeatureDisabledError";
  }
}
