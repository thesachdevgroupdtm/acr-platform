import { test, expect } from '@playwright/test';

/**
 * Phase 4.5b — /:slug catch-all SEO page integration.
 *
 * Depends on the SeoPageSeeder having run against the dev DB
 * (audi-service-delhi, bmw-service-cost-guide,
 * monsoon-car-care-tips, best-car-ac-service-gurugram).
 */

test.describe('SEO pages', () => {
  test('/:slug renders the seeded SEO page with title and body', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto('/audi-service-delhi', { waitUntil: 'commit', timeout: 30_000 });

    // Heading is the page title (rendered inside PageBanner).
    await expect(page.getByRole('heading', { name: /Audi Service in Delhi/i }).first())
      .toBeVisible({ timeout: 15_000 });

    // Body content from the seeded HTML.
    await expect(page.getByText(/factory-grade diagnostics/i).first()).toBeVisible();

    // CTA from the seeded payload.
    await expect(page.getByText(/Book Your Audi Service Today/i)).toBeVisible();
  });

  test('Helmet injects meta_title as document.title', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto('/audi-service-delhi', { waitUntil: 'commit', timeout: 30_000 });
    // Wait for Helmet to flush. document.title is updated after the
    // useQuery resolves and SeoHead mounts.
    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15_000 });

    await expect.poll(async () => await page.title(), {
      timeout: 10_000,
      message: 'document.title should reflect the page meta_title',
    }).toContain('Audi Service in Delhi');
  });

  test('Helmet injects og:title and og:description meta tags', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto('/audi-service-delhi', { waitUntil: 'commit', timeout: 30_000 });
    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15_000 });

    await expect.poll(async () =>
      await page.locator('head meta[property="og:title"]').first()
        .getAttribute('content').catch(() => null),
      { timeout: 10_000 }
    ).toContain('Audi Service in Delhi');
  });

  test('Reserved slug /cart does NOT render the SEO page view', async ({ page }) => {
    // /cart hits the existing Cart route (which itself loads). The
    // assertion is "no SeoPageView body content rendered, no
    // /api/v1/seo-pages call fired".
    let seoPageCalled = false;
    page.on('request', (req) => {
      if (req.url().includes('/api/v1/seo-pages/')) {
        seoPageCalled = true;
      }
    });

    await page.goto('/cart', { waitUntil: 'commit', timeout: 30_000 });
    await page.waitForLoadState('domcontentloaded', { timeout: 10_000 });
    await page.waitForTimeout(800);

    expect(seoPageCalled, 'Cart page must not call /api/v1/seo-pages').toBe(false);
  });

  test('Unknown single-segment slug renders the NotFound page', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto('/this-slug-definitely-does-not-exist-' + Date.now(), {
      waitUntil: 'commit',
      timeout: 30_000,
    });

    // NotFound should render some recognisable text. Project's
    // NotFound has "404" in the heading.
    await expect.poll(
      async () => (await page.content()).match(/404|not\s*found/i)?.[0] ?? null,
      { timeout: 15_000 }
    ).not.toBeNull();
  });

  test('Related Articles section renders when a sibling exists', async ({ page }) => {
    test.setTimeout(90_000);

    // Wait for the seo-page API call BEFORE asserting on the DOM
    // — Vite under load can outrun the default 15s element timeout.
    const seoResp = page.waitForResponse(
      (r) =>
        r.url().includes('/api/v1/seo-pages/audi-service-delhi') &&
        r.status() === 200,
      { timeout: 45_000 }
    );

    await page.goto('/audi-service-delhi', { waitUntil: 'commit', timeout: 30_000 });
    await seoResp;

    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15_000 });
    await expect(
      page.getByRole('heading', { name: /Related Articles/i })
    ).toBeVisible({ timeout: 15_000 });
  });

  test('Reading progress bar mounts on /:slug', async ({ page }) => {
    test.setTimeout(90_000);

    const seoResp = page.waitForResponse(
      (r) =>
        r.url().includes('/api/v1/seo-pages/audi-service-delhi') &&
        r.status() === 200,
      { timeout: 45_000 }
    );

    await page.goto('/audi-service-delhi', { waitUntil: 'commit', timeout: 30_000 });
    await seoResp;

    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15_000 });

    // The bar only mounts when the page is tall enough to scroll.
    // The seeded body + sidebar layout always exceeds the viewport.
    await expect(page.locator('[data-testid="reading-progress"]')).toBeVisible({
      timeout: 10_000,
    });
  });

  test('Article tag chips navigate to a filtered /explore', async ({ page }) => {
    test.setTimeout(90_000);

    const seoResp = page.waitForResponse(
      (r) =>
        r.url().includes('/api/v1/seo-pages/audi-service-delhi') &&
        r.status() === 200,
      { timeout: 45_000 }
    );

    await page.goto('/audi-service-delhi', { waitUntil: 'commit', timeout: 30_000 });
    await seoResp;

    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15_000 });

    const firstTag = page.locator('[data-testid^="article-tag-"]').first();
    await expect(firstTag).toBeVisible({ timeout: 10_000 });

    await firstTag.click();

    await expect.poll(
      async () => {
        const url = new URL(page.url());
        return url.pathname === '/explore' && url.searchParams.has('search');
      },
      { timeout: 10_000 }
    ).toBe(true);
  });
});
