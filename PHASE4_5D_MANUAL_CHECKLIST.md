# Phase 4.5d — Manual Verification Checklist

Run after a fresh deploy / dev server restart.

---

## Legacy SeoHead cleanup

- [ ] `src/lib/SeoHead.tsx` no longer exists
- [ ] `grep -rn "lib/SeoHead" src/` returns no live `import` hits
      (one doc-comment reference in `src/components/SeoHead.tsx`
      remains — informational only)
- [ ] `npm run build` succeeds

## Schema preview (PART C)

Open each of:
- `/admin/seo-pages/{id}/edit`
- `/admin/services/{id}/edit`
- `/admin/service-categories/{id}/edit`
- `/admin/service-centers/{id}/edit`

For each:
- [ ] "Preview JSON-LD" button visible in the header bar
- [ ] Click → modal opens with formatted JSON-LD in a black `<pre>`
      block; schema_type shown in the subtitle
- [ ] Edit `schema_type` or `meta_title` in the form, **don't save**,
      re-open the modal → preview reflects the unsaved change
- [ ] "Copy to clipboard" button works (button label flips to
      "Copied ✓" for 1.5s)
- [ ] "Validate" button runs validation, errors / warnings / info
      render inline (color-coded, bulleted lists)
- [ ] When `schema_type=None`, the modal explains "No JSON-LD
      will be rendered" instead of showing an empty `<pre>`

## FAQ schema wiring (PART D — Path B)

- [ ] `/admin/faqs` (NEW resource) appears in the nav
- [ ] 6 seeded FAQs listed (Q01 warranty validity, Q02 pickup/drop,
      Q03 insurance claims, Q04 transparent pricing, Q05 brands, Q06
      turnaround) — copied verbatim from `HomeFAQ.tsx:HOME_FAQS`
- [ ] Reorder via drag-handle works, sort_order updates
- [ ] Toggle is_active off → row hidden from `/api/v1/faqs`
- [ ] `curl http://127.0.0.1:8000/api/v1/faqs | jq` returns 6
      active rows
- [ ] Create an SEO page with `schema_type=FAQPage`, preview JSON-LD
      → mainEntity array contains 6 Question/Answer pairs

## JSON-LD validator (PART E)

```bash
# Valid Service JSON-LD
curl -X POST http://127.0.0.1:8000/api/v1/seo/validate \
  -H 'Content-Type: application/json' \
  -d '{"jsonld":"{\"@context\":\"https://schema.org\",\"@type\":\"Service\",\"name\":\"X\",\"description\":\"Y\"}"}' | jq

# Expected: { "valid": true, "errors": [], "warnings": ["Service.provider …"], "info": ["Validated against …"] }
```

- [ ] Valid Service JSON-LD → `valid: true`
- [ ] Wrong `@context` → `valid: false`, errors include
      "Unexpected @context"
- [ ] Missing `@context` → errors include "Missing @context."
- [ ] LocalBusiness without address → errors mention `address`
      and `telephone`
- [ ] Invalid JSON syntax → errors include "Invalid JSON"

## Admin quality features (PART F)

### 5a — Character counts

- [ ] In any SEO-bearing Edit form, type into `meta_title` — hint
      updates live, color goes gray → amber → green → red as you
      cross thresholds (0 / 29 / 60)
- [ ] Same for `meta_description` (thresholds 0 / 119 / 160)

### 5b — Completeness badge

- [ ] `/admin/services` table shows "SEO" column with icon per row
- [ ] Service with no SEO → gray minus-circle, tooltip "No SEO record"
- [ ] Service with only meta_title → amber exclamation-triangle,
      tooltip "SEO partial — missing title or description"
- [ ] Service with full SEO → green check-circle, tooltip "SEO
      complete"
- [ ] Same badge column on `/admin/service-categories`,
      `/admin/service-centers`, `/admin/seo-pages`

### 5c — Bulk SEO generation

- [ ] On `/admin/services`, multi-select records → bulk action menu
      shows "Generate basic SEO"
- [ ] Confirm modal appears
- [ ] After confirm, notification: "Generated SEO for N record(s)"
- [ ] Re-open list, status badges for those rows changed to
      green/amber (depending on whether starter SEO is "complete"
      per the trait rule)
- [ ] Run again on the same records → notification says "0
      record(s) — All selected records already had SEO."
- [ ] Same action present on `/admin/service-categories` and
      `/admin/service-centers`
- [ ] **No** bulk action on `/admin/seo-pages` (correctly absent —
      SeoPage already has its own SEO form)

## Regression checks (CRITICAL)

- [ ] `/sitemap.xml` still returns 200 with 77+ URLs
- [ ] `/explore` editorial page renders all sections
- [ ] Customer frontend `/`, `/services`, `/category/X`,
      `/services/X/Y`, `/service-centers` all inject meta tags
- [ ] `/:slug` SEO pages still work
- [ ] `./vendor/bin/pest` → **155 passing** (was 137)
- [ ] Filament admin loads — no fatal errors in browser console
