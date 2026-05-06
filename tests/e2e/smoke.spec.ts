import { test, expect } from '@playwright/test';

/**
 * Phase 2.6c — frontend smoke tests.
 *
 * Scope: prove the React app boots, renders the home page without
 * console errors, surfaces the auth modal on the Login click, and
 * routes /payment to NotFound (regression guard for the Phase 2.6a
 * payment-page removal).
 *
 * Network failures from the dev API (CORS, 404 on /api/v1/home if
 * the Laravel server is not seeded) are tolerated: the home page is
 * expected to render skeletons even when the home payload is empty.
 * Only console *errors* originating from the React runtime fail
 * the test.
 */

test('home page renders without console errors', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });

  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // Header is the most stable mount point — it renders unconditionally.
  await expect(page.getByRole('banner')).toBeVisible();

  // Footer mounts after the page has hydrated.
  await expect(page.getByRole('contentinfo')).toBeVisible({ timeout: 10_000 });

  // Filter out network-related noise (failed XHRs, image 404s) — those
  // are environmental, not React errors.
  const reactErrors = consoleErrors.filter((text) => {
    const lower = text.toLowerCase();
    return !lower.includes('failed to load resource')
      && !lower.includes('net::')
      && !lower.includes('the server responded with a status');
  });

  expect(reactErrors, `Console errors:\n${reactErrors.join('\n')}`).toEqual([]);
});

test('clicking the Login button opens the auth modal', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // Header renders the Login button as <button> with text "Login"
  // (uppercased via CSS, raw text is "Login").
  const loginBtn = page.getByRole('button', { name: 'Login', exact: true });
  await expect(loginBtn).toBeVisible({ timeout: 10_000 });
  await loginBtn.click();

  // The AuthModal renders "Welcome back" as its title for the login tab.
  await expect(page.getByText('Welcome back')).toBeVisible({ timeout: 5_000 });

  // The phone input is the first field on stage 1.
  await expect(page.getByText('Phone (10 digits)')).toBeVisible();
});

test('/payment routes to NotFound (no silent home redirect)', async ({ page }) => {
  await page.goto('/payment', { waitUntil: 'domcontentloaded' });

  // NotFound page surfaces a "404" string and a "Page not found" heading
  // (per src/pages/NotFound.tsx). Either is enough to confirm we did NOT
  // silently land on Home (which would show "Multi-Brand Car Service…").
  const notFoundIndicator = page.getByText(/404|Page not found|page (?:you[’']?re looking for|doesn[’']?t exist)/i);
  await expect(notFoundIndicator.first()).toBeVisible({ timeout: 10_000 });
});
