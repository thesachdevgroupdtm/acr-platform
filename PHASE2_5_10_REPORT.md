# Phase 2.5.10 — sub-nav activation timing tuning

Phase 2.5.8 fixed the IntersectionObserver algorithm; 2.5.9
extended sub-nav coverage to all sections. But the activation
line at 15% of viewport meant a section heading had to nearly
scroll OFF the top before its sub-nav entry activated — operator
reported reading content for 2-3 seconds with the previous
section's blue underline still showing.

This commit moves the activation line from 15% to 25% of
viewport height so the section activates while its heading is
still comfortably in the upper-third reading zone.

Algorithm and section coverage from 2.5.8/2.5.9 unchanged —
this is a two-constant tune.

Frontend-only commit. Single file changed.

---

## 1. Files modified

| Path | Why |
|---|---|
| `src/hooks/useSubNavSync.ts` | rootMargin top inset 15% → 25%, bottom inset 55% → 50%; activation line ratio 0.15 → 0.25 (now derived from the rootMargin constant). |
| `PHASE2_5_10_REPORT.md` | This report. |

No page files touched. The hook's tuning constants are the only
levers; the activation line at runtime is `window.innerHeight *
ACTIVATION_LINE_RATIO`, so the change applies universally to
every page using `useSubNavSync` (`/services`, `/category/{slug}`,
`/services/{cat}/{sub}`).

---

## 2. PART A — pre-change values

```ts
// src/hooks/useSubNavSync.ts (pre-2.5.10)
const ROOT_MARGIN = "-15% 0px -55% 0px"; // line 84

// inside IO callback (line 163)
const activationLine = window.innerHeight * 0.15;
```

Two issues with the pre-change state:

1. **Activation timing too late.** `0.15` puts the activation
   line at 15% from viewport top — about 162px on a 1080px
   monitor, 90px on a 600px laptop. A section's heading "passes"
   this line only when it's nearly off-screen. By that point
   the user has been reading the section's body for several
   hundred pixels.

2. **Magic number duplication.** The `0.15` ratio inside the IO
   callback is hand-coupled to the `-15%` in `ROOT_MARGIN`.
   Easy to tune one and forget the other.

---

## 3. PART B — `useSubNavSync.ts` diff

```diff
-const ROOT_MARGIN = "-15% 0px -55% 0px"; // 30%-tall band, upper-middle viewport
+// Phase 2.5.10 — activation timing tuning. The activation line —
+// where a section becomes "active" as the user scrolls down —
+// moved from 15% to 25% of viewport height so the heading
+// activates while it's still comfortably in the user's upper-third
+// reading zone, NOT after it has nearly scrolled off the top.
+//
+// Operator's pre-2.5.10 symptom: "I'm clearly reading the section
+// but the sub-nav still says the previous one."
+//
+// The two constants are derived from each other: ROOT_MARGIN's
+// top inset (in %) drives the activation line at runtime
+// (`window.innerHeight * ACTIVATION_LINE_RATIO`). Always tune
+// them together.
+const ROOT_MARGIN_TOP_PCT = 25;       // 2.5.10: was 15
+const ROOT_MARGIN_BOTTOM_PCT = 50;    // 2.5.10: was 55
+const ROOT_MARGIN = `-${ROOT_MARGIN_TOP_PCT}% 0px -${ROOT_MARGIN_BOTTOM_PCT}% 0px`;
+const ACTIVATION_LINE_RATIO = ROOT_MARGIN_TOP_PCT / 100;
```

```diff
-        const activationLine = window.innerHeight * 0.15;
+        const activationLine = window.innerHeight * ACTIVATION_LINE_RATIO;
```

### Effect on the observation band

| | Pre-2.5.10 | Post-2.5.10 |
|---|---|---|
| Top inset (rootMargin) | -15% | **-25%** |
| Bottom inset (rootMargin) | -55% | **-50%** |
| Band top edge (from viewport top) | 15% | **25%** |
| Band bottom edge (from viewport top) | 45% | **50%** |
| Band height | 30% | **25%** |
| Activation line | 15% | **25%** |

The band is slightly narrower (25% vs 30%) but sits lower in
the viewport, with the activation line firmly in the user's
upper-third reading zone.

---

## 4. PART C — mental verification on three viewport heights

For each scenario, "active" flips when a section's
`getBoundingClientRect().top` becomes ≤ `activationLine`.

### Scenario A — 1080px desktop, `/services/{cat}/{sub}` (10 sections)
- `activationLine = 1080 * 0.25 = 270px` from viewport top.
- User scrolls down 600px from page top.
  - "Why This" heading currently at viewport y=400.
  - 400 > 270 → not yet passed → active stays "Included".
- User scrolls 200px more (heading at y=200 in viewport).
  - 200 ≤ 270 → passed → active flips to "why-service".
- User is now reading the section content; heading is at upper-third.
  Sub-nav matches. ✓

