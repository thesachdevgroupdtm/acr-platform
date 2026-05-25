import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.7 — visual record of the operator's hand-drawn final
 * blueprint after fresh rebuild. Six snapshots:
 *
 *   1. Trending Now           — full-width 5-card mosaic (LARGE center)
 *   2. Brand Service          — 1 LARGE-stacked + 3 SMALL right (in container 1)
 *   3. City Service           — 4×2 grid of equal-size cards (in container 1)
 *   4. Big Grid Dual          — 2 sub-sections side by side (in container 2)
 *   5. Service Guide          — wide LARGE+text-panel top + 3-col bottom
 *   6. Full page              — entire /explore for side-by-side mockup compare
 *
 * NO assertions — these snapshots go on disk for the operator to
 * verify against `explore-final-mockup.png`. Sections that don't
 * render (e.g. Big Grid Dual when both fallback categories are
 * absent) log a console message and pass.
 */

const SECTIONS = [
  { id: 'trending',      selector: '[data-section="trending"]',                                  name: 'Trending Now' },
  { id: 'brand-service', selector: '[data-section="brand-service"][data-category="brand-service"]', name: 'Brand Service' },
  { id: 'city-service',  selector: '[data-section="city-service"][data-category="city-service"]',   name: 'City Service' },
  { id: 'big-grid-dual', selector: '[data-section="big-grid-dual"]',                              name: 'Big Grid Dual' },
  { id: 'service-guide', selector: '[data-section="service-guide"][data-category="service-guide"]', name: 'Service Guide' },
] as const;

test.describe('Explore section screenshots (visual record)', () => {
  for (const section of SECTIONS) {
    test(`snapshots the ${section.name} section to disk`, async ({ page }) => {
      test.setTimeout(60_000);

      const payloadResp = page.waitForResponse(
        (r) => r.url().includes('/api/v1/explore') &&
               !r.url().includes('/list') &&
               !r.url().includes('/categories') &&
               r.status() === 200,
        { timeout: 30_000 }
      );

      await page.goto('/explore', { waitUntil: 'commit', timeout: 30_000 });
      await payloadResp;

      const locator = page.locator(section.selector);
      const count = await locator.count();

      if (count === 0) {
        // eslint-disable-next-line no-console
        console.log(`[phase-4-5-7] ${section.name}: section not rendered (data-state).`);
        return;
      }

      await locator.first().scrollIntoViewIfNeeded();
      await page.waitForTimeout(500);

      await locator.first().screenshot({
        path: `test-results/phase-4-5-7-${section.id}.png`,
      });
    });
  }

  test('snapshots the full /explore page for mockup comparison', async ({ page }) => {
    test.setTimeout(90_000);

    const payloadResp = page.waitForResponse(
      (r) => r.url().includes('/api/v1/explore') &&
             !r.url().includes('/list') &&
             !r.url().includes('/categories') &&
             r.status() === 200,
      { timeout: 30_000 }
    );

    await page.goto('/explore', { waitUntil: 'commit', timeout: 30_000 });
    await payloadResp;

    // Wait for the editorial root to mount + give lazy images a beat.
    await expect(page.getByTestId('explore-editorial')).toBeVisible({ timeout: 20_000 });
    await page.waitForTimeout(800);

    await page.screenshot({
      path: 'test-results/phase-4-5-7-full-page.png',
      fullPage: true,
    });
  });
});
