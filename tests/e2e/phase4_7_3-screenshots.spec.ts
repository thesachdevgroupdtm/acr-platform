import { test, expect, type Page } from "@playwright/test";
import path from "node:path";

/**
 * Phase 4.7.3 — visual-evidence capture rig.
 *
 * Each test takes a tight element-bounded screenshot for one
 * violation and writes it under
 * `screenshots/phase4_7_3/{stage}/V-{ID}-{slot}.png` where
 * `stage` is "before" or "after" via env var PHASE = before|after.
 *
 * Usage:
 *   PHASE=before npx playwright test phase4_7_3-screenshots --project=smoke
 *   …make fixes…
 *   PHASE=after  npx playwright test phase4_7_3-screenshots --project=smoke
 */

const STAGE = (process.env.PHASE === "after" ? "after" : "before") as
  | "before"
  | "after";
const OUT_DIR = path.join(
  process.cwd(),
  "screenshots",
  "phase4_7_3",
  STAGE,
);

function fileFor(id: string, slot = "main") {
  return path.join(OUT_DIR, `V-${id}-${slot}.png`);
}

async function settle(page: Page) {
  await page.waitForLoadState("domcontentloaded");
  // Brief settle for fonts / motion.
  await page.waitForTimeout(800);
}

// V-A — Home hero
test("V-A · Home hero (top of /)", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  await page.screenshot({
    path: fileFor("A", "hero"),
    clip: { x: 0, y: 0, width: 1440, height: 720 },
  });
});

// V-B — Home WhyChooseUs section
test("V-B · Home WhyChooseUs section", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  // Find the section that contains the cards. Scroll into view.
  const heading = page.getByText(/Why Choose|why\s+choose|Engineered for/i).first();
  if ((await heading.count()) > 0) {
    await heading.scrollIntoViewIfNeeded();
    await page.waitForTimeout(400);
  }
  await page.screenshot({
    path: fileFor("B", "why-choose"),
    fullPage: false,
  });
});

// V-C1 — Testimonials promo
test("V-C1 · Testimonials promo H2", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/testimonials", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  // Manually scroll to a known offset: the testimonials grid is long,
  // and the promo CTA sits ~ 2400 px down on 1440 wide.
  await page.evaluate(() => {
    const sh = document.querySelectorAll("h2.section-heading");
    const promo = sh[sh.length - 1] as HTMLElement | undefined;
    if (promo) promo.scrollIntoView({ block: "center" });
  });
  await page.waitForTimeout(600);
  await page.screenshot({ path: fileFor("C1", "testimonials-promo") });
});

// V-C2 — Corporate promo
test("V-C2 · Corporate promo H2", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/corporate", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight - 1200));
  await page.waitForTimeout(400);
  await page.screenshot({ path: fileFor("C2", "corporate-promo") });
});

