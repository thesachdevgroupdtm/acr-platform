import { test, expect } from '@playwright/test';

/**
 * Phase 2.6d edge cases — cart-merge UX.
 *
 * The full guest-add → login → merge flow requires a real OTP
 * round-trip with a known phone number in the dev DB, which is
 * not stable across feature branches. This spec covers the
 * deterministic substrate that backs that flow:
 *
 *   1. A guest's `acr_cart_session` UUID is generated lazily and
 *      survives a reload (the localStorage value persists).
 *   2. The Cart page renders an empty-cart state for a guest with
 *      no items.
 *
 * Backend cart-merge correctness (last-cart-wins, empty-guest
 * preserves user) is exercised by
 * `backend/tests/Feature/EdgeCases/CartMergeTest.php` against the
 * same CartMergeService instance the frontend talks to.
 */

test('cart session UUID is generated and persists across reload', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });

  // First visit — useCart bootstraps and writes the UUID. The hook
  // generates the value lazily on the first cart op or on page mount
  // (see src/hooks/useCart.ts and src/lib/api.ts:339 which reads
  // window.localStorage.getItem('acr_cart_session')).
  //
  // Visiting /cart guarantees the cart bootstrap has run by the
  // time the page mounts.
  await page.goto('/cart', { waitUntil: 'domcontentloaded' });

  // Wait for the first cart query to settle so the UUID is written.
  await page.waitForFunction(() => {
    return window.localStorage.getItem('acr_cart_session') !== null;
  }, { timeout: 15_000 });

  const uuidBefore = await page.evaluate(() =>
    window.localStorage.getItem('acr_cart_session'),
  );
  expect(uuidBefore).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);

  // Reload — same UUID survives.
  await page.reload({ waitUntil: 'domcontentloaded' });
  const uuidAfter = await page.evaluate(() =>
    window.localStorage.getItem('acr_cart_session'),
  );
  expect(uuidAfter).toBe(uuidBefore);
});

test('empty guest cart: /cart renders an empty state without crashing', async ({ page }) => {
  // Clear any prior cart UUID to guarantee a fresh empty cart.
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    window.localStorage.removeItem('acr_cart_session');
    window.localStorage.removeItem('acr_api_token_v1');
  });

  await page.goto('/cart', { waitUntil: 'domcontentloaded' });

  // The Cart page mounts; chrome stays alive; no error boundary fires.
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });
  await expect(page.getByRole('contentinfo')).toBeVisible({ timeout: 15_000 });

  // The default empty-cart copy comes from src/pages/Cart.tsx; we
  // don't pin to specific copy here (it has changed across phases).
  // A real failure mode would be a "Page failed to load" banner from
  // the chunk error boundary — assert that's NOT visible.
  await expect(page.getByText(/page failed to load/i)).not.toBeVisible();
});
