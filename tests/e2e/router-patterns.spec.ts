import { test, expect } from '@playwright/test';

/**
 * Phase 3A — react-router-dom v7 pattern tests.
 *
 * Three deterministic invariants that the router migration unlocks:
 *
 *   1. `useLocation` reflects the current URL — the browser bar and
 *      the React tree agree after every nav.
 *   2. `useSearchParams` round-trips: a query param attached to a
 *      navigation lands on the URL and survives a reload.
 *   3. Programmatic navigation (Header buttons → useNavigate via the
 *      Phase 3A shim) updates BOTH the URL and the rendered route in
 *      lockstep — neither drifts.
 *
 * These run under the `edges` Playwright project (Vite dev :3000)
 * because they require live React rendering, not built artifacts.
 */

test('1 — useLocation: URL bar reflects the active route after click navigation', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page).toHaveURL(/\/?$/);  // home → "/" (or trailing-slash variant)

  // Programmatic-via-button navigation. The Header button calls the
  // Phase 3A shim (setCurrentPage → useNavigate("/services")).
  await page.getByRole('banner').getByRole('button', { name: 'Insurance', exact: true }).click();

  await expect(page).toHaveURL(/\/insurance$/);
  // The page rendered for the new URL. The PageBanner's heading is
  // the most stable content anchor for this route.
  await expect(
    page.getByRole('heading', { name: /insurance claims/i }).first(),
  ).toBeVisible({ timeout: 10_000 });
});

test('2 — useSearchParams: query string round-trips and survives reload', async ({ page }) => {
  // Direct URL hit with a query param. The router parses the
  // pathname → /services route; the search string lives separately.
  await page.goto('/services?source=phase3a-test&ref=playwright', {
    waitUntil: 'domcontentloaded',
  });

  // The URL bar carries the query.
  await expect(page).toHaveURL(/\/services\?source=phase3a-test&ref=playwright/);

  // The Services page mounts (lazy chunk loaded under Suspense, then
  // the PageBanner renders the canonical "Our Services" title).
  await expect(page.getByText('Our Services')).toBeVisible({ timeout: 10_000 });

  // Confirm the URLSearchParams API agrees with the bar — this is
  // the actual contract that useSearchParams() reads from.
  const params = await page.evaluate(() => {
    const sp = new URLSearchParams(window.location.search);
    return { source: sp.get('source'), ref: sp.get('ref') };
  });
  expect(params).toEqual({ source: 'phase3a-test', ref: 'playwright' });

  // Reload — query persists end-to-end (browser history + router
  // basename handling + Vite dev SPA fallback all agree).
  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page).toHaveURL(/\/services\?source=phase3a-test&ref=playwright/);
});

test('3 — programmatic navigate(): URL and rendered route update in lockstep', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // Three distinct destinations in sequence. After each click both
  // the URL and the page content must agree — no drift in either
  // direction is the whole point of route-as-source-of-truth.
  const sequence: { btn: string; expectedPath: RegExp; expectedHeading: RegExp }[] = [
    { btn: 'Gallery',    expectedPath: /\/gallery$/,    expectedHeading: /gallery/i },
    { btn: 'Corporate',  expectedPath: /\/corporate$/,  expectedHeading: /corporate/i },
    { btn: 'Services',   expectedPath: /\/services$/,   expectedHeading: /our services/i },
  ];

  for (const step of sequence) {
    await page.getByRole('banner').getByRole('button', { name: step.btn, exact: true }).click();
    await expect(page).toHaveURL(step.expectedPath);
    await expect(
      page.getByRole('heading', { name: step.expectedHeading }).first(),
    ).toBeVisible({ timeout: 10_000 });
  }

  // Browser back is the strongest test that the router is the
  // source of truth — popstate should ship us back to /corporate
  // AND re-render the corresponding page.
  await page.goBack();
  await expect(page).toHaveURL(/\/corporate$/);
  await expect(
    page.getByRole('heading', { name: /corporate/i }).first(),
  ).toBeVisible({ timeout: 10_000 });
});
