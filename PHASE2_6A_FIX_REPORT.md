# Phase 2.6a-fix — Site-wide loading-state regression

## Files modified

| File | Change |
|---|---|
| `src/pages/Cart.tsx` | Gate empty-state behind `cartLoading \|\| !bootstrapped`; render `<CartSkeleton />` during the indeterminate window. |
| `src/components/CartSkeleton.tsx` | **New.** 3 placeholder item rows + summary card + coupon block, matching Cart chrome. |
| `src/components/Header.tsx` | Cart count badge now gated on `bootstrapped && !cartLoading && cartCount > 0` (both the cart icon badge and the user-menu "My Cart" inline count). |
| `src/pages/Services.tsx` | Adds `cartReady` flag; wraps `cartItemFor` closure so ADDED-badge derivation is suppressed until cart query lands. |
| `src/pages/ServiceCategory.tsx` | Same `cartReady` gate around the inline `findCartItem` call inside the row map. |
| `src/pages/ServiceDetail.tsx` | Same `cartReady` gate around the page-level `findCartItem` call. |
| `src/pages/NotFound.tsx` | **New.** Themed 404 page with "Go to Home" CTA. |
| `src/App.tsx` | Imports `NotFound`; switch's `default` branch now renders it instead of `<Home />`; explicit `case "not-found"` for direct navigation. |

## PART A — site-wide audit findings

