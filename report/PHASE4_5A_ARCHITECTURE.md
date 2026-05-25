# Phase 4.5a — SEO Architecture Foundation

**Date:** 2026-05-08
**Status:** Foundation only. NO frontend integration. NO Filament
resource pages. Phase 4.5b/c/d build on this.

---

## 1. Polymorphic SEO design

### Why polymorphic?

A typical naive design adds `meta_title`, `meta_description`,
`og_image`, etc. directly to every resource (Service,
ServiceCategory, ServiceCenter, SeoPage, Coupon, …). That
multiplies a 20-column SEO surface across every table — schema
churn every time we add an SEO field, and no consistent way to
query "all pages where robots_meta=noindex".

This phase introduces a single `seo_metadata` table that any
resource can attach to via a Laravel polymorphic `morphOne`
relation. One SEO record per resource (enforced by a unique
index on the polymorphic pair).

### ER diagram (text)

```
┌────────────────────┐         ┌──────────────────┐
│ services           │         │ service_categories│
│  - id              │         │  - id            │
│  - name, slug      │         │  - name, slug    │
└────────┬───────────┘         └────────┬─────────┘
         │ morphOne                     │ morphOne
         │ (seoable)                    │ (seoable)
         ▼                              ▼
   ┌──────────────────────────────────────────────┐
   │ seo_metadata                                 │
   │  - id                                        │
   │  - seoable_type     (e.g. App\Models\Service)│
   │  - seoable_id       (e.g. 42)                │
   │  - meta_title, meta_description, …           │
   │  - schema_type, schema_data, custom_jsonld   │
   │  - include_in_sitemap, priority, changefreq  │
   │  UNIQUE (seoable_type, seoable_id)           │
   └──────────────────────────────────────────────┘
         ▲                              ▲
         │ morphOne                     │ morphOne
         │ (seoable)                    │ (seoable)
┌────────┴───────────┐         ┌────────┴────────────┐
│ service_centers    │         │ seo_pages (4.5b)    │
│  - id              │         │  - id               │
│  - name, slug      │         │  - slug, title      │
│  - address, …      │         │  - body             │
└────────────────────┘         └─────────────────────┘
```

### morphTo / morphOne pattern

- On `seo_metadata`: `seoable()` → `morphTo()` returns the parent
  Service / ServiceCategory / etc. based on `seoable_type`.
- On each SEO-aware model: `seoMetadata()` → `morphOne(SeoMetadata, 'seoable')` returns the (at most one) attached row.

### Models that get `HasSeoMetadata` in this phase

For Phase 4.5a (foundation) we only need to wire the trait so
the test suite can exercise the relationship:

- `App\Models\Service`
- `App\Models\ServiceCategory`
- `App\Models\ServiceCenter`

Phase 4.5c will add it to additional resources and surface
`SeoFieldGroup` in their Filament forms. Phase 4.5b will create
a brand-new `SeoPage` model that uses the trait.

---

## 2. Fallback chain

When the frontend asks "what's the meta_title for /services/audi-service-delhi?", the answer comes from a 3-level fallback:

1. **Resource-level SEO record** — the row in `seo_metadata`
   attached to that Service. If the operator filled in
   `meta_title = "Audi Service in Delhi | ACR"`, we return that.
2. **Site defaults from `site_seo_settings`** — the single-row
   table holds `default_meta_title_template = "{{page_title}} | ACR Mechanics"`. If the resource has no SEO record (or a
   blank `meta_title`), we return the template rendered with
   the resource's `name`/`title` as `{{page_title}}`.
3. **Hard-coded fallback** — last resort: the resource's own
   `name`/`title`/`"Page"` literal. Never null, never empty.

Same pattern for every field. `og_title` falls back to
`meta_title` then to page title. `twitter_image` falls back to
`og_image` then to `default_og_image`.

### Worked example: a Service with no SEO record

Given:
- `Service { id: 5, name: "Battery Charging" }`
- No `seo_metadata` row.
- `site_seo_settings.default_meta_title_template = "{{page_title}} | ACR Mechanics"`.

`$service->getSeoData()` returns:
```
meta_title       => "Battery Charging | ACR Mechanics"
meta_description => "<site default>"
og_title         => "Battery Charging" (from page title; meta_title cascade chains apply)
og_image         => "<site default>"
robots_meta      => "index,follow"
schema_jsonld    => null  (no SEO record → no schema)
include_in_sitemap => true
priority           => 0.5
changefreq         => "monthly"
```

Operator can override any single field by attaching a SEO record
and filling that one field — the rest of the cascade still
applies for the unfilled fields.

---

## 3. Schema template engine architecture

### Supported types

| `schema_type` | Generator | Notes |
|---|---|---|
| `None` | returns null | Skip structured data entirely |
| `LocalBusiness` | `localBusiness($seo)` | For ServiceCenter — auto-fills name/address/geo/phone from the parent |
| `Service` | `service($seo)` | For Service — auto-fills name/description/offers from base_price |
| `FAQPage` | `faqPage($seo)` | Reads `schema_data['faqs']` array. Phase 4.5d wires the FAQ data source |
| `BreadcrumbList` | `breadcrumbList($seo)` | Reads `schema_data['items']` array. Utility for any URL path |
| `Article` | `article($seo)` | For SeoPage in 4.5b — auto-fills headline/dates from the page |
| `Custom` | passes `custom_jsonld` through (after JSON validation) | Operator pastes raw JSON-LD |

### Template + schema_data merge pattern

The engine fills in obvious fields (name, address, dates) by
reading the polymorphic `seoable` relation. Anything that's
NOT inferable from the resource (priceRange, openingHours,
areaServed) is read from `schema_data` JSON — a free-form
key-value map operator can edit in Filament's `KeyValue` field.

