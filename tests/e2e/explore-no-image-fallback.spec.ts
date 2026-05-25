import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.1 — cards whose payload entries lack `hero_image_url`
 * must render the new fallback design (gradient + heroicon +
 * restrained title), NOT the old giant-text fill.
 *
 * Strategy: intercept the /api/v1/explore response, force every
 * card's `hero_image_url` to null, and verify at least one
 * `[data-testid="explore-card-fallback"]` mounts.
 */
test.describe('Explore no-image fallback', () => {
  test('cards without hero_image_url render the fallback design, not giant text fill', async ({ page }) => {
    test.setTimeout(60_000);

    // Intercept the payload response and strip every image URL.
    await page.route('**/api/v1/explore**', async (route) => {
      const response = await route.fetch();
      const body = await response.json();

      const stripImage = (card: Record<string, unknown>) => {
        if (card && typeof card === 'object') {
          card.hero_image_url = null;
        }
        return card;
      };

      if (Array.isArray(body.hero)) body.hero = body.hero.map(stripImage);
      if (Array.isArray(body.trending_grid)) body.trending_grid = body.trending_grid.map(stripImage);
      if (Array.isArray(body.categories)) {
        body.categories = body.categories.map((c: Record<string, unknown>) => {
          if (c.featured) c.featured = stripImage(c.featured as Record<string, unknown>);
          if (Array.isArray(c.items)) c.items = c.items.map(stripImage);
          return c;
        });
      }
      if (body.rails) {
        if (Array.isArray(body.rails.trending_searches)) {
          body.rails.trending_searches = body.rails.trending_searches.map(stripImage);
        }
        if (Array.isArray(body.rails.most_read_week)) {
          body.rails.most_read_week = body.rails.most_read_week.map(stripImage);
        }
      }

      await route.fulfill({
        response,
        body: JSON.stringify(body),
        headers: { ...response.headers(), 'content-type': 'application/json' },
      });
    });

    await page.goto('/explore', { waitUntil: 'commit', timeout: 30_000 });

    // Wait for the editorial root to mount.
    await expect(page.getByTestId('explore-editorial')).toBeVisible({ timeout: 20_000 });

    // At least one fallback must render. Multiple are expected
    // since every card had its image stripped.
    const fallback = page.locator('[data-testid="explore-card-fallback"]').first();
    await expect(fallback).toBeVisible({ timeout: 15_000 });

    // The fallback must NOT show the old giant-text rendering.
    // The new design uses `text-sm md:text-base font-medium` —
    // assert no class containing the old `text-7xl`/`text-6xl`
    // anywhere inside any fallback element.
    const giantTextCount = await page.locator(
      '[data-testid="explore-card-fallback"] [class*="text-7xl"], ' +
      '[data-testid="explore-card-fallback"] [class*="text-6xl"], ' +
      '[data-testid="explore-card-fallback"] [class*="text-5xl"]'
    ).count();
    expect(giantTextCount).toBe(0);
  });
});
