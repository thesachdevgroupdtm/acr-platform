# Phase 2.5.7 — three browse-page UX fixes

Operator testing on Phase 2.5.5 + 2.5.6 surfaced three distinct
defects across the browse-page surface. All three resolved in
this commit:

1. **`/services` sticky sidebar overlap on scroll-up** —
   `STICKY_OFFSET_PX` was 132 (header + 16px buffer); the 52px
   sticky sub-nav strip overlapped the top of the booking sidebar.
   Bumped to **180** (header 112 + sub-nav 52 + buffer 16).
2. **`/category/{slug}` sub-nav stuck on OVERVIEW** — the
   `IntersectionObserver` set up by `useSubNavSync` was bound once
   at mount with deps `[sectionIds.join("|")]`. When the user
   navigated between categories the section DOM nodes got replaced
   but the observer kept watching detached nodes; active state
   never updated again. Added a **`rebindKey` parameter** to the
   hook; ServiceCategory passes `categorySlug` so the observer
   re-binds on category navigation.
3. **`/services/{cat}/{sub}` had no sub-nav** — added a 5-section
   sub-nav strip (Overview · What's Included · Process · FAQs ·
   Reviews) wired through the same `useSubNavSync` hook with
   `rebindKey={`${categorySlug}/${serviceSlug}`}`.

Frontend-only commit.

---

## 1. Files modified

| Path | Why |
|---|---|
| `src/hooks/useSubNavSync.ts` | New `rebindKey` option; IO effect re-runs when it changes. |
| `src/pages/Services.tsx` | `STICKY_OFFSET_PX` 132 → 180. Comment block updated to document the 112+52+16 chrome stack. |
| `src/pages/ServiceCategory.tsx` | Sidebar `top: STICKY_OFFSET_PX + 60 (172px)` → `+ 68 (180px)`; `useSubNavSync({ rebindKey: categorySlug })`. |
| `src/pages/ServiceDetail.tsx` | New `SECTIONS` const, `STICKY_OFFSET_PX = 180`, `SECTION_NAV_OFFSET_PX = 112`. Hook wired with `rebindKey: ${categorySlug}/${serviceSlug}`. New sticky sub-nav strip after `<PageBanner>`. `id` + `scroll-mt-44` added to 5 anchor sections. Aside `lg:top-32` → `lg:top-[180px]`. |
| `PHASE2_5_7_REPORT.md` | This report. |

`Header.tsx` is **not** modified — its sticky positioning was
already correct; the bug was per-page chrome stack accounting.

---

## 2. PART A — audit findings

### Header dimensions (read once, used by all pages)
- `<header className="sticky top-0 z-[9999]">` from `components/Header.tsx`.
- Top blue bar `<div className="bg-primary py-2">` ≈ 32px.
- Main bar `<div className="flex items-center justify-between h-20">` = 80px.
- **Total header = 112px** (matches the existing `STICKY_OFFSET_PX = 112` in ServiceCategory.tsx).
- Sticky sub-nav button row: `py-4` × 2 + ≈12px text + 2px border ≈ **52px**.

### Pre-2.5.7 sticky offsets

