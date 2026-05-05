# Phase 2.5.5 — cart entry-point consolidation across browse pages

UX-audit outcome — 4+ redundant "View Cart" surfaces on `/services`,
`/category/{slug}`, and `/services/{cat}/{sub}` collapsed to 2
purposeful surfaces, with the contextual card placed BELOW the
primary booking/estimate panel:

1. **Global top-header cart icon** (untouched) — industry-standard
   global access, available on every page.
2. **Contextual `<SmartMiniCart>`** — placed BELOW the primary
   booking/estimate/car-selection panel in each browse page's
   right sidebar. Renders only when cart has items. Booking is
   primary; cart is secondary.

This commit supersedes the prior 2.5.5 commit (`ef5d8a0`) which
positioned `<SmartMiniCart>` ABOVE the booking panel — the
operator's design priority is booking-first, cart-secondary
(D-2.5.5-6).

Frontend-only commit. No backend, no API contract, no FEATURES
flag changes.

---

## 1. Files modified

### New (already shipped in `ef5d8a0`; unchanged here)
| Path | Purpose |
|---|---|
| `src/components/SmartMiniCart.tsx` | Sidebar mini-cart — header, max 3 item lines + "+N more", total, VIEW CART CTA. Renders null when items empty. |

### Modified (this commit)
| Path | Why |
|---|---|
| `src/pages/Services.tsx` | Removed sub-nav `Cart (N)` link (missed in prior 2.5.5); flipped sidebar order so `<BookingSidebar>` is first and `<SmartMiniCart>` is second. |
| `src/pages/ServiceCategory.tsx` | Moved `<SmartMiniCart>` from top of right aside to AFTER the Re-Check Prices card (still before trust-badges section). |
| `src/pages/ServiceDetail.tsx` | Same move — `<SmartMiniCart>` now lives after the booking-context card. |

### New (this commit)
| Path | Purpose |
|---|---|
| `PHASE2_5_5_REPORT.md` | This report (overwrites prior version). |

---

## 2. PART A — re-audit findings

Grep `View Cart|VIEW CART|CART \(|service in your cart|service added|ITEMS IN CART|item in cart|Cart \(` across `src/`:

| # | File | Lines | Surface | Pre-2.5.5 (orig) | Prior 2.5.5 (`ef5d8a0`) | This commit |
|---|---|---|---|---|---|---|
| 1 | `pages/Services.tsx` | 226–233 | Sub-nav "Cart (N)" link at far-right | Present | **Still present (missed)** | **Removed** |
| 2 | `pages/Services.tsx` | 345–366 | Mid-page floating cart strip | Present | Removed | (n/a) |
| 3 | `pages/Services.tsx` aside | (now 360-372) | `<SmartMiniCart>` above `<BookingSidebar>` | n/a | **Wrong order (above)** | **Inverted → below** |
| 4 | `pages/ServiceCategory.tsx` sub-nav | 619-626 | "CART (N)" anchor | Present | Removed | (n/a) |
| 5 | `pages/ServiceCategory.tsx` body | 880-900 | Mid-page strip | Present | Removed | (n/a) |
| 6 | `pages/ServiceCategory.tsx` aside | line 1119 | `<SmartMiniCart>` at TOP of sidebar | (n/a) | **Wrong placement** | **Moved → after Re-Check Prices card** |
| 7 | `pages/ServiceCategory.tsx` aside | 1340-1363 (orig) | Bottom inline VIEW CART card | Present | Removed | (n/a) |
| 8 | `pages/ServiceDetail.tsx` aside | line 674 | `<SmartMiniCart>` at TOP of sidebar | (n/a) | **Wrong placement** | **Moved → after booking context card** |
| 9 | `pages/ServiceDetail.tsx` aside | 881-902 (orig) | Bottom inline VIEW CART button | Present | Removed | (n/a) |

`components/BookingSidebar.tsx` carries no cart UI — verified by
grepping `cart|Cart|ShoppingCart` (no matches). The cart UI lived
inline in each parent page; consolidation has been done at the
parent-page level.

The top-header cart icon (`components/Header.tsx`) and the per-row
"ADDED" status badge (`pages/ServiceCategory.tsx`) are untouched
per D-2.5.5-4 / D-2.5.5-5.

---

## 3. PART B — sub-nav cleanup diff (`Services.tsx`)

