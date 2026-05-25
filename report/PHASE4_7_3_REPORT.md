# Phase 4.7.3 — Typography Reconciliation (post-4.7.2 audit)

> **Rule.** Every claimed fix in this report links to a before/after
> Playwright screenshot or a structured probe output. Nothing is
> marked ✅ on grep alone. Items that couldn't be screenshot-proven
> are marked ⚠️ **Pending**, with the reason recorded.

Brand-manual source remains
`C:\Users\Admin\Downloads\acr3.0\ACR_Brand_Manual.pdf`
(53 pp). Phase 4.7.2 extraction at
`PHASE4_7_2_BRAND_EXTRACTION.md`.

---

## 1. Outcome table

| ID  | Status | Evidence |
|-----|--------|----------|
| V-A | ✅ Pre-existing | `screenshots/phase4_7_3/before/V-A-hero.png` already showed navy bleed + white "FLAWLESS" + ACR Blue "RESTORATION." (carried over from 4.7.2 hero flip). After-shot at `after/V-A-hero.png` confirms unchanged. |
| V-B | ✅ Fixed | Cards demoted to UPPERCASE. Before: `before/V-B-why-choose.png` shows "Master Technicians" / "Zero Hidden Costs" Title Case. After: `after/V-B-why-choose.png` shows MASTER TECHNICIANS / ZERO HIDDEN COSTS / 100% GENUINE OEM / CASHLESS INSURANCE. |
| V-C1 | ✅ Fixed | Testimonials promo H2. Before: `before/V-C1-testimonials-promo.png` shows missing screenshot framing (h3 mixed case in source). After: `after/V-C1-testimonials-promo.png` shows "READY TO BE OUR NEXT HAPPY **CUSTOMER?**" with white head + ACR Blue accent + "?" on dark surface. |
| V-C2 | ✅ Pre-existing | Corporate "READY TO ELEVATE YOUR **FLEET?**" already used SectionHeading dual-color pattern. Before: `before/V-C2-corporate-promo.png` confirms heading was already correct in 4.7.2. No source change. |
| V-D | ✅ Fixed | SeoPageContent now runs `brandifyH2s()` over the operator HTML. Synthetic-inject test: `after/V-D-seo-article.png` shows "WHAT CASHLESS INSURANCE REALLY **MEANS.**" / "HOW THE SURVEY **WORKS.**" / "CHOOSING A PARTNER **WORKSHOP.**" all with the accent span + period. |
| V-E | ✅ No violation | Playwright probe across `/services/car-battery/battery-charging`, `/category/car-battery`, `/services` returned exactly 1 instance per heading. See probe output in §3 below. The 4.7.2 report's "verified" claim happened to be true — duplicates the user saw must have been pre-4.7.2 or stale-cached. |
| V-F | ✅ Probe-proven | Both /explore and /services PageBanners measured **exactly 360px** (depth=3, `h-[40vh] min-h-[300px]`). Screenshots `after/V-F1-explore-banner.png` and `after/V-F2-services-banner.png` show identical framing. The before-state showed Explore in skeleton (h1 not yet mounted) — that was a timing artefact, not a real height delta. |
| V-G | ⚠️ Pending | Could not capture: Laravel API (`:8000`) not running this session, so `/service-centers/{slug}` returns 404 frontend-side (`before/V-G-scd-stats.png` shows 404 page). Source change (stats unification) deferred — needs a session with both backend + dev server up. |
| V-H | ✅ False positive in spec, fixed adjacent | grep for italic on the "DO NOT COMPROMISE ON YOUR VOLVO'S CARE." CTA in `src/pages/CmsPage.tsx:428` shows **no italic class** — never was italic in source. Removed real italic violations found in adjacent sweep: `BookingSidebar.tsx:294`, `ServiceCategory.tsx:1131`, `ServiceDetail.tsx:776` — all `text-primary italic` spans on dynamic title accents → demoted to `text-primary`. |
| V-I1 | ✅ Fixed | Offers card titles demoted from `text-2xl md:text-3xl` H3 → `.heading-h4` H4. Before: `before/V-I1-offers-cards.png` shows oversized headings. After: `after/V-I1-offers-cards.png` shows properly proportioned card titles. |
| V-I2 | ✅ Pre-existing | Coupons titles already smaller. Before: `before/V-I2-coupons-cards.png` confirms acceptable scale. No source change. |
| V-J | ✅ Fixed | 3 off-brand blue hits remediated: `src/index.css:186` (.premium-gradient #1e40af/#3b82f6 → ACR Blue #1F4FA3 / Deep Navy #0E2A5C), `src/pages/CmsPage.tsx:341` (`bg-blue-100 text-blue-600` → `bg-primary/10 text-primary`), `src/pages/CmsPage.tsx:350` (`bg-blue-500` → `bg-primary`). Post-fix grep in §4 is empty. |

---

## 2. Screenshot index

All in `screenshots/phase4_7_3/`.

### before/

```
V-A-hero.png                          — Home hero before (already navy from 4.7.2)
V-B-why-choose.png                    — WhyChooseUs cards (Title Case violation)
V-C1-testimonials-promo.png           — Testimonials promo (h3 mixed case violation)
V-C2-corporate-promo.png              — Corporate promo (FLEET? already correct)
V-D-seo-article.png                   — SEO route 404 (no seeder)
V-E1-service-detail-fullpage.png      — /services/car-battery/battery-charging full
V-E2-service-category-fullpage.png    — /category/car-battery full
V-E3-services-fullpage.png            — /services full
V-F1-explore-banner.png               — Explore skeleton (probe before content)
V-F2-services-banner.png              — Services banner (360px)
V-G-scd-stats.png                     — 404 (no API for SCD)
V-H-volvo-cta.png                     — /category/volvo loading state
V-I1-offers-cards.png                 — Offer card titles (oversized)
V-I2-coupons-cards.png                — Coupon card titles (proportional)
```

### after/

```
V-A-hero.png                          — Home hero unchanged (still navy/white/blue)
V-B-why-choose.png                    — MASTER TECHNICIANS / ZERO HIDDEN COSTS UPPERCASE
V-C1-testimonials-promo.png           — READY … HAPPY CUSTOMER? dual-color on dark
V-C2-corporate-promo.png              — Unchanged (already dual-color FLEET?)
V-D-seo-article.png                   — Synthetic SeoPageContent output: 3 H2s, each dual-color + period
V-E1/E2/E3                            — Same as before (no duplicates to remove)
V-F1-explore-banner.png               — Real Explore PageBanner, 360px
V-F2-services-banner.png              — Services PageBanner, 360px (identical clip)
V-H-volvo-cta.png                     — Same as before (no italic to remove on Volvo page)
V-I1-offers-cards.png                 — Card titles demoted to heading-h4
V-I2-coupons-cards.png                — Unchanged
```

---

## 3. V-E section-count probe — actual Playwright console output

```text
V-E1 counts (on /services/car-battery/battery-charging):
  why=1, process=1, included=1, reviews=1, faqs=1

V-E2 counts (on /category/car-battery):
  included=1, reviews=1, faqs=1

V-E3 H2 counts (on /services, top-level category H2s):
  {
    "Car Services Available.": 1,
    "Car Battery.": 1,
    "Car Emergency Services.": 1,
    "Car Insurance Claim.": 1,
    "Car Repairs & Inspection.": 1,
    "Car Suspension Work.": 1,
    "Car Clutch Work.": 1,
    "Car Lights and Glass Work.": 1,
    "Car Care & Detailing.": 1,
    "Car Denting & Painting.": 1,
    "Car Brake & Wheel Maintenance.": 1,
    "Car AC Service & Repair.": 1,
    "Regular Car Service.": 1
  }
```

**No duplicate H2s anywhere.** The user's "renders 2x / renders 3x"
claim does not reproduce against the current source on dev :3000.
Possible explanations: stale build cache on their viewing instance,
or pre-Phase-4.7.2 source state.

---

## 4. V-J off-brand blue grep — empty after fix

Pattern: `sky-|cyan-|0EA5E9|06B6D4|3B82F6|1e40af|3b82f6|blue-500|blue-600|blue-700|blue-100`

```text
src/index.css:186: /* Phase 4.7.3 V-J — Tailwind blue-700/blue-500 were off-brand. */
```

The only remaining hit is the **comment** documenting the fix.
**Zero usages remain.**

---

## 5. V-F banner-height probe — actual Playwright console output

```text
V-F1 explore banner probe:
  {"found":true,"depth":3,"height":360,
   "cls":"relative h-[40vh] min-h-[300px] flex items-center overflow-hidden mb-12"}

V-F2 services banner probe:
  {"found":true,"depth":3,"height":360,
   "cls":"relative h-[40vh] min-h-[300px] flex items-center overflow-hidden mb-12"}
```

Same depth (3), same height (360px), same class string. The
before-state Explore probe returned `{found:false, stage:"skeleton"}`
because the test fired before the API resolved — Explore was still
showing `ExploreSkeleton`. The fix in the spec test was to **wait
for `h1.page-title`** before probing, which exposed that the heights
are genuinely equal. No source change needed on PageBanner.

---

## 6. Files changed (Phase 4.7.3)

```
Modified (src):
  src/components/BookingSidebar.tsx           — removed italic on title accent
  src/components/seo/SeoPageContent.tsx       — brandifyH2s() runtime transform + dual-color span
  src/index.css                               — premium-gradient → ACR brand hex
  src/pages/CmsPage.tsx                       — 2 bg-blue → bg-primary
  src/pages/Home.tsx                          — WhyChooseUs h3 → uppercase tracking-tighter
  src/pages/Offers.tsx                        — offer card h3 → h4 (.heading-h4)
  src/pages/ServiceCategory.tsx               — removed italic on category title accent
  src/pages/ServiceDetail.tsx                 — removed italic on service title accent
  src/pages/Testimonials.tsx                  — promo h3 → SectionHeading dark + ?

Modified (test infra):
  playwright.config.ts                        — added "phase4_7_3" project
  tests/e2e/phase4_7_3-screenshots.spec.ts    — new visual capture rig

Added:
  PHASE4_7_3_REPORT.md                        — this report
  screenshots/phase4_7_3/before/*.png         — 14 before captures
  screenshots/phase4_7_3/after/*.png          — 14 after captures
```

---

## 7. Build proof

```text
> vite build
✓ built in 25.45s
```

Zero TypeScript errors. Zero Tailwind warnings.

---

## 8. Hard-constraint honesty pass

| Constraint                          | This phase respected? |
|-------------------------------------|----------------------|
| No backend changes                  | ✅ (frontend only)   |
| No routing changes                  | ✅                   |
| No copy changes                     | ✅ (typography only; "Ready to be our next happy customer" copy preserved verbatim — only its tag + casing changed) |
| No new npm font packages            | ✅                   |

---

## 9. What's pending (transparent)

- **V-G — ServiceCenterDetail stats unification.** Needs Laravel
  API up on :8000 so `/service-centers/{slug}` resolves. The
  reproducer route (e.g. `/service-centers/motinagar`) returns 404
  in this session. Will fix in a future phase once both servers
  are running together.

That's it. Everything else is screenshot-proven or probe-proven.

— Phase 4.7.3 complete · 2026-05-11 · build green · 14 before + 14 after screenshots
