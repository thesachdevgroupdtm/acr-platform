# Phase 4.5b — Manual Verification Checklist

Run after the architect signs off. Each item ~30 sec.

## Setup

1. Backend running: `cd backend && php artisan serve --host=127.0.0.1 --port=8000`
2. Frontend running: `npm run dev` (opens on :3000 unless taken)
3. Admin login: `admin@acr-mechanics.in / change-me-on-first-login`
4. Customer browser: <http://localhost:3000>
5. Verify the seeder ran: `php artisan db:seed --class=SeoPageSeeder`
   (idempotent — safe to re-run).

## Filament admin: SEO page CRUD

Visit <http://127.0.0.1:8000/admin/seo-pages>:

- [ ] List page renders with the 4 seeded pages visible
- [ ] Click "New SEO Page"
- [ ] Type a title — slug auto-fills via Str::slug
- [ ] Body editor (RichEditor) shows toolbar: bold, italic, h2, h3,
      list, link, blockquote
- [ ] Try slug `cart` → form rejects with "reserved" error
- [ ] Try slug `audi-service-delhi` → unique error
- [ ] Add a category and 2 tags
- [ ] Fill CTA section (collapsed by default — expand)
- [ ] Toggle "is_published" → save
- [ ] Reload page → published_at auto-stamped

## SEO field group (Phase 4.5a component, first consumer)

On the same edit page:

- [ ] Scroll to "SEO Settings" — collapsed by default
- [ ] Expand → 5 tabs visible: Basic SEO, Open Graph, Twitter
      Cards, Schema.org, Advanced
- [ ] Fill `meta_title` → save → reload → value persisted
- [ ] Fill `og_image` URL → save → reload → value persisted
- [ ] Schema.org tab: choose `Article` → KeyValue field appears
- [ ] Choose `Custom` → KeyValue hidden, Advanced tab's
      `custom_jsonld` field stays available
- [ ] Toggle `include_in_sitemap` off → save → page omitted from
      sitemap.xml (verify in next section)

## Frontend: /:slug

For each of the seeded pages
(`/audi-service-delhi`, `/bmw-service-cost-guide`,
`/monsoon-car-care-tips`, `/best-car-ac-service-gurugram`):

- [ ] Page loads with banner showing the title
- [ ] Body content renders with HTML formatting
- [ ] Category badge shown above body
- [ ] CTA section appears at the bottom of the body
- [ ] Related Articles section shows ≤3 sibling cards
- [ ] Click a related card → navigates to that slug

View page source (Ctrl+U):

- [ ] `<title>` tag set to meta_title (or fallback)
- [ ] `<meta name="description">` set
- [ ] `<meta property="og:title">` set
- [ ] `<meta property="og:image">` set
- [ ] `<meta name="twitter:card">` set
- [ ] `<script type="application/ld+json">` present (when
      schema_type ≠ None)

## Frontend: /explore

Visit <http://localhost:3000/explore>:

- [ ] Banner reads "Explore Articles"
- [ ] All 4 seeded pages render as cards
- [ ] Each card has: category badge, title, excerpt, tag chips
- [ ] Filter dropdown lists categories from seeded data
- [ ] Choose "Brand Service" → only Audi + BMW visible
- [ ] Type "Monsoon" in search → only Monsoon card visible
- [ ] Clear filters → all 4 cards return
- [ ] Click any card → navigates to /:slug

## Reserved slugs

- [ ] Visit `/cart` → Cart page (NOT SeoPageView)
- [ ] Visit `/admin` → Filament admin login
- [ ] Visit `/payment` → 404 NotFound (preserves Phase 2.6a-fix smoke)
- [ ] Visit `/explore` → ExplorePage (NOT SeoPageView)

## URL redirect

In Filament tinker / a console:

```
\App\Models\UrlRedirect::create([
  'from_path' => '/old-audi-page',
  'to_path' => '/audi-service-delhi',
  'status_code' => 301,
]);
```

- [ ] Visit `/old-audi-page` → address bar updates to
      `/audi-service-delhi`
- [ ] Audi page renders normally (no double redirect)

## sitemap.xml

Visit <http://127.0.0.1:8000/api/v1/sitemap.xml>:

- [ ] Browser shows valid XML (not an error page)
- [ ] Contains `<loc>...</loc>` entries for each seeded page
- [ ] Contains entries for /, /services, /service-centers, /coupons, /explore
- [ ] If you toggled `include_in_sitemap=false` on any page,
      that URL is absent
- [ ] Cache-Control header: `public, max-age=3600`

## Sign-off

- [ ] All 25+ items checked
- [ ] No console errors anywhere on customer pages
- [ ] No 500 errors in `php artisan serve` terminal output
- [ ] Phase 4.1/4.2/4.5a admin functions still work (login,
      Order/User/Coupon/SeoPage/etc. resources)

Reply: **"Phase 4.5b manual verification COMPLETE"** or list
specific failures with screenshots.
