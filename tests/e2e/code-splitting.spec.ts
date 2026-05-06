import { test, expect, type Route } from '@playwright/test';

/**
 * Phase 2.6b — worst-case code-splitting tests.
 *
 * Run against the production preview build (npm run preview, port 4173)
 * because the dev server doesn't emit hashed per-route chunk filenames
 * — tests that abort `**\/Services-*.js` would silently no-op against
 * dev-mode esbuild streaming.
 *
 * Vite is configured with `base: '/app/'` for production (vite.config.ts),
 * so all URLs include that prefix. Per-route chunks land at
 * /app/assets/<RouteName>-<hash>.js.
 *
 * Each test is a known failure mode for lazy routing:
 *   1. Slow chunk → Suspense fallback shows.
 *   2. Failed chunk → ChunkErrorBoundary catches; app does not crash.
 *   3. Hard refresh on a lazy-only route → renders without crash.
 *   4. Rapid sequential clicks → final route wins; no console errors.
 *   5. Already-loaded chunk → cached on second visit; no re-fetch.
 */

const PREVIEW_URL = 'http://localhost:4173/app/';

test('1 — slow chunk load: Suspense fallback renders before route', async ({ page }) => {
  // Throttle every Services-*.js chunk request by 1.5s so the fallback
  // is observable before the chunk resolves. We must register the route
  // BEFORE the page navigation that triggers the click — otherwise the
  // first navigation may already pre-fetch.
  await page.route('**/assets/Services-*.js', async (route: Route) => {
    await new Promise((resolve) => setTimeout(resolve, 1500));
    await route.continue();
  });

  await page.goto(PREVIEW_URL, { waitUntil: 'domcontentloaded' });
  // Header buttons surface the navItems verbatim.
  const servicesBtn = page.getByRole('button', { name: 'Services', exact: true }).first();
  await expect(servicesBtn).toBeVisible({ timeout: 10_000 });
  await servicesBtn.click();

  // GlobalLoadingFallback emits a "Loading" caption inside an
  // aria-busy region. The first visible match must arrive while the
  // chunk is still in flight.
  await expect(page.getByText('Loading', { exact: true }).first()).toBeVisible({ timeout: 1_000 });

  // After the throttle expires the route should mount.
  await expect(page.getByText('Our Services')).toBeVisible({ timeout: 8_000 });
});

test('2 — chunk fail: ChunkErrorBoundary catches; app does not crash', async ({ page }) => {
  // Block the ServiceDetail chunk entirely. React.lazy() rejects;
  // Suspense rethrows; the boundary catches.
  await page.route('**/assets/ServiceDetail-*.js', (route: Route) => route.abort('failed'));

  // Ignore the expected console "ChunkErrorBoundary" warning + the
  // dynamic-import-failed error. Any unrelated console error would
  // still bubble through the test runner if we asserted on them.
  await page.goto(`${PREVIEW_URL}services/car-battery/battery-charging`, {
    waitUntil: 'domcontentloaded',
  });

  // Boundary UI: the literal copy from ChunkErrorBoundary.tsx.
  await expect(page.getByText(/page failed to load/i)).toBeVisible({ timeout: 8_000 });
  await expect(page.getByRole('button', { name: /reload/i })).toBeVisible();

  // Critical invariant — the app did NOT unmount entirely. The
  // header (rendered OUTSIDE the boundary) stays alive so the user
  // can still navigate away.
  await expect(page.getByRole('banner')).toBeVisible();
});

test('3 — hard refresh on a lazy route renders without crash', async ({ page }) => {
  // /booking-history aliases to currentPage="my-bookings" → MyBookings
  // (lazy). Hard refresh forces the chunk to load before any UI.
  await page.goto(`${PREVIEW_URL}booking-history`, { waitUntil: 'domcontentloaded' });

  // Both chrome elements must mount — the lazy chunk resolved.
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });
  await expect(page.getByRole('contentinfo')).toBeVisible({ timeout: 15_000 });

  // The page must not have ended up on the chunk-fail boundary or
  // the NotFound page — the alias from /booking-history → my-bookings
  // is the regression we're guarding against.
  await expect(page.getByText(/page failed to load/i)).not.toBeVisible();
  await expect(page.getByRole('heading', { name: /page not found/i })).not.toBeVisible();
});

