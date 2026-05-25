import { test, expect } from '@playwright/test';

/**
 * Phase 2.6d edge cases — coupon UX (read-only / no-auth subset).
 *
 * The full apply → use → remove flow requires an authenticated user
 * with a non-empty cart, which transitively requires a deterministic
 * dev DB user (see journey.spec.ts notes). These tests cover the
 * deterministic surface area only:
 *
 *   1. The /coupons marketing page renders the seeded coupons
 *      (FIRST10, ACCOOL20, SAVER15) from the
 *      2026_05_05_120001_create_coupons_table.php migration.
 *   2. Hitting /coupons via lazy navigation does not crash — the
 *      Coupons chunk loads and mounts under Suspense.
 *
 * Backend validate() reasons (per-user limit, min order, expiry,
 * stale auto-clear) are pinned by
 * `backend/tests/Feature/EdgeCases/CouponEdgeCasesTest.php`.
 */

test('Coupons page renders at least one of the seeded coupon codes', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // The Coupons route is gated under the "More" dropdown on
  // desktop (Header.tsx navItems). Hit it directly via URL — the
  // pseudo-router parses /coupons → currentPage='coupons'.
  await page.goto('/coupons', { waitUntil: 'domcontentloaded' });

  // Phase 4.2.5 — the original test asserted all three seeded codes
  // (FIRST10/ACCOOL20/SAVER15) verbatim. With Filament admin live
  // (Phase 4.2), operators legitimately add/deactivate coupons via
  // the admin panel, which causes brittle equality with the seed
  // set to flake. Robust contract: at least one of the canonical
  // seeded codes is visible AND no error UI surfaces.
  await expect(page.getByText(/couldn't load coupons/i)).toHaveCount(0);

  // Wait for the coupons list to settle. Any of the canonical seeded
  // codes appearing first wins; we poll up to 15s to absorb chunk
  // load + initial /coupons fetch on a cold cache.
  const candidates = ['FIRST10', 'ACCOOL20', 'SAVER15'];
  await expect
    .poll(
      async () => {
        for (const code of candidates) {
          if (await page.getByText(code).first().isVisible().catch(() => false)) {
            return code;
          }
        }
        return null;
      },
      { timeout: 15_000, message: 'Expected one of the seeded coupon codes on /coupons' }
    )
    .not.toBeNull();
});

test('navigating to /coupons via the More dropdown does not crash', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });

  // Hover the "More" dropdown to surface its sub-items. Scope the
  // Coupons button lookup to the header navigation so we don't
  // collide with the footer's "Coupons" quick-link button (both
  // exist by design — strict mode would flag them otherwise).
  const headerNav = page.getByRole('navigation').first();
  await page.getByRole('button', { name: 'More', exact: true }).first().hover();

  const couponsLink = headerNav.getByRole('button', { name: 'Coupons', exact: true });
  await expect(couponsLink).toBeVisible({ timeout: 5_000 });
  await couponsLink.click();

  // Page loaded, header still mounted, no chunk-error banner.
  await expect(page.getByText('FIRST10').first()).toBeVisible({ timeout: 10_000 });
  await expect(page.getByText(/page failed to load/i)).not.toBeVisible();
});