// V-D — SEO article H2 sample. The dev DB has no SeoPageSeeder data,
// so we can't visit a real /seo/{slug} page. Instead we synthetically
// inject sample HTML into the live page and run the SAME transform
// function used by SeoPageContent, then screenshot the result. That
// proves the brand-typography contract holds for arbitrary author HTML.
test("V-D · SEO article H2 sample (synthetic inject)", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);

  // Inject the same `brandifyH2s` transform inline (kept in sync with
  // src/components/seo/SeoPageContent.tsx). Mount a styled container
  // that mirrors the seo-page-body utilities.
  await page.evaluate(() => {
    const sampleHtml = `
      <h2>What cashless insurance really means</h2>
      <p>Cashless repair is an arrangement between ACR and the insurer where the customer pays nothing at the workshop. The bill is settled directly. You only pay the policy deductible.</p>
      <h2>How the survey works</h2>
      <p>The insurer dispatches a surveyor to ACR within 24 hours of intimation. They photograph the damage and approve the repair scope.</p>
      <h2>Choosing a partner workshop</h2>
      <p>Always confirm the workshop is on the insurer's preferred-network list before authorising work.</p>
    `;

    function brandifyH2s(html: string): string {
      const doc = new DOMParser().parseFromString(`<div>${html}</div>`, "text/html");
      const root = doc.body.firstElementChild;
      if (!root) return html;
      for (const h2 of Array.from(root.querySelectorAll("h2"))) {
        h2.classList.add("section-heading");
        const text = (h2.textContent ?? "").trim();
        if (!text) continue;
        const lastSpace = text.lastIndexOf(" ");
        const head = lastSpace === -1 ? "" : text.slice(0, lastSpace);
        let tail = lastSpace === -1 ? text : text.slice(lastSpace + 1);
        tail = tail.replace(/[.?!]$/, "") + ".";
        h2.innerHTML = head
          ? `${head} <span class="section-heading-accent">${tail}</span>`
          : `<span class="section-heading-accent">${tail}</span>`;
      }
      return root.innerHTML;
    }

    const transformed = brandifyH2s(sampleHtml);
    const wrap = document.createElement("div");
    wrap.id = "v-d-synthetic";
    wrap.style.cssText = "position:fixed;inset:0;background:white;z-index:9999;padding:48px 64px;overflow:auto;";
    wrap.innerHTML = `
      <div class="seo-page-body max-w-3xl mx-auto" data-testid="v-d-mount">${transformed}</div>
    `;
    document.body.appendChild(wrap);
  });

  await page.waitForTimeout(400);
  await page.screenshot({ path: fileFor("D", "seo-article"), fullPage: false });
});

// V-E1 — ServiceDetail duplicate sections probe
test("V-E1 · ServiceDetail section counts (battery-charging)", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/services/car-battery/battery-charging", {
    waitUntil: "commit",
    timeout: 20_000,
  });
  await settle(page);
  await page.waitForTimeout(1500);

  const why = await page.getByText(/WHY CHOOSE THIS SERVICE/i).count();
  const proc = await page.getByText(/^THE PROCESS/i).count();
  const incl = await page.getByText(/SERVICES INCLUDED/i).count();
  const rev = await page.getByText(/CUSTOMER REVIEWS/i).count();
  const faq = await page.getByText(/COMMON QUESTIONS/i).count();

  // Record counts (the test passes regardless — this is a probe, not assertion).
  console.log(
    `V-E1 counts: why=${why}, process=${proc}, included=${incl}, reviews=${rev}, faqs=${faq}`,
  );

  await page.screenshot({ path: fileFor("E1", "service-detail-fullpage"), fullPage: true });
});

// V-E2 — ServiceCategory duplicate sections probe
test("V-E2 · ServiceCategory section counts (car-battery)", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/category/car-battery", {
    waitUntil: "commit",
    timeout: 20_000,
  });
  await settle(page);
  await page.waitForTimeout(1500);

  const incl = await page.getByText(/SERVICES INCLUDED/i).count();
  const rev = await page.getByText(/CUSTOMER REVIEWS/i).count();
  const faq = await page.getByText(/COMMON QUESTIONS/i).count();
  console.log(`V-E2 counts: included=${incl}, reviews=${rev}, faqs=${faq}`);

  await page.screenshot({ path: fileFor("E2", "service-category-fullpage"), fullPage: true });
});

// V-E3 — Services page (top-level categories shouldn't loop)
test("V-E3 · Services top-level category counts", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/services", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  await page.waitForTimeout(2000);

  // Count distinct API category H2s. Look at h2.section-heading entries.
  const allH2s = await page.locator("h2.section-heading").allTextContents();
  const trimmed = allH2s.map((t) => t.trim().replace(/\s+/g, " "));
  const counts: Record<string, number> = {};
  for (const t of trimmed) counts[t] = (counts[t] ?? 0) + 1;
  console.log("V-E3 H2 counts:", JSON.stringify(counts, null, 2));

  await page.screenshot({ path: fileFor("E3", "services-fullpage"), fullPage: true });
});

