# SERVICE_PAGES_PHASE2C_REPORT — Layer-1 active-category tabs + shared ServiceCard (verified)

**Status: DONE and screenshot-verified.** `/services` (Layer 1) is now an **active-category
TAB** view inside the persistent `ServicesShell`: the shell's ONE cross-category bar selects
the active category **in-place** (URL stays `/services`, no route change, the sidebar + bar
never remount), and the body shows ONLY that category's cards plus a **"View full page →"**
link to `/category/:slug`. The old all-categories vertical dump is gone. The service card is
extracted into **one shared `ServiceCard`** used by Layer 1 and Layer 2.

- **TSC:** clean — only the **2 pre-existing** errors in `tests/e2e/brand-typography.spec.ts`. Zero new.
- **Vite build:** clean (`✓ built in ~6s`).
- **Backend Pest:** **317 passed (1327 assertions)** — unchanged (no backend edits).
- **Frontend e2e (`--project=phase2`):** **14/14 passed** (9 prior + 5 new; 1 prior test updated for the new tab behavior).

---

## PART A — shared `ServiceCard` extraction

- **New `src/components/service/ServiceCard.tsx`** — the inline `<article>` from
  `ServiceCategory.tsx` moved here verbatim, props typed cleanly. The card is presentational +
  emits `onAdd / onRemove / onViewDetail`; the **price 4-state + cart logic stays in the parent**
  (D-2c-5) — the parent computes `showPrice / pricingLoading / price / inCart / justAdded` and
  passes them in, so behavior is byte-identical.
- **New `src/components/service/categoryIcon.ts`** — the per-category fallback-icon map lifted
  out of `ServiceCategory.tsx` so **both** layers resolve the SAME glyph.
- **`ServiceCategory.tsx` (Layer 2) refactored** to render `<ServiceCard …/>` (removed the inline
  duplicate + its local icon map + the now-unused `ServiceMetaRow`/`ExploreCardFallback` imports).

**Layer-2 identical proof:** the existing Layer-2 anatomy test still passes, and the regenerated
close-up `phase2-shots/category-desktop-card.png` is pixel-equivalent — fallback tile + category
badge, duration pill, title, ServiceMetaRow (duration · warranty · interval), 4-item inclusions
preview, "+5 more · View All", ₹ price + ADD TO CART. No visual change.

---

## PART B — Layer-1 active-category tabs

### One bar, two behaviors (D-2c-2 — no second bar)
The shell owns the active-tab state and hands it to its children via `<Outlet context>`:
```ts
const [activeTab, setActiveTab] = useState("");
const effectiveActiveTab = activeTab || categories[0]?.slug || "";   // default = first category
const isServicesLayer = segs[0] === "services" && segs.length === 1; // exactly /services
```
The single cross-category bar branches on the layer:
```tsx
const active = isServicesLayer ? c.slug === effectiveActiveTab : c.slug === categorySlug;
onClick={() => isServicesLayer ? setActiveTab(c.slug) : navigate(`/category/${c.slug}`)}
```
- On **/services** → click sets the active tab (pure React state; **no navigation**).
- On **/category/:slug** & **detail** → click navigates to `/category/:slug` (unchanged).

Because a tab switch is same-route client state, the App-level `animKey` ("services-shell") and
the shell's pathname-keyed crossfade are both untouched → **no remount, no crossfade, no
scroll-jump → instant swap, by construction.** `Services.tsx` reads `activeTab` via
`useOutletContext<ServicesShellContext>()`.

### Body = only the active category (D-2c-3 / D-2c-6)
`Services.tsx` fetches the active category through the **same `fetchCategoryDetail` call + React
Query key** Layer 2 uses → identical data, identical price 4-state, the shared `ServiceCard`, and
a **warm cache so "View full page →" is instant**. It renders: `{Category} Services` heading +
**"View full page →"** + the personalised/select-car banner + the `ServiceCard` list + the global
trust strip. The per-category vertical dump is removed.

### In-place-swap proof (URL stays /services, sidebar mounted)
e2e test `L1 tabs — switch swaps cards in place; URL stays /services; sidebar same node`:
captures the live `[data-testid="car-sidebar"]` handle, asserts the default tab shows Car Battery
cards (and **no** regular-car-service item), clicks the Regular Car Service tab, then asserts:
`toHaveURL(/\/services$/)` (unchanged), the body now shows `primary service` and **no** Battery
Charging, the bar's `aria-current="page"` moved, and `handle.isConnected === true` with exactly
**one** sidebar. Screenshots `phase2c-services-tabA-desktop.png` (banner still **"OUR SERVICES"**,
Car Battery active, 2 cards) and `…-tabB-desktop.png` (still "OUR SERVICES", Regular active, 3
cards) prove the in-place swap.

---

