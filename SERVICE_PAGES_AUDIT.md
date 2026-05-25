# SERVICE_PAGES_AUDIT — current structure before the GoMechanic-style redesign

**Audit only. Zero code changes were made.** All findings below come from reading
the source + a **read-only** `SELECT` against the local dev DB (`127.0.0.1/acr_v3`,
`APP_ENV=local` — not production).

**Headline feasibility verdict (read this first):** The schema already supports a
GoMechanic-style card (image + duration + warranty + recommended-when + description
all exist as columns and are emitted by the API end-to-end). **But in the actual
data, those fields are 0% populated** — 0 of 92 services have an image, duration,
warranty, recommended, description, or note; 0 of 13 categories have an image. What
*is* rich is **pricing** (52,521 `service_prices` rows; all 92 services priced
per-vehicle). So the redesign is **structurally feasible with no schema change for
image/duration/warranty/description**, but is **blocked on content/data entry** —
it needs either a data-population pass or graceful image-less fallbacks (one already
exists: `ExploreCardFallback`). "What's included" and "every N km" have **no column
at all** and would need new data work if they must be structured/per-service.

---

## 1. The 3 page types — routes, components, file paths

| Page type | Route (App.tsx) | Component | Data hook → endpoint |
|---|---|---|---|
| **All-services landing** | `/services` (`App.tsx:134`) | `src/pages/Services.tsx` | `fetchServices(brand_id,model_id,fuel_id)` → `GET /api/v1/services` |
| **Category page** (sub-services of one category) | `/category/:slug` (`App.tsx:136`) | `src/pages/ServiceCategory.tsx` | `fetchCategoryDetail(slug, brand/model/fuel **slugs**)` → `GET /api/v1/services/{slug}` |
| **Sub-service detail** | `/services/:category/:service` (`App.tsx:135`) | `src/pages/ServiceDetail.tsx` | `fetchServiceDetail(categorySlug, serviceSlug, brand_id/model_id/fuel_id)` → `GET /api/v1/services/{categorySlug}/{serviceSlug}` |

> Note the slug shapes differ by page: `/category/:slug` is the category page, while
> `/services/:category/:service` is the sub-service detail. The example URLs in the
> task brief (e.g. `/car-battery`, `/car-battery-replacement`) are **not** how this
> app routes — categories live under `/category/...` and sub-services under
> `/services/.../...`. (Bare single-segment slugs like `/car-battery` fall through
> to `SeoPageView` at `App.tsx:166`, a different CMS surface.)

How they link to each other (all via React Router, client-side):
- Landing → detail: `Services.tsx:284` `onViewDetail` → `navigate('/services/${category.slug}/${subSlug}')`.
- Landing → category: `Services.tsx:287` `onViewCategory` → `navigate('/category/${category.slug}')` ("View Details" link per section).
- Category → detail: `ServiceCategory.tsx:597` row title button → `navigate('/services/${category.slug}/${sub.slug}')`.
- Detail → related: `ServiceDetail.tsx:756` card → `navigate('/services/${category.slug}/${related.slug}')`.

Backend routes: `backend/routes/api.php:49-51`
(`services`, `services/{slug}`, `services/{categorySlug}/{serviceSlug}` →
`Api\V1\ServiceController@index / show / detail`).

---

## 2. Navigation mechanism — why a sub-service click *feels* like a full reload

**It is NOT a full page reload.** Navigation is 100% client-side React Router v7:
- Router: `react-router-dom` `<Routes>` in `App.tsx:132-174`.
- All service pages call `useNavigate()` and use `navigate('/services/...')` on button
  `onClick` (the row titles are `<button>`s, not `<a>`s). The `explore/` cards use
  `<Link>` instead — both are SPA navigation, no `<a href>` hard nav anywhere in the
  service flow.

So why does it feel jarring? Four things stack on every route change:
1. **Page-transition animation** (`App.tsx:116-122`): `AnimatePresence mode="wait"`
   keyed on `location.pathname` — the old page animates **out** (opacity→0, y→-20)
   over 0.4s, *then* the new page animates **in** (opacity 0→1, y 20→0). `mode="wait"`
   means the new page can't appear until the exit finishes — a deliberate ~0.4–0.8s
   gap that reads like a page swap.
