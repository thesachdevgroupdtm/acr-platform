# FILEUPLOAD-RECOVERY — stabilize Filament image upload on entity resources

Makes admin image uploads fully recoverable from the UI: preview hydrates instantly
(no "waiting for size" hang), remove/replace controls render, and re-uploading a
different format no longer leaves duplicate-extension orphans.

**Backend: 280 passed (1121 assertions)** — 270 baseline + 10 new. Backend only
(Filament config + a model trait + one command); storage structure, schema, bulk
upload, and L1 API resources untouched. No new packages.

---

## 1. Diagnosis (PART A) — confirmed root cause

Each of the 5 resources has **two** `FileUpload::make('image')` (the edit-form field +
the list-view "upload image" row action). **Neither set `fetchFileInformation()`**, so
it defaulted to `true`. On edit/open, Filament hydrates the existing stored path and
calls `fetchFileInformation()` to read the file's size/dimensions over HTTP from the
disk URL. In this split-origin setup (admin :8000, storage URL) that request **hangs →
"waiting for size" forever**, and the remove/replace actions don't render until it
resolves. Confirmed config before: `->image()->disk('public')->directory(...)->maxSize(5120)
->imagePreviewHeight('150')->getUploadedFileNameForStorageUsing(...)` — no
`fetchFileInformation`, no `visibility`, no `acceptedFileTypes`, no preview/action flags.

## 2. Config changes (PART B) — before/after, per resource

To **all 10 FileUploads** (edit-form + list-action × 5 resources), inserted after
`->imagePreviewHeight('150')`:
```php
->fetchFileInformation(false)      // ← the key fix: stops the "waiting for size" hang
->visibility('public')
->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])   // incl. webp (D-FU-6)
->previewable(true)
->downloadable()
->openable()
->deletable(true)                  // ensures the remove (X) control renders
```
Kept `->image() ->disk('public') ->directory('entity-images/{type}') ->maxSize(5120)
->getUploadedFileNameForStorageUsing(slug…)`. Applied via a regex insert on the
`->imagePreviewHeight('150')` anchor so indentation matched each of the 10 occurrences
(20/24/28-space contexts). All 5 resources lint clean and `filament:cache-components`
registers them with no errors.

## 3. Overwrite cleanup hook (PART C) — duplicate-extension fix

New trait `app/Models/Concerns/CleansOldImage.php`, applied to `CarBrand`, `CarModel`,
`FuelType`, `Service`, `ServiceCategory`. On `saving`, when `image` is dirty and the
path actually changed, it deletes the previous file from the public disk:
```php
static::saving(function ($model) {
    if (! $model->isDirty('image')) return;
    $old = $model->getOriginal('image'); $new = $model->image;
    if (is_string($old) && $old !== '' && $old !== $new) {
        Storage::disk('public')->delete($old);
    }
});
```
- `.png → .webp` (slug stays, path changes) → old `.png` deleted → **no
  `wagon-r.png` + `wagon-r.webp` duplicate**.
- Same-path overwrite (`old === new`) → **not** deleted (Storage already overwrote in
  place; deleting would remove the just-written file).
- Clearing the image → old file deleted. Create (no original) → nothing deleted.
- Uses the per-trait `bootCleansOldImage` boot hook, so it composes with models that
  already define `booted()`.

## 4. Normalize command (PART D)

New `php artisan acr:normalize-image-paths [--dry-run]`
(`app/Console/Commands/NormalizeImagePaths.php`): walks all 5 entities, repairs
malformed `image` values — JSON-encoded Filament state → embedded `entity-images/…`
path; stray `livewire-tmp/…` → null; clean relative paths left untouched. Uses
`saveQuietly()` (never triggers CleansOldImage during a column-value repair).

**Dry-run on the dev DB: `checked 49 image value(s), would fix 0 malformed`.** So the
stuck records were **not** column-corrupted — the bug was purely the FileUpload UI
hydration (the `fetchFileInformation` hang), which §2 fixes. The command remains a
safe guard/repair for any environment; run it without `--dry-run` to apply.

## 5. Test results (PART E)

`tests/Feature/Images/FileUploadRecoveryTest.php` — 10 tests:
- Trait: change-ext deletes old (no duplicate); same-path no-delete; clear→delete;
  create→no-delete; trait applied to all 5 models.
- Config guard: every resource source has `fetchFileInformation(false)` ≥2× (form +
  action).
- Command: JSON-array → clean path; livewire-tmp → null; clean unchanged; `--dry-run`
  writes nothing.

Full suite **280 passed (1121 assertions)** — the earlier image/upload tests
(inline edit-form upload, bulk processForType, smart matcher, list renders) all still
pass with the new config.

## 6. Manual verification (operator — the actual bug)

a) Edit an entity with an existing image → preview shows immediately (no "waiting for
   size"); the remove (X) control is visible and clears the image.
b) Upload a new image → preview → save works.
c) Re-upload a **different** image → old preview replaced; save deletes the old file.
d) Upload `.png` then `.webp` → only ONE file remains (old auto-deleted) — no
   `wagon-r.png + wagon-r.webp` duplicate.
e) A wrong upload can be replaced from the UI — no filesystem access needed.
f) `.webp` previews correctly (acceptedFileTypes includes `image/webp`).

## 7. Duplicate-extension fix confirmed

The trait prevents all **future** duplicate-extension orphans (verified by test:
`.png → .webp` deletes the `.png`). The dev DB had 0 malformed column values. The one
pre-existing stray file the operator saw (`wagon-r-stingray.png` beside `.webp`,
created during the broken state) is an orphan **file** (the DB points to one of them) —
not a column issue, so it isn't auto-removed. Either re-upload that model's image (a
format change now self-cleans) or delete the stray file once; storage structure is
unchanged.

## 8. Deviations

- **Applied the stable config to BOTH FileUploads per resource** (edit-form field AND
  list-action field), since both hydrate existing images and both could hang — the
  brief named the edit field but the list action shares the defect.
- **Config inserted via regex** on the `->imagePreviewHeight('150')` anchor (10
  occurrences, 3 indentation levels) rather than 10 hand-edits — same result, lower
  risk of inconsistency.
- **Kept `getUploadedFileNameForStorageUsing` (slug naming)** per the constraints; the
  duplicate-extension problem is solved by the CleansOldImage trait (D-FU-3 Option B:
  delete the previous file on change) rather than by removing slug naming.
- **Normalize command found 0 malformed** in the dev DB — the fix is the config, not
  data repair; the command is retained as a guard. The pre-existing orphan *file* is a
  documented one-time manual cleanup (see §7).
- **Frontend untouched** (backend-only fix) — no tsc/build/smoke run.
- `filament:cache-components` was run to verify registration, then cleared so dev stays
  dynamic (re-run before production).
