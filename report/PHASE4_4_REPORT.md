# Phase 4.4 — Auto-bootstrap layer for pricing matrix import

**Status:** Code complete; **198 backend tests green** (190 prior + 8
new bootstrapper tests). Importer core untouched per the brief.

> **Why this phase exists:** Phase 4.3.x proved the upload → analyze →
> preview → import pipeline works. But the operator's first real-data
> run produced "585 invalid · 0 valid" — not because the Excel was bad,
> but because the production DB is missing most of the brands / models
> / fuels / services the sheet references. The strict-lookup design
> (Phase 4.3) doesn't match the actual business model where **the Excel
> sheet is the source of truth**. Phase 4.4 adds the auto-bootstrap
> layer that closes that gap.

---

## 1. Operator-facing change

**Before.** Upload → "585 invalid · 98 services need attention" → operator
has to manually pre-create master data + map each column.

**After.** Upload → "X new brands, Y new models, Z new services created
from your file. Ready to import N prices." → operator clicks Import. Done.

The manual column-mapping UI is still present, but it's a typo-protection
fallback (kicks in only when fuzzy match ≥85% finds an *existing* service
the resolver would otherwise auto-create as a duplicate). The "Save these
matches for next time" toggle has been removed — saving is now the only
behaviour.

---

## 2. Files changed

| File | Change |
|------|--------|
| `backend/app/Services/Imports/PricingMatrixBootstrapper.php` | **NEW** — additive auto-create layer. ~250 lines. |
| `backend/app/Filament/Pages/PricingMatrixImportPage.php` | Calls bootstrapper in `analyze()`, surfaces counts in success notification, drops `saveMappings` toggle, adds `bootstrapSummary` state + `buildBootstrapBlurb()` helper. |
| `backend/resources/views/filament/pages/pricing-matrix-import.blade.php` | Replaces save-toggle checkbox with a static one-liner ("matches saved automatically"); adds a blue Sparkles banner above the 4-card summary that lists what bootstrap created (only when totals > 0). |
| `backend/tests/Feature/Imports/PricingMatrixBootstrapperTest.php` | **NEW** — 8 tests covering the bootstrap behaviour end-to-end. |

**Not changed:**

* `PricingMatrixImporter.php` — importer core. The `commit()` signature
  and row processing are byte-identical to Phase 4.3.4.
* `PricingMatrixPreviewService.php` — analyzer. Read-only stays read-only.
* `ServiceColumnMapping`, `Import`, `Service`, `CarBrand`, `CarModel`,
  `FuelType`, `ServiceCategory` models.
* No migrations, no routes, no FileUpload component config.
* All 190 prior tests pass unchanged.

---

## 3. Design

### 3.1 Where bootstrap runs

`PricingMatrixImportPage::analyze()` — **before** `PricingMatrixPreviewService::analyze()`.

Rationale: the operator's goal is "upload → analyze shows green
preview → import." If bootstrap waited until `commit()`, the preview
would still show "585 invalid", which defeats the brief. Running
bootstrap on analyze means side-effects (new master rows) persist even
if operator hits Cancel — that's the conscious trade-off and matches
industry-standard auto-import pipelines.

A fresh `PricingMatrixPreviewService` is instantiated *after* the
bootstrap commits, so its constructor's in-memory hashes see the
newly-created rows.

### 3.2 What bootstrap creates

Walks the file once, then in a single `DB::transaction`:

1. **Brands** — collects every distinct `Make` value, `firstOrCreate`s
   each missing one (case-insensitive name match).
2. **Fuels** — same pattern, on the `Fuel_Type` column.
3. **Models** — collects every `(Make, Model)` pair, finds the brand
   row (now present), `firstOrCreate`s the model under it.
4. **Services** — for each header column that the resolver would tag
   as `unmapped` (Layer 4), creates a new `Service` under the
   `Auto-imported` `ServiceCategory` and writes a `ServiceColumnMapping`
   alias row so subsequent imports hit Layer 2 instead.

### 3.3 The category landing zone

