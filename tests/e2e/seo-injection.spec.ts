import { test, expect, type Page } from "@playwright/test";

/**
 * Phase 4.5c — verify Helmet-injected <head> tags on each of the 5
 * customer-facing pages that received the SeoHead retrofit.
 *
 * Helmet replaces tags AFTER the React Query fetch resolves, so we
 * poll for an unambiguous SeoHead-only marker (the og:type tag —
 * absent in index.html, always emitted by SeoHead) before asserting
 * structure.
 */

async function waitForSeoHead(page: Page, timeoutMs = 10_000) {
  await page.waitForFunction(
    () => !!document.querySelector('meta[property="og:type"]'),
    null,
    { timeout: timeoutMs },
  );
}

async function readHeadMeta(page: Page) {
  return await page.evaluate(() => {
    const get = (sel: string, attr: "content" | "href" = "content") =>
      document.querySelector(sel)?.getAttribute(attr) ?? null;
    return {
      title: document.querySelector("title")?.textContent ?? "",
      description: get('meta[name="description"]'),
      robots: get('meta[name="robots"]'),
      ogTitle: get('meta[property="og:title"]'),
      ogType: get('meta[property="og:type"]'),
      ogImage: get('meta[property="og:image"]'),
      twitterCard: get('meta[name="twitter:card"]'),
      canonical: get('link[rel="canonical"]', "href"),
    };
  });
}

test("home page injects og:type + robots via SeoHead", async ({ page }) => {
  test.setTimeout(45_000);
  await page.goto("/", { waitUntil: "commit", timeout: 20_000 });
  await waitForSeoHead(page);

  const head = await readHeadMeta(page);
  expect(head.ogType).toBe("website");
  expect(head.robots).not.toBeNull();
  // SiteSeoSettings cascade renders a real title (not the index.html default).
  expect(head.title).not.toBe("My Google AI Studio App");
  expect(head.title.length).toBeGreaterThan(0);
});

test("services page injects og:type=website + description", async ({ page }) => {
  test.setTimeout(45_000);
  await page.goto("/services", { waitUntil: "commit", timeout: 20_000 });
  await waitForSeoHead(page);

  const head = await readHeadMeta(page);
  expect(head.ogType).toBe("website");
  expect(head.description).not.toBeNull();
  expect((head.description ?? "").length).toBeGreaterThan(0);
});

test("service category page injects twitter:card + og:image", async ({ page }) => {
  test.setTimeout(45_000);
  await page.goto("/category/car-battery", { waitUntil: "commit", timeout: 20_000 });
  await waitForSeoHead(page);

  const head = await readHeadMeta(page);
  expect(head.twitterCard).toBe("summary_large_image");
  // og:image is non-null (cascade pulls default_og_image from SiteSeoSettings).
  expect(head.ogImage).not.toBeNull();
});

test("service centers list page injects og:type", async ({ page }) => {
  test.setTimeout(45_000);
  await page.goto("/service-centers", { waitUntil: "commit", timeout: 20_000 });
  await waitForSeoHead(page);

  const head = await readHeadMeta(page);
  expect(head.ogType).toBe("website");
  expect(head.title).not.toBe("My Google AI Studio App");
});
