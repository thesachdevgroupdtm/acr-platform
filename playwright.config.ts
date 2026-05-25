import { defineConfig, devices } from '@playwright/test';

/**
 * Phase 2.6c — single chromium project against the Vite dev server.
 * Phase 2.6b — split into two projects:
 *   - "smoke"      → Vite dev    (http://localhost:3000)
 *   - "production" → Vite preview (http://localhost:4173)
 *
 * The code-splitting tests REQUIRE the production build because dev
 * mode (esbuild streaming) does not emit hashed per-route chunk
 * filenames; tests that abort the Services-hash.js chunk URL would
 * no-op against dev. The smoke tests have no such requirement and
 * stay on dev for the faster feedback loop.
 *
 * Running the full e2e suite requires both servers to be up.
 * Project filtering lets you target one at a time:
 *   npx playwright test --project=smoke
 *   npx playwright test --project=production
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list']],
  timeout: 30_000,
  expect: { timeout: 5_000 },
  use: {
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    actionTimeout: 10_000,
    navigationTimeout: 15_000,
  },
  projects: [
    {
      name: 'smoke',
      testMatch: /[\\/]smoke\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      name: 'production',
      testMatch: /(code-splitting|console-errors)\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        // No baseURL — production tests use absolute URLs because the
        // build is served from /app/ (vite.config.ts base) and Playwright's
        // URL.resolve(baseURL, path) gets confusing with subdirectory bases.
        // Tests construct full URLs against PREVIEW_URL below.
      },
    },
    {
      // Phase 2.6d — single-viewport mobile project per D-2.6d-3.
      // Pixel 5 instead of iPhone 12 because Pixel 5 is a Chromium
      // device — Phase 2.6c's `npx playwright install chromium`
      // didn't pull WebKit, and HARD CONSTRAINT "DO NOT install new
      // packages" reads as "no new browser installs either". Pixel 5
      // covers the same mobile invariants (393x851 viewport, mobile
      // UA, touch event support) under the existing engine.
      name: 'mobile',
      testMatch: /mobile\.spec\.ts$/,
      use: {
        ...devices['Pixel 5'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 2.6d — non-mobile dev-server e2e (full journey, cart
      // merge UX, coupon flow, auth edges). These tests need real
      // backend reads and writes, so they target dev (:3000) where
      // the Laravel API on :8000 already passes CORS for that origin.
      // Phase 3A — router-pattern tests added to this project.
      name: 'edges',
      testMatch: /(journey|cart-merge|coupon-flow|auth-edges|router-patterns|router-params|typography-consistency|brand-consistency)\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 4.2 — Filament admin panel smoke. Targets the Laravel
      // dev server on :8000 (NOT Vite). Run with:
      //   npx playwright test --project=admin
      // Requires `php artisan serve` running.
      name: 'admin',
      testMatch: /admin-smoke\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://127.0.0.1:8000',
      },
    },
    {
      // Phase 4.2.5 — frontend ↔ API integration tests. Targets the
      // Vite dev server on :3000 (which proxies/calls Laravel on :8000).
      // Locks in the audit fixes: coupons page, /service-centers API
      // migration, no-4xx sweep, and CouponPickerModal error UI.
      name: 'api-integration',
      testMatch: /api-integration\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 4.2.5b — verify the CORS allowed_origins_patterns
      // regex covers Vite's :3001 fallback port. Run with:
      //   npx playwright test --project=cors-fallback
      // Requires a Vite instance bound to :3001 (Vite chooses this
      // automatically when :3000 is already in use).
      name: 'cors-fallback',
      testMatch: /cors-3001-verify\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3001',
      },
    },
    {
      // Phase 4.5b — operator-managed SEO content tests:
      // /:slug catch-all + /explore hub + helmet-injected meta.
      // Requires the SeoPageSeeder to have run against the dev DB.
      name: 'seo',
      testMatch: /(seo-pages|explore|explore-editorial|explore-category-filter|explore-no-image-fallback|explore-page-banner|explore-lead-form|explore-footer-revamp|explore-sections-screenshots|explore-big-grid-dual)\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 4.7.3 — visual evidence rig. Re-do the Phase 4.7.2
      // typography claims with before/after element screenshots.
      // Runs against the Vite dev server on :3000.
      //   PHASE=before npx playwright test --project=phase4_7_3
      //   PHASE=after  npx playwright test --project=phase4_7_3
      name: 'phase4_7_3',
      testMatch: /(phase4_7_3-screenshots|brand-typography)\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 4.7.4 — Home + Footer H2 unification visual evidence.
      //   PHASE=before npx playwright test --project=phase4_7_4
      //   PHASE=after  npx playwright test --project=phase4_7_4
      name: 'phase4_7_4',
      testMatch: /phase4_7_4-screenshots\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 4.7.5 — H2 size normalization visual evidence.
      name: 'phase4_7_5',
      testMatch: /phase4_7_5-screenshots\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
    {
      // Phase 4.5c — Helmet-injected meta tag assertions for the 5
      // customer pages that received SeoHead. Requires both Vite
      // (:3000) and Laravel (:8000) running.
      name: 'phase4_5c',
      testMatch: /seo-injection\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
  ],
});