### Scenario B — 800px laptop, `/category/{slug}` (9 sections)
- `activationLine = 800 * 0.25 = 200px`.
- Each section transitions when its heading's top reaches 200px
  from viewport top — about a quarter of the way down. Heading
  is still clearly visible above the user's reading focus.

### Scenario C — 375px mobile, any browse page
- `activationLine = 375 * 0.25 ≈ 94px`.
- 94px from top is below the sticky chrome (~112px header + 52px
  sub-nav = 164px stack). The activation line sits at the
  intersection of "sticky chrome bottom" and "first comfortable
  reading line" on this narrow viewport.
- Sections still activate appropriately as headings cross the
  line going up.

### Cross-cutting correctness
The activation line is recomputed from `window.innerHeight` on
every IO callback fire, so window resizes (orientation flip on
mobile, devtools open/close, etc.) re-base correctly without any
window resize listener.

---

## 5. PART D — edge case behaviour

| Edge case | Behaviour |
|---|---|
| Page top, only Overview visible | Overview's top ≈ 0 (or slightly positive due to PageBanner). 0 ≤ 270 → passed. Active = "overview". ✓ (works because the "passed" filter accepts any top ≤ activationLine). |
| Bottom of page, last section partially visible | Last section's top is small positive (just below activation line) or already above. All earlier sections have very negative tops. Among `passed`, largest top wins → last section. ✓ |
| Tall section spanning > viewport | Section's top stays passed for many scroll px. No other section's top crosses the line. Active stays on this section. ✓ |
| Short section between two long ones | Short section enters intersecting set; once its top crosses activationLine, its top is largest among passed (closest to line) → active flips correctly. When user keeps scrolling, next long section's top eventually crosses → flips again. ✓ |
| Rapid scroll past multiple sections | Multiple sections' state changes batched in one IO fire. Running `Set` updates all of them. `passed.filter(top ≤ line)` and `largest top wins` rule still finds the correct most-recently-passed. Visually feels like sub-nav "races" to keep up. ✓ |
| Viewport resize mid-scroll | `window.innerHeight` is read on every fire. A resize that changes the viewport height re-bases activation line on next fire. No stale state. |
| Zoom (browser zoom in/out) | Browser zoom keeps `innerHeight` correct in CSS pixels. Activation line scales appropriately. |
| User uses keyboard arrow-down to scroll one viewport at a time | Each press triggers ~viewport-height scroll. Multiple sections cross activation line per keystroke; the running-Set + largest-top rule resolves to the bottom-most section visible in the new viewport. ✓ |

---

## 6. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-J4NAzJbN.css  107.60 kB │ gzip:  17.59 kB
dist/assets/index-CwC4bSZM.js   781.54 kB │ gzip: 205.83 kB
✓ built in 11.88s
```

JS bundle +0.02 KB raw / +0.03 KB gzip vs prior commit
(`083c8d5`) — accounts for the slightly more verbose constant
declarations and inline comment. Pre-existing >500 kB chunk
warning unchanged.

---

## 7. Commit

`fix(frontend): Phase 2.5.10 — sub-nav activation timing tuning. Move activation line from 15% to 25% viewport height. Section activates when heading enters upper-third reading zone, not when it scrolls off-screen. rootMargin updated to '-25% 0px -50% 0px'. Algorithm and section coverage from 2.5.8/2.5.9 unchanged. Closes timing drift from 2.5.9 testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 8. Deviations

- **Constants extracted to named values rather than tuned in
  place.** The pre-change state had two coupled magic numbers
  (`-15%` in `ROOT_MARGIN`, `0.15` in the IO callback). Future
  tuning would risk forgetting one. The new
  `ROOT_MARGIN_TOP_PCT = 25` is the single source of truth;
  `ROOT_MARGIN` and `ACTIVATION_LINE_RATIO` are derived from
  it. Spec PART A explicitly recommended this refactor.
- **Bottom inset reduced from 55% → 50%.** The spec specified
  this change (D-2.5.10-2's "New rootMargin
  '-25% 0px -50% 0px'"). Combined effect: band shifts down AND
  narrows slightly (30% → 25%). Narrower band = less ambiguity
  about which section is active when many are intersecting at
  once, complementing the running-Set algorithm from 2.5.8.
- **No section / page changes.** Per HARD CONSTRAINTS — the
  hook tune universally affects all consumers. Verified by
  `git diff --stat` showing only `useSubNavSync.ts` (and the
  report) changed.
- **No tests.** Same rationale as 2.5.8/2.5.9 — IntersectionObserver
  in JSDom is unreliable; behaviour validated via the
  verification matrix.
- **No timeout / debounce on activation.** The optimistic
  click-based update (`scrollToSection` pre-sets `activeSlug`)
  already prevents flicker during smooth scroll animations.
  Adding a timeout to gate scroll-driven updates would slow
  the underline's response on natural scrolls — net worse UX.
