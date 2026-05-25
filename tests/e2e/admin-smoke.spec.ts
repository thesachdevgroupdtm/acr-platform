import { test, expect } from '@playwright/test';

/**
 * Phase 4.2 — admin panel smoke regression.
 *
 * Targets the Laravel/Filament backend on :8000 (NOT the Vite
 * frontend on :3000 used by other e2e projects). Verifies the
 * login page renders cleanly and that unknown /admin paths
 * return a sane HTTP status (never 500).
 */
test.describe('Admin panel smoke', () => {
  test.use({ baseURL: 'http://127.0.0.1:8000' });

  test('admin login page renders without console errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    await page.goto('/admin/login', { waitUntil: 'networkidle' });
    // Filament renders the form via Livewire — wait for an input
    // that's reliably present (the email/phone field) instead of a
    // text label that lives inside a button.
    await expect(page.locator('input[type="email"], input[name="data.email"]').first()).toBeVisible();

    // Filter out browser-extension noise, which is never our problem.
    const filtered = errors.filter(
      (e) => !e.toLowerCase().includes('extension')
    );
    expect(filtered).toEqual([]);
  });

  test('non-existent admin path returns a clean status (never 500)', async ({
    page,
  }) => {
    const response = await page.goto('/admin/nonexistent-resource', {
      waitUntil: 'networkidle',
    });
    const status = response?.status() ?? 0;
    expect([200, 302, 404]).toContain(status);
  });
});
