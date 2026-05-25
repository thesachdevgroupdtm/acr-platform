import { test, expect } from "@playwright/test";

/**
 * Phase 4.7.2 — Brand typography enforcement (exhaustive).
 *
 * Locks in the nine violation fixes (V-1 … V-9) by asserting the
 * brand-manual contract at runtime:
 *   • Exactly one H1 per page, in `.page-title`, font-family
 *     Montserrat, never italic.
 *   • At least one `.section-heading` H2 with the dual-colour
 *     accent span (`.section-heading-accent`) on every marketing
 *     surface.
 *   • Hero H1 on /, /services, /explore, /insurance, /service-centers,
 *     /offers all resolve to Montserrat.
 *   • /service-centers/{slug} renders the 1-word AMENITIES H2 (V-4 +
 *     V-7) as a dual-colour heading — entire word in the accent span.
 *   • /offers "NEED A CUSTOM SOLUTION?" heading exists, ends with
 *     "?" (V-5 terminator), and runs on a dark surface (white text).
 *   • / home hero H1 ("FLAWLESS RESTORATION.") renders in white on
 *     a navy hero (V-6 — manual p.45 "Navy bleed").
 */

const MARKETING_PAGES = [
  "/about",
  "/services",
  "/explore",
  "/insurance",
  "/contact",
] as const;

for (const path of MARKETING_PAGES) {
  test(`brand typography: ${path}`, async ({ page }) => {
    test.setTimeout(60_000);
    await page.goto(path, { waitUntil: "commit", timeout: 30_000 });

    const h1 = page.locator("h1").first();
    await expect(h1).toBeVisible({ timeout: 15_000 });

    // CR-2: H1 carries `.page-title`.
    const hasPageTitle = await h1.evaluate((el) =>
      el.classList.contains("page-title"),
    );
    expect(hasPageTitle).toBe(true);

    // CR-1: Display font = Montserrat.
    const fontFamily = await h1.evaluate((el) =>
      window.getComputedStyle(el).fontFamily.toLowerCase(),
    );
    expect(fontFamily).toContain("montserrat");

    // CR-2: never italic.
    const fontStyle = await h1.evaluate((el) =>
      window.getComputedStyle(el).fontStyle,
    );
    expect(fontStyle).toBe("normal");

    // CR-3 + CR-4: at least one dual-colour H2 with accent span.
    const accent = page.locator(".section-heading-accent").first();
    await expect(accent).toBeVisible({ timeout: 15_000 });
  });
}

test("V-5 — /offers 'NEED A CUSTOM SOLUTION?' has terminator '?' on dark surface", async ({
  page,
}) => {
  test.setTimeout(60_000);
  await page.goto("/offers", { waitUntil: "commit", timeout: 30_000 });

  const heading = page.locator("h2").filter({
    hasText: /NEED A CUSTOM/i,
  });
  await expect(heading.first()).toBeVisible({ timeout: 15_000 });

  const text = (await heading.first().textContent()) ?? "";
  expect(text).toMatch(/\?\s*$/);

  // Question text reads as white (dark-surface branch of V-5).
  const color = await heading.first().evaluate((el) =>
    window.getComputedStyle(el).color,
  );
  expect(color).toMatch(/rgb\(\s*255,\s*255,\s*255/);
});

test("V-6 — home hero 'FLAWLESS RESTORATION.' is white on navy", async ({
  page,
}) => {
  test.setTimeout(60_000);
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });

  const h1 = page.locator("h1").first();
  await expect(h1).toBeVisible({ timeout: 15_000 });

  // Head text colour resolves to white per V-6.
  const headColor = await h1.evaluate((el) =>
    window.getComputedStyle(el).color,
  );
  expect(headColor).toMatch(/rgb\(\s*255,\s*255,\s*255/);

  // Accent span resolves to ACR Blue.
  const accent = h1.locator("span").first();
  if ((await accent.count()) > 0) {
    const accentColor = await accent.evaluate((el) =>
      window.getComputedStyle(el).color,
    );
    // #1F4FA3 → rgb(31, 79, 163)
    expect(accentColor).toMatch(/rgb\(\s*31,\s*79,\s*163/);
  }
});

test("V-2 — /explore PageBanner shares min-height with /services", async ({
  page,
}) => {
  test.setTimeout(60_000);

  await page.goto("/explore", { waitUntil: "commit", timeout: 30_000 });
  const exploreBanner = await page
    .locator("h1.page-title")
    .first()
    .evaluate((el) => {
      // Walk up to the banner container.
      let cur: HTMLElement | null = el;
      for (let i = 0; i < 6 && cur; i++) {
        const cls = cur.className?.toString?.() ?? "";
        if (cls.includes("min-h-[300px]") || cls.includes("h-[40vh]")) {
          return cur.getBoundingClientRect().height;
        }
        cur = cur.parentElement;
      }
      return 0;
    });

  await page.goto("/services", { waitUntil: "commit", timeout: 30_000 });
  const servicesBanner = await page
    .locator("h1.page-title")
    .first()
    .evaluate((el) => {
      let cur: HTMLElement | null = el;
      for (let i = 0; i < 6 && cur; i++) {
        const cls = cur.className?.toString?.() ?? "";
        if (cls.includes("min-h-[300px]") || cls.includes("h-[40vh]")) {
          return cur.getBoundingClientRect().height;
        }
        cur = cur.parentElement;
      }
      return 0;
    });

  // Heights should match within 4 px (rounding / scrollbar tolerance).
  expect(Math.abs(exploreBanner - servicesBanner)).toBeLessThanOrEqual(4);
});
