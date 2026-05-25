# Phase 4.7.1 — Brand Manual Extraction (PART A)

**Source:** `C:\Users\Admin\Downloads\acr3.0\ACR_Brand_Manual (4).pdf` (53 pages, v1.0 · 2026)
**Date:** 2026-05-11
**Scope:** Authoritative typography + color + banner specs extracted from the brand manual. Phase 4.7.1 reconciles all deviations.

---

## 1. Typography (Chapter 04, pp. 20-22)

### Display font (pp. 21)
- **Family**: **Montserrat**
- **Allowed weights**: Regular (400), SemiBold (600), Bold (700)
- **Use**: brand voice, headlines, hero statements

### Text font (pp. 21)
- **Family**: **Inter**
- **Allowed weights**: Regular (400), Medium (500), SemiBold (600)
- **Use**: body copy, UI, captions, long-form

### Hierarchy (pp. 22)

| Level | Spec | Manual example |
|---|---|---|
| **H1** | Montserrat **Bold (700)** · 36-48pt | "Repair you can trust." |
| **H2** | Montserrat **SemiBold (600)** · 22-28pt | "Collision Repair" |
| **Body** | Inter Regular · 14-16pt | "Transparent estimates. Skilled technicians. One workshop." |
| **Caption** | Inter Medium · 10-12pt | "Service ref · 2026-01 · Motinagar workshop" |

### Key observations
- Manual H2 example "Collision Repair" is **Title Case** (NOT uppercase), **NO period**, **NO dual-color** — plain Montserrat SemiBold.
- **Italic** is shown ONLY as the manual's tagline rendering style ("*All Cars. One Repair Stop.*") and in social/banner *creative* contexts (email signature, video lower-third). **Italic is NOT in the hierarchy specification for H1/H2/Body/Caption.**
- The manual's own **chapter titles** ("Brand Manual.", "Essence.", "Hierarchy.", "Colour.") use the **dual-color + period creative treatment** — this is a CHAPTER-TITLE-STYLE creative treatment used sparingly for hero/manifesto contexts, NOT a universal H2 rule.

---

## 2. Colors (Chapter 03, pp. 17-19)

### Primary palette (pp. 17)

| Token | Hex | RGB | Use |
|---|---|---|---|
| **ACR Blue** | `#1F4FA3` | R31·G79·B163 | Logo + brand signal |
| **Deep Navy** | `#0E2A5C` | R14·G42·B92 | Premium surfaces |
| **Workshop Black** | `#111111` | R17·G17·B17 | Typography |
| **Clean White** | `#FFFFFF` | R255·G255·B255 | Space + clarity |

### Accent palette (pp. 18)

| Token | Hex | Use |
|---|---|---|
| **Mechanical Orange** | `#F28C28` | Service highlights, offer flags, CTA chips |
| **Collision Red** | `#D62828` | Warnings, error states, 'don't' callouts |
| **Service Silver** | `#B8BDC7` | Icons, tables, subtle borders |
| **Steel Grey** | `#5F6368` | Secondary type, dividers, UI |

### 60·25·10·5 ratio (pp. 19)
```
60%  White & light    — workshop feels clean, not cramped
25%  ACR Blue         — signal colour, anchors every composition
10%  Black & Navy     — type and premium surfaces
 5%  Accent (Orange)  — offers, urgency, CTAs (NEVER decoration)
```

### Key observations
- **NO "dark navy + bright blue" combo** — that's `Deep Navy + ACR Blue` per manual, but the manual specifies WHITE backgrounds (60%) with ACR Blue (25%) as the dominant duo. Dark surfaces are reserved for premium contexts (10% of compositions).
- **NO cyan, teal, indigo, sky-blue, or off-palette blues exist** in the manual.
- **Italic is permitted only for the tagline** rendering ("*All Cars. One Repair Stop.*") and short hero accents in social creative — NOT for body H1s.

---

## 3. Banner / page headers

The brand manual does NOT explicitly diagram a "PageBanner" web component the way it does for fascia signage (pp. 40). The closest reference is:

### Homepage hero (pp. 45)
- **Navy background** (full-bleed)
- White H1 headline ("All Cars. One Repair Stop.")
- Orange "BOOK NOW" CTA chip
- Workshop hero image on the right
- Logo top-left, primary nav top-right

### Blog post header (pp. 46)
- Navy header strip with logo + breadcrumb-style label ("Blog")
- **Orange category tag** below
- **H1 in Montserrat 28-36pt** (article title)
- Hero image (no overlay specified, but the example shows a navy placeholder)
- Two-column body + sidebar

