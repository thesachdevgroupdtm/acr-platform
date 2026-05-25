# Phase 4.5c — Manual Verification Checklist

Run after a fresh deploy / dev-server restart. Times are rough.

---

## Filament admin (5 min)

- [ ] `/admin/services` — edit any service. Bottom of form shows
      collapsible "SEO Settings" Section with 5 tabs (Basic, Open
      Graph, Twitter, Schema.org, Advanced).
- [ ] Schema.org tab → `schema_type` Select default is **Service**.
- [ ] Enter a `meta_title` ("Test Service Title"). Save.
- [ ] Reload edit page. `meta_title` is still "Test Service Title".
- [ ] `/admin/service-categories` — edit any category. SEO section
      visible. `schema_type` default is **None**.
- [ ] `/admin/service-centers` (NEW) — appears in left nav at
      navigationSort 30.
- [ ] 4 seeded centers listed (Moti Nagar, Gurugram, Noida, Okhla).
- [ ] Edit any center. Form shows: name, slug, phone, email,
      address, city, state (default "Delhi NCR"), pincode, lat/lng,
      is_active toggle, sort_order — plus the SEO section.
- [ ] `schema_type` default is **LocalBusiness**.
- [ ] Save SEO → reload → values persist.
- [ ] Attempt delete on a center referenced by an order: blocked
      with red notification "Cannot delete — N order(s) reference
      this center."
- [ ] Filter by `is_active` and by `city` works.
- [ ] `/admin/seo-pages` (Phase 4.5b) — refactored Create/Edit
      pages still save SEO correctly (no behavior change).

## Customer frontend (10 min)

For each URL: open dev tools → Elements → `<head>` and confirm the
listed meta tags are present.

- [ ] `/` — `<title>` non-empty; `<meta name="description">`,
      `<meta property="og:type" content="website">`, optional
      `<script type="application/ld+json">` (organization JSON-LD
      from SiteSeoSettings).
- [ ] `/services` — same shape; `<title>` includes "Our Services".
- [ ] `/category/{any-real-slug}` — `<title>` reflects the
      category name through the `default_meta_title_template`
      `{{page_title}}` substitution.
- [ ] `/services/{cat}/{service}` — `<title>` reflects the
      individual service name; `og:image` present.
- [ ] `/service-centers` — list page; `<title>` "Service Centers".

End-to-end SEO override flow:
- [ ] Open `/admin/services/{id}/edit` for a service shown on the
      customer site.
- [ ] Set `meta_title` to "Custom Override Title". Save.
- [ ] Hard-reload (Ctrl+Shift+R) the customer service detail page.
- [ ] `<title>` reads "Custom Override Title".

## API spot checks

```bash
curl -s http://127.0.0.1:8000/api/v1/home | jq .seo
curl -s http://127.0.0.1:8000/api/v1/services | jq .seo
curl -s http://127.0.0.1:8000/api/v1/services/{slug} | jq .seo
curl -s http://127.0.0.1:8000/api/v1/service-centers | jq .seo
curl -s http://127.0.0.1:8000/api/v1/service-centers/{slug} | jq .seo
```

Each must return an object with 14 keys (meta_title, …, schema_jsonld).

## Regression checks (CRITICAL)

- [ ] `/explore` — editorial page renders all sections.
- [ ] `/:slug` (any seeded SEO page) — renders correctly.
- [ ] `/sitemap.xml` — valid XML, includes seo-page entries.
- [ ] `/admin/seo-pages` — CRUD still works end-to-end.
- [ ] `./vendor/bin/pest` — **130 passing** (was 118; +12 new).
- [ ] `npx playwright test seo-injection.spec.ts --project=phase4_5c`
      — 4 passing.

If anything fails: see `PHASE4_5C_REPORT.md` §"Deviations" for known
edge cases, otherwise file a regression note.
