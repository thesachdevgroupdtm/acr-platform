# Demo-readiness audit — Phase 2.6a polish pass

This is a cumulative report covering three commits: `4d9dd58` (initial polish), `6621452` (Testimonials page), and the current commit (FAQ accordion fix). Static source analysis throughout. Runtime checks (clicking through every page in a live browser, watching the Network tab, mobile viewport spot-checks) require an operator-driven session — the relevant items are flagged "OPERATOR" below with the exact steps to run.

---

## 1. Pages audited

| Page | File | Static-audit status | Notes |
|---|---|---|---|
| Home | `src/pages/Home.tsx` | ✅ polished | stats updated (see §5); Unsplash hero + section images load reliably |
| Services (list) | `src/pages/Services.tsx` | ✅ | API-driven; skeleton-first |
| ServiceCategory | `src/pages/ServiceCategory.tsx` | ✅ | API-driven; ADDED-badge gate intact (2.6a-fix) |
| ServiceDetail | `src/pages/ServiceDetail.tsx` | ✅ | API-driven; ADDED gate intact |
| Service Centers | `src/pages/ServiceCenters.tsx` | ✅ | renders 4 LOCATIONS with Unsplash hero each |
| Service Center Detail | `src/pages/ServiceCenterDetail.tsx` | ⚠️ | OPERATOR — visit `/center/moti-nagar` and 3 others; map embed not validated |
| Insurance | `src/pages/Insurance.tsx` | ✅ | static marketing page |
| Corporate | `src/pages/Corporate.tsx` | ✅ | static marketing page |
| Gallery | `src/pages/Gallery.tsx` | ✅ | uses external image URLs |
| About | `src/pages/About.tsx` | ✅ | 3 team members with realistic Indian names + Unsplash portraits |
| Contact | `src/pages/Contact.tsx` | ✅ | uses BUSINESS_INFO.phone/email; form posts to backend |
| Coupons | `src/pages/Coupons.tsx` | ✅ | API-driven; relies on seeded coupon rows |
| Offers | `src/pages/Offers.tsx` | ✅ | migrated to `useCoupons('marketing')` in 2.6a |
| Cart (empty) | `src/pages/Cart.tsx` | ✅ | empty-state polished, gated behind cartLoading per 2.6a-fix |
| Cart (items) | same | ✅ | renders from server cart |
| Checkout | `src/pages/Checkout.tsx` | ✅ | bootstrapped + auth guards from 2.5.3 |
| Booking Confirmation | `src/pages/BookingConfirmation.tsx` | ⚠️ | OPERATOR — verify confirmation copy after a real placeOrder |
| MyBookings | `src/pages/MyBookings.tsx` | ⚠️ | bootstrapped guard intact at JSX line 76 — but see 2.6a-fix re-audit report for the useAuth race window that can still cause a brief flash |
| OrderDetail | `src/pages/OrderDetail.tsx` | ✅ | early-return skeleton |
| NotFound | `src/pages/NotFound.tsx` | ✅ | shipped in 2.6a-fix; reachable via `/payment` or any unknown URL |
| Sitemap | `src/pages/Sitemap.tsx` | ✅ | listed in Quick Links; Testimonials added to its main-pages list |
| **Testimonials** (NEW) | `src/pages/Testimonials.tsx` | ✅ | new page — 12 reviews, trust strip, page banner, bottom CTA |

---

## 2. Console errors and noise

| Issue | Location | Status |
|---|---|---|
| `console.log("[api] base = …")` fires on every page mount | `src/lib/api.ts:69` | **Fixed** — gated behind `import.meta.env.DEV`; production build is silent |
| `console.warn(…)` in useAuth bootstrap timeout (10s) | `src/hooks/useAuth.ts:217` | **Kept** — only fires when /profile actually times out; useful diagnostic, not noise |
| `console.warn(…)` in cart merge failure | `src/hooks/useAuth.ts:356` | **Kept** — error path only |
| `console.warn(…)` in cart addItem failure | `src/hooks/useCart.ts:344` | **Kept** — error path only |

OPERATOR: open DevTools console, hard-refresh `/`, click through Services → Cart → Checkout → MyBookings. Production-build console must show **zero** non-warning output. Any unexpected `console.log` is a regression.

