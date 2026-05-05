# Phase 2.5.5 — cart entry-point consolidation (final)

UX-audit final outcome: **single global cart access** via the
top-header cart icon + count badge. Every other in-page cart
surface across browse pages has been removed:

- Sub-nav `Cart (N)` links — gone.
- Mid-page "X service in cart" strips — gone.
- Right-sidebar VIEW CART card / `<SmartMiniCart>` — gone.

The booking/estimate panel is now the sole occupant of the right
sidebar across `/services`, `/category/{slug}`, and
`/services/{cat}/{sub}`. Service-row "ADDED" badges remain as
status indicators only (D-2.5.5-5).

This commit supersedes both prior 2.5.5 attempts:
- `ef5d8a0` (mounted `<SmartMiniCart>` ABOVE booking panel)
- `7e80e23` (inverted to BELOW booking panel)

Both were intermediate steps that never matched the final
operator decision. This commit deletes the component entirely
and removes its mount points.

Frontend-only commit. No backend, no FEATURES flags, no API
contract changes.

---

## 1. Files modified

### Modified
| Path | Why |
|---|---|
| `src/pages/Services.tsx` | Removed `<SmartMiniCart>` mount + import. Sidebar is `<BookingSidebar>` only. |
| `src/pages/ServiceCategory.tsx` | Removed `<SmartMiniCart>` mount + import. Sidebar is Re-Check Prices booking card → trust-badges only. |
| `src/pages/ServiceDetail.tsx` | Removed `<SmartMiniCart>` mount + import. Sidebar is booking-context card → trust badges only. |
| `PHASE2_5_5_REPORT.md` | Overwrites prior version with the final-state report. |

### Deleted
| Path | Reason |
|---|---|
| `src/components/SmartMiniCart.tsx` | No remaining importers. The component was specific to the in-page cart-surface use case which is now gone. |

`src/components/Header.tsx` is **not** modified — audit confirmed
the existing top-header cart icon already meets D-2.5.5-1
requirements (see PART E).

---

## 2. PART A — final audit

Grep `View Cart|VIEW CART|CART \(|service in your cart|service added|ITEMS IN CART|item in cart|<SmartMiniCart` across `src/`:

| File | Pre-this-commit hits | Action | After |
|---|---|---|---|
| `src/components/SmartMiniCart.tsx` | Component definition (124 LoC) + 3 internal mentions | **File deleted** | — |
| `src/pages/Services.tsx:366` | `<SmartMiniCart setCurrentPage={setCurrentPage} />` | **Removed**; import line dropped | comment-only |
| `src/pages/ServiceCategory.tsx:1328` | `<SmartMiniCart setCurrentPage={setCurrentPage} />` | **Removed**; import line dropped | comment-only |
| `src/pages/ServiceDetail.tsx:889` | `<SmartMiniCart setCurrentPage={setCurrentPage} />` | **Removed**; import line dropped | comment-only |
| `src/pages/Services.tsx` (orig 226-233) | sub-nav `Cart (N)` link | already removed in `7e80e23` | — |
| `src/pages/ServiceCategory.tsx` (orig 619-626) | sub-nav `Cart (N)` link | already removed in `ef5d8a0` | — |
| `src/pages/ServiceCategory.tsx` (orig 880-900) | mid-page strip | already removed in `ef5d8a0` | — |
| `src/pages/Services.tsx` (orig 345-366) | mid-page floating strip | already removed in `ef5d8a0` | — |
| `src/pages/ServiceCategory.tsx` (orig 1340-1363) | bottom inline VIEW CART card | already removed in `ef5d8a0` | — |
| `src/pages/ServiceDetail.tsx` (orig 881-902) | bottom inline VIEW CART button | already removed in `ef5d8a0` | — |
| `src/components/Header.tsx:380-392` | top-header cart icon + count badge | **KEEP** (D-2.5.5-1) | — |
| `src/pages/ServiceCategory.tsx` service rows | "ADDED" status badge | **KEEP** (D-2.5.5-5; status indicator only) | — |

Post-commit grep returns:
```
$ grep -rn "<SmartMiniCart" src/
(no hits)

$ grep -rn "import SmartMiniCart" src/
(no hits)
```

Comment-only references like "the SmartMiniCart that briefly lived
here was removed per UX audit" remain in the page-level Phase
2.5.5 anchor comments — inert text documenting the history.

---

## 3. PART B — sub-nav cleanup status

No diff in this commit — both `ServiceCategory.tsx` and
`Services.tsx` had their sub-nav `Cart (N)` links removed in the
prior 2.5.5 commits. Final sub-nav contents:

