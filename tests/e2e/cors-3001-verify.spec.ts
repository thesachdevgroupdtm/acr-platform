import { test, expect } from '@playwright/test';

/**
 * Phase 4.2.5b — verify the CORS fix from a browser pointed at
 * Vite's fallback port :3001. This is the operator's actual
 * environment when :3000 is already bound by a stale dev process.
 *
 * Without the cors.php pattern fix, the browser preflight from
 * Origin: http://localhost:3001 returned 204 with NO
 * Access-Control-Allow-Origin header → browser blocked the
 * fetch → useQuery errored → operator saw "Couldn't load coupons"
 * / "Could not load services" / "API: Failed to fetch".
 *
 * With the fix, the requests succeed and content renders.
 *
 * Pattern: don't use waitForResponse as a precondition (Vite chunk
 * loads can outrun a 15s budget under load); instead navigate and
 * poll for the actual rendered output. CORS errors would surface
 * either as console errors (caught by `failedCors`) OR as the
 * page rendering its error UI (caught by the success-path
 * assertions below).
 */
test('Browser at :3001 successfully fetches /api/v1/coupons (CORS allows fallback port)', async ({ page }) => {
  test.setTimeout(60_000);
  const failedCors: string[] = [];
  const failedApi: Array<{ url: string; status: number }> = [];

  page.on('console', (msg) => {
    const t = msg.text().toLowerCase();
    if (t.includes('cors') || t.includes('failed to fetch') || t.includes('access-control')) {
      failedCors.push(msg.text());
    }
  });
  page.on('response', (res) => {
    if (res.url().includes('/api/v1/') && res.status() >= 400 && res.status() !== 429) {
      failedApi.push({ url: res.url(), status: res.status() });
    }
  });

  await page.goto('http://localhost:3001/coupons', {
    waitUntil: 'commit',
    timeout: 30_000,
  });

  // Error UI must NOT be visible at any point during the load.
  // Poll the success path AND check for the failure UI in the same
  // window — whichever resolves first answers the question.
  const candidates = ['FIRST10', 'ACCOOL20', 'SAVER15', 'ATUL500'];
  await expect
    .poll(
      async () => {
        for (const code of candidates) {
          if (await page.getByText(code).first().isVisible().catch(() => false)) return code;
        }
        return null;
      },
      {
        timeout: 30_000,
        message: 'Expected at least one seeded coupon code to render at :3001 (CORS fix verified)',
      }
    )
    .not.toBeNull();

  expect(failedCors, `CORS/network errors in console: ${failedCors.join(' | ')}`).toEqual([]);
  expect(failedApi, `API failures: ${JSON.stringify(failedApi)}`).toEqual([]);
  await expect(page.getByText(/couldn't load coupons/i)).toHaveCount(0);
});

test('Browser at :3001 successfully fetches /api/v1/services and /api/v1/home', async ({ page }) => {
  test.setTimeout(60_000);
  const failedCors: string[] = [];
  const failedApi: Array<{ url: string; status: number }> = [];

  page.on('console', (msg) => {
    const t = msg.text().toLowerCase();
    if (t.includes('cors') || t.includes('failed to fetch') || t.includes('access-control')) {
      failedCors.push(msg.text());
    }
  });
  page.on('response', (res) => {
    if (res.url().includes('/api/v1/') && res.status() >= 400 && res.status() !== 429) {
      failedApi.push({ url: res.url(), status: res.status() });
    }
  });

  await page.goto('http://localhost:3001/services', {
    waitUntil: 'commit',
    timeout: 30_000,
  });

  // Header services dropdown trigger must be present (proves header
  // rendered with categories from /home — the bug operator reported).
  await expect(page.getByRole('button', { name: /^services$/i }).first()).toBeVisible({
    timeout: 30_000,
  });

  expect(failedCors, `CORS errors: ${failedCors.join(' | ')}`).toEqual([]);
  expect(failedApi, `API failures: ${JSON.stringify(failedApi)}`).toEqual([]);
});
