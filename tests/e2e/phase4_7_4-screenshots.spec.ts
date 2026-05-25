import { test, type Page } from "@playwright/test";
import path from "node:path";

/**
 * Phase 4.7.4 — Home + Footer H2 unification visual evidence.
 *
 *   PHASE=before npx playwright test phase4_7_4-screenshots --project=phase4_7_4
 *   PHASE=after  npx playwright test phase4_7_4-screenshots --project=phase4_7_4
 */

const STAGE = (process.env.PHASE === "after" ? "after" : "before") as
  | "before"
  | "after";
const OUT_DIR = path.join(process.cwd(), "screenshots", "phase4_7_4", STAGE);

function fileFor(slot: string) {
  return path.join(OUT_DIR, `${slot}.png`);
}

async function settle(page: Page) {
  await page.waitForLoadState("domcontentloaded");
  await page.waitForTimeout(800);
}

// 1 — Home page top-to-bottom fullPage.
test("home-fullpage", async ({ page }) => {
  test.setTimeout(90_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);
  // Let lazy / motion-revealed sections enter view before snapshot.
  await page.evaluate(async () => {
    const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
    const step = window.innerHeight * 0.8;
    const total = document.body.scrollHeight;
    for (let y = 0; y < total; y += step) {
      window.scrollTo(0, y);
      await sleep(150);
    }
    window.scrollTo(0, 0);
    await sleep(400);
  });
  await page.screenshot({ path: fileFor("home-fullpage"), fullPage: true });
});

// 2 — Reference: /offers "CURRENT OFFERS." H2.
test("offers-current-offers", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/offers", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);
  // Frame the H2 specifically — clip 0–500 px shows banner + heading.
  await page.screenshot({
    path: fileFor("offers-current-offers"),
    clip: { x: 0, y: 0, width: 1440, height: 600 },
  });
});

// 3 — Home page "CURRENT OFFERS." H2 (EXCLUSIVE DEALS section).
test("home-current-offers", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);
  // Scroll to the EXCLUSIVE DEALS section.
  await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2"));
    const target = all.find((h) =>
      /CURRENT\s+OFFERS|current offers/i.test(h.textContent ?? ""),
    );
    if (target) target.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(700);
  await page.screenshot({
    path: fileFor("home-current-offers"),
  });
});

// 4 — Footer "INDIA'S FASTEST-GROWING SELF-OWNED MULTI-BRAND NETWORK".
test("footer-network-heading", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);
  await page.evaluate(() => {
    const all = Array.from(document.querySelectorAll("h2, h3, h4"));
    const target = all.find((h) =>
      /FASTEST-?GROWING|fastest-?growing/i.test(h.textContent ?? ""),
    );
    if (target) target.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(700);
  await page.screenshot({ path: fileFor("footer-network") });
});

// 5 — H2 inventory probe: every h2 on home with its full text + computed
// font-weight + computed color. Lets us prove the visual delta vs /offers.
test("home-h2-inventory", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);

  const inventory = await page.evaluate(() => {
    return Array.from(document.querySelectorAll("h2")).map((h) => {
      const cs = window.getComputedStyle(h);
      return {
        text: (h.textContent ?? "").trim().replace(/\s+/g, " ").slice(0, 80),
        fontWeight: cs.fontWeight,
        color: cs.color,
        hasSectionHeading: h.classList.contains("section-heading"),
        classes: h.className,
      };
    });
  });
  console.log("HOME H2 INVENTORY:", JSON.stringify(inventory, null, 2));
});

// 6 — Reference H2 inventory probe on /offers (for comparison).
test("offers-h2-inventory", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/offers", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);

  const inventory = await page.evaluate(() => {
    return Array.from(document.querySelectorAll("h2")).map((h) => {
      const cs = window.getComputedStyle(h);
      return {
        text: (h.textContent ?? "").trim().replace(/\s+/g, " ").slice(0, 80),
        fontWeight: cs.fontWeight,
        color: cs.color,
        hasSectionHeading: h.classList.contains("section-heading"),
      };
    });
  });
  console.log("OFFERS H2 INVENTORY:", JSON.stringify(inventory, null, 2));
});

// 7 — Side-by-side: Home "CURRENT OFFERS." vs /offers "LIMITED TIME OFFERS."
// Both should now render as SemiBold (600) + black head + ACR Blue accent.
// We mount two iframes inside one page so the clip captures both at once.
test("side-by-side-current-offers", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1500, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 30_000 });
  await settle(page);

  // Inject a side-by-side comparison page.
  await page.evaluate(async () => {
    const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
    const overlay = document.createElement("div");
    overlay.id = "side-by-side";
    overlay.style.cssText =
      "position:fixed;inset:0;background:#fff;z-index:99999;display:grid;grid-template-columns:1fr 1fr;gap:0;";
    overlay.innerHTML = `
      <div style="display:flex;flex-direction:column;border-right:1px solid #B8BDC7;">
        <div style="background:#0E2A5C;color:#fff;font-family:Montserrat,sans-serif;font-weight:700;font-size:11px;letter-spacing:0.15em;text-transform:uppercase;padding:10px 18px;">HOME · "CURRENT OFFERS."</div>
        <iframe src="/" style="flex:1;border:0;"></iframe>
      </div>
      <div style="display:flex;flex-direction:column;">
        <div style="background:#1F4FA3;color:#fff;font-family:Montserrat,sans-serif;font-weight:700;font-size:11px;letter-spacing:0.15em;text-transform:uppercase;padding:10px 18px;">/OFFERS · "LIMITED TIME OFFERS."</div>
        <iframe src="/offers" style="flex:1;border:0;"></iframe>
      </div>
    `;
    document.body.appendChild(overlay);
    await sleep(2500);

    // Scroll each iframe to its target H2.
    const iframes = overlay.querySelectorAll("iframe");
    if (iframes[0]) {
      const doc = (iframes[0] as HTMLIFrameElement).contentDocument;
      const target = Array.from(doc?.querySelectorAll("h2") ?? []).find((h) =>
        /current\s+offers/i.test(h.textContent ?? ""),
      );
      target?.scrollIntoView({ block: "center" });
    }
    if (iframes[1]) {
      const doc = (iframes[1] as HTMLIFrameElement).contentDocument;
      const target = Array.from(doc?.querySelectorAll("h2") ?? []).find((h) =>
        /limited\s+time\s+offers/i.test(h.textContent ?? ""),
      );
      target?.scrollIntoView({ block: "center" });
    }
    await sleep(1000);
  });

  await page.screenshot({ path: fileFor("side-by-side-current-offers") });
});
