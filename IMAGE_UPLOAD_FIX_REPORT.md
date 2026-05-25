# IMAGE-UPLOAD-FIX — bulk page redesign + inline resource uploads

Fixes the broken Import button by **replacing the 2-step flow with a tabbed,
auto-processing page**, adds a `fuel_types.image` column, and adds an inline image
field to all 5 entity resources. Backend only; no new packages (native `ZipArchive`);
import system / AutoBootstrapResolver / L1 API controllers untouched.

**Result: 254 passed (1059 assertions)** — 243 baseline + 11 new.

---

## 1. Import button bug — root cause + fix

**Root cause (PART A):** the old 2-step `BulkImageUploadPage::commit()` (wired to the
Import button) **re-resolved the uploaded ZIP in a second Livewire round-trip** via
`resolveUploadedFilePath()`, which reads the volatile Filament `uploadData['file']`
state, and the button was gated by `:disabled="$report['total_matched'] === 0"`.
Across that second round-trip the temporary-upload reference is no longer reliably
present (Livewire dehydrates it after analyze), so the re-resolution path silently
no-opped — and a 0-match report left the button disabled. Net effect: "clicking
Import did nothing." `Storage::disk('public')` + `storage:link` were already correct
(verified — the symlink exists), so storage was not the cause.

**Fix:** rather than patch the soon-to-be-removed 2-step, the redesign (D-FIX-3)
eliminates it. The page now **auto-processes on file select** in a single Livewire
action — it holds no cross-request upload state and has no separate button, so the
failure mode cannot occur.

## 2. Fuel-types image column (PART B)

- Guarded migration `2026_05_22_000001_add_image_to_fuel_types.php` — adds
  `string('image')->nullable()` only `if (! Schema::hasColumn('fuel_types','image'))`
  (idempotent; `down()` drops it since this migration owns the column).
- `'image'` added to `FuelType::$fillable`.
- Verified by test: `Schema::hasColumn('fuel_types','image')` true + fillable contains it.

## 3. Bulk page redesign (PART C) — 5 tabs · 3 inputs · auto-process

`app/Filament/Pages/BulkImageUploadPage.php` (rewritten) +
`resources/views/filament/pages/bulk-image-upload.blade.php` (rewritten).

- **Tabs:** Brands · Models · Services · Categories · Fuel Types. The active tab IS the
  entity type — no folder prefix needed (D-FIX-1).
- **Three input methods per tab (D-FIX-2)**, all bound to that tab's Livewire file
  property: multiple-file `<input multiple>`, folder `<input webkitdirectory multiple>`,
  and `.zip` (same multiple input `accept`s `.zip`).
- **No analyze→import 2-step (D-FIX-3):** the Livewire `updated{Tab}Uploads()` hook
  fires the moment files finish uploading → `BulkImageMatcher::processForType()` matches
  + stores matched images immediately → a result card shows
  "✅ N uploaded · ⚠️ M not matched: […] · K skipped". No buttons.
- The page uses `Livewire\WithFileUploads`; each hook resets its bucket after processing.

**`BulkImageMatcher::processForType(array $files, string $type)` (PART C step 10):**
takes a flat list of `{name, contents, size}` items (the page builds these from the
Livewire `TemporaryUploadedFile`s). Each item is an image OR a `.zip` (detected by
extension → extracted via `ZipArchive`, `__MACOSX`/dotfiles ignored). Reuses the
existing `matchBrand/matchModel/matchService/matchCategory` helpers + a new `matchFuel`,
via a shared `matchForType()`. Matched → stored to `entity-images/{type}/{slug}.{ext}`
on the public disk + `entity.image` set, all in one `DB::transaction`. The original
`analyze()`/`commit()` (folder-ZIP) methods are kept intact (their 12 L2 tests still
pass); `processForType` is additive.

## 4. Inline single upload in 5 resources (PART D)

Added `Forms\Components\FileUpload::make('image')` to `CarBrandResource`,
`CarModelResource`, `ServiceResource`, `ServiceCategoryResource`, `FuelTypeResource`:

```php
->image()->disk('public')->directory('entity-images/{type}')->maxSize(5120)
->imagePreviewHeight('150')
->getUploadedFileNameForStorageUsing(fn ($file, $get) =>
    (($get('slug') ?: Str::slug((string) $get('name'))) ?: 'image') . '.' . $file->getClientOriginalExtension())
```

`{type}` = brands / models / services / categories / fuel-types. The
`getUploadedFileNameForStorageUsing` closure names the stored file by **slug**, so an
inline upload lands at the **exact same path** the bulk system writes
(`entity-images/{type}/{slug}.{ext}`) — re-uploading via either route overwrites
consistently (D-FIX-7). Verified by a Filament form test: editing a brand + uploading
stores `entity-images/brands/audi.png` and sets `image`.

## 5. Match-result UX

Auto-upload on select; per-tab result card lists matched count, the **not-matched
filenames** (so the operator can rename), and skipped files with reasons
(unsupported format / >5 MB / ambiguous model name → "rename to Brand_Model"). A success
notification summarizes "N uploaded · M not matched · K skipped".

## 6. Test count

`254 passed (1059 assertions)` — 243 baseline + **11 new** in
`tests/Feature/Images/ProcessForTypeTest.php`: brands (multiple files), model
`Brand_Model`, fuel-type, category+service, `.zip` extraction, unmatched reporting,
bad-format/oversize skip, `fuel_types` column+fillable, bulk-page renders for admin,
CarBrand edit form renders, inline upload stores to `entity-images/brands/{slug}`.
The L2 `BulkImageMatcherTest` (12, folder-ZIP analyze/commit) still passes unchanged.

## 7. Verification (PART F)

- `./vendor/bin/pest` → **254 passed**.
- `php artisan storage:link` → symlink already present (`public/storage` → `storage/app/public`).
- `php artisan filament:cache-components` → "All done!" (the new page + 5 modified
  resources register with no errors); then `filament:clear-cached-components` so the dev
  environment stays dynamic (re-run cache before production).
- L1 API mapping unchanged — `BrandResource`/`ModelResource`/`ServiceResource`/
  `CategoryResource` already expose `image → hero_image_url`.

**Operator manual test:** Admin → Data Operations → **Bulk image upload** → a tab (e.g.
Brands) → select images / a folder / a .zip → they auto-upload; the result card shows
matched/unmatched. Then edit any single brand/model/service/category/fuel → upload its
image inline → save. Confirm DB `image` columns + `storage/app/public/entity-images/**`
files + `GET /api/v1/public/vehicles/brands` `hero_image_url`.

## 8. Deviations

- **PART A** was diagnosed but not patched on the old 2-step code, because PART C
  removes that code entirely; the auto-process redesign is the fix.
- **`UploadedFile::fake()->image()` needs the GD extension** (absent in this PHP), so
  the inline-upload test uses `->create('x.png', 40, 'image/png')`, which satisfies
  Filament's `->image()` validation without GD.
- **`filament:cache-components` was cleared after running** (not left cached) so the
  operator can keep iterating in dev; cache should be re-run for production.
- **Type key for fuel is `fuel-types`** (matches the tab + storage subfolder
  `entity-images/fuel-types/`); added to `BulkImageMatcher::TYPES` and the
  `ImageMatchReport` type set.
- ZIP size note unchanged from L2: very large bundles may hit Livewire's temp-upload
  limit — raise `livewire.temporary_file_upload.rules`/PHP `upload_max_filesize` if
  needed (not changed here).
