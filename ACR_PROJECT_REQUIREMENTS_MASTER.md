# ACR Platform — Master Requirements Document (Day 0 → Today)

> **Purpose:** Yeh file Step 1 ka output hai. Saari purani Claude chats deep
> analysis karke nikali gayi requirements + decisions phase-wise document
> ki gayi hain. Yeh authoritative requirements list hai.
>
> **Next step:** Yeh file Claude Code ko deni hai (Step 2 prompt). Woh code +
> DB + git + reports ke against har requirement check karega aur status
> dega: ✅ Complete · ⚠️ Partial / Broken · ❌ Not started.
>
> **Built from:** 5 major chats — `Fixing type inconsistencies in HomeProps`
> (30 Apr), `Automotive service platform architecture rebuild` (~11 May),
> `Website typography consistency issues` (21 May), aur current chat (May 26+).
> Google Ads / marketing chats (4–5 May) website project se alag hain — yahan
> nahi.
>
> **Last updated:** 29 May 2026

---

## 0. Project at-a-glance

| Item | Value |
|---|---|
| Name | ACR automotive service platform (GoMechanic-style) |
| First trace | 30 April 2026 (`Fixing type inconsistencies in HomeProps`) |
| Major rebuild started | early May 2026 (`Automotive service platform architecture rebuild`) |
| Today | ~29 May 2026 → **~30 calendar days active** |
| Stack | React (Vite + TS + Tailwind) frontend · Laravel 10 API · MySQL `acr_v3` · Filament admin · Hostinger deploy planned |
| Workflow | Operator (product owner) ↔ Claude App (architect/prompts) ↔ Claude Code (writes code in VS Code) |
| Strict rules | DB never break · slugs never change (SEO) · production data never delete · audit-before-code · scope discipline · operator commits manually |

---

## 1. Day-0 vision (the founding requirements)

From the very first chats, the operator's vision was a customer e-commerce-grade
car-service platform with these end-state requirements:

- **R-0-1** Single rebuilt React + Laravel API + MySQL system. **No more
  hybrid static + API** (the pre-rebuild system had API failures, flickering UI,
  incomplete backend).
- **R-0-2** Backend is the **single source of truth**. Everything API-driven.
- **R-0-3** Scalable CMS architecture (admin manages content).
- **R-0-4** **No fake leads** — every booking has a verified, real owner
  (Phone + OTP auth, anti-fraud).
- **R-0-5** "Fill once, never again" — customer fills details once, system
  reuses them across pages (no re-entering).
- **R-0-6** Customer journey: Services → Cart → Auth gate → Checkout →
  Payment (cash-at-center for MVP) → My Bookings.
- **R-0-7** SEO-safe — slugs locked, production-grade.
- **R-0-8** Hostinger deploy ready.

---

## 2. Phase-by-phase requirements (Day 0 → today)

> Each requirement gets an ID (e.g. P1-R3) so Claude Code can mark each one
> individually in the Step 2 audit.

### Phase 0 — Pre-rebuild legacy fixes (~30 April 2026)

Inherited from `Fixing type inconsistencies in HomeProps`. The site was a
hybrid React app with localStorage-backed mock auth.

| ID | Requirement |
|---|---|
| P0-R1 | Fix TS type inconsistencies in HomeProps |
| P0-R2 | Build initial **auth modal** + `useAuth` (phone+email OTP, password strength, math captcha, disposable-email blocklist, honeypot, rate limiting) |
| P0-R3 | Build initial **booking sidebar** (`BookingSidebar.tsx`) + cart + checkout + payment flow |
| P0-R4 | Build initial **coupon system** (FIRST10, SAVER15, ACCOOL20) — auto-pick best applicable, manual entry, persists Cart→Checkout→Payment |
| P0-R5 | Header: add Cart icon + Login/User menu next to "Pay Online" |
| P0-R6 | Cart "Proceed to Checkout" hard auth gate |
| P0-R7 | Auto-prefill from logged-in user on Checkout |
| P0-R8 | `/my-bookings` page with booking history |
| P0-R9 | Service Category page: skip OTP if user already logged in |