```diff
              : apiCategories.map((c) => (
                  <button
                    key={c.id}
                    onClick={() => scrollToSection(c.slug)}
                    className={`text-[10px] sm:text-xs uppercase tracking-widest font-bold py-4 px-3 sm:px-5 whitespace-nowrap border-b-2 transition-colors shrink-0 ${
                      activeSection === c.slug
                        ? "border-primary text-primary"
                        : "border-transparent text-neutral-500 hover:text-primary"
                    }`}
                  >
                    {c.title}
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
+           {/* Phase 2.5.5 — sub-nav is category-anchors only
+               (D-2.5.5-1). The previous "CART (N)" link was a
+               redundant cart entry point; the global header icon
+               and the contextual SmartMiniCart in the right
+               sidebar own that role. */}
          </div>
        </div>
      </nav>
```

The `ServiceCategory.tsx` sub-nav had its `Cart (N)` link removed
in the prior 2.5.5 commit (`ef5d8a0`); no further change needed
there. The `Services.tsx` sub-nav had the same pattern but was
overlooked — fixed here.

After this commit:
- `/category/{slug}`: Overview · Pricing · Services · Process · Reviews · FAQs
- `/services`: dynamic category strip (Car Battery · Car Emergency · …) — no CART link

---

## 4. PART C — mid-page strip removal (verification)

Both mid-page strips were already removed in the prior 2.5.5
commit (`ef5d8a0`):
- `ServiceCategory.tsx` lines 880-900 (the "{N} services in your cart" strip between price-list and Services Included).
- `Services.tsx` lines 345-366 (the floating cart summary after the categories list).

Greps for `service in your cart` and `service added` return zero
matches in source files (only documentation comments).

---

## 5. PART D — `<SmartMiniCart>` component preview

Component already shipped in `ef5d8a0` and is unchanged here. Snippet
of the rendered shape (when 2 items in cart):

```
┌─────────────────────────────────────┐
│ 🛒 2 ITEMS IN CART                  │   header — primary tint, uppercase
├─────────────────────────────────────┤
│ Battery Charging          ₹1,650    │   item line × max 3
│ Battery Replacement       ₹4,950    │   ↕ divide-y between rows
├─────────────────────────────────────┤
│ TOTAL                     ₹6,600    │   border-t separator, font-black
│                                     │
│      [VIEW CART →]                  │   btn-ink-primary, w-full
└─────────────────────────────────────┘
```

Key implementation notes (unchanged):
- Renders `null` when `useCart().items.length === 0` — parent
  sidebar reflows to single-card naturally.
- `Intl.NumberFormat('en-IN', { style:'currency', currency:'INR', maximumFractionDigits: 0 })` for `₹1,650` (not `₹1650`).
- `bg-white border border-primary/30 shadow-xl` matches the
  primary booking card's chrome so the two stack as visual
  siblings.
- `motion.div` entrance (`initial y:10, animate y:0`) so the card
  slides in when items first land.

---

## 6. PART E — sidebar wiring (per page)

The structural change in this commit is the placement flip — booking
panel is now PRIMARY (top), `<SmartMiniCart>` is SECONDARY (below).

### `Services.tsx` — order swap
```diff
            {/* ───── BOOKING SIDEBAR ───── */}
            <aside className="order-1 lg:order-2 space-y-5">
-             {/* Phase 2.5.5 — contextual mini-cart, sibling to the
-                 BookingSidebar (D-2.5.5-3). */}
-             <SmartMiniCart setCurrentPage={setCurrentPage} />
+             {/* Phase 2.5.5 (D-2.5.5-3, D-2.5.5-6) — booking panel is
+                 PRIMARY (top of sidebar); SmartMiniCart is SECONDARY,
+                 rendered BELOW and conditional on cart non-empty. */}
              <BookingSidebar
                titleStart="EXPERIENCE THE BEST"
                titleAccent="CAR SERVICES"
                titleEnd="IN"
                stickyTopPx={STICKY_OFFSET_PX}
              />
+             <SmartMiniCart setCurrentPage={setCurrentPage} />
            </aside>
```

