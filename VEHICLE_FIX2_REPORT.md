# REBUILD-VEHICLE-FIX2 — Remove silent auto-add; explicit "Add to cart"

The cart no longer fills itself on page load. The current detail-page service is
added **only** when the user clicks an explicit "Add to cart" CTA. Frontend only;
no backend, cart-persistence, coupon, checkout, selector, redirect, or price-reveal
changes.

Gate: `tsc --noEmit` clean (except the 2 pre-existing `brand-typography.spec.ts`
errors) **+** `vite build` exit 0 **+** `playwright --project=smoke` **3/3 passed**.

---

## 1. Auto-add removal

- **Deleted** `src/components/car-sidebar/useAutoAddService.ts` (the mount-time
  `useEffect` that silently added the current service when a vehicle was present).
- **`src/components/car-sidebar/CarSidebar.tsx`**: removed the `useAutoAddService`
  import and its call. The `currentService` prop is now used only to render an
  explicit CTA (below) — never to mutate the cart on mount.
- No other file used the hook (grep). The lone remaining mention was a stale
  comment in `ServiceCategory.tsx`, which was updated.

Result: landing on a service detail page performs **zero** cart mutations. With
cookies cleared + cart emptied + hard refresh, the cart is EMPTY on arrival.

## 2. Explicit "Add to cart" CTA (CarSidebar)

The visible right-column on ServiceDetail is the `CarSidebar` (the page's old
ESTIMATE/Add-to-Cart card lives inside a `hidden` aside kept for reference — it is
`display:none`, non-interactive, and never auto-added; left untouched). The explicit
add now lives in `CarSidebar`, driven by `currentService`:

- **Logic** (`onAddCurrent`, user-click only): reuses `useCart().addItem` with the
  same fields the old auto-add used — `serviceId`, `title`, `price` (resolved
  `vehiclePrice`, else `base_price`), `categorySlug`, `car` + `brand_id/model_id/
  fuel_id` from booking context, `location`. Removal reuses `removeItem`.
- **Gate (scenario 3):** if no vehicle, the CTA does **not** add — it opens the
  in-place `VehicleSelector` (`setSelectorOpen(true)`), and the empty-state copy
  reads "Select your car to add this service & see its price." Never adds a
  price-less service.
- **ADDED state (scenario 2):** in-cart is detected via
  `findCartItem({ ref_id, brand_id, model_id, fuel_id })`; the button shows
  `✓ Added` (with a 1.8 s `justAdded` flash bridging the cart refetch), else
  `Add to Cart`. Styling: `btn-ink-primary` (add) ↔ `btn-ink-outline` (added) — ACR
  Blue, matching the list rows.
- **Toggle-remove:** clicking `Added` removes the line (same as the list rows);
  the cart row's `×` also removes (scenario 5).

## 3. Consistency with Services/Category list rows

The list-row "ADD TO CART"/"ADDED" buttons on `Services.tsx` and
`ServiceCategory.tsx` call the same `useCart().addItem`/`removeItem` with the same
field shape. The new ServiceDetail CTA is identical in path and feedback, so adding
from a list row reflects in the CarSidebar cart, and the CTA shows `Added` for the
current service (scenario 4). One server-authoritative cart, one add path.

## 4. grep proof — no mount-time auto-add anywhere

```
$ grep -rn "useAutoAddService" src         # → none (only a comment in ServiceCategory, since fixed)
$ grep -rn "addItem(" src
src/components/car-sidebar/CarSidebar.tsx:94   → inside onAddCurrent()  (onClick)
src/pages/ServiceCategory.tsx:246              → inside handleAddToCart() (row onClick)
src/pages/ServiceDetail.tsx:200                → inside handleAddToCart() (hidden card onClick)
src/pages/Services.tsx:148                     → inside handleAddToCart() (row onClick)
```

All four `addItem` call sites are user-event handlers; none run in a `useEffect`/on
mount. Cart mutations are user-initiated only.

## 5. Before / after

- **Before:** fresh refresh on `/services/:cat/:svc` → `useAutoAddService` fired in a
  mount effect → the service was already in the cart, unasked.
- **After:** fresh refresh → cart EMPTY. The CarSidebar shows the vehicle/empty-state
  and an `Add to Cart` CTA (once a car is selected); the service enters the cart only
  on click → button flips to `✓ Added`.

(GUI screenshots aren't capturable here; runtime proof = smoke 3/3 green, including
"home renders without console errors". Operator to visually verify scenarios 1-6.)

## 6. tsc / build / smoke / backend

- `tsc --noEmit`: only the 2 pre-existing `tests/e2e/brand-typography.spec.ts`
  errors.
- `vite build`: exit 0 (index chunk 194.49 KB).
- `playwright test --project=smoke`: **3/3 passed** (live dev server).
- Backend untouched. Files changed: `CarSidebar.tsx` (remove hook + add CTA),
  deleted `useAutoAddService.ts`, comment fix in `ServiceCategory.tsx`. No new
  packages; no changes to cart persistence, coupon, checkout, selector, redirect,
  or price reveal.

### Deviation / note
The old ServiceDetail ESTIMATE card (with its own `Add to Cart` + `canBook =
booking.pricesShown` gate) still exists inside a `hidden` aside (`ServiceDetail.tsx`,
kept for reference since the rebuild). It is `display:none`, non-interactive, and has
no mount-time add — so it does not affect this fix. Removing that dead block is
optional future cleanup; left as-is to avoid touching unrelated ServiceDetail markup.