### Example: LocalBusiness for ServiceCenter

```php
$seo = $center->seoMetadata()->create([
    'schema_type' => 'LocalBusiness',
    'schema_data' => [
        'priceRange' => '₹₹₹',
        'openingHours' => 'Mo-Sa 09:00-19:00',
    ],
]);
```

Engine returns:
```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "ACR Moti Nagar",          ← from $center->name
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "63, Rama Rd…",  ← from $center->address
    "addressLocality": "New Delhi",
    "addressRegion": "Delhi NCR",
    "postalCode": "110015",
    "addressCountry": "IN"
  },
  "geo": { "@type": "GeoCoordinates", "latitude": 28.6, "longitude": 77.2 },
  "telephone": "9870400861",
  "priceRange": "₹₹₹",                ← from schema_data
  "openingHours": "Mo-Sa 09:00-19:00" ← from schema_data
}
```

### Custom override path

If `custom_jsonld` is set, the engine **ignores** `schema_type`
and returns the raw JSON-LD verbatim (after a `json_decode`
sanity check — invalid JSON returns null instead of crashing).
This is the escape hatch for operators / SEO consultants who
want pixel-perfect control.

---

## 4. Filament SeoFieldGroup component

### 5-tab structure

Reusable `SeoFieldGroup::make()` returns a `Section` with a
`Tabs` component containing five tabs:

1. **Basic SEO** — meta_title, meta_description, meta_keywords,
   canonical_url, robots_meta.
2. **Open Graph** — og_title, og_description, og_image,
   og_keywords, og_type.
3. **Twitter Cards** — twitter_card, twitter_title,
   twitter_description, twitter_image.
4. **Schema.org** — schema_type select + dynamic
   `schema_data` KeyValue (visible only for templated types).
5. **Advanced** — custom_jsonld, include_in_sitemap, priority,
   changefreq.

### Field validation rules

- `meta_title` ≤ 70 chars (Google search-result truncation point)
- `meta_description` ≤ 160 chars (snippet truncation)
- `og_title` ≤ 70 chars; `og_description` ≤ 200 chars
- `canonical_url`, `og_image`, `twitter_image` validated as URLs
- `priority` ∈ {0.1, 0.3, 0.5, 0.7, 0.9, 1.0}
- `changefreq` ∈ enum (always/hourly/daily/weekly/monthly/yearly/never)
- `robots_meta` ∈ 4 standard combinations
- `schema_type` ∈ 7 values

### Helper text for non-technical admins

Each field has a `->helperText(...)` line in plain language —
no jargon, examples included. Example: meta_title says
*"Appears in browser tab and search results. Max 70 chars."*
with a placeholder *"e.g. Audi Service in Delhi | ACR"*.

### Live preview (Phase 4.5d consideration)

Out of scope for 4.5a. Phase 4.5d will add:
- Inline Google search-result preview as the operator types
- Facebook/Twitter card preview
- "Validate JSON-LD" button hitting Google's Rich Results API

---

## 5. URL redirects design

### Schema

`url_redirects` table:
- `from_path` (unique, indexed) — e.g. `/old-audi-page`
- `to_path` — e.g. `/services/audi-service-delhi`
- `status_code` (default 301) — 301 (permanent) or 302 (temp)
- `is_active` (default true)
- `hits` (unsigned int, default 0) — for Phase 6 analytics
- `notes` — operator memo
- composite index on `(from_path, is_active)` for the
  catch-all middleware lookup

### Lookup helper

```php
UrlRedirect::findActiveFor('/old-audi-page')
  ->/* returns the row OR null */
```

### Hit tracking

The `hits` column exists in this commit but is NOT incremented
yet. Phase 6 will add the increment + a Filament dashboard
showing top redirects. Stuffing analytics into the request path
is unwise without a queue.

### Catch-all middleware

The frontend route catch-all that consults this table comes in
**Phase 4.5b**. This commit only ships the table + model.

---

## 6. Migration roadmap

| Phase | Scope | Tests delta |
|---|---|---|
| **4.5a (this commit)** | Foundation: 3 tables, 3 models, trait, schema engine, Filament group | +10 backend |
| **4.5b** | `seo_pages` table + `SeoPageResource` Filament page + `/:slug` frontend catch-all + react-helmet-async install + `<head>` injection + url_redirects middleware | +20 (10 backend + 10 frontend) |
| **4.5c** | Apply `HasSeoMetadata` to remaining resources (Coupon, Order admin, Page); surface `SeoFieldGroup` in Service/ServiceCategory/ServiceCenter Filament forms; migrate static-rendered SEO from frontend constants | +10 backend |
| **4.5d** | Schema preview (live Google card render), FAQPage data source wiring, JSON-LD validator endpoint, sitemap.xml generator | +15 backend |

After all four sub-phases land: ~150 tests total, fully
operator-driven SEO with no developer involvement for routine
content.

---

## 7. Out of scope for 4.5a

Locked decisions explicitly defer the following:

- ❌ Frontend `<head>` injection (no `react-helmet-async`)
- ❌ Catch-all `/:slug` route (no `routes/api.php` change)
- ❌ Static `LOCATIONS` / `TESTIMONIALS` migration
- ❌ Filament resource pages for SEO
- ❌ Surfacing `SeoFieldGroup` in existing Service / ServiceCategory / ServiceCenter forms
- ❌ Sitemap.xml generation
- ❌ Hit-tracking on redirects
- ❌ JSON-LD live validation against Google's API
- ❌ New composer packages

If a downstream phase changes one of these decisions, document
the reason in that phase's report.