> Note: Phase 0 work was a frontend-only mock with localStorage. Phase 1 below
> rebuilt this against a real Laravel backend.

### Phase 1 — Backend Foundation ✅ (claimed complete)

| ID | Requirement |
|---|---|
| P1-R1 | Laravel 10 + MySQL fresh schema (`acr_v3`) |
| P1-R2 | **25+ database tables** (users, services, categories, brands, models, vehicles, orders, cart, coupons, etc.) |
| P1-R3 | Master data seeded: 12 service categories, 100+ services |
| P1-R4 | Vehicle-specific pricing tiers (`service_prices`, 52,521 rows) |
| P1-R5 | Phase 1.6 — N+1 query fix (single round-trip page loads) |
| P1-R6 | 16 documented API routes locked |

### Phase 2 — Core Customer Features ✅ (claimed complete)

| ID | Requirement |
|---|---|
| P2-R1 (2.1) | Auth: Phone + OTP + Bearer token (real backend, not mock) |
| P2-R2 (2.2) | User addresses CRUD |
| P2-R3 (2.3) | Server-side cart with vehicle-specific pricing |
| P2-R4 (2.4) | Cart merge protocol (guest → logged-in user) |
| P2-R5 (2.5a) | Real Checkout + Orders + Cancellation |
| P2-R6 (2.5b) | Coupon system: 3 active coupons, modal picker, marketing `/coupons` page, 6-step validation chain |
| P2-R7 (2.5.1–9) | UX polish: sub-nav scrollspy, auth hydration (no login-flash on refresh) |
| P2-R8 (2.6a) | Dead code cleanup, loading skeletons sitewide, 401 toast handling |
| P2-R9 (2.6b) | Code-splitting + vendor chunks (route-based) |
| P2-R10 (2.6c) | Test infrastructure (smoke tests) |
| P2-R11 (2.6d) | 47 automated tests / edge case coverage |

### Phase 3 — Router Migration ✅ (claimed complete)

| ID | Requirement |
|---|---|
| P3-R1 (3A) | react-router-dom foundation + shim |
| P3-R2 (3B) | Pure router migration (custom routing removed) |
| P3-R3 | Deep linking, browser back/forward, hash-based code splitting per route |
| P3-R4 | 53 automated tests passing (28 backend + 25 frontend) |

### Demo Polish ✅ (claimed complete)

| ID | Requirement |
|---|---|
| DP-R1 | `/testimonials` page (12 customer stories) |
| DP-R2 | Site-wide FAQ accordion (default-closed, single-open) |
| DP-R3 | Home FAQ design upgrade (premium cards, primary blue accent) |
| DP-R4 | Marketing numbers populated |
| DP-R5 | Broken images fixed |

### Phase 4 — Filament Admin Panel (in progress)

**Locked decisions (Phase 4-wide):**
- Single super-admin (no roles for MVP)
- Same `users` table + `is_admin` flag (no separate admin table)
- Default Filament Amber theme (branding deferred to Phase 6)
- Cash-at-center only (no payment gateway)
- Client-side SEO rendering (no SSR)
- Header search deferred to Phase 6+

#### Phase 4.1 — Filament foundation ✅
| ID | Requirement |
|---|---|
| P4.1-R1 | Filament v3.3.50 installed, panel live at `/admin` |
| P4.1-R2 | `admin@acr-mechanics.in` seeder + `is_admin` + `canAccessPanel()` |

