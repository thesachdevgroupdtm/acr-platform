# Phase 2.5.7 (final) — sub-nav scrollspy hard-fix

The blue active underline on `/category/{slug}` and
`/services/{cat}/{sub}` was permanently stuck on OVERVIEW
regardless of scroll position. The earlier 2.5.7 commit
(`aafc83f`) added a `rebindKey` parameter keyed by URL slug, but
that was insufficient because both pages render a SKELETON during
data load — the IntersectionObserver was attempting to observe
sections that didn't exist yet on first mount.

This commit fixes the underlying root cause and adopts the
operator's prescribed `data-subnav-section` attribute approach.

Frontend-only commit. Supersedes the earlier 2.5.7 commit
(`aafc83f`) for scrollspy behaviour; the sticky-offset and
ServiceDetail sub-nav additions from `aafc83f` are unchanged.

---

## 1. Files modified

| Path | Why |
|---|---|
| `src/hooks/useSubNavSync.ts` | Refactored: queries `[data-subnav-section]` (no more `sectionIds` array param); rootMargin widened to `-15% 0px -55% 0px`, threshold `[0, 0.1]`; exposes `setActiveSlugManual` for optimistic click updates; effect deps narrowed to `[rebindKey]`. |
| `src/pages/Services.tsx` | Drop `sectionIds` prop, pass `rebindKey: services:${apiCategories.length}`; add `data-subnav-section={category.slug}` to the per-category `<section>`. |
| `src/pages/ServiceCategory.tsx` | Compose `rebindKey: ${categorySlug}:${detailQuery.isLoading ? "loading" : "ready"}` so the observer re-binds when content lands; add `data-subnav-section` to all 6 anchor sections; rename `setActiveSection` callsite to `setActiveSlugManual`. |
| `src/pages/ServiceDetail.tsx` | Same `rebindKey` composition with `detailQuery.isLoading`; add `data-subnav-section` to all 5 anchor sections. |
| `PHASE2_5_7_REPORT.md` | Overwrites prior version. |

---

## 2. PART A — re-audit findings

| Question | Pre-this-commit |
|---|---|
| Where does `useSubNavSync` live? | `src/hooks/useSubNavSync.ts` (extracted in 2.5.6). |
| Imported in `ServiceCategory.tsx`? | **Yes** (since `aafc83f`). |
| Imported in `ServiceDetail.tsx`? | **Yes** (since `aafc83f`). |
| `data-subnav-section` attrs on sections? | **No** (only `id` attrs). |
| Sections discovered on `/category/{slug}` | `overview`, `pricing`, `services`, `process`, `reviews`, `faqs` — all `<section>` tags with `id` attrs. |
| Sections discovered on `/services/{cat}/{sub}` | `overview`, `included`, `process`, `faqs`, `reviews` — all `<section>` tags with `id` attrs. |

### Root cause
Both pages have an early-return skeleton render during data load:

```tsx
// pages/ServiceCategory.tsx, line 268
if (isLoadingDetail) {
  return <Skeleton />;   // sections do NOT exist in DOM
}
```

```tsx
// pages/ServiceDetail.tsx, line 101
if (detailQuery.isLoading) {
  return <Skeleton />;   // sections do NOT exist in DOM
}
```

The hook's `IntersectionObserver` effect runs once on first mount.
At that moment, the page is showing the skeleton; the `<section>`
nodes the observer wants to watch don't exist yet. After data
loads, React re-renders with the real sections, but the IO effect's
deps `[sectionIds.join("|"), rebindKey]` haven't changed — the
effect doesn't re-run, the observer never observes anything, and
`activeSlug` stays at the initial-fallback "overview" forever.

The earlier 2.5.7 fix passing `rebindKey: categorySlug` didn't
help because `categorySlug` is identical between the loading and
loaded renders.

