# PROJECT_STATUS_AUDIT — read-only, evidence-based

**Date:** 2026-05-29 · **Audit method:** filesystem + git log + `artisan migrate:status` + read-only SELECTs against `acr_v3` + report file headers. **No writes.**

---

## 1 · PROJECT TIMELINE

| Metric | Value |
|---|---|
| First commit | **2026-04-30 17:01:18 +0530** (`ad6bc8d` "chore: baseline + add API layer") |
| Last commit | **2026-05-25 16:56:32 +0530** (`3e40c1a` "feat(services): service content import + inclusions grouping; coupon guest preview") |
| Total commits | **53** |
| Days active | **~25 calendar days** (2026-04-30 → 2026-05-25); reports + working-tree edits extend to **2026-05-29** (today) — i.e. **~30 days end-to-end** |
| Uncommitted work in tree | **Phase 2 service-pages series (2b → 2e)** and grouped autogroup column are in the working tree but not yet committed. The single 2026-05-25 commit bundled the import + grouping + coupon-guest backend work; everything dated 2026-05-26 onward (4 phase-2 reports + edits to 14 files) is uncommitted. |

### Condensed phase ↔ date table
(Sources: git commit messages + report mtimes + migration timestamp prefixes)

| Phase | Description | Started | Completed |
|---|---|---|---|
| Phase 1 — API baseline | Add API layer to existing Front controllers; React/Laravel integration | 2026-04-30 | 2026-05-01 |
| Phase 1 schema — services + vehicles | `service_categories`, `services`, `car_brands`, `car_models`, `fuel_types`, `service_prices`, `pages`, `sections` | 2026-05-01 (migrations) | 2026-05-01 |
| Phase 2.1 — Auth + OTP | Lead capture, OTP send/verify, addresses, nullable email/password | 2026-05-02 (migration) | 2026-05-04 (commit `535f650`) |
| Phase 2.5a — Real orders + payments | `orders` / `order_items` / `payment_transactions` / `service_centers`; 5-state order flow; anti-fraud | 2026-05-04 (migration + commit `45e7658`) | 2026-05-04 |
| Phase 2.5b — Real coupon system | `coupons` + `coupon_usages` + `coupon_id` on carts/orders; CouponService; /coupons page | 2026-05-04 (commit `0605e8b`) | 2026-05-04 |
| Phases 2.5.1 → 2.5.10 — UX fixes | Routing flicker, VehicleBadge, slot layout, sub-nav scrollspy iterations | 2026-05-04 → 2026-05-05 | 2026-05-05 (commit `ed7521f`) |
| Phase 2.6 — Code-splitting + test harness | Vite chunk splitting, Pest+Playwright harness, edge-case coverage | 2026-05-06 (commits `2e7c110` → `b85015b`) | 2026-05-06 |
| Phase 3A/3B — React-router migration | Shim then pure react-router-dom v7 | 2026-05-06 → 2026-05-07 | 2026-05-07 (commit `120eb7a`) |
| Phase 4.5 — SEO Pages + Explore editorial | `seo_metadata`, `site_seo_settings`, `url_redirects`, `seo_pages` (+5 enhancement migrations), Explore page, sitemap.xml | 2026-05-08 → 2026-05-09 (migration prefixes) | 2026-05-09 |
| Phase 4.7 — Demo-readiness polish | Testimonials page, FAQ accordion, FAQ v2/v3 redesigns | 2026-05-05 reports show; some commits cover later screenshots | 2026-05-05 |
| Imports + audit columns | `faqs`, `imports`, `service_column_mappings`, `add_auto_create_audit_columns` | 2026-05-12 → 2026-05-13 (migrations) | 2026-05-13 |
| BS-3 / Backend perf + selectors / vehicle rebuild | Slow-data fix, skeleton fix, cart/session loss; selector consolidation; vehicle rebuild; image upload recovery; pricing-matrix fixes | 2026-05-20 → 2026-05-23 (mtimes) | 2026-05-23 |
| Coupon polish (compact, guest preview, gate fix) | Sidebar slim strip; guest can apply + see discount; second gate fix | 2026-05-23 → 2026-05-25 (mtimes) | 2026-05-25 |
| Service Pages Phase 1 (data) | `service_inclusions` + `interval_info` + Filament editor fields | 2026-05-24 (migrations) | 2026-05-25 (report) |
| Service Pages Phase 1.5 — grouping | `group_name` column + Filament Select + API field + `inclusions:autogroup` command | 2026-05-24 (migration) | 2026-05-25 |
| Service content import | `service-content:import` (old→acr_v3 by slug, additive/NULL-only + tests) | 2026-05-25 | 2026-05-25 |
| Service Pages Phase 2 series (a→e) | Backend `inclusions_preview` → category redesign → ServicesShell → Layer-3 detail → tabs → polish → GoMechanic-clean polish | 2026-05-25 (PHASE2 report) | 2026-05-27 (PHASE2E report) |
| Task time-tracking + this audit | Set up TASK_TIMINGS.md; comprehensive read-only audit | 2026-05-29 | 2026-05-29 (in flight) |

