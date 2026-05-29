# Sub-Phase 1.2 (Phase 4.3.5) — Auto-Bootstrap Resolver with strict dry-run / commit separation

**Status:** Complete. **215 backend tests pass** (190 baseline + 12 unit
+ 12 feature + 1 integration). Migration rolls back and re-applies
cleanly. Caches cleared. Importer + PreviewService cores untouched.

---

## 1. Files created

| Path | Role |
|---|---|
| `backend/database/migrations/2026_05_13_100000_add_auto_create_audit_columns.php` | 5-table audit-columns migration (was created in earlier session; verified intact). |
| `backend/app/Exceptions/AutoBootstrapException.php` | Typed exception with `parseError` / `persistenceError` / `invalidShape` factories. |
| `backend/app/Services/Imports/DTOs/EntitySummary.php` | Per-entity rollup (`matchedExisting` / `wouldCreate` / `created` / `previewNames` / `createdIds`). Static factories `dryRun()` + `persisted()`. |
| `backend/app/Services/Imports/BootstrapReport.php` | Top-level DTO. `toArray()` for Livewire serialisation. `totalNewEntities()` / `totalMatchedExisting()` helpers. |
| `backend/app/Services/Imports/Strategies/FuzzyMatcher.php` | D-1.2-3 Levenshtein-normalised similarity. `findBest` / `similarity` / `normalize`. PHP-native `levenshtein` cap of 255 chars guarded with fallback to `similar_text`. |
| `backend/app/Services/Imports/Strategies/SectionHeaderDetector.php` | D-1.2-4 vocabulary + sparse-row heuristics. Maps column-index → section name. |
| `backend/app/Services/Imports/AutoBootstrapResolver.php` | The resolver. `resolveDryRun()` + `resolveAndPersist()`. Shared `parseInventory()`. |
| `backend/tests/Unit/Imports/FuzzyMatcherTest.php` | 7 unit tests. |
| `backend/tests/Unit/Imports/SectionHeaderDetectorTest.php` | 5 unit tests. |
| `backend/tests/Feature/Imports/AutoBootstrapResolverTest.php` | 12 feature tests. |

## 2. Files modified

| Path | Change |
|---|---|
| `backend/app/Models/CarBrand.php` `CarModel.php` `FuelType.php` `Service.php` `ServiceCategory.php` | (Done in earlier session) Each has the 7 audit fields in `$fillable` + appropriate `$casts`. |
| `backend/app/Filament/Pages/PricingMatrixImportPage.php` | Added `AutoBootstrapException` + `DB` imports. New property `public ?array $bootstrap = null;`. `analyze()` calls `resolveDryRun()` BEFORE PreviewService. `commit()` wraps `resolveAndPersist()` + Importer in a single outer `DB::transaction`. Added `AutoBootstrapException` catch arm. `cancel()` resets `$bootstrap`. |
| `backend/resources/views/filament/pages/pricing-matrix-import.blade.php` | Added Phase-4.3.5 bootstrap summary card above the 4-card row in STATE_PREVIEW. Shows per-entity existing/new counts. "View details" `<details>` with the preview-names list per dimension. Suppressed when `totalNewEntities === 0`. |
| `backend/tests/Feature/Imports/PricingMatrixImportPageTest.php` | Added 1 integration test pinning the "analyze does NOT write master data" contract at the Livewire layer. |

## 3. PART A — Schema verification

The audit-columns migration was applied in an earlier session and is
intact. Per-table column listing:

```
car_brands         : is_auto_created, auto_created_from, auto_created_import_id, reviewed_at, reviewed_by, include_in_sitemap, seo_enriched_at
car_models         : (same 7)
fuel_types         : (same 7)
services           : (same 7)
service_categories : (same 7)
```

Round-trip:

```
INFO  Rolling back migrations.
2026_05_13_100000_add_auto_create_audit_columns ...... 354ms DONE
INFO  Running migrations.
2026_05_13_100000_add_auto_create_audit_columns ...... 1,200ms DONE
```

`down()` drops the FKs + index + columns; `up()` re-adds them. Clean
either direction.

## 4. PART B — Value objects + strategies