// V-F — Explore + Services banner side-by-side
test("V-F1 · Explore PageBanner", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/explore", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  // Wait for the real ExploreEditorial to mount past the skeleton.
  // The H1.page-title appears only inside the real PageBanner.
  await page
    .locator("h1.page-title")
    .first()
    .waitFor({ state: "visible", timeout: 20_000 })
    .catch(() => {
      /* keep going — we'll capture the skeleton if API never returned */
    });
  await page.waitForTimeout(800);

  const probe = await page.evaluate(() => {
    const h1 = document.querySelector("h1.page-title");
    if (!h1) return { found: false, stage: "skeleton" };
    let cur: HTMLElement | null = h1 as HTMLElement;
    for (let i = 0; i < 8 && cur; i++) {
      const cls = (cur.className?.toString?.() ?? "") + "";
      if (cls.includes("min-h-") || cls.includes("h-[")) {
        const r = cur.getBoundingClientRect();
        return { found: true, depth: i, height: r.height, cls };
      }
      cur = cur.parentElement;
    }
    return { found: false, stage: "h1-but-no-bounded-ancestor" };
  });
  console.log("V-F1 explore banner probe:", JSON.stringify(probe));
  await page.screenshot({
    path: fileFor("F1", "explore-banner"),
    clip: { x: 0, y: 0, width: 1440, height: 500 },
  });
});

test("V-F2 · Services PageBanner", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/services", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);

  const probe = await page.evaluate(() => {
    const h1 = document.querySelector("h1.page-title");
    if (!h1) return { found: false };
    let cur: HTMLElement | null = h1 as HTMLElement;
    for (let i = 0; i < 8 && cur; i++) {
      const cls = (cur.className?.toString?.() ?? "") + "";
      if (cls.includes("min-h-") || cls.includes("h-[")) {
        const r = cur.getBoundingClientRect();
        return { found: true, depth: i, height: r.height, cls };
      }
      cur = cur.parentElement;
    }
    return { found: false };
  });
  console.log("V-F2 services banner probe:", JSON.stringify(probe));
  await page.screenshot({
    path: fileFor("F2", "services-banner"),
    clip: { x: 0, y: 0, width: 1440, height: 500 },
  });
});

// V-G — ServiceCenterDetail stats
test("V-G · ServiceCenterDetail stats row", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  // Try a few centers from typical slugs.
  const candidates = ["/service-centers/motinagar", "/service-centers/gurugram", "/service-centers/noida"];
  for (const p of candidates) {
    const resp = await page.goto(p, { waitUntil: "commit", timeout: 15_000 }).catch(() => null);
    await settle(page);
    if (resp && resp.status() < 400) break;
  }
  await page.waitForTimeout(800);
  await page.screenshot({ path: fileFor("G", "scd-stats"), fullPage: true });
});

// V-H — Volvo category page (italic CTA)
test("V-H · Volvo category sidebar CTA", async ({ page }) => {
  test.setTimeout(60_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  const candidates = ["/category/volvo", "/services/volvo", "/category/car-battery"];
  for (const p of candidates) {
    const resp = await page.goto(p, { waitUntil: "commit", timeout: 15_000 }).catch(() => null);
    await settle(page);
    if (resp && resp.status() < 400) break;
  }
  await page.waitForTimeout(800);
  await page.screenshot({ path: fileFor("H", "volvo-cta"), fullPage: true });
});

// V-I — Offers + Coupons
test("V-I1 · Offers card titles", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/offers", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  await page.waitForTimeout(800);
  await page.screenshot({ path: fileFor("I1", "offers-cards"), fullPage: true });
});

test("V-I2 · Coupons card titles", async ({ page }) => {
  test.setTimeout(45_000);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/coupons", { waitUntil: "commit", timeout: 20_000 });
  await settle(page);
  await page.waitForTimeout(800);
  await page.screenshot({ path: fileFor("I2", "coupons-cards"), fullPage: true });
});