---

## 2 · REPORTS FOUND (evidence of completed phases)

32 evidence files at repo root (+ `README.md` and `TASK_TIMINGS.md`). One-liners taken from each report's opening lines; verdict inferred from the report's own status header.

| # | File | Date (mtime) | One-line summary | Verdict |
|---|------|-------|------------------|---------|
| 1 | `BS3_DIAGNOSTIC_AND_FIXES.md` | 2026-05-21 | Phase BS-3 — slow backend, skeleton shrink, multi-step crashes; diagnostic + fixes | Done |
| 2 | `BACKEND_PERF_REPORT.md` | 2026-05-21 | Backend perf diagnostic + minimal index-justified fixes; no migrations added | Done |
| 3 | `FORMS_CONSOLIDATION_REPORT.md` | 2026-05-21 | FORMS-1 — right form on right page + vehicle-selector consolidation (frontend only) | Done |
| 4 | `VEHICLE_REBUILD_REPORT.md` | 2026-05-21 | REBUILD-VEHICLE — three duplicate selectors → 3 shared (`VehicleSelector`, `CarSidebar`, `HomeCarSelector`) | Done |
| 5 | `VEHICLE_FIX1_REPORT.md` | 2026-05-21 | CarSidebar width parity + Home blank-space fix (CSS-only) | Done |
| 6 | `VEHICLE_FIX2_REPORT.md` | 2026-05-21 | Remove silent auto-add to cart; require explicit "Add to cart" click | Done |
| 7 | `L2_BULK_IMAGE_REPORT.md` | 2026-05-21 | Bulk image upload — 1 ZIP → filename-matched assignment + dry-run + commit | Done |
| 8 | `IMAGE_UPLOAD_FIX_REPORT.md` | 2026-05-22 | Bulk page redesign + inline resource uploads + `fuel_types.image` column | Done |
| 9 | `IMAGE_URL_FIX_REPORT.md` | 2026-05-22 | L1 API returns fully-qualified storage URLs (centralized helper) | Done |
| 10 | `SELECTOR_CONVERGENCE_REPORT.md` | 2026-05-22 | Vehicle selector reads L1 public endpoints (full image URLs, no 404s) | Done |
| 11 | `IMAGE_SYSTEM_FIXES_REPORT.md` | 2026-05-22 | Model/fuel images + smart messy-filename matcher + list-view uploads | Done |
| 12 | `FILEUPLOAD_RECOVERY_REPORT.md` | 2026-05-22 | Stabilize Filament image upload on entity resources (preview hydrates, no duplicate-ext orphans) | Done |
| 13 | `SELECTOR_DENSITY_REPORT.md` | 2026-05-23 | GoMechanic-parity visual density pass on `VehicleSelector` grids | Done |
| 14 | `MODEL_FUEL_SCOPE_REPORT.md` | 2026-05-23 | Fuel step filtered by valid pricing combinations for the chosen model | Done |
| 15 | `MANUAL_ENTRY_CONTACT_REPORT.md` | 2026-05-23 | Manual vehicle-entry users rerouted to `/contact` (prefilled) | Done |
| 16 | `MANUAL_ENTRY_FLOW_REPORT.md` | 2026-05-23 | Homepage Check-Price form + manual car entry bugs fixed | Done |
| 17 | `SIDEBAR_REPLICA_REPORT.md` | 2026-05-23 | Service-page sidebar as GoMechanic visual replica | Done |
| 18 | `SIDEBAR_REPLICA_FIX_REPORT.md` | 2026-05-23 | Correct prior pass divergences (corners, photo size, density, checkout) | Done |
| 19 | `COUPON_COMPACT_REPORT.md` | 2026-05-23 | Slim single-line coupon strip in sidebar (~39 px, was ~64 px) | Done |
| 20 | `COUPON_GUEST_PREVIEW_REPORT.md` | 2026-05-25 | Guests can apply a coupon + see discount; checkout still gated | Done |
| 21 | `COUPON_CLIENT_GATE_FIX_REPORT.md` | 2026-05-25 | Removed the second gate (slider Apply disabled for guests) | Done |
| 22 | `SERVICE_PAGES_AUDIT.md` | 2026-05-25 | Pre-redesign structural audit; zero code changes | Diagnostic only |
| 23 | `SERVICE_DATA_PHASE1_REPORT.md` | 2026-05-25 | Service Pages Phase 1 — `service_inclusions` + `interval_info` + Filament editor | Done |
| 24 | `SERVICE_IMPORT_DIAGNOSTIC.md` | 2026-05-25 | Old acr2025 → current acr_v3 read-only diagnostic; throwaway parser | Diagnostic only |
| 25 | `SERVICE_INCLUSIONS_GROUPING_REPORT.md` | 2026-05-25 | Phase 1.5 — `group_name` column + Filament + API + `inclusions:autogroup` | Done |
| 26 | `SERVICE_CONTENT_IMPORT_REPORT.md` | 2026-05-25 | `service-content:import` (old→acr_v3 by slug, NULL-only) + idempotency + 8 Pest tests | Done — real run executed |
| 27 | `SERVICE_PAGES_PHASE2_REPORT.md` | 2026-05-25 | Phase 2a — backend `inclusions_preview` + shared blocks + `ServiceCategory` rebuild | Done |
| 28 | `SERVICE_PAGES_PHASE2B_REPORT.md` | 2026-05-25 | Phase 2b — initial `ServicesShell` build (preceded the routing-wire continuation) | Done |
| 29 | `SERVICE_PAGES_PHASE2B_CONT_REPORT.md` | 2026-05-26 | Phase 2b-cont — wire `ServicesShell` into routing; Layer-3 detail rebuild; persistence proof | Done |
| 30 | `SERVICE_PAGES_PHASE2C_REPORT.md` | 2026-05-26 | Phase 2c — Layer-1 active-category tabs + shared `ServiceCard` extraction | Done |
| 31 | `SERVICE_PAGES_PHASE2D_REPORT.md` | 2026-05-26 | Phase 2d — four polish fixes (icon bar below banner, dropped pill, etc.) | Done |
| 32 | `SERVICE_PAGES_PHASE2E_REPORT.md` | 2026-05-27 | Phase 2e — GoMechanic-clean polish + 2d closeout (contained bar, "+N more" expands inline) | Done |