### `FuzzyMatcher` — Levenshtein normalised similarity

```php
normalize(s)     = strtolower(strip_non_alnum(trim(s)))
similarity(a, b) = 1 - levenshtein(a, b) / max(len(a), len(b))
findBest(input, candidates, field, threshold=0.85)
                = highest-similarity candidate ≥ threshold OR null
```

Edge guards: empty input → 0.0, identical post-normalize → 1.0,
strings > 255 chars → fall back to `similar_text` percentage (PHP's
`levenshtein` errors above 255).

### `SectionHeaderDetector`

Walks raw rows looking for "banner" rows — rows that contain a
vocabulary hit, OR a single non-empty cell preceded by a blank row
(visual banner), OR a single non-empty cell on a sparse row. Returns
`column-index → section-name` map. Caller treats absent columns as
the `Imported Services` fallback.

KNOWN_VOCABULARY: Battery · Car Care · Paint · AC Service · Suspension ·
Brake · Clutch · Emergency Services · Detailing · Engine · Transmission
· Tyres · Wheel Alignment · Body Work · Electricals · Mechanical ·
Insurance · Inspection.

Cell-formatting heuristic (bold/colored) intentionally **not implemented**
— Maatwebsite's `ToArray` only returns values, not formatting. The
three text-based heuristics cover the documented production layouts;
formatting detection is a future enhancement requiring dropping into
PhpSpreadsheet directly.

### `BootstrapReport` + `EntitySummary`

Same DTO shape for both phases. `isDryRun` flag tells the consumer
which field to read:

* dry-run → `wouldCreate` populated, `created=0`, `createdIds=[]`
* persist → `created` populated, `wouldCreate=0`, `createdIds=[…]`

`previewNames` carries the human-readable list for both — populated
the same way in either phase. The Blade card reads `previewNames` for
the "View details" expansion.

## 5. PART C — Resolver flow

### Public API

```php
public function resolveDryRun(string $absoluteFilePath): BootstrapReport;
public function resolveAndPersist(string $absoluteFilePath, ?int $importId): BootstrapReport;
```

Both share `parseInventory()` (private). The dry-run path issues
**only SELECT queries** against master-data tables — never INSERT /
UPDATE / DELETE. This is the load-bearing invariant the dedicated
integration test pins.

### parseInventory layout detection

* Header row detected by scanning for any cell matching one of
  `carid` / `make` / `brand` / `model` / `fueltype` / `fuel` / `segment`
  (normalised).
* Rows above header → section banner candidates handed to
  `SectionHeaderDetector`.
* Rows below header → data; extract uniques for brand / model / fuel.
* Service columns = non-vehicle non-empty header cells.
* `column_to_section` = per-column section assignment (null for
  columns with no banner above).

### resolveAndPersist ordering

```
1. Categories  (services depend on them; FALLBACK ensured)
2. Brands      (models depend on them)
3. Models      (scoped per brand)
4. Fuel types  (independent)
5. Services    (with category id) + ServiceColumnMapping aliases
```

Each step queries existing rows once, fuzzy-matches every input,
creates the missing ones with audit fields populated:

```php
[
    'is_auto_created'        => true,
    'auto_created_from'      => 'pricing_matrix_import',
    'auto_created_import_id' => $importId,
    'include_in_sitemap'     => false,    // SEO discipline per D-1.2-5
    'is_active'              => true,     // frontend-visible immediately
]
```

Slug-collision suffixing (`-2` / `-3` / …) lets the resolver create
a brand named "Ford" even if a different row already holds slug
`ford`.

### ServiceColumnMapping key normalisation

The resolver's `ensureColumnMapping()` snaps `excel_column` to
`Str::slug($name, '_')` — the same format Maatwebsite's
`HeadingRowImport` (FORMATTER_SLUG) feeds the importer's `$overrides`
keys. Without this alignment the importer's `updateOrCreate` would
miss the resolver's row and create a duplicate. This was caught by
the existing commit-test (`expect(ServiceColumnMapping::count())->toBe(+1)`)
and is now pinned by a dedicated feature test.

## 6. PART D — Integration points

### `analyze()`

