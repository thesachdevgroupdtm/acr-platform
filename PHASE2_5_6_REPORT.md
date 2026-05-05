# Phase 2.5.6 — sub-nav active-section auto-scroll

When the user scrolls past the visible portion of the horizontal
sticky sub-nav on `/services` (12 categories) — or `/category/{slug}`
on a narrow viewport — the active blue-underline indicator now
auto-scrolls into view. Standard scrollspy-with-self-scroll
pattern (Apple docs / Stripe docs / MDN sidebar). No external
library, native `IntersectionObserver` + `Element.scrollIntoView`.

Frontend-only commit.

---

## 1. Files modified

### New
| Path | Purpose |
|---|---|
| `src/hooks/useSubNavSync.ts` | Shared scrollspy + auto-scroll hook. Exposes `activeSection`, `setActiveSection`, `scrollToSection`, and a `navRef` to attach to the sub-nav scroll container. |
| `PHASE2_5_6_REPORT.md` | This report. |

### Modified
| Path | Why |
|---|---|
| `src/pages/Services.tsx` | Replaced inline `IntersectionObserver` + `scrollToSection` with `useSubNavSync({ sectionIds, stickyOffsetPx })`. Wired `navRef` to the horizontal-scroll inner div; added `data-subnav-link={slug}` to each category button. Dropped the now-unused `useEffect` import. |
| `src/pages/ServiceCategory.tsx` | Same swap. Section ids derived from the local `SECTION_NAV` const (Overview / Pricing / Services / Process / Reviews / FAQs). Reset effect on `categorySlug` change kept (prevents stale active state when navigating between categories). |

`src/pages/ServiceDetail.tsx` has no sub-nav — skipped (audit
confirmed). `src/components/Header.tsx` is global nav, not page
sub-nav — also skipped.

---

## 2. PART A — audit findings

### `Services.tsx` (PRIMARY target)
- IntersectionObserver scroll-spy already present (lines 73-91 pre-2.5.6).
- `rootMargin: "-30% 0px -60% 0px"` — already in the spec's recommended upper-middle band.
- Sections in body: `<section id={category.slug}>`.
- Sub-nav: `<button onClick={scrollToSection(c.slug)}>` with `activeSection === c.slug` underline.
- **Missing**: auto-scroll of the sub-nav container itself when active section changes off-screen. With 12 categories overflowing the viewport, this is the user-reported bug.

### `ServiceCategory.tsx`
- Same scroll-spy pattern (lines 207-224 pre-2.5.6), same `rootMargin`.
- 6-section nav (`SECTION_NAV` const). Fits inline on most desktops; can overflow on narrow viewports / mobile.
- **Missing**: same auto-scroll. Apply for narrow-viewport robustness — no-op when content fits.

### `ServiceDetail.tsx`
- No sub-nav at all (single-service detail). **Skip.**

### Other surfaces
- `Header.tsx` — global app nav, not section anchor. Skip.
- No other pages render an anchor sub-nav.

---

## 3. PART B/C — `useSubNavSync` hook

### Signature
```ts
function useSubNavSync({
  sectionIds: string[],
  stickyOffsetPx: number,
  autoScrollNav?: boolean,  // default true
}): {
  activeSection: string;
  setActiveSection: (id: string) => void;
  scrollToSection: (id: string) => void;
  navRef: RefObject<HTMLElement | null>;
}
```

### Internals (one source of truth across pages)

1. **Initial-active fallback** — first id wins until the observer or
   a click sets a real value. Re-checks on `sectionIds` change so
   async page-data flows (Services.tsx loading categories from the
   API) seed the active state once the list arrives.

2. **Scroll-spy** — `IntersectionObserver` with `rootMargin
   "-30% 0px -60% 0px"` and `threshold: 0` watches every
   `document.getElementById(id)`. Picks the topmost intersecting
   entry; sets `activeSection`.

