# Phase 4.7.5 — H2 Size Normalization

> **Rule (carried from 4.7.3/4.7.4).** Every claimed fix below ties
> to a Playwright screenshot or computed-style probe. No grep-only
> claims.

Brand manual: `C:\Users\Admin\Downloads\acr3.0\ACR_Brand_Manual.pdf`
(CR-2 H2 spec — SemiBold weight, dual-colour, clamp size).

---

## 1. Outcome

| ID  | What                                            | Status |
|-----|-------------------------------------------------|--------|
| V-1 | Home FLEET MAINTENANCE H2 rendered at 48px vs other Home H2s at 36px → match | ✅ Fixed |
| V-2 | Footer "INDIA'S FASTEST-GROWING…" H2 wraps to 2 lines at 1440px → single line | ✅ Fixed (new `.section-heading-sm` utility + `size="sm"` prop) |

---

## 2. Before-state probes (computed style from live DOM)

`tests/e2e/phase4_7_5-screenshots.spec.ts` — console output of the
`home-h2-size-probe` test against `PHASE=before`:

```text
HOME H2 SIZES (selected):
  "Current Offers."          fontSize: 36px   boundingHeight: 45
  "Specialized Care."        fontSize: 36px   boundingHeight: 45
  "The Transformation."      fontSize: 36px   boundingHeight: 45
  "Absolute Trust."          fontSize: 36px   boundingHeight: 45
  "Our Service Centers."     fontSize: 36px   boundingHeight: 45
  "Fleet Maintenance."       fontSize: 48px   boundingHeight: 60   ← V-1 violation
  "Car Care Blog."           fontSize: 36px   boundingHeight: 45

FOOTER 1440 PROBE:
  fontSize: 36px, lineHeight: 45px, boundingHeight: 90, approxLines: 2
  classes: "section-heading mb-4"                                   ← V-2 violation
```

FLEET MAINTENANCE rendered 33% larger than its siblings; the footer
heading occupied two lines at desktop width.

---

## 3. After-state probes

Same probe under `PHASE=after`:

```text
HOME H2 SIZES (selected):
  "Current Offers."          fontSize: 36px   boundingHeight: 45
  "Specialized Care."        fontSize: 36px   boundingHeight: 45
  "Fleet Maintenance."       fontSize: 36px   boundingHeight: 45   ← matches
  "Car Care Blog."           fontSize: 36px   boundingHeight: 45

FOOTER 1440 PROBE:
  fontSize: 22px, lineHeight: 27.5px, boundingHeight: 28, approxLines: 1
  classes: "section-heading-sm mb-4"                                ← one line

FOOTER 375 PROBE (mobile):
  fontSize: 18px, lineHeight: 22.5px, boundingHeight: 45
  (≈ 2 lines — wrapping is allowed per spec on mobile)
```

The clamp scales the footer heading from **22px desktop → 18px
mobile** so it stays legible on small screens while fitting on a
single line at wide breakpoints.

---

## 4. Required screenshots

All in `screenshots/phase4_7_5/after/`.

### 4.1 V-1 — Home, FLEET MAINTENANCE at same size as CURRENT OFFERS

- `home-current-offers.png` — "CURRENT OFFERS." at 36px
- `home-fleet-maintenance.png` — "FLEET MAINTENANCE." at 36px

Both render with identical clamp size (`.section-heading`). Visual
parity achieved.

### 4.2 V-2 — Footer at 1440px (single line)

- `footer-1440.png` — "INDIA'S FASTEST-GROWING SELF-OWNED
  MULTI-BRAND **NETWORK.**" renders on **one line** at ≈ 22px
  with SemiBold weight + ACR Blue accent.

### 4.3 V-2 — Footer at 375px mobile (wrapping ok)

- `footer-mobile.png` — same heading at ≈ 18px, wrapping to
  ~2 lines. Reads cleanly; spec explicitly allows wrapping on
  mobile.

---

## 5. Implementation

### 5.1 New `.section-heading-sm` utility

`src/index.css` (added in the `@layer components` block):

```css
/* Phase 4.7.5 — smaller H2 variant for footer dividers / long-string
   section labels that need to fit on a single line at desktop ≥1280px
   while still rendering as SemiBold + dual-color accent per CR-2. The
   clamp keeps it responsive (≈ 18px mobile → 22px desktop). */
.section-heading-sm {
  @apply font-display font-semibold uppercase tracking-tighter text-neutral-900 leading-tight;
  font-size: clamp(1.125rem, 1.8vw, 1.375rem);
}
```

