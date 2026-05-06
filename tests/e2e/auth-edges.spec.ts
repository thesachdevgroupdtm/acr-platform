import { test, expect } from '@playwright/test';

/**
 * Phase 2.6d edge cases — auth edge cases.
 *
 * Runs under the `edges` Playwright project (Desktop Chrome,
 * dev :3000). The dev server requires the Laravel API on :8000 to
 * be running because token-corruption test triggers a /user/profile
 * call which must return 401 (the real auth-failure path) for the
 * session-expired toast event to fire.
 */

test('corrupted token in localStorage triggers SessionExpiredToast', async ({ page }) => {
  // Land on a page that does NOT immediately fire an auth-protected
  // call so we can plant the bad token before the auth bootstrap
  // runs against the API.
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // localStorage key per src/lib/api.ts:12 — "acr_api_token_v1".
  await page.evaluate(() => {
    window.localStorage.setItem('acr_api_token_v1', 'definitely-not-a-real-token');
  });

  // Navigate to a route that calls a protected endpoint
  // (/user/orders requires sanctum). The 401 returned by the API
  // triggers `acr-session-expired` which the toast listens for
  // (src/lib/api.ts:199, src/components/SessionExpiredToast.tsx:25).
  await page.goto('/booking-history', { waitUntil: 'domcontentloaded' });

  // Toast text from SessionExpiredToast.tsx:46-49.
  await expect(page.getByText('Session expired')).toBeVisible({ timeout: 10_000 });
  await expect(page.getByText('Please sign in again to continue.')).toBeVisible();

  // Toast is role="alert" — accessibility check.
  const toast = page.getByRole('alert').filter({ hasText: 'Session expired' });
  await expect(toast).toBeVisible();
});

test('AuthModal phone field caps at 10 digits and strips non-digits', async ({ page }) => {
  // Make sure we land cleanly on the home page — purge any stale
  // token from the previous test in this worker so the auth
  // bootstrap doesn't fire a 401 again.
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => window.localStorage.removeItem('acr_api_token_v1'));
  await page.reload();

  // Open the auth modal.
  const loginBtn = page.getByRole('button', { name: 'Login', exact: true });
  await expect(loginBtn).toBeVisible({ timeout: 10_000 });
  await loginBtn.click();

  await expect(page.getByText('Welcome back')).toBeVisible({ timeout: 5_000 });

  // The phone field has type="tel", inputmode="numeric", maxlength=10,
  // placeholder="10 Digits". onChange normalises to digits and slices
  // to 10 (AuthModal.tsx:359).
  const phoneInput = page.locator('input[type="tel"]').first();
  await expect(phoneInput).toBeVisible();

  // 1. The 10-digit cap. Filling with 14 digits should leave only
  //    the first 10 (the input's maxlength + onChange slice both
  //    enforce this).
  await phoneInput.fill('98765432101234');
  await expect(phoneInput).toHaveValue('9876543210');

  // 2. The non-digit strip. Clearing then typing letters should
  //    leave the input empty — the onChange replaces /\D/g with ''.
  await phoneInput.fill('');
  await phoneInput.fill('abcdef');
  await expect(phoneInput).toHaveValue('');
});