`PricingMatrixBootstrapper::AUTO_CATEGORY_SLUG = 'auto-imported'` /
`AUTO_CATEGORY_NAME = 'Auto-imported'` — `firstOrCreate`'d once on first
bootstrap run. New services land here. Operator re-categorises later
via the standard Services admin UI; nothing in the importer assumes
this category remains the owner.

### 3.4 Typo protection — fuzzy still wins

The brief offered "pure source-of-truth" (every new column = new
service) but the locked choice was **keep fuzzy ≥85% as a safety net**.
The bootstrapper consults `PreviewService::resolveColumn()` which runs
the same 4-layer resolver. Only Layer 4 (`confidence === 'unmapped'`)
triggers auto-create. So `Periodic Servic` (≥85% match to an existing
`Periodic Service`) is **not** auto-created as a near-duplicate — it
snaps to the existing service.

### 3.5 Idempotency

Re-uploading the same file is a complete no-op for master data:

* `whereRaw('LOWER(name) = ?')` case-insensitive lookups before every
  `create()` call.
* `firstOrCreate` for the catch-all category.
* `updateOrCreate` for `ServiceColumnMapping`.
* The price import was already idempotent (composite unique key →
  update-vs-insert).

Test `it is idempotent — re-running on the same file creates nothing
new` pins this behaviour: second run reports `0 / 0 / 0 / 0` creates
and row counts unchanged.

### 3.6 Slug collisions

`uniqueSlug($modelClass, $name)` generates a base slug with `Str::slug`,
then appends `-2`, `-3`, ... if taken. Test
`it appends a numeric suffix when the slug for a new row collides` pre-seeds
a brand with slug `ford` then imports a different "Ford" brand — assertion
proves the new row gets slug `ford-2`.

### 3.7 The Maatwebsite-formatter round-trip

Maatwebsite's `HeadingRowImport` applies `FORMATTER_SLUG` by default,
so the operator's `Frunk Detailing` arrives in PHP as `frunk_detailing`.
Service names are humanised back via `Str::headline()`:

* `frunk_detailing` → `Frunk Detailing` ✓ (clean round-trip)
* `brand_new_service` → `Brand New Service` (intentional: explicit
  hyphens in headers are lost on auto-create; operator renames in admin
  if they care)

The `ServiceColumnMapping.excel_column` value stores the Maatwebsite-
formatted form because the resolver's `norm()` collapses both casings
to the same lookup key anyway.

---

## 4. Behavioural summary table

| Operator scenario | Bootstrap outcome |
|---|---|
| Brand `Lamborghini` not in DB | Auto-created with slug `lamborghini`. |
| Brand exists, model `Huracan` not in DB | Auto-created under the existing brand. |
| Fuel `Petrol` exists, fuel `Hydrogen` not in DB | `Hydrogen` auto-created. |
| Column `Periodic Service` exact-matches existing service | No-op (Layer 1). |
| Column `Periodic Servic` 96% fuzzy-matches existing | No-op (Layer 3) — typo protection. |
| Column `Sunroof Lubrication` no match anywhere | Auto-created service under `Auto-imported`, alias row written. |
| Operator uploads same file twice | Second run: `0 brands, 0 models, 0 fuels, 0 services` created. |
| Sheet has `AUDI` and existing brand is `Audi` | No-op (case-insensitive name match). |
| Sheet header `Frunk Detailing` and slug `frunk-detailing` taken | Slug becomes `frunk-detailing-2`. |

---

## 5. Tests

### 5.1 New bootstrapper test file — 8 cases

```
✓ creates missing brand, model and fuel rows from Excel data
✓ auto-creates a service for an unmapped column under the Auto-imported category
✓ does NOT auto-create a service when fuzzy match >= 85% finds an existing one
✓ is idempotent — re-running on the same file creates nothing new
✓ appends a numeric suffix when the slug for a new row collides
✓ reuses the Auto-imported category across runs
✓ case-insensitive: AUDI in Excel does not duplicate an existing Audi brand
✓ end-to-end: empty DB + Excel with all-new universe lands prices via the page flow
```

The end-to-end test hard-deletes master tables, uploads an Excel with
a completely unknown universe (`Ferrari` / `F8` / `Petrol` / new
service), drives the full Livewire flow `analyze → commit`, and asserts
that brand/model/fuel/service rows + price row all exist.

