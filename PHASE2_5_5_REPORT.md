# Phase 2.5.5 — cart entry-point consolidation

UX-audit outcome: 4 redundant "View Cart" surfaces on
`/category/{slug}` (and parallel render sites on
`/services/...`, `/services`) collapsed to 2 purposeful surfaces:

1. Global top-header cart icon (untouched).
2. Contextual `<SmartMiniCart>` in the right sidebar, sibling
   to the existing booking-context card.

Frontend-only commit. No backend, no API contract, no FEATURES
flag changes.

---

## 1. Files modified

### New
| Path | Purpose |
|---|---|
| `src/components/SmartMiniCart.tsx` | Sidebar mini-cart with item titles, total, VIEW CART CTA. Renders only when cart has items. |
| `PHASE2_5_5_REPORT.md` | This report. |

### Modified
| Path | Why |
|---|---|
| `src/pages/ServiceCategory.tsx` | Removed sub-nav `Cart (N)` link; removed mid-page strip; removed bottom-of-sidebar VIEW CART card; mounted `<SmartMiniCart>` at the top of the right sidebar. |
| `src/pages/ServiceDetail.tsx` | Removed bottom-of-sidebar VIEW CART card; mounted `<SmartMiniCart>` at the top of the right sidebar. |
| `src/pages/Services.tsx` | Removed mid-page floating cart summary; mounted `<SmartMiniCart>` above the BookingSidebar in the right aside (added `space-y-5` to the aside). |

---

## 2. PART A — pre-2.5.5 cart-link render sites (audit)

Grep `View Cart|CART (|service in your cart|service added` across `src/`:

| # | File | Lines | Surface | Disposition |
|---|---|---|---|---|
| 1 | `pages/ServiceCategory.tsx` | 619–626 | Sub-nav "CART (N)" anchor at far-right of section nav | **Removed** (D-2.5.5-1) |
| 2 | `pages/ServiceCategory.tsx` | 880–900 | Mid-page strip "{N} services in your cart [VIEW CART →]" between price-list and Services Included | **Removed** (D-2.5.5-2) |
| 3 | `pages/ServiceCategory.tsx` | 1340–1363 | Bottom-of-sidebar `<motion.button>` "VIEW CART / N services added" | **Replaced by `<SmartMiniCart>` at top of sidebar** |
| 4 | `pages/Services.tsx` | 345–366 | Floating strip after the categories list, same shape as #2 | **Removed** + `<SmartMiniCart>` mounted in sidebar |
| 5 | `pages/ServiceDetail.tsx` | 881–902 | Bottom-of-sidebar VIEW CART button | **Replaced by `<SmartMiniCart>` at top of sidebar** |

`components/BookingSidebar.tsx` does not render any cart UI — the sidebar VIEW CART cards lived inline inside their parent pages.

The top-header cart icon (`components/Header.tsx`) and the per-row "ADDED" badge (`pages/ServiceCategory.tsx` services list) are untouched per D-2.5.5-4 / D-2.5.5-5.

---

## 3. PART B — sub-nav cleanup diff

`pages/ServiceCategory.tsx` lines 606–629:

```diff
            {SECTION_NAV.map((s) => (
              <button
                key={s.id}
                onClick={() => scrollToSection(s.id)}
                className={`text-[10px] sm:text-xs uppercase tracking-widest font-bold py-4 px-3 sm:px-5 whitespace-nowrap border-b-2 transition-colors shrink-0 ${
                  activeSection === s.id
                    ? "border-primary text-primary"
                    : "border-transparent text-neutral-500 hover:text-primary"
                }`}
              >
                {s.label}
              </button>
            ))}
-           {count > 0 && (
-             <button
-               onClick={() => setCurrentPage("cart")}
-               className="ml-auto flex items-center gap-2 text-[10px] sm:text-xs uppercase tracking-widest font-bold py-4 px-3 sm:px-5 text-primary whitespace-nowrap shrink-0"
-             >
-               <ShoppingCart className="w-4 h-4" /> Cart ({count})
-             </button>
-           )}
          </div>
```

The strip now contains only the 6 anchor buttons defined in `SECTION_NAV` (Overview / Pricing / Services / Process / Reviews / FAQs). Mixing global navigation ("Cart") into a section-anchor strip violated the purpose of the strip; the global header icon owns that role.

---

## 4. PART C — mid-page strip removal diff

`pages/ServiceCategory.tsx` lines 880–900 (now collapsed to a single comment):

```diff
-           {count > 0 && pricesShown && (
-             <motion.div
-               initial={{ opacity: 0, y: 10 }}
-               animate={{ opacity: 1, y: 0 }}
-               className="mt-5 bg-neutral-50 border border-border p-4 flex items-center justify-between gap-4"
-             >
-               <div className="flex items-center gap-3 min-w-0">
-                 <ShoppingCart className="w-5 h-5 text-primary shrink-0" />
-                 <p className="text-sm font-bold text-neutral-900 tracking-tighter truncate">
-                   {count} {count === 1 ? "service" : "services"} in your
-                   cart
-                 </p>
-               </div>
-               <button
-                 onClick={() => setCurrentPage("cart")}
-                 className="bg-primary text-white px-4 py-2.5 ..."
-               >
-                 View Cart <ArrowRight className="w-3.5 h-3.5" />
-               </button>
-             </motion.div>
-           )}
+           {/* Phase 2.5.5 — mid-page strip removed; page flows
+               directly from price-list to "Services Included". */}
          </section>
```

`pages/Services.tsx` lines 345–366: same shape removed; page flows directly from category cards to the trust strip.

---

## 5. PART D — SmartMiniCart component

