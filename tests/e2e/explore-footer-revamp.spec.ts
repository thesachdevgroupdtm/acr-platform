import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.4 — ExploreInternalLinks 3-column footer revamp.
 *
 * Replaces the Phase 4.5 2-column footer (categories + chips) with
 * a 3-col rich layout: Browse-by-Category w/ icons + Popular
 * Searches chips + Why-ACR stats card with CTA.
 */
test.describe('Explore footer revamp', () => {
  test('explore footer renders 3 columns with category list, popular chips, and stats card', async ({ page }) => {
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

    // Scroll to bottom so the footer is in view (lazy assets etc.).
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

    // Section container.
    const section = page.locator('[data-section="internal-links"]');
    await expect(section).toBeVisible({ timeout: 15_000 });

    // Three columns.
    await expect(page.getByTestId('footer-categories')).toBeVisible({ timeout: 10_000 });
    await expect(page.getByTestId('footer-popular')).toBeVisible({ timeout: 10_000 });
    await expect(page.getByTestId('footer-stats')).toBeVisible({ timeout: 10_000 });

    // Category list has at least 1 entry (icon + name + chevron),
    // popular has at least 1 chip, stats card has the CTA.
    await expect(page.locator('[data-testid^="internal-cat-"]').first()).toBeVisible();
    await expect(page.locator('[data-testid^="internal-link-"]').first()).toBeVisible();
    await expect(page.getByTestId('footer-cta-estimate')).toBeVisible();

    // Stats card shows "Why ACR?" heading.
    await expect(page.getByTestId('footer-stats')).toContainText(/why acr\?/i);
  });

  test('clicking a category in footer navigates to /explore?category', async ({ page }) => {
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

    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

    const firstCatLink = page.locator('[data-testid^="internal-cat-"]').first();
    await expect(firstCatLink).toBeVisible({ timeout: 15_000 });

    // Read the testid first to know what slug to expect in the URL.
    const testId = await firstCatLink.getAttribute('data-testid');
    const expectedSlug = testId?.replace(/^internal-cat-/, '') ?? '';
    expect(expectedSlug).not.toBe('');

    const filteredResp = page.waitForResponse(
      (r) => r.url().includes('/api/v1/explore') &&
             r.url().includes(`category=${encodeURIComponent(expectedSlug)}`) &&
             r.status() === 200,
      { timeout: 30_000 }
    );

    await firstCatLink.click();
    await filteredResp;

    await expect.poll(() => {
      const u = new URL(page.url());
      return u.searchParams.get('category');
    }, { timeout: 10_000 }).toBe(expectedSlug);
  });
});