### 5.2 Existing 190 tests

All pass unchanged. Key safety check: `PricingMatrixImportPageTest`'s
three Phase-4.3 tests run with a pre-seeded universe (`pmipSeedUniverse`),
so the bootstrapper finds existing rows and creates nothing new. Their
assertions on Import audit rows + price counts are unaffected.

### 5.3 Final suite

```
Tests:    198 passed (819 assertions)
Duration: 45.58s
```

Decomposed: 182 baseline (Phase 4.3 complete) + 7 unit (Phase 4.3.4
helper) + 1 integration (Phase 4.3.4 hash-keyed array) + 8 bootstrapper
(this phase) = **198 expected · 198 actual**.

---

## 6. UI changes

### 6.1 Bootstrap-summary banner (new)

Above the 4-card summary in the PREVIEW state, when any bootstrap count
is non-zero:

```
✨ Master data auto-updated from this file
   3 new brands · 12 new models · 1 new fuel type · 5 new services
   New services land in the Auto-imported category. Re-categorise via
   the Services admin when you have a moment.
```

Hidden entirely on steady-state runs where the bootstrap is a no-op.

### 6.2 Removed save-toggle

The "Save these matches for next time" checkbox is gone. Replaced with
a one-line caption inside the service-matching table:

> *Any matches you change here are saved automatically and applied next
> time you upload a similar file.*

### 6.3 Success notification

Phase 4.3 said: *"Found N vehicles and M services. Review the matches
and click Import when ready."*

Phase 4.4 says: *"Found N vehicles and M services. Created 3 new brands,
12 new models, 5 new services. Click Import when ready."* (the *"Created
..."* fragment is empty on steady-state runs.)

---

## 7. Operator browser flow (what you'll see)

1. Open `/admin/pricing-matrix-import`.
2. Upload your real pricing matrix Excel.
3. Click **Analyze**.
4. **Expected:** State transitions to PREVIEW with a blue banner listing
   what the bootstrap created. The 4-card summary now shows N rows
   valid + 0 invalid (or close to it). The service-matching table shows
   every column resolved (mostly to `Saved match` for newly-created
   services, and `Matched` / `Likely match` for pre-existing ones).
5. Click **Import**.
6. **Expected:** State transitions to SUCCESS with `inserted` + `updated`
   counts reflecting the full sheet.
7. Re-upload the same file. Bootstrap banner disappears (no creates).
   Prices update in place; no duplicates.

The temporary `Log::debug()` dumps from Phase 4.3.4 are still in place
(Phase 4.4 doesn't touch them). They'll show CASE 4 hash-keyed array
behaviour on this run, same as before. Strip them in a one-line follow-up
once you've confirmed the new flow.

---

## 8. Constraint adherence

* ✅ Importer core untouched (`PricingMatrixImporter.commit` byte-identical).
* ✅ Preview service untouched (`PricingMatrixPreviewService` byte-identical).
* ✅ Schema untouched — no migrations, no new tables/columns.
* ✅ No routes changed.
* ✅ No packages installed.
* ✅ Production data safe — bootstrap is additive (`firstOrCreate` semantics
  on case-insensitive name; never updates or renames existing rows).
* ✅ All 190 prior Pest tests pass alongside 8 new ones.
* ✅ Manual column-mapping UI retained as the typo-protection fallback.

---

## 9. Follow-ups (not in this phase)

1. **Strip Phase 4.3.4 debug dumps.** Two `Log::debug()` calls in
   `PricingMatrixImportPage::analyze()` and inside
   `resolveUploadedFilePath()`. One-line edit each. Pending your PART E
   browser verification of the helper.
2. **Auto-imported category re-categorisation UX.** The banner already
   nudges operator to the Services admin. If volume gets high, a
   filtered list view ("services in Auto-imported") would help — but
   that's not a Phase 4.4 concern.
3. **Vehicle-field fuzzy matching.** Currently exact-or-create for
   brands/models/fuels. Option 3 of Q2 (fuzzy ≥85% on those too) wasn't
   selected, but could be added later if `Audii` → `Audi` autocorrect
   becomes a real complaint.
