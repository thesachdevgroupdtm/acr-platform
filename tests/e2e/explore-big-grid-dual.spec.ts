import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.10 — Big Grid Dual section dedicated spec.
 *
 * - Snapshots the section to disk for visual review against the
 *   operator's reference images.
 * - Asserts both sub-sections render with at least 3 visible cards
 *   each (1 featured + 2 children), proving the spec D-4.5.10-6
 *   fallback chain pads the layout when the named categories
 *   (maintenance-tips / comparison) are absent from the payload.
 */
test.describe('Explore Big Grid Dual', () => {
  test('renders 2 sub-sections each with at least 3 visible cards', async ({ page }) => {
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

    const section = page.locator('[data-section="big-grid-dual"]');
    await expect(section).toBeVisible({ timeout: 15_000 });
    await section.scrollIntoViewIfNeeded();
    await page.waitForTimeout(400);

    // Both sub-sections (left = thumb-rows, right = grid-2x2).
    const left  = section.locator('[data-section="big-grid"][data-variant="thumb-rows"]');
    const right = section.locator('[data-section="big-grid"][data-variant="grid-2x2"]');

    await expect(left).toHaveCount(1);
    await expect(right).toHaveCount(1);

    // Left has featured + N thumb-rows; right has featured + N grid cells.
    // Min threshold per spec D-4.5.10-6: ≥2 cards per sub-section.
    // We assert ≥3 (featured + 2 children) as a sturdier proof that the
    // fallback chain padded correctly.
    const leftCount = await left.locator('[data-testid^="big-grid-feature-"], [data-testid^="big-grid-row-"]').count();
    const rightCount = await right.locator('[data-testid^="big-grid-feature-"], [data-testid^="big-grid-cell-"]').count();

    expect(leftCount).toBeGreaterThanOrEqual(3);
    expect(rightCount).toBeGreaterThanOrEqual(3);

    // Snapshot for visual comparison against big-grid-reference.png.
    await section.screenshot({
      path: 'test-results/phase-4-5-10-big-grid-dual.png',
    });
  });

  test('full /explore snapshot for section-order verification', async ({ page }) => {
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

    await expect(page.getByTestId('explore-editorial')).toBeVisible({ timeout: 20_000 });
    await page.waitForTimeout(800);

    await page.screenshot({
      path: 'test-results/phase-4-5-10-full-page.png',
      fullPage: true,
    });
  });
});
