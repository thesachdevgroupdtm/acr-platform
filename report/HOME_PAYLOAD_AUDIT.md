# /api/v1/home payload audit

Read-only trace of every top-level key in the `/api/v1/home` response,
sourced from `backend/app/Http/Controllers/Api/V1/HomeController.php`.
Skipped per the brief: `success`, `seo`, `service_categories`,
`car_brands`, `car_models` (the last three already classified in the
prior audit; in this controller `car_models` is in fact `[]` — see
note in the table).

## Source map

| Key | Source (HomeController.php line) | Classification | Phase 2 implication |
|---|---|---|---|
| `service_centers` | `[]` literal (L46) — no model, no DB query | **EMPTY** | Real entity. Frontend `LOCATIONS` static array (4 centres) is the de-facto data; needs `service_centers` table + CRUD + GET /api/v1/service-centers if it stays in the home payload. |
| `offer_slider` | `[]` literal (L47) — no model | **EMPTY** | Marketing carousel — could live as a `pages` row with `sections[]` of type `offer_slider`, or its own table. Decide based on whether non-devs will edit slides via admin. |
| `tabular_offers` | `[]` literal (L48) — no model | **EMPTY** | Same marketing-ops question as offer_slider. Closest fit: `sections` JSON content with type `tabular_offer`, no new table needed. |
| `service_packages` | `[]` literal (L49) — no model | **EMPTY** | Distinct concept from `service_categories` (bundle SKUs vs. taxonomy). Needs a real entity if launched: `service_packages` table FK → many `services` via pivot. |
| `featured_products` | `[]` literal (L50) — no model | **EMPTY** | Products = accessories. Legacy `old-backend/app/Models/Product.php` exists but no migration in the new `backend/`. Either real entity (`products` table + CRUD) or kill the slot if accessories are out-of-scope for the rebuild. |
| `faqs` | `[]` literal (L51) — no model | **EMPTY** | Structured Q&A. Cleanest in `sections` JSON with type `faq` under either a per-page or a global `faqs` page. Real table only justified if FAQs need search/tagging beyond CMS rendering. |
| `brand_logo_slider` | `[]` literal (L52) — no model | **EMPTY** | Logo strip is downstream of `car_brands` (every active brand with a non-null `image`). Could be derived in this same query — no separate table required. |
| `membership_package` | `[]` literal (L53) — no model | **EMPTY** | Subscription tier table. Real entity if memberships ship: `membership_packages` table + endpoints + checkout flow. Currently nothing in the codebase references it on the frontend either — candidate to drop from the response. |
| `home_page_setting` | `null` literal (L54) — no model | **EMPTY** | Single-row "site settings" record. Best fit: a dedicated `pages` row with `slug='home'` and ordered `sections` carrying hero copy/CTA blocks. Avoids a new singleton table. |
| `settings` | hardcoded `['site_name' => config('app.name')]` (L55–57) | **CONFIG** | App-name is `.env` `APP_NAME`. If more keys join (phone, email, social URLs — currently in `src/data/businessData.ts:BUSINESS_INFO`), they should also be config-backed (env or `config/site.php`), not a DB table. No CRUD needed. |

### Note on `car_models`
Excluded from the table per the brief, but worth flagging: in this
controller `car_models` is **also** `[]` (L45). The `CarModel` model
and `car_models` table exist and have 81 rows seeded, but the home
endpoint does not query them — models are fetched on demand by
`/api/v1/vehicle/models?brand_id=` after the user picks a brand. So
the key is structurally EMPTY in the home payload despite the entity
existing. Front-end `useBrands()` + `useModels(brandId)` already
handle this correctly via separate endpoints; no change required.

## Cross-check: models vs migrations

`ls backend/app/Models/` returns **9 files**:
`CarBrand`, `CarModel`, `FuelType`, `Page`, `Section`, `Service`,
`ServiceCategory`, `ServicePrice`, **`User`**.

The 8 application migrations in `backend/database/migrations/2026_…`
back the first 8 models verbatim. `User` is backed by the Laravel
skeleton migration `2014_10_12_000000_create_users_table.php` (the
`users` table) — included by the framework, not part of the
application's 8-table audit. **No models are uncovered by migrations.**

No model query is referenced by any of the empty/config keys above,
so the tinker-count check for table existence/data was not necessary
(the brief said to run it *only* for keys sourced from a model).

## Summary

Of the 10 keys investigated, **9 are EMPTY** (literal `[]` or `null`)
and **1 is CONFIG** (a single env-derived `site_name`). **Zero are
backed by real tables today.** Phase 2 should resolve these in three
buckets, not all the same way:

- **3 real entities worth their own table + CRUD + endpoint.**
  `service_centers` (clearly a domain object — branches with address,
  hours, photos), `service_packages` (bundle SKUs distinct from
  `services`), and possibly `products` (re-enable if accessories ship,
  otherwise drop `featured_products` from the response).
- **5 fit the existing `pages`/`sections` CMS** without new tables.
  `offer_slider`, `tabular_offers`, `faqs`, `home_page_setting`, and
  `brand_logo_slider` are all marketing/content shapes already
  expressible as `sections.content` (JSON) under a `home` page.
  `brand_logo_slider` is doubly redundant — it can be derived from
  `car_brands` directly.
- **1 stays config.** `settings` should grow into a `config/site.php`
  / env-keyed map for phone/email/social — no DB table, no admin UI.
- **1 candidate to delete from the response.** `membership_package`
  has no consumer on the frontend and no domain decision behind it
  yet; cheaper to remove the key than to design an entity for a
  feature that may never ship.

Net Phase 2 shape: roughly **3 new tables** (service_centers,
service_packages, optionally products), **a `home` CMS page with
~4 section types** (offers, tabular_offers, faqs, hero) covering the
content slots, and a small **site config** module for the
businessData.ts gap. Not 10 new tables.
