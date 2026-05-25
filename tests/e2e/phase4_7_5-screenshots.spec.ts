import { test, type Page } from "@playwright/test";
import path from "node:path";

/**
 * Phase 4.7.5 — H2 size normalization visual evidence.
 *
 *   PHASE=before npx playwright test phase4_7_5-screenshots --project=phase4_7_5
 *   PHASE=after  npx playwright test phase4_7_5-screenshots --project=phase4_7_5
 */

const STAGE = (process.env.PHASE === "after" ? "after" : "before") as
  | "before"
  | "after";
const OUT_DIR = path.join(process.cwd(), "screenshots", "phase4_7_5", STAGE);

function fileFor(slot: string) {
  return path.join(OUT_DIR, `${slot}.png`);
}

async function settle(page: Page) {
  await page.waitForLoadState("domcontentloaded");
  await page.waitForTimeout(800);
}

async function warmHome(page: Page) {
  // Trigger any lazy-mounted / motion-revealed sections.
  await page.evaluate(async () => {
    const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
    const step = window.innerHeight * 0.8;
    const total = document.body.scrollHeight;
    for (let y = 0; y < total; y += step) {
      window.scrollTo(0, y);
      await sleep(120);
    }
    window.scrollTo(0, 0);
    await sleep(300);
  });
}

// 1 — Home side-by-side: CURRENT OFFERS H2 next to FLEET MAINTENANCE H2.
// We scroll to each, take element-bound screenshots, and the comparison
// rests on the visual font-size being the same (clamp).
test("home-current-offers-h2", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);
  await warmHome(page);

  await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2"));
    const target = all.find((h) =>
      /current\s+offers/i.test(h.textContent ?? ""),
    );
    if (target) target.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(500);
  await page.screenshot({ path: fileFor("home-current-offers"), fullPage: false });
});

test("home-fleet-maintenance-h2", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);
  await warmHome(page);

  await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2"));
    const target = all.find((h) =>
      /fleet\s+maintenance/i.test(h.textContent ?? ""),
    );
    if (target) target.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(500);
  await page.screenshot({ path: fileFor("home-fleet-maintenance"), fullPage: false });
});

// 2 — Sizing probe: compare actual rendered font-size of every Home H2.
test("home-h2-size-probe", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);

  const sizes = await page.evaluate(() => {
    return Array.from(document.querySelectorAll("h2")).map((h) => {
      const cs = window.getComputedStyle(h);
      const r = h.getBoundingClientRect();
      return {
        text: (h.textContent ?? "").trim().replace(/\s+/g, " ").slice(0, 60),
        fontSize: cs.fontSize,
        boundingHeight: Math.round(r.height),
      };
    });
  });
  console.log("HOME H2 SIZES:", JSON.stringify(sizes, null, 2));
});

// 3 — Footer pre-section at 1440px (target: ONE line).
test("footer-1440", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);

  // Wait for the footer SectionHeading then scroll into view.
  await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2, h3, h4"));
    const target = all.find((h) =>
      /FASTEST-?GROWING/i.test(h.textContent ?? ""),
    );
    if (target) target.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(700);

  // Measure: does the heading render on a single line?
  const probe = await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2, h3, h4"));
    const el = all.find((h) =>
      /FASTEST-?GROWING/i.test(h.textContent ?? ""),
    );
    if (!el) return { found: false };
    const cs = window.getComputedStyle(el);
    const r = el.getBoundingClientRect();
    const lineHeight = parseFloat(cs.lineHeight);
    // Single-line height ≈ lineHeight. Two-line height ≈ 2× lineHeight.
    const ratio = lineHeight ? r.height / lineHeight : 0;
    return {
      found: true,
      fontSize: cs.fontSize,
      lineHeight: cs.lineHeight,
      boundingHeight: Math.round(r.height),
      approxLines: Math.round(ratio * 10) / 10,
      classes: el.className,
    };
  });
  console.log("FOOTER 1440 PROBE:", JSON.stringify(probe, null, 2));

  await page.screenshot({ path: fileFor("footer-1440") });
});

// 4 — Footer pre-section at 375px (mobile; wrapping is OK here).
test("footer-mobile-375", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);

  await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2, h3, h4"));
    const target = all.find((h) =>
      /FASTEST-?GROWING/i.test(h.textContent ?? ""),
    );
    if (target) target.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(700);

  const probe = await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2, h3, h4"));
    const el = all.find((h) =>
      /FASTEST-?GROWING/i.test(h.textContent ?? ""),
    );
    if (!el) return { found: false };
    const cs = window.getComputedStyle(el);
    const r = el.getBoundingClientRect();
    return {
      found: true,
      fontSize: cs.fontSize,
      lineHeight: cs.lineHeight,
      boundingHeight: Math.round(r.height),
    };
  });
  console.log("FOOTER 375 PROBE:", JSON.stringify(probe, null, 2));

  await page.screenshot({ path: fileFor("footer-mobile") });
});
