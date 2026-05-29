# ACR Platform — System Reconnaissance Audit

Generated: 2026-05-01 · read-only scan, no code changes.

---

## 1. REPO STRUCTURE

**Layout:** split (single repo, separate frontend + backend trees).
- Frontend root: `./` (sources under `src/`, package manifest at `./package.json`).
- Backend root: `./backend/` (Laravel app).
- A legacy `./old-backend/` is also present, untracked, used as historical reference only.

**Tooling versions detected (read-only):**
| Tool | Version |
|---|---|
| Node | v24.13.0 |
| npm | 11.13.0 |
| PHP | 8.2.12 (CLI) |
| Laravel framework (composer.json) | `^10.10` |
| Sanctum | `^3.3` |
| React | `^19.0.0` |
| Vite | `^6.2.0` |
| TypeScript | `~5.8.2` |
| Tailwind | `^4.1.14` |
| @tanstack/react-query | `^5.100.7` |
| Package manager (frontend) | npm (lockfile present) |

**Top-level folders (one-liner each):**
| Path | Purpose |
|---|---|
| `backend/` | Fresh Laravel 10 app (current API server). |
| `src/` | React/TS frontend sources. |
| `deploy/` | Hostinger deployment artifacts (`.htaccess`, `index.php`, env templates, README). |
| `old-backend/` | Legacy Laravel codebase preserved as reference; not loaded at runtime. |
| `dist/` | Build output of `npm run build` (created on demand; present from prior build). |
| `node_modules/` | Frontend dependencies. |
| `.claude/`, `.git/` | Tooling / VCS. |
| Root files | `package.json`, `vite.config.ts`, `tsconfig.json`, `index.html`, `.env.example`, `.env.local`, `composer.phar` (downloaded helper), `README.md`, `metadata.json`. |

---

## 2. FRONTEND DATA SOURCES

### 2.1 Static-data exports under `src/`

Only one file in `src/` exports static content:

**`src/data/businessData.ts`** — exports:
| Symbol | Line | Entity |
|---|---|---|
| `LOCATIONS` (array of 4) | 19 | Service centres (Moti Nagar, Gurugram, Noida, Okhla) — id, name, address, phone, image, etc. |
| `BUSINESS_INFO` (object) | 70 | Brand identity — name, tagline, phone, email, social links, trust points. |
| `CAR_DATA` (Record<brand, models[]>) | 93 | 12 brands → ~5–9 model strings each. Used as fallback in vehicle picker. |
| `TESTIMONIALS` (array of 6) | 108 | Hardcoded reviews — name, text, rating, initials. |
| `OfferCoupon` (interface) | 155 | Coupon shape. |
| `OFFERS` (array of 5) | 171 | Coupon definitions consumed by Cart/Checkout/Payment. |
| `computeCouponDiscount`, `pickBestOffer` (functions) | ~270 / ~290 | Pure coupon math. |

The same file (lines 296–470) **also defines a parallel API hook layer** (`useApiHome`, `useApiServiceCategories`, `useApiServiceCenters`, `useApiServiceCenter`, `useApiCarBrands`, `useApiCarModels`, `useApiCarFuels`, `useApiOffers`, `useApiFaqs`, `useApiServices`, `useApiServiceCategory`, `useApiServiceDetail`) plus a private `useResource<T>` helper that imports `apiGet` from `../lib/api`. These hooks are **dead code** — a project-wide grep finds zero consumers (only the file itself).

`src/pages/Offers.tsx:10` defines its **own local `const OFFERS = [...]`** (separate from the one in `businessData.ts`) — also static and hardcoded.

### 2.2 API call sites

All HTTP traffic flows through `src/lib/api.ts` (single file). Direct `fetch(...)` is used only inside that file (line 157, the underlying request). Outside, callers use `apiGet/apiPost/apiPut/apiDelete/apiUpload`.

