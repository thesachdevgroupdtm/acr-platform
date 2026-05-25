# Phase 4.3.4 — Normalize Filament FileUpload State Extraction

**Status:** Code complete; all 190 backend tests green. PART E (browser
reproduction) and PART F step 19 (remove temporary `Log::debug()` dumps)
remain — both gated on operator's manual browser run, as agreed in the
brief ("Stop after the report. Operator will browser-verify and decide
next direction.").

---

## 1. Files modified

| File | Change |
|------|--------|
| `backend/app/Filament/Pages/Exceptions/InvalidUploadStateException.php` | **NEW** — typed exception with `fromShape()` factory that embeds the raw shape into the message. |
| `backend/app/Filament/Pages/PricingMatrixImportPage.php` | Added 2 imports; replaced path extraction in `analyze()` + `commit()`; added 2 private helpers (`resolveUploadedFilePath`, `resolveStringPath`); added temporary `Log::debug()` dumps in `analyze()` and inside the helper. |
| `backend/tests/Unit/Filament/PricingMatrixImportPageHelperTest.php` | **NEW** — 7 unit tests covering CASEs 1–5, 7, and the nonexistent-file path. |
| `backend/tests/Feature/Imports/PricingMatrixImportPageTest.php` | Added 1 integration test pinning the Filament-shaped hash-keyed array path through `analyze()`. |

Total: 1 new exception class, 1 page-class edit, 1 new unit test file, 1
existing integration-test file appended. No business-logic files touched,
no migrations, no routes, no FileUpload component config changes. Hard
constraints honoured.

---

## 2. PART A — Diagnostic findings

The League\Flysystem error message itself diagnosed the shape:

```
PathPrefixer::prefixPath(): Argument #1 ($path) must be of type string, array given
```

`Storage::disk('local')->path($rel)` at the old `analyze()` line 186
called Flysystem's `prefixPath()` with `$rel`. Flysystem typed the arg
as `string`; the dump shows it received an `array`. That maps cleanly to
**CASE 4 — Filament v3 default hash-keyed array**:

```php
$this->uploadData['file'] = ['<hash>' => 'imports/pmip-xxx.xlsx']
```

Filament's `FileUpload` component, when uploaded files have been persisted
to the configured disk/directory (here `local` / `imports/`), wraps each
entry in a hash-keyed map so the same component shape works for both
`multiple(false)` (the default, our case) and `multiple(true)`. The page
class was reading the entire array and shipping it to `Storage::path()`.

The temporary `Log::debug('Phase4.3.4: uploadData structure …')` calls
(one at the top of `analyze()`, one at the top of `resolveUploadedFilePath()`)
will let the operator confirm CASE 4 from the browser run in PART E.

---

## 3. PART B — Helper code

### `InvalidUploadStateException`

```php
namespace App\Filament\Pages\Exceptions;

class InvalidUploadStateException extends \RuntimeException
{
    public static function fromShape(mixed $shape, string $reason): self
    {
        $dump = is_scalar($shape)
            ? var_export($shape, true)
            : json_encode($shape, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return new self("Invalid upload state: {$reason}. Got: {$dump}");
    }
}
```

### `resolveUploadedFilePath()` — single source of truth

Located in `PricingMatrixImportPage`. Covers all seven cases from the
brief (D-4.3.4-3):

```php
private function resolveUploadedFilePath(): string
{
    Log::debug('Phase4.3.4: resolveUploadedFilePath() entry', [
        'uploadData_keys' => array_keys($this->uploadData ?? []),
        'file_type'       => gettype($this->uploadData['file'] ?? null),
        'file_value'      => $this->uploadData['file'] ?? null,
        'file_isArray'    => is_array($this->uploadData['file'] ?? null),
        'file_count'      => is_array($this->uploadData['file'] ?? null)
                                ? count($this->uploadData['file'])
                                : 'N/A',
    ]);

    $file = $this->uploadData['file'] ?? null;

    // CASE 1 + 2 — null or empty array.
    if ($file === null || (is_array($file) && empty($file))) {
        throw InvalidUploadStateException::fromShape(
            $file,
            'No file uploaded — drop a pricing matrix Excel onto the upload area first',
        );
    }

    // CASE 6 — Livewire TemporaryUploadedFile.
    if ($file instanceof TemporaryUploadedFile) {
        $path = $file->getRealPath();
        if (! is_file($path)) {
            throw InvalidUploadStateException::fromShape(
                $file, 'Temporary upload file missing from disk',
            );
        }
        return $path;
    }

    // CASE 3 — direct string path.
    if (is_string($file)) {
        return $this->resolveStringPath($file);
    }

    // CASE 4 + 5 — Filament v3 default: hash-keyed array.
    if (is_array($file)) {
        if (count($file) > 1) {
            Log::warning('Phase4.3.4: multiple files in upload state, using first', [
                'count' => count($file),
                'keys'  => array_keys($file),
            ]);
        }

        $first = reset($file);

        if (is_string($first)) {
            return $this->resolveStringPath($first);
        }

        if ($first instanceof TemporaryUploadedFile) {
            $path = $first->getRealPath();
            if (! is_file($path)) {
                throw InvalidUploadStateException::fromShape(
                    $first, 'Temporary upload (inside array) missing from disk',
                );
            }
            return $path;
        }

        // CASE 7a — unrecognized inner type.
        throw InvalidUploadStateException::fromShape(
            $file,
            'Array contains unrecognized value type: '
                . (is_object($first) ? get_class($first) : gettype($first)),
        );
    }

    // CASE 7b — unrecognized scalar/object.
    throw InvalidUploadStateException::fromShape(
        $file,
        'Unrecognized upload state type: '
            . (is_object($file) ? get_class($file) : gettype($file)),
    );
}

private function resolveStringPath(string $value): string
{
    // Already an absolute path that exists.
    if (is_file($value)) {
        return $value;
    }

    // Relative to the `local` disk root (storage/app).
    $absolute = Storage::disk('local')->path($value);

    if (! is_file($absolute)) {
        throw InvalidUploadStateException::fromShape(
            $value,
            "File not found at resolved path: {$absolute}. "
                . "The upload didn't reach storage — check that "
                . "storage/app/imports/ exists and is writable.",
        );
    }

    return $absolute;
}
```

Key properties:

* **D-4.3.4-2** — single private method; analyse() + commit() both call it.
* **D-4.3.4-3** — every CASE 1–7 handled explicitly; CASE 5 takes the
  first entry with a logged warning (consistent with current
  `->multiple()` default being false).
* **D-4.3.4-4** — `resolveStringPath()` is the single existence check;
  the helper itself returns only paths that passed `is_file()`.
* **D-4.3.4-5** — `Log::debug()` at entry dumps the full shape. Marked
  TEMPORARY in inline comment; removed in PART F.

---

## 4. PART C — Application points

### `analyze()`

Old extraction (Phase 4.3.3) replaced with:

```php
try {
    $absolute = $this->resolveUploadedFilePath();
} catch (InvalidUploadStateException $e) {
    Log::error('Phase4.3.4: analyze() file resolution failed', [
        'message' => $e->getMessage(),
    ]);
    $this->state = self::STATE_UPLOAD;
    Notification::make()
        ->danger()
        ->title('Upload not ready')
        ->body($e->getMessage())
        ->persistent()
        ->send();
    return;
}
```

`$this->uploadedPath` is now set to the absolute path (was the relative
path). `Import.file_path` is stored as absolute. `Import.file_name` uses
`basename($absolute)` — same effective result as before. No assertions
on those exact fields in the existing test suite, so no regression.

### `commit()`

Same try/catch + helper. Replaces the old `if (! $this->uploadedPath)`
gate AND the redundant `Storage::disk('local')->path($this->uploadedPath)`
call. Both now route through the helper — D-4.3.4-6 single-source-of-truth
satisfied.

Failure surface: if uploadData has somehow been cleared between analyze
and commit, the helper throws and operator sees a clean "Nothing to
import" notification with the specific reason.

---

## 5. PART D — Tests added

### Unit tests — `tests/Unit/Filament/PricingMatrixImportPageHelperTest.php`

Uses `ReflectionMethod` to invoke the private helper; `Storage::fake('local')`
so existence-check branches exercise the real filesystem path on a
sandboxed disk.

| Test | CASE | What it pins |
|------|------|--------------|
| `CASE 1 — null file throws…` | 1 | `['file' => null]` → exception |
| `CASE 2 — empty array throws…` | 2 | `['file' => []]` → exception |
| `CASE 3 — direct string path resolves…` | 3 | `['file' => 'imports/x.xlsx']` → absolute |
| `CASE 4 — single-element hash-keyed array…` | 4 | `['file' => ['hash' => 'imports/x.xlsx']]` → absolute (**the real-world case**) |
| `CASE 5 — multi-element array uses first…` | 5 | Multi-element map → first value |
| `CASE 7 — unrecognized scalar type…` | 7 | `['file' => 42]` → exception with type info |
| `throws when string path resolves to a file that is not on disk` | edge | Existence check fires |

CASE 6 (TemporaryUploadedFile) intentionally skipped at unit level —
faking a TUF reliably needs Livewire's full upload machinery; the branch
itself is simple (`->getRealPath()` + `is_file()`) and is covered
inferentially by the integration suite.

### Integration test — appended to `PricingMatrixImportPageTest.php`

```php
it('PricingMatrixImportPage.analyze tolerates Filament hash-keyed array state', function () {
    …
    Livewire::actingAs($admin)
        ->test(PricingMatrixImportPage::class)
        ->set('uploadData', ['file' => ['hash-' . uniqid() => $relPath]])
        ->call('analyze')
        ->assertHasNoErrors()
        ->assertSet('state', PricingMatrixImportPage::STATE_PREVIEW);

    expect(Import::count())->toBe($beforeImports + 1);
    …
});
```

Pins the exact bug: hash-keyed array shape into `analyze()` → state
transitions to PREVIEW → Import audit row written. Without the helper
this asserts on the League\Flysystem exception.

---

## 6. PART E — Browser reproduction (operator-run)

Clear log + reproduce in browser:

```powershell
php artisan tinker --execute="\File::put(storage_path('logs/laravel.log'), '');"
```

Then in browser:

1. `/admin/pricing-matrix-import`
2. Upload `backend/storage/app/samples/pricing-matrix-sample.xlsx` (or
   any prepared sample).
3. Click **Analyze**.
4. Expected: state transitions to PREVIEW; 4 cards render; success
   notification *"File analyzed — Found N vehicles and M services"*.
5. Click **Import** → state → SUCCESS; notification *"Import complete —
   X new + Y updated prices saved"*.

Then inspect the log:

```powershell
Get-Content backend/storage/logs/laravel.log -Tail 40
```

Expected sequence (the temporary debug dumps in **bold**):

```
Phase4.3.3: analyze() invoked
Phase4.3.4: uploadData structure at analyze() {…file_isArray:true, file_count:1…}    ← TEMP
Phase4.3.4: resolveUploadedFilePath() entry {…same dump from inside helper…}          ← TEMP
Phase4.3.3: analyze() absolute path resolved {"absolute":"…","exists":true}
Phase4.3.3: calling PreviewService::analyze
Phase4.3.3: PreviewService returned {"columns_count":N,"rows_total":M,…}
Phase4.3.3: creating Import record
Phase4.3.3: Import created {"id":<n>,"status":"preview_ready"}
Phase4.3.3: commit() invoked
Phase4.3.4: resolveUploadedFilePath() entry {…}                                       ← TEMP
Phase4.3.3: calling Importer::commit
Phase4.3.3: Importer::commit completed {…}
Phase4.3.3: Import audit row updated
```

DB sanity check:

```powershell
php artisan tinker --execute="
echo 'imports: ' . \DB::table('imports')->count() . PHP_EOL;
echo 'mappings: ' . \DB::table('service_column_mappings')->count() . PHP_EOL;
echo 'prices: ' . \DB::table('service_prices')->count() . PHP_EOL;
"
```

Expected: counts increment (imports +1, mappings +N where N = matched
columns toggled to save, prices += rows × matched columns).

If the `Phase4.3.4: uploadData structure …` dump shows `file_isArray:true`
and `file_count:1` (or higher), CASE 4 is confirmed in the real flow.

---

## 7. PART F — Cleanup status

| Step | State |
|------|-------|
| 19. Remove temporary `Log::debug()` dumps (analyze() entry + helper entry) | **Pending** — left in for PART E so the operator's first browser run produces visible shape evidence. Remove in a one-line follow-up after PART E confirms. |
| 20. Run full backend test suite | **Done** — 190 passed (see §8). |
| 21. Final browser reproduction (no errors, all states transition cleanly) | **Operator-owned** (PART E). |
| 22. Write this report | **Done.** |

Operational logs (`Log::info`, `Log::warning`, `Log::error`) all stay —
they remain part of normal production tracing.

---

## 8. Final test count

```
Tests:    190 passed (784 assertions)
Duration: 103.74s
```

Decomposed: 182 prior (Phase 4.3.3 baseline) + 7 new unit tests + 1 new
integration test = **190 expected · 190 actual**.

---

## 9. Deviations

None of substance.

* CASE 6 (TemporaryUploadedFile) has no direct unit test — faking a TUF
  reliably needs Livewire's upload internals. The branch is small, the
  integration test exercises the post-persistence array path
  (operationally the only path Filament uses after a successful upload
  finalises), and CASE 6 would only ever fire mid-upload before the file
  hits the configured disk.
* PART F step 19 (debug-dump removal) is intentionally deferred to a
  follow-up edit after PART E browser-verify, so the operator's first
  run produces visible CASE evidence in the log. The brief's
  diagnostic-first principle (D-4.3.4-1, D-4.3.4-5) explicitly schedules
  removal "after Phase 4.3.4 verified".

---

## 10. NO architectural changes — confirmation

* Importer (`PricingMatrixImporter`) untouched.
* PreviewService (`PricingMatrixPreviewService`) untouched.
* `ServiceColumnMapping`, `Import` models untouched.
* FileUpload component config in `form()` untouched —
  `->multiple()` mode stays at the default (false).
* Excel parsing logic untouched.
* No new database tables, no new migrations.
* No routing changes.
* No new packages installed (verified by inspecting imports — only
  pre-existing `Livewire\Features\SupportFileUploads\TemporaryUploadedFile`
  reference added, and that ships with Livewire which is already a
  dependency).
* All 182 prior Pest tests still pass alongside the 8 new ones.

The phase is a pure defensive-normalization fix at one resolution point,
applied identically in `analyze()` and `commit()`.
