# Phase 2.3.5 — strict vehicle-only price display + ADDED hover parity (report)

Single-commit hotfix bundle from user testing on Phase 2.3.4.
Closes two issues: **(1)** ServiceCategory, ServiceDetail, and the
catch-all Services page all flickered between `base_price` and
the vehicle-specific price on initial mount and on back-navigation.
The product decision is now firm: **base_price never renders in the
UI**. Three render states only — no-vehicle (existing "Check Price"
UX), loading (skeleton), priced (vehicle-specific number), or
no-price ("Quote on Inspection"). **(2)** the ADDED state of the
Add-to-Cart button used `bg-white text-primary border-primary
hover:bg-primary/5` which made the hover effectively invisible
next to the BOOK NOW button's ink-sweep treatment. Both states now
use the existing `btn-ink` ink-sweep utility from `index.css` so
the buttons feel consistent. Backend untouched.

## Files modified

### Frontend
| File | Change |
|---|---|
| `src/pages/Services.tsx` | Added `vehicleSelected` and `pricingLoading` derivations from the existing `usePricingFor` query. The `<CategorySection>` prop renamed from `priceFor: (subId) => number \| undefined` to `priceStateFor: (subId) => PriceState` returning a discriminated union. Row's price column now branches strictly on the four states; `base_price` is never read. New `PriceState` type declared at module scope. |
| `src/pages/ServiceCategory.tsx` | Imports `usePricingFor` and runs a parallel POST `/pricing` keyed on `bookingCtx0.car` ids and the visible sub-service ids. The detailQuery (`/services/{slug}`) is still used for category/copy/seo, but per-row prices come from `priceMap` only. Row's price column branches on `pricingLoading`/`priceMap.has(sub.id)` — never `sub.price`. |
| `src/pages/ServiceDetail.tsx` | Reads `detailQuery.data.vehicle_price` directly (top-level field, set only when a priced row matched). Computes a local `PriceState` discriminated union; `priceDisplay`, the sidebar ESTIMATE block, the hero copy, and the FAQ answer all switch on it. `service.price` (which silently falls back to `base_price` server-side) is no longer trusted for any rendered text. The sidebar adds a "SELECT YOUR CAR" view for the no-vehicle state and a "QUOTE ON INSPECTION" view for the no-price state, alongside the existing skeleton + price states. |
| `src/pages/ServiceDetail.tsx` (button) | ADDED + ADD TO CART now use `btn-ink btn-ink-white` for the sidebar primary CTA — same ink-sweep BOOK NOW already uses, so hover behavior is uniform. Border-only flip distinguishes ADDED (`border border-primary`) from ADD TO CART (no border). |
| `src/pages/ServiceCategory.tsx` (button) | ADD TO CART → `btn-ink btn-ink-primary` (sweep to primary-dark on hover); ADDED → `btn-ink btn-ink-outline` (sweep fills primary, text turns white). Same dimensions retained (`px-4 py-2 text-[10px] … w-full sm:w-auto`). |
| `src/pages/Services.tsx` (button) | Same `btn-ink-primary` / `btn-ink-outline` toggle — applied verbatim across the three pages. |

No backend, no migration, no package install, no FEATURES change.

## PART A — strict vehicle-only price display

### Pre-fix grep (`grep -rn 'base_price\|sub.price\|service.price' src/pages/{Services,ServiceCategory,ServiceDetail}.tsx`)

```
ServiceCategory.tsx:736:  {sub.price ? `₹${sub.price}` : "Quote"}                 ← row price column
ServiceCategory.tsx:275:  price: Number(sub.price) || 0,                         ← addItem hint (legacy, harmless)
Services.tsx:442:        const displayPrice = vehiclePrice ?? sub.base_price ?? null;  ← FLICKER SOURCE
Services.tsx:150:        price: Number(sub.base_price) || 0,                     ← addItem hint
ServiceDetail.tsx:109:   const priceDisplay = service.price ? `Starting at ₹${service.price}` : "Get Custom Quote";
ServiceDetail.tsx:716:   {service.price ? `₹${service.price}` : "Get Quote"}    ← sidebar ESTIMATE
ServiceDetail.tsx:228:   `${service.title} starts at ₹${service.price}. …`     ← FAQ answer
ServiceDetail.tsx:117:   price: Number(service.price) || 0,                    ← addItem hint
```

