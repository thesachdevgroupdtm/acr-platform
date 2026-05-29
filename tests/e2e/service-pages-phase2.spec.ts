import { test, expect, type Page } from "@playwright/test";

/**
 * Phase 2a — service CATEGORY page redesign (GoMechanic-style cards, ACR skin).
 * Manual-run project `phase2` (needs Vite :3000 + Laravel :8000 up).
 * Captures desktop(1440) + mobile(390) screenshots and asserts the card
 * anatomy: image/fallback, inclusions preview, price 4-state, sidebar mounted.
 */

const CATEGORY = "/category/regular-car-service";

// Audi A3 Petrol — a real tuple with service_prices for this category, so
// prices + previews reveal (vehicleSelected + price_show).
const VEHICLE = {
  location: "",
  car: {
    brand: "Audi", model: "A3", fuel: "Petrol",
    brand_id: 34, model_id: 317, fuel_id: 5,
    brand_slug: "audi", model_slug: "a3", fuel_slug: "petrol",
    segment: null,
  },
  phone: "", otpVerified: false, pricesShown: true, entry_mode: "structured",
};

async function seedVehicle(page: Page) {
  await page.addInitScript((v) => {
    localStorage.setItem("acr_booking_ctx_v1", JSON.stringify(v));
  }, VEHICLE);
}

test("category page — desktop 1440, vehicle selected (prices + previews)", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });

  // GoMechanic-style cards render.
  const cards = page.locator("main article");
  await expect(cards.first()).toBeVisible();
  expect(await cards.count()).toBeGreaterThan(0);

  // Inclusions preview (checkmark list) is present on at least one card.
  await expect(page.locator("main article ul li").first()).toBeVisible();

  // Sidebar (CarSidebar) mounted in the right column.
  await expect(page.locator("aside").first()).toBeVisible();

  await page.screenshot({ path: "phase2-shots/category-desktop-vehicle.png", fullPage: true });
  // Close-up of the first card to verify horizontal anatomy at desktop.
  await cards.first().screenshot({ path: "phase2-shots/category-desktop-card.png" });
});

test("category page — desktop 1440, no vehicle (fallback / select state)", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });

  const cards = page.locator("main article");
  await expect(cards.first()).toBeVisible();
  // "Select car" CTA shows when no vehicle is chosen.
  await expect(page.getByText("Select car", { exact: false }).first()).toBeVisible();

  await page.screenshot({ path: "phase2-shots/category-desktop-novehicle.png", fullPage: true });
});

test("category page — mobile 390, vehicle selected", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });

  await expect(page.locator("main article").first()).toBeVisible();
  await page.screenshot({ path: "phase2-shots/category-mobile-vehicle.png", fullPage: true });
});

/* ════════════════════════════════════════════════════════════════════
 * Phase 2b-continue — persistent ServicesShell + rebuilt Layer-3 detail.
 * The whole point: /services ↔ /category/:slug ↔ /services/:cat/:svc swap
 * ONLY the center content; the sticky cross-category bar + CarSidebar stay
 * MOUNTED (the same DOM node, no reload feel). Plus: the rebuilt detail
 * renders grouped What's Included from REAL inclusions, and the cart flow
 * does not regress.
 * ════════════════════════════════════════════════════════════════════ */

const SERVICE = "/services/regular-car-service/primary-service"; // real grouped inclusions

// D-2b proof: navigating category → detail must NOT remount the sidebar.
// We capture the live <aside data-testid="car-sidebar"> handle BEFORE the
// client-side nav and assert the SAME node is still connected AFTER — a
// remount would detach the old handle (isConnected === false).
test("shell — sidebar is the SAME DOM node across category → detail (persistent)", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });

  const sidebar = page.locator('[data-testid="car-sidebar"]');
  await expect(sidebar).toBeVisible();
  const handleBefore = await sidebar.elementHandle();
  expect(handleBefore).not.toBeNull();

  // Client-side nav to the detail layer by clicking a service card title.
  const card = page.locator("main article", { hasText: /primary service/i }).first();
  await card.locator("button").first().click();
  await expect(page).toHaveURL(/\/services\/regular-car-service\/primary-service$/);

  // Wait for DETAIL-ONLY, fully-LOADED content (the navy "after you book"
  // band + a real grouped inclusion) — not the "INCLUDED" heading, which
  // would also match the exiting category page mid-crossfade.
  await expect(page.getByRole("heading", { name: /AFTER YOU BOOK/i })).toBeVisible();
  await expect(page.getByText("Engine Oil Replacement", { exact: false })).toBeVisible();

  // The sidebar node we grabbed on the category page is STILL the live
  // node after the full transition — it was never remounted.
  const stillConnected = await handleBefore!.evaluate((el) => el.isConnected);
  expect(stillConnected).toBe(true);

  // And there is exactly ONE sidebar (no duplicate from the page).
  expect(await page.locator('[data-testid="car-sidebar"]').count()).toBe(1);

  await page.waitForTimeout(350); // let the 180ms crossfade settle to opacity:1
  await page.screenshot({ path: "phase2-shots/phase2b-cont-detail-desktop.png", fullPage: true });
});

