# Phase 4.3 — Manual Verification Checklist

Run after a fresh deploy / dev server restart.

## Family A — Standard imports

### /admin/services (HeaderActions wired)

- [ ] "Template" button → downloads `services-template.xlsx` with
      the 6 headers: name, category_name, slug, description,
      base_price, is_active
- [ ] "Export" button → downloads `services.xlsx` with all 40
      current rows
- [ ] "Import" button → file picker → choose modified Excel →
      preview confirmation → action runs
- [ ] Notification: "Imported N services records"
- [ ] /admin/imports shows new audit row with status=completed
- [ ] FK lookups work — services pointing at unknown
      category_name flagged in error_summary, valid rows commit

### /admin/{brands,models,fuel-types}

These resources don't have dedicated Filament admin classes yet
(no `CarBrandResource` etc. — pre-Phase-4.3 scaffolding gap). The
imports work programmatically (`Excel::import(new BrandsImport(),
$path)`); they just don't have header-action UI yet. **Flagged
for Phase 4.4 / 4.5 follow-up.**

## Family B — Pricing matrix (CORE)

- [ ] /admin/pricing-matrix-import page accessible in nav under
      "Data Operations"
- [ ] Step 1 — upload area renders
- [ ] Upload sample 5-vehicle × 5-service matrix file
- [ ] Step 2 — preview renders with:
      - Row summary cards (total / valid / invalid / cells)
      - Price summary cards (insert / update / skipped / invalid)
      - Column mappings collapsible table with confidence chips
      - Row error details (if any)
- [ ] "Cancel" returns to Step 1
- [ ] "Confirm and import" runs UPSERT inside DB::transaction
- [ ] Notification: "X new, Y updated, Z invalid"
- [ ] Re-upload same file → second time no inserts, all updates
      (idempotent UPSERT)
- [ ] /admin/imports shows the matrix import row with status=completed
- [ ] "Export current matrix" header action downloads xlsx with
      one column per active service, NA in empty cells

## Service column mappings

- [ ] /admin/service-column-mappings (under Data Operations)
- [ ] Empty initially (operator can manually seed common headers)
- [ ] Create a mapping: `excel_column='foo bar', service=<pick>` → save
- [ ] Re-import a matrix whose Excel header is 'foo bar' →
      preview marks it as `alias` confidence (Layer 2 hit)
- [ ] Edit: set service_id=null on a mapping → marked "ignored"
- [ ] Mapping with is_active=false bypassed entirely

## Import audit history

- [ ] /admin/imports (under Data Operations) lists all attempts
- [ ] Filter by import_type works (brands / models / pricing_matrix etc.)
- [ ] Filter by status works
- [ ] Click row → modal shows error summary with row numbers
- [ ] Delete action removes the audit row

## Validation edge cases (matrix)

- [ ] NA cell → skipped, not counted as invalid
- [ ] Empty cell → skipped
- [ ] Negative number → invalid (counted in invalid_prices)
- [ ] Text in price column → invalid
- [ ] Unknown make/model/fuel → entire row skipped, others
      continue
- [ ] Same composite key appearing twice in one file → last cell
      wins (in-batch dedupe at PricingMatrixImporter ~line 165)

## Regression checks (CRITICAL)

- [ ] /sitemap.xml still returns 200 with 77+ URLs
- [ ] /explore editorial page renders
- [ ] Customer frontend /, /services, /category/X meta tags
- [ ] /:slug SEO pages still work
- [ ] /admin/seo-pages CRUD still works (Phase 4.5b/c/d)
- [ ] Phase 4.5d features still work (char counts, seo status badge,
      bulk seo, JSON-LD validator, FAQ schema)
- [ ] All 180 backend tests pass:
      `cd backend && ./vendor/bin/pest`
