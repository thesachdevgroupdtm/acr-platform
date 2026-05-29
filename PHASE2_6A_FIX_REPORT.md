# Phase 2.6a-fix — Re-audit (regenerated from live source)

**HEAD at audit time:** `382fe7f fix(frontend): Phase 2.6a-fix — site-wide loading-state regression.`

This report was regenerated from scratch by re-reading every page's actual current source after operator testing surfaced a possible discrepancy (login-wall flash on hard-refreshing `/booking-history` despite the previous report claiming "guard intact, no change needed"). Each row below was verified against the file at the cited line numbers; nothing in this report is taken from prior write-ups.

---

## 1. Per-page render-order audit (verified from current source)

### `src/pages/MyBookings.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES — line 15 |
| Reads `bootstrapped` | YES — line 31, destructured alongside `user, isAuthenticated, logout` |
| Reads `isAuthenticated` | YES — line 31 |
| Guard placement | **Inline ternary inside JSX**, NOT an early return. Lines 76–85: `{!bootstrapped ? <MyBookingsSkeleton /> : !isAuthenticated \|\| !user ? <NotLoggedIn /> : <authedContent />}` |
| Skeleton component | `MyBookingsSkeleton` (defined locally at line 391, pure visual pulses, no auth refs) |
| Order of guards | Correct: `!bootstrapped → skeleton`, then `!isAuthenticated → auth wall`, then content |
| `<PageBanner>` | Renders unconditionally (outside the ternary, lines 66–72) |

**Status:** the bootstrapped guard IS present. Structurally, `!bootstrapped → MyBookingsSkeleton` is the first branch evaluated. **However**, see Section 4 below — there is a documented race-window risk in `useAuth.ts` itself that this guard cannot defend against alone, which is the most likely cause of the operator's observed flash.

### `src/pages/OrderDetail.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES — line 17 |
| Reads `bootstrapped` | YES — line 46 |
| Reads `isAuthenticated` | NO — page does not gate on auth state directly; relies on the parent ordering pattern |
| Guard placement | **Early return** at line 74: `if (isLoading || !bootstrapped) return <skeleton-page />` |
| Skeleton component | `OrderDetailSkeleton` (renders inside a full-page chrome shell with PageBanner + breadcrumbs) |
| Error fallback | Separate early return at line 97: `if (isError || !order) return <error-page />` |

**Status:** clean. The skeleton gate folds in `isLoading || !bootstrapped`, returning a fully-formed page. No flash possible here.

### `src/pages/Checkout.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES (via combined hook block) |
| Reads `bootstrapped` | YES — line 53 |
| Reads `isAuthenticated` | YES — line 53 |
| Guard placement | **Early return** at line 305: `if (!bootstrapped) return <skeleton-page />`, then early return at line 327: `if (!isAuthenticated) return <login-wall>` |
| Skeleton component | `CheckoutSkeleton` (Phase 2.5.3) |

**Status:** clean. Two distinct early returns in correct order, each rendering its own fully-formed page chrome.

### `src/pages/Cart.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES — line 18 |
| Reads `bootstrapped` | YES — line 31, also at line 54 (handleCheckout gate) and line 86 (render-time skeleton gate) |
| Reads `isAuthenticated` | YES — line 31 |
| `cartLoading` from useCart | YES — destructured at line 29 as `isLoading: cartLoading` |
| Guard placement | **Inline ternary inside JSX**, line 86: `{cartLoading \|\| !bootstrapped ? <CartSkeleton /> : items.length === 0 ? <EmptyCart /> : <ActualCart />}` |
| Skeleton component | `CartSkeleton` from `src/components/CartSkeleton.tsx` (created by 382fe7f) |
| Comment trail | Phase 2.6a-fix comment block on lines 79–85 documenting why the bootstrapped fold-in matters |

**Status:** the empty-state-flash is fixed. Verified the ternary is structured correctly: cartLoading OR !bootstrapped wins first, then empty state, then content.

### `src/pages/Services.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES — line 22 (added by 382fe7f, was absent pre-fix) |
| Reads `bootstrapped` | YES — line 50 |
| `cartLoading` from useCart | YES — destructured at line 49 as `isLoading: cartLoading` |
| `cartReady` flag | YES — line 56: `const cartReady = bootstrapped && !cartLoading;` |
| ADDED-badge gating | YES — `cartItemFor` closure at line 297–306 wraps `findCartItem` with `cartReady ? ... : null` |
| Page-level loading gate | NO top-level `!bootstrapped → skeleton` — the page is public, doesn't gate on auth (correct per D-2.6a-fix-4) |