2. **Scroll-to-top** on every pathname change (`App.tsx:84-86`) — the viewport jumps
   to the top, reinforcing "new page."
3. **Lazy-loaded route chunks** (`App.tsx:16-40`, `Suspense` at `:131`): the first
   visit to `ServiceDetail`/`ServiceCategory` downloads a separate JS chunk and shows
   `GlobalLoadingFallback` while it fetches.
4. **Per-page skeleton + fresh API fetch**: each page runs its own query and renders
   a full-page skeleton (`ServiceDetail.tsx:118-133`, `ServiceCategory.tsx:200-220`)
   until the API resolves. So the sequence is: exit anim → (maybe chunk fetch) →
   mount → skeleton → API → content.

Net: clicking a sub-service = animated unmount + scroll jump + (chunk) + skeleton +
refetch. Functionally SPA, perceptually a full reload. **A GoMechanic-style "data
swaps in place" feel would mean reducing/removing the exit animation, keeping shared
chrome (sidebar/sub-nav) mounted, and prefetching/caching the detail data** (see §9).

---

## 3. Current layout structure — horizontal + vertical, and why the page is long

**All-services landing (`Services.tsx`):**
- **Sticky horizontal category nav** (`Services.tsx:190-227`): a horizontally
  scrolling strip of category buttons, scroll-spy-driven by `useSubNavSync`
  (`src/hooks/useSubNavSync.ts`) — clicking smooth-scrolls to that category's section;
  scrolling updates the active underline.
- **Vertical stack of category sections** (`Services.tsx:256-292`): the main column
  renders **one `<CategorySection>` per category, and every category renders ALL of
  its sub-services as table rows** (`CategorySection`, `:366-553`). No pagination, no
  lazy reveal, no tabs — **every category and every sub-service is in the DOM at
  once**. With 13 categories / 92 services that's why the page is very long.
- 2-column grid: `lg:col-span-2` main content + `CarSidebar` right (`:232`).

**Category page (`ServiceCategory.tsx`):** long for a different reason — it renders a
**price-list table** (`:563-696`, all sub-services of that one category) *plus* many
**static SEO/marketing sections**: Overview, Services Included, Why Choose Us,
Process, Reviews, FAQs, Brands, "Why ACR in {city}" (`SECTION_NAV`, `:63-73`). Most of
that content is hardcoded, not data — it inflates length regardless of catalog size.

**Sub-service detail (`ServiceDetail.tsx`):** also long & mostly static — Overview,
Pricing (text), Services Included, Why Choose, Process, CTA strip, "Real Results"
(before/after **using one hardcoded Unsplash image twice**, `:366,635-656`), FAQs,
Related, Reviews, Recommended (`SECTIONS`, `:50-61`).

All three share the same **sticky horizontal sub-nav + vertical sections + right
`CarSidebar`** skeleton.

---

## 4. Sub-service row/card — exact fields currently rendered

**Landing (`Services.tsx` `CategorySection`, rows at `:415-549`):**
- **Name only** (`sub.title`, a clickable button → detail) + **price column**
  (vehicle price / "Select car" / "On Inspection") + **action** (Add to Cart / Call
  Now / Select Your Car). **No image. No description. No duration/warranty/inclusions.**
  It's a 3-column table row (`Service Type | Price From | Action`).

**Category page (`ServiceCategory.tsx` rows at `:570-695`):**
- `sub.title` (button → detail) + a **one-line description**: `sub.recommended_info`
  *or* a generated fallback string, `line-clamp-2` (`:604-607`) + price + action.
  **No image. No duration/warranty shown in the row.**

**Detail page (`ServiceDetail.tsx`):** shows more, but mostly **static or hardcoded**:
- From data: `service.title`, `service.time_takes` + `time_takes_option`/`time_unit`
  ("Time Required", `:447-457`), `service.warrenty_info` ("Warranty", `:459-465`),
  `service.recommended_info` ("Recommended When", `:468-477`), vehicle price (sidebar).
- **Hardcoded / static:** the hero/"Real Results" images are a single Unsplash URL
  (`heroImage`, `:366-367`); "Services Included" is a **static 6-item array identical
  for every service** (`serviceIncludes`, `:241-272`); "Why Choose", "Process",
  testimonials, FAQs are static/generated.