**Verdict tallies:** **29 Done · 3 Diagnostic-only · 0 Stopped at checkpoint.**

---

## 3 · MIGRATIONS LEDGER

`php artisan migrate:status` (against `acr_v3`):
**48 migrations total — ALL `Ran`. 0 Pending. 24 batches.**

Grouped by timestamp prefix (project-specific only; the 4 Laravel defaults are batch 1):

| Group | Migrations | Date |
|---|---|---|
| Phase 1 schema (batch 1) | `create_service_categories_table`, `create_services_table`, `create_car_brands_table`, `create_car_models_table`, `create_fuel_types_table`, `create_service_prices_table`, `create_pages_table`, `create_sections_table` | 2026-05-01 |
| Auth (batches 2–3) | `extend_users_for_auth`, `create_otp_verifications_table` | 2026-05-02 |
| Cart + nullable login (batches 4–6) | `create_addresses_table`, `make_users_email_password_nullable`, `create_carts_table`, `create_cart_items_table` | 2026-05-03 |
| Orders (batch 7) | `create_service_centers_table`, `create_orders_table`, `create_order_items_table`, `create_payment_transactions_table` | 2026-05-04 |
| Coupons (batch 8) | `create_coupons_table`, `create_coupon_usages_table`, `add_coupon_id_to_carts_table`, `add_coupon_fk_to_orders_table` | 2026-05-05 |
| is_admin (batch 9) | `add_is_admin_to_users_table` | 2026-05-07 |
| SEO core (batches 10–11) | `create_seo_metadata_table`, `create_site_seo_settings_table`, `create_url_redirects_table`, `create_seo_pages_table` | 2026-05-08 |
| SEO enhancements (batches 12–14) | `add_searchable_text_to_seo_pages`, `add_featured_and_views_to_seo_pages`, `create_seo_page_categories_table`, `create_seo_page_related_table`, `enhance_seo_pages_for_explore_editorial` | 2026-05-09 |
| Newsletter created+dropped (batches 15–16) | `create_newsletter_subscriptions_table`, `drop_newsletter_subscriptions_table` | 2026-05-09 (same day) |
| Leads (batch 17) | `create_leads_table` | 2026-05-09 |
| FAQs + Imports + Mappings (batches 18–19) | `create_faqs_table`, `create_imports_table`, `create_service_column_mappings_table` | 2026-05-12 |
| Audit columns (batch 20) | `add_auto_create_audit_columns` | 2026-05-13 |
| Vehicle pivot (batch 21) | `add_segment_and_model_fuel_pivot` | 2026-05-20 |
| Entity images (batch 22) | `add_image_to_entities`, `add_image_to_fuel_types` | 2026-05-21–22 |
| Service Phase 1 + 1.5 (batches 23–24) | `create_service_inclusions_table`, `add_interval_info_to_services_table`, `add_group_name_to_service_inclusions_table` | 2026-05-24 |