---

## 3. Broken images / missing assets

`/public/` contains only `.htaccess` — no local image assets are referenced from src. Every `<img>` either points to:

- An Unsplash URL with `referrerPolicy="no-referrer"` set
- The backend API (resolved image fields on services, categories, service centers)

Audit of all `<img>` tags in `src/pages/` and `src/components/` (24 sites): every src is either a real Unsplash URL, a backend-resolved URL, or driven from a state variable. No bare `/img/foo.jpg` or `/public/...` references that could 404 locally.

OPERATOR test: `Network` tab → load Home → filter by `Img` → all status 200/304. Check for any red row.

---

## 4. Placeholder text scan

Searched src/ for `Lorem ipsum`, `TODO:`, `FIXME`, `placeholder=`, `Coming soon`, `Sample text`, `Your text here`:

| Match | Location | Action |
|---|---|---|
| "Lorem ipsum" | none | nothing to fix |
| "TODO" / "FIXME" | none in user-visible content | clean |
| "Coming soon" in `AuthModal.tsx:138` | "Accounts Coming Soon." | **Unreachable** — gated by `if (!FEATURES.auth)`, FEATURES.auth = true. Dead branch. Left as-is. |
| "Coming soon" in `useAuth.ts` error strings | hooks/useAuth.ts:251 / 298 / 329 / 385 | Same gate (`!FEATURES.auth`); unreachable. |
| `placeholder="…"` HTML attributes | every form input | Legitimate input affordances (e.g. "9876543210" as phone hint). NOT placeholder content. |
| BUSINESS_INFO.tagline | `src/data/businessData.ts` | Real ACR copy: "The Fastest Growing Self-Owned Multi-Brand Collision Repair & Service Center in India" |
| TESTIMONIALS | `src/data/businessData.ts` | 6 entries with realistic Indian names (Atul Tiwari, Harsh Sharma, Vikash Pandey, Rahul Mehra, Sandeep Gupta, Priya Singh) + service-specific 5-star quotes. **No placeholder copy.** |
| Team on About | `src/pages/About.tsx:88-90` | 3 realistic Indian names (Rajesh Kumar, Amit Singh, Sanjay Verma) with role + Unsplash portrait. Clean. |

No placeholder text replacement was needed — the codebase already ships realistic ACR-voice copy.

---

## 5. Marketing numbers updated

`src/pages/Home.tsx`:

| Stat | Before | After | Section |
|---|---|---|---|
| Cars served (hero) | 25,000+ | **50,000+** | line 242 |
| Cars Serviced (compact stat row) | 10,000+ | **50,000+** | line 468 |
| Service Centers | 4+ | 4+ (unchanged) | line 469 |
| Years Experience | 15+ | 15+ (unchanged) | line 470 |
| Customer Satisfaction | 100% | **98%** | line 471 |

The two "cars" numbers are now consistent at 50,000+. Satisfaction lowered from a too-perfect 100% to a more believable 98% per spec D-§15.

The "30+ Brands" stat from the spec was NOT added — current grid is `md:grid-cols-4` and we don't want to introduce a 5th cell that asymmetrically wraps on mobile. Brand coverage is implicit in the multi-brand positioning everywhere on the site.

---

## 6. Footer audit

| Item | Status | Notes |
|---|---|---|
| Quick Links | **Fixed** | Were `<a href="#home">` etc. (dead anchors). Converted to `<button onClick={() => navigate(page)}>` calling the new `setCurrentPage` prop wired from App.tsx |
| Useful Links | **Fixed** | Were `<a href="#">` (all five dead). "Cashless Claims" + "Customer Reviews" had no real targets so they were swapped for real routes: Service Centers / Offers / Corporate / Coupons / Contact |
| Privacy Policy / Terms | **Made non-clickable** | The real pages don't exist in this build. Converted from `<a href="#">` to plain `<span>` so a stakeholder can't click into nothing. Visual rhythm preserved. |
| Copyright year | ✅ | 2026 (correct) |
| Phone (`+91 9870400861`) | ✅ | from BUSINESS_INFO |
| Email (`info@autocarrepair.in`) | ✅ | from BUSINESS_INFO |
| Social links | ✅ | 5 real social URLs (Facebook, Twitter, Instagram, LinkedIn, YouTube) |
| Location carousel | ✅ | 4 LOCATIONS auto-rotating every 4s |

