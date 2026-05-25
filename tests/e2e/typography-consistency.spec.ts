import { test, expect } from '@playwright/test';

/**
 * Phase 4.7 — typography consistency smoke spec.
 *
 * Verifies on a handful of major pages that:
 *   - Exactly one H1 exists per page (the PageBanner H1).
 *   - That H1 carries the `.page-title` utility class so changes
 *     to the canonical type system propagate site-wide in one
 *     edit.
 *   - At least one H2 carries the `.section-heading` utility,
 *     proving the page has been migrated to the dual-color +
 *     period pattern.
 *
 * Pages chosen: high-visibility marketing surfaces that completed
 * Phase 4.7 migration. The Home page is INTENTIONALLY OMITTED —
 * it uses a distinct primary-dark + italic-accent treatment per
 * its existing design language (Phase 4.7 follow-up — see report).
 */

const PAGES = [
  { path: '/about',          name: 'About'        },
  { path: '/contact',        name: 'Contact'      },
  { path: '/services',       name: 'Services'     },
  { path: '/coupons',        name: 'Coupons'      },
  { path: '/offers',         name: 'Offers'       },
  { path: '/testimonials',   name: 'Testimonials' },
  { path: '/service-centers',name: 'Centers'      },
  { path: '/sitemap',        name: 'Sitemap'      },
  { path: '/gallery',        name: 'Gallery'      },
  { path: '/insurance',      name: 'Insurance'    },
  { path: '/corporate',      name: 'Corporate'    },
  { path: '/explore',        name: 'Explore'      },
] as const;

for (const p of PAGES) {
  test(`typography canonical: ${p.name} (${p.path})`, async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto(p.path, { waitUntil: 'commit', timeout: 30_000 });

    // Wait for any h1 to mount (PageBanner loads with page chrome).
    const h1s = page.locator('h1');
    await expect(h1s.first()).toBeVisible({ timeout: 15_000 });

    // Exactly one H1 per page.
    const h1Count = await h1s.count();
    expect(h1Count).toBe(1);

    // That single H1 is inside PageBanner → carries `.page-title`.
    const hasPageTitleClass = await h1s.first().evaluate((el) =>
      el.classList.contains('page-title'),
    );
    expect(hasPageTitleClass).toBe(true);

    // At least one H2 with `.section-heading` class.
    const sectionHeadings = page.locator('h2.section-heading');
    await expect(sectionHeadings.first()).toBeVisible({ timeout: 15_000 });
  });
}