#### Phase 4.2 — Core CRUD ✅
| ID | Requirement |
|---|---|
| P4.2-R1 | OrderResource + status transitions (with documented bypass risk for Phase 6) |
| P4.2-R2 | UserResource (no password field, OTP login; admin-toggle self-protection) |
| P4.2-R3 | CouponResource (description field doing double-duty as T&C — flagged) |
| P4.2-R4 | ServiceCategoryResource (drag-reorder via `position`) + FileUpload for `image` + **icon_image** |
| P4.2-R5 | ServiceResource + FileUpload for `image` |
| P4.2-R6 | OperationsStats dashboard widget (4 stats: Pending Orders, Today's Bookings, This Week's Revenue, Active Customers) |
| P4.2-R7 | 5 admin access tests (admin vs customer 403) |
| P4.2-R8 | 58 backend Pest tests after this phase |

#### Phase 4.3 — Master data + Excel upload ⏳ (launch blocker)
| ID | Requirement |
|---|---|
| P4.3-R1 | Brand admin CRUD with image (Filament) |
| P4.3-R2 | Model admin CRUD with image (Filament) |
| P4.3-R3 | Fuel type admin CRUD with image |
| P4.3-R4 | Service pricing admin CRUD |
| P4.3-R5 | **Excel upload** for brands, models, fuel, pricing (bulk) |
| P4.3-R6 | Auto-fallback icon when image null (brand monogram / car icon / fuel icon) |

> ⚠️ Note: From Phase 4.5.3 we saw `cars:import` (vehicle xlsx), `pricing:import` (pricing xlsx), and `app/Imports/ServicesImport.php` (Laravel-Excel) **already exist** in the repo. The audit (Step 2) needs to confirm whether these satisfy P4.3-R1..R5 fully or only partially.

#### Phase 4.4 — Bulk image upload + slug auto-mapping ⏳ (launch blocker)
| ID | Requirement |
|---|---|
| P4.4-R1 | Bulk folder import command (slug-matching, e.g. `bmw-3-series.png` → BMW 3 Series) |
| P4.4-R2 | Filament single-upload field on Brand/Model/Fuel edit pages |
| P4.4-R3 | Auto-fallback if image still null |
| P4.4-R4 | API exposes `image_url` for these entities |
| P4.4-R5 | Frontend uses real image when present, fallback otherwise |

#### Phase 4.5 — SEO Pages system ⏳ (biggest single sub-phase)

This is the largest piece. Multiple sub-phases ran (4.5, 4.5.1–4.5.6) focused on `/explore`.

| ID | Requirement |
|---|---|
| P4.5-R1 | **200 dynamic SEO pages** with top-level slug strategy (e.g. `/audi-service-delhi`) |
| P4.5-R2 | `seo_pages` table + admin CRUD |
| P4.5-R3 | TipTap rich text editor in admin |
| P4.5-R4 | URL redirects for old website continuity |
| P4.5-R5 | `sitemap.xml` route |
| P4.5-R6 | `robots.txt` route |
| P4.5-R7 | `react-helmet-async` for client-side SEO |
| P4.5-R8 | `/explore` hub page (editorial layout) |
| P4.5-R9 | Internal SEO page renderer (`SeoPageView` promoted from CmsPage) with breadcrumbs, related articles, sticky CTA, internal linking footer |
| P4.5.1-R1 | Explore: hero carousel (3–5 featured pages, autoplay) |
| P4.5.1-R2 | Explore: featured grid (5-card mosaic — `is_pinned=true` × 5) |
| P4.5.1-R3 | Explore: trending grid + category sections + rails ("Trending Searches", "Most Read This Week") |
| P4.5.1-R4 | Explore: sidebar widgets — initially Newsletter, then **Lead form** (see 4.5.3) |
| P4.5.1-R5 | Explore: TopPicks, PopularBrands, RelatedTopics, GetSocial widgets |
| P4.5.1-R6 | Explore: smart search + recent searches in localStorage |
| P4.5.1-R7 | Explore: footer "Explore More" 3-column (Categories with icons / Popular Searches chips / Quick Stats card + CTA) |
| P4.5.3-R1 | Replace Newsletter with `LeadFormWidget` — 6 fields: name, email, phone, brand, model, service |
| P4.5.3-R2 | `leads` table + Lead model + POST `/api/v1/leads` (throttled 5/IP/hour) |
| P4.5.3-R3 | Lookup endpoints: `/lookups/brands`, `/lookups/models?brand_id=X`, `/lookups/services` (categorized) |
| P4.5.3-R4 | Filament `LeadResource` (view incoming leads) |
| P4.5.3-R5 | Newsletter infrastructure **removed** (table dropped, controller/test deleted) |
| P4.5.3-R6 | 2 more SEO pages marked `is_pinned=true` to make 5-mosaic full |
| P4.5.4-R1 | Brand Service / City Service category layout (no dead space) |
| P4.5.5-R1 | Trending Now layout fixed (12-col grid, no empty right column) |
| P4.5.6-R1 | Service Guide section: 1 LARGE + 3 SMALL stacked, no dead space |

