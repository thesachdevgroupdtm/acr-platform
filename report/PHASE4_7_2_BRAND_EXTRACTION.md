# Phase 4.7.2 — Brand Manual Extraction Confirmation

> **Spec PART A0 confirmation.** The ACR Brand Manual v1.0 · 2026 has
> been read in full (53 pages) for Phase 4.7.2. This document is the
> mandatory extraction record. All canonical-rule decisions (CR-1
> through CR-9) and violation fixes (V-1 through V-9) trace back to
> the specs captured here. Source file:
> `C:\Users\Admin\Downloads\acr3.0\ACR_Brand_Manual.pdf`
> (1.6 MB · renamed from `ACR_Brand_Manual (4).pdf` per spec for
> path-safety).

---

## 1. Eight Canonical Colours

The manual ships **four primary** colours (p. 17) plus **four accent**
colours (p. 18). All eight are encoded as exact hex.

### Primary palette (p. 17 — chapter 03 · Colour)

| # | Name           | Hex      | RGB             | Use                                  |
|---|----------------|----------|-----------------|--------------------------------------|
| 1 | ACR Blue       | `#1F4FA3` | R31 · G79 · B163  | Logo + brand signal                 |
| 2 | Deep Navy      | `#0E2A5C` | R14 · G42 · B92   | Premium surfaces                    |
| 3 | Workshop Black | `#111111` | R17 · G17 · B17   | Typography                          |
| 4 | Clean White    | `#FFFFFF` | R255 · G255 · B255 | Space + clarity                    |

### Accent palette (p. 18)

| # | Name                | Hex      | Use                                |
|---|---------------------|----------|------------------------------------|
| 5 | Mechanical Orange   | `#F28C28` | Service highlights, offer flags, CTA chips |
| 6 | Collision Red       | `#D62828` | Warnings, error states, "don't" callouts |
| 7 | Service Silver      | `#B8BDC7` | Icons, tables, subtle borders     |
| 8 | Steel Grey          | `#5F6368` | Secondary type, dividers, UI      |

### Tailwind/CSS token mapping (in `src/index.css`)

| Brand colour       | CSS variable             | Status |
|--------------------|--------------------------|--------|
| ACR Blue           | `--color-primary`        | ✅ `#1F4FA3` exact |
| Deep Navy          | `--color-primary-dark`   | ✅ `#0E2A5C` exact |
| Mechanical Orange  | `--color-accent`         | ✅ `#F28C28` exact |
| Collision Red      | `--color-accent-dark`    | ✅ `#D62828` exact |
| Service Silver     | `--color-border`         | ✅ `#B8BDC7` exact |
| Steel Grey         | `--color-muted`          | ✅ `#5F6368` exact |
| Workshop Black     | body `text-[#111111]`    | ✅ exact |
| Clean White        | `bg-white` (Tailwind)    | ✅ exact |

All eight colours present and mapped. No drift.

---

## 2. Two Fonts (p. 21 — chapter 04 · Typography)

### Display: **Montserrat**

> "Brand voice, headlines, hero statements. Regular · SemiBold · Bold"
> *(manual p. 21)*

### Text: **Inter**

> "Body copy, UI, captions, long-form. Regular · Medium · SemiBold"
> *(manual p. 21)*

### Status

- ✅ Both families loaded via Google Fonts `@import` at top of
  `src/index.css` (line 1).
- ✅ Inter weights imported: 400, 500, 600, 700.
- ✅ Montserrat weights imported: 500, 600, 700, 800, 900.
- ✅ `--font-display: Montserrat` and `--font-sans: Inter` declared
  in `@theme` block.

---

## 3. Four Typography Levels (p. 22)

The manual defines **exactly four** hierarchy levels:

| Level   | Spec (manual p. 22)                         | CSS Utility                  |
|---------|---------------------------------------------|------------------------------|
| H1      | Montserrat **Bold** · 36–48 pt              | `.page-title`                |
| H2      | Montserrat **SemiBold** · 22–28 pt          | `.section-heading`           |
| Body    | Inter **Regular** · 14–16 pt                | `body` / `.body-text`        |
| Caption | Inter **Medium** · 10–12 pt                 | `.heading-h6` (caption-like) |

H3–H6 utilities are project extensions (not in manual) — they step
down from H2 and remain conservative per the manual's "two typefaces,
two jobs. Nothing else" principle (p. 20).

---

## 4. 60 · 25 · 10 · 5 Colour Ratio (p. 19)

> "The 60·25·10·5 rule keeps every page unmistakeably ACR."
> *(manual p. 19)*

| %    | Bucket             | Roles                                            |
|------|--------------------|--------------------------------------------------|
| 60%  | White & light      | "White dominates — the workshop feels clean, not cramped." |
| 25%  | ACR Blue           | "Anchors every composition" — signal colour.    |
| 10%  | Black & Navy       | Typography + premium surfaces.                  |
| 5%   | Accent (Orange)    | "A spice — offers, urgency, CTAs. Never decoration." |

### Application rule

- Hero pages and editorial templates lean white-dominant.
- Premium pillars (Insurance, About) use Deep Navy panels but
  spaced so navy stays ~10–25%.
- Mechanical Orange appears **only** as CTA chips, offer flags, or a
  single accent word — never as a fill.

---

## 5. Dual-colour H2 + period — brand extension justification

The manual's chapter titles (p. 5 "Essence.", p. 7 "Personality.",
p. 22 "Hierarchy.", p. 28 "Instagram feed.", etc.) all terminate with
**a period**. Several pages (p. 6 "Vision. / Mission. / Promise.";
p. 7 "Calm hands. / Clear words. / Straight answers.") use a
**two-colour dual stack** — navy + ACR Blue + Mechanical Orange.

Phase 4.7.2's `SectionHeading` component (dual-colour H2 — neutral-900
head + ACR Blue accent + period) extends this established manual
treatment to ACR's web product. The pattern is justified directly
by the manual's own chapter-title rendering — not an invention.

---

## 6. Tone of voice (p. 7, 24, 25)

> "Calm hands. Clear words. Straight answers." (p. 7)
> "Repair you can trust." / "Multi-brand capability under one roof."
> / "Honest estimate. Fixed delivery window." (p. 24 — "Say this")

All copywriting in Phase 4.7.2 (e.g., "FLAWLESS RESTORATION.",
"REPAIR YOU CAN TRUST.", "NEED A CUSTOM SOLUTION?") leans on the
manual's example voice.

---

## 7. Confirmation summary

| Requirement (per spec PART A0)         | Status |
|----------------------------------------|--------|
| Confirm 8 colours with exact hex       | ✅ §1 above |
| Confirm 2 fonts (Montserrat + Inter)   | ✅ §2 above |
| Confirm 4 typography levels            | ✅ §3 above |
| Confirm 60·25·10·5 colour ratio        | ✅ §4 above |

The brand manual is the **single source of truth** for Phase 4.7.2.
All nine violations (V-1 … V-9) are remediated to bring the live
product into conformance with the rules captured here.

— Phase 4.7.2 extraction complete · 2026-05-11
