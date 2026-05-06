import { test, expect } from '@playwright/test';

/**
 * Phase 2.6d edge cases — mobile-viewport critical flows.
 *
 * Runs under the `mobile` Playwright project (iPhone 12 device,
 * 390x844 viewport, mobile UA). Targets the Vite dev server on
 * :3000 — mobile-specific code paths render the same source as
 * desktop, just with the `lg:hidden` mobile chrome instead of the
 * `hidden lg:flex` desktop nav.
 *
 * The 3 tests cover the highest-traffic mobile interactions:
 *  - Hamburger menu toggle + nav item click closes menu and routes.
 *  - Cart icon visibility in the mobile header.
 *  - Home page FAQ accordion expand/collapse with aria-expanded
 *    state correctness.
 *
 * Full multi-device matrix (Pixel, Galaxy S, tablet, etc.) is
 * deferred per D-2.6d-3.
 */

test('mobile hamburger menu opens, navigates, and closes after click', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });

  // The mobile menu toggle is the only `lg:hidden` button inside the
  // header (it has no aria-label — see src/components/Header.tsx:567).
  const hamburger = page.locator('header button.lg\\:hidden').first();
  await expect(hamburger).toBeVisible();
  await hamburger.click();

  // Scope nav-item lookups to the banner — the footer has its own
  // "Insurance"/"Services" buttons (Footer.tsx Quick Links) and a
  // top-level role lookup hits both, breaking strict mode.
  const headerInsurance = page.getByRole('banner').getByRole('button', { name: 'Insurance', exact: true });
  await expect(headerInsurance).toBeVisible({ timeout: 5_000 });
  await headerInsurance.click();

  // Insurance page mounted. The PageBanner renders the title as
  // an h1 — anchor on the heading role to avoid colliding with
  // body-copy occurrences of "insurance" elsewhere (Home FAQ,
  // Footer SEO blurb, etc.).
  await expect(
    page.getByRole('heading', { name: /insurance claims/i }).first(),
  ).toBeVisible({ timeout: 10_000 });

  // The mobile menu should have auto-closed on nav (Header.tsx:592).
  // After close, the only "Insurance" button left in the banner is
  // the desktop nav (which is hidden under lg:hidden on mobile), so
  // the banner-scoped Insurance button must NOT be visible.
  await expect(headerInsurance).not.toBeVisible();
});

test('mobile header surfaces the cart icon for guests', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.getByRole('banner')).toBeVisible({ timeout: 10_000 });

  // The cart icon's button has aria-label="View cart" (Header.tsx:387).
  // Guest users see no badge until they add an item — Phase 2.6a-fix
  // gated `showCartBadge` on bootstrapped + count>0 to suppress the
  // hard-refresh 0→N flicker.
  const cartButton = page.getByRole('button', { name: 'View cart' });
  await expect(cartButton).toBeVisible();
});

test('mobile FAQ accordion: clicking Q01 toggles its aria-expanded', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // Scroll near the bottom — HomeFAQ lives in the lower section of
  // the home page (src/pages/Home.tsx:1216).
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

  // HomeFAQ.tsx renders each question with a "Q01"/"Q02"/... numeric
  // label inside the button (HomeFAQ.tsx:147). Targeting Q01 directly
  // is far more stable than relying on document order, which the
  // header dropdowns also affect (those use aria-expanded too).
  const q01 = page.locator('button[aria-controls="home-faq-panel-0"]').first();
  await expect(q01).toBeVisible({ timeout: 10_000 });
  await q01.scrollIntoViewIfNeeded();

  // Initial state: collapsed.
  await expect(q01).toHaveAttribute('aria-expanded', 'false');

  await q01.click();
  // After click: expanded.
  await expect(q01).toHaveAttribute('aria-expanded', 'true', { timeout: 2_000 });
});