test("shell — sidebar persists across /services → category → detail chain", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto("/services", { waitUntil: "domcontentloaded" });

  const sidebar = page.locator('[data-testid="car-sidebar"]');
  await expect(sidebar).toBeVisible();
  const handleAtServices = await sidebar.elementHandle();

  // Phase 2c — on /services the bar selects a TAB in-place; the hop to the
  // full category PAGE is the "View full page →" link.
  await page.locator('nav [data-cat-slug="regular-car-service"]').click();
  await expect(page.getByText("primary service", { exact: false }).first()).toBeVisible();
  await page.getByRole("button", { name: /view full page/i }).click();
  await expect(page).toHaveURL(/\/category\//);
  await expect(page.locator("main article").first()).toBeVisible();
  expect(await handleAtServices!.evaluate((el) => el.isConnected)).toBe(true);

  // category → detail (client-side). Wait for detail-only loaded content.
  await page.locator("main article", { hasText: /primary service/i }).first()
    .locator("button").first().click();
  await expect(page.getByRole("heading", { name: /AFTER YOU BOOK/i })).toBeVisible();
  expect(await handleAtServices!.evaluate((el) => el.isConnected)).toBe(true);
});

test("detail — grouped What's Included from real data + meta + NO unsplash hero", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(SERVICE, { waitUntil: "domcontentloaded" });

  // Grouped headings render (groupInclusions → Essential/Performance image
  // cards, Additional checklist). acr_v3 has all three for this service.
  await expect(page.getByRole("heading", { name: /INCLUDED/i }).first()).toBeVisible();
  await expect(page.getByRole("heading", { name: "Essential", exact: true })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Performance", exact: true })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Additional", exact: true })).toBeVisible();

  // Real inclusion labels (not the old static 6-item array).
  await expect(page.getByText("Engine Oil Replacement", { exact: false })).toBeVisible();
  await expect(page.getByText("Spark Plug Cleaning", { exact: false })).toBeVisible();

  // Meta row (detail): real warranty + static Free pickup. Non-null only.
  // exact:true so the meta-row label doesn't collide with the CTA copy
  // ("…Free pickup & drop"), which legitimately mentions it too.
  await expect(page.getByText("Warranty 1000 kms or 1 month", { exact: false })).toBeVisible();
  await expect(page.getByText("Pickup & Drop", { exact: true })).toBeVisible();

  // Hero fallback: service.image is null → the Unsplash banner image is
  // killed (D-2b-7). No unsplash <img> anywhere on the detail page.
  expect(await page.locator('img[src*="unsplash"]').count()).toBe(0);

  // Navy Steps-After-Booking band present.
  await expect(page.getByRole("heading", { name: /AFTER YOU BOOK/i })).toBeVisible();

  await page.setViewportSize({ width: 390, height: 844 });
  await page.reload({ waitUntil: "domcontentloaded" });
  await expect(page.getByRole("heading", { name: /INCLUDED/i }).first()).toBeVisible();
  await expect(page.getByText("Engine Oil Replacement", { exact: false })).toBeVisible();
  await page.screenshot({ path: "phase2-shots/phase2b-cont-detail-mobile.png", fullPage: true });
});

