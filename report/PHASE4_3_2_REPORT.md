# Phase 4.3.2 — Pricing Matrix Import UX Redesign

> Replaces the Phase 4.3 two-state preview UI with a 5-state
> Livewire state machine + plain-English vocabulary throughout.
> Adds inline mapping override + "save these matches" toggle so
> operators can fix unmapped columns once and have the system
> remember next time.

---

## 1. Files modified

```
backend/app/Filament/Pages/PricingMatrixImportPage.php
backend/app/Services/Imports/PricingMatrixImporter.php
backend/resources/views/filament/pages/pricing-matrix-import.blade.php
```

### Files NOT touched (per spec)

```
backend/app/Services/Imports/PricingMatrixPreviewService.php   ← preview logic unchanged
backend/app/Models/Import.php                                  ← model unchanged
backend/app/Models/ServiceColumnMapping.php                    ← model unchanged
backend/app/Imports/*                                          ← Family A importers unchanged
backend/app/Exports/*                                          ← exports unchanged
backend/database/migrations/*                                  ← no new migrations
backend/tests/*                                                ← no new tests, no existing tests touched
routes/*                                                       ← no URL changes
```

---

## 2. PART A — Audit findings

`PricingMatrixImportPage.php` (Phase 4.3 version) — 187 lines,
3 methods (`analyze`, `commit`, `cancel`), implicit 2-state via
`$preview === null`.

Blade view used the resolver's internal vocabulary directly:

| Phase 4.3 raw term  | Where it appeared                            |
|---------------------|----------------------------------------------|
| `exact`             | Confidence badge class                       |
| `alias`             | Confidence badge class                       |
| `fuzzy`             | Confidence badge class                       |
| `unmapped`          | Cell-level summary, sentence copy            |
| `ignored`           | Confidence badge class                       |
| `NA`                | Cell-level summary copy                      |
| `invalid_prices`    | Card label                                   |
| `valid_vehicles`    | Card label                                   |
| `will_insert`       | Card label                                   |

`PricingMatrixImporter::commit()` already accepted an
`$overrides` parameter (Phase 4.3 wired it but the page passed
`[]`). The Phase 4.3 implementation also always persisted those
overrides to `service_column_mappings` — to support the new
"save these matches" toggle as an opt-out, a single
`bool $persistMappings = true` parameter was added (the **only**
backend touch in this phase, explicitly carved out by PART E of
the brief).

---

## 3. PART B — Livewire state machine

Public state property added with 5 named constants:

```php
public const STATE_UPLOAD    = 'upload';
public const STATE_ANALYZING = 'analyzing';
public const STATE_PREVIEW   = 'preview';
public const STATE_IMPORTING = 'importing';
public const STATE_SUCCESS   = 'success';

public string $state = self::STATE_UPLOAD;
```

Transitions:

```
upload   ──[analyze()]──▶  analyzing  ──[PreviewService done]──▶  preview
preview  ──[commit()]──▶  importing  ──[Importer done]──▶       success
preview  ──[cancel()]──▶  upload
success  ──[importAnother()]──▶ upload
```

Each transition method assigns `$this->state = …` before/after
the actual work so Livewire's re-render shows the spinner state.

Additional reactive properties:
- `$columnMappingOverrides: array<string, int|null>` — keyed by
  Excel header text, the value the operator picked in the
  dropdown. Seeded from the resolver's default during analyze().
- `$saveMappings: bool` (default true) — checkbox state for
  "Save these matches for next time".
- `$result: ?array` — populated after commit; snapshots
  `inserted / updated / skipped / invalid / totalDone` for the
  success-state stats grid.
- `getServiceOptionsProperty(): array` — Livewire computed prop
  that pulls active services for the override dropdown once per
  render.

Plain-English label helpers live on the page class so the Blade
view never has to map vocabulary:

```php
public function confidenceLabel(?string $c): string
{
    return match ($c) {
        'exact'   => 'Matched',
        'alias'   => 'Saved match',
        'fuzzy'   => 'Likely match',
        'ignored' => 'Skipped',
        default   => 'Needs attention',
    };
}
```

---

## 4. PART C — Blade view layout (5 state-driven sections)

### 4.1 Upload state