**Base URL source:** `import.meta.env.VITE_API_BASE_URL` (Vite env), with a runtime resolver in `src/lib/api.ts` lines 14–62 that rewrites a local hostname (localhost / 127.0.0.1 / RFC1918) to match the current page origin. If the env var is empty it derives `${pageProto}//${pageHost}:8000/api/v1`.

**Endpoints called by the frontend:**

Defined as fetcher functions in `src/lib/api.ts`:
| Function | Method | Endpoint | Backend route exists? |
|---|---|---|---|
| `fetchHome` | GET | `/home` | ✅ |
| `fetchServices` | GET | `/services?brand_id&model_id&fuel_id` | ✅ |
| `fetchCategoryDetail` | GET | `/services/{slug}?brand&model&fuel` | ✅ |
| `fetchServiceDetail` | GET | `/services/{cat}/{svc}?brand_id&model_id&fuel_id` | ✅ |
| `fetchBrands` | GET | `/vehicle/brands` | ✅ |
| `fetchModels` | GET | `/vehicle/models?brand_id` | ✅ |
| `fetchFuels` | GET | `/vehicle/fuels?brand_id&model_id` | ✅ |
| `postPricing` | POST | `/pricing` | ✅ |
| `fetchPage` | GET | `/pages/{slug}` | ✅ |

Direct `apiGet/apiPost/apiPut` calls outside `lib/api.ts`:
| File | Line | Method | Endpoint |
|---|---|---|---|
| `src/hooks/useAuth.ts` | 204 | GET | `/user/profile` |
| `src/hooks/useAuth.ts` | 242 | POST | `/auth/register` |
| `src/hooks/useAuth.ts` | 270 | POST | `/auth/login` |
| `src/hooks/useAuth.ts` | 293 | POST | `/auth/logout` |
| `src/hooks/useAuth.ts` | 312 | PUT | `/auth/profile` |
| `src/hooks/useAuth.ts` | 348 | POST | `/user/addresses` |
| `src/hooks/useAuth.ts` | 372 | POST | `/checkout/offline` |
| `src/hooks/useCart.ts` | 86 | POST | `/cart/sync` |

**The 8 endpoints in this second table do NOT exist in `routes/api.php`** — see §9.

### 2.3 Hybrid / static-fallback rendering patterns

Found by grepping for static usage outside `businessData.ts` itself. The earlier "no matches" claim only held against a narrow `data || STATIC` regex; ternary-based fallbacks slip through. Real fallback sites:

| File:Line | Pattern | Fallback symbol |
|---|---|---|
| `src/components/BookingSidebar.tsx:186` | brand list = `apiBrands.length ? apiBrands : Object.keys(CAR_DATA)…` | `CAR_DATA` |
| `src/components/BookingSidebar.tsx:305–310` | models list = API result OR `CAR_DATA[pendingCar.brand]` | `CAR_DATA` |
| `src/pages/ServiceCategory.tsx:456–457` | same models fallback as above | `CAR_DATA` |
| `src/components/EstimateProcess.tsx:185–189` | `CAR_BRANDS = Object.keys(CAR_DATA)`, models pulled from `CAR_DATA[make]` | `CAR_DATA` (no API attempt at all) |

All other usages of `LOCATIONS`, `TESTIMONIALS`, `BUSINESS_INFO`, and `OFFERS` are **static-as-source-of-truth** (no API parallel exists), not fallbacks. Surface count of consumer files: 18 (App, Footer, Header, Home, Sitemap, ServiceCenters, ServiceCenterDetail, ServiceCategory, ServiceDetail, BookingSidebar, EstimateProcess, About, Cart, Checkout, Payment, Contact, Offers, Sitemap).

---

## 3. ENV & BUILD CONFIG

### 3.1 Frontend env

`.env.example` (root) — keys only, values redacted:
```
GEMINI_API_KEY      = "<set by AI Studio>"
APP_URL             = "<set by AI Studio>"
VITE_API_BASE_URL   = "http://localhost:8000/api/v1"   (default; non-secret)
```