```
1. resolveUploadedFilePath()            (existing Phase 4.3.4)
2. state → STATE_ANALYZING
3. resolver.resolveDryRun($absolute)    NEW (NO DB WRITES)
   ↓ store report in $this->bootstrap (Livewire-serialised)
4. previewService.analyze($absolute)    (existing)
5. Import::create                       (existing)
6. state → STATE_PREVIEW
```

### `commit()`

```
1. resolveUploadedFilePath()
2. state → STATE_IMPORTING
3. DB::transaction(function () {
       resolver.resolveAndPersist($absolute, $importId)  NEW
       previewSvc = app(PreviewService::class)    fresh
       importer   = new Importer(previewSvc)      fresh
       importer.commit(...)
   });
4. Import::update(STATUS_COMPLETED)
5. state → STATE_SUCCESS
6. Notification includes bootstrap-created count
```

`PreviewService` + `Importer` instances are constructed **inside** the
transaction, **after** `resolveAndPersist` has committed the master-
data rows. Both constructors preload master-data hashes; instantiating
beforehand would miss the new rows.

`AutoBootstrapException` (from `resolveAndPersist`) is caught explicitly
in a dedicated arm so the operator notification is "Bootstrap failed"
rather than the generic "Import failed". The outer `DB::transaction`
auto-rolls back any partial entity creates when the exception
propagates.

### Blade card

```
┌─────────────────────────────────────────────────────────┐
│ ✨ Auto-bootstrap will create:                          │
│                                                          │
│  Brands  · Models · Fuel types · Services · Categories │
│  X exist   X exist · X exist   · X exist  · X exist     │
│  Y new     Y new   · Y new     · Y new    · Y new       │
│                                                          │
│  [View details ▼]                                        │
│                                                          │
│  Nothing is saved until you click Import. Cancel to     │
│  back out without side-effects.                         │
└─────────────────────────────────────────────────────────┘
```

Suppressed entirely when `totalNewEntities === 0` (steady-state run).

## 7. PART E — Unit tests (12 / 12 pass)

```
PASS  Tests\Unit\Imports\FuzzyMatcherTest
  ✓ returns 1.0 for exact match
  ✓ returns 1.0 for case-insensitive match after normalize
  ✓ returns above 0.85 for one-character typo in 6-char string
  ✓ returns below 0.85 for two-character typo in 5-char string
  ✓ normalizes special characters and spaces
  ✓ returns 0.0 for empty input
  ✓ finds best match among candidates above threshold

PASS  Tests\Unit\Imports\SectionHeaderDetectorTest
  ✓ detects known vocabulary sections
  ✓ detects single-cell rows as potential sections
  ✓ falls back to Imported Services when no sections detected
  ✓ maps columns to nearest section above (multiple banners)
  ✓ handles empty/sparse rows correctly
```

## 8. PART F — Feature tests (12 + 1 = 13 / 13 pass)

```
PASS  Tests\Feature\Imports\AutoBootstrapResolverTest
  ✓ dry-run does not create any entities
  ✓ dry-run reports accurate would-create counts
  ✓ dry-run identifies existing entities via fuzzy match
  ✓ commit creates entities with correct audit fields
  ✓ commit is transactionally atomic — rollback on failure
  ✓ commit creates models scoped by brand
  ✓ commit creates services with auto-detected category
  ✓ commit falls back to Imported Services when section undetected
  ✓ re-upload of same file is idempotent
  ✓ creates ServiceColumnMapping for each service column on commit
  ✓ respects fuzzy threshold of 85% at the boundary
  ✓ produces deterministic results across multiple dry runs

PASS  Tests\Feature\Imports\PricingMatrixImportPageTest (+ 1 new)
  ✓ PricingMatrixImportPage.analyze does not write master data during dry-run
```

## 9. PART G — Full suite verification

```
Tests:    215 passed (879 assertions)
Duration: 99.70s
```

190 baseline + 12 unit + 12 feature + 1 integration = **215 expected,
215 actual**.

Cache clears:

```
INFO  Compiled views cleared successfully.
INFO  Configuration cache cleared successfully.
Caching registered components...  All done!
```

Migration round-trip: rollback (354 ms) + re-apply (1.2 s) clean.