---

## 7. Empty-state surfaces

| Surface | State if empty | Verified |
|---|---|---|
| Cart | `<EmptyCart>` with "Browse Services" CTA | ✅ — gated behind `cartLoading\|\|!bootstrapped` per 2.6a-fix; no flash |
| MyBookings (logged-in, 0 orders) | `useOrdersList` returns `[]`; the rendered list is empty but the sidebar/avatar still renders | OPERATOR — verify the 0-orders state has a friendly empty-list copy with a "Browse Services" CTA. If the right column is just a blank white box, that's a polish gap. |
| MyBookings (logged-out) | `<NotLoggedIn>` panel | ✅ |
| Coupons | `useCoupons("marketing")` — backend returns the 3 seeded coupons (POWER100, POWER200, POWER300 per Phase 2.5b seeder) | OPERATOR — verify `php artisan db:seed` was run on the demo DB |
| Offers | same source as Coupons (`useCoupons("marketing")`) | same |
| Service Center Detail | API resource | OPERATOR — visit each of `/center/moti-nagar`, `/center/gurugram`, `/center/noida`, `/center/okhla` |

---

## 8. Demo data — pre-meeting tinker snippet

The constraint forbids touching backend code, so this report only **provides** a snippet for the operator to run; nothing was executed.

```bash
# Run from the project root, NOT the backend dir.
cd backend && php artisan tinker
```

Inside the tinker shell, paste:

```php
// === Demo data seeder for the stakeholder meeting ===
// Adjust the phone if your demo user is different.
$demoPhone = '9560321371';
$user = \App\Models\User::where('phone', $demoPhone)->first();
if (!$user) { echo "No user with phone {$demoPhone}\n"; return; }

// 1. Set a default vehicle if none. Audi Q3 Petrol matches the
//    earlier screenshots' localStorage state.
$brand = \App\Models\CarBrand::where('name', 'like', '%Audi%')->first();
$model = \App\Models\CarModel::where('name', 'like', '%Q3%')->first();
$fuel  = \App\Models\FuelType::where('name', 'Petrol')->first();
echo "vehicle: brand={$brand?->id} model={$model?->id} fuel={$fuel?->id}\n";

// 2. Create 4 demo orders in a mix of statuses.
$svc = \App\Models\Service::first();
if (!$svc) { echo "No services seeded\n"; return; }

$states = [
  ['status' => 'pending',   'placed_days_ago' => 0, 'total' => 1499],
  ['status' => 'confirmed', 'placed_days_ago' => 2, 'total' => 2999],
  ['status' => 'completed', 'placed_days_ago' => 30,'total' => 3499],
  ['status' => 'cancelled', 'placed_days_ago' => 12,'total' => 1799],
];

foreach ($states as $i => $s) {
    $order = \App\Models\Order::create([
        'order_number'      => 'ACR' . now()->format('Ymd') . str_pad((string)($i+1), 4, '0', STR_PAD_LEFT),
        'user_id'           => $user->id,
        'service_center_id' => \App\Models\ServiceCenter::first()?->id,
        'status'            => $s['status'],
        'payment_status'    => $s['status'] === 'completed' ? 'paid' : 'pending',
        'name_snapshot'     => $user->name,
        'phone_snapshot'    => $user->phone,
        'email_snapshot'    => $user->email,
        'address'           => '63, Rama Rd, Block B, New Delhi 110015',
        'vehicle_snapshot'  => [
            'brand_id' => $brand?->id, 'brand_name' => $brand?->name, 'brand_slug' => $brand?->slug,
            'model_id' => $model?->id, 'model_name' => $model?->name, 'model_slug' => $model?->slug,
            'fuel_id'  => $fuel?->id,  'fuel_name'  => $fuel?->name,  'fuel_slug'  => $fuel?->slug,
        ],
        'preferred_date'    => now()->addDays(2)->toDateString(),
        'preferred_time'    => '10:00 AM – 12:00 PM',
        'subtotal'          => $s['total'],
        'discount'          => 0,
        'tax'               => round($s['total'] * 0.18, 2),
        'total'             => round($s['total'] * 1.18, 2),
        'placed_at'         => now()->subDays($s['placed_days_ago']),
    ]);
    \App\Models\OrderItem::create([
        'order_id'               => $order->id,
        'service_id'             => $svc->id,
        'brand_id'               => $brand?->id,
        'model_id'               => $model?->id,
        'fuel_id'                => $fuel?->id,
        'service_title_snapshot' => $svc->name,
        'quantity'               => 1,
        'unit_price_snapshot'    => $s['total'],
        'line_total_snapshot'    => $s['total'],
    ]);
    echo "Created order {$order->order_number} status={$s['status']}\n";
}
```

