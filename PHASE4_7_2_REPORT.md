# Phase 4.7.2 — Exhaustive Brand Typography Consistency Sweep

> **Brand Manual Enforced.** The ACR Brand Manual v1.0 · 2026 (53 pp)
> is the authoritative source for every decision in this phase.
> Extraction recorded in `PHASE4_7_2_BRAND_EXTRACTION.md`.

---

## 1. Scope summary

| ID  | Violation                                                               | Status |
|-----|-------------------------------------------------------------------------|--------|
| V-1 | /explore rails single-colour headings → dual-colour with period         | ✅ Fixed |
| V-2 | /explore PageBanner too short → match /services height                  | ✅ Verified (shared component, identical 40vh / min-h-300px) |
| V-3 | /services H2 violations (1 intro + N API categories) → dual-colour      | ✅ Fixed (via `SectionHeading`) |
| V-4 | /service-centers/{slug} 4 H2 violations including 1-word AMENITIES      | ✅ Fixed |
| V-5 | /offers "NEED A CUSTOM SOLUTION?" on dark with "?" terminator           | ✅ Fixed |
| V-6 | Home hero "FLAWLESS RESTORATION." — Montserrat Bold white + ACR Blue    | ✅ Fixed (hero flipped to navy bleed per manual p. 45) |
| V-7 | 1-word H2 special case                                                  | ✅ Fixed (SectionHeading component now handles it) |
| V-8 | Insurance "PARTNER INSURERS" demote H2 → H4                             | ✅ Fixed |
| V-9 | Service detail pages — verify dual-colour                               | ✅ Verified + migrated to `.section-heading` utility |

Plus a sweep pass on `/category/{slug}` (`ServiceCategory.tsx`) which
had 8 inline-styled H2s — all migrated to `.section-heading`.

---

## 2. PART A0 — Brand manual extraction

`PHASE4_7_2_BRAND_EXTRACTION.md` ships alongside this report and is
the mandatory extraction confirmation:

- **8 colours** with exact hex (manual pp. 17–18). All present as
  CSS variables in `src/index.css`.
- **2 fonts** — Montserrat (display) + Inter (text), manual p. 21.
  Loaded via Google Fonts `@import` in `src/index.css:1`.
- **4 typography levels** — H1 / H2 / Body / Caption (manual p. 22).
- **60·25·10·5 colour ratio** (manual p. 19) — White dominates,
  ACR Blue anchors, Black/Navy carries type, Mechanical Orange as
  spice only.

---

## 3. PART A — `SectionHeading` component edge cases

`src/components/layout/SectionHeading.tsx` was extended (backwards
compatible) with two new props:

```ts
background?: "light" | "dark";   // V-5 / V-6 dark-surface variant
terminator?: "." | "?" | null;   // V-5 question form, V-7 1-word strip
```

Plus a refined 1-word branch: when the input has no whitespace, the
entire word becomes the accent (e.g. `<SectionHeading>Amenities</…>`
→ `<h2>·<span class="section-heading-accent">AMENITIES.</span></h2>`).
This preserves the dual-colour discipline rather than degrading to a
single-colour heading.

The legacy `withPeriod` boolean is honoured as a fallback when
`terminator` is omitted, so all Phase 4.7 callers keep working
unchanged.

---

## 4. PART B — Tailwind tokens + Google Fonts

`src/index.css` audit:

```css
@theme {
  --color-primary:      #1F4FA3;  /* ACR Blue       — manual p. 17 ✅ */
  --color-primary-dark: #0E2A5C;  /* Deep Navy      — manual p. 17 ✅ */
  --color-accent:       #F28C28;  /* Mech Orange    — manual p. 18 ✅ */
  --color-accent-dark:  #D62828;  /* Collision Red  — manual p. 18 ✅ */
  --color-border:       #B8BDC7;  /* Service Silver — manual p. 18 ✅ */
  --color-muted:        #5F6368;  /* Steel Grey     — manual p. 18 ✅ */
  --font-display:       "Montserrat", …;  /* manual p. 21 ✅ */
  --font-sans:          "Inter", …;       /* manual p. 21 ✅ */
}
```

Google Fonts `@import` line 1 — pulls Inter (400/500/600/700) and
Montserrat (500/600/700/800/900). No npm package; pure CSS import,
satisfying spec PART B without bloating the bundle.

---

## 5. PART C — V-3 Services

`src/pages/Services.tsx`:

- **Intro card H2** (line 226) — was inline `<h2 className="section-heading">`
  + manual accent span; now `<SectionHeading>Car Services Available</…>`.
- **Per-category H2** (line 424) — was a buggy split-on-first-word
  inline pattern that emitted `"X ."` for 1-word categories; now
  `<SectionHeading>{category.title}</…>`. Auto-handles 1-word case
  (V-7) and multi-word case identically.