- `/category/{slug}`: Overview · Pricing · Services · Process · Reviews · FAQs
- `/services`: dynamic category strip (Car Battery · Car Emergency · Car Insurance Claim · …) — populated from `apiCategories`, no CART tail link

`ServiceDetail.tsx` does not render a sub-nav (single-service detail page).

---

## 4. PART C — mid-page strip status

No diff in this commit — both mid-page strips were removed in
`ef5d8a0`:
- `ServiceCategory.tsx` lines 880-900 (between price-list and Services Included).
- `Services.tsx` lines 345-366 (after categories list).

Greps for `service in your cart` / `service added` return zero
matches in source files.

---

## 5. PART D — sidebar cart-card removal (the actual change)

### `Services.tsx`
```diff
            <aside className="order-1 lg:order-2 space-y-5">
-             {/* Phase 2.5.5 (D-2.5.5-3, D-2.5.5-6) — booking panel is
-                 PRIMARY (top of sidebar); SmartMiniCart is SECONDARY,
-                 rendered BELOW and conditional on cart non-empty. */}
+             {/* Phase 2.5.5 (final) — sidebar shows ONLY the booking
+                 panel (D-2.5.5-4). All cart-state UI in browse pages
+                 was consolidated to the global top-header cart icon;
+                 the SmartMiniCart that briefly lived here was removed
+                 per UX audit. */}
              <BookingSidebar
                titleStart="EXPERIENCE THE BEST"
                titleAccent="CAR SERVICES"
                titleEnd="IN"
                stickyTopPx={STICKY_OFFSET_PX}
              />
-             <SmartMiniCart setCurrentPage={setCurrentPage} />
            </aside>
```
Import removed:
```diff
 import PageBanner from "../components/PageBanner";
 import BookingSidebar from "../components/BookingSidebar";
-import SmartMiniCart from "../components/SmartMiniCart";
 import VehicleReplaceModal from "../components/VehicleReplaceModal";
```

### `ServiceCategory.tsx`
```diff
              </div>

-             {/* Phase 2.5.5 — SmartMiniCart sits HERE (D-2.5.5-3,
-                 D-2.5.5-6): below the primary Re-Check Prices card,
-                 above the trust-badges section. Conditional on cart
-                 non-empty. */}
-             <SmartMiniCart setCurrentPage={setCurrentPage} />
+             {/* Phase 2.5.5 (final) — sidebar shows ONLY the
+                 Re-Check Prices booking panel + trust badges
+                 (D-2.5.5-4). The SmartMiniCart that briefly lived
+                 between them was removed per UX audit; the global
+                 top-header cart icon is the single cart-access
+                 surface across browse pages. */}

              {/* TRUST BADGES */}
              <div className="bg-white p-5 sm:p-6 border border-border shadow-xl">
```
Import removed (same pattern).

### `ServiceDetail.tsx`
```diff
              </div>

-             {/* Phase 2.5.5 — SmartMiniCart sits HERE (D-2.5.5-3,
-                 D-2.5.5-6): below the primary booking context card,
-                 above the trust badges. Conditional on items > 0. */}
-             <SmartMiniCart setCurrentPage={setCurrentPage} />
+             {/* Phase 2.5.5 (final) — sidebar shows ONLY the booking
+                 context card + trust badges (D-2.5.5-4). The
+                 SmartMiniCart that briefly lived between them was
+                 removed per UX audit; cart access is the global
+                 top-header icon. */}

              {/* Trust badges */}
```
Import removed (same pattern).

### `src/components/SmartMiniCart.tsx`
File deleted. No remaining importers.

---

## 6. PART E — Header.tsx audit (no changes)

`src/components/Header.tsx` lines 380-392:

```tsx
{/* Cart icon with count badge */}
<button
  onClick={() => setCurrentPage("cart")}
  aria-label="View cart"
  className="relative flex items-center hover:opacity-80 transition-all"
>
  <ShoppingCart className="w-4 h-4" />
  {cartCount > 0 && (
    <span className="absolute -top-1.5 -right-2 bg-white text-primary text-[9px] font-black w-4 h-4 flex items-center justify-center leading-none">
      {cartCount > 9 ? "9+" : cartCount}
    </span>
  )}
</button>
```

