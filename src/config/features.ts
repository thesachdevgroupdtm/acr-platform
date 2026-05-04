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
  /**
   * Phase 2.4 — server-side cart merge protocol live; client and
   * server cart stay synchronized through the OTP-verify hook
   * (X-Cart-Session header → server-side merge before token issue)
   * and the explicit POST /cart/merge endpoint for the multi-device
   * / re-merge case. Set true now that the protocol ships.
   */
  cartSync: true,
  /**
   * Phase 2.5a — real /checkout/place-order endpoint shipped. Was
   * `false` from 2.1 onward as a forward-declared key; flipping it
   * to `true` activates the real backend for the place-order flow.
   * Stays true unless we need to dark-launch a regression.
   */
  offlineCheckout: true,
  /**
   * Phase 2.3.4 — restored to original client-side flow. The
   * pre-2.3.2 Checkout → Payment → BookingConfirmation experience
   * is the production launch path until Phase 2.5 swaps the fake
   * /checkout/place-order call for a real one. The 2.3.2 gate
   * served its purpose during diagnostics and is kept as a key so
   * Phase 2.5 can flip it back briefly during a partial rollout
   * if needed.
   */
  checkoutFlow: true,
  /**
   * Phase 2.3.4 — restored alongside checkoutFlow. MyBookings
   * renders the existing two-column user-profile + bookings-history
   * layout. The bookings list itself is empty pre-2.5 (no
   * persistence) which matches the pre-2.3.2 behavior.
   */
  bookingsList: true,
  /**
   * Phase 2.5a — coupon UI hidden. The /cart/coupon endpoints still
   * 501; the orders.coupon_id column exists but is always null.
   * Phase 2.5b lights up coupon math + UI in one go.
   */
  couponsLit: false,
  /**
   * Phase 2.5a — payments are placeholder ("Pay at Service Center"
   * / cash_at_center). Real gateway (Razorpay/UPI) lands in Phase 4+.
   */
  paymentGateway: false,
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