### Post-fix grep

```
$ grep -n 'base_price\|sub.price\|service.price' src/pages/Services.tsx \
                                                  src/pages/ServiceCategory.tsx \
                                                  src/pages/ServiceDetail.tsx
ServiceCategory.tsx:97:  // without trusting `sub.price` (which the backend silently falls
ServiceCategory.tsx:98:  // back to base_price when no row matches). priceMap drives the
ServiceCategory.tsx:310: price: Number(sub.price) || 0,                          ← addItem hint, non-display
ServiceCategory.tsx:767: Never base_price. Vehicle-resolved or "Quote on        ← comment
Services.tsx:116:  // The /services list endpoint deliberately returns base_price only
Services.tsx:118:  // service id and never fall back to base_price for display.
Services.tsx:152: price: Number(sub.base_price) || 0,                            ← addItem hint, non-display
Services.tsx:389:  /** Phase 2.3.5 — strict 4-state price status; never base_price. */
Services.tsx:457:  // Phase 2.3.5 — strict 4-state machine. Never base_price.
ServiceDetail.tsx:109:  // NOT `service.price` (which silently falls back to base_price
ServiceDetail.tsx:260:  // We never render base_price here either.
ServiceDetail.tsx:749:  {/* Phase 2.3.5 — strict 4-state machine; never base_price. */}
```

Every remaining hit is a comment OR a legacy non-display
`addItem({ price: … })` hint that the server overwrites on
`POST /cart/items` (the cart re-snapshots from `service_prices`
authoritatively per Phase 2.3 §6.6). Display-time `base_price` is
zero-traced.

### Per-page state-machine summary

| Page | Source of truth | `loading` signal | "Check Price" | "Quote on Inspection" |
|---|---|---|---|---|
| `Services.tsx` | `usePricingFor` → `priceMap` | `pricingQuery.isFetching && data === undefined` | (existing `pricesShown=false` envelope, parent renders `Lock` Hidden) | row renders "Quote / On Inspection" |
| `ServiceCategory.tsx` | `usePricingFor` (parallel to `fetchCategoryDetail`) | same | (existing `showPrice=false` envelope) | row renders "Quote / On Inspection" |
| `ServiceDetail.tsx` | `detailQuery.data.vehicle_price` | `detailQuery.isLoading` | sidebar renders "SELECT YOUR CAR" header | sidebar renders "QUOTE ON INSPECTION" |

### Loading state UI

Skeleton bars match the existing skeleton vocabulary:
`h-5 w-16 bg-neutral-200 animate-pulse rounded` for the row price
column; `h-10 sm:h-12 w-32 bg-white/20 animate-pulse rounded` for
the ServiceDetail sidebar (translucent white on the primary-blue
sidebar background).

### Cache strategy

Already correct from earlier work — no code change in this
commit. `src/main.tsx` ships the QueryClient with
`defaultOptions.queries.staleTime: 5 * 60 * 1000`. All four
pricing-relevant queries (`useApiQuery`, `usePricingFor`,
`useCart`, `useApiQuery` for service detail) inherit this default.
React Query keys correctly include the vehicle ids:

- `usePricingFor`'s key: `["pricing", req]` where `req` includes
  `brand_id/model_id/fuel_type_id/service_ids[]` — vehicle change
  invalidates correctly.
- `useApiQuery` for ServiceDetail: `["service-detail", categorySlug, serviceSlug, carIds]`.
- `useApiQuery` for ServiceCategory detail: `["category-detail", categorySlug, carSlugs]`.

Within 5 min, back-navigation reuses the cached data, the row
renders the priced state immediately, no flicker.

## PART B — ADDED hover parity with BOOK NOW

### BOOK NOW class string (verbatim)
`btn-ink btn-ink-white w-full py-3.5 font-black uppercase tracking-tighter text-sm flex items-center justify-center gap-2`

### `btn-ink-white` definition (`src/index.css:76-81`)
```css
.btn-ink-white          { @apply bg-white text-primary; }
.btn-ink-white::before  { @apply bg-neutral-100; }    /* ink sweep on hover */
.btn-ink:hover          { transform: scale(1.03); }   /* shared scale */
.btn-ink:hover::before  { width: 100%; }              /* sweep fills */
```

### ADDED diff (per page)