Confirms:
- **Visible on every page** — sits in the always-rendered Header top bar, OUTSIDE the `FEATURES.auth ? null : ...` gate at line 218 that only affects the Login/Sign Up auth controls.
- **Count updates with cart state** — `cartCount` is destructured from `useCart()` at line 119; React Query keeps the cart cache fresh.
- **Click → `/cart`** — `setCurrentPage("cart")` is the page-router prop (which became `navigateTo` after Phase 2.5.2 → pushes URL via `history.pushState`).
- **Auth-state independent** — `cartCount` reflects guest carts (X-Cart-Session) AND user carts (Bearer); the icon shows for everyone.
- **9+ cap** — visual safety against double-digit overflow in the small `w-4 h-4` badge.

No code changes needed.

---

## 7. Final visual state

### `/services`
```
Top header:    [Logo] [nav]                            [🛒²] [⋯]
Sub-nav:       Car Battery · Car Emergency · … · Regular Service
                                              ↑ no CART tail link
Page body:     Categories list → Trust strip
                                              ↑ no mid-page strip
Right sidebar: ┌ BookingSidebar (only card) ┐
               └ Location / Car / Phone     ┘
```

### `/category/car-battery`
```
Top header:    [Logo] [nav]                            [🛒¹] [⋯]
Sub-nav:       Overview · Pricing · Services · Process · Reviews · FAQs
                                              ↑ no CART tail link
Page body:     Hero → Price List → Services Included → Process → Reviews → FAQs
                                              ↑ no mid-page strip
Service rows:  "Battery Charging         ₹1,650 [ADDED ✓]"
                                              ↑ status badge only
Right sidebar: ┌ Re-Check Prices (booking) ┐
               ├ Trust Badges              ┤
               └                           ┘
                                              ↑ no SmartMiniCart between
```

### `/services/{cat}/{sub}` (ServiceDetail)
```
Top header:    [Logo] [nav]                            [🛒¹] [⋯]
Page body:     Hero → Description → What's Included → …
                                              (no sub-nav on detail page)
Right sidebar: ┌ Booking Context Card  ┐
               ├ Trust Badges          ┤
               └                       ┘
                                              ↑ no SmartMiniCart between
```

### Empty cart state (any browse page)
```
Top header: [🛒]   ← icon only, no badge
Sub-nav:    section/category anchors only
Sidebar:    booking panel only
Page body:  no cart UI anywhere
```

---

## 8. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-B-sNDa1V.css  107.26 kB │ gzip:  17.55 kB
dist/assets/index-DrcZ5-lE.js   778.80 kB │ gzip: 204.72 kB
✓ built in 11.13s
```

JS bundle dropped 2.4 KB (gzip −0.6 KB) from the prior commit
(SmartMiniCart removal). Pre-existing >500 kB chunk warning
unchanged.

---

## 9. Commit

`fix(frontend): Phase 2.5.5 — cart entry-point consolidation. Remove ALL in-page cart access surfaces (sub-nav CART links, mid-page strips, right-sidebar cart cards) across /services, /category/{slug}, /services/{cat}/{sub}. Final state: single global cart access via top header cart icon + count badge. ADDED service-row badges retained as status indicators. UX audit outcome from operator design review.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 10. Deviations

- **Three Phase 2.5.5 commits in the history.** `ef5d8a0` placed
  `<SmartMiniCart>` above the booking panel; `7e80e23` flipped it
  below; this commit removes it entirely. Final design intent
  arrived only after observing the in-context UX, hence the
  iteration. The intermediate commits weren't reverted because
  they were on `main` and the audit trail is more useful than
  squashed history.
- **Comment text references SmartMiniCart in three pages.** The
  comment blocks left in place ("the SmartMiniCart that briefly
  lived here was removed per UX audit") explain *why* there's a
  blank between the booking card and the trust badges, useful for
  future readers. No live import, no JSX usage, no bundle size
  cost.
- **Header.tsx untouched.** The audit found the existing cart
  icon already satisfies all D-2.5.5-1 requirements. Refactoring
  it for cosmetic consistency would have been scope creep.
- **Service-row "ADDED" badge logic unchanged.** Lives in
  `ServiceCategory.tsx` — calls `isInCart({ kind:'service', ref_id, … })`
  from `useCart`. It's a status indicator (per D-2.5.5-5), not a
  cart-access link, so it stays.
- **`Cart.tsx` and `Checkout.tsx` pages untouched** per the hard
  constraint — those are destination pages, not browse pages, and
  fall outside the scope of this consolidation.
- **No tests added.** This is a removal-only change to existing
  pages; the surface that was tested (cart adds, totals, checkout)
  still works the same way. The deletion is structural, not
  behavioural.
