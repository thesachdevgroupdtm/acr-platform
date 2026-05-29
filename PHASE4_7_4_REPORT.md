# Phase 4.7.4 — Home Page Typography Unification

> **Rule (carried from 4.7.3).** Every claimed fix in this report
> ties to a Playwright screenshot or a computed-style inventory
> probe. No grep-only claims.

Brand manual source:
`C:\Users\Admin\Downloads\acr3.0\ACR_Brand_Manual.pdf`
(53 pp · CR-2 H2 spec on p. 22).

---

## 1. The violation Phase 4.7.3 missed

`tests/e2e/phase4_7_4-screenshots.spec.ts` runs a `getComputedStyle`
probe across every H2 on `/` vs `/offers`. The **before** state:

| Page    | H2 text              | Computed font-weight | Computed color           |
|---------|----------------------|----------------------|--------------------------|
| /       | "More Than Repairs. Absolute Trust." | **900**  | rgb(14, 42, 92)  ← Deep Navy |
| /       | "Specialized Care."  | **900**              | rgb(14, 42, 92)          |
| /       | "Current Offers."    | **900**              | rgb(14, 42, 92)          |
| /       | "Why Choose Us?"     | **900**              | rgb(14, 42, 92)          |
| /       | "The Transformation."| **900**              | rgb(14, 42, 92)          |
| /       | "Absolute Trust."    | **900**              | rgb(14, 42, 92)          |
| /       | "Our Service Centers." | **900**            | rgb(255, 255, 255)       |
| /       | "Car Care Blog."     | **900**              | rgb(14, 42, 92)          |
| /offers | "LIMITED TIME OFFERS." | **600**            | oklch(0.205 0 0) ← Workshop Black |
| /offers | "NEED A CUSTOM SOLUTION?" | **600**          | rgb(255, 255, 255)       |

