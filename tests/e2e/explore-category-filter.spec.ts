import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.1 — clicking "View All" on a category section
 * navigates to /explore?category={slug} and the same
 * ExploreEditorial component re-renders with the filter applied.
 */
test.describe('Explore category filter', () => {
  test('clicking View All on a category navigates with ?category and updates the page', async ({ page }) => {
    test.setTimeout(60_000);

    const firstPayload = page.waitForResponse(
      (r) => r.url().includes('/api/v1/explore') &&
             !r.url().includes('/list') &&
             !r.url().includes('/categories') &&
             r.status() === 200,
      { timeout: 30_000 }
    );

    await page.goto('/explore', { waitUntil: 'commit', timeout: 30_000 });
    await firstPayload;

    // Find the FIRST category section's View All link. The link's
    // testid encodes the slug — read it before clicking so we
    // know what URL/payload to expect.
    const viewAllLink = page.locator('a[data-testid^="explore-category-viewall-"]').first();
    await expect(viewAllLink).toBeVisible({ timeout: 15_000 });

    const linkTestId = await viewAllLink.getAttribute('data-testid');
    const expectedSlug = linkTestId?.replace(/^explore-category-viewall-/, '') ?? '';
    expect(expectedSlug).not.toBe('');

    // Set up the filtered-payload listener BEFORE clicking.
    const filteredPayload = page.waitForResponse(
      (r) => r.url().includes('/api/v1/explore') &&
             r.url().includes(`category=${encodeURIComponent(expectedSlug)}`) &&
             r.status() === 200,
      { timeout: 30_000 }
    );

    await viewAllLink.click();
    await filteredPayload;

    // URL carries the new query string.
    await expect.poll(() => {
      const u = new URL(page.url());
      return u.searchParams.get('category');
    }, { timeout: 10_000 }).toBe(expectedSlug);

    // Filter chip surfaces — only visible when ?category is set.
    const chip = page.getByTestId('category-filter-chip');
    await expect(chip).toBeVisible({ timeout: 10_000 });
  });
});