#### Phase 4.6 — Content migration ⏳
| ID | Requirement |
|---|---|
| P4.6-R1 | Migrate `LOCATIONS` from `businessData.ts` → backend table + admin |
| P4.6-R2 | Migrate `BUSINESS_INFO` (hours, contacts, etc.) → backend |
| P4.6-R3 | Migrate `TESTIMONIALS` → backend table + admin |

#### Phase 4.7 — Site-wide Typography & Brand Consistency Pass

Driven by the brand manual (`ACR_Brand_Manual_4.pdf`, 53 pages, in `/mnt/project/`).

**Source-of-truth specs:**
- **Colors:** ACR Blue `#1F4FA3` · Deep Navy `#0E2A5C` · Workshop Black `#111111` · Clean White `#FFFFFF` · Mechanical Orange `#F28C28` (CTAs only) · Collision Red `#D62828` (warnings only) · Steel Grey `#5F6368` · Service Silver `#B8BDC7`
- **Fonts:** Display Montserrat (Regular/SemiBold/Bold) · Body Inter (Regular/Medium/SemiBold). Loaded via Google Fonts `@import` (NOT npm).
- **H1:** Montserrat Bold 36–48pt
- **H2:** Montserrat **SemiBold (600)** 22–28pt — last word + period in ACR Blue (e.g. `CURRENT` black + `OFFERS.` blue)
- **Body:** Inter Regular 14–16pt
- **Caption:** Inter Medium 10–12pt
- **Colour ratio:** 60% White / 25% Blue / 10% Black / 5% Orange

| ID | Requirement |
|---|---|
| P4.7-R1 | `<SectionHeading>` component (dual-color H2 with period, blue accent on last word) |
| P4.7-R2 | `<PageBanner>` standardized (image bg + overlay + breadcrumb + display-font H1 on every page) |
| P4.7-R3 | No inline H1 outside PageBanner |
| P4.7-R4 | All H3+ uppercase-bold-black (no dual-color, no period) |
| P4.7.1-R1 | Brand manual extracted as source of truth (`PHASE4_7_1_BRAND_EXTRACTION.md`) |
| P4.7.2-R1 | Site-wide 18+ violations swept across 15+ pages |
| P4.7.3-R1 | Home hero "FLAWLESS RESTORATION." flip to navy+white (was light-bg+cyan) |
| P4.7.4-R1 | Home page H2 unification → migrate all to `<SectionHeading>` |
| P4.7.5-R1 | Micro-fixes: home "FLEET MAINTENANCE" size override; footer heading → `.section-heading-sm` clamp ~22px one-line desktop |