Eight Home H2s rendered at weight **900** with `text-primary-dark`
(Deep Navy #0E2A5C). The brand-manual H2 spec on p. 22 says
**SemiBold (600)** with Workshop Black (#111111). The visual delta
was real and measurable. Phase 4.7.3 fixed individual headings in
isolation but never compared Home against the reference set.

---

## 2. Outcome table

| H2 / element              | Section                       | Status                       |
|---------------------------|-------------------------------|------------------------------|
| "More Than Repairs. Absolute Trust." | THE ACR STANDARD     | ✅ Migrated (Option b — accent on last word of 2nd sentence) |
| "Specialized Care."       | OUR EXPERTISE                 | ✅ `<SectionHeading>`        |
| "Current Offers."         | EXCLUSIVE DEALS               | ✅ `<SectionHeading>`        |
| "Why Choose Us?"          | (no eyebrow)                  | ✅ `<SectionHeading terminator="?">` |
| "The Transformation."     | VISUAL PROOF                  | ✅ `<SectionHeading>`        |
| "Absolute Trust."         | CUSTOMER STORIES              | ✅ `<SectionHeading>`        |
| "Our Service Centers."    | OUR NETWORK (dark)            | ✅ `<SectionHeading background="dark">` |
| "Car Care Blog."          | LATEST INSIGHTS               | ✅ `<SectionHeading>`        |
| "India's Fastest-Growing Self-Owned Multi-Brand Network." | Footer pre-section | ✅ `<SectionHeading>` in `Footer.tsx` |
| Eyebrow labels (×7)       | All Home sections             | ✅ Removed (Option A — recommended) |

---

## 3. The visual proof set

### 3.1 H2 inventory probe — after

`tests/e2e/phase4_7_4-screenshots.spec.ts` `home-h2-inventory`
console output (every H2 on `/` after fixes):

```text
{
  "text": "FLAWLESS RESTORATION.",      "fontWeight": "700", "color": "rgb(255, 255, 255)",   "hasSectionHeading": false   // H1, not H2
},
{
  "text": "More Than Repairs. Absolute Trust.",
  "fontWeight": "600", "color": "oklch(0.205 0 0)",         "hasSectionHeading": true
},
{ "text": "Specialized Care.",         "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "Current Offers.",           "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "Why Choose Us?",            "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "The Transformation.",       "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "Absolute Trust.",           "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "Our Service Centers.",      "fontWeight": "600", "color": "rgb(255, 255, 255)",     "hasSectionHeading": true },
{ "text": "Fleet Maintenance.",        "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "QUESTIONS WE GET ASKED.",   "fontWeight": "600", "color": "rgb(255, 255, 255)",     "hasSectionHeading": true },
{ "text": "Car Care Blog.",            "fontWeight": "600", "color": "oklch(0.205 0 0)",       "hasSectionHeading": true },
{ "text": "REPAIR YOU CAN TRUST.",     "fontWeight": "600", "color": "rgb(255, 255, 255)",     "hasSectionHeading": true },
{ "text": "India's Fastest-Growing Self-Owned Multi-Brand Network.",
  "fontWeight": "600", "color": "oklch(0.205 0 0)",          "hasSectionHeading": true }
```

**Every H2 on the home page now renders at weight 600 with the
canonical `.section-heading` utility.** Light-bg H2s read as
Workshop Black (`oklch(0.205 0 0)`); dark-bg H2s read as Clean
White (`rgb(255, 255, 255)`). Identical to the /offers reference.

### 3.2 Side-by-side: Home "CURRENT OFFERS." vs /offers "LIMITED TIME OFFERS."

`screenshots/phase4_7_4/after/side-by-side-current-offers.png`

Two iframes mounted side-by-side, each scrolled to its respective
H2. Both show:
- Same Montserrat SemiBold weight
- Same black head text
- Same ACR Blue accent word + period
- Same `.section-heading` clamp size

The visual treatment is identical. Phase 4.7.4 unification target met.

### 3.3 Full-page Home capture

`screenshots/phase4_7_4/after/home-fullpage.png`

Top-to-bottom Home page after fixes. Every section's H2 renders in
the unified style. No orange-dash eyebrows remain (Option A —
removed for consistency with the rest of the site).

### 3.4 Footer pre-section

`screenshots/phase4_7_4/after/footer-network.png`

"INDIA'S FASTEST-GROWING SELF-OWNED MULTI-BRAND **NETWORK.**" now
renders with the canonical dual-color treatment — black head + ACR
Blue accent on the last word + period terminator. The previous
heavy navy `<h3>` is gone.

### 3.5 Home "CURRENT OFFERS." in isolation

`screenshots/phase4_7_4/before/home-current-offers.png` vs
`screenshots/phase4_7_4/after/home-current-offers.png`:

| Before                                                       | After                                                       |
|--------------------------------------------------------------|-------------------------------------------------------------|
| **Bold** font-black 900 navy "**CURRENT**" + heavy "**OFFERS.**" | SemiBold 600 black "CURRENT" + ACR Blue "OFFERS."           |
| Orange-dash eyebrow "EXCLUSIVE DEALS"                        | No eyebrow                                                  |
| Section anchored by heavy heading                            | Section anchored by lighter, on-brand heading               |

---

## 4. Grep proof — empty after fix

### 4.1 Heavy weight on H2 in `src/pages/Home.tsx` + `src/components/`

```bash
grep -rE '<h2[^>]*font-(black|extrabold)' src/pages/Home.tsx src/components/
```

**Hits in `src/pages/Home.tsx`: zero.**

Remaining hits in `src/components/` (all on **non-marketing-section
H2 elements** — flagged for separate review, not auto-fixed per
spec):

```
src/components/AuthModal.tsx:141           — modal title H2
src/components/AuthModal.tsx:328           — modal title H2
src/components/AuthModal.tsx:412           — modal title H2
src/components/AuthModal.tsx:481           — modal title H2
src/components/CancelOrderModal.tsx:80     — modal title H2
src/components/ChunkErrorBoundary.tsx:91   — error boundary H2
src/components/CouponPickerModal.tsx:120   — modal title H2
src/components/LogoutConfirmModal.tsx:58   — modal title H2
src/components/VehicleReplaceModal.tsx:64  — modal title H2
src/components/seo/cards/FeatureCard.tsx:52  — card-internal H2
src/components/seo/cards/StandardCard.tsx:37 — card-internal H2
```

These were explicitly **out of Phase 4.7 scope** per the original
boundary (modals, post-action UI, card-internal headings). The spec
in this task says *"flag those separately, don't auto-fix"* — done.

### 4.2 `text-primary-dark` on h1/h2/h3

```bash
grep -rE '<h[1-3][^>]*text-primary-dark' src/pages/Home.tsx src/components/
```

**Hits: zero.** Every heading on Home + every component heading is
off `text-primary-dark`. The 7 H3 sub-headings I touched (Quick
Estimate widget, offer card title, Comprehensive Auto Care H3,
WhyChooseUs card titles ×2, Transformation card title, Blog card
title) all moved to `text-neutral-900`.

---

## 5. PageBanner heights — re-check

The `/explore` vs `/services` PageBanner check from Phase 4.7.3
remains stable. Both probes still return:

```text
height: 360, depth: 3, cls: "relative h-[40vh] min-h-[300px] flex items-center overflow-hidden mb-12"
```

No change.

---

## 6. Eyebrow labels — Option A applied

Per spec default, **Option A (remove eyebrows from Home)** was
applied. Seven orange-dash eyebrow labels removed:

| Eyebrow text       | Section                  | Status      |
|--------------------|--------------------------|-------------|
| "The ACR Standard" | Right side: Focused Content | Removed |
| "Our Expertise"    | Categorized Services     | Removed     |
| "Exclusive Deals"  | Offers Section           | Removed     |
| "Visual Proof"     | Before vs After          | Removed     |
| "Customer Stories" | Reviews & Video Testimonials | Removed |
| "Our Network"      | Our Service Centers (dark) | Removed   |
| "Latest Insights"  | Blog Highlights          | Removed     |

The home page now visually matches the rest of the marketing site:
no eyebrow → H2 in canonical SectionHeading style → body copy →
content grid.

---

## 7. Files changed (Phase 4.7.4)

```
Modified (src):
  src/components/Footer.tsx           — pre-section H3 → <SectionHeading>
  src/pages/Home.tsx                  — 8 H2s migrated, 7 H3s recoloured, 7 eyebrows removed

Modified (test infra):
  playwright.config.ts                — added "phase4_7_4" project
  tests/e2e/phase4_7_4-screenshots.spec.ts  — visual capture + inventory probe rig

Added:
  PHASE4_7_4_REPORT.md                — this report
  screenshots/phase4_7_4/before/      — home-fullpage, home-current-offers, offers-current-offers, footer-network
  screenshots/phase4_7_4/after/       — same set + side-by-side-current-offers
```

---

## 8. Build proof

```text
> vite build
✓ built in 16.34s
```

Zero TypeScript errors. Zero Tailwind warnings.

---

## 9. Hard-constraint honesty pass

| Constraint                          | Status   |
|-------------------------------------|----------|
| No backend changes                  | ✅       |
| No routing changes                  | ✅       |
| No copy changes                     | ✅ — every H2's source string preserved verbatim, only the wrapping component + utility classes changed. SectionHeading CSS-uppercases the rendered output, so visible text is identical to the previous rendered output (e.g. "Current Offers." was already uppercase-rendered before via `uppercase` class). |
| No new npm font packages            | ✅       |
| No new font sizes — use existing `.section-heading` clamp | ✅ — no new size utilities introduced; the clamp from `src/index.css` line ~58 (`clamp(1.5rem, 3vw, 2.25rem)`) is doing the work. |

---

## 10. What I deliberately did NOT touch (flagged for next phase)

1. **Modal H2s** (AuthModal, CancelOrderModal, LogoutConfirmModal,
   VehicleReplaceModal, ChunkErrorBoundary, CouponPickerModal) —
   still at font-black 900 with text-neutral-900. Out of marketing
   scope per Phase 4.7 boundary.
2. **SEO card H2s** (`FeatureCard`, `StandardCard`) — card-internal
   titles, kept heavy for in-card hierarchy. Need a dedicated audit
   of whether SEO card grids should adopt SectionHeading or a
   separate `.card-title` utility.
3. **Home `<h3>` card titles** — recoloured (text-neutral-900) but
   still at font-black weight. These are card titles, not section
   headings, so the manual's CR-2 H2 spec doesn't apply directly.
   If operator wants a stricter CR-3 H3 spec (SemiBold 18-22pt),
   that's a Phase 4.7.5 ask.

Nothing claimed ✅ above this line without screenshot or probe
evidence.

— Phase 4.7.4 complete · 2026-05-11 · build green · screenshot evidence in `screenshots/phase4_7_4/`