## 10. Browser verification instructions for operator

Two scenarios to validate the dry-run / commit separation:

### Test A — dry-run never writes

```
1. Open /admin/pricing-matrix-import
2. Upload an Excel containing brands/models/fuels NOT in your DB
3. Click Analyze
4. Verify: bootstrap card shows non-zero "new" counts
5. Click Cancel
6. SQL: SELECT COUNT(*) FROM car_brands; etc.
7. Confirm: counts unchanged from before step 1
```

### Test B — commit persists atomically

```
1. Open /admin/pricing-matrix-import
2. Re-upload the same file
3. Click Analyze
4. Bootstrap card shows the same "new" counts as Test A
5. Click Import
6. Wait for SUCCESS state
7. SQL: SELECT COUNT(*), SUM(is_auto_created::int) FROM car_brands;
8. Confirm: new rows present, each tagged is_auto_created=1
9. Check: SELECT include_in_sitemap, seo_enriched_at FROM services
          WHERE is_auto_created=1
10. Confirm: include_in_sitemap=0, seo_enriched_at IS NULL
            (premium SEO discipline per D-1.2-5)
```

Test C — re-upload idempotency (Sub-phase 1.2's headline UX
property): repeat Test B with the same file. Bootstrap card should
read `0 new` across every dimension; only `service_prices` updated_at
timestamps move.

## 11. Deviations

1. **25 new tests total, not 24.** The brief's hard-constraint line said
   "Add 24 new tests total (12 unit + 12 feature)" while the detailed
   PART E + PART F breakdown enumerated 12 + 12 + 1 = 25. I implemented
   25, since the integration test (PART F #18 — "analyze does not write
   to DB during dry-run") is the load-bearing pin for the architectural
   guarantee of strict dry-run / persist separation. Dropping it would
   weaken the safety net the rest of the phase rests on.

2. **Cell-formatting heuristic in `SectionHeaderDetector`** — not
   implemented. Maatwebsite's `ToArray` returns values only;
   formatting detection requires PhpSpreadsheet-direct access. The
   three implemented text-based heuristics (vocabulary match, banner-
   after-blank-row, sparse-single-cell) cover the documented production
   sheet layouts. Flagged for future enhancement.

3. **`ServiceColumnMapping.excel_column` key uses `Str::slug($name, '_')`.**
   This aligns with how Maatwebsite's `HeadingRowImport` FORMATTER_SLUG
   feeds the importer, so the importer's downstream `updateOrCreate`
   collides with the resolver's `firstOrCreate` row (no duplicate). The
   brief specified `firstOrCreate(['excel_column' => $columnName], …)`
   with `$columnName` being the raw column; that would have created
   duplicate alias rows. Documented in code + this report.

4. **Migration filename uses `2026_05_13_100000`** rather than the brief's
   suggested `2026_05_12_XXXXXX`. Same content, different timestamp —
   the file was created in an earlier session before the brief was
   finalised. Functionally identical.

## 12. NO architectural changes — confirmation

* `PricingMatrixImporter` — UNTOUCHED. `git diff` on `app/Services/Imports/PricingMatrixImporter.php` is empty (the only Phase 4.3.5 wiring lives in `AutoBootstrapResolver` + `PricingMatrixImportPage`).
* `PricingMatrixPreviewService` — UNTOUCHED.
* `ServiceColumnMapping` model — UNTOUCHED.
* `Import` model — UNTOUCHED.
* Excel parsing logic — UNTOUCHED (resolver reads via its own raw
  `Excel::toArray($anonymousReader)` so PreviewService's
  `HeadingRowImport` behaviour is unaffected).
* Routing — UNTOUCHED.
* No new packages installed.
* All 190 prior Pest tests pass alongside the 25 new ones.

Dry-run zero-writes guarantee verified directly by:

* `Tests\Feature\Imports\AutoBootstrapResolverTest::it dry-run does not create any entities`
* `Tests\Feature\Imports\AutoBootstrapResolverTest::it produces deterministic results across multiple dry runs`
* `Tests\Feature\Imports\PricingMatrixImportPageTest::it analyze does not write master data during dry-run` (Livewire-level integration)