**Pending typography violations (operator-flagged, last audit):**
| ID | Requirement |
|---|---|
| P4.7-PEND-1 | Home hero "FLAWLESS RESTORATION." — flip didn't land (still light bg + cyan) |
| P4.7-PEND-2 | Card-title casing split-brain: most pages UPPERCASE, Home "Why Choose Us" Mixed Case → pick uppercase (CR-3) |
| P4.7-PEND-3 | Promo H2s single-colour: "READY TO BE OUR NEXT HAPPY CUSTOMER?" / "READY TO ELEVATE YOUR FLEET?" → dual-colour with `?` terminator |
| P4.7-PEND-4 | SEO article H2s bare (Mercedes-Benz page): WHAT WE SERVICE, PERIODIC SERVICE TIERS, etc. — `SeoPageContent.tsx` wrongly marked out-of-scope; these are customer-facing |
| P4.7-PEND-5 | Off-brand blue grep + replace with `#1F4FA3` / `text-primary` (sky/cyan/`#0EA5E9`/`#06B6D4`/`#3B82F6`) |

#### Phase 4.8 — Backend performance pass

| ID | Requirement |
|---|---|
| P4.8-R1 | Measure: query count + total SQL ms per endpoint (home, services, /:slug, /:cat/:svc, lookups, pricing) |
| P4.8-R2 | Covering index on `service_prices(brand_id, model_id, fuel_type_id, service_id)` |
| P4.8-R3 | Fix any N+1 via eager-loading |
| P4.8-R4 | Re-measure + prove improvement |

### Service Pages Redesign sprint (current chat, May 26+)

This is a major sprint that wasn't anticipated in the original phase plan. It
goes after the 3 service surfaces with a GoMechanic-style redesign.

