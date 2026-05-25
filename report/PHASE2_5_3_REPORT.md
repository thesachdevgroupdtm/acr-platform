# Phase 2.5.3 — auth-hydration fix

Closes Issue 2 from Phase 2.5.2 user testing: hard-refresh on
auth-gated pages briefly rendered the "Login required" wall during
the useAuth bootstrap window before flipping to the authenticated
state, causing a ~5s flicker for already-logged-in users.

Commit: see "Commit" below.

---

## 1. Files modified

| Path | Why |
|---|---|
| `src/hooks/useAuth.ts` | Added 10s safety timeout to the bootstrap effect (D-2.5.3-3); `bootstrapped` was already exposed (line 491). |
| `src/components/Header.tsx` | Pattern D — small avatar/label pulse during `!bootstrapped` so the header doesn't flicker between Login/Sign Up and the user menu. |
| `src/pages/Cart.tsx` | Pattern B — "Login to Checkout" / "Proceed to Checkout" button rendered unconditionally; disabled with a 3-dot pulse during hydration. `handleCheckout` no-ops while `!bootstrapped`. |
| `src/pages/Checkout.tsx` | Pattern A — full-page `<CheckoutSkeleton>` during hydration (matches left form + right summary chrome) before deciding between auth-wall and form. |
| `src/pages/MyBookings.tsx` | Pattern A — `<MyBookingsSkeleton>` (sidebar + 3 card placeholders) during hydration before deciding between `<NotLoggedIn>` and the bookings list. |
| `src/pages/OrderDetail.tsx` | Replaced bare "Loading…" with `<OrderDetailSkeleton>` matching page chrome; gates on `!bootstrapped` in addition to React Query's `isLoading` so a stale 401 doesn't surface "We couldn't load that order" before auth resolves. |
| `PHASE2_5_3_REPORT.md` | This report. |

No backend touched. No new packages. No new hooks.

---

## 2. PART A — useAuth audit findings

| Question | Pre-2.5.3 state |
|---|---|
| Was `bootstrapped` exposed? | **Yes** (line 491 of useAuth.ts; doc-comment line 14 mentions it). The flicker existed because consumers branched on `!user` / `!isAuthenticated` directly without checking the flag — the API was complete; the wiring wasn't. |
| Token storage key | `acr_api_token_v1` (via `getToken`/`setToken` helpers in `src/lib/api.ts`). |
| Profile fetcher | `fetchProfile` from `src/lib/api.ts` → `GET /user/profile`. |
| 401 handler | **Yes**, two layers: `src/lib/api.ts` (line 190–192) calls `setToken(null)` on any 401 unless caller opts out; `useAuth.refreshFromServer` (line 213–218) repeats the clear and zeroes `user`. Both fire on the bootstrap-time profile fetch as well as any subsequent API call. |
| Was there a timeout on the bootstrap effect? | **No.** A hung profile fetch (network drop, sleeping VPN) would leave `bootstrapped=false` indefinitely. |

**Recommendation**: keep `bootstrapped` as the canonical flag, add the 10s timeout, and wire the gate into all consumers that branched on auth.

---

## 3. PART B — useAuth diff

### `bootstrapped` exposure
Already present — left as-is.

### Bootstrap effect — timeout added

```diff
   useEffect(() => {
-    refreshFromServer().finally(() => setBootstrapped(true));
+    let cancelled = false;
+    let bootstrappedLocal = false;
+
+    const timeoutId = window.setTimeout(() => {
+      if (cancelled || bootstrappedLocal) return;
+      bootstrappedLocal = true;
+      console.warn(
+        "[useAuth] Bootstrap timeout (10s); falling back to unauthenticated state.",
+      );
+      setToken(null);
+      setUser(null);
+      setBootstrapped(true);
+    }, 10_000);
+
+    refreshFromServer().finally(() => {
+      if (cancelled) return;
+      bootstrappedLocal = true;
+      window.clearTimeout(timeoutId);
+      setBootstrapped(true);
+    });
+
     const onTokenUpdate = () => { void refreshFromServer(); };
     window.addEventListener("acr-token-updated", onTokenUpdate);
     window.addEventListener(EVENT, onTokenUpdate);
     return () => {
+      cancelled = true;
+      window.clearTimeout(timeoutId);
       window.removeEventListener("acr-token-updated", onTokenUpdate);
       window.removeEventListener(EVENT, onTokenUpdate);
     };
   }, [refreshFromServer]);
```

`bootstrappedLocal` is the in-effect flag that prevents both the timeout-fallback and the `.finally()` from running each other's path twice. `cancelled` covers the React StrictMode double-mount case. The cleanup clears the timer so a fast unmount doesn't fire a stray `setBootstrapped` on a torn-down hook.

### 401 path
Untouched — already correct in `refreshFromServer` and `api.ts`. The new timeout doesn't interact with it: a 401 lands inside the try/catch first and resolves `bootstrapped=true` long before the 10s window.

---

## 4. PART C — per-consumer audit + fix table