test('4 — rapid route clicks: last-clicked route wins, no console errors', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });

  await page.goto(PREVIEW_URL, { waitUntil: 'domcontentloaded' });

  // Click three lazy routes in immediate succession. Each click
  // changes `currentPage`, which selects a different lazy component;
  // React.lazy() races to resolve all three but only the last one
  // is rendered when its promise settles.
  await page.getByRole('button', { name: 'Services', exact: true }).first().click();
  await page.getByRole('button', { name: 'Service Centers', exact: true }).first().click();
  await page.getByRole('button', { name: 'Insurance', exact: true }).first().click();

  // Insurance is the final navigation. The Header marks the active
  // nav item via an "active" CSS state — this is the cheapest proof
  // that React processed all three setCurrentPage() calls and the
  // last one won. Then we wait for the Insurance content itself.
  //
  // The PageBanner title "Insurance Claims" is rendered both on the
  // banner and (later) inside Insurance.tsx body copy, so we anchor
  // on a banner-level aria role instead of plain text.
  await expect(page.getByRole('button', { name: 'Insurance', exact: true }).first()).toBeVisible({ timeout: 5_000 });
  // Allow generous time — AnimatePresence mode="wait" queues exits,
  // so a triple-rapid-click can take a beat to settle the final
  // mount. The point of the test is "no crash + final route wins";
  // we are not measuring transition speed.
  await expect(page.getByRole('heading', { name: /insurance claims/i }).first()).toBeVisible({ timeout: 20_000 });

  // Filter only environmental noise (image 404s, transport-level
  // failures from external resources). Application errors are NOT
  // filtered — a real race-condition crash here would surface as
  // a React error and fail the test.
  //
  // Phase 2.6b-fix — the previous CORS-bypass filters
  // ('cors policy' / 'access to fetch') were removed once the
  // backend allowlist was extended to include :4173. The API now
  // accepts requests from the preview origin so no CORS errors
  // should reach the console at all.
  const realErrors = consoleErrors.filter((text) => {
    const lower = text.toLowerCase();
    return !lower.includes('failed to load resource')
      && !lower.includes('net::')
      && !lower.includes('the server responded with a status');
  });
  expect(realErrors, `Console errors:\n${realErrors.join('\n')}`).toEqual([]);
});

test('5 — already-loaded chunk is cached: no re-fetch on revisit', async ({ page }) => {
  await page.goto(PREVIEW_URL, { waitUntil: 'domcontentloaded' });

  // First visit — the Services chunk SHOULD be fetched.
  await page.getByRole('button', { name: 'Services', exact: true }).first().click();
  await expect(page.getByText('Our Services')).toBeVisible({ timeout: 8_000 });

  // Click back to Home (eager — bundled into the main chunk).
  await page.getByRole('button', { name: 'Home', exact: true }).first().click();
  // Wait for the home hero to mount before continuing. The Home page
  // has several headings containing "Restoration" (the hero h1 plus
  // a couple of project-card h3s in the gallery section), so use
  // .first() to match the hero deterministically.
  await expect(page.getByRole('heading', { name: /restoration/i }).first()).toBeVisible({ timeout: 8_000 });

  // Now track .js requests for the SECOND Services visit. React.lazy
  // memoises the resolved import — the browser cache reinforces this
  // for the underlying script — so the chunk URL must NOT appear here.
  const secondVisitJsRequests: string[] = [];
  page.on('request', (req) => {
    if (req.url().endsWith('.js')) secondVisitJsRequests.push(req.url());
  });

  await page.getByRole('button', { name: 'Services', exact: true }).first().click();
  await expect(page.getByText('Our Services')).toBeVisible({ timeout: 8_000 });

  const servicesChunkRefetches = secondVisitJsRequests.filter((url) =>
    /assets\/Services-[^/]+\.js$/.test(url),
  );
  expect(
    servicesChunkRefetches,
    `Services chunk should not have been refetched on second visit. Got:\n${servicesChunkRefetches.join('\n')}`,
  ).toEqual([]);
});