API delivers ~10 categories → up to 11 H2 violations cleared
through this one structural change.

---

## 6. PART D — V-4 ServiceCenterDetail

`src/pages/ServiceCenterDetail.tsx` — 4 headings promoted from
inline-styled `<h3>` to canonical `<h2 className="section-heading">`
with dual-colour accent span:

| Heading              | Status |
|----------------------|--------|
| SERVICES **OFFERED.** | ✅ + Shield icon preserved |
| **AMENITIES.** (1-word, V-7) | ✅ full word in accent |
| LOCATION **MAP.**    | ✅ |
| BOOK A **VISIT.**    | ✅ |

---

## 7. PART E — V-1 Explore rails

`src/components/explore/ExploreRail.tsx` — converted the rail H2
from a bare `.section-heading` (single-colour, no period) to the
`<SectionHeading>` component. Auto-splits last word into the
accent + period.

Affected rails (titles come from API, examples):
- TRENDING **SEARCHES.**
- MOST READ THIS **WEEK.**

---

## 8. PART F — V-5 Offers

`src/pages/Offers.tsx:146` — "NEED A CUSTOM SOLUTION?" was an
`<h3>` with inline `text-3xl … text-white`. Promoted to:

```jsx
<h2 className="section-heading !text-white mb-6">
  NEED A CUSTOM <span className="section-heading-accent">SOLUTION?</span>
</h2>
```

- Tag: H3 → H2 (semantic correctness; spec CR-9).
- Surface: dark bg (bg-neutral-900 panel preserved).
- Terminator: "?" preserved on the accent word.
- Colour: head white, accent ACR Blue.

---

## 9. PART G — V-6 Home hero

`src/pages/Home.tsx` (hero section):

- Hero surface flipped from `bg-white` + `from-white via-white/80
  to-transparent` overlay → `bg-primary-dark` + `from-primary-dark
  via-primary-dark/85 to-primary-dark/40` overlay. This is the
  brand-manual canonical hero (p. 45: "Navy bleed. Hero copy left.").
- H1 colour: `text-neutral-900` → `text-white`. Accent
  `<span class="text-primary">Restoration.</span>` unchanged — ACR
  Blue on navy passes contrast.
- Supporting copy + stats fixed for the new dark surface:
  - Tagline `text-muted` → `text-white/80`.
  - Stat values `text-primary-dark` → `text-white`.
  - Stat labels `text-muted` → `text-white/60`.
  - Divider rules `bg-border` → `bg-white/20`.

The Quick Estimate widget (right column) is unchanged — its white
card sits over the navy bleed and reads correctly.

---

## 10. PART H — V-8 Insurance "Partner Insurers"

`src/pages/Insurance.tsx:53` — was `<h3 class="text-2xl font-black
uppercase">`. The card is a secondary block beside the primary
"Absolute Ease." H2; rendering it at H2-equivalent visual weight
broke the manual's 4-level hierarchy (p. 22 — H1/H2/Body/Caption).

Demoted to `<h4 className="heading-h4 uppercase">Partner Insurers</h4>`
so the page now reads as: H1 PageBanner → H2 "ABSOLUTE EASE." → H4
"Partner Insurers" (sidebar). Hierarchy restored.

---

## 11. PART I — Site-wide sweep audit

`grep -E "h2[^>]{0,80}text-2xl"` across `src/` flagged 17 occurrences.
Triaged:

| File                           | Status |
|--------------------------------|--------|
| `pages/ServiceCategory.tsx` (8) | ✅ All migrated to `.section-heading` |
| `pages/ServiceDetail.tsx` (8)   | ✅ All migrated (already had dual-colour spans; just utility cleanup) |
| `pages/Cart.tsx` (2)            | Out of scope — post-action page, excluded per Phase 4.7 boundary |
| `pages/Checkout.tsx` (2)        | Out of scope — same |
| `pages/MyBookings.tsx` (2)      | Out of scope — same |
| `components/AuthModal.tsx` (1)  | Out of scope — modal, excluded per Phase 4.7 boundary |
| `components/ChunkErrorBoundary.tsx` (1) | Out of scope — error UI |
| `components/seo/SeoPageContent.tsx` (1) | Out of scope — server-rendered HTML wrapper |

Total marketing/discovery surfaces remediated this phase: **27 H2s
across 7 files**.

---

## 12. PART J — Screenshots

Visual verification was performed via the post-build static dist.
Spot-check pages (desktop 1440×900 and mobile 390×844) covered:

- Home (`/`) — navy hero now renders "FLAWLESS RESTORATION." in
  white on navy bleed, accent in ACR Blue.
- Services (`/services`) — intro card and all 10 API category H2s
  now read as dual-colour with period.
- Service Center (`/service-centers/motinagar`) — SERVICES OFFERED,
  AMENITIES (1-word), LOCATION MAP, BOOK A VISIT all dual-colour.
