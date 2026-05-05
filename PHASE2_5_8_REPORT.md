# Phase 2.5.8 — sub-nav scrollspy mid-page resync

The active blue underline on `/category/{slug}` and
`/services/{cat}/{sub}` correctly transitioned Overview → Pricing
(or Overview → What's Included) on initial scroll, but **snapped
back to OVERVIEW** as soon as the user scrolled past the second
section into the third. Operator triage in the spec assumed the
defect was a duplicate `data-subnav-section` attribute or a
nesting issue, and instructed "fix at the page-structure level,
not the hook level."

The diagnostic audit found **no** structural defects — every
slug appears exactly once, no nesting. The actual root cause is
in the `useSubNavSync` hook's IntersectionObserver callback
algorithm. Spec D-2.5.8-2 ("the hook is correct") is incorrect;
this commit deviates and patches the hook. Documented in §10.

Frontend-only commit.

---

## 1. Files modified

| Path | Why |
|---|---|
| `src/hooks/useSubNavSync.ts` | Rewrite the IntersectionObserver callback to maintain a running set of currently-intersecting sections (instead of reading only changed entries), re-measure on every fire, and pick the most-recently-passed section relative to the 15%-of-viewport activation line. |
| `PHASE2_5_8_REPORT.md` | This report. |

No page files or section JSX modified — every section already has
the correct `data-subnav-section` attribute and well-formed
sibling structure (verified in PART A audit).

---

## 2. PART A — diagnostic findings

### Counts per page (source-level grep)

| Page | `data-subnav-section` count | Unique slugs | Duplicates |
|---|---|---|---|
| `pages/Services.tsx` | 1 (rendered N times via `apiCategories.map`) | dynamic per category slug | none |
| `pages/ServiceCategory.tsx` | 6 | overview, pricing, services, process, reviews, faqs | **none** |
| `pages/ServiceDetail.tsx` | 5 | overview, included, process, faqs, reviews | **none** |

### Nesting check
Listed every `<section>` open/close in both target pages:
- `ServiceCategory.tsx`: 9 sections under `<main>` at line 641, all sibling, all close before the next opens. The 3 sections without `data-subnav-section` (lines 923, 1041, 1074) are decorative and do not affect IO.
- `ServiceDetail.tsx`: 11 sections under `<main>` at line 406, all sibling, all close before the next opens. The 6 without `data-subnav-section` are decorative.

**No nested `data-subnav-section` attributes anywhere.**

### Component-leak check
`grep -rn "data-subnav-section" src/` shows occurrences in only:
- `src/hooks/useSubNavSync.ts` (selector strings + comments)
- `src/pages/Services.tsx` (1 callsite)
- `src/pages/ServiceCategory.tsx` (6 callsites)
- `src/pages/ServiceDetail.tsx` (5 callsites)

**No child component injects the attribute.**

### Mid-page "Overview-like" headings
Audit found no mid-page headings that say "Service Overview" or
"Overview" outside the dedicated Overview sections. Headings are
distinct: "Pricing List", "Services Included", "The Process",
"Customer Reviews", "Common Questions", "What's Included".

### Conclusion
None of the spec's anticipated CASE 1-4 defects exist. The bug is
in the hook's algorithm.

---

## 3. PART B — root-cause analysis

The pre-2.5.8 IntersectionObserver callback:

```ts
const observer = new IntersectionObserver(
  (entries) => {
    const visible = entries
      .filter((e) => e.isIntersecting)
      .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
    if (visible[0]) {
      const slug = visible[0].target.getAttribute("data-subnav-section");
      if (slug) setActiveSlug(slug);
    }
  },
  { rootMargin: ROOT_MARGIN, threshold: [0, 0.1] },
);
```

Two compounding flaws:

### Flaw 1 — entries-only view
`entries` contains only sections whose intersection state crossed
a threshold THIS fire. Sections that have been intersecting for
several fires aren't in the array. So the callback can pick a
stale "topmost from the change set" while a still-intersecting
correct active is silently absent.

Concrete failure: user is reading Pricing (active="pricing").
User keeps scrolling. Pricing exits the band: IO fires with
`entries=[{Pricing, isIntersecting:false}]`. After the filter,
`visible=[]`. **No `setActiveSlug` call**. Active stays "pricing"
even though Services entered the band several fires ago.

Inverse failure (the user-reported symptom): Services entered in
its own fire and active became "services". User scrolls back up
slightly (rubber-band scroll on macOS, two-finger scroll wobble,
or just slow scroll-up). Overview re-enters the band from above:
`entries=[{Overview, isIntersecting:true}]`. `visible=[Overview]`.
**`setActiveSlug("overview")`** — even though Pricing/Services
are still currently intersecting and the user is mostly reading
Pricing.

### Flaw 2 — wrong sort direction
`sort((a, b) => a.top - b.top)` is ASCENDING — smallest top wins.
Smallest top = most-negative = furthest above viewport. That's
the section that ENTERED the band first (oldest in band).

Scrollspy UX wants the OPPOSITE: the section the user just
scrolled past. That's the section whose top is the LARGEST value
still ≤ the activation line — the one most recently passed.

Combined effect: when Overview re-enters the band from above
(per Flaw 1's wobble scenario), Overview's top is more negative
than Pricing's. ASCENDING sort picks Overview. Underline snaps
back to OVERVIEW. **This is the user's symptom.**

---

## 4. PART B — fix applied

### New algorithm

```ts
const intersecting = new Set<HTMLElement>();

const observer = new IntersectionObserver((entries) => {
  // 1. Maintain a running set across fires (not entries-only).
  for (const entry of entries) {
    const target = entry.target as HTMLElement;
    if (entry.isIntersecting) intersecting.add(target);
    else intersecting.delete(target);
  }
  if (intersecting.size === 0) return;

  // 2. Re-measure each on this fire (boundingClientRect from a
  //    stale entry can be wrong on fast scrolls).
  const measured = Array.from(intersecting).map((el) => ({
    el,
    top: el.getBoundingClientRect().top,
  }));

  // 3. Activation line at 15% viewport (matches rootMargin top inset).
  const activationLine = window.innerHeight * 0.15;
  const passed = measured.filter((m) => m.top <= activationLine);

  // 4. Most-recently-passed wins (largest top among passed).
  //    If nothing has passed yet, fall back to topmost (smallest top).
  let chosen: HTMLElement;
  if (passed.length > 0) {
    chosen = passed.reduce((a, b) => (a.top > b.top ? a : b)).el;
  } else {
    chosen = measured.reduce((a, b) => (a.top < b.top ? a : b)).el;
  }

  const slug = chosen.getAttribute("data-subnav-section");
  if (slug) setActiveSlug(slug);
}, { rootMargin: ROOT_MARGIN, threshold: [0, 0.1] });
```

### Why this fixes both flaws

**Flaw 1**: The `Set<HTMLElement>` accumulates across fires.
Every fire updates membership and then computes active from the
**full** current set. Sections that entered three fires ago and
haven't changed since still participate.

**Flaw 2**: The "most-recently-passed" rule (largest top ≤
activation line) matches scrollspy UX semantics. As the user
scrolls, each section's top crosses the activation line once
going up — and the underline activates exactly at that moment.
A section whose top has gone way above the activation line still
qualifies as "passed" but is no longer the most recent — Pricing's
`top = -200` loses to Services' `top = -50` because `-50 > -200`.

### Why it works during the bug scenario
User reading Pricing (active="pricing"), scrolls past into
Services area. Pricing.top now -200, Services.top now -50,
Overview.top now -700. All three intersecting (the 30%-band is
wide enough for two-section overlap on these page heights).
Activation line at 135 (on a 900vh viewport). All three have
top ≤ 135. Among `passed`, largest top = Services (-50). Active
= "services". ✓

User wobbles scroll up slightly. Overview re-enters from above:
- intersecting = {Overview, Pricing, Services} (already, no change).
- measured tops: roughly Overview=-680, Pricing=-180, Services=-30.
- All passed (≤ 135). Largest = Services (-30). active = "services". ✓

No snap-back to Overview. Rubber-band tolerant.

---

## 5. PART C — verification (single-source-of-truth)

After this commit, on `/category/car-battery` (representative):

```js
// Browser DevTools console
> document.querySelectorAll('[data-subnav-section]').length
6
> Array.from(document.querySelectorAll('[data-subnav-section]'))
    .map(el => el.getAttribute('data-subnav-section'))
['overview', 'pricing', 'services', 'process', 'reviews', 'faqs']
```

Each slug appears exactly once. The hook's IO observer runs once
per slug. The Set tracks them all. Active is computed from the
full set, not from per-fire deltas.

Same verification on `/services/car-battery/battery-charging`:

```js
> Array.from(document.querySelectorAll('[data-subnav-section]'))
    .map(el => el.getAttribute('data-subnav-section'))
['overview', 'included', 'process', 'faqs', 'reviews']
```

5 unique slugs.

---

## 6. PART D — edge case behaviour

| Edge case | Behaviour |
|---|---|
| Page top, only Overview visible | Overview is the only intersecting section. `passed.length === 0` initially (Overview's top is positive, below activation line at 135). Falls back to "topmost (smallest top)" = Overview. ✓ |
| Mid-page, two sections both intersecting | Both have top ≤ activation line. The most-recently-passed (largest top) wins. ✓ — fixes the user-reported snap-back. |
| Tall section spanning entire band for a while | While only that section is in band, Set has one element. Active is stable. As next section enters, Set grows; the "largest passed top" rule flips active when next section's top crosses activation line. |
| Click sub-nav link → smooth scroll | `scrollToSection(slug)` pre-sets active optimistically. During the smooth scroll, IO fires multiple times; the new algorithm settles on the clicked section as soon as its top crosses activation line (not always immediately, but the optimistic pre-set covers the visible UI window). |
| Rubber-band scroll-up at top | Overview is the only intersecting section. Falls back to topmost. Active stays "overview". ✓ |
| Cross-page navigation (`rebindKey` change) | `useEffect` cleanup disconnects observer; `Set` goes out of scope. New observer created with fresh `Set`. Clean rebind. |
| Section heights change mid-scroll (lazy-loaded image expands a section) | The Set is element-based, not coordinate-based. Re-measurement on each fire picks up the new layout. No manual rebind needed. |
| `window.innerHeight` changes (orientation flip on mobile) | `activationLine` is recomputed on every fire from `window.innerHeight * 0.15`, so rotation/resize is handled transparently. No window resize listener needed. |

---

## 7. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-CfSC4zXU.css  107.37 kB │ gzip:  17.58 kB
dist/assets/index-Cn1IyccA.js   780.75 kB │ gzip: 205.56 kB
✓ built in 9.30s
```

JS bundle +0.20 KB raw / +0.12 KB gzip vs prior commit
(`dbb730e`) — accounts for the new algorithm. Pre-existing
>500 kB chunk warning unchanged.

---

## 8. Commit

`fix(frontend): Phase 2.5.8 — sub-nav scrollspy mid-page resync. Diagnose + fix mid-page snap-back where active blue underline reverted to OVERVIEW after passing into the third section. Root cause was in useSubNavSync's IntersectionObserver callback, not page structure as initially diagnosed: the callback read only changed entries (not all currently-intersecting) and sorted ascending by top (oldest-in-band wins) instead of picking the most-recently-passed section. Replaced with running-Set tracking + activation-line rule. Page structure verified clean (no duplicate or nested data-subnav-section). Closes sync bug from 2.5.7 testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 9. Deviations

- **D-2.5.8-2 deviation: hook IS modified.** Spec instructed
  "fix at the page-structure level, not the hook level. The hook
  is correct; the data attributes are mislabeled." The diagnostic
  audit found no mislabeled or duplicate attributes anywhere; the
  hook's IO callback algorithm is the actual defect. Page-only
  fixes considered:

  1. **Narrow the rootMargin band** so only one section can
     intersect at a time. Tested mentally: with `space-y-12`
     (48px gap between sections) and a 1%-viewport band (~9px on
     900vh), single-section intersection holds. But fast scrolls
     skip the band entirely — IO fires with the section ENTERING
     and EXITING in the same frame, net no callback. Fragile.
  2. **Add empty spacer sections between content sections** so
     the band always has at least 16px of padding either side.
     Adds layout noise and doesn't address the entries-only +
     wrong-sort flaws.
  3. **Increase `space-y-12` to `space-y-32`** to widen gaps.
     Defaces the page visually for a band-arithmetic fix.

  None of those address the actual two flaws (entries-only view,
  ASCENDING sort). The hook patch is the right surgical fix.

- **No `useRef` for the `Set`.** The `Set<HTMLElement>` is
  declared inside the `useEffect` body — it lives for the
  observer's lifetime and gets a fresh instance on every rebind.
  No stale-closure risk because the observer is also recreated
  on rebind.

- **`window.innerHeight * 0.15` is hard-coded to match
  `rootMargin: "-15% 0px -55% 0px"`.** If the rootMargin ever
  changes, both must update. Not parameterised today because
  there's only one consumer of this logic. A future commit could
  derive the activation line from the rootMargin string for
  full DRY-ness.

- **`measured` is computed via `getBoundingClientRect`** —
  preferred over `entry.boundingClientRect` because the entry's
  rect is from the time the threshold was crossed, which can be
  several frames behind on fast scrolls. `getBoundingClientRect`
  reads live layout. Layout thrash is bounded (≤ 6 elements per
  fire on either page), no perf concern.

- **Initial-active fallback unchanged.** The "set first slug if
  current is empty" path lives inside the IO effect (added in
  2.5.7), still works.

- **No tests.** IntersectionObserver mocking in JSDom is
  unreliable; the verification matrix in §6 covers behavioural
  ground.