```
┌─────────────────────────────────────┐
│ 🛒 1 ITEM IN CART                   │   header — primary tint
├─────────────────────────────────────┤
│ Battery Charging          ₹1,650    │   item line × max 3
├─────────────────────────────────────┤
│ + 2 more items                      │   overflow line, italic
├─────────────────────────────────────┤
│ TOTAL                     ₹4,250    │   border-t separator
│                                     │
│      [VIEW CART →]                  │   btn-ink-primary, full-width
└─────────────────────────────────────┘
```

- Props: `className?`, `setCurrentPage?` (page-router setter; falls back to `window.location` if omitted so the component is usable in isolation).
- Renders `null` when `useCart().items.length === 0` — the parent sidebar reflows naturally.
- Item rows show title, optional `× qty` modifier, and line total.
- `Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 })` formats prices as `₹1,650` not `₹1650`.
- Visual contract: `bg-white border border-primary/30 shadow-xl` matches the existing booking-card chrome (same border weight, same shadow scheme) so the two cards stack visually as siblings.
- `motion.div` entrance animation matches the prior cart-summary card (`initial y:10, animate y:0`) so the appearance feel is preserved.

---

## 6. PART E — sidebar wiring

| Page | Aside container before | After |
|---|---|---|
| `ServiceCategory.tsx` | `<aside class="… space-y-5">` then booking card → trust badges → bottom VIEW CART card → trust badges (continued) | Same `space-y-5` aside; **`<SmartMiniCart setCurrentPage={setCurrentPage} />` inserted as first child**; bottom inline VIEW CART card removed. |
| `ServiceDetail.tsx` | `<aside class="space-y-6 …">` then booking-context card → … → bottom VIEW CART button | **`<SmartMiniCart setCurrentPage={setCurrentPage} />` inserted as first child**; bottom inline VIEW CART button removed. |
| `Services.tsx` | `<aside class="order-1 lg:order-2">` containing only `<BookingSidebar/>` | Aside now `space-y-5`; **`<SmartMiniCart setCurrentPage={setCurrentPage} />` inserted above `<BookingSidebar/>`**. |

In all three pages the import block also gains `import SmartMiniCart from "../components/SmartMiniCart";`.

---

## 7. Before / after states (described)

**Empty cart state** (`/category/car-battery`, guest browsing):
- Top header: cart icon, no badge.
- Sub-nav: 6 section anchors only — no "CART" entry.
- Page body: services list flows directly into "Services Included" (no strip between).
- Right sidebar: booking context card → trust badges. **No mini-cart.**
- Net result: page is decluttered, focused on browsing.

**Cart with 1 item** (after clicking ADD on a service row):
- Top header: cart icon shows "1" badge.
- Service row: ADDED status badge appears.
- Right sidebar: **`<SmartMiniCart>` slides in at the top** showing "1 ITEM IN CART", the service title with price `₹1,650`, TOTAL row, and VIEW CART button. Sits above the booking context card as a sibling.
- Page body: still no mid-page strip.
- Sub-nav: still anchor-only.

**Cart with 4+ items**:
- `<SmartMiniCart>` shows the first 3 service titles + their line totals, then `+ 1 more items` overflow line, then aggregated TOTAL of all 4.
- VIEW CART button unchanged.

**VIEW CART click paths** all converge on `setCurrentPage('cart')` (which is `navigateTo` after Phase 2.5.2):
- Top header cart icon → `/cart`.
- SmartMiniCart VIEW CART button → `/cart`.
- (Removed paths: sub-nav link, mid-page strip, bottom sidebar card.)

---

## 8. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-O4_C5Wv2.css  107.32 kB │ gzip:  17.56 kB
dist/assets/index-DzM-MUgp.js   781.18 kB │ gzip: 205.28 kB
✓ built in 13.18s
```

Pre-existing >500 kB chunk warning unchanged.

---

## 9. Commit

`fix(frontend): Phase 2.5.5 — cart entry-point consolidation. Remove redundant CART link from category sub-nav; remove mid-page 'X service in cart' strip; replace bottom sidebar VIEW CART card with SmartMiniCart component (item titles, total, CTA) mounted above Re-Check Prices panel. Final state: 2 purposeful cart surfaces (global header icon + contextual sidebar mini-cart) replacing 4 redundant entry points. UX audit outcome from operator design review.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 10. Deviations

- **`Services.tsx` mid-page strip removed even though spec scoped PART C to category pages.** The spec showed `/category/car-battery` as the reproduction case but the same pattern existed verbatim on `/services`. Leaving it would have meant the inconsistency surfaced again on the next browse-page audit; cheaper to align both now.
- **`Services.tsx` aside got `space-y-5` added.** Was a single child (BookingSidebar) so spacing didn't matter; with `<SmartMiniCart>` above it the gap is needed. ServiceCategory + ServiceDetail asides already had `space-y-5` / `space-y-6` for their existing card stacks.
- **SmartMiniCart accepts an optional `setCurrentPage` prop, not a hard `useNav` import.** Pages already destructure `setCurrentPage` from props (Phase 2.5.2 wired `navigateTo` into that name), so the existing pattern is reused. Component falls back to `window.location.href = '/cart'` when called outside a routed page (safety net for future adopters).
- **No mobile sticky CTA added.** The spec-screenshot mobile pattern showed a sticky bottom strip on small viewports — out of scope here since the existing top-header icon already shows count on mobile. Worth revisiting in a future mobile-UX pass.
- **`ShoppingCart` / `ArrowRight` imports left in `Services.tsx` and `ServiceDetail.tsx`.** Both icons are still used elsewhere on those pages (header tile, "View Details" arrows). Removing the imports would have been incorrect.
- **No prop drilling refactor.** `setCurrentPage` is still passed page → SmartMiniCart manually. A future commit could lift the navigation entry to a context once the codebase migrates to react-router (Phase 3); for now the explicit prop matches every other component on these pages.