---

## 4 · ARTISAN COMMANDS BUILT

7 custom commands in `backend/app/Console/Commands/`:

| Command | Signature | Purpose |
|---|---|---|
| `cars:import` | `ImportCarList.php` | Wipe brands/models/fuels and re-seed them from a [Brand, Model, Fuel, Segment] xlsx |
| `pricing:import` | `ImportPricingMatrix.php` | Auto-bootstrap + import a pricing matrix Excel file |
| `service-content:import` | `ImportServiceContent.php` | Import old service content (inclusions, time, pattern-validated warranty/recommended/interval) into acr_v3 by slug. Additive + NULL-only + re-runnable |
| `inclusions:autogroup` | `AutogroupInclusions.php` | Auto-classify ungrouped service inclusions into Essential/Performance/Additional (NULL-only, re-runnable) |
| `acr:normalize-image-paths` | `NormalizeImagePaths.php` | Normalize entity image column values to clean relative paths (repair malformed FileUpload state) |
| `orders:auto-confirm` | `AutoConfirmOrdersCommand.php` | Auto-confirm pending orders older than N minutes (default 120) |
| `perf:measure` | `PerfMeasure.php` | Time + query-count each public endpoint with realistic params |

---

## 5 · DATA POPULATED IN `acr_v3` (current state)

