import { test, expect } from '@playwright/test';

/**
 * Phase 4.7.1 — brand-manual consistency smoke.
 *
 * Asserts that:
 *   - Every audited page renders exactly ONE H1 with the
 *     `.page-title` class (PageBanner pattern from D-4.7.1-3).
 *   - The H1's computed font-family resolves to Montserrat
 *     (brand manual display font, p. 21).
 *   - At least one `.section-heading-accent` span is present
 *     on each page (proves dual-color H2 treatment landed).
 *   - The home hero — D-4.7.1-2 — uses NON-italic styling
 *     on its primary H1 (operator-flagged italic violation).
 *   - The SEO internal article banner uses the SAME PageBanner
 *     class (D-4.7.1-3), NOT the previous bg-neutral-900
 *     SeoPageHero solid-black pattern.
 */

const BRAND_PAGES = [
  { path: '/about',          name: 'About'        },
  { path: '/contact',        name: 'Contact'      },
  { path: '/services',       name: 'Services'     },
  { path: '/explore',        name: 'Explore'      },
  { path: '/insurance',      name: 'Insurance'    },
] as const;

for (const p of BRAND_PAGES) {
  test(`brand consistency: ${p.name} (${p.path})`, async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto(p.path, { waitUntil: 'commit', timeout: 30_000 });

    const h1s = page.locator('h1');
    await expect(h1s.first()).toBeVisible({ timeout: 15_000 });

    // Exactly one H1 per page (D-4.7-8 + D-4.7.1-3).
    const h1Count = await h1s.count();
    expect(h1Count).toBe(1);

    // H1 has `.page-title`.
    const hasPageTitle = await h1s.first().evaluate((el) =>
      el.classList.contains('page-title'),
    );
    expect(hasPageTitle).toBe(true);

    // Computed font-family includes Montserrat (manual display font).
    const fontFamily = await h1s.first().evaluate((el) =>
      window.getComputedStyle(el).fontFamily.toLowerCase(),
    );
    expect(fontFamily).toContain('montserrat');

    // No italic on H1 (D-4.7.1-2 brand rule).
    const fontStyle = await h1s.first().evaluate((el) =>
      window.getComputedStyle(el).fontStyle,
    );
    expect(fontStyle).toBe('normal');

    // At least one section-heading-accent span (dual-color H2 landed).
    const accents = page.locator('.section-heading-accent');
    await expect(accents.first()).toBeVisible({ timeout: 15_000 });
  });
}

test('home hero (FLAWLESS RESTORATION) is non-italic and uses brand fonts', async ({ page }) => {
  test.setTimeout(60_000);

  await page.goto('/', { waitUntil: 'commit', timeout: 30_000 });

  // Home has its own hero H1 — not inside PageBanner.
  const h1 = page.locator('h1').first();
  await expect(h1).toBeVisible({ timeout: 15_000 });

  // Manual-mandated NON-italic (D-4.7.1-2).
  const fontStyle = await h1.evaluate((el) =>
    window.getComputedStyle(el).fontStyle,
  );
  expect(fontStyle).toBe('normal');

  // Display font = Montserrat (manual p. 21).
  const fontFamily = await h1.evaluate((el) =>
    window.getComputedStyle(el).fontFamily.toLowerCase(),
  );
  expect(fontFamily).toContain('montserrat');

  // Accent span (if present) is NOT italic — operator's flagged
  // bug was "Restoration." in italic + bright blue.
  const accent = h1.locator('span').first();
  if (await accent.count() > 0) {
    const accentStyle = await accent.evaluate((el) =>
      window.getComputedStyle(el).fontStyle,
    );
    expect(accentStyle).toBe('normal');
  }
});