### Inferred web banner pattern (combining hero + blog header)
- Dark surface (navy OR image-with-overlay)
- White H1 (Montserrat Bold)
- Breadcrumb/category context above title
- Single CTA chip (orange) when actionable

### Operator's spec D-4.7.1-3 + image references
- ALL page banners use the same component (PageBanner.tsx)
- Image background with overlay
- Overlay color/opacity from brand (Deep Navy at ~70-80%)
- Breadcrumb above title
- H1 in consistent font/color/size

This aligns with the manual's web spec direction.

---

## 4. Personality + brand voice constraints (pp. 7-8)

### We are
Trusted · Skilled · Transparent · Reassuring · Structured · Modern

### We aren't
~~Cheap-looking~~ · ~~Noisy~~ · ~~Confusingly technical~~ · **~~Overly flashy~~** · ~~Unstructured~~ · ~~Casual on safety~~

### Implication for typography
- Italic + multi-color + gradient treatments on hero text = "Overly flashy" → AVOID
- Plain Montserrat Bold + ACR Blue accent = "Structured + Modern" → PREFER

---

## 5. Phase 4.7 deviations from the brand manual

| Phase 4.7 setting | Brand manual spec | Action |
|---|---|---|
| `.page-title` uses `font-black` (900) | Manual H1 = Montserrat **Bold (700)** | **UPDATE** → font-bold (700) |
| `.section-heading` uses `font-black` (900) | Manual H2 = Montserrat **SemiBold (600)** | **UPDATE** → font-semibold (600) |
| `.section-heading` uses `uppercase` + `tracking-tighter` + dual-color + period | Manual H2 = Title Case, no period, no dual-color | **PRAGMATIC KEEP** — the dual-color + period IS the manual's chapter-title creative treatment. Operator's prior phases (4.5+) established this as ACR's editorial signature. Per D-4.7.1-4 keep it, but lighten weight to SemiBold to honor manual hierarchy. |
| Phase 4.7 used `text-primary` for accent | Manual ACR Blue = `#1F4FA3` (already wired as `--color-primary`) | NO CHANGE — already correct |
| Home hero "Flawless Restoration" uses italic + primary-dark + cyan/bright blue | Manual: no italic for H1, only ACR Blue (#1F4FA3) + Workshop Black (#111111) | **FIX** — remove italic, replace bright blue with ACR Blue, base in Workshop Black |
| SEO page banner uses solid black background | Manual web spec: navy with image hero OR image-with-overlay | **FIX** — refactor to use PageBanner image+overlay |
| Trending Searches / Most Read rails currently single-color H2 | Operator's interpretation D-4.7.1-4 + Phase 4.7 canonical | Verify in Phase 4.7 work — should already be dual-color via the updated ExploreRail (Phase 4.7 modified this file) |

---

## 6. Authoritative resolution rules (this commit)

Per D-4.7.1-1 ("MANUAL WINS on contradictions"):

1. **Font weights** drop to manual specs: page-title → Bold 700, section-heading → SemiBold 600.
2. **Color palette is restricted to**: `#1F4FA3` (ACR Blue), `#0E2A5C` (Deep Navy), `#111111` (Workshop Black), `#FFFFFF` (Clean White), `#F28C28` (Mechanical Orange — accent only).
3. **No italic** for H1/H2 body headings. Italic permitted only for: tagline rendering, social/email creative.
4. **Dual-color + period** treatment is preserved per operator's D-4.7.1-4 directive (it IS the manual's own chapter-title style). Applied to H2 section-heading utility.
5. **All page banners** use the same PageBanner component pattern (image + dark overlay + white H1 + breadcrumb), per D-4.7.1-3.

---

## 7. Files that will change in Phase 4.7.1

```
MODIFY (foundation):
  src/index.css                                      (font-black → font-bold/semibold per manual)

MODIFY (operator-flagged):
  src/pages/Home.tsx                                 (hero: remove italic, fix off-palette colors)
  src/pages/SeoPageView.tsx                          (banner: refactor to use PageBanner)

VERIFY (Phase 4.7 work):
  src/components/explore/ExploreRail.tsx             (rail H2 already migrated in Phase 4.7)
  src/components/explore/ExploreInternalLinks.tsx    (already migrated)
  src/components/seo/ContinueReading.tsx             (already migrated)
  src/components/seo/RelatedArticlesGrid.tsx         (already migrated)
  src/components/seo/InternalLinkingFooter.tsx       (already migrated)

NEW:
  tests/e2e/brand-consistency.spec.ts                (5-page brand assertion smoke)
  PHASE4_7_1_BRAND_EXTRACTION.md  (this doc)
  PHASE4_7_1_REPORT.md            (after PART J)
```

— end of extraction — proceeding with implementation —