`.env.local` (root) — present:
```
VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1
```
(no other keys; trailing whitespace observed)

`deploy/frontend.env.production` — production override template:
```
VITE_API_BASE_URL=https://acr-mechanics.in/api/v1
# VITE_BASE_PATH=/app/   (commented; defaults to /app/ in production mode)
```

### 3.2 Backend env

`backend/.env` — keys only, values redacted:
```
APP_NAME, APP_ENV=local, APP_KEY=base64:…, APP_DEBUG=true, APP_URL=http://localhost:8000
LOG_*
DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=acr_v3, DB_USERNAME=root, DB_PASSWORD=(empty)
BROADCAST_DRIVER=log, CACHE_DRIVER=file, FILESYSTEM_DISK=local, QUEUE_CONNECTION=sync, SESSION_DRIVER=file, SESSION_LIFETIME=120
FRONTEND_URL=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
IMPORT_API_TOKEN=dev-import-token-change-me     (dev placeholder — flagged in §10)
```

`deploy/.env.production.template` covers the production env: APP_ENV=production, APP_URL=https://acr-mechanics.in, DB_*, MAIL_*, MSG91_*, PAYU_*, GOOGLE_RECAPTCHA_* — all currently `<SET_ME>`.

### 3.3 Vite config (`vite.config.ts`)

- Plugins: `@vitejs/plugin-react`, `@tailwindcss/vite`.
- `base`: `/app/` in production, `/` in dev (overridable via `VITE_BASE_PATH`).
- `define`: `process.env.GEMINI_API_KEY` injected from env.
- Alias: `@` → project root.
- `server.hmr`: on unless `DISABLE_HMR=true`.
- **No proxy block** — the dev server does not forward `/api/*` to Laravel; the React app talks to the Laravel server directly via `VITE_API_BASE_URL`.
- `package.json` script: `dev = vite --port=3000 --host=0.0.0.0` (binds dev server to all interfaces).

### 3.4 Production build

```
npm run build           # vite build → ./dist/
                        # asset paths prefixed with /app/ (base=/app/ in prod mode)
```
Output folder: `./dist/`. Deploy README `deploy/README.md §3 A` describes copying `dist/*` to `/public_html/app/` on Hostinger.

---

## 4. BACKEND API SURFACE

`php artisan route:list --path=api` (run live, output captured):