## PART C — tests (phase2 project, all green)
New:
1. `L1 tabs — switch swaps cards in place; URL stays /services; sidebar same node`.
2. `L1 tabs — 'View full page' routes to the full /category page` (asserts Layer-2-only section nav).
3. `ServiceCard parity — identical anatomy on Layer-1 tab and Layer-2 page` (inclusions_preview,
   ₹ price 4-state, image-null fallback — asserted on both layers).
4. `L1 tabs — add to cart from a card works (no regression)`.
5. `L1 tabs — mobile 390 tab A + tab B (in-place swap)`.

Updated: the prior 2b `…/services → category → detail chain` test now hops `/services → category`
via **"View full page →"** (the bar tab is in-place in 2c), not a bar-click navigation.

**Full suite:** `--project=phase2` → **14/14 passed**. Backend Pest → **317 passed**. TSC clean.
Vite build clean. Zero regressions.

---

## PART D — screenshots (hard gate, inspected)
| File | Viewport | Shows |
|---|---|---|
| `phase2c-services-tabA-desktop.png` | 1440 | /services, **Car Battery** tab active, only its 2 cards, "View full page →", one bar, sidebar right |
| `phase2c-services-tabB-desktop.png` | 1440 | /services after switching to **Regular Car Service** — banner still "OUR SERVICES" (URL unchanged), one bar (active moved), 3 cards swapped in |
| `phase2c-services-tabA-mobile.png` | 390 | mobile tab A (Car Battery), stacked cards |
| `phase2c-services-tabB-mobile.png` | 390 | mobile tab B (Regular Car Service), stacked cards |
| `phase2c-category-l2-desktop.png` | 1440 | Layer-2 `/category/regular-car-service` UNCHANGED — shared cards + Overview/Why-Us/Process/Reviews/FAQs/Brands sections |
| `category-desktop-card.png` | — | Layer-2 card close-up (extraction identical) |

**D8 violation sweep (all clear):** exactly **one** category bar (the shell's — no second bar);
tab click does **not** change the route (banner stays "OUR SERVICES", URL `/services`); sidebar is
the **same DOM node** across a tab switch (count 1); zero GoMechanic red/grey; cards render intact
(fallback tiles, ₹ price, Add-to-Cart).

---

## Brand check (D-2c-5)
ACR **blue** accents (active tab underline, links, prices, CTAs), navy fallback tiles, Montserrat
section headings, Inter body. **Zero** GoMechanic red/grey; CTAs blue.

---

## Files modified / added
- **NEW** `src/components/service/ServiceCard.tsx` — shared card (Layer 1 + Layer 2).
- **NEW** `src/components/service/categoryIcon.ts` — shared fallback-icon map.
- `src/layouts/ServicesShell.tsx` — `activeTab` state + `effectiveActiveTab` + exported
  `ServicesShellContext` + `<Outlet context>`; bar branches tab-vs-nav by layer;
  `data-cat-slug` + `aria-current` on bar buttons.
- `src/pages/Services.tsx` — rewritten as the Layer-1 active-category tab view (reads `activeTab`
  from Outlet context, `fetchCategoryDetail`, `ServiceCard` list + "View full page →" + trust strip;
  all-categories dump removed).
- `src/pages/ServiceCategory.tsx` — uses shared `ServiceCard` + shared `categoryIcon`; inline
  article + local icon map + `ServiceMetaRow`/`ExploreCardFallback` imports removed.
- `tests/e2e/service-pages-phase2.spec.ts` — 5 new Phase-2c tests; 1 prior chain test updated.

**New screenshots:** `phase2-shots/phase2c-services-{tabA,tabB}-{desktop,mobile}.png`,
`phase2-shots/phase2c-category-l2-desktop.png`.

**No** migrations, **no** slug changes, **no** `service_prices`/pricing changes, **no** new packages.

---

## Deviations (called out)
1. **Layer-1 fetches per active category via `fetchCategoryDetail`** (not the lean `/services`
   nested data). This is deliberate: only the category-detail payload carries `warrenty_info`, so
   it's the only way to get **true card parity** with Layer 2, and it shares Layer 2's React Query
   cache → "View full page →" is instant. Cost: one small fetch on a tab's first visit (cached for
   5 min thereafter).
2. **Active tab is shell-owned client state, not a `?cat=` query param.** Per the "your call"
   allowance — URL stays clean on `/services`, the choice persists across category↔detail nav
   while the shell is mounted, and resets to the first category only when the shell unmounts. (A
   `?cat=` param would add back-button history but interacts with the App-level keying/scroll; the
   simpler state was the better trade.)
3. **Layer-3 "related" was NOT converted to `ServiceCard`** (the optional item in D-2c-4). The
   related block shows price-less *navigational* recommendation cards; wiring the full price-4-state
   + cart card there is not low-risk, so it keeps its current simpler treatment.
4. **The personalised / "select your car" banner from Layer 2 is shown on Layer 1 too** — it guides
   the price reveal and keeps the two layers consistent.
5. The first/default tab is the **first category** returned by `/services` (currently *Car Battery*).

---

## GIT
No git commands were run. Files changed/added are listed above; **operator commits.**