- **There is no real per-service image and no real per-service "what's included"
  list anywhere in the rendered output today.**

---

## 5. DATA availability — does a sub-service have image + what's-included + duration/warranty/km? (REAL sample)

**Schema (`backend/database/migrations/...create_services_table.php` + `Service` model):**
`services` columns = `category_id, name, slug, description(text), image(string),
base_price(decimal), time_takes(string), time_unit(string), warrenty_info(text),
recommended_info(text), note(text), is_active`.

**API emits all of it** — `ServiceResource` (detail/category, `:39-62`) returns
`description, image, time_takes, time_unit, warrenty_info, recommended_info, note` +
3 price fields; `SubServiceResource` (list, `:54-69`) returns `image, time_takes,
time_unit` + prices. (`image` is emitted **raw** — the literal DB string, no URL
transform.)

**Real data (read-only query against `acr_v3`):**
```
SERVICES_TOTAL      = 92        CATEGORIES_TOTAL          = 13
WITH_IMAGE          = 0         CATEGORIES_WITH_IMAGE     = 0
WITH_TIME_TAKES     = 0         CATEGORIES_WITH_ICON_IMAGE= 0
WITH_WARRANTY       = 0
WITH_RECOMMENDED    = 0         SERVICE_PRICES total      = 52,521
WITH_DESCRIPTION    = 0         services with >=1 price   = 92  (all)
WITH_NOTE           = 0
```
Real sample sub-service row (raw columns):
```json
{
  "id": 102, "category": "Regular Car Service",
  "name": "primary service", "slug": "primary-service",
  "description": null, "image": null, "base_price": null,
  "time_takes": null, "time_unit": null,
  "warrenty_info": null, "recommended_info": null, "note": null,
  "is_active": true
}
```

**Field-by-field verdict for a GoMechanic card:**

| GoMechanic element | Column exists? | Populated? | Verdict |
|---|---|---|---|
| Image / hero | ✅ `services.image` (+ `service_categories.image`, `icon_image`) | ❌ 0/92 (cats 0/13) | Needs data entry, or fallback (`ExploreCardFallback`). |
| "What's included" / inclusions | ❌ no column / no relation | — | **Needs schema work** (new `service_inclusions` table or JSON col) OR keep static. Today it's a hardcoded array. |
| Description | ✅ `services.description` | ❌ 0/92 (categories DO have descriptions) | Needs data entry per service. |
| Duration ("4 hrs") | ✅ `time_takes` + `time_unit` | ❌ 0/92 | Needs data entry. Render path already exists in `ServiceDetail`. |
| Warranty ("1 month") | ✅ `warrenty_info` | ❌ 0/92 | Needs data entry. |
| Interval / km ("every 5000 km") | ❌ no column | — | **Needs schema work** or fold into `note`/`description`. |
| Price (per vehicle) | ✅ `service_prices` | ✅ 52,521 rows, all 92 priced | **Fully available** — the strongest data we have. |

**Bottom line:** image/duration/warranty/description/recommended are **0% filled but
zero-migration to surface** (columns + API already there) → redesign can render them
the moment an operator fills them, with fallbacks until then. **Inclusions** and
**km/interval** are the only truly missing pieces and need either a migration
(additive only — see memory `project_data_safety`) or a "keep it static / put in
`note`" decision.

---

## 6. Category → sub-service data structure

- `ServiceCategory` hasMany `Service` (`Service.category()` belongsTo `category_id`;
  `ServiceController@index` eager-loads `services`). One service belongs to exactly
  one category (`services.category_id`, unique `[category_id, slug]`).
- `ServiceCategory` columns surfaced: `slug, name/title, description, image, icon_image,
  position` (`ServiceCategoryResource`). Ordered by `position` then `id`.
- List endpoints (`/home`, `/services`) nest sub-services under each category via
  `SubServiceResource` (lean shape). The per-slug detail endpoints return the full
  `ServiceResource` shape in a top-level `services[]` (category page) or single
  `service` (detail page).