This is the structural inversion the new spec calls out explicitly
("the current cart card appears ABOVE the car-selection panel —
INVERT this"). After this commit `/services` has the operator-
correct hierarchy.

### `ServiceCategory.tsx` — relocation
- `<SmartMiniCart>` removed from the top of the aside (between the
  comment block and the Re-Check Prices `<div>`).
- `<SmartMiniCart>` re-mounted between the Re-Check Prices card's
  closing `</div>` and the trust-badges card.
- Aside order is now: Re-Check Prices → SmartMiniCart → Trust
  Badges (Why Trust Us). The trust-badges card is informational
  chrome unaffected by either re-order.

### `ServiceDetail.tsx` — same relocation pattern
- `<SmartMiniCart>` removed from the top of the aside (it was
  immediately under the comment block, before the booking-context
  card).
- `<SmartMiniCart>` re-mounted after the booking-context card's
  closing `</div>` and before the trust-badges card.
- Final order: Booking Context → SmartMiniCart → Trust Badges.

---

## 7. Final state

### `/services` (cart with 2 items)
```
Sub-nav: Car Battery · Car Emergency · … (NO CART link)

Right sidebar:
┌ BookingSidebar (PRIMARY)             ┐
│  Location · Car · Phone · Get Estimate│
└──────────────────────────────────────┘
┌ SmartMiniCart (SECONDARY, conditional) ┐
│  🛒 2 ITEMS IN CART                  │
│  Battery Charging        ₹1,650      │
│  Battery Replacement     ₹4,950      │
│  TOTAL                   ₹6,600      │
│       [VIEW CART →]                  │
└──────────────────────────────────────┘
```

### `/category/car-battery` (cart with 1 item)
```
Sub-nav: Overview · Pricing · Services · Process · Reviews · FAQs
         (NO CART link)

Page body: Hero → Price List → Services Included → …
           (NO mid-page strip between Price List and Services Included)

Right sidebar:
┌ Re-Check Prices Panel (PRIMARY)      ┐
└──────────────────────────────────────┘
┌ SmartMiniCart (SECONDARY)            ┐
│  🛒 1 ITEM IN CART                   │
│  Battery Charging        ₹1,650      │
│  TOTAL                   ₹1,650      │
│       [VIEW CART →]                  │
└──────────────────────────────────────┘
┌ Trust Badges (Why Trust Us)          ┐
└──────────────────────────────────────┘
```

### Empty cart on either page
- Top header: cart icon, no badge.
- Sub-nav: section/category anchors only.
- Right sidebar: booking panel only — `<SmartMiniCart>` returns
  `null` and the trust-badges card sits directly under the booking
  panel.

---

## 8. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-O4_C5Wv2.css  107.32 kB │ gzip:  17.56 kB
dist/assets/index-Bw9-IsAv.js   780.90 kB │ gzip: 205.22 kB
✓ built in 22.91s
```

Pre-existing >500 kB chunk warning unchanged.

---

## 9. Commit

`fix(frontend): Phase 2.5.5 — cart entry-point consolidation across browse pages. Remove redundant CART links from /services + /category sub-navs; remove mid-page strip; unify bottom-sidebar VIEW CART cards into SmartMiniCart placed BELOW booking/estimate panel (priority: booking primary, cart secondary). Final state: 2 purposeful cart surfaces (global header icon + contextual sidebar mini-cart) replacing 4+ redundant entry points. UX audit outcome from operator design review.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 10. Deviations

- **Ships as a follow-up to `ef5d8a0`, not a clean replacement.**
  The prior 2.5.5 commit got the placement priority backwards
  (cart above booking) and missed the `Services.tsx` sub-nav
  CART link. This commit corrects both. A future cleanup could
  squash both into one historical commit if desired; not done
  here to preserve the audit trail.
- **No mobile sticky CTA added.** Out of scope; the existing
  top-header icon already shows the count badge on mobile.
- **`<SmartMiniCart>` component itself is unchanged.** Only its
  parent-page mount points were modified.
- **`ServiceCategory.tsx` aside sticky positioning preserved.**
  `lg:sticky lg:self-start` with custom `top` style means the
  whole stack (Re-Check Prices → SmartMiniCart → Trust Badges)
  scrolls together; the inversion doesn't affect sticky behaviour.
- **No prop drilling refactor.** `setCurrentPage` is still passed
  page → SmartMiniCart manually, matching every other component
  on these pages. A context-based nav lift remains a Phase 3
  router-migration concern.
- **Trust-badges card stays beneath SmartMiniCart on
  `/category/{slug}`.** Visual hierarchy: primary booking card →
  conditional cart card → static trust card. The trust card was
  always last in the aside; preserving that.