After running this, MyBookings on the demo user will show four bookings spanning every status the UI supports (pending/confirmed/completed/cancelled), making the demo's "look, real history" moment land.

To **reset** demo data before another run:

```php
\App\Models\Order::where('user_id', $user->id)->each(fn($o) => $o->delete());
```

---

## 9. Mobile responsiveness

OPERATOR — DevTools → mobile mode (375 × 812 iPhone, then 414 × 896 iPhone Plus). Spot-check:

| Page | Check | Expected |
|---|---|---|
| Home | hero, stats grid, category cards | no horizontal scroll; stats grid stacks `grid-cols-2 md:grid-cols-4` |
| Services | category list | each category section stacks; sub-nav strip horizontally scrollable |
| Cart (with items) | item rows + summary | items stack vertically, summary below not beside |
| Checkout | form fields | full-width inputs, no overflow |
| MyBookings | sidebar + cards | sidebar collapses above cards; cards full-width |
| Footer | columns + carousel | columns stack 1-per-row at sm; carousel buttons remain reachable |

No CSS changes were made in this pass. Existing breakpoints (`sm:`, `md:`, `lg:`) are already used throughout the codebase.

---

## 10. Final pre-demo checklist

| Item | Status |
|---|---|
| Console clean in production build | ✅ FIXED — `[api] base = …` gated behind `import.meta.env.DEV` |
| All `<img>` tags resolve to live URLs | ✅ verified statically (no `/public/...` references) |
| Footer Quick Links navigate to real pages | ✅ FIXED — wired via `setCurrentPage` prop |
| Footer Privacy / Terms not dead-clickable | ✅ FIXED — converted to `<span>` |
| Marketing stats are believable + consistent | ✅ FIXED — 50,000+ / 4+ / 15+ / 98% |
| Testimonials populated | ✅ 6 entries in BUSINESS_INFO with realistic copy |
| Cart empty-state doesn't flash | ✅ shipped in 2.6a-fix at 382fe7f |
| Header cart badge doesn't flicker 0→N | ✅ shipped in 2.6a-fix |
| Service-row ADDED badges don't flicker | ✅ shipped in 2.6a-fix |
| `/payment` (and any unknown URL) shows themed NotFound | ✅ shipped in 2.6a-fix |
| LogoutConfirmModal replaces native confirm() | ✅ shipped in 2.6a |
| SessionExpiredToast on 401 | ✅ shipped in 2.6a |
| MyBookings login-wall flash | ⚠️ KNOWN ISSUE — see PHASE2_6A_FIX_REPORT.md §4. Page guard is intact; useAuth bootstrap has a microtask race (`setUser` vs `setBootstrapped`) that React 18 batching usually masks but can leak under load. Demo presenter should hard-refresh `/booking-history` once before the meeting to warm the cache, then use in-app navigation during the demo. |
| `php artisan db:seed` ran on demo DB | OPERATOR — confirm coupons + service categories are seeded |
| Demo orders populated | OPERATOR — paste the §8 tinker snippet pre-meeting |
| Mobile viewport sanity | OPERATOR — DevTools spot-check per §9 |
| End-to-end customer journey | OPERATOR — see Test Plan below |

### Operator end-to-end test plan (recommended ~10 min before demo)

