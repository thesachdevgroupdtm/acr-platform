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
      testMatch: /smoke\.spec\.ts$/,
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
      name: 'edges',
      testMatch: /(journey|cart-merge|coupon-flow|auth-edges)\.spec\.ts$/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:3000',
      },
    },
  ],
});