test("cart — add from detail sidebar toggles Added + appears in cart (no regression)", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(SERVICE, { waitUntil: "domcontentloaded" });

  const sidebar = page.locator('[data-testid="car-sidebar"]');
  await expect(sidebar).toBeVisible();

  // The shell sidebar offers a single toggle CTA for the current detail
  // service. Guest carts can survive across runs server-side, so normalise
  // to the "Add to Cart" state first (remove a leftover line if present).
  await expect(
    sidebar.getByRole("button", { name: /add to cart|added/i })
  ).toBeVisible();
  if (await sidebar.getByRole("button", { name: /^\s*added\s*$/i }).count()) {
    await sidebar.getByRole("button", { name: /^\s*added\s*$/i }).click();
    await expect(
      sidebar.getByRole("button", { name: /add to cart/i })
    ).toBeVisible({ timeout: 10_000 });
  }

  await sidebar.getByRole("button", { name: /add to cart/i }).click();

  // Toggles to "Added" and the service line shows up in the cart summary.
  // Generous timeout: add is a backend POST + React Query refetch.
  await expect(
    sidebar.getByRole("button", { name: /added/i })
  ).toBeVisible({ timeout: 10_000 });
  await expect(
    sidebar.getByText(/primary service/i).first()
  ).toBeVisible({ timeout: 10_000 });

  await page.screenshot({ path: "phase2-shots/phase2b-cont-cart-populated.png", fullPage: true });
});

test("D-2d-1/2 — category bar renders BELOW the banner, sticky, with an ACR-blue active underline", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });

  const barBtn = page.locator('nav [data-cat-slug="regular-car-service"]');
  await expect(barBtn).toBeVisible({ timeout: 10_000 });

  // DOM order (D-2d-1): the PageBanner h1 comes BEFORE the category bar.
  const order = await page.evaluate(() => {
    const banner = document.querySelector(".page-title");
    const navEl = document.querySelector("nav [data-cat-slug]")?.closest("nav");
    if (!banner || !navEl) return "missing";
    return banner.compareDocumentPosition(navEl) & Node.DOCUMENT_POSITION_FOLLOWING
      ? "banner-first"
      : "bar-first";
  });
  expect(order).toBe("banner-first");

  // The bar is still sticky.
  const bar = page.locator("nav").filter({ has: page.locator("[data-cat-slug]") }).first();
  expect(await bar.evaluate((el) => getComputedStyle(el).position)).toBe("sticky");

  // Active item = ACR BLUE underline (#1F4FA3 = rgb(31,79,163)), NOT red.
  const active = page.locator('nav [data-cat-slug="regular-car-service"][aria-current="page"]');
  await expect(active).toBeVisible();
  expect(await active.evaluate((el) => getComputedStyle(el).borderBottomColor)).toBe(
    "rgb(31, 79, 163)"
  );
});

test("category — price 4-state intact (₹ with vehicle, Select car without)", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });

  // No vehicle → "Select car" invite (not a price).
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(
    page.getByText("Select car", { exact: false }).first()
  ).toBeVisible({ timeout: 10_000 });

  // Vehicle seeded → at least one ₹ price reveals (vehicleSelected + price_show).
  // Generous timeout: prices are resolved server-side per vehicle tuple.
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(
    page.locator("main article").getByText(/₹\s?\d/).first()
  ).toBeVisible({ timeout: 10_000 });
});

/* ════════════════════════════════════════════════════════════════════
 * Phase 2c — Layer-1 active-category TABS + shared ServiceCard.
 * /services shows ONLY the active category's cards; the shell's ONE
 * cross-category bar selects the tab in-place (URL stays /services, no
 * route change, sidebar never remounts). ServiceCard is shared with
 * Layer 2. "View full page →" routes to /category/:slug.
 * ════════════════════════════════════════════════════════════════════ */

test("L1 tabs — switch swaps cards in place; URL stays /services; sidebar same node", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto("/services", { waitUntil: "domcontentloaded" });

  const sidebar = page.locator('[data-testid="car-sidebar"]');
  await expect(sidebar).toBeVisible();
  const handle = await sidebar.elementHandle();

  // Default tab = first category (Car Battery): its card shows; a
  // regular-car-service item does NOT (only the active category renders).
  await expect(page.getByText("Battery Charging", { exact: false }).first()).toBeVisible();
  await expect(page.locator('nav [data-cat-slug="car-battery"][aria-current="page"]')).toBeVisible();
  await expect(page.getByText("primary service", { exact: false })).toHaveCount(0);
  await page.screenshot({ path: "phase2-shots/phase2c-services-tabA-desktop.png", fullPage: true });

  // Click the Regular Car Service tab — in-place, NO navigation.
  await page.locator('nav [data-cat-slug="regular-car-service"]').click();

  await expect(page).toHaveURL(/\/services$/); // URL unchanged
  await expect(page.getByText("primary service", { exact: false }).first()).toBeVisible();
  await expect(page.getByText("Battery Charging", { exact: false })).toHaveCount(0);
  await expect(page.locator('nav [data-cat-slug="regular-car-service"][aria-current="page"]')).toBeVisible();

  // The tab switch did NOT remount the sidebar (same DOM node, exactly one).
  expect(await handle!.evaluate((el) => el.isConnected)).toBe(true);
  expect(await page.locator('[data-testid="car-sidebar"]').count()).toBe(1);

  await page.screenshot({ path: "phase2-shots/phase2c-services-tabB-desktop.png", fullPage: true });
});