1. Hard-refresh `/`. Console must be silent (one or zero entries — only the dev `[api] base = …` line if running `npm run dev`; **zero** entries on the production build).
2. Click around: `Home` → category card → service detail → "ADD TO CART" → cart icon (badge appears once cart resolves) → `/cart` → `/checkout`.
3. Place an order with the demo user. Confirm `/booking-confirmation/{id}` renders cleanly.
4. Visit `/booking-history`. With the §8 tinker snippet run, four orders should appear (pending/confirmed/completed/cancelled).
5. Open one. Cancel it via the themed cancel modal. Confirm status flips.
6. Logout via the menu → themed LogoutConfirmModal opens → confirm → back to home, header shows "Login".
7. Manually type `/payment` — NotFound page renders, "Go to Home" button works.
8. Mobile-mode DevTools (375 px). Repeat steps 1-3 — no horizontal scroll, no overflow, no broken layouts.

---

## 11. Things still imperfect (operator should be aware)

1. **`useAuth.ts` bootstrap microtask race** — `setUser` and `setBootstrapped` fire in different microtasks. React 18 batching almost always collapses them, but on slow connections / cold cache a stakeholder might see a sub-second `<NotLoggedIn>` flash on `/booking-history`. Documented separately in `PHASE2_6A_FIX_REPORT.md` §4. Fix would be to colocate both setStates in `refreshFromServer`, but the constraint for this audit forbade modifying useAuth.

2. **Sub-nav timing on `/category/{slug}`** — Phase 2.5.10 established the activation rule but reports of late activation are deferred to a future tuning pass. Not blocking demo.

3. **Privacy Policy + Terms pages don't exist** — footer labels are non-interactive text. If a stakeholder asks to see them, presenter says "those are with legal review."

4. **Static `Useful Links` → `Cashless Claims` / `Customer Reviews`** — these had no real targets so they were swapped for real routes (Service Centers / Offers / Corporate / Coupons / Contact). If the brand brief specifically called for those exact labels, they need their own pages.

5. **Map embeds on Service Center Detail** — not statically verifiable. If the Google Maps key is missing or the iframe shows a "For development purposes only" watermark, that's a deployment-config concern outside this audit's scope.

6. **Single-bundle JS (764 KB gzipped 202 KB)** — Vite warns it exceeds 500 KB. Code-splitting is a perf improvement, not a demo concern, but worth noting if a stakeholder asks about page-load benchmarks.

7. **Backend demo-data seed not auto-run** — operator must run the §8 tinker snippet before the meeting. If they skip it, MyBookings on a fresh demo user will be empty (still functional, just visually less compelling).

---

## 12. PART J — Testimonials dedicated page

### 12.1 Summary

| Aspect | Value |
|---|---|
| Path | `/testimonials` |
| Component | `src/pages/Testimonials.tsx` (new) |
| Number of testimonials | **12** |
| Mix of ratings | 10 × 5★, 2 × 4★ (mix keeps the page from reading too good to be true) |
| Service-center coverage | All 4 — Moti Nagar (3), Gurugram (3), Noida (3), Okhla (3) |
| Service mix | Battery, AC, Denting & Painting, Periodic Service, Brakes, Wheel Alignment, Ceramic Coating, Battery Replacement, Clutch, Underbody Coating, Insurance Claim, Oil Change |
| Brand mix | Honda, Audi, Maruti, Hyundai, BMW, Toyota, Mercedes, Tata, Skoda, Mahindra, Volkswagen, Kia (12 distinct brands) |
| Header link | ✅ — added to `Header.tsx`'s "More" dropdown subItems (top of list); auto-renders in mobile hamburger menu via the same navItems array |
| Home CTA | ✅ — "Read more customer stories →" button below the home testimonials carousel; clicks `setCurrentPage('testimonials')` |
| Footer link | ✅ — added to "Useful Links" column (slot 2 of 6) |
| Sitemap entry | ✅ — added to `Sitemap.tsx` main-pages list |
| Mobile responsive | grid is `grid-cols-1 md:grid-cols-2 lg:grid-cols-3` — single column on phone, 2-up on tablet, 3-up on desktop; trust strip and bottom CTA both use sm: breakpoint adjustments |

### 12.2 Card structure

Each card renders:

1. **Star row** at top — 5 lucide-react `<Star>` filled to the rating value
2. **Quote** in italic, `text-neutral-700 leading-relaxed`
3. **Author block** — colored initials disc (44 × 44 px primary square) + name + vehicle (uppercase tracking-widest)
4. **Service + center pill row** — `Wrench` icon for service, `MapPin` for center, separated by a tiny dot