| Page | Type | bootstrapped guard | Skeleton | Pre-fix issue |
|---|---|---|---|---|
| `MyBookings.tsx` | Auth-required | **YES** (inline ternary at line 76) | `MyBookingsSkeleton` | OK — 2.5.3 fix is intact (the operator's hypothesis of regression here was incorrect). |
| `Checkout.tsx` | Auth-required | **YES** (early return at 305) | `CheckoutSkeleton` | OK. |
| `OrderDetail.tsx` | Auth-required | **YES** (early return at 74, combined with `isLoading`) | inline | OK. |
| `Cart.tsx` | Public-cart | **NO** top-level guard | none | **Empty-state flash** — `items.length === 0` evaluated before cart query resolved → "YOUR CART IS EMPTY" rendered for 1–2s on hard refresh. |
| `Header.tsx` | Public-userAware | partial (auth UI gates on bootstrapped at 212) | n/a | **Cart count badge 0→N flicker** — `cartCount > 0` was false during cart load, badge appeared abruptly on resolve. |
| `Services.tsx` | Public-userAware | **NO** | category-list skeleton already exists | **ADDED badge flicker** on hard refresh — `findCartItem` returned null while cart pending, then flipped to ADDED. |
| `ServiceCategory.tsx` | Public-userAware | **NO** for ADDED rows | detail skeleton from 2.5.7 | Same ADDED-badge flicker. |
| `ServiceDetail.tsx` | Public-userAware | **NO** for ADDED button | service skeleton from 2.5.7 | Same ADDED-badge flicker on the BOOK NOW / ADDED toggle. |
| `Home.tsx`, `About.tsx`, `Contact.tsx`, `Gallery.tsx`, `Insurance.tsx`, `Corporate.tsx` | Purely public | n/a | n/a | OK — no user-aware elements rendered in body. |

**Audit conclusion:** the operator's regression hypothesis identified the symptom correctly (Cart empty-state flash) but mis-attributed it to MyBookings. MyBookings still has the 2.5.3 guard. The actual gaps are Cart, Header badge, and ADDED badges on the three service pages — none of which had ever been gated. Phase 2.6a didn't regress these; it surfaced a pre-existing inconsistency by deleting the FEATURES gates that had been masking some of the indeterminate windows.

## PART B — restored / added bootstrapped checks

### Cart.tsx (the empty-state flash, the headline bug)
```tsx
// before:
{items.length === 0 ? <EmptyCart ... /> : <ActualCart .../>}

// after:
{cartLoading || !bootstrapped
  ? <CartSkeleton />
  : items.length === 0
    ? <EmptyCart ... />
    : <ActualCart ... />}
```
The bootstrapped fold-in is deliberate (D-2.6a-fix-5): the bearer token is wired into `addItem` calls only after auth hydrates, so a cart fetch that resolves before bootstrap may be against the wrong owner. Treating the unbootstrapped window as still-loading is correct.

### MyBookings.tsx, Checkout.tsx, OrderDetail.tsx
**No change.** Audit confirmed the 2.5.3 guards are intact and in correct order. Verified per page.

## PART C — skeleton inventory

| Page | Skeleton | Status |
|---|---|---|
| MyBookings | `MyBookingsSkeleton` (2.5.3) | unchanged, verified |
| OrderDetail | inline (2.5.3) | unchanged |
| Checkout | `CheckoutSkeleton` (2.5.3) | unchanged |
| Cart | `CartSkeleton` | **NEW** — 3 line-item rows + summary card + coupon block |
| Services | category-list skeleton | unchanged (already vehicle-agnostic) |
| ServiceCategory | detail skeleton (2.5.7) | unchanged |
| ServiceDetail | service skeleton (2.5.7) | unchanged |

## PART D — Header cart count badge gating

```tsx
// before:
const { count: cartCount } = useCart();
...
{cartCount > 0 && <Badge>{cartCount}</Badge>}

// after:
const { count: cartCount, isLoading: cartLoading } = useCart();
const { bootstrapped } = useAuth();
const showCartBadge = bootstrapped && !cartLoading && cartCount > 0;
...
{showCartBadge && <Badge>{cartCount}</Badge>}
```
Both badge sites updated — the cart icon's `-top-1.5 -right-2` numeric badge AND the user-menu's "My Cart" inline count. Cart icon itself stays mounted always (D-2.5.5-1 preserved).

## PART E — NotFound page

`src/App.tsx` — switch default branch:
```tsx
case "not-found":
  return <NotFound setCurrentPage={navigateTo} />;
default:
  return <NotFound setCurrentPage={navigateTo} />;
```
`parsePageFromUrl` is unchanged — already returns the unknown stripped path verbatim, which then falls through the switch's default to NotFound. So `/payment` → currentPage="payment" → default → NotFound, with the URL bar staying at `/payment` for honesty.

`NotFound.tsx` — simple themed card with breadcrumbs, Compass icon, "Go to Home" primary CTA. Matches the site's PageBanner + site-container vocabulary so it doesn't feel grafted on.

## Verify

```
$ npx tsc --noEmit                 → exit 0
$ npm run build                    → ✓ built in 28.18s
                                     dist/index.html               0.42 kB
                                     dist/assets/index-*.css     107.10 kB
                                     dist/assets/index-*.js      764.25 kB
```

## Browser test plan (operator runs)

A. Cart loading-state — login + add 2 items + hard-refresh `/cart` → CartSkeleton briefly → real cart. **Never** see "YOUR CART IS EMPTY".

B. MyBookings — already passing pre-fix; re-verify after this commit doesn't disturb.

C. Header cart count — add items, hard-refresh any page → cart icon visible always; badge appears ONCE with correct count, no 0→N flicker.

D. ADDED badges — login + add service A + hard-refresh `/category/{slug}` → row A: NO badge initially, then ADDED appears once cart loads. No "BOOK NOW → ADDED" flip.

E. Public pages — `/about`, `/contact`, `/gallery`, `/insurance`, `/corporate` — same render every time, no flicker.

F. `/payment` → NotFound page with "Go to Home" button; URL stays at `/payment`. Same for typos like `/bookings-history` (note: `/booking-history` is an explicit alias and resolves to my-bookings).

G. Cross-tab — `/cart` (logged in, items) in tab 1, `/booking-history` in tab 2 — both load with skeletons → real content.

## Single commit

(see commit message in this branch's HEAD)

## Deviations

- The operator's hypothesis attributed the regression to a removed `if (!bootstrapped) return <Skeleton/>` guard in MyBookings during 2.6a. **Audit found this guard intact.** The real bugs are the four pre-existing gaps (Cart empty-state, Header badge, three service-row badges) plus the NotFound fallback. Recommendation accepted as the canonical loading-state pattern (D-2.6a-fix-1) but framed as "establish convention" rather than "restore" for accuracy.
- `cartItemFor` in Services.tsx returns `null` when cart isn't ready (instead of a placeholder skeleton on the badge area). The button column simply renders BOOK NOW until ready — which is the same render an unauthenticated user sees, so it stays consistent.