test("L1 tabs — 'View full page' routes to the full /category page", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await page.locator('nav [data-cat-slug="regular-car-service"]').click();
  await expect(page.getByText("primary service", { exact: false }).first()).toBeVisible();

  await page.getByRole("button", { name: /view full page/i }).click();
  await expect(page).toHaveURL(/\/category\/regular-car-service$/);
  // Layer-2-only content (a marketing section that Layer 1 never renders)
  // confirms we're on the full page. (The in-page section nav was removed
  // in Phase 2d.)
  await expect(page.getByRole("heading", { name: /CHOOSE US/i })).toBeVisible();
});

test("ServiceCard parity — identical anatomy on Layer-1 tab and Layer-2 page", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);

  // Layer 1 — Regular Car Service tab.
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await page.locator('nav [data-cat-slug="regular-car-service"]').click();
  const l1 = page.locator("main article", { hasText: /primary service/i }).first();
  await expect(l1).toBeVisible();
  await expect(l1.getByText("Engine Oil Replacement", { exact: false })).toBeVisible(); // inclusions_preview
  await expect(l1.getByText(/₹\s?\d/).first()).toBeVisible({ timeout: 10_000 });        // price 4-state
  await expect(l1.locator('[data-testid="explore-card-fallback"]')).toBeVisible();        // image-null fallback

  // Layer 2 — full category page: SAME card anatomy.
  await page.goto("/category/regular-car-service", { waitUntil: "domcontentloaded" });
  const l2 = page.locator("main article", { hasText: /primary service/i }).first();
  await expect(l2).toBeVisible();
  await expect(l2.getByText("Engine Oil Replacement", { exact: false })).toBeVisible();
  await expect(l2.getByText(/₹\s?\d/).first()).toBeVisible({ timeout: 10_000 });
  await expect(l2.locator('[data-testid="explore-card-fallback"]')).toBeVisible();

  await page.screenshot({ path: "phase2-shots/phase2c-category-l2-desktop.png", fullPage: true });
});

test("L1 tabs — add to cart from a card works (no regression)", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await page.locator('nav [data-cat-slug="regular-car-service"]').click();

  const card = page.locator("main article", { hasText: /primary service/i }).first();
  await expect(card).toBeVisible();

  // Normalise to "Add to Cart" first (guest carts can persist across runs).
  if (await card.getByRole("button", { name: /^\s*added\s*$/i }).count()) {
    await card.getByRole("button", { name: /^\s*added\s*$/i }).click();
    await expect(card.getByRole("button", { name: /add to cart/i })).toBeVisible({ timeout: 10_000 });
  }
  await card.getByRole("button", { name: /add to cart/i }).click();
  await expect(card.getByRole("button", { name: /added/i })).toBeVisible({ timeout: 10_000 });
});

test("L1 tabs — mobile 390 tab A + tab B (in-place swap)", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await seedVehicle(page);
  await page.goto("/services", { waitUntil: "domcontentloaded" });

  await expect(page.getByText("Battery Charging", { exact: false }).first()).toBeVisible();
  await page.screenshot({ path: "phase2-shots/phase2c-services-tabA-mobile.png", fullPage: true });

  await page.locator('nav [data-cat-slug="regular-car-service"]').click();
  await expect(page).toHaveURL(/\/services$/);
  await expect(page.getByText("primary service", { exact: false }).first()).toBeVisible();
  await page.screenshot({ path: "phase2-shots/phase2c-services-tabB-mobile.png", fullPage: true });
});

/* ════════════════════════════════════════════════════════════════════
 * Phase 2d — category bar reposition + icon redesign; remove the
 * personalised-price pill; Layer-2 drops Brands + the section-nav.
 * ════════════════════════════════════════════════════════════════════ */