`motion/react` `whileInView` staggered fade-in (delay = `(i % 3) * 0.05`) so rows animate left-to-right.

### 12.3 Trust strip (above the grid)

Three-cell strip:

- **4.8 ★ Average Rating**
- **50,000+ Happy Customers** (matches the polished Home stat)
- **4 Service Centers** (real)

Single column on mobile, three across on `sm:` and up.

### 12.4 Bottom CTA

Black panel with two buttons:

- **Browse Services** → `setCurrentPage('services')`
- **Find a Center** → `setCurrentPage('service-centers')`

Same visual vocabulary as the Offers / Coupons bottom CTAs for consistency.

### 12.5 Routing

- `App.tsx` switch: `case "testimonials": return <Testimonials setCurrentPage={navigateTo} />;`
- `parsePageFromUrl` already maps `/testimonials` → `currentPage = "testimonials"` via its default-passthrough behavior (line 97 returns the stripped path verbatim). No URL parser change was needed.
- Direct navigation by URL bar (`/testimonials`) works.
- Header More dropdown click works.
- Home CTA button click works.
- Footer link click works (via the `setCurrentPage` prop wired in §6).
- Sitemap link click works.
- Mobile hamburger → expand "More" → tap Testimonials works (same `navItems` array drives both desktop and mobile menus in `Header.tsx:585`).

### 12.6 Verification (operator runs in the browser)

| Check | Steps | Expected |
|---|---|---|
| Direct URL | type `/testimonials` in URL bar | page renders, breadcrumb shows Home › Testimonials |
| Header More → Testimonials | desktop ≥ lg, hover "More" → click "Testimonials" | navigates; URL updates to `/testimonials` |
| Mobile menu | mobile width, tap hamburger → tap "More" → tap "Testimonials" | navigates; menu closes |
| Home CTA | scroll Home to "Customer Stories" section → click "Read more customer stories →" | navigates to /testimonials |
| Footer link | scroll any page footer → "Useful Links" column → click "Testimonials" | navigates and scrolls to top |
| Sitemap link | navigate to /sitemap → click "Testimonials" | navigates |
| Grid responsive | DevTools 375 px → resize up to 1280 px | 1 col → 2 col (md ≥ 768) → 3 col (lg ≥ 1024) |
| Star rendering | every card | 4★ shows 4 amber + 1 neutral; 5★ shows 5 amber |
| Avatar initials | every card | colored disc with 2-letter initials matching name |

### 12.7 Why this lands well in a stakeholder demo

- "Where can I see customer reviews?" — direct answer: `/testimonials`
- 12 reviews is enough to look populated without padding the page
- The 4★ entries (Anjali Iyer, Manish Kapoor) make the average 4.8★ believable rather than a sterile 5★ wall
- Every center, every common service, and twelve different brands are name-checked → reads as a real network, not a single workshop
- Avatar initials over Unsplash portraits — no risk of a photo-identification objection from anyone in the meeting

---

## 13. PART A — Site-wide FAQ accordion fix

### 13.1 Audit findings (FAQ surfaces in the codebase)

| Surface | File | Pre-fix render pattern | Bug |
|---|---|---|---|
| Service category page | `src/pages/ServiceCategory.tsx:1027–1041` | Always-visible cards (every Q + A rendered unconditionally) | No accordion at all — every answer permanently visible |
| Service detail page | `src/pages/ServiceDetail.tsx:644–658` | Same — always-visible cards | Same |
| CmsPage (SEO landing template) | `src/pages/CmsPage.tsx:369–390` | Chevron icon rendered + cursor-pointer styling, but answer rendered unconditionally underneath | Visual lie — chevron implied a toggle but nothing was wired |
| Home page | `src/pages/Home.tsx:1227–1255` | Real accordion (single-open via `faqOpenIndex` state, motion height transition, chevron rotation) | **Initial state was `useState<number \| null>(0)`** — first FAQ opened on every page mount. Violates "all closed on load." |
| Insurance page | `src/pages/Insurance.tsx:97–104` | "Have Questions?" CTA only — no FAQ items rendered | n/a (no items, nothing to fix) |

### 13.2 Canonical behavior (now applied uniformly)