- **The 13 real categories (slug | name | #services):**

| slug | name | #services |
|---|---|---|
| car-battery | Car Battery | 2 |
| car-emergency-services | Car Emergency Services | 3 |
| car-insurance-claim | Car Insurance Claim | 2 |
| car-repairs-inspection | Car Repairs & Inspection | 19 |
| car-suspension-work | Car Suspension Work | 8 |
| car-clutch-work | Car Clutch Work | 5 |
| car-lights-and-glass-work | Car Lights and Glass Work | 7 |
| car-care-detailing | Car Care & Detailing | 9 |
| car-denting-painting | Car Denting & Painting | 16 |
| car-brake-wheel-maintenance | Car Brake & Wheel Maintenance | 9 |
| car-ac-service-repair | Car AC Service & Repair | 3 |
| regular-car-service | Regular Car Service | 3 |
| car-inspection | Car Inspection | 6 |

(Matches the operator's pasted list.) Categories carry **descriptions** but **no
images**.

---

## 7. Reusable components for the redesign (optimize, don't recreate)

Already in the codebase and directly relevant:

- **`src/components/explore/ExploreCard.tsx`** — the closest thing to a GoMechanic
  service card. Image card with **6 size/layout variants** (`stack` full-bleed
  overlay, `horizontal`, `large-stacked` = image-on-top + text panel), hover-lift +
  image-zoom, category badge, meta line, built on `<Link>` (true SPA nav). Currently
  typed to the SeoPage `ExploreCard` payload — reuse means generalizing its props or
  adapting a service-shaped payload.
- **`src/components/explore/ExploreCardFallback.tsx`** — **the no-image fallback
  card** (slate gradient + lucide icon by category + badge + title + "ACR"
  watermark). This is the graceful-degradation answer to "0% of services have
  images." Reusable as-is conceptually.
- **`src/components/explore/ExploreRail.tsx`** — horizontal auto-scroll rail
  (desktop autoplay, hover-pause, wheel-to-horizontal, arrows). Good base for a
  GoMechanic category/sub-service carousel.
- **`src/components/explore/ExploreFeaturedGrid.tsx`** + **`CategoryFilterChip.tsx`** —
  responsive card grid + a chip/tab component for category filtering (tab UI).
- **`src/hooks/useSubNavSync.ts`** — sticky horizontal scroll-spy + auto-scroll +
  click-to-smooth-scroll. Already powers the in-page nav on all 3 pages; reusable for
  a sticky category tab bar that smooth-scrolls.
- **`src/components/car-sidebar/CarSidebar.tsx`** (+ `MobileShell.tsx`) — the shared
  vehicle/cart/coupon/checkout sidebar (see §8). Keep mounted across the redesign.
- **`src/components/PageBanner.tsx`** (title + breadcrumbs), **`FAQAccordion.tsx`**,
  **`SeoHead.tsx`** (per-page SEO from API `seo`), **`VehicleReplaceModal.tsx`**,
  **`EmptyState.tsx` / `ApiErrorState.tsx`** — all reusable, already used here.
- **Price-state machine** (the 4-state `no-vehicle / loading / price / no-price`
  logic) is duplicated across `Services`, `ServiceCategory`, `ServiceDetail` — a
  redesign could extract it into one shared row/card to remove the triplication.

No existing generic `ServiceCard` / `Tabs` component for the catalog specifically —
the `explore/*` set is the nearest reuse target.

---

## 8. Sidebar mounting on the 3 pages

Same shared component on all three (the already-styled vehicle/cart form):

| Page | Mount | Props |
|---|---|---|
| `Services.tsx:313` | `<CarSidebar stickyTopPx={180} className="lg:order-2" />` | no `currentService` → no auto-add; shows cart or "Select your car" empty state |
| `ServiceCategory.tsx:948` | `<CarSidebar categorySlug={categorySlug} stickyTopPx={180} className="lg:order-2" />` | category-scoped |
| `ServiceDetail.tsx:783` | `<CarSidebar currentService={service} vehiclePrice={…} categorySlug={…} stickyTopPx={180} className="lg:col-span-4" />` | passes the current service + resolved price |

- Imported from `src/components/car-sidebar` (`index.ts` → `CarSidebar.tsx`).
- It renders its **own** sticky `<aside>` + a fixed mobile bar (`MobileShell.tsx`), so
  each page just drops it in as the right grid column. Grid is `lg:grid-cols-3` (sidebar
  = 1 col) on Services/Category and `lg:grid-cols-12` (sidebar = `col-span-4`) on Detail
  — same effective width.
- It reads/writes the shared **booking context** (`useBookingContext`) so vehicle
  selection persists across the 3 pages; prices reveal on `hasVehicle` (per memory
  `project_forms_consolidation`). **Keep this mounted and untouched through the
  redesign** — it's the cross-page state anchor and the "smooth nav" win (it already
  survives navigation between the 3 routes).

---

## 9. Recommended approach (my read — for planning only, nothing implemented)

**A. Data first — it's the gating item.** The layout can be built, but a GoMechanic
card is empty without content. Two tracks, not mutually exclusive:
  1. **Populate existing columns** (no migration): `image`, `description`,
     `time_takes`+`time_unit`, `warrenty_info`, `recommended_info` per service — and
     `image`/`icon_image` per category. The API already ships these; the UI already
     has render slots for duration/warranty/recommended.
  2. **Only if structured "what's included" / "every N km" are required:** add them
     **additively** (memory `project_data_safety` — no drops/renames). Options: a
     `service_inclusions` child table (cleanest for a bulleted list) and a
     `service_interval_km` (or reuse `note`). Until then, keep the current static
     "Services Included" block or move it to category-level copy.
  - **Ship graceful fallbacks regardless**, so the redesign looks right at 0%
    population: reuse `ExploreCardFallback` for image-less cards; show "Duration:
    varies" / "Standard warranty" when null; hide empty rows. This matches what the
    pages already do for missing fields.

**B. Layout — reuse the `explore/` card system, don't invent.** Build the three pages
on `ExploreCard`/`ExploreCardFallback` + `ExploreRail`/`CategoryFilterChip`:
  - **Landing `/services`:** keep the sticky horizontal category bar
    (`useSubNavSync`), but instead of dumping all 92 rows, render category sections as
    **card grids** (image+name+duration+from-price) and/or rails. Consider **only
    rendering the active/near categories** (the all-at-once render is the "very long"
    cause) — e.g. tabbed categories or lazy-mount sections.
  - **Category `/category/:slug`:** lead with the **sub-service card grid** (the real
    catalog), demote the static SEO sections lower. Reuse the existing FAQ/SEO blocks.
  - **Detail `/services/:cat/:svc`:** real `service.image` hero with fallback,
    duration/warranty/recommended from data, real inclusions if track A.2 lands; keep
    the `CarSidebar` booking column.

**C. Smooth navigation — make data swap in place.** The pieces are already SPA; the
jank is presentation:
  1. Keep `CarSidebar` + the sticky category bar **mounted** across category/sub-service
     changes (shared layout) so only the inner content swaps — the biggest perceptual win.
  2. **Soften/remove the `mode="wait"` exit animation** for intra-catalog navigation
     (or scope `AnimatePresence` so the sidebar/nav don't unmount) — that 0.4s
     out-then-in gap is the main "full reload" feel.
  3. **Prefetch + cache detail data**: prime the React Query cache on row hover/visible
     (the list already has name/price), and reuse it as `placeholderData` on the detail
     route so it paints instantly instead of skeleton→fetch. Optionally drop the hard
     scroll-to-top for catalog-internal nav, or scroll smoothly.
  4. Switch row/card click targets to `<Link to=…>` (like `ExploreCard`) for
     prefetchable, accessible SPA links.

**Risk/constraint reminders for the plan:** additive migrations only; never touch
existing slugs (memory `project_seo_safety`); the redesign is data-blocked, not
code-blocked — sequence the content-entry work (and a Filament admin surface for it,
if not already present) alongside the UI.

---

### Files referenced
- `src/App.tsx` (routing, transitions) · `src/pages/Services.tsx` ·
  `src/pages/ServiceCategory.tsx` · `src/pages/ServiceDetail.tsx`
- `src/lib/api.ts` (types + endpoint helpers) · `src/hooks/useSubNavSync.ts` ·
  `src/components/car-sidebar/*` · `src/components/explore/*`
- `backend/routes/api.php` · `backend/app/Http/Controllers/Api/V1/ServiceController.php`
- `backend/app/Models/Service.php` ·
  `backend/database/migrations/2026_05_01_120002_create_services_table.php`
- `backend/app/Http/Resources/{ServiceResource,SubServiceResource,ServiceCategoryResource}.php`