test("D-2d-3 — the 'Prices personalised for' pill is GONE on Layer 1 and Layer 2", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page); // a vehicle is selected → the pill WOULD have shown pre-2d

  // Layer 1 (/services active tab).
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await expect(page.getByText("Battery Charging", { exact: false }).first()).toBeVisible();
  await expect(page.getByText(/Prices personalised for/i)).toHaveCount(0);

  // Layer 2 (/category/:slug).
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(page.locator("main article").first()).toBeVisible();
  await expect(page.getByText(/Prices personalised for/i)).toHaveCount(0);
});

test("D-2d-4 — Layer 2 has no Brands section nor section-nav; other sections + sidebar + cart intact", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(page.locator("main article").first()).toBeVisible();

  // Brands section + the section-nav scroller are gone.
  await expect(page.getByRole("heading", { name: /BRANDS WE/i })).toHaveCount(0);
  await expect(page.locator("[data-subnav-link]")).toHaveCount(0);

  // Remaining content sections still render.
  await expect(page.getByRole("heading", { name: /CHOOSE US/i })).toBeVisible();   // Why Us
  await expect(page.getByRole("heading", { name: /HOW IT/i })).toBeVisible();        // Process
  await expect(page.getByRole("heading", { name: /COMMON/i })).toBeVisible();        // FAQs

  // Sidebar persists; cart add from a Layer-2 card still works.
  await expect(page.locator('[data-testid="car-sidebar"]')).toBeVisible();
  const card = page.locator("main article", { hasText: /primary service/i }).first();
  await expect(card).toBeVisible();
  if (await card.getByRole("button", { name: /^\s*added\s*$/i }).count()) {
    await card.getByRole("button", { name: /^\s*added\s*$/i }).click();
    await expect(card.getByRole("button", { name: /add to cart/i })).toBeVisible({ timeout: 10_000 });
  }
  await card.getByRole("button", { name: /add to cart/i }).click();
  await expect(card.getByRole("button", { name: /added/i })).toBeVisible({ timeout: 10_000 });

  await page.screenshot({ path: "phase2-shots/phase2d-category-l2-desktop.png", fullPage: true });
});

test("D-2d screenshots — /services icon bar + Layer 2 cleaned (desktop + mobile)", async ({ page }) => {
  await seedVehicle(page);

  // Desktop /services — icon bar below the banner, a tab active.
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await expect(page.locator("nav [data-cat-slug]").first()).toBeVisible({ timeout: 10_000 });
  await expect(page.getByText("Battery Charging", { exact: false }).first()).toBeVisible();
  await page.screenshot({ path: "phase2-shots/phase2d-services-iconbar-desktop.png", fullPage: true });

  // Mobile /services.
  await page.setViewportSize({ width: 390, height: 844 });
  await page.reload({ waitUntil: "domcontentloaded" });
  await expect(page.locator("nav [data-cat-slug]").first()).toBeVisible({ timeout: 10_000 });
  await page.screenshot({ path: "phase2-shots/phase2d-services-iconbar-mobile.png", fullPage: true });

  // Mobile Layer 2 (no section-nav / no Brands).
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(page.locator("main article").first()).toBeVisible({ timeout: 10_000 });
  await page.screenshot({ path: "phase2-shots/phase2d-category-l2-mobile.png", fullPage: true });
});

/* ════════════════════════════════════════════════════════════════════
 * Phase 2e — GoMechanic-clean polish: contained bar w/ bigger icons +
 * active blue-tint pill; tighter site container padding; in-place
 * inclusions expand.
 * ════════════════════════════════════════════════════════════════════ */

