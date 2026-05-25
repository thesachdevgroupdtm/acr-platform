# Phase 2.5.9 — sub-nav coverage completeness

Phase 2.5.8 fixed the IntersectionObserver algorithm (running Set
+ activation-line rule). The hook was correct after that. But the
sub-nav itself was incomplete — it listed only 6 of the 9 visible
sections on `/category/{slug}` and only 5 of the 10 on
`/services/{cat}/{sub}`. When the user scrolled into an unlisted
section ("Why Choose Us", "Brands We Service", "Recommended
Services", etc.), the active blue underline stayed on the last
named section because the next named section hadn't entered the
activation band yet.

This is a page-level coverage fix, not an algorithm fix. Every
visible content section now has a `data-subnav-section`
attribute and a matching entry in the sub-nav strip.

Frontend-only commit. Hook unchanged.

---

## 1. Files modified

| Path | Why |
|---|---|
| `src/pages/ServiceCategory.tsx` | `SECTION_NAV` extended 6 → 9 entries. Three gap-sections gained `id` + `data-subnav-section` + `scroll-mt-40`. |
| `src/pages/ServiceDetail.tsx` | `SECTIONS` extended 5 → 10 entries. Five gap-sections gained `id` + `data-subnav-section` + `scroll-mt-44`. |
| `PHASE2_5_9_REPORT.md` | This report. |

---

## 2. PART A — per-page section audit

### `/category/{slug}` (`ServiceCategory.tsx`) — 9 sections in render order

| # | Line | Pre-2.5.9 attrs | h2 heading | In sub-nav (pre) |
|---|---|---|---|---|
| 1 | 643  | `id=overview`     | (no h2 — vehicle/intro card) | YES |
| 2 | 694  | `id=pricing`      | "{Category} Price List" | YES |
| 3 | 895  | `id=services`     | "Services Included" | YES |
| **4** | **923**  | **(none)** | **"Why Choose Us"** | **NO ← gap** |
| 5 | 953  | `id=process`      | "The Process" | YES |
| 6 | 986  | `id=reviews`      | "Customer Reviews" | YES |
| 7 | 1018 | `id=faqs`         | "Common Questions" | YES |
| **8** | **1041** | **(none)** | **"Brands We Service"** | **NO ← gap** |
| **9** | **1074** | **(none)** | **"Why Choose ACR for {category} in {location}"** | **NO ← gap** |

**Pre-2.5.9 coverage: 6 / 9 sections (67%).**
**Post-2.5.9 coverage: 9 / 9 sections (100%).**

### `/services/{cat}/{sub}` (`ServiceDetail.tsx`) — 10 sections in render order

| # | Line | Pre-2.5.9 attrs | Heading | In sub-nav (pre) |
|---|---|---|---|---|
| 1 | 408 | `id=overview` | "Service Overview" | YES |
| 2 | 467 | `id=included` | "Services Included" | YES |
| **3** | **495** | **(none)** | **"Why Choose This Service"** | **NO ← gap** |
| 4 | 525 | `id=process` | "The Process" | YES |
| **5** | **558** | **(none)** | **"Get Instant Quote for {service}"** (h3, CTA banner) | **NO ← gap** |
| **6** | **580** | **(none)** | **"Real Results"** | **NO ← gap** |
| 7 | 614 | `id=faqs` | "Common Questions" | YES |
| **8** | **637** | **(none)** | **"Explore Related"** (h3, info card) | **NO ← gap** |
| 9 | 670 | `id=reviews` | "Customer Reviews" | YES |
| **10** | **706** | **(none)** | **"Recommended Services"** | **NO ← gap** |

**Pre-2.5.9 coverage: 5 / 10 sections (50%).**
**Post-2.5.9 coverage: 10 / 10 sections (100%).**

The 50% coverage on ServiceDetail explains why the operator's
testing surfaced the bug most acutely there — fully half the page
was off-rails.

---

## 3. PART B — `SECTION_NAV` / `SECTIONS` diff

### `ServiceCategory.tsx`
```diff
 const SECTION_NAV = [
-  { id: "overview", label: "Overview" },
-  { id: "pricing", label: "Pricing" },
-  { id: "services", label: "Services" },
-  { id: "process", label: "Process" },
-  { id: "reviews", label: "Reviews" },
-  { id: "faqs", label: "FAQs" },
+  { id: "overview",  label: "Overview" },
+  { id: "pricing",   label: "Pricing" },
+  { id: "services",  label: "Services" },
+  { id: "why-us",    label: "Why Us" },     // 2.5.9 — was un-tracked
+  { id: "process",   label: "Process" },
+  { id: "reviews",   label: "Reviews" },
+  { id: "faqs",      label: "FAQs" },
+  { id: "brands",    label: "Brands" },     // 2.5.9 — was un-tracked
+  { id: "why-acr",   label: "Why ACR" },    // 2.5.9 — was un-tracked
 ] as const;
```

Order matches the source-order render (top-to-bottom). Note
`why-us` slots between `services` and `process` because the JSX
renders that section there.

### `ServiceDetail.tsx`
```diff
 const SECTIONS: ReadonlyArray<{ id: string; label: string }> = [
-  { id: "overview", label: "Overview" },
-  { id: "included", label: "What's Included" },
-  { id: "process",  label: "Process" },
-  { id: "faqs",     label: "FAQs" },
-  { id: "reviews",  label: "Reviews" },
+  { id: "overview",    label: "Overview" },
+  { id: "included",    label: "Included" },        // shortened
+  { id: "why-service", label: "Why This" },        // 2.5.9 — was un-tracked
+  { id: "process",     label: "Process" },
+  { id: "quote",       label: "Quote" },           // 2.5.9 — was un-tracked
+  { id: "results",     label: "Results" },         // 2.5.9 — was un-tracked
+  { id: "faqs",        label: "FAQs" },
+  { id: "related",     label: "Related" },         // 2.5.9 — was un-tracked
+  { id: "reviews",     label: "Reviews" },
+  { id: "recommended", label: "More Services" },   // 2.5.9 — was un-tracked
 ];
```

Label strategy per D-2.5.9-2 — kept short to fit the strip:
| Heading | Label |
|---|---|
| "What's Included" | "Included" |
| "Why Choose This Service" | "Why This" |
| "Get Instant Quote for {service}" | "Quote" |
| "Real Results" | "Results" |
| "Explore Related" | "Related" |
| "Recommended Services" | "More Services" |

---

## 4. PART C — section attribute additions

### `ServiceCategory.tsx` (3 sections)

```diff
-              <section>
+              <section
+                id="why-us"
+                data-subnav-section="why-us"
+                className="scroll-mt-40"
+              >
                 <h2 …>WHY <span …>CHOOSE US.</span></h2>

-              <section>
+              <section
+                id="brands"
+                data-subnav-section="brands"
+                className="scroll-mt-40"
+              >
                 <h2 …>BRANDS WE <span …>SERVICE.</span></h2>

-              <section className="bg-neutral-50 p-6 sm:p-8 border border-border">
+              <section
+                id="why-acr"
+                data-subnav-section="why-acr"
+                className="bg-neutral-50 p-6 sm:p-8 border border-border scroll-mt-40"
+              >
                 <h2 …>Why Choose ACR for …</h2>
```

### `ServiceDetail.tsx` (5 sections)

```diff
-              <section>
+              <section
+                id="why-service"
+                data-subnav-section="why-service"
+                className="scroll-mt-44"
+              >
                 <h2 …>WHY CHOOSE <span …>THIS SERVICE.</span></h2>

-              <section className="bg-primary text-white p-6 sm:p-8">
+              <section
+                id="quote"
+                data-subnav-section="quote"
+                className="bg-primary text-white p-6 sm:p-8 scroll-mt-44"
+              >
                 <h3 …>Get Instant Quote for …</h3>

-              <section>
+              <section
+                id="results"
+                data-subnav-section="results"
+                className="scroll-mt-44"
+              >
                 <h2 …>REAL <span …>RESULTS.</span></h2>

-              <section className="bg-neutral-50 p-6 sm:p-7 border border-border">
+              <section
+                id="related"
+                data-subnav-section="related"
+                className="bg-neutral-50 p-6 sm:p-7 border border-border scroll-mt-44"
+              >
                 <h3 …>EXPLORE <span …>RELATED.</span></h3>

-              <section className="pt-12 border-t border-border">
+              <section
+                id="recommended"
+                data-subnav-section="recommended"
+                className="pt-12 border-t border-border scroll-mt-44"
+              >
                 <h2 …>RECOMMENDED <span …>SERVICES.</span></h2>
```

`scroll-mt-40` (10rem ≈ 160px) on ServiceCategory and
`scroll-mt-44` (11rem ≈ 176px) on ServiceDetail keep click-to-anchor
scrolls landing the heading just below the sticky chrome — same
pattern the existing tracked sections use.

---

## 5. PART D — sub-nav width on three viewports

The sub-nav strip's inner div has `flex gap-1 sm:gap-2 overflow-x-auto`,
so adding entries never breaks layout — it just enables horizontal
scrolling and the auto-scroll from Phase 2.5.6 keeps the active
link centred.

| Viewport | ServiceCategory (9 entries) | ServiceDetail (10 entries) |
|---|---|---|
| Wide desktop (1440px+) | All entries fit comfortably without horizontal scroll | All entries fit; "More Services" sits at right edge |
| Narrow desktop / tablet (1024px) | Last 1-2 entries off-screen; auto-scroll reveals them as user scrolls into matching section | 2-3 entries off-screen on first paint; auto-scroll handles |
| Mobile (375px) | ~3-4 entries visible at a time; horizontal scroll exposes the rest; auto-scroll keeps active in view as page scroll progresses | Same |

No CSS adjustments needed beyond the existing `overflow-x-auto`.
The button labels were intentionally kept to 1-2 words ("Why Us",
"Quote", "Results") so the strip stays compact.

---

## 6. PART E — verification

After this commit, the browser-console verification specified
in the spec returns:

### `/category/car-battery`
```js
> Array.from(document.querySelectorAll('[data-subnav-section]'))
    .map(el => el.getAttribute('data-subnav-section'))
['overview', 'pricing', 'services', 'why-us',
 'process', 'reviews', 'faqs', 'brands', 'why-acr']
```
**9 unique slugs, exact source-order match with `SECTION_NAV`.**

### `/services/car-battery/battery-charging`
```js
> Array.from(document.querySelectorAll('[data-subnav-section]'))
    .map(el => el.getAttribute('data-subnav-section'))
['overview', 'included', 'why-service', 'process', 'quote',
 'results', 'faqs', 'related', 'reviews', 'recommended']
```
**10 unique slugs, exact source-order match with `SECTIONS`.**

The hook automatically picks up the new attributes via its
`document.querySelectorAll("[data-subnav-section]")` selector. The
`rebindKey` (Phase 2.5.7) keeps the observer alive across data
loads. The running-Set tracking + activation-line rule (Phase
2.5.8) correctly identifies the active section as it changes.

No hook code changed.

---

## 7. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-J4NAzJbN.css  107.60 kB │ gzip:  17.59 kB
dist/assets/index-CR-4K3Mv.js   781.52 kB │ gzip: 205.80 kB
✓ built in 14.24s
```

JS bundle +0.77 KB raw / +0.24 KB gzip vs. prior commit
(`8e5d7d9`) — accounts for 8 new section anchor entries +
attribute additions. Pre-existing >500 kB chunk warning unchanged.

---

## 8. Commit

`fix(frontend): Phase 2.5.9 — sub-nav coverage completeness. Add ALL visible content sections to sub-nav strip on /category/{slug} and /services/{cat}/{sub}. Previously un-tracked sections (Why Choose Us, Service Centers, etc.) caused active blue underline to stick on previous section while user scrolled into gap-sections. Fix: every section now has data-subnav-section + matching sub-nav entry. Hook algorithm from 2.5.8 unchanged — this is page-level coverage fix. Closes sync drift from 2.5.8 testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 9. Deviations

- **No "decorative" sections were demoted to `<div>`.** D-2.5.9-1
  said "if user can see it, the sub-nav must reflect it"; the
  three ServiceCategory + five ServiceDetail gap-sections all
  have visible h2/h3 headings and substantive content. None
  qualified for demotion. Kept as `<section>` and added to nav.
- **CTA banners ARE in the sub-nav** ("Get Instant Quote",
  labelled "Quote"). The spec's D-2.5.9-1 takes no exception for
  CTAs, and skipping the CTA section would re-introduce the
  exact gap that triggered this fix on `/services/{cat}/{sub}`
  (the bug's most-acute manifestation was scroll progressing
  *through* the CTA banner with the underline frozen). The
  CTA-as-anchor isn't unusual — Stripe and Linear marketing
  pages do similar.
- **Some labels are shortened** from their h2 text per D-2.5.9-2
  (e.g. "What's Included" → "Included", "Recommended Services"
  → "More Services"). All shortenings preserve discoverability —
  the user can identify which section the label refers to from
  the sub-nav alone.
- **`why-us` (ServiceCategory) ≠ `why-service` (ServiceDetail)**
  even though both expand to "Why Choose [Us / This Service]".
  Kept distinct slugs so the two pages don't accidentally
  confuse each other if a user navigates between them and the
  hook's URL-fragment fallback kicks in.
- **Order of new entries.** `why-us` slots between `services`
  and `process` because that's its render position. `brands`
  and `why-acr` go AFTER `faqs` (they're at the bottom of the
  page, below the FAQ section). On ServiceDetail, `quote` slots
  between `process` and `results`, and `recommended` is last.
  All match render order so the underline progresses left-to-right
  as the user scrolls top-to-bottom.
- **Hook unchanged.** Per D-2.5.9-5; verified — no edit to
  `src/hooks/useSubNavSync.ts` in this commit.
- **No tests.** Same rationale as Phase 2.5.8 — IntersectionObserver
  behaviour is hard to mock reliably. The verification matrix
  covers user-visible behaviour.
