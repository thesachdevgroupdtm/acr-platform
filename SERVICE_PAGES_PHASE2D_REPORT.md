# SERVICE_PAGES_PHASE2D_REPORT — four polish fixes (verified)

**Status: DONE and screenshot-verified.** Four surgical fixes to the service shell + Layer-2
page: (1) the cross-category bar now renders **below the PageBanner** as a **GoMechanic-style
icon + label** bar with left/right chevrons; (2) the redundant **"Prices personalised for…"**
blue pill is removed from every service surface; (3) Layer-2 drops the **"Brands We Service"**
section and (4) its **in-page section-nav scroller** + scrollspy. No data/pricing/slug changes;
tabs (2c), the persistent sidebar (2b), cart/booking and Layer-3 detail all still work.

- **TSC:** clean — only the **2 pre-existing** errors in `tests/e2e/brand-typography.spec.ts`. Zero new.
- **Vite build:** clean (`✓ built in ~5s`).
- **Backend Pest:** **317 passed (1327 assertions)** — unchanged (no backend edits).
- **Frontend e2e (`--project=phase2`):** **17/17 passed** (13 prior + 4 new; 2 prior tests updated for 2d).

---

## PART A — category bar reposition + icon redesign (shell)

**Order is now banner → bar → content (D-2d-1).** In `ServicesShell.tsx` the `<nav>` moved to
render **after** `<PageBanner>` and before the grid. It stays `sticky top-[112px] z-30`, so it
scrolls with the banner and then sticks under the site header once you scroll past the banner.
Still exactly **ONE** bar (the shell's).

**Icon + label redesign (D-2d-2).** Each item is a vertical stack: the category **icon**
(`icon_image` when present, else the shared `categoryIcon(slug)` lucide glyph — the same mapping
the fallback tiles use) + a **Montserrat** (`font-display`) uppercase label. Active item =
**ACR-blue underline + blue tint** (`border-primary text-primary`); inactive = muted
(`text-neutral-500`). `aria-current="page"` + `data-cat-slug` preserved. **Horizontal scroll**
kept (`overflow-x-auto`) with new **left/right chevron** buttons (desktop) that `scrollBy` the row
under a white gradient fade; hidden on mobile where touch-scroll suffices.

**Unchanged logic (verified):** the bar still drives the `/services` active **tab in-place**
(2c behavior, no nav) and **navigates to `/category/:slug`** on the other layers — only markup
position + styling changed.

**DOM-order proof:** e2e asserts (via `compareDocumentPosition`) that the `.page-title` banner
precedes the category `<nav>` (`"banner-first"`), that the bar's computed `position` is `sticky`,
and that the active item's `border-bottom-color` is **`rgb(31, 79, 163)`** (= `#1F4FA3`, ACR blue,
**not** red).

---

## PART B — personalised-price pill removed (D-2d-3)
The blue **"Prices personalised for {CAR} · {FUEL} in {AREA}"** pill is removed from **Layer 1**
(`Services.tsx`) and **Layer 2** (`ServiceCategory.tsx`) — the only two surfaces it rendered on
(Layer-3 detail never had it). The CarSidebar's own car display is untouched, the price 4-state on
cards is untouched, and the neutral **"Select your car" nudge** (a different element, shown only
when no vehicle is chosen) is kept. Now-unused `CheckCircle2` / `LOCATIONS` imports +
`selectedLocationName` were removed from `Services.tsx`.

**Proof:** e2e seeds a vehicle (so the pill *would* have shown) and asserts
`getByText(/Prices personalised for/i)` has **count 0** on both `/services` and `/category/:slug`.

---

## PART C — Layer-2 cleanups (D-2d-4)
`ServiceCategory.tsx`:
- **(a)** the **"Brands We Service"** `<section>` is deleted. `brandsQuery`/`brandList` stay —
  they still feed the Overview ("…and more", "{N}+ Supported") and Why-ACR copy, so the query is
  not dead.
- **(b)** the **in-page section-nav scroller** `<nav>` and its **scrollspy** are deleted:
  `useSubNavSync` usage (hook call, `activeSlug`/`scrollToSection`/`navRef`/`setActiveSlugManual`
  + the reset `useEffect`), the `SECTION_NAV` constant, the sticky-offset constants, and the
  `useSubNavSync` import. Dead imports that fell out (`React` namespace, `AnimatePresence`,
  `useEffect`) were removed too.
- The remaining content sections still render **in order**: Catalog → Overview → Services Included
  → Why Choose Us → How It Works → Customer Reviews → FAQs → Why ACR. The shell's cross-category
  bar is the only nav.

