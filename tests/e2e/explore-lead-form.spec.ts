import { test, expect } from '@playwright/test';

/**
 * Phase 4.5.3 — explore-sidebar lead-capture form.
 *
 * Replaces the deleted Newsletter widget. Lives in BOTH sticky aside
 * positions on /explore (Section 1 + Section 2) and the mobile-only
 * widget block, so a desktop visit always has at least 2 instances
 * — assertions use `.first()` to target the topmost.
 */
test.describe('Explore lead form', () => {
  test('lead form renders with all 6 fields and required markers', async ({ page }) => {
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

    const widget = page.getByTestId('lead-form-widget').first();
    await expect(widget).toBeVisible({ timeout: 15_000 });

    // All 6 fields present.
    await expect(widget.getByTestId('lead-form-name')).toBeVisible();
    await expect(widget.getByTestId('lead-form-email')).toBeVisible();
    await expect(widget.getByTestId('lead-form-phone')).toBeVisible();
    await expect(widget.getByTestId('lead-form-brand')).toBeVisible();
    await expect(widget.getByTestId('lead-form-model')).toBeVisible();
    await expect(widget.getByTestId('lead-form-service')).toBeVisible();
    await expect(widget.getByTestId('lead-form-submit')).toBeVisible();

    // Name + Phone are required → asterisk in their <label> text.
    await expect(widget.getByText(/Name\s*\*/)).toBeVisible();
    await expect(widget.getByText(/Phone\s*\*/)).toBeVisible();

    // Model is disabled until a Brand is picked.
    await expect(widget.getByTestId('lead-form-model')).toBeDisabled();
  });

  test('submitting valid form shows success state', async ({ page }) => {
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

    const widget = page.getByTestId('lead-form-widget').first();
    await expect(widget).toBeVisible({ timeout: 15_000 });

    await widget.getByTestId('lead-form-name').fill('Playwright Test');
    // Random valid Indian mobile (starts with [6-9]).
    const phone = '9' + Math.floor(100000000 + Math.random() * 899999999).toString();
    await widget.getByTestId('lead-form-phone').fill(phone);

    const submitResp = page.waitForResponse(
      (r) => r.url().includes('/api/v1/leads') && r.request().method() === 'POST',
      { timeout: 15_000 }
    );

    await widget.getByTestId('lead-form-submit').click();
    const resp = await submitResp;
    expect(resp.status()).toBe(200);

    // Success card replaces the form.
    await expect(page.getByTestId('lead-form-widget-success').first())
      .toBeVisible({ timeout: 10_000 });
    await expect(page.getByTestId('lead-form-widget-success').first())
      .toContainText(/thanks/i);
  });

  test('submitting without phone shows validation error', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto('/explore', { waitUntil: 'commit', timeout: 30_000 });

    const widget = page.getByTestId('lead-form-widget').first();
    await expect(widget).toBeVisible({ timeout: 15_000 });

    await widget.getByTestId('lead-form-name').fill('Validation Test');

    // Phone field has `required` attribute → browser blocks submit
    // before any network call. Assert the field reports invalid via
    // checkValidity(). This is the cleanest cross-browser check
    // since native validation messages are localized.
    await widget.getByTestId('lead-form-submit').click();

    const phoneIsInvalid = await widget
      .getByTestId('lead-form-phone')
      .evaluate((el: HTMLInputElement) => !el.checkValidity());
    expect(phoneIsInvalid).toBe(true);

    // The form must NOT have transitioned to success.
    await expect(page.getByTestId('lead-form-widget-success'))
      .toHaveCount(0);
  });
});