#### Phase 1 (sprint) — Service data backend + admin ✅
| ID | Requirement |
|---|---|
| SP-1-R1 | New `service_inclusions` table (service_id, label, image, position, group_name, timestamps) — guarded migration |
| SP-1-R2 | New `services.interval_info` column — guarded migration |
| SP-1-R3 | `Service.inclusions()` hasMany (ordered by position) + cascade delete |
| SP-1-R4 | Filament: `interval_info` input + "What's Included" Repeater (drag-reorder via `orderColumn('position')`) with optional thumbnail |
| SP-1-R5 | Filament: `icon_image` FileUpload on ServiceCategory (`{slug}-icon` filename so it doesn't clobber hero) |
| SP-1-R6 | API ServiceResource emits `inclusions[]` + `interval_info` + full image URLs (via `ImageUrl::resolve`); inclusions are detail-only (`whenLoaded`) |
| SP-1-R7 | API list (SubServiceResource) emits `interval_info` + full URLs (no inclusions — lean) |
| SP-1-R8 | API ServiceCategoryResource: `image`, `image_1`, `icon_image` all full URLs |
| SP-1-R9 | 10 new tests; 298 total passing |

#### Phase 1.5 (sprint) — Inclusion grouping ✅
| ID | Requirement |
|---|---|
| SP-1.5-R1 | `service_inclusions.group_name` (nullable string) — Essential / Performance / Additional |
| SP-1.5-R2 | Filament Select for group_name |
| SP-1.5-R3 | API emits `group_name` in detail inclusions[] |
| SP-1.5-R4 | `inclusions:autogroup` artisan command — keyword-based, NULL-only, idempotent, `--dry-run` |
| SP-1.5-R5 | 7 new tests; 305 total passing |

#### Service content import (one-shot operation) ✅
| ID | Requirement |
|---|---|
| SP-IMP-R1 | Diagnostic: parse old dumps `sceduled_packages.sql` (91) + `package_specification.sql` (549). Build slug map (73 exact + 17 corrected near + 1 skip rear-shock) |
| SP-IMP-R2 | `service-content:import` artisan command, additive + NULL-only, transactional, `--dry-run` first |
| SP-IMP-R3 | Pattern-filter on dirty `warrenty_info` / `recommended_info` (symptom text rejected) |
| SP-IMP-R4 | Map `Hour→hours` / `Day→days` for `time_unit` |
| SP-IMP-R5 | Seed `interval_info` from "every N km" pattern in recommended_info |
| SP-IMP-R6 | Skip old price/image/note entirely |
| SP-IMP-R7 | Idempotent (empty-guard for inclusions, NULL-only for columns) |
| SP-IMP-R8 | Real-run result: 543 inclusions / 90 services-with-inclusions / 73 time / 40 warranty / 19 recommended / 5 interval |
| SP-IMP-R9 | Autogroup applied: 462 Essential / 23 Performance / 58 Additional |

> ⚠️ Operator hand-corrections still pending (10-minute work in admin):
> | Pending | Detail |
> |---|---|
> | SP-PEND-1 | Move fluid top-ups (Brake/Wiper/Battery Water) Performance → Essential |
> | SP-PEND-2 | Move "Exterior Inspection" + "Exterior and Interior Inspection" Additional → Essential |
> | SP-PEND-3 | Add 5 "miles" interval values to `interval_info`: front-brake-pad, rear-brake-shoes, tyre-rotation, wheel-balancing, complete-wheel-care |

#### Phase 2 (sprint) — GoMechanic-style service surfaces

3-layer model:
- **Layer 1** `/services` — active-category TABS (in-place swap, URL stays)
- **Layer 2** `/category/:slug` — category page with cards + SEO content
- **Layer 3** `/services/:cat/:svc` — service detail (GoMechanic-style)

| ID | Requirement |
|---|---|
| SP-2-R1 | Persistent `ServicesShell` layout route: sticky cross-category bar + `<Outlet/>` + single `CarSidebar`, mounted once across all 3 layers |
| SP-2-R2 | Scoped crossfade (180ms) on Outlet only — no reload feel |
| SP-2-R3 | Stable App-level animation key (`"services-shell"`) so the whole subtree doesn't remount on catalog-internal nav |
| SP-2-R4 | Single source for sidebar (shell owns it; pages no longer mount CarSidebar) |
| SP-2-R5 | **Layer 1 — active-category TAB view**: shell bar drives in-place tab switch (URL stays `/services`); body shows only active category's cards via shared ServiceCard; "View full page →" link per category to `/category/:slug` |
| SP-2-R6 | **Layer 2 — category page**: GoMechanic-style ServiceCard list at top (image/fallback + duration pill + ServiceMetaRow + inclusions_preview ≤4 + "+N more" + price 4-state + CTA); SEO sections demoted below |
| SP-2-R7 | **Layer 3 — detail page**: "What's Included / Also Includes / Timelines" strip; ServiceMetaRow (duration · warranty · interval(Recommended) · free pickup); **grouped What's Included** (Essential + Performance as image cards; Additional as checkmark list); Deep Navy Steps-After-Booking band; reviews / FAQAccordion / related / Top-Links static; hero fallback (no Unsplash) |
| SP-2-R8 | Shared `ServiceCard` component (Layer 1 + Layer 2 + Layer 3 "related") |
| SP-2-R9 | `ServiceMetaRow` + `groupInclusions()` helpers (NULL group → Essential bucket; empty groups hidden) |
| SP-2-R10 | `inclusions_preview` on list endpoint (labels[≤4] + total, NO N+1) |
| SP-2-R11 | ACR brand only (blue `#1F4FA3` + navy `#0E2A5C` + Montserrat/Inter). Zero GoMechanic red/grey. CTA blue. |
| SP-2-R12 | Image fallback everywhere images are null (data is 0% images) |

#### Phase 2 operator-flagged polish (Phase 2d/2e — pending after report)

| ID | Requirement |
|---|---|
| SP-2d-R1 | Category bar position: BELOW the PageBanner, not directly under the site header. Sticky under header once scrolled past banner. |
| SP-2d-R2 | Category bar look: GoMechanic-style icon (bigger, ~28–32px) + Montserrat label + active **ACR-blue underline + soft blue-tint background pill** |
| SP-2d-R3 | Category bar inner content contained to site max-width (NOT edge-to-edge full viewport) |
| SP-2d-R4 | REMOVE "Prices personalised for {CAR} · {FUEL} in {AREA}" banner from all service surfaces (sidebar already shows car) |
| SP-2d-R5 | Layer 2: REMOVE "Brands We Service" section entirely |
| SP-2d-R6 | Layer 2: REMOVE in-page section-nav scroller (Overview / Pricing / Services / Why Us / Process / Reviews / FAQs / Brands / Why ACR) — shell's category bar is the only nav |
| SP-2d-R7 | "+N more · View All" on cards → **in-place EXPAND** (not navigate to detail). Title click still navigates. |
| SP-2d-R8 | Site-wide horizontal body container padding REDUCED by ~half (e.g. 20px → 10px), via the ONE shared container — audit-first |

#### Category icons populated (operator) ✅
| ID | Requirement |
|---|---|
| SP-ICON-R1 | All 13 ServiceCategory rows have `icon_image` uploaded (operator confirmed — was 0/13, fixed once "Icon Image" box found vs the hero "Image" box) |
| SP-ICON-R2 | API serves full /storage URLs for `icon_image`; files HTTP 200 |

---

### Future: Phase 5 — Production Deploy on Hostinger ⏳ (launch blocker)

| ID | Requirement |
|---|---|
| P5-R1 | Hostinger setup (PHP 8.2, Composer, Node, ext-intl) |
| P5-R2 | DB migration to live `acr_v3` |
| P5-R3 | Frontend build → `/public_html/app/` |
| P5-R4 | Backend → `/public_html/backend/` |
| P5-R5 | Cron entry for auto-confirm scheduled command |
| P5-R6 | SSL certificate + DNS cutover from current site |
| P5-R7 | Enable dormant GitHub Actions CI workflow |
| P5-R8 | Resolve 7 documented data collisions |
| P5-R9 | Smoke test on production |

### Operational pending (security)

| ID | Requirement |
|---|---|
| OPS-R1 | 🔴 GitHub remote backup |
| OPS-R2 | 🔴 Admin password change after leak |

---

## 3. Tier-3 (post-launch) — do NOT block launch on these

These are explicitly deferred per operator's locked decisions.

- **T3-R1** FAQs management (admin CRUD)
- **T3-R2** Brand/Model master data CRUD (deferred until needed; partially done in 4.5.3 lookup endpoints)
- **T3-R3** Activity log / audit trail
- **T3-R4** Analytics dashboards (orders by status, revenue, high-risk queue)
- **T3-R5** Advanced exportable customer/booking reports
- **T3-R6** Custom Filament branding (ACR logo, blue theme)
- **T3-R7** Role-based access (super-admin/manager/viewer; Spatie + Filament Shield)
- **T3-R8** Refund initiation flow
- **T3-R9** WhatsApp/email remarketing & notification triggers
- **T3-R10** Multi-location inventory
- **T3-R11** Header search (Meilisearch)
- **T3-R12** Sub-nav timing fix (cosmetic — Phase 2.5.10)
- **T3-R13** Mobile app + deep-link integration
- **T3-R14** Real-time payment gateway (current = cash only)
- **T3-R15** Locality / brand-city long-tail SEO pages (50 cities × 13 services × 6 localities × 22 brands math — purely SEO infrastructure)

---

## 4. What Step 2 (Claude Code audit) must produce

For EVERY ID above (P0-R1 through SP-2d-R8 + T3-*), classify status with **evidence**:

| Status | Meaning | Evidence required |
|---|---|---|
| ✅ Complete | Done, working, tested | File path + commit/migration/test name |
| ⚠️ Partial / Broken | Some code exists but doesn't fully meet requirement | Specifically what's missing or broken |
| ❌ Not started | No code/migration/test for this | "No file matches X" / "Migration absent" |
| ⏸️ Deferred | Marked Tier-3 / post-launch | OK to keep deferred |

Plus the final delivery:

- A **pre-launch blocker list** (only items that MUST land before going live)
- A **time estimate** per blocker based on historical phase sizes
- An **honest call-out** of contradictions between this requirements doc and the
  real code state (e.g. things claimed done that aren't, or things done that
  this doc didn't list)

---

*End of master requirements document.*