**Proof:** e2e asserts on `/category/regular-car-service` that `heading /BRANDS WE/i` has count 0,
`[data-subnav-link]` has count 0, while `/CHOOSE US/i`, `/HOW IT/i`, `/COMMON/i` headings are
visible, the sidebar persists, and an add-to-cart from a Layer-2 card still toggles to "Added".

---

## PART D — screenshots (hard gate, inspected)
| File | Viewport | Shows |
|---|---|---|
| `phase2d-services-iconbar-desktop.png` | 1440 | /services — **banner → icon bar → content**, Car Battery active (blue underline), chevrons, no personalised pill |
| `phase2d-services-iconbar-mobile.png` | 390 | /services icon bar (touch-scroll), stacked cards |
| `phase2d-category-l2-desktop.png` | 1440 | Layer-2 — **no section-nav, no Brands, no personalised pill**; Overview/Services Included/Why Us/How It Works/Reviews/FAQs/Why ACR intact; sidebar persists |
| `phase2d-category-l2-mobile.png` | 390 | Layer-2 cleaned, mobile |
| `phase2c-services-tab{A,B}-{desktop,mobile}.png` | 1440/390 | regenerated with the new icon bar (tab-switch in-place still works) |

**Violation sweep (all clear):** no second bar; banner precedes the bar; icons render (lucide
fallback glyphs); active underline is **blue** not red; Layer-2 has no section-nav and no Brands;
sections present; sidebar mounted; cart add works.

---

## Brand check
ACR **blue** active underline (`#1F4FA3` = `rgb(31,79,163)`, test-asserted) + blue icon/label tint
on the active item; Montserrat (`font-display`) labels; navy/blue palette throughout. **Zero**
GoMechanic red/grey.

---

## Files modified
- `src/layouts/ServicesShell.tsx` — bar moved below PageBanner; icon+label vertical redesign;
  active blue underline/tint; left/right chevron scroll affordance (`useRef` + `scrollBar`);
  `categoryIcon` + `ChevronLeft`/`ChevronRight` imports. Tab/nav logic unchanged.
- `src/pages/Services.tsx` — removed the personalised pill (kept the select-car nudge); removed
  unused `CheckCircle2`/`LOCATIONS` imports + `selectedLocationName`.
- `src/pages/ServiceCategory.tsx` — removed personalised pill, the section-nav `<nav>` + scrollspy
  (`useSubNavSync` usage, `SECTION_NAV`, offset consts, reset effect), the Brands section, and the
  now-dead `React`/`AnimatePresence`/`useEffect`/`useSubNavSync` imports.
- `tests/e2e/service-pages-phase2.spec.ts` — replaced the obsolete "section-nav collision" test
  with the 2d bar-order/sticky/blue-underline test; updated the "View full page" assertion
  (section nav → marketing heading); added 3 Phase-2d tests (personalised gone, Layer-2 cleaned,
  screenshots).

**New screenshots:** `phase2-shots/phase2d-services-iconbar-{desktop,mobile}.png`,
`phase2-shots/phase2d-category-l2-{desktop,mobile}.png`.

**No** migrations, **no** slug changes, **no** `service_prices`/pricing-logic changes, **no** new packages.

---

## Deviations (called out)
1. **`icon_image` is null for all 13 categories today**, so the bar renders the **lucide fallback
   glyphs** from `categoryIcon.ts`. The `icon_image ?? glyph` code path is in place — admin-uploaded
   icons will render automatically once present.
2. **The "Select your car" nudge is kept** (Layer 1 + Layer 2). D-2d-3 named only the blue
   "Prices personalised for…" pill (the duplicate of the sidebar car display); the neutral dashed
   nudge is a distinct, useful CTA shown only when no vehicle is selected.
3. **Dead `data-subnav-section` / `scroll-mt-40` attributes were left** on the remaining Layer-2
   sections. They are inert HTML attributes (no JS references them after the scrollspy removal);
   stripping them from 8 sections is churn with no functional benefit. No dead JS refs remain.
4. **Chevrons are desktop-only** (`hidden sm:flex`); mobile relies on native touch/overflow scroll.
5. **Frontend e2e scoped to `--project=phase2`** (consistent with prior phases). Confirmed via grep
   that no other e2e spec references the changed elements (section-nav / personalised pill / Brands),
   so the broader suite is unaffected.

---

## GIT
No git commands were run. Files changed are listed above; **operator commits.**