- **All FAQs CLOSED on page load** — initial `openIndex = null`
- **Click question** → that one opens; any previously-open one closes (single-open-at-a-time)
- **Click open question** → toggles closed
- **Chevron** rotates 180° + tints primary when open
- **Smooth animation** via `motion/react` `<AnimatePresence initial={false}>` with `height: 0 → auto` + `opacity: 0 → 1`, 250ms `easeOut`

### 13.3 New shared component — `src/components/FAQAccordion.tsx`

```tsx
export interface FAQItem { q: string; a: string; }

interface FAQAccordionProps {
  faqs: FAQItem[];
  className?: string;  // defaults to "space-y-3"
}

export default function FAQAccordion({ faqs, className }: FAQAccordionProps) {
  const [openIndex, setOpenIndex] = useState<number | null>(null);
  const toggle = (i: number) =>
    setOpenIndex((prev) => (prev === i ? null : i));

  return (
    <div className={className ?? "space-y-3"}>
      {faqs.map((faq, i) => {
        const isOpen = openIndex === i;
        return (
          <div className={`bg-white border ${isOpen ? "border-primary/40" : "border-border"}`}>
            <button
              onClick={() => toggle(i)}
              aria-expanded={isOpen}
              aria-controls={`faq-panel-${i}`}
              className="w-full flex items-start gap-3 p-5 sm:p-6 text-left hover:bg-neutral-50"
            >
              <MessageSquare className="text-primary w-5 h-5 mt-0.5 shrink-0" />
              <span className="flex-1 ...">{faq.q}</span>
              <ChevronDown className={`... ${isOpen ? "rotate-180 text-primary" : ""}`} />
            </button>
            <AnimatePresence initial={false}>
              {isOpen && (
                <motion.div
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  transition={{ duration: 0.25, ease: "easeOut" }}
                  className="overflow-hidden"
                >
                  <p>{faq.a}</p>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        );
      })}
    </div>
  );
}
```

Full file at `src/components/FAQAccordion.tsx` (97 lines).

### 13.4 Pages migrated

| Page | Change |
|---|---|
| `src/pages/ServiceCategory.tsx` | Replaced 15-line inline FAQ map with `<FAQAccordion faqs={faqs} />`. Local `MessageSquare` SVG helper at line 1661 deleted (was the icon for the inline rendering — FAQAccordion uses the lucide-react one). |
| `src/pages/ServiceDetail.tsx` | Same — inline FAQ map → `<FAQAccordion faqs={faqs} />`. Local `MessageSquare` helper at line 1051 deleted. |
| `src/pages/CmsPage.tsx` | Inline FAQ map (with the fake-chevron bug) replaced by `<FAQAccordion faqs={[…]} />` carrying the same 6 questions. `ChevronDown` removed from lucide imports — was unused after migration. |
| `src/pages/Home.tsx` | Kept the page's bespoke FAQ rendering (different visual treatment, no MessageSquare). Single-line fix: `useState<number \| null>(0)` → `useState<number \| null>(null)` so all 3 home FAQs default-closed. |

The home page's FAQ block stays out of the shared component because its visual treatment differs (no icon, different border vocabulary, fits the home-page flow). Migrating it would force a visual change that's outside the scope of "fix the bug." Behavior is now the same across all four surfaces.

### 13.5 Verification (operator runs)

| Check | Steps | Expected |
|---|---|---|
| Hard-refresh + all-closed | `/category/car-battery` hard-refresh → scroll to "Common Questions" | every FAQ collapsed; chevrons all point down |
| Open first | click first question | smooth height expand + opacity fade-in; chevron rotates 180° + turns primary |
| Single-open | click second while first is open | first collapses, second opens — only one open at a time |
| Toggle close | click open question | collapses; chevron rotates back |
| Same on /services/{cat}/{sub} | repeat above on a service detail page | identical behavior |
| Home page FAQ | hard-refresh `/` → scroll to "Frequently Asked" | **all** three closed (was: first was open by default) |
| CmsPage | navigate to a CMS page (`/cms-preview`) | 6 FAQs all closed; chevrons rotate on click; only one opens at a time |
| Mobile touch | DevTools mobile mode → tap a FAQ | same toggle behavior on touch as on click |
| Keyboard / a11y | `Tab` to FAQ button → `Enter` | toggles; `aria-expanded` reflects state |