| Page | Before (2.3.4) | After (2.3.5) |
|---|---|---|
| `ServiceDetail.tsx` (sidebar primary CTA) | `bg-white text-primary border border-primary hover:bg-primary/5` (invisible hover) | `btn-ink btn-ink-white w-full py-3.5 … border border-primary` *(border only when inCart)* |
| `ServiceCategory.tsx` (priced row) | `border bg-white text-primary border-primary hover:bg-primary/5` | `btn-ink btn-ink-outline px-4 py-2 …` *(hover paints primary, text white)* |
| `Services.tsx` (priced row) | same as ServiceCategory before | same as ServiceCategory after |

### ADD TO CART diff

| Page | Before (2.3.4) | After (2.3.5) |
|---|---|---|
| `ServiceDetail.tsx` | `bg-white text-primary border border-white hover:bg-white/90` | `btn-ink btn-ink-white …` (no border so it reads as the primary CTA) |
| `ServiceCategory.tsx` | `bg-primary text-white border border-primary hover:bg-primary-dark hover:border-primary-dark` | `btn-ink btn-ink-primary px-4 py-2 …` |
| `Services.tsx` | same | same |

The two states share the same `btn-ink` foundation so dimensions
and hover-scale are identical; only the variant class
(`btn-ink-primary` vs `btn-ink-outline` on row buttons,
`btn-ink-white` with optional inCart border on the sidebar)
distinguishes them. Box dimensions match because all `btn-ink-*`
variants apply only color rules — padding, width, font-size,
icon-gap stay on the call-site.

### Accessibility

- `aria-pressed={inCart}` retained on every toggle button.
- `btn-ink-outline:hover { @apply text-white }` flips text color
  when the primary fill sweeps in, preserving WCAG-AA contrast on
  hover. `btn-ink-white:hover` preserves primary-on-neutral-100
  which is also AA.

## Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2161 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-Bs7Co01j.css   104.96 kB │ gzip:  17.23 kB
dist/assets/index-CPsNeD6G.js    740.67 kB │ gzip: 196.10 kB
✓ built in 57.71s

$ # Vite dev restart
VITE v6.4.2  ready in 1622 ms
GET http://localhost:3000/  →  HTTP 200
```

## Single commit

`2b9e48e92b278dd77383183a7f683ab31d7d6938` — 4 files, 440 insertions, 81 deletions.
3 frontend files + 1 report file. Backend untouched.

## Deviations

1. **`ServiceCategory.tsx` adds a parallel POST `/pricing` query**
   even though `fetchCategoryDetail` already returns a `services[]`
   array with per-service `price` fields. The reason: `service.price`
   is silently `base_price` when no priced row matched (server-side
   fallback in `ServiceResource::toArray`), and the frontend has no
   way to tell "matched" from "fell back." Adding `usePricingFor`
   gives an explicit `matched_prices` set. Marginal extra request
   (one POST per category visit when a vehicle is selected); the
   visual correctness gain is worth it. A future backend tweak —
   exposing `vehicle_price`/`base_price` as separate fields on the
   list resource — would let us drop this. Out of scope here.

2. **The legacy `addItem({ price: … })` hint sites still read
   `sub.price` / `sub.base_price`** in the three Add-to-Cart
   handlers. These values never reach the server (the cart's
   AddCartItemRequest has no `price` field) and the server
   re-snapshots from `service_prices` on every POST. Threading the
   priceMap to those callsites would be code churn for zero
   semantic benefit. Documented inline.

3. **ServiceDetail's sidebar ADD TO CART (non-inCart state)
   inherits BOOK NOW's `btn-ink-white` base**, which means the
   two side-by-side buttons now look near-identical — both white
   on the primary-blue sidebar, both with the same ink sweep on
   hover. Distinguished by their labels (Add to Cart vs Book Now)
   and the icon (ShoppingCart vs ArrowRight). If this proves too
   subtle in user testing, we can flip ADD TO CART to
   `btn-ink-dark` (filled primary-dark). The brief explicitly
   asked for hover parity, not base-state distinction.

4. **`ServiceDetail.tsx` adds a "SELECT YOUR CAR" sidebar header
   for the no-vehicle state.** The brief mentioned routing the
   user to a picker; this implementation just shows a label since
   the BookingSidebar (the picker UI) renders elsewhere on the
   page. A future enhancement could focus-scroll the picker on
   click; for the launch fix, the label is enough.