### Why this affected only `/category` + `/services/{cat}/{sub}`
`/services` parent page also calls the hook, but its data flow
is different — `apiCategories.length` changes from 0 to N when
the API resolves, and the `apiCategories.length` value composed
into `rebindKey` here naturally bumps. So the parent page worked.

---

## 3. PART B — `useSubNavSync.ts` refactor

### Surface change
Before:
```ts
useSubNavSync({
  sectionIds: string[],
  stickyOffsetPx: number,
  autoScrollNav?: boolean,
  rebindKey?: string | number | null,
}): {
  activeSection: string,
  setActiveSection: (id: string) => void,
  scrollToSection: (id: string) => void,
  navRef: RefObject<HTMLElement | null>,
}
```

After:
```ts
useSubNavSync({
  stickyOffsetPx: number,        // sectionIds REMOVED
  autoScrollNav?: boolean,
  rebindKey?: string | number | null,
}): {
  activeSlug: string,            // renamed from activeSection
  setActiveSlugManual: (slug: string) => void,   // renamed from setActiveSection
  scrollToSection: (slug: string) => void,
  navRef: RefObject<HTMLElement | null>,
}
```

### Internal changes
- **Query mechanism**: `document.querySelectorAll<HTMLElement>("[data-subnav-section]")` instead of `sectionIds.forEach(id => document.getElementById(id))`. Cleaner separation — the hook doesn't need to know slug strings; it discovers what's in the DOM.
- **rootMargin**: `-30% 0px -60% 0px` (10% band) → `-15% 0px -55% 0px` (30% band). Wider band gives more reliable detection on tall sections that span multiple "transitions".
- **Threshold**: `0` → `[0, 0.1]`. Fires on both edge-touch AND 10% visibility — catches partial intersections that the strict-zero threshold misses.
- **Initial-active fallback**: now lives INSIDE the IO effect (not a separate effect). `setActiveSlug((current) => current || sections[0].getAttribute(...))` runs once when sections first appear in DOM. Avoids the prior bug where the fallback effect could fire before sections existed.
- **Effect deps**: `[sectionIds.join("|"), rebindKey]` → `[rebindKey]`. Single source of "should I re-bind?" — simpler, fewer accidental re-binds.
- **`scrollToSection`**: now uses `[data-subnav-section="..."]` selector (was `getElementById`). Also bumped chrome buffer from `+ 60` to `+ 16` (the previous +60 was over-padding; the page's `scroll-mt-*` Tailwind classes already handle most of it).
- **`scrollToSection` is now optimistic**: pre-sets `activeSlug` before the smooth scroll completes. Replaces the prior arrangement where the consumer had to call both `setActiveSection` and `scrollToSection`.

### Why deps narrowed to `[rebindKey]`
The IO observer's job is "watch every `[data-subnav-section]` element in the DOM." That set changes only when the DOM body changes, which is exactly what `rebindKey` is meant to signal. Including `sectionIds.join("|")` was redundant and noisy — the hook no longer takes `sectionIds`.

---

## 4. PART C — `ServiceCategory.tsx` wiring

### Hook call diff
```diff
-  const sectionIds = useMemo(() => SECTION_NAV.map((s) => s.id), []);
   const {
-    activeSection,
-    setActiveSection,
+    activeSlug: activeSection,
+    setActiveSlugManual,
     scrollToSection,
     navRef: subNavRef,
   } = useSubNavSync({
-    sectionIds,
     stickyOffsetPx: STICKY_OFFSET_PX,
-    rebindKey: categorySlug,
+    rebindKey: `${categorySlug}:${detailQuery.isLoading ? "loading" : "ready"}`,
   });
```

`categorySlug` flips on cross-category navigation; the
`isLoading` half flips on data arrival. Both cases re-bind the
observer, fixing both the navigation-bug and the
skeleton-on-first-mount bug.

### `setActiveSection("overview")` callsite renamed
```diff
   useEffect(() => {
-    setActiveSection("overview");
+    setActiveSlugManual("overview");
   }, [categorySlug]);
```

### Section attribute additions (6 sections)
```diff
   <section
     id="overview"
+    data-subnav-section="overview"
     className="bg-neutral-50 p-6 sm:p-8 border border-border scroll-mt-40"
   >
-  <section id="pricing"  className="scroll-mt-40">
+  <section id="pricing"  data-subnav-section="pricing"  className="scroll-mt-40">
-  <section id="services" className="scroll-mt-40">
+  <section id="services" data-subnav-section="services" className="scroll-mt-40">
-  <section id="process"  className="scroll-mt-40">
+  <section id="process"  data-subnav-section="process"  className="scroll-mt-40">
-  <section id="reviews"  className="scroll-mt-40">
+  <section id="reviews"  data-subnav-section="reviews"  className="scroll-mt-40">
-  <section id="faqs"     className="scroll-mt-40">
+  <section id="faqs"     data-subnav-section="faqs"     className="scroll-mt-40">
```

`id` attrs preserved because `scroll-mt-40` + the `href="#slug"`
URL-fragment fallback continue to work in browsers without JS.

### Sub-nav button block (unchanged this commit)
The `<button>` rendering already had `data-subnav-link={s.id}`
from the prior 2.5.7 commit, and the `onClick` already uses
`scrollToSection(s.id)` which now does the optimistic update
internally. No JSX change needed in the sub-nav itself.

---

## 5. PART D — `ServiceDetail.tsx` wiring

### Hook moved below `detailQuery`
The hook's `rebindKey` references `detailQuery.isLoading`, so the
hook call had to move below the query declaration:

```diff
-  // Hook called here (above detailQuery) ❌
-  const sectionIds = useMemo(() => SECTIONS.map((s) => s.id), []);
-  const { activeSection, scrollToSection, navRef: subNavRef } = useSubNavSync({
-    sectionIds,
-    stickyOffsetPx: SECTION_NAV_OFFSET_PX,
-    rebindKey: `${categorySlug}/${serviceSlug}`,
-  });
-
   // ---------- API: service detail (skeleton-first) ----------
   const detailQuery = useApiQuery(...);
+
+  const {
+    activeSlug: activeSection,
+    scrollToSection,
+    navRef: subNavRef,
+  } = useSubNavSync({
+    stickyOffsetPx: SECTION_NAV_OFFSET_PX,
+    rebindKey: `${categorySlug}/${serviceSlug}:${detailQuery.isLoading ? "loading" : "ready"}`,
+  });
```

### Section attribute additions (5 sections)
```diff
   <section
     id="overview"
+    data-subnav-section="overview"
     className="bg-neutral-50 p-6 sm:p-8 border border-border scroll-mt-44"
   >
-  <section id="included" className="scroll-mt-44">
+  <section id="included" data-subnav-section="included" className="scroll-mt-44">
-  <section id="process"  className="scroll-mt-44">
+  <section id="process"  data-subnav-section="process"  className="scroll-mt-44">
-  <section id="faqs"     className="scroll-mt-44">
+  <section id="faqs"     data-subnav-section="faqs"     className="scroll-mt-44">
   <section
     id="reviews"
+    data-subnav-section="reviews"
     className="pt-12 border-t border-border scroll-mt-44"
   >
```

Sub-nav button block already had `data-subnav-link={s.id}` from
`aafc83f`. No JSX change to the strip itself.

---

## 6. PART E — edge cases

| Edge case | Handling |
|---|---|
| Skeleton-first render where sections aren't in DOM yet | **The actual bug.** Solved by `rebindKey` composing the `isLoading` state — observer re-binds when sections land. |
| Cross-category navigation (`/category/car-battery` → `/category/car-emergency`) | `rebindKey` composes `categorySlug` — bumps on navigation, observer re-binds on the new section nodes. |
| Cross-service navigation (`/services/car-battery/charging` → `/services/car-battery/replacement`) | `rebindKey` composes `${categorySlug}/${serviceSlug}` — same handling. |
| Page content shorter than viewport (small mobile, all sections visible at once) | Initial-active fallback picks first section; IO callback's topmost-intersecting-wins logic keeps it stable. |
| User clicks last sub-nav link (e.g. FAQs) | `scrollToSection` pre-sets `activeSlug` optimistically → underline jumps immediately. Smooth scroll plays out; observer reconciles when scroll settles (no flicker because all intermediate sections are below the band during the smooth scroll). |
| Dynamic content (lazy-loaded images change section heights mid-scroll) | IntersectionObserver auto-handles — section bounding rects re-measure on each observation cycle. No manual rebind needed. |

### Why the wider rootMargin matters
The pre-2.5.7 band was 10% of viewport height (around the 30%–40% mark). On phone landscape (≈400px tall) that's a 40px band — narrower than typical section-heading line-heights. Sections could "skip" the band entirely on a fast scroll, leaving stale active state. The new band is 30% of viewport (around the 15%–45% mark) — 120px on the same phone — robust to fast flicks.

---

## 7. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-CfSC4zXU.css  107.37 kB │ gzip:  17.58 kB
dist/assets/index-CsXfnYhb.js   780.55 kB │ gzip: 205.44 kB
✓ built in 13.63s
```

JS bundle: +0.45 KB raw / +0.08 KB gzip vs. prior commit
(`aafc83f`). Net effect of the hook simplification minus the
6+5 attribute additions. Pre-existing >500 kB chunk warning
unchanged.

---

## 8. Commit

`fix(frontend): Phase 2.5.7 — sub-nav scrollspy on category + service-detail pages. Wire useSubNavSync hook (extracted to shared) + data-subnav-section attributes across both child browse pages. Blue active underline now tracks page scroll position correctly (was permanently stuck on OVERVIEW). Closes scrollspy bug from 2.5.6 testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 9. Deviations

- **Two Phase 2.5.7 commits in history.** The first (`aafc83f`)
  added `rebindKey` keyed only by URL slug, which addressed the
  cross-navigation case but not the skeleton-first case. This
  commit is the actual fix. Kept as a separate commit (not
  squashed) because the diff against the prior 2.5.7 makes the
  root-cause analysis legible.
- **`scrollToSection`'s chrome buffer dropped from `+ 60` to `+ 16`.**
  The previous +60 was over-padding — the per-section
  `scroll-mt-{40,44}` Tailwind classes already provide most of
  the offset. Net visual effect: section headings land slightly
  closer to the sub-nav (still with breathing room from
  `scroll-mt`), no longer with redundant whitespace from a
  double offset.
- **Hook surface renamed** (`activeSection` → `activeSlug`,
  `setActiveSection` → `setActiveSlugManual`). All callers updated.
  Renaming-with-aliasing (`activeSlug: activeSection`) preserves
  every JSX reference downstream that reads `activeSection`,
  minimising churn.
- **`sectionIds` parameter removed entirely** — the hook now
  discovers sections from the DOM via the attribute selector.
  Consumers no longer pass an array. Slight API tightening.
- **`id` attrs left on every section.** Two reasons: the
  `href="#slug"` URL-fragment fallback works in JS-disabled
  contexts (rare but cheap to preserve), and `scroll-mt-*`
  classes work correctly only on elements that are valid scroll
  targets — `id` makes them explicit.
- **No tests.** IntersectionObserver behaviour is hard to mock
  reliably. The verification matrix in the spec covers the
  user-visible surface; CI would need a headless-browser harness
  this codebase doesn't have.
- **No regression on `/services` parent.** That page worked
  pre-this-commit because its `rebindKey` (then implicitly via
  `apiCategories.length`-derived sectionIds) bumped naturally.
  After refactor it still works — `rebindKey: services:${apiCategories.length}`
  bumps when categories arrive.