test("D-2e — category bar is CONTAINED to max-width, with a blue-tint active pill + real icons", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });

  const active = page.locator('nav [data-cat-slug="regular-car-service"][aria-current="page"]');
  await expect(active).toBeVisible({ timeout: 10_000 });

  // Active has a soft (non-transparent) blue-tint PILL fill that inactive
  // items lack. Tailwind v4 emits bg-primary/10 as oklab(…/0.1), so compare
  // structurally (active vs inactive) rather than by an rgb string.
  const activeBg = await active.evaluate((el) => getComputedStyle(el).backgroundColor);
  const inactiveBg = await page
    .locator('nav [data-cat-slug]:not([aria-current="page"])')
    .first()
    .evaluate((el) => getComputedStyle(el).backgroundColor);
  const transparent = ["rgba(0, 0, 0, 0)", "transparent"];
  expect(transparent).not.toContain(activeBg); // active HAS a pill fill
  expect(transparent).toContain(inactiveBg); // inactive is transparent
  expect(activeBg).not.toBe(inactiveBg);
  // Underline = full-opacity ACR blue (#1F4FA3 → rgb), NOT red.
  expect(await active.evaluate((el) => getComputedStyle(el).borderBottomColor)).toBe(
    "rgb(31, 79, 163)"
  );

  // Real uploaded icon renders as an <img> (icon_image), ~32px (bigger).
  const icon = active.locator("img");
  await expect(icon).toBeVisible();
  expect((await icon.boundingBox())!.width).toBeGreaterThanOrEqual(28);

  // Items are CONTAINED to the site max-width (NOT edge-to-edge): the bar's
  // .site-container is ≤ ~1340px and centered at a 1440 viewport.
  const barContainer = page
    .locator("nav")
    .filter({ has: page.locator("[data-cat-slug]") })
    .first()
    .locator(".site-container")
    .first();
  const cbox = await barContainer.boundingBox();
  expect(cbox!.width).toBeLessThanOrEqual(1340);
  expect(cbox!.x).toBeGreaterThan(30);
});

test("D-2e — '+N more' EXPANDS inclusions in place (no navigation) + toggles; title still navigates", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await seedVehicle(page);
  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" }); // primary service has 9 inclusions

  const card = page.locator("main article", { hasText: /primary service/i }).first();
  await expect(card).toBeVisible({ timeout: 10_000 });

  // Collapsed: the extra (beyond first 4) inclusions are NOT in the DOM.
  await expect(card.locator('[data-testid="inclusion-extra"]')).toHaveCount(0);
  const toggle = card.getByRole("button", { name: /\d+ more/i });
  await expect(toggle).toBeVisible();

  // Expand IN PLACE — URL must stay on the category page.
  await toggle.click();
  await expect(page).toHaveURL(/\/category\/regular-car-service$/);
  await expect(card.locator('[data-testid="inclusion-extra"]').first()).toBeVisible();
  await expect(card.getByRole("button", { name: /show less/i })).toBeVisible();
  await page.screenshot({ path: "phase2-shots/phase2e-card-expanded-desktop.png", fullPage: true });

  // Collapse back.
  await card.getByRole("button", { name: /show less/i }).click();
  await expect(card.locator('[data-testid="inclusion-extra"]')).toHaveCount(0);

  // The card TITLE is a separate control → still navigates to the detail page.
  await card.locator("button").first().click();
  await expect(page).toHaveURL(/\/services\/regular-car-service\/primary-service$/);
});

test("D-2e screenshots — contained bar + tighter padding + Layer-2 (desktop + mobile)", async ({ page }) => {
  await seedVehicle(page);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await expect(page.locator("nav [data-cat-slug]").first()).toBeVisible({ timeout: 10_000 });
  await expect(page.getByText("Battery Charging", { exact: false }).first()).toBeVisible({ timeout: 10_000 });
  await page.screenshot({ path: "phase2-shots/phase2e-services-desktop.png", fullPage: true });
  // Full-resolution close-up of just the bar (to judge icon size).
  await page
    .locator("nav")
    .filter({ has: page.locator("[data-cat-slug]") })
    .first()
    .screenshot({ path: "phase2-shots/phase2e-bar-closeup.png" });

  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(page.locator("main article").first()).toBeVisible({ timeout: 10_000 });
  await page.screenshot({ path: "phase2-shots/phase2e-category-l2-desktop.png", fullPage: true });

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/services", { waitUntil: "domcontentloaded" });
  await expect(page.locator("nav [data-cat-slug]").first()).toBeVisible({ timeout: 10_000 });
  await expect(page.getByText("Battery Charging", { exact: false }).first()).toBeVisible({ timeout: 10_000 });
  await page.screenshot({ path: "phase2-shots/phase2e-services-mobile.png", fullPage: true });

  await page.goto(CATEGORY, { waitUntil: "domcontentloaded" });
  await expect(page.locator("main article").first()).toBeVisible({ timeout: 10_000 });
  await page.screenshot({ path: "phase2-shots/phase2e-category-l2-mobile.png", fullPage: true });
});