Heading + sub-line + Filament file picker + "Analyze file"
button. Tip line at the bottom explains the export-edit-reupload
loop.

### 4.2 Analyzing state

Centered spinner + "Reading your file…" + "Usually takes 2–5
seconds."

### 4.3 Preview state — 4 summary cards

Grid `grid-cols-1 md:grid-cols-2 lg:grid-cols-4`:

| Card                | Big number                  | Sub-text                          |
|---------------------|-----------------------------|-----------------------------------|
| **File**            | `total` (rows read)         | "vehicle rows read"               |
| **Vehicles**        | `valid_vehicles` (green)    | + red line if any need attention  |
| **Prices to save**  | `valid_prices` (emerald)    | "X new · Y updates"               |
| **Service matching**| `count(matchings)`          | "All columns matched" / "X need attention" |

Below the cards:

- **Service matching detail** — collapsible (auto-opens if any
  unmapped). Three columns:
  *Your column* / *Status* (badge with plain-English label) /
  *Service it maps to* (dropdown, options = all active services
  + "Skip this column").
  Bottom of the table has the "Save these matches for next time"
  checkbox bound to `$saveMappings`.

- **Rows needing attention** — collapsible, auto-shown when
  `$preview['row_summary']['errors']` is non-empty. Lists each
  bad row with the specific reason.

- **Cell-level notes** — a quiet gray panel listing
  blank/NA cells and "issue" cells separately, with explanatory
  copy.

Top of the preview has Cancel + "Import N prices" button (label
includes the live `valid_prices` count).

### 4.4 Importing state

Centered spinner + "Importing {{ number_format($count) }}
prices…" + "Please don't close this page."

### 4.5 Success state

Big circle icon — green check (no issues) or amber triangle
(partial) — with appropriate copy below.

Stats grid (4 colored tiles):
- **New prices** (emerald) — `inserted`
- **Updated prices** (blue) — `updated`
- **Skipped (blank/NA)** (gray) — `skipped`
- **With issues** (red) — `invalid`

Bottom: **Import another file** + **View import history**
(→ `/admin/imports`).

---

## 5. PART D — Plain-English vocabulary

Translation enforced on both layers — the page class exposes
`confidenceLabel()` / `confidenceColor()` and the Blade only uses
those + plain phrasing. Side-by-side:

| Phase 4.3 raw    | Phase 4.3.2 operator-facing label            |
|------------------|----------------------------------------------|
| exact            | Matched (green)                              |
| alias            | Saved match (blue)                           |
| fuzzy            | Likely match (amber)                         |
| unmapped         | Needs attention (red)                        |
| ignored          | Skipped (gray)                               |
| NA / N/A         | "blank or marked NA"                         |
| invalid_prices   | "cells … aren't valid prices"                |
| valid_vehicles   | "ready"                                      |
| invalid_vehicles | "need attention"                             |
| will_insert      | "new"                                        |
| will_update      | "updates"                                    |
| total_cells      | "price cells" (in tooltips / notes)          |
| skipped_na       | "blank or marked NA"                         |
| service_id NULL  | "Skip this column" (in dropdowns)            |

Tooltips and confirmation strings rephrased too. The
`wire:confirm` on the Import button now reads:
*"Import these prices now? This will add and update rows in your
live pricing table."* instead of the SQL-flavoured Phase 4.3
version.

---

## 6. PART E — Mapping override + save flow

### 6.1 During analyze

The page seeds `$columnMappingOverrides` with the resolver's
default service_id for every Excel column. So the dropdowns
start aligned with the resolver, and the operator only changes
what's wrong.

```php
foreach ($preview['column_mappings'] as $m) {
    $this->columnMappingOverrides[$m['excel']] = $m['service_id'];
}
```

### 6.2 During commit

The page hands the override map + save-flag to the importer:

```php
$importer->commit(
    absolutePath: $absolute,
    overrides: $this->columnMappingOverrides,
    userId: auth()->id(),
    persistMappings: $this->saveMappings,
);
```

### 6.3 Importer signature (minimal backend touch)

Phase 4.3 signature:
```php
public function commit(string $absolutePath, array $overrides = [], ?int $userId = null): void
```