| Component | Pre-2.5.3 branch | Post-2.5.3 branch | Skeleton type |
|---|---|---|---|
| `pages/MyBookings.tsx` | `!isAuthenticated \|\| !user → <NotLoggedIn>` | `!bootstrapped → <MyBookingsSkeleton>` `else !isAuthenticated → <NotLoggedIn>` `else <Authed>` | Sidebar (avatar + name + 2 stats) + 3 booking-card placeholders |
| `pages/Checkout.tsx` | `!isAuthenticated → <Login wall page>` | `!bootstrapped → <CheckoutSkeleton>` `else !isAuthenticated → <Login wall>` `else <Form>` | Two-column: left form (4 input pulses + 2 sub-section pulses) + right summary (vehicle banner + items + totals + button pulse) |
| `pages/OrderDetail.tsx` | `isLoading → "Loading…"` text | `(isLoading \|\| !bootstrapped) → <OrderDetailSkeleton>` | Header card + vehicle row + 3-item services list + 4 schedule rows + totals strip |
| `pages/Cart.tsx` | Button label switched on `isAuthenticated` directly; layout would flicker text on hydration | Button rendered unconditionally; `disabled` while `!bootstrapped`; 3-dot pulse label during hydration; "create account" sub-text gated on `bootstrapped && !isAuthenticated` so it doesn't briefly appear under a logged-in user's button | 3-dot inline pulse inside the existing button (no full-page skeleton — Cart is public) |
| `components/Header.tsx` | `!isAuthenticated → Login/Sign Up` `else → user menu` | Three-way: `!bootstrapped → small avatar pulse + label pulse` (busy indicator) | Tiny `w-5 h-5` pulse + `h-3 w-16` label pulse, sized to occupy exactly the space of the user menu trigger |

`pages/BookingsComingSoon.tsx` reads `isAuthenticated` but only renders when `FEATURES.bookingsList === false`; the flag is currently `true` so the page is unreachable and was left untouched (would otherwise need the same gate).

`components/BookingSidebar.tsx` and the Service{Category,Detail} pages branch on auth for cosmetic content (e.g. showing the "logged in as" line), not for content gating — they continue to render their unauthenticated branch during hydration without surfacing a wall, which is acceptable.

### Skeleton vocabulary

All four new skeletons use the same Tailwind primitive: `bg-neutral-200 animate-pulse` for primary placeholders and `bg-neutral-100 animate-pulse` for secondary lines, with `bg-white/30 animate-pulse` on the dark Header bar. No shared component (per D-2.5.3-2) — each page owns its skeleton inline so the chrome match is explicit.

---

## 5. PART D — 401 hardening

Already centralised. Two layers, no infrastructure to add:

1. **`src/lib/api.ts` line 190–192** — every non-2xx response with `status === 401` clears the token via `setToken(null)` (which dispatches `acr-token-updated` so the cart query also re-keys). Caller can opt out by passing `allowUnauthorized: true` (used by code that handles 401 explicitly, e.g. inline OTP retry).
2. **`useAuth.refreshFromServer`** — same 401 handling at the hook level so an `ApiError(401)` thrown by `fetchProfile` clears the token and zeroes `user`. The `acr-token-updated` listener inside the bootstrap effect re-fires `refreshFromServer` whenever any other code path calls `setToken(null)`, keeping the user state coherent.

A future Phase 2.6 hardening pass could add a centralised "logged-out toast" notification, but the data plumbing is already correct.

---

## 6. Build outputs

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2167 modules transformed.
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-CR9-7ESO.css  107.52 kB │ gzip:  17.59 kB
dist/assets/index-B0if9J-T.js   777.35 kB │ gzip: 204.36 kB
✓ built in 26.72s
```

Pre-existing >500 kB chunk warning unchanged.

---

## 7. Commit

`fix(frontend): Phase 2.5.3 — auth hydration. Expose bootstrapped flag from useAuth; 10s safety timeout for hung profile fetch; per-page skeletons during hydration window (MyBookings, OrderDetail, Checkout, Cart CTA, Header user menu); 401 token cleanup. Closes Issue 2 from 2.5.2 testing.`

(Hash printed by `git log -1 --oneline` after the commit lands.)

---

## 8. Deviations

- **`bootstrapped` was already exposed.** The audit found the flag had been on the public surface since Phase 2.1 — the flicker bug was purely a wiring issue at consumer sites. The "Expose `bootstrapped`" sub-task collapsed to a no-op; the timeout addition + per-consumer rewiring was the actual change.
- **Timeout-after-success no-op.** A successful `refreshFromServer` resolves `bootstrappedLocal=true` and clears the timer; the timer's callback also re-checks `bootstrappedLocal` defensively. Belt-and-braces in case the StrictMode double-effect interleaving ever produces a race.
- **OrderDetail gates on `!bootstrapped` even though it doesn't read `user`.** Reason: React Query fires the `/user/orders/{id}` request synchronously on mount using `getToken()` from localStorage. If the stored token is invalid, the request 401s before the user even sees the page; the api.ts 401 handler clears the token, but React Query has already settled into the error state. Gating the render on `bootstrapped` defers the user-visible decision until the auth handshake is over.
- **Cart skeleton is just an in-button pulse.** The Cart page is public (guests can browse / add items / see prices); only the bottom CTA's label depends on auth. Replacing the whole page with a skeleton during hydration would be over-correction. The 3-dot pulse keeps the button geometry identical between hydration and resolution so there's no layout shift.
- **`BookingsComingSoon.tsx` left untouched.** Currently unreachable (FEATURES.bookingsList=true). When a future commit dark-launches the bookings list off, this file will need the same gate; flagged for that future work but not added defensively today (would risk dead skeleton code).
- **No central "auth loading shell" component (D-2.5.3-2).** Each page owns its inline skeleton. Trade-off accepted: ~120 LoC of skeleton JSX vs. a tighter chrome match per page. The skeleton vocabulary (`bg-neutral-{100,200} animate-pulse`) is consistent so the visual feel is uniform without the abstraction.