| # | Method | URI | Controller@action | Status |
|---|---|---|---|---|
| 1 | GET | `api/v1/home` | `Api\V1\HomeController@index` | COMPLETE |
| 2 | GET | `api/v1/services` | `Api\V1\ServiceController@index` | COMPLETE |
| 3 | GET | `api/v1/services/{slug}` | `Api\V1\ServiceController@show` | COMPLETE |
| 4 | GET | `api/v1/services/{categorySlug}/{serviceSlug}` | `Api\V1\ServiceController@detail` | COMPLETE |
| 5 | GET | `api/v1/vehicle/brands` | `Api\V1\VehicleController@brands` | COMPLETE |
| 6 | GET | `api/v1/vehicle/models` | `Api\V1\VehicleController@models` | COMPLETE |
| 7 | GET | `api/v1/vehicle/fuels` | `Api\V1\VehicleController@fuels` | COMPLETE |
| 8 | GET | `api/v1/search/brands` | `Api\V1\VehicleController@brands` | COMPLETE (alias of #5) |
| 9 | GET | `api/v1/search/models` | `Api\V1\VehicleController@models` | COMPLETE (alias of #6) |
| 10 | GET | `api/v1/search/fuels` | `Api\V1\VehicleController@fuels` | COMPLETE (alias of #7) |
| 11 | POST | `api/v1/pricing` | `Api\V1\PricingController@quote` | COMPLETE |
| 12 | GET | `api/v1/pages/{slug}` | `Api\V1\PageController@show` | COMPLETE |
| 13 | POST | `api/v1/import/car-brands` | `Api\V1\ImportController@carBrands` | COMPLETE (token-gated) |
| 14 | POST | `api/v1/import/car-models` | `Api\V1\ImportController@carModels` | COMPLETE |
| 15 | POST | `api/v1/import/fuel-types` | `Api\V1\ImportController@fuelTypes` | COMPLETE |
| 16 | POST | `api/v1/import/service-prices` | `Api\V1\ImportController@servicePrices` | COMPLETE |

For each route the controller method exists, validation runs (`$request->validate()` with `exists:` constraints), it returns either a single resource or a `response()->json([...])` payload, and it queries the DB via Eloquent. Import endpoints additionally pass through `App\Http\Middleware\VerifyImportToken` (registered as alias `import.token` in `app/Http/Kernel.php`).

**There is no auth route, no user route, no cart route, no checkout route, no order route in `routes/api.php`.** See §7 and §9.

---

## 5. DATABASE STATE

### 5.1 Tables (from `backend/database/migrations/`)

12 migrations, all `up()`-only creates:

| File | Table |
|---|---|
| `2014_10_12_000000_create_users_table.php` | `users` (Laravel skeleton) |
| `2014_10_12_100000_create_password_reset_tokens_table.php` | `password_reset_tokens` |
| `2019_08_19_000000_create_failed_jobs_table.php` | `failed_jobs` |
| `2019_12_14_000001_create_personal_access_tokens_table.php` | `personal_access_tokens` (Sanctum) |
| `2026_05_01_120001_create_service_categories_table.php` | `service_categories` |
| `2026_05_01_120002_create_services_table.php` | `services` |
| `2026_05_01_120003_create_car_brands_table.php` | `car_brands` |
| `2026_05_01_120004_create_car_models_table.php` | `car_models` |
| `2026_05_01_120005_create_fuel_types_table.php` | `fuel_types` |
| `2026_05_01_120006_create_service_prices_table.php` | `service_prices` |
| `2026_05_01_120007_create_pages_table.php` | `pages` |
| `2026_05_01_120008_create_sections_table.php` | `sections` |

### 5.2 Core entity columns + FKs

```
service_categories
  id BIGINT PK
  name VARCHAR
  slug VARCHAR UNIQUE
  description TEXT NULL
  image VARCHAR NULL
  icon_image VARCHAR NULL
  position SMALLINT DEFAULT 0
  is_active BOOL DEFAULT TRUE
  timestamps
  INDEX (is_active, position)

services
  id BIGINT PK
  category_id BIGINT FK → service_categories.id  (cascadeOnDelete)
  name VARCHAR
  slug VARCHAR
  description TEXT NULL
  image VARCHAR NULL
  base_price DECIMAL(10,2) NULL
  time_takes VARCHAR NULL
  time_unit VARCHAR NULL
  warrenty_info TEXT NULL
  recommended_info TEXT NULL
  note TEXT NULL
  is_active BOOL DEFAULT TRUE
  timestamps
  UNIQUE (category_id, slug)
  INDEX (is_active)

car_brands
  id BIGINT PK
  name VARCHAR
  slug VARCHAR UNIQUE
  image VARCHAR NULL
  is_active BOOL DEFAULT TRUE

car_models
  id BIGINT PK
  brand_id BIGINT FK → car_brands.id  (cascadeOnDelete)
  name VARCHAR
  slug VARCHAR
  image VARCHAR NULL
  is_active BOOL DEFAULT TRUE
  UNIQUE (brand_id, slug)

fuel_types
  id BIGINT PK
  name VARCHAR
  slug VARCHAR UNIQUE
  is_active BOOL DEFAULT TRUE

service_prices
  id BIGINT PK
  service_id    BIGINT FK → services.id        (cascadeOnDelete)
  brand_id      BIGINT FK → car_brands.id      (cascadeOnDelete)
  model_id      BIGINT FK → car_models.id      (cascadeOnDelete)
  fuel_type_id  BIGINT FK → fuel_types.id      (cascadeOnDelete)
  price DECIMAL(10,2)
  UNIQUE (service_id, brand_id, model_id, fuel_type_id)   -- index name: svcprice_full_unique
  INDEX  (brand_id, model_id, fuel_type_id)               -- svcprice_vehicle_idx

pages
  id BIGINT PK
  title VARCHAR
  slug VARCHAR UNIQUE
  seo_title, seo_description, seo_keywords (nullable)
  is_active BOOL

sections
  id BIGINT PK
  page_id BIGINT FK → pages.id  (cascadeOnDelete)
  type VARCHAR
  content JSON NULL
  position SMALLINT
  is_active BOOL
  INDEX (page_id, position)
```

### 5.3 Brand → Model → Fuel → Price join

```
car_brands.id  ─┐
                ├──→ service_prices.brand_id
car_models.id ──┤    service_prices.model_id  ──→ price
                ├──→ service_prices.fuel_type_id
fuel_types.id ──┘
                                    ↑
                              service_prices.service_id ─→ services.id ─→ service_categories.id
```

Pricing is keyed by the **4-tuple (service, brand, model, fuel)** — fully normalized, with a composite UNIQUE on the same tuple guaranteeing one canonical price per combination. The vehicle hierarchy (brand → model) is enforced by `car_models.brand_id` FK; fuel is independent (a fuel type is shared across all vehicles).

### 5.4 What's NOT in the schema

The new backend has no tables for: `users` (beyond Laravel default skeleton), `addresses`, `cart`, `cart_items`, `orders`, `order_items`, `bookings`, `slots`, `enquiries`, `faqs`, `offers`, `service_centers`, `testimonials`, `home_page_settings`, `companies`. The legacy `old-backend/app/Models/` had ~38 models covering most of these — the new backend has 8 models only.

---

## 6. CORS & DEPLOYMENT CONFIG

### 6.1 CORS

`backend/config/cors.php` (current contents):

```php
'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods'          => ['*'],
'allowed_origins'          => ['http://localhost:3000', 'http://127.0.0.1:3000', env('FRONTEND_URL', 'http://localhost:3000')],
'allowed_origins_patterns' => [
    '#^http://192\.168\.\d+\.\d+:3000$#',
    '#^http://10\.\d+\.\d+\.\d+:3000$#',
    '#^http://172\.(1[6-9]|2[0-9]|3[01])\.\d+\.\d+:3000$#',
],
'allowed_headers'          => ['*'],
'exposed_headers'          => [],
'max_age'                  => 3600,
'supports_credentials'     => false,
```

CORS uses Laravel's built-in `\Illuminate\Http\Middleware\HandleCors` (no fruitcake — Laravel 9+). It is registered globally in `backend/app/Http/Kernel.php:19`.

### 6.2 APP_URL

`backend/.env:5` → `APP_URL=http://localhost:8000` (dev value).
Production template (`deploy/.env.production.template:10`) → `APP_URL=https://acr-mechanics.in`.

### 6.3 .htaccess files in repo

| Path | Purpose |
|---|---|
| `backend/public/.htaccess` | Laravel default front-controller redirect. |
| `deploy/.htaccess` | Hostinger root rewriter — blocks `/backend/*`, SPA fallback for `/app/`, default funnel into `index.php`. Cache headers + gzip. (Full text in §6.3a below.) |
| `deploy/app.htaccess` | SPA fallback inside `/public_html/app/`. |
| `old-backend/public/plugins/**/.htaccess` | Legacy KCFinder / file-share plugin guards — not deployed by anything in `deploy/`. |

#### 6.3a `deploy/.htaccess` (production)

```
RewriteRule ^backend(/.*)?$ - [F,L]                         # block /backend/*
RewriteCond %{REQUEST_URI} ^/app/
RewriteCond !-f / !-d   →  /app/index.html                  # SPA fallback
RewriteCond !-f / !-d   →  index.php                        # default into Laravel
Header set Cache-Control public,max-age=31536000,immutable  on hashed assets
DEFLATE on text/html, css, javascript, json
```
CORS headers section is commented out — same-origin in production, so unused.

### 6.4 Frontend / API origin

In production: same domain — `https://acr-mechanics.in/app/` (frontend) and `https://acr-mechanics.in/api/v1/*` (backend), per `deploy/README.md §1` and `deploy/frontend.env.production`. **No CORS preflight expected in production.** In dev: cross-origin (`localhost:3000` → `localhost:8000`), so CORS does fire.

---

## 7. AUTH STATE

- **Sanctum is installed** (`composer.json` requires `laravel/sanctum: ^3.3`) and the `personal_access_tokens` migration is present.
- **No auth routes are wired** in `routes/api.php`. There is no `/auth/login`, `/auth/register`, `/auth/logout`, `/auth/profile`, `/user/profile`, `/user/addresses` route registered. `php artisan route:list` confirms only the 16 routes listed in §4.
- No middleware group references `auth:sanctum` on any custom route.
- `backend/.env` sets `SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000` — useful only when stateful auth is wired, which it currently is not.
- `EnsureFrontendRequestsAreStateful` is **commented out** in `backend/app/Http/Kernel.php:42` (default Laravel skeleton state).
- The frontend (`src/hooks/useAuth.ts`) calls all of these auth endpoints unconditionally on render — see §9.

**Net:** Sanctum is present at the dependency / DB level but **the auth layer is unwired end-to-end.** Effectively no auth.

---

## 8. ADMIN / CMS

- No Filament. No Nova. No Backpack. No custom admin Blade views.
- The closest thing to an admin surface is the bearer-token-gated `POST /api/v1/import/*` family (4 endpoints) — these accept CSVs to upsert brands / models / fuel types / service prices. They are not a UI.
- The legacy `old-backend/` directory contains a full admin Blade panel (the legacy `routes/web.php` `Route::group(['prefix' => 'backend'])`) — not loaded by the new `backend/` app.

---

## 9. KNOWN BROKEN ENDPOINTS

Cross-reference of every frontend call (§2.2) against the backend route list (§4):

### 9.1 Endpoints the frontend calls that do NOT exist on the backend
| Frontend call site | Method + endpoint |
|---|---|
| `src/hooks/useAuth.ts:204` | `GET /user/profile` |
| `src/hooks/useAuth.ts:242` | `POST /auth/register` |
| `src/hooks/useAuth.ts:270` | `POST /auth/login` |
| `src/hooks/useAuth.ts:293` | `POST /auth/logout` |
| `src/hooks/useAuth.ts:312` | `PUT /auth/profile` |
| `src/hooks/useAuth.ts:348` | `POST /user/addresses` |
| `src/hooks/useAuth.ts:372` | `POST /checkout/offline` |
| `src/hooks/useCart.ts:86` | `POST /cart/sync` |

Every one of the above will return `404` from the new backend. The login/signup modal, "My Bookings" page, profile editor, address management, and the cart-sync side-effect are therefore **dead at the wire level**.

### 9.2 Endpoints called via dead-code hooks (no consumer, but the hooks exist)
The 11 `useApi*` hooks at `src/data/businessData.ts:384–470` reference paths the backend implements (`/home`, `/services`, etc.) and a few it does NOT (`/service-centers`, `/service-centers/{id}`, `/offers`, `/faqs`). Because no component imports these hooks, they cause no runtime traffic — but if a future maintainer imports them, the calls to `/service-centers`, `/offers`, `/faqs` will all 404.

### 9.3 Endpoints the backend exposes that the frontend never calls
- `POST /api/v1/import/*` (4 endpoints) — admin/CSV use-case, no UI consumer.
- `GET /api/v1/search/{brands|models|fuels}` — alias trio kept for compat; the live frontend only uses `/vehicle/{brands|models|fuels}`.

### 9.4 Routes marked PARTIAL or STUB
None. Every route in §4 is COMPLETE (controller present, validation runs, DB queried, JSON returned). The "incompleteness" of the system lives in the **missing routes** identified in §9.1, not in half-finished ones.

---

## 10. RISK FLAGS

### 10.1 Production-DB schema risk
- The new backend has its **own** schema (`acr_v3` per `backend/.env`). It does not share a DB with `old-backend`. Running `php artisan migrate` against the legacy production DB would create the 8 new tables alongside legacy tables — **non-destructive** as long as no legacy table is named `service_categories`, `services`, `car_brands`, `car_models`, `fuel_types`, `pages`, `sections`, or `service_prices`. UNKNOWN — needs runtime check against the live DB to confirm name collisions.
- The legacy `old-backend/database/migrations/` was sparse (4 base + a handful of column-add migrations); the live DB schema is not authoritatively reproduced by migrations. Reading the legacy `.env` against the live DB is required before migrating.

### 10.2 Slug-modifying code
Grep for `Str::slug`, `setSlug`, `slug =` writes:
- `backend/app/Http/Controllers/Api/V1/ImportController.php` calls `Str::slug($name)` when a CSV row omits the `slug` column. This is **only used on rows lacking a slug** — provided rows pass through verbatim. Dedup is keyed by `slug`, so re-running an import does not modify existing slugs.
- No frontend code calls `Str::slug` or its TS equivalent.
- No migration alters or replaces an existing slug column.
**Verdict:** no slug-mutation hazard in current code.

### 10.3 Destructive migrations
All 8 application migrations are pure `Schema::create`. Their `down()` methods are `Schema::dropIfExists` — destructive only if `php artisan migrate:rollback` is run. There are no `dropColumn`, `renameColumn`, or `dropTable` migrations pending.

### 10.4 Other risks
- **`IMPORT_API_TOKEN=dev-import-token-change-me`** in `backend/.env`. The middleware (`backend/app/Http/Middleware/VerifyImportToken.php:22`) refuses to operate when the token is the placeholder AND `app()->environment('production')` is true (operator precedence note: the check parses as `($expected === '') || ($expected === 'dev-…' && production)` — a placeholder token in non-production is allowed; production deploy with this placeholder is rejected with 500. Prod deploy must replace the value.)
- **`old-backend/` is an unintended deploy hazard** — if `/old-backend/` were uploaded to `/public_html/`, its public/ directory would be web-served and could expose KCFinder plugin files (`old-backend/public/plugins/kcfinder/...`). Deploy README only describes uploading the new `backend/`.
- **Dead code in `src/data/businessData.ts:296–470`** — a parallel `useResource` + 11 `useApi*` hooks that compete with `src/lib/api.ts` + `src/hooks/use*.ts`. Not a runtime risk but a maintenance trap (a future contributor could re-import them and quietly bypass React Query caching).
- **Static fallback to `CAR_DATA`** in `BookingSidebar.tsx:186,305`, `ServiceCategory.tsx:456`, `EstimateProcess.tsx:188` is the remaining hybrid render surface. The vehicle picker still renders static brand/model lists when API data hasn't arrived (or has arrived empty).
- **`personal_access_tokens` is migrated** but no controller issues tokens — login flow is dead (§7, §9.1).
- **Frontend `App.tsx` uses string-state pseudo-routing** (`currentPage` switch). No `react-router-dom`. The browser URL never changes; this is a known design choice but means deep-linking and SEO crawling depend on the legacy Blade routes (still served by `old-backend/`), not by the React app.

---

## End of report

Path: `/AUDIT_REPORT.md` (absolute on this machine: `C:\Users\Admin\Downloads\acr3.0\AUDIT_REPORT.md`).
