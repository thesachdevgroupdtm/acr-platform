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
  /**
   * Phase 2.1 (this commit) wires the OTP-based auth surface:
   *   /auth/lead-capture, /auth/send-otp, /auth/verify-otp,
   *   /auth/login, /auth/logout, /user/profile (GET, PUT).
   * Address endpoints land in 2.2 — auth=true alone is sufficient
   * for the login flow.
   */
  auth: true,
  /** /cart/* (server-authoritative cart) lands in Phase 2.3. */
  cartSync: false,
  /** /checkout/place-order lands in Phase 2.5. */
  offlineCheckout: false,
  /**
   * Phase 2.3.2 — gates the existing client-side fake Checkout +
   * Payment flow until Phase 2.5 ships /checkout/place-order. With
   * this off, Checkout/Payment render a "coming soon" notice with
   * the cart summary and a Call Now CTA instead of generating a
   * fake `ACR<timestamp>` invoice that no backend ever sees.
   */
  checkoutFlow: false,
  /**
   * Phase 2.3.2 — gates MyBookings until /user/orders ships. With
   * this off, MyBookings shows a "coming soon" notice next to the
   * user's profile card instead of "0 BOOKINGS" (which read from
   * the unwired AcrUser.bookings array).
   */
  bookingsList: false,
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