Confirmed via read-only SELECTs at audit time.

**Services**
- Total: **92** · with image: **0** · with description: **0** · with `time_takes`: **73** · with `warrenty_info`: **40** · with `recommended_info`: **19** · with `interval_info`: **5**

**Categories**
- Total: **13** · with `image`: **0** · `icon_image` column exists (count not exposed)
- Schema includes: `id, name, slug, description, image, icon_image, position, is_active, is_auto_created, auto_created_from, auto_created_import_id, reviewed_at, reviewed_by, include_in_sitemap, seo_enriched_at, created_at, updated_at`

**Inclusions**
- Total: **543** · with `group_name`: **543** (100%) · NULL: **0** → `inclusions:autogroup` has been run on the live DB

**Vehicle catalogue + pricing**
- `service_prices`: **52,521** rows · `car_brands`: **32** · `car_models`: **314** · `fuel_types`: **3**

**Users + orders**
- `users`: **21** (1 admin) · `orders`: **9** (2 confirmed + 7 cancelled — test data only, no production traffic)
- `order_items`: **10** · `payment_transactions`: **9** · `addresses`: **1**

**Coupons**
- `coupons`: **3** (all active) · `coupon_usages`: **2**

**SEO + content**
- `pages`: **3** · `sections`: **4** · `seo_metadata`: **32** · `site_seo_settings`: **1** · `url_redirects`: **0**
- `seo_pages`: **17** · `seo_page_categories`: **16** · `seo_page_related`: **0**
- `faqs`: **6**

**Operational**
- `service_centers`: **4** · `leads`: **10** · `imports`: **7** history entries · `service_column_mappings`: **94**
- `carts`: **1,487** (guest+user; almost certainly needs a sweep) · `cart_items`: **159**
- `otp_verifications`: **65** (audit log accumulation)

**Tables that DO NOT exist:** `testimonials`, `locations`, `business_info`, `model_fuel_type`. Locations content lives in `service_centers` (4 rows); testimonials + business info still hardcoded in `src/data/businessData.ts` (138 lines, exporting `LOCATIONS`, `BUSINESS_INFO`, `TESTIMONIALS`).

---

## 6 · FRONTEND ROUTES + PAGES

Source: `src/App.tsx`. All routes lazy-loaded except `/` (Home, eager).

| Path | Component | Inside ServicesShell? |
|---|---|---|
| `/` | `Home` (eager) | — |
| `/services` | `Services` | ✅ |
| `/services/:category/:service` | `ServiceDetail` | ✅ |
| `/category/:slug` | `ServiceCategory` | ✅ |
| `/service-centers` | `ServiceCenters` | — |
| `/center/:id` | `ServiceCenterDetail` | — |
| `/insurance` | `Insurance` | — |
| `/corporate` | `Corporate` | — |
| `/gallery` | `Gallery` | — |
| `/about` | `About` | — |
| `/contact` | `Contact` | — |
| `/offers` | `Offers` | — |
| `/coupons` | `Coupons` | — |
| `/sitemap` | `Sitemap` (HTML index page; XML sitemap is backend route `/sitemap.xml`) | — |
| `/cms-preview` | `CmsPage` | — |
| `/cart` | `Cart` | — |
| `/checkout` | `Checkout` | — |
| `/booking-history` + `/my-bookings` | `MyBookings` | — |
| `/testimonials` | `Testimonials` | — |
| `/order/:id` | `OrderDetail` | — |
| `/booking-confirmation/:id` | `BookingConfirmation` | — |
| `/not-found` | `NotFound` | — |
| `/explore` | `ExploreEditorial` | — |
| `/:slug` | `SeoPageView` (catch-all for SEO pages; reserved-slug guard preserves `/payment` etc. → NotFound) | — |
| `/*` | `NotFound` | — |

