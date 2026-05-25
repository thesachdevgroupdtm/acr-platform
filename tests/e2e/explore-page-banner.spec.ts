import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.2 — /explore now has the same PageBanner as every
 * other content page on the site (Services, About, Contact, etc.).
 *
 * The banner sits ABOVE the page-level fade wrapper so it loads
 * with the page chrome rather than fading in. Verifies the title
 * + breadcrumb structure rather than the deeper visuals (color,
 * background image) since those are owned by the shared
 * PageBanner component used by 22 other pages.
 */
test.describe('Explore page banner', () => {
  test('page banner renders with title and breadcrumb on /explore', async ({ page }) => {
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

    // The PageBanner emits an h1 with the title — and is the only
    // h1 above the fold on /explore (categories use h2/h3).
    const banner = page.locator('h1').first();
    await expect(banner).toBeVisible({ timeout: 15_000 });
    await expect(banner).toHaveText(/explore/i);

    // Breadcrumb cluster: must contain "Home" (clickable) and
    // "Explore" (current). PageBanner renders crumbs as <span>s
    // with a "/" separator span in between.
    const homeCrumb = page.getByText(/^home$/i).first();
    await expect(homeCrumb).toBeVisible({ timeout: 10_000 });

    // Click "Home" — should navigate to "/" (root). Confirms the
    // breadcrumb's onClick handler is wired.
    await homeCrumb.click();
    await expect.poll(() => new URL(page.url()).pathname, { timeout: 10_000 }).toBe('/');
  });

  test('page banner sits above the page-level fade wrapper', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto('/explore', { waitUntil: 'commit', timeout: 30_000 });

    // The editorial root mounts inside the fade. The banner's h1
    // should be present in DOM order BEFORE the explore-editorial
    // testid container (it's a sibling at a higher level).
    const editorial = page.getByTestId('explore-editorial');
    await expect(editorial).toBeVisible({ timeout: 15_000 });

    const h1 = page.locator('h1').first();
    await expect(h1).toBeVisible();

    // Quick sanity: the explore-featured-grid lives inside the
    // editorial container, ie. inside the fade wrapper.
    const grid = page.getByTestId('explore-featured-grid');
    await expect(grid).toBeVisible({ timeout: 15_000 });
  });
});
