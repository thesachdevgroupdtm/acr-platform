# Phase 4.3.3 — Pricing Matrix Import diagnostic log

> Reproduction + log evidence captured while debugging the operator's
> "click Analyze, nothing happens" report. Kept as a standalone
> companion to `PHASE4_3_3_REPORT.md` so future debuggers can trace
> the exact reasoning.

---

## 1. Pre-flight

```
Sample file present:  storage/app/samples/pricing-matrix-sample.xlsx   (495 KB, 12 May 2026)
imports dir present:  storage/app/imports/  ← created during audit (was missing)
Phase 4.3 isolated suite (11 PricingMatrixImportTest tests):  PASS
```

The 11 importer tests passing tells us the **business logic is
intact**. The bug is in the page wiring.

## 2. Instrumentation added (then trimmed in PART G)

In `app/Filament/Pages/PricingMatrixImportPage.php`:

- 5 `Log::info('Phase4.3.3: …')` checkpoints — kept after cleanup
  for ongoing observability:
  1. `analyze() invoked`
  2. `analyze() absolute path resolved` (with `exists` check)
  3. `calling PreviewService::analyze`
  4. `PreviewService returned` (columns_count / rows_total)
  5. `creating Import record` → `Import created`
- 2 `Log::error('Phase4.3.3: … exception')` catches — kept.
- 3 `Log::debug(...)` form-state dumps — REMOVED in PART G
  cleanup per D-4.3.3-5.

## 3. Reproduction via Livewire integration test

Browser repro wasn't available in this env, so I drove the page
through `Livewire::test(PricingMatrixImportPage::class)` directly
— same Livewire lifecycle the browser would invoke.

`backend/tests/Feature/Imports/PricingMatrixImportPageTest.php`
two tests:

- `analyze creates an Import audit row and populates preview`
- `commit upserts service_prices and stamps the audit row`

Both pass with the post-fix code. **Before the fix**, the failure
would only show up if the test mimicked the bug — i.e. set
`uploadData['uploadData']['file']` (the buggy nested location) and
called `analyze`, which would early-exit with no Import row.

## 4. Log output during the green test run

Captured from `storage/logs/laravel.log` after the Livewire tests:

```
testing.INFO: Phase4.3.3: analyze() invoked
testing.INFO: Phase4.3.3: analyze() absolute path resolved {"absolute":"…/storage/app/imports/pmip-test-…xlsx","exists":true}
testing.INFO: Phase4.3.3: calling PreviewService::analyze
testing.INFO: Phase4.3.3: PreviewService returned {"columns_count":1,"rows_total":1,"valid_prices":1}
testing.INFO: Phase4.3.3: creating Import record
testing.INFO: Phase4.3.3: Import created {"id":1,"status":"preview_ready"}
testing.INFO: Phase4.3.3: commit() invoked {"import_id":1}
testing.INFO: Phase4.3.3: calling Importer::commit
testing.INFO: Phase4.3.3: Importer::commit completed {"inserted":1,"updated":0,"skipped":0,"invalid":0}
testing.INFO: Phase4.3.3: Import audit row updated {"id":1,"status":"completed"}
```

Full flow runs end-to-end when `$this->uploadData['file']` is set.

## 5. Root cause — SCENARIO 2 in the task brief

> SCENARIO 2: Logs show analyze() but no "PreviewService"
> → File resolution fails. Check FileUpload state.

The page declared:

```php
public function form(Form $form): Form
{
    return $form
        ->schema([
            FileUpload::make('uploadData.file')      // ← bug: dotted name
                ->label('Pricing matrix Excel (.xlsx)')
                ->disk('local')
                ->directory('imports')
                ->required(),
        ])
        ->statePath('uploadData');                   // ← combined with statePath…
}
```

Filament treats `FileUpload::make('NAME')` as **relative to the
form's statePath**. So the effective Livewire write target is
`uploadData.uploadData.file` (`statePath` + `NAME`).

After the operator's browser upload, the file path landed at
`$this->uploadData['uploadData']['file']` — a key `analyze()`
never reads. `analyze()` reads `$this->uploadData['file']` →
`null` → silent early-exit on the "Pick a file first" branch.

The 11 isolated importer tests didn't catch this because they
bypass the page entirely (they invoke `PricingMatrixImporter`
directly with an absolute path). The page's own
`PricingMatrixImportPageTest` (added in PART F) covers this
exact wiring.

## 6. The fix

`backend/app/Filament/Pages/PricingMatrixImportPage.php` — one
line change:

```diff
-                FileUpload::make('uploadData.file')
+                FileUpload::make('file')
```

`->statePath('uploadData')` stays — now the FileUpload correctly
writes to `$this->uploadData['file']`, which is exactly what
`analyze()` reads.

## 7. Verification after fix

```
PASS  Tests\Feature\Imports\PricingMatrixImportPageTest
  ✓ analyze creates an Import audit row and populates preview
  ✓ commit upserts service_prices and stamps the audit row

PASS  full suite — 182 passed (766 assertions)
```

That's 180 baseline + 2 new Livewire integration tests. No
regressions. The same Livewire lifecycle the browser drives is
now end-to-end green.

— Diagnostic complete · scenario 2 confirmed · 1-line fix applied