Backend sitemap route: `GET /sitemap.xml` → `SitemapController@index` (registered in `routes/web.php`, not under `/api/v1`, so crawlers reach it at root).

---

## 7 · ADMIN (Filament) RESOURCES

14 resources in `backend/app/Filament/Resources/`:

| Resource | Admins | Navigation group / label |
|---|---|---|
| `ServiceResource.php` | `Service` (services + inclusions Repeater) | — |
| `ServiceCategoryResource.php` | `ServiceCategory` | "Service Categories" |
| `ServiceCenterResource.php` | `ServiceCenter` | "Service Centers" |
| `ServiceColumnMappingResource.php` | `ServiceColumnMapping` | "Data Operations" / "Service mappings" |
| `CarBrandResource.php` | `CarBrand` | "Vehicle Catalogue" / "Brands" |
| `CarModelResource.php` | `CarModel` | "Vehicle Catalogue" / "Models" |
| `FuelTypeResource.php` | `FuelType` | "Vehicle Catalogue" / "Fuel types" |
| `CouponResource.php` | `Coupon` | — |
| `OrderResource.php` | `Order` | — |
| `UserResource.php` | `User` | — |
| `LeadResource.php` | `Lead` | — |
| `FaqResource.php` | `Faq` | "FAQs" |
| `SeoPageResource.php` | `SeoPage` | "SEO Pages" |
| `ImportResource.php` | `Import` (import history) | "Data Operations" / "Import history" |

**No admin resources for:** Testimonials, Locations (separate from service centers), Pages/Sections (CMS), SEO Metadata (polymorphic — likely managed inline per resource), Site SEO Settings, URL Redirects.

---

## 8 · TEST COUNTS — VERIFIED PASSING

**Backend (Pest):**
- Test files: **75** (`backend/tests/{Feature,Unit}/**/*Test.php`)
- Live run: `./vendor/bin/pest --testdox` → **OK (317 tests, 1327 assertions)** — all green.

**Frontend (Playwright):**
- `npx playwright test --list` → **Total: 137 tests in 30 files**
- Per-project flags exist in `playwright.config.ts`: `phase2`, `phase4_5c`, `phase4_7_3`, `phase4_7_4`, `phase4_7_5`, plus the default smoke project. Per-project counts not enumerated here (single-line totals were sufficient for the audit; ask if a per-project breakdown is needed for the manager).

---

## 9 · HONEST PRE-LAUNCH GAP ANALYSIS

Based purely on §1–8 evidence.

### TRUE LAUNCH BLOCKERS

| Gap | Evidence | Size | Why blocker |
|---|---|---|---|
| **Service images (92/92 services have NO image)** | `services.image` populated count = 0 (and same for `description`) | M | Cards render with `ExploreCardFallback` (designed-for fallback), so it's not visually broken — but for a public launch with paying customers, the catalog being entirely image-less is reputationally weak. The bulk-image-upload tooling (`L2_BULK_IMAGE_REPORT`) and inline Filament upload exists; operator just needs to upload the asset pack. |
| **Service descriptions (92/92 services have empty `description`)** | `services.description` populated count = 0 | M | SEO + UX. Filament editor field exists since Phase 1 (`SERVICE_DATA_PHASE1_REPORT`); content not yet authored. |
| **Category images (13/13 categories have no image; icon_image not checked here)** | `service_categories.image` populated count = 0 | S | Layer-1 category bar relies on icon_image — if those are populated the bar works; if not, fallback glyph map in `src/components/service/categoryIcon.ts` carries it. Worth verifying icon_image population. |
| **Static content still hardcoded in frontend (`LOCATIONS`, `BUSINESS_INFO`, `TESTIMONIALS`)** | `src/data/businessData.ts` (138 lines) exports all three; no `testimonials` / `locations` / `business_info` tables exist | M | `LOCATIONS` overlaps with `service_centers` (4 rows in DB) — these should be reconciled. `TESTIMONIALS` has no backend table at all. Per the project's stated architecture ("Target architecture is full API-driven CMS — backend single source of truth, frontend pure consumer, no static fallbacks" — from MEMORY.md), this is a direction-violation that should be closed before launch. |
| **No CI/CD pipeline** | `.github/workflows/` does not exist | M | Push-to-deploy automation absent. Deploy is currently a manual ZIP-and-upload (see `/deploy/README.md`). For launch you can ship without CI, but post-launch you'll want it before the first hotfix cycle. |
| **Orders table only has test data** | 9 orders total; 2 confirmed / 7 cancelled (no production traffic) | — | Not a build gap — just reality. The flow is wired end-to-end (Phase 2.5a). |
| **`carts` table has 1,487 rows** | Direct count | S | Almost certainly stale guest carts; needs a `carts:prune` command or scheduled cleanup before launch (no such command exists in §4). |