3. **Auto-scroll the sub-nav** — useEffect on `[activeSection, autoScrollNav]`:
   ```ts
   const link = navRef.current?.querySelector(
     `[data-subnav-link="${CSS.escape(activeSection)}"]`
   );
   link?.scrollIntoView({
     behavior: "smooth",
     block: "nearest",   // never scrolls the page vertically
     inline: "center",   // centres the active link in the nav viewport
   });
   ```
   `CSS.escape` guards against slugs with special chars; `block:'nearest'`
   prevents unwanted vertical page scroll when the active link is in a
   different scroll container; `inline:'center'` is the standard
   "scroll-spy" centring behaviour.

4. **`scrollToSection(id)` helper** — click handler. Smooth-scrolls
   the page to the section (offset = `stickyOffsetPx + 60` to clear
   the header + section-nav stack), and proactively sets
   `activeSection` so the underline updates immediately rather than
   waiting for the IO callback after the smooth scroll completes.

### Edge cases handled
- **Click-loop guard**: clicking a sub-nav link → smooth page scroll → IntersectionObserver fires → `setActiveSection` → auto-scroll the nav. The auto-scroll is no-op when the link is already at-centre, and `scrollIntoView`'s `behavior: 'smooth'` is non-blocking, so the two animations coexist without jank.
- **User-initiated horizontal scroll preservation**: there is no `scrollIntoView({ inline: 'start' })` clamp or scroll-position restoration; if the user drags the nav while the page scrolls, the next active-change fires a *new* `scrollIntoView` that re-centres. Native browser behaviour handles momentum/inertia.
- **Empty sections list**: hook short-circuits when `sectionIds.length === 0`.
- **Stable observer**: `sectionIds.join("|")` as the effect dep so identity changes from upstream React Query don't churn the observer when the slug list is functionally unchanged.

---

## 4. PART D — per-page wiring

### `Services.tsx` (PRIMARY)
- Removed the inline `useState<string>("")` for `activeSection`, the inline `useEffect` for the scroll-spy observer, the inline initial-active fallback, and the inline `scrollToSection`.
- Replaced with:
  ```ts
  const sectionIds = useMemo(
    () => apiCategories.map((c) => c.slug),
    [apiCategories],
  );
  const { activeSection, scrollToSection, navRef } = useSubNavSync({
    sectionIds,
    stickyOffsetPx: SECTION_NAV_OFFSET_PX,
  });
  ```
- Wired `navRef` to the inner horizontal `<div>` of the sub-nav (the actual `overflow-x-auto` scroll container, not the outer `<nav>`).
- Added `data-subnav-link={c.slug}` to each `<button>`.
- Dropped the unused `useEffect` import (the hook owns all effects now).

### `ServiceCategory.tsx`
- Same swap. Section list comes from the local const:
  ```ts
  const sectionIds = useMemo(() => SECTION_NAV.map((s) => s.id), []);
  const {
    activeSection, setActiveSection, scrollToSection, navRef: subNavRef,
  } = useSubNavSync({ sectionIds, stickyOffsetPx: STICKY_OFFSET_PX });
  ```
- The legacy `useEffect` that re-bound the observer on `categorySlug` change collapsed to a tiny effect that resets `activeSection` to `"overview"` so the underline doesn't carry across category navigations.
- `setActiveSection` exposed by the hook is used here for that reset; it's also a public surface concession so existing call sites that pre-set state on click (e.g. `setTimeout(() => scrollToSection("pricing"), 50)`) keep working.
- Added `data-subnav-link={s.id}` to each `<button>` and wired `subNavRef`.

### `ServiceDetail.tsx`
Skipped — no sub-nav. (Audited via grep on `activeSection|<nav` — zero matches.)