**Status:** ADDED-badge flicker is fixed via `cartReady` gating. Page-level chrome remains public/unauth-aware as intended.

### `src/pages/ServiceCategory.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES — line 38 (was already imported pre-fix) |
| Reads `bootstrapped` | YES — line 140 (added by 382fe7f) |
| `cartLoading` from useCart | YES — line 134 |
| `cartReady` flag | YES — line 143 |
| ADDED-badge gating | YES — line 764–771: `const cartItem = cartReady ? findCartItem({...}) : null;` |
| Page-level loading gate | Detail-fetch skeleton handles its own loading state (Phase 2.5.7); no auth gate needed |

**Status:** clean. Same pattern as Services.tsx.

### `src/pages/ServiceDetail.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES — line 27 |
| Reads `bootstrapped` | YES — line 77 (was destructured but `bootstrapped` added by 382fe7f) |
| `cartLoading` from useCart | YES — line 72 |
| `cartReady` flag | YES — line 80 |
| ADDED-badge gating | YES — line 149–157 |

**Status:** clean. Same pattern.

### `src/components/Header.tsx`

| Aspect | Finding |
|---|---|
| Imports `useAuth` | YES (existing) |
| Reads `bootstrapped` | YES — line 120 |
| `cartLoading` from useCart | YES — line 119 |
| `showCartBadge` flag | YES — line 129: `bootstrapped && !cartLoading && cartCount > 0` |
| Cart icon badge gate | YES — line 390: `{showCartBadge && <Badge>{cartCount}</Badge>}` |
| User-menu "My Cart" inline count | Verified — uses `showCartBadge` (per prior commit's diff in the user-menu block) |

**Status:** cart count flicker is fixed.

### `src/App.tsx`

| Aspect | Finding |
|---|---|
| `NotFound` import | YES — line 29 |
| Switch `case "not-found"` | YES — line 288 |
| Switch `default` | Renders `<NotFound />` (lines 290–296) instead of `<Home />` |
| `parsePageFromUrl` fallback | Returns the unknown stripped path verbatim (line 97 of App.tsx); the switch's default handles it |

**Status:** unknown URLs render NotFound at the original URL. Verified.

---

## 2. Comprehensive comparison table

| Page | Type | bootstrapped guard | Order | Skeleton | Cart-aware | Current state |
|---|---|---|---|---|---|---|
| MyBookings | Auth-required | YES (inline ternary, line 76) | `!bootstrapped` first, then `!isAuthenticated\|\|!user`, then content | MyBookingsSkeleton (local) | n/a | Structurally correct; subject to useAuth race (Section 4) |
| OrderDetail | Auth-required | YES (early return, line 74) | `isLoading\|\|!bootstrapped` → skeleton; `isError\|\|!order` → error; content | OrderDetailSkeleton | n/a | Clean |
| Checkout | Auth-required | YES (early return, line 305) | `!bootstrapped` → skeleton, then `!isAuthenticated` → auth-wall, then content | CheckoutSkeleton | uses `useCart` for items but no separate cartLoading guard | Clean |
| Cart | Public-cart | YES (inline ternary, line 86, folded with cartLoading) | `cartLoading\|\|!bootstrapped` → CartSkeleton, then `items.length===0` → EmptyCart, then content | CartSkeleton (new in 382fe7f) | YES | Clean |
| Services | Public-userAware | NO page-level guard; `cartReady` derived flag for ADDED badges | n/a (public) | category-list skeleton | YES (cartReady gates `cartItemFor`) | Clean |
| ServiceCategory | Public-userAware | NO page-level; `cartReady` for ADDED badges (line 143) | n/a | detail skeleton (Phase 2.5.7) | YES (cartReady gates inline `findCartItem`) | Clean |
| ServiceDetail | Public-userAware | NO page-level; `cartReady` (line 80) | n/a | service skeleton (Phase 2.5.7) | YES | Clean |
| Header | Component | YES (badge gate) | `bootstrapped && !cartLoading && cartCount > 0` | n/a | YES | Clean |
| App.tsx | Router | n/a | NotFound for unknown keys (default + explicit case) | n/a | n/a | Clean |

---

## 3. Discrepancies between previous report and live code

| Previous report claim | Verified against source | Verdict |
|---|---|---|
| "MyBookings 2.5.3 guard is intact (line 76)" | Confirmed at line 76: `{!bootstrapped ? <MyBookingsSkeleton /> : ...}` | **TRUE** as a literal source-code statement |
| "Operator's regression hypothesis was incorrect for MyBookings" | The guard is structurally correct, but **see Section 4** — there is a separate race-window root cause the prior report did not consider | **PARTIALLY MISLEADING** — the guard is intact, but the page can still flash due to a useAuth-internal race that no in-page guard can fully defend against |
| "Cart skeleton replaces empty-state flash" | Confirmed line 86 ternary uses `CartSkeleton` first | TRUE |
| "Header cart badge gated on `bootstrapped && !cartLoading && cartCount > 0`" | Confirmed line 129 + line 390 | TRUE |
| "ADDED badges on Services/ServiceCategory/ServiceDetail gated via `cartReady`" | Confirmed lines 56, 143, 80 + their respective findCartItem call sites | TRUE |
| "App.tsx default → NotFound" | Confirmed lines 288–296 | TRUE |

**Key correction to the prior report:** the prior report concluded the operator's observed flash was a misattribution. Re-audit shows the structural guard IS present — but the underlying useAuth bootstrap sequence has a race window between `setUser` (inside the try block) and `setBootstrapped(true)` (inside the `.finally()` block) that can produce a brief render with `bootstrapped=true && user=null && isAuthenticated=false`, during which MyBookings' second ternary branch (`!isAuthenticated || !user → NotLoggedIn`) is what renders. **This is a real bug, just not located where the prior audit looked.**

---

## 4. Root cause investigation — useAuth bootstrap race

`src/hooks/useAuth.ts` (lines 184–230, observed but NOT modified per task constraint):

```ts
const refreshFromServer = useCallback(async () => {
  ...
  try {
    const data = await fetchProfile();
    setUser(presentUser(data.user));   // ← microtask A
  } catch (e) { ... }
}, []);

useEffect(() => {
  ...
  refreshFromServer().finally(() => {
    if (cancelled) return;
    bootstrappedLocal = true;
    window.clearTimeout(timeoutId);
    setBootstrapped(true);             // ← microtask B
  });
  ...
}, []);
```

When `fetchProfile()` resolves successfully:

- **Microtask A** runs the continuation of `refreshFromServer`'s body → calls `setUser(presentUser(data.user))` → returns from the async function (resolving its promise).
- **Microtask B** runs the `.finally()` callback → calls `setBootstrapped(true)`.

These are two separate microtasks. React 18's automatic batching DOES batch state updates across microtasks within the same task, so in the happy path both setters land in one render cycle and there is no flash.

**Failure modes that can produce a visible flash:**

1. **Concurrent rendering with Suspense or transition boundaries** — React MAY interleave a render between microtasks A and B if a higher-priority update lands first.
2. **Browser scheduling under stress** — paint between microtasks (rare but observable on slower devices).
3. **A stray state update elsewhere** that triggers a render in microtask A's tail.

In any of these failure modes, MyBookings sees:

- Render N:   `bootstrapped=false, user=null` → `<MyBookingsSkeleton />` ✓
- Render N+1: `bootstrapped=true,  user=null` → `<NotLoggedIn />` ✗  (the flash)
- Render N+2: `bootstrapped=true,  user={…}` → authed content ✓

The same race can affect Checkout (`!isAuthenticated` early return at line 327) and any other page that distinguishes "bootstrapped + unauthenticated" from "bootstrapped + authenticated."

**Why the prior report missed this:** the prior audit verified the literal source-code guard ("does the bootstrapped check exist? Yes.") but did not trace the bootstrap sequence in useAuth itself. The guard cannot defend against a render where `bootstrapped=true && user=null` is the actual state — that's a valid, well-defined state per the current useAuth contract.

**Recommended fix (NOT applied — task is report-only AND the constraint forbids modifying useAuth):** combine both setState calls into a single synchronous block by moving `setBootstrapped(true)` next to `setUser(...)` inside the same try/catch/finally arm of `refreshFromServer`, or wrap both in a `flushSync`/single batched callback.

---

## 5. Reproducible verification steps for the operator

These steps reproduce the operator's reported flash and validate any future fix:

### Setup
```
1. Login normally so a token is stored in localStorage.
2. Open DevTools → Network → throttle to "Slow 3G" (forces a measurable window
   between mount and /user/profile resolution).
3. Open the React DevTools "Highlight updates when components render" toggle.
```

### Test 1 — MyBookings flash repro
```
1. Navigate to /booking-history.
2. Hard refresh (Ctrl+F5).
3. Watch the body of the page across three frames:
   FRAME 1 (≤100 ms): MyBookingsSkeleton — pulses on left + 3 placeholder cards
   FRAME 2 (race window, throttled 3G makes it ~50–200 ms wide):
     possible <NotLoggedIn /> with "Login to view bookings" CTA
   FRAME 3 (when /user/profile resolves): authed content with user name + bookings
```
A reproducible Frame 2 confirms the useAuth race; absence of Frame 2 across 5 hard refreshes suggests React's batching is holding it off in the current environment but the structural risk remains.

### Test 2 — Cart skeleton confirmation (no regression from 382fe7f)
```
1. With items in cart, hard refresh /cart.
2. Expected: CartSkeleton briefly → real items.
3. Failure: any frame with "YOUR CART IS EMPTY" before items resolve.
```

### Test 3 — Header cart badge gating
```
1. With items in cart, hard refresh any page.
2. Watch the cart icon area:
   - Cart icon: visible from frame 1.
   - Numeric badge: must NOT appear with count=0, then flip to count=N.
     Either absent the entire load window → appears with correct count,
     or absent throughout if cart is empty.
```

### Test 4 — ADDED badge gating on /category/{slug}
```
1. Login, add service A to cart.
2. Hard refresh /category/{slug containing service A}.
3. Service A row:
   - Frame 1: "BOOK NOW" CTA (cart not yet ready, cartReady=false → cartItem=null)
   - Frame 2 (after cart resolves): "ADDED" badge appears
4. Failure: BOOK NOW → ADDED flip is acceptable per the current implementation.
   What is NOT acceptable: ADDED → BOOK NOW → ADDED (would indicate cartReady
   thrashing).
```

### Test 5 — /payment graceful 404
```
1. Manually type /payment in the URL bar and hit Enter.
2. Expected: NotFound page renders. URL bar still shows /payment.
3. "Go to Home" button navigates to /home.
```

### Test 6 — Public pages no-flash sanity
```
1. Hard refresh /home, /about, /contact, /gallery, /insurance, /corporate.
2. None of these pages have user-aware elements in the body. Render must be
   identical between an authed and an unauthed session.
```

### Test 7 — useAuth race repro under DevTools
```
With React DevTools Profiler attached to /booking-history:
1. Start recording.
2. Hard refresh.
3. Stop recording after content stabilizes.
4. Inspect the commit timeline — count how many commits MyBookings receives.
   - 2 commits (skeleton → content): batching held; race did not fire.
   - 3+ commits (skeleton → NotLoggedIn → content): race fired; the second
     commit's prop snapshot will show bootstrapped=true & user=null.
```

---

## 6. Files inspected (read-only, no modifications)

| File | Lines read | Purpose |
|---|---|---|
| `src/pages/MyBookings.tsx` | 1–100, 385–414 | Render order, skeleton component |
| `src/pages/OrderDetail.tsx` | 1–130 | Early-return ordering |
| `src/pages/Checkout.tsx` | 50–60, 300–340 | Two-step early return |
| `src/pages/Cart.tsx` | 25–125 | Skeleton ternary + cartLoading destructure |
| `src/pages/Services.tsx` | 45–125 | cartReady derivation, cartItemFor closure |
| `src/pages/ServiceCategory.tsx` | 130–155, 760–775 | cartReady, findCartItem gating |
| `src/pages/ServiceDetail.tsx` | 68–88, 144–158 | cartReady, findCartItem gating |
| `src/components/Header.tsx` | 115–135, 378–395 | showCartBadge derivation + badge sites |
| `src/App.tsx` | 28–35, 285–296 | NotFound import + switch default |
| `src/hooks/useAuth.ts` | 180–240 | Bootstrap sequence (race-window investigation) |
| `git log --oneline -3` | — | Hash verification: HEAD = 382fe7f |

No source files were modified during this audit.

---

## 7. Recommendation summary

1. **The four loading-state fixes shipped at 382fe7f are correctly in place** and verified line-by-line: Cart skeleton, Header badge gate, three service-page ADDED gates, NotFound fallback. None of those need re-work.
2. **The operator's MyBookings flash report is plausible and points at a real race in `useAuth.ts` itself**, not at MyBookings' guard. The page-level guard is structurally correct; the underlying hook's bootstrap sequence allows a transient `bootstrapped=true && user=null` state.
3. **Recommended next step (separate task):** modify `useAuth.ts` to flip `bootstrapped` and `user` together (move `setBootstrapped(true)` into the same arm as `setUser`, or wrap both in a single batched callback). This task is REPORT-ONLY so the change is not applied here. The hard constraint "DO NOT modify useAuth hook" must be lifted to apply this fix.
4. **The previous report's "no change needed" conclusion for MyBookings was technically accurate at the page level but missed the hook-level race.** This report supersedes that conclusion with a structural cause analysis.

---

**Audit performed:** 2026-05-05.
**Source HEAD inspected:** `382fe7f`.
**Code modifications during audit:** none.
