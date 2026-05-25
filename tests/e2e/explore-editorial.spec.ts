import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.1 — /explore editorial integration.
 *
 * Phase 4.5.8 — the hero ExploreFeaturedGrid was REMOVED because
 * it duplicated the Trending Now mosaic just below it (same 5-card
 * layout pulling from the same overlay-image pool). The Phase 4.5
 * carousel was already deleted in 4.5.1 — this test now asserts
 * BOTH the carousel testid AND the featured-grid testid are absent
 * from /explore. The trending-mosaic + search + click-navigation
 * tests below cover the editorial pipeline.
 */
test.describe('Explore editorial', () => {
  test('hero block is absent (no carousel, no featured grid)', async ({ page }) => {
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

    // Trending Now must be the first editorial mosaic on the page.
    await expect(page.getByTestId('explore-trending-grid')).toBeVisible({ timeout: 15_000 });

    // Old hero surfaces must NOT render anymore.
    await expect(page.getByTestId('explore-hero')).toHaveCount(0);
    await expect(page.getByTestId('explore-hero-dots')).toHaveCount(0);
    await expect(page.getByTestId('explore-featured-grid')).toHaveCount(0);
  });

  test('search filters cards client-side and highlights matches', async ({ page }) => {
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

    const input = page.getByTestId('explore-search-input');
    await expect(input).toBeVisible({ timeout: 10_000 });

    await input.click();
    await input.fill('audi');

    const dropdown = page.getByTestId('explore-search-dropdown');
    await expect(dropdown).toBeVisible({ timeout: 10_000 });

    await expect(dropdown.locator('mark').first()).toBeVisible({ timeout: 5_000 });
  });

  test('clicking a trending card navigates to /:slug', async ({ page }) => {
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

    const grid = page.getByTestId('explore-trending-grid');
    await expect(grid).toBeVisible({ timeout: 15_000 });

    // Phase 4.5.6 — trending grid is now a 5-card mosaic (1 LARGE
    // center + up to 4 SMALL flanking, items[2] is the LARGE per
    // operator's hand-drawn blueprint). Render is capped at 5
    // with graceful 1/2/3/4-card degradation.
    const trendingCount = await grid.locator('[data-testid^="trending-card-"]').count();
    expect(trendingCount).toBeGreaterThanOrEqual(1);
    expect(trendingCount).toBeLessThanOrEqual(5);
    await expect(grid.locator('[data-slot="trending-large"]')).toHaveCount(1);

    const firstCard = grid.locator('[data-testid^="trending-card-"]').first();
    const testid = await firstCard.getAttribute('data-testid');
    const expectedSlug = testid?.replace(/^trending-card-/, '') ?? '';
    expect(expectedSlug).not.toBe('');

    await firstCard.click();

    await expect.poll(async () => {
      const path = new URL(page.url()).pathname;
      return path === '/' + expectedSlug;
    }, { timeout: 10_000 }).toBe(true);
  });
});