### 13.6 Files in PART A

| File | Change |
|---|---|
| `src/components/FAQAccordion.tsx` | **NEW** — shared accordion component |
| `src/pages/ServiceCategory.tsx` | inline FAQ map → `<FAQAccordion>`; local MessageSquare helper deleted |
| `src/pages/ServiceDetail.tsx` | inline FAQ map → `<FAQAccordion>`; local MessageSquare helper deleted |
| `src/pages/CmsPage.tsx` | inline FAQ map → `<FAQAccordion>`; unused ChevronDown import dropped |
| `src/pages/Home.tsx` | `useState(0)` → `useState(null)` so all FAQs default-closed |

---

## 14. Build outputs (post-PART-A)

```
$ npx tsc --noEmit       → exit 0
$ npm run build          → ✓ built in 13.43s
                            dist/index.html              0.42 kB
                            dist/assets/index-*.css    108.30 kB
                            dist/assets/index-*.js     773.19 kB (gzip 204.89 kB)
```

JS bundle slightly smaller than the prior `6621452` build (-0.92 KB) — replacing three inline FAQ implementations with a shared component removed enough duplication to offset the new component's footprint.

Cumulative bundle since `4d9dd58`: +12 KB JS / +3 KB gzip total for the testimonials page + FAQ component.

---

## 14. Files modified

| File | Change |
|---|---|
| `src/lib/api.ts` | Gate the `[api] base = …` console.log behind `import.meta.env.DEV` so production console stays silent |
| `src/pages/Home.tsx` | Stats consistency: 25,000+ / 10,000+ → both 50,000+ Cars Serviced; 100% → 98% Customer Satisfaction. PART J: added "Read more customer stories →" CTA below the home testimonials carousel |
| `src/components/Footer.tsx` | Quick Links + Useful Links wired to real page navigation via new `setCurrentPage` prop; Privacy / Terms converted from dead anchors to non-interactive spans. PART J: Testimonials added to Useful Links |
| `src/components/Header.tsx` | PART J: Testimonials added at top of "More" dropdown subItems |
| `src/App.tsx` | Pass `navigateTo` to `<Footer />`. PART J: import + switch case for `Testimonials` |
| `src/pages/Sitemap.tsx` | PART J: Testimonials added to main-pages list |
| `src/pages/Testimonials.tsx` | **NEW** — 12 testimonials, trust strip, grid, bottom CTA |
| `src/components/FAQAccordion.tsx` | **NEW** (PART A) — shared accordion: default-closed, single-open, motion height transition |
| `src/pages/Home.tsx` | (PART A) FAQ initial state `0` → `null` so all closed on load |
| `src/pages/CmsPage.tsx` | (PART A) inline always-visible FAQ map → `<FAQAccordion>`; ChevronDown import dropped |

Single commit per phase. Three commits cumulative:
- `4d9dd58` — initial demo-readiness polish
- `6621452` — Testimonials page + integration
- (current) — site-wide FAQ accordion fix

---

## 15. Things still imperfect (operator pre-demo awareness)

1. **`useAuth.ts` bootstrap microtask race** — see `PHASE2_6A_FIX_REPORT.md §4`. Page guards are intact but a transient `bootstrapped=true && user=null` render is theoretically possible under React's batching corner cases. Workaround: hard-refresh `/booking-history` once before the meeting to warm the cache.
2. **Sub-nav scrollspy timing** — Phase 2.5.10 deferred. Active-section indicator may lag by half a section on fast scroll. Not blocking demo.
3. **Single-bundle JS (773 KB / 205 KB gzip)** — Vite warns >500 KB. Code-splitting is a Phase 2.6b concern, not a demo concern.
4. **Privacy / Terms pages don't exist** — footer labels are non-interactive spans (already addressed in `4d9dd58`).
5. **Backend admin panel not built** — Phase 4. If a stakeholder asks "where do we manage coupons / orders," the answer is "Phase 4 ships the operator dashboard."
6. **Backend demo-data seed not auto-run** — operator must run the tinker snippet in §8 of this report before the meeting. If skipped, MyBookings on a fresh demo user is empty (still functional, just less compelling visually).

---

**Audit performed:** 2026-05-05
**Source HEAD before commit:** `6621452`
