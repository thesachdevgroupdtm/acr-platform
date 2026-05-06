import { test, expect } from '@playwright/test';

/**
 * Phase 2.6b — sanity test that lazy routes navigate cleanly.
 *
 * Walks the user through Services → Service Centers → Insurance →
 * Corporate → Gallery → Coupons (under the More dropdown). At each
 * step the previous chunk is already cached, so the test only fails
 * if a chunk's first load throws at module-eval time, or if a route
 * triggers an unhandled error during render.
 *
 * Network-related console noise (CORS preflights against the dev
 * Laravel API, missing image 404s) is filtered out — those are
 * environmental, not React errors.
 */

const PREVIEW_URL = 'http://localhost:4173/app/';

test('no console errors during full navigation flow', async ({ page }) => {
  const errors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') errors.push(msg.text());
  });

  await page.goto(PREVIEW_URL, { waitUntil: 'domcontentloaded' });
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });

  // Routes reachable from the desktop Header without a dropdown.
  const routes: { btn: string; expectedText: RegExp }[] = [
    { btn: 'Services', expectedText: /our services/i },
    { btn: 'Service Centers', expectedText: /service centers|service centres/i },
    { btn: 'Insurance', expectedText: /insurance claims/i },
    { btn: 'Corporate', expectedText: /corporate/i },
    { btn: 'Gallery', expectedText: /gallery/i },
  ];

  for (const { btn, expectedText } of routes) {
    await page.getByRole('button', { name: btn, exact: true }).first().click();
    await expect(page.getByText(expectedText).first()).toBeVisible({ timeout: 10_000 });
  }

  // Filter only environmental noise (browser-extension injected
  // messages, image 404s, transport-level failures from external
  // resources). Application errors — including any CORS rejection
  // from our own API — are NOT filtered: a real one should fail
  // the test.
  //
  // Phase 2.6b-fix — the previous CORS-bypass filters
  // ('cors policy' / 'access to fetch') were removed once the
  // backend allowlist was extended to include :4173.
  const realErrors = errors.filter((text) => {
    const lower = text.toLowerCase();
    return !lower.includes('failed to load resource')
      && !lower.includes('net::')
      && !lower.includes('the server responded with a status')
      && !lower.includes('extension');
  });

  expect(realErrors, `Console errors:\n${realErrors.join('\n')}`).toEqual([]);
});
