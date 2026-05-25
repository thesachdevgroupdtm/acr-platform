# L2 — Bulk Image Upload

Operator uploads ONE ZIP of all entity images; the system extracts, matches each
image to an entity by filename, and (on confirm) stores it + writes `entity.image`.
Dry-run → commit, mirroring the pricing-matrix import. Backend only; no new packages
(PHP-native `ZipArchive`); existing import system, AutoBootstrapResolver, Filament
resources, and L1 API controllers untouched.

**Result: 243 passed (1028 assertions)** — 231 baseline + 12 new.

---

## 1. Migration + column verification

The `image` column **already existed** on all four tables (declared in the L1
create-table migrations) and is already in each model's `$fillable`, and the L1 API
resources already map it to `hero_image_url`. Per PART A's "skip if column already
exists":

- Verified `string('image')->nullable()` present in:
  `…120001_create_service_categories_table`, `…120002_create_services_table`,
  `…120003_create_car_brands_table`, `…120004_create_car_models_table`.
- Verified `'image'` in `$fillable` of `CarBrand`, `CarModel`, `Service`,
  `ServiceCategory` (no changes needed).
- Added a **guarded, idempotent** migration
  `database/migrations/2026_05_21_000001_add_image_to_entities.php` — adds the column
  only `if (! Schema::hasColumn(...))`, so it's a no-op on this codebase but makes the
  feature portable to any environment whose create-migrations predate the column.
  `down()` is intentionally a no-op (the column is owned by the create-table
  migrations).

## 2. BulkImageMatcher service

`app/Services/Images/BulkImageMatcher.php` + DTO `ImageMatchReport.php` + exception
`app/Exceptions/BulkImageException.php`.

- `analyze(string $zipPath): ImageMatchReport` — opens the ZIP (`ZipArchive`), walks
  entries, classifies by top folder, validates format + size, matches to entities,
  builds the report. **Zero storage, zero DB writes.**
- `commit(string $zipPath): ImageMatchReport` — `DB::transaction(fn () => process(true))`:
  for each matched image, stores bytes to the `public` disk at
  `entity-images/{type}/{slug}.{ext}` and sets `entity.image` to that relative path.
  Overwrites existing (D-L2-7), so re-running the same ZIP is idempotent.
- Helpers (public): `normalizeFilename()` (strip ext, trim, lowercase),
  `matchBrand()`, `matchCategory()`, `matchService()`, `matchModel()` (Brand_Model
  split on first `_`, with a unique-global fallback).
- Validation: formats `png, jpg, jpeg, webp`; max **5 MB** (uncompressed size from
  `statIndex`), oversize/bad-format → `skipped` with reason. Stray entries
  (`__MACOSX/`, dotfiles, unknown folders, root files) are silently ignored.

## 3. Filament page + states

`app/Filament/Pages/BulkImageUploadPage.php` (auto-discovered; nav group "Data
Operations", icon `photo`) + view
`resources/views/filament/pages/bulk-image-upload.blade.php`.

State machine mirrors `PricingMatrixImportPage`:
`upload → analyzing → preview → importing → success`.
- `analyze()` resolves the uploaded ZIP path (compact port of the pricing page's
  `resolveUploadedFilePath`, reusing the existing `InvalidUploadStateException`),
  runs `BulkImageMatcher::analyze`, shows the report (no writes).
- `commit()` runs `BulkImageMatcher::commit` (transactional), → success.
- `cancel()` / `importAnother()` reset. FileUpload accepts `.zip` only; stored to the
  `local` disk under `bulk-images/`.
- Preview view shows the per-type summary, an expandable **unmatched** list (so the
  operator can fix filenames) and an expandable **skipped** list, then
  `[Cancel] [Import N images]`.

## 4. Match rules confirmation (D-L2-2) — all covered by tests

| Type | Rule | Verified |
|---|---|---|
| Brands | filename = `name` (case-insensitive, trimmed) — `AUDI.png` → "Audi" | ✓ |
| Categories | filename = `name` — `Battery.png` → "Battery" | ✓ |
| Services | filename = `name` (spaces ok) — `Battery Replacement.png` | ✓ |
| Models | `Brand_Model` split on first `_` — `Audi_Q5.png` → Audi's Q5 (not Honda's) | ✓ |
| Models (fallback) | no `_` → global match; **ambiguous** (>1 brand) → `skipped` with a "rename to Brand_Model" hint | ✓ |

Normalization: strip extension → trim → lowercase; models split on the first
underscore before normalizing each part.

## 5. Test results

`tests/Feature/Images/BulkImageMatcherTest.php` — 12 tests (uses a real on-disk ZIP +
`Storage::fake('public')`):
1. analyze writes nothing (no storage, `entity.image` still null) 2. brand case-insensitive
3. category 4. service (with spaces) 5. model Brand_Model + brand disambiguation
6. unmatched reported 7. oversize skipped (reason) 8. bad format skipped (reason)
9. commit stores + sets `entity.image` 10. commit transactionally atomic (forced
mid-transaction failure rolls back ALL updates) 11. re-upload overwrites + idempotent
12. mixed folders + stray entries ignored.

```
Tests:    243 passed (1028 assertions)   (231 baseline + 12 new)
```
Full suite green; Filament admin still boots (AdminAuth tests pass) with the new page
auto-discovered.

## 6. Manual / integration verification

The commit → DB → storage path is proven by tests #9–#12 (file written to the public
disk at `entity-images/{type}/{slug}.{ext}`; `entity.image` set to that path;
overwrite + atomic rollback verified). The L1 API resources already expose it:
`BrandResource`/`ModelResource`/`ServiceResource`/`CategoryResource` all map
`'hero_image_url' => $this->image`, so once committed, e.g.
`GET /api/v1/public/vehicles/brands` returns `hero_image_url` for matched brands.
`php artisan storage:link` was run (symlink `public/storage → storage/app/public`
created) so those relative paths resolve to public URLs.

**Operator UI test:** Admin → Data Operations → **Bulk image upload** → drop the ZIP →
**Process** (review the match report; fix any unmatched filenames) → **Import** →
confirm DB `image` columns + `storage/app/public/entity-images/**` files + API
`hero_image_url`.

## 7. Operator instructions — ZIP folder structure

One `.zip`, top-level folders per type; filename (minus extension) = entity name
(case-insensitive). Models that share a name across brands use `Brand_Model`:

```
brands/Audi.png
brands/BMW.png
models/Audi_Q5.png            (Brand_Model — required when a model name repeats)
models/Honda_City.png
services/Battery Replacement.png
categories/Battery.png
```
Formats: png, jpg, jpeg, webp · max 5 MB each. Re-uploading overwrites (safe to re-run).

## 8. Deviations

- **PART A** column/`$fillable`/API mapping already existed (L1). Added only the
  guarded no-op migration to formally fulfill the step; did not duplicate fillable or
  touch the L1 resources (constraint).
- **Ambiguous models** (no underscore, name shared across brands) are reported under
  **skipped** with a "rename to `Brand_Model`" hint rather than silently unmatched —
  honoring D-L2-2's "warn if ambiguous."
- **Storage is not transaction-rolled-back** (only DB is): a mid-commit failure rolls
  back all `entity.image` writes (verified atomic), but any image bytes already
  written to disk for earlier entries remain as harmless orphans — a re-run overwrites
  them (idempotent, D-L2-7).
- The interactive admin-UI upload is left for the operator (no GUI in this
  environment); the full commit pipeline is covered by automated tests + `storage:link`
  + the confirmed API mapping.
