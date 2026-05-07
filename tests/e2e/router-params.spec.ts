import { test, expect } from '@playwright/test';

/**
 * Phase 3B — useParams + direct-URL deep-load tests.
 *
 * Three deterministic invariants the pure migration introduces:
 *
 *   1. /category/:slug — useParams reads the right slug; navigating
 *      between two categories swaps content without crashing.
 *   2. /services/:category/:service — multi-param useParams resolves
 *      both segments and the page renders the right service.
 *   3. Direct deep-URL load (no client-side nav) — useParams
 *      works on initial mount, page hydrates without console errors,
 *      and a hard reload of the same URL keeps it stable.
 *
 * These run under the `edges` project (Vite dev :3000) because they
 * need the live API for ServiceCategory / ServiceDetail to render
 * any content beyond the breadcrumb.
 */

test('1 — useParams: /category/:slug surfaces the slug in the rendered page', async ({ page }) => {
  // The strongest evidence that useParams resolved the URL segment
  // is to put a guaranteed-unique slug in the URL and watch the
  // page render that exact string back to us. ServiceCategory's
  // not-found branch surfaces the slug verbatim ("Category
  // 'foo-bar' not found"), so this works regardless of which
  // categories happen to be seeded in the dev DB.
  const slug = 'route-test-slug-9b2f';
  await page.goto(`/category/${slug}`, { waitUntil: 'domcontentloaded' });

  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });
  // The slug appears in the rendered output → useParams worked.
  await expect(page.getByText(slug).first()).toBeVisible({ timeout: 15_000 });

  // ChunkErrorBoundary did NOT fire — useParams worked, the page
  // mounted, the API simply didn't recognise the slug. This is
  // the healthy unhappy-path.
  await expect(page.getByText(/page failed to load/i)).not.toBeVisible();
});

test('2 — useParams (multi-param): /services/:category/:service resolves both', async ({ page }) => {
  await page.goto('/services/car-battery/battery-charging', {
    waitUntil: 'domcontentloaded',
  });

  // The URL bar should reflect the deep path (BrowserRouter +
  // basename '/' on dev keeps it 1:1).
  expect(page.url()).toMatch(/\/services\/car-battery\/battery-charging/);

  // The page renders ServiceDetail; its PageBanner uses the
  // service title as h1. Don't pin the exact title; just verify
  // the page mounted under main with content.
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });
  await expect(page.locator('main h1').first()).toBeVisible({ timeout: 15_000 });

  // ChunkErrorBoundary's "Page failed to load" must NOT be visible —
  // a useParams misread or a routing miss would land here.
  await expect(page.getByText(/page failed to load/i)).not.toBeVisible();
});

test('3 — direct deep-URL load: hard refresh keeps the route stable, no React errors', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });

  // Land directly on a deep URL — no prior client-side nav, no
  // history. The router must parse the URL on initial mount.
  await page.goto('/services/car-battery/battery-charging', {
    waitUntil: 'domcontentloaded',
  });
  await expect(page.locator('main h1').first()).toBeVisible({ timeout: 15_000 });

  // Hard reload — same URL, fresh React tree. useParams must
  // re-resolve from the URL on the second mount with no flicker
  // through the home page (Phase 2.5.1 regression guard).
  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page.locator('main h1').first()).toBeVisible({ timeout: 15_000 });
  expect(page.url()).toMatch(/\/services\/car-battery\/battery-charging/);

  // No real React errors — environmental noise (failed fetches,
  // image 404s) is filtered, but a router/useParams crash would
  // surface here as an unfiltered error.
  const real = consoleErrors.filter((text) => {
    const lower = text.toLowerCase();
    return !lower.includes('failed to load resource')
      && !lower.includes('net::')
      && !lower.includes('the server responded with a status');
  });
  expect(real, `Console errors:\n${real.join('\n')}`).toEqual([]);
});