### Why a shared hook
Two near-identical implementations would have drifted (one already had `categorySlug` as observer dep, the other didn't). The hook is ~90 LoC and centralises three subtleties at once: rootMargin tuning, click-vs-spy state precedence, and the new auto-scroll. Extracting was cheaper than dual-maintaining.

---

## 5. Mobile considerations

- The horizontal-scroll inner `<div>` already has `overflow-x-auto` on both pages — no layout change needed.
- Native `scrollIntoView({ behavior: 'smooth', inline: 'center' })` is supported on iOS Safari 14+ and Chrome / Edge / Firefox on Android. Older iOS gracefully falls back to instant scroll (the visual jump is fine; the active link still ends up centred).
- `block: 'nearest'` guarantees we never trigger a vertical page scroll on mobile, where the sub-nav's nearest *vertical* scroll container is sometimes the body itself.
- Touch-drag inertia: tested mentally — the hook's auto-scroll fires only on `activeSection` *change*, so a flick that keeps the active section unchanged doesn't trigger a competing programmatic scroll. The inertia plays out cleanly, then the next page-scroll-induced active change re-centres.
- 375px viewport: ServiceCategory's 6-section nav now overflows on narrow widths, and the auto-scroll pays off there too.

---

## 6. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-B-sNDa1V.css  107.26 kB │ gzip:  17.55 kB
dist/assets/index-DaCw_Gm2.js   778.95 kB │ gzip: 204.85 kB
✓ built in 14.40s
```

JS bundle effectively unchanged (+0.15 KB raw, +0.13 KB gzip vs.
prior commit `4ea8817`). The hook is small and the inline
implementations it replaced were similar size. Pre-existing
>500 kB chunk warning unchanged.

---

## 7. Commit

`fix(frontend): Phase 2.5.6 — sub-nav active-section auto-scroll. When page scroll moves the active category off-screen in the horizontal sub-nav, the nav now smooth-scrolls to keep the active link centered. IntersectionObserver-driven scrollspy + native scrollIntoView. Applied to /services (and other long sub-nav pages where applicable). UX audit outcome from operator design review.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 8. Deviations

- **Shared hook, not per-page inline.** The spec presented inline
  code as the primary example and only suggested extraction at the
  bottom ("If multiple pages need this, EXTRACT…"). Two pages do
  need it; extracting was the right call given the very recent
  Phase 2.5.5 outcome ("delete redundancies, even small ones").
- **`navRef` lives on the inner horizontal `<div>`, not the outer
  `<nav>`.** The outer `<nav>` element doesn't have
  `overflow-x-auto`; the inner div is the actual scroll container.
  `scrollIntoView` walks up to find the nearest scrollable ancestor
  regardless, but anchoring `navRef` on the inner div makes the
  intent explicit and means the `querySelector` lookup runs on
  exactly the relevant subtree.
- **TypeScript: `RefObject<HTMLElement | null>` cast to
  `RefObject<HTMLDivElement>` at the JSX site.** React 19 typings
  for `ref` on a `<div>` expect `HTMLDivElement | null`. The hook's
  generic ref is wider for portability; the consumer-side cast is
  one line per page and avoids a generic-explosion in the hook
  signature.
- **Public `setActiveSection` exposed.** Strictly `useSubNavSync`
  could keep the setter internal. Exposing it lets ServiceCategory
  reset on `categorySlug` change without re-architecting (the prior
  inline implementation already drove the reset via the observer
  re-binding on the dep change; the new hook's observer dep is
  `sectionIds.join("|")` which doesn't change with `categorySlug`).
- **No CSS scroll-snap, no padding compensation.** Tried-and-true
  `scrollIntoView({ inline: 'center' })` doesn't need either. Adding
  scroll-snap would interfere with user-driven horizontal drag; not
  worth the complexity here.
- **No ServiceDetail wiring.** It has no sub-nav. Adding one for
  consistency would be scope creep.
- **No tests.** The hook has no testable side effects easily mocked
  in unit tests (it depends on `IntersectionObserver` + DOM layout).
  Visual verification matrix in the spec covers the behavioural
  surface.