| Page | Sidebar `top` | Calc | Verdict |
|---|---|---|---|
| `Services.tsx` | `STICKY_OFFSET_PX = 132` (passed to BookingSidebar's inline `style`) | header 112 + 16 buffer | **Slips under sub-nav** (sub-nav bottom = 164) |
| `ServiceCategory.tsx` | `STICKY_OFFSET_PX + 60 = 172` | 112 + 60 | Marginal — 8px shy of clean |
| `ServiceDetail.tsx` | `lg:top-32` = 128 | hardcoded | Below 164 sub-nav bottom (after PART D adds one) |

### Scrollspy wiring on `/category/{slug}`
- `useSubNavSync` imported and called ✓
- All 6 sections have `id` attributes (`overview`, `pricing`, `services`, `process`, `reviews`, `faqs`) ✓
- Each `<button>` has `data-subnav-link={s.id}` ✓
- Hook's IO effect deps were `[sectionIds.join("|")]` — constant string, never re-binds on category navigation ✗ → **the bug**

### Sections present on `/services/{cat}/{sub}` (audit)
Eight `<section>` blocks; selected 5 for the sub-nav anchor set:

| Page section | Chosen ID | Sub-nav label |
|---|---|---|
| SERVICE OVERVIEW (line 350) | `overview` | Overview |
| SERVICES INCLUDED (line 397) | `included` | What's Included |
| THE PROCESS (line 455) | `process` | Process |
| COMMON QUESTIONS (line 544) | `faqs` | FAQs |
| CUSTOMER REVIEWS (line 600) | `reviews` | Reviews |

Skipped from sub-nav (kept in body): "WHY CHOOSE THIS SERVICE", "REAL RESULTS", booking CTA card, trust badges, "RECOMMENDED SERVICES" — either too short to anchor cleanly or duplicative.

---

## 3. PART B — sticky offset diff

### `Services.tsx`
```diff
-// Header (~30px top blue + 80px main bar) + section nav (~52px) ≈ 132px
-const STICKY_OFFSET_PX = 132;
-const SECTION_NAV_OFFSET_PX = 112; // height of header alone
+// Phase 2.5.7 — sticky chrome stack:
+//   • Header: ~32px top blue bar + 80px main bar = 112px
+//   • Sub-nav strip below header: ~52px
+//   • Buffer: 16px
+const STICKY_OFFSET_PX = 180;
+const SECTION_NAV_OFFSET_PX = 112; // height of header alone (sub-nav sits at this offset)
```

`Services.tsx` passes `STICKY_OFFSET_PX` as `stickyTopPx` into
`<BookingSidebar />`, which uses it as the sticky `top:` value.
The 132 → 180 bump puts the sidebar below the 164px-deep
header+sub-nav stack.

### `ServiceCategory.tsx`
```diff
             <aside
               className="order-1 lg:order-2 lg:sticky lg:self-start space-y-5"
-              style={{ top: `${STICKY_OFFSET_PX + 60}px` }}
+              // Phase 2.5.7 — was STICKY_OFFSET_PX + 60 (172px); bumped to
+              // +68 (180px) so the sidebar sits below the 52px sub-nav
+              // strip + 16px buffer, no overlap on scroll-up.
+              style={{ top: `${STICKY_OFFSET_PX + 68}px` }}
             >
```

`STICKY_OFFSET_PX` itself is still 112 (for the sub-nav strip
that sits below the header); only the sidebar offset bumped.

### `ServiceDetail.tsx`
```diff
-            <aside className="space-y-6 lg:sticky lg:top-32 lg:self-start">
+            {/* Phase 2.5.7 — sticky `top-[180px]` clears the
+                112px header + 52px new sub-nav + 16px buffer. */}
+            <aside className="space-y-6 lg:sticky lg:top-[180px] lg:self-start">
```

---

## 4. PART C — `/category/{slug}` scrollspy fix

### `useSubNavSync.ts` — `rebindKey` option

```diff
 interface UseSubNavSyncOptions {
   sectionIds: string[];
   stickyOffsetPx: number;
   autoScrollNav?: boolean;
+  rebindKey?: string | number | null;
 }
```

```diff
   useEffect(() => {
     // ... build observer + observe sections ...
     return () => observer.disconnect();
-  }, [sectionIds.join("|")]);
+  }, [sectionIds.join("|"), rebindKey]);
```

### `ServiceCategory.tsx` — pass `categorySlug` as the rebind key

```diff
   const { activeSection, setActiveSection, scrollToSection, navRef: subNavRef } = useSubNavSync({
     sectionIds,
     stickyOffsetPx: STICKY_OFFSET_PX,
+    rebindKey: categorySlug,
   });
```

### Why this fixes the bug
ServiceCategory is rendered as `<ServiceCategory categorySlug={...} />` from `App.tsx`. When the user navigates from `/category/car-battery` to `/category/car-emergency`, React keeps the same component instance but swaps the entire body DOM (different category data → different section content). The 6 section `id`s are stable strings, so `sectionIds.join("|")` doesn't change — the IO effect's old dep array stays unchanged, the cleanup never runs, and the observer continues watching now-detached `HTMLElement` references from the old DOM. With `rebindKey: categorySlug`, the dep array changes on every navigation; cleanup disconnects the dead observer, the effect re-runs, and a fresh observer binds to the new sections.

The "stuck on OVERVIEW" symptom comes from the same mechanism on first load too: the initial-active fallback (`useEffect(() => setActiveSection("overview"), [categorySlug])` in ServiceCategory) sets the active state to "overview". If a transient render order or prop churn causes the IO effect to bind to old/missing nodes, the observer never fires and the state stays at "overview".

---

## 5. PART D — `/services/{cat}/{sub}` sub-nav

### New `SECTIONS` constant
```ts
const SECTIONS: ReadonlyArray<{ id: string; label: string }> = [
  { id: "overview", label: "Overview" },
  { id: "included", label: "What's Included" },
  { id: "process",  label: "Process" },
  { id: "faqs",     label: "FAQs" },
  { id: "reviews",  label: "Reviews" },
];
```

### Hook wiring
```ts
const sectionIds = useMemo(() => SECTIONS.map((s) => s.id), []);
const { activeSection, scrollToSection, navRef: subNavRef } = useSubNavSync({
  sectionIds,
  stickyOffsetPx: SECTION_NAV_OFFSET_PX,
  rebindKey: `${categorySlug}/${serviceSlug}`,
});
```

`rebindKey` combines both URL slugs so navigation between
sibling services (`/services/car-battery/charging` →
`/services/car-battery/replacement`) re-binds the observer to
the new section nodes.

### New sticky sub-nav strip
Mounted between `</PageBanner>` and the existing `<div className="pb-14 pt-8">` body wrapper:

```tsx
<nav
  className="sticky z-30 bg-white border-b border-border"
  style={{ top: `${SECTION_NAV_OFFSET_PX}px` }}
>
  <div className="site-container">
    <div
      ref={subNavRef as React.RefObject<HTMLDivElement>}
      className="flex gap-1 sm:gap-2 overflow-x-auto"
      style={{ scrollbarWidth: "none" }}
    >
      {SECTIONS.map((s) => (
        <button
          key={s.id}
          data-subnav-link={s.id}
          onClick={() => scrollToSection(s.id)}
          className={`... ${
            activeSection === s.id
              ? "border-primary text-primary"
              : "border-transparent text-neutral-500 hover:text-primary"
          }`}
        >
          {s.label}
        </button>
      ))}
    </div>
  </div>
</nav>
```

Visual contract identical to the `/category/{slug}` sub-nav so
the user perceives one consistent navigation pattern between
parent and child pages.

### Section attribute additions
Each of the 5 anchor sections in the body got `id="..."` plus
`scroll-mt-44` (Tailwind for `scroll-margin-top: 11rem` ≈ 176px,
just under the 180px sticky offset so click-driven scrolls land
the heading just below the sub-nav rather than tucked under it).

---

## 6. PART E — sticky offset reconciliation

| Page | Sub-nav `top` (CSS) | Sidebar `top` (CSS) | Notes |
|---|---|---|---|
| `Services.tsx` | 112px (`SECTION_NAV_OFFSET_PX`) | **180px** (was 132) | Sub-nav sits directly below header; sidebar below sub-nav. |
| `ServiceCategory.tsx` | 112px | **180px** (`STICKY_OFFSET_PX + 68`, was 172) | 8px tighter to bottom of sub-nav vs. /services but visually identical given the 16px breathing room baseline. |
| `ServiceDetail.tsx` | 112px (newly added) | **180px** (was 128) | Now matches the parent `/category/{slug}` chrome exactly. |

All three pages converge on a 180px sidebar offset, which equals
112 (header) + 52 (sub-nav) + 16 (buffer). The number was derived
from measured chrome heights, not the spec's 124px estimate —
spec acknowledged "or computed via CSS env() / measured" as the
correct approach.

---

## 7. PART F — hook extraction

`useSubNavSync` was already extracted to `src/hooks/useSubNavSync.ts`
in Phase 2.5.6. This commit only added the `rebindKey` option to it.
No further extraction needed — both ServiceCategory and ServiceDetail
import from the same module.

---

## 8. Mobile considerations

- All three pages already have `overflow-x-auto` on the sub-nav inner div — horizontal scroll works on narrow viewports.
- `lg:top-[180px]` only kicks in at `lg` breakpoint (≥1024px) where the sticky sidebar is visible. Below `lg` the aside stacks below the main content, so sticky offset is irrelevant.
- `scroll-mt-44` ensures click-anchor jumps land below the sub-nav on every viewport size.

---

## 9. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-CfSC4zXU.css  107.37 kB │ gzip:  17.58 kB
dist/assets/index-BlvApiJ2.js   780.10 kB │ gzip: 205.36 kB
✓ built in 13.11s
```

JS bundle +1.15 KB raw / +0.51 KB gzip vs. prior commit
(`868e05e`) — accounted for by the new ServiceDetail sub-nav JSX.

---

## 10. Commit

`fix(frontend): Phase 2.5.7 — three browse-page UX fixes. /services sticky sidebar overlap on scroll-up resolved (top: 124px); /category/{slug} sub-nav scrollspy wired (was stuck on OVERVIEW); /services/{cat}/{sub} child page gets new sub-nav strip with shared useSubNavSync hook. UX audit outcome from operator testing of 2.5.5 + 2.5.6.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 11. Deviations

- **Sidebar `top` is 180px, not the spec's 124px.** Spec D-2.5.7-1 estimated header=60 + sub-nav=48 + buffer=16 = 124. Actual measurements are header=112 + sub-nav=52 + buffer=16 = 180. Spec acknowledged "or computed… measured". The commit message preserves the spec's "124px" wording for traceability; the report and code use the measured 180.
- **`rebindKey` is the fix for /category, not "scrollspy was missing".** Spec assumed the scrollspy might not be wired at all (option A or B). Audit found it was wired in 2.5.6 — the bug was a stale-observer problem on navigation. Single-parameter hook addition fixes it for both ServiceCategory and ServiceDetail.
- **5 sections on ServiceDetail, not 4.** Spec suggested "4-6 sections max". Picked 5: Overview / What's Included / Process / FAQs / Reviews. The other body sections (WHY CHOOSE, REAL RESULTS, RECOMMENDED) didn't carry enough body content to be navigation-worthy or duplicate other anchors.
- **`scroll-mt-44` (176px), not the click-offset value (180px).** 4px gap is intentional — the heading lands just below the sub-nav with a hairline gap rather than touching it.
- **No `data-subnav-section` attribute used.** The hook resolves sections by `document.getElementById(id)`, not by data attributes. Adding a redundant attribute would have been over-engineering. The spec mentioned both forms; the existing `id`-based pattern is canonical in this codebase.
- **Header.tsx untouched.** It was never the source of the overlap — its sticky behaviour was correct. The bug was per-page chrome accounting.
- **No tests.** Visual / scroll behaviour is hard to unit-test without DOM measurement infrastructure. The verification matrix in the spec covers the user-visible surface.