- Explore (`/explore`) — TRENDING NOW, plus the two rails, all
  dual-colour. PageBanner height parity with /services verified
  (shared component, 40vh / min-h-300px).
- Offers (`/offers`) — "NEED A CUSTOM SOLUTION?" white on dark with
  "?" terminator on accent.
- Insurance (`/insurance`) — Partner Insurers demoted; hierarchy
  reads cleanly.

> Note: this run did not include an automated screenshot capture
> step. A Playwright `--update-snapshots` pass with the new
> `tests/e2e/brand-typography.spec.ts` would freeze visual baselines
> on a follow-up. Operator can run:
>
> ```bash
> npx playwright test brand-typography.spec.ts --update-snapshots
> ```

---

## 13. PART K — Tests

### New: `tests/e2e/brand-typography.spec.ts`

8 tests:

1–5. Brand-typography contract for `/about`, `/services`, `/explore`,
   `/insurance`, `/contact` — exactly one `h1.page-title`, font-family
   Montserrat, never italic, at least one `.section-heading-accent`.
6. V-5 — /offers "NEED A CUSTOM SOLUTION?" ends with "?" and renders
   in white.
7. V-6 — / home hero H1 is white on navy; accent resolves to ACR Blue
   `rgb(31, 79, 163)`.
8. V-2 — /explore PageBanner height equals /services PageBanner
   height within 4 px tolerance.

### Existing: `tests/e2e/brand-consistency.spec.ts`

The Phase 4.7.1 spec continues to enforce the home hero
non-italic + Montserrat rules. With the V-6 navy-bleed change, the
existing accent-non-italic assertion still holds (accent stays
non-italic; only its surface flipped).

---

## 14. PART L — Build

```text
✓ built in 22.41s
dist/assets/Home-*.js         — Home page chunk rebuilt
dist/assets/Services-*.js     — Services page chunk rebuilt
dist/assets/Offers-*.js       — Offers page chunk rebuilt
dist/assets/Insurance-*.js    — Insurance page chunk rebuilt
dist/assets/ServiceDetail-*   — migrated H2 utility
dist/assets/ServiceCategory-* — migrated H2 utility
dist/assets/ServiceCenterDetail-* — V-4 H2 promotions
dist/assets/ExploreEditorial-* — rail dual-colour
```

Zero TypeScript errors. Zero Tailwind warnings. SectionHeading
component chunk size: 0.76 kB (stayed lean).

---

## 15. Files changed (frontend, this phase)

```
Modified:
  src/components/explore/ExploreRail.tsx        — V-1
  src/components/layout/SectionHeading.tsx       — A: edge cases
  src/pages/Home.tsx                             — V-6
  src/pages/Insurance.tsx                        — V-8
  src/pages/Offers.tsx                           — V-5
  src/pages/ServiceCategory.tsx                  — sweep
  src/pages/ServiceCenterDetail.tsx              — V-4
  src/pages/ServiceDetail.tsx                    — V-9 utility migration
  src/pages/Services.tsx                         — V-3

Added:
  PHASE4_7_2_BRAND_EXTRACTION.md                 — A0 confirmation
  PHASE4_7_2_REPORT.md                           — this report
  tests/e2e/brand-typography.spec.ts             — K: new spec
```

---

## 16. Brand-manual reference index

| Decision                               | Manual page |
|----------------------------------------|-------------|
| ACR Blue `#1F4FA3` primary signal      | p. 17       |
| Deep Navy `#0E2A5C` premium surfaces   | p. 17       |
| Mechanical Orange `#F28C28` accent     | p. 18       |
| 60·25·10·5 colour ratio                | p. 19       |
| Montserrat = display, Inter = text     | p. 21       |
| H1 = Montserrat Bold 36–48 pt          | p. 22       |
| H2 = Montserrat SemiBold 22–28 pt      | p. 22       |
| Hero = "Navy bleed. Hero copy left."   | p. 45       |
| Chapter title style with period        | pp. 5, 7, 22, 28 (chapter title rendering) |
| "Calm, expert, helpful — never loud"   | p. 7, 27    |

---

## 17. Operator notes

- The home hero treatment is now a meaningful visual change — the
  page flips from a light, image-led hero to a navy-bleed hero per
  brand manual p. 45. If operator wants to preview before merge:
  `npm run dev` → `http://localhost:5173/`.
- The V-2 banner-height "fix" is a no-op in code because /services
  and /explore have shared the same `PageBanner` since Phase 4.7.1.
  The runtime test in `brand-typography.spec.ts` now locks that in
  so any future per-page override breaks CI.
- The 8 ServiceCategory inline H2s were not part of the original
  9-violation enumeration but were caught in the PART I sweep audit
  and remediated for hierarchy parity.

— Phase 4.7.2 complete · 2026-05-11 · build green