### LIKELY NICE-TO-HAVES (post-launch)

| Gap | Evidence | Size | Notes |
|---|---|---|---|
| **WhatsApp integration** | Floating button in `App.tsx` opens `wa.me/...` URL only; no server-side WhatsApp Business API | M | Currently click-to-chat only. Real WhatsApp templates / receipts / OTP-via-WhatsApp are post-launch. |
| **Real payment gateway** | `payment_transactions` table exists; only "Pay at Center" placeholder per `Phase 2.5a` report | M | Online payments deferred — operator confirmed "Pay at Center" was intentional for launch. |
| **Refund flow** | `OrderResource` admin exists; no dedicated refund tooling visible | S–M | Manual refund via admin order actions today. |
| **Role-based admin access** | `users.is_admin` is a boolean (added 2026-05-07); single admin user in DB | S–M | Fine-grained roles (operator, manager, content editor) not implemented. |
| **`url_redirects` table is empty** | 0 rows | S | Available for SEO redirect rules; nothing seeded yet. Only matters if you have legacy URLs to redirect from. |
| **Newsletter subscriptions** | Migration created (2026-05-09) then dropped (same day) | — | Already explicitly removed. Not a gap. |
| **Admin Resources for Testimonials / Locations / Pages / Sections** | No resource files exist for these tables | M | Tied to the static-content-in-frontend gap — if you migrate those to DB, you need admin to manage them. |
| **Sitemap content size** | `seo_pages`: 17 rows; sitemap.xml is wired but lean | S | Content authoring task, not engineering. |

### ALREADY DONE (sometimes mistakenly listed as pending)

| Item | Evidence |
|---|---|
| SEO Pages system | 4 SEO migrations + 4 enhancement migrations all `Ran`; `seo_pages` has 17 rows, `seo_metadata` 32 rows; `/sitemap.xml` route wired in `routes/web.php`; admin `SeoPageResource` exists |
| Excel master-data importers | `cars:import` + `pricing:import` + `service-content:import` exist as artisan commands; ServicesImport via Filament `ImportResource` + `ServiceColumnMappingResource` (94 mappings in DB); ZIP-based `L2_BULK_IMAGE_REPORT` workflow shipped |
| Bulk image upload with slug-mapping | `L2_BULK_IMAGE_REPORT` (2026-05-21) + `IMAGE_UPLOAD_FIX_REPORT` (2026-05-22) — bulk page redesign + smart messy-filename matcher (`SmartMatcherTest`, `BulkImageMatcherTest`, `FuzzyMatcherTest` all in test list) |
| Hostinger deploy readiness | `/deploy/` folder exists with `.env.production.template`, `.htaccess`, `app.htaccess`, `index.php`, README.md (Hostinger-specific layout instructions, hPanel target) — deploy is "documented + bundled", just not automated via CI |
| Service catalogue data import | `service-content:import` real run executed (per `SERVICE_CONTENT_IMPORT_REPORT.md` PART C); DB now has 92 services with 543 inclusions, 73 with duration, 40 with warranty, etc. — matches the import report exactly |