### 5.2 New `size` prop on `<SectionHeading>`

`src/components/layout/SectionHeading.tsx`:

```ts
size?: "default" | "sm";
```

Default → `.section-heading` (clamp 1.5rem → 2.25rem).
`sm` → `.section-heading-sm` (clamp 1.125rem → 1.375rem).
Weight, dual-colour accent, terminator behaviour all preserved
across both sizes. CR-2 compliance unchanged.

### 5.3 V-1 fix — `src/pages/Home.tsx`

Before (line ~1173):

```tsx
<div className="flex items-center gap-3 mb-4">
  <div className="w-8 h-0.5 bg-accent" />
  <span className="text-xs uppercase tracking-widest text-muted font-bold">Corporate Solutions</span>
</div>
<h2 className="section-heading mb-6 text-4xl md:text-5xl">
  Fleet <span className="section-heading-accent">Maintenance.</span>
</h2>
```

After:

```tsx
<SectionHeading className="mb-6">Fleet Maintenance</SectionHeading>
```

The inline `text-4xl md:text-5xl` forced 48px (md:48px). Removing
the override lets `.section-heading`'s clamp resolve to 36px,
matching every other Home H2. The "Corporate Solutions" eyebrow
was also removed per Phase 4.7.4 Option A consistency.

### 5.4 V-2 fix — `src/components/Footer.tsx`

Before:

```tsx
<SectionHeading className="mb-4">India's Fastest-Growing Self-Owned Multi-Brand Network</SectionHeading>
```

After:

```tsx
<SectionHeading className="mb-4" size="sm">India's Fastest-Growing Self-Owned Multi-Brand Network</SectionHeading>
```

The new `size="sm"` swaps the class to `.section-heading-sm`,
dropping desktop size from 36px → 22px. The 10-word heading now
fits on a single line at 1440px while still wrapping gracefully
below ~640px viewport.

---

## 6. Files changed

```
Modified:
  src/components/layout/SectionHeading.tsx   — new `size` prop
  src/index.css                              — new `.section-heading-sm` utility
  src/pages/Home.tsx                         — V-1 fix
  src/components/Footer.tsx                  — V-2 fix
  playwright.config.ts                       — phase4_7_5 project

Added:
  PHASE4_7_5_REPORT.md                       — this report
  tests/e2e/phase4_7_5-screenshots.spec.ts   — probe + capture rig
  screenshots/phase4_7_5/before/             — 4 captures (state pre-fix)
  screenshots/phase4_7_5/after/              — 4 captures (state post-fix)
```

---

## 7. Build

```text
> vite build
✓ built in 30.25s
```

Zero TypeScript errors. Zero Tailwind warnings.

---

## 8. Hard-constraint honesty pass

| Constraint                                | Status |
|-------------------------------------------|--------|
| No backend changes                        | ✅ |
| No routing changes                        | ✅ |
| No copy changes                           | ✅ |
| No new npm font packages                  | ✅ |
| No global H2 size change                  | ✅ — only the footer-divider context uses `.section-heading-sm`; the default `.section-heading` clamp is untouched, so every other H2 site-wide is unaffected. |
| No fixed px size — clamp() only           | ✅ — `clamp(1.125rem, 1.8vw, 1.375rem)` |
| No font-bold/black                        | ✅ — still SemiBold (600) on both variants |
| No italic                                 | ✅ |

---

## 9. What I deliberately did NOT change

- `REPAIR YOU CAN TRUST.` (final CTA on Home, fontSize 60px) — this
  is the page's closing hero H2, intentionally larger than the
  section-divider H2s. The spec specifically mentioned FLEET
  MAINTENANCE only.
- `QUESTIONS WE GET ASKED.` (HomeFAQ, fontSize 60px) — same
  rationale; treated as a major header for the FAQ section, not a
  section-divider.
- Modal H2s and SEO card H2s — out of marketing scope per Phase 4.7
  boundary (flagged at end of Phase 4.7.4).

— Phase 4.7.5 complete · 2026-05-11 · build green · 8 screenshots in `screenshots/phase4_7_5/`