Phase 4.3.2 signature:
```php
public function commit(
    string $absolutePath,
    array $overrides = [],
    ?int $userId = null,
    bool $persistMappings = true,
): void
```

Default `true` preserves identical behaviour for existing call
sites (and the 11 Phase 4.3 matrix tests, which all still pass).

The persist loop is now gated:
```php
if ($persistMappings) {
    foreach ($overrides as $excelOrNorm => $sid) {
        ServiceColumnMapping::updateOrCreate(/* … */);
    }
}
```

That's the entire backend diff for this phase.

---

## 7. PART F — Verification

### 7.1 Backend tests

```
Tests:    180 passed (752 assertions)
Duration: 176.74s
```

Phase 4.3 baseline (180) preserved exactly. **Zero regressions.**
The Phase 4.3 PricingMatrixImportTest's 11 tests still pass
because the new `$persistMappings` parameter defaults to the
prior behaviour.

### 7.2 Filament cache + view cache

```
php artisan filament:cache-components → Caching registered components... All done!
php artisan view:clear                 → Compiled views cleared successfully.
```

Routes unchanged (only the page rendering changed; URL is still
`/admin/pricing-matrix-import`).

### 7.3 Manual UX walkthrough (per spec PART F)

The UI now visits all 5 states in sequence on a real import:

| State        | Visible element                                       |
|--------------|-------------------------------------------------------|
| upload       | "Upload your pricing matrix" heading + file picker    |
| analyzing    | Spinner + "Reading your file…"                        |
| preview      | 4 cards + service-matching table + dropdowns + checkbox + import button |
| importing    | Spinner + "Importing N prices…"                       |
| success      | Green check (or amber triangle) + 4-tile stats grid + next-action buttons |

Color coding consistent across all states (success = green/emerald,
info = blue, warning = amber, danger = red, neutral = gray).

---

## 8. Deviations

1. **Backend touched (single param added).** The spec's intro
   said "NO backend changes", but PART E explicitly carved out
   that the Importer's `commit()` signature may change if needed
   for the saveMappings flag. I added one optional parameter
   (`bool $persistMappings = true`) with the spec's default
   preserving prior behaviour. Total backend diff: ~6 lines (one
   signature line, one if-block wrapping an existing loop). No
   test signature changes needed — existing callers pass through
   the default.

2. **Live override re-analyze deferred.** PART E suggested
   "re-run analyze() in background (debounced) to update counts"
   when an operator changes a dropdown. The override → final
   commit flow already produces correct results without
   re-analyzing — the live preview counts are based on the
   resolver's initial pass, and the commit applies overrides on
   top. Adding background re-analyze would have required
   plumbing a "lightweight count-only" path through
   `PreviewService` that's its own design exercise. I let the
   preview counts stay as the resolver's initial estimate; the
   success state shows the authoritative numbers from the
   importer. Flagged for follow-up if operator feedback shows
   confusion.

3. **No URL change.** Spec called out URL restructure (Issue 1)
   as explicitly out of scope for this phase per the operator's
   decision. Not addressed.

---

## 9. Files unchanged confirmation

| File / area                                          | Touched? |
|------------------------------------------------------|----------|
| PricingMatrixPreviewService                          | No       |
| ServiceColumnMapping model                           | No       |
| Import model                                         | No       |
| BrandsImport / ModelsImport / FuelTypesImport / ServicesImport | No |
| MasterDataExport / PricingMatrixExport               | No       |
| All migrations                                       | No       |
| All tests (155 baseline + 25 Phase 4.3 imports)      | No       |
| Routes / api.php / web.php                           | No       |
| Frontend (React)                                     | No       |

Only two files changed total (page class + Blade view) plus one
6-line minimal Importer signature extension.

---

## 10. Note on Issue 1 (URL restructure) — DEFERRED

Per the spec's tail note, the URL restructuring concern (Issue 1
in the original operator brief) is **not addressed** in Phase
4.3.2. The page still lives at `/admin/pricing-matrix-import`.
Operator decided to track URL restructuring as a separate
Phase 4.5e pickup once the UX behaviour ships and is in use.

— Phase 4.3.2 complete · backend 180 / 180 · UI redesigned end-to-end · plain-English vocabulary live