---

## 10 · CONTRADICTIONS / TRUTHS WORTH CALLING OUT

These are points where current evidence diverges from things that might still appear in working notes or earlier briefs.

1. **"Inclusions need to be autogrouped" → NO, they already are.** All 543 rows in `service_inclusions` have `group_name` set (NULL count = 0). The `inclusions:autogroup` command has been run on the live DB. Any task plan that still includes "run autogroup" is stale.

2. **"Service content import is pending" → NO, the real run was committed.** Commit `3e40c1a` (2026-05-25) + `SERVICE_CONTENT_IMPORT_REPORT.md` PART C confirms the import executed in one transaction; DB counts (92 services, 543 inclusions, 73 time / 40 warranty / 19 recommended / 5 interval) match the dry-run plan exactly. Idempotency was also verified — a second run is a no-op.

3. **"Phases 2b through 2e are pending" → NO, all five Phase 2 reports exist + are verified.** Phase 2a/2b/2b-cont/2c/2d/2e reports are all "Status: DONE and screenshot-verified" with test counts that climb 3/3 → 9/9 → 14/14 across the series. The catch: **these phases are uncommitted** (working tree only, see §1) — operator needs to commit them before any deploy.

4. **"Service images are populated" → NO, `services.image` is 0/92.** The cards LOOK populated in the screenshots because `ExploreCardFallback` (navy panel + lucide icon + ACR watermark) is doing all the work. Same for `services.description` (0/92). Bulk upload tooling is ready; the content isn't.

5. **"Categories need icons" → PARTIALLY.** `service_categories.image` is 0/13; `icon_image` count wasn't queried here but the column exists and the Phase 2d/2e reports describe the bar rendering "real icons", which implies icon_image IS populated for most. Worth one more SELECT before claiming closure.

6. **"Testimonials/Locations are in the DB" → NO.** Tables don't exist. They are still in `src/data/businessData.ts` as static TypeScript exports. This contradicts the stated full-API-driven-CMS architecture (memory: `project_architecture_direction.md`) — flag for the manager as a known direction-debt, not silent debt.

7. **"`service_centers` covers locations" → PARTIALLY.** 4 rows in `service_centers`; `LOCATIONS` in `businessData.ts` is a separate UI-marketing list. The two surfaces have not been reconciled.

8. **"There's CI on push" → NO.** No `.github/workflows/` directory. Deploy is the manual Hostinger procedure documented in `/deploy/README.md`. If a manager assumes "tests run on PR", correct that — tests run locally + as part of the operator's release ritual.

9. **"Carts get pruned" → NO command for it.** 1,487 rows in `carts` (vs 159 `cart_items`) suggests most are empty/abandoned. There is no `carts:prune` artisan command (see §4). Add one before launch or this table will grow unboundedly.

10. **"Orders flow is untested in production" → CORRECT (and explicit).** 9 total orders, 7 cancelled. Flow is wired (Phase 2.5a backend + Phase 2.5.x UX fixes) but has not been exercised by real users yet — soft-launch / friendly-user test pass is the natural next gate.

---

## Audit method + scope reminder

- All counts in §5 are point-in-time at **2026-05-29 ~11:45 IST**.
- §3 ledger comes from `php artisan migrate:status` against `acr_v3` (the dev DB, but the same migrations are intended for production per the `production DB reused as-is, additive migrations only` memory).
- §8 test counts come from a fresh `pest` run + `playwright test --list` (no test was skipped or filtered).
- This was strictly read-only: no migrations, no writes, no installs, no destructive commands. The audit doc is the only file created.

---

**End of audit.** Next step on the operator's side: review §9 + §10 with the manager and turn the Tier-1 gaps into a small punch-list (image/description content authoring + `carts:prune` command + decision on hardcoded testimonials/locations + commit-and-push the 2b → 2e tree).
