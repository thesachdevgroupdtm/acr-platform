# Phase 4.3.1 — CarBrand / CarModel / FuelType Filament resources

> Closes the UI gap left by Phase 4.3 deviation 1: Family A
> imports for brands / models / fuel types worked
> programmatically but had no admin surface. This phase scaffolds
> the 3 missing resources and wires the
> `HasMasterDataImportActions` trait so the operator gets
> Template / Export / Import buttons on each list page.

---

## 1. Files created

```
backend/app/Filament/Resources/CarBrandResource.php
backend/app/Filament/Resources/CarBrandResource/Pages/ListCarBrands.php
backend/app/Filament/Resources/CarBrandResource/Pages/CreateCarBrand.php
backend/app/Filament/Resources/CarBrandResource/Pages/EditCarBrand.php

backend/app/Filament/Resources/CarModelResource.php
backend/app/Filament/Resources/CarModelResource/Pages/ListCarModels.php
backend/app/Filament/Resources/CarModelResource/Pages/CreateCarModel.php
backend/app/Filament/Resources/CarModelResource/Pages/EditCarModel.php

backend/app/Filament/Resources/FuelTypeResource.php
backend/app/Filament/Resources/FuelTypeResource/Pages/ListFuelTypes.php
backend/app/Filament/Resources/FuelTypeResource/Pages/CreateFuelType.php
backend/app/Filament/Resources/FuelTypeResource/Pages/EditFuelType.php

PHASE4_3_1_REPORT.md
```

**12 PHP files** (3 resources × 4 = resource + 3 pages each).
Zero modifications to existing code.

---

## 2. PART A — Model verification

All 3 models already existed (Phase 4.3 didn't need to add them).
Public surface used by Phase 4.3 importers:

| Model      | Relationships kept intact                                          |
|------------|--------------------------------------------------------------------|
| `CarBrand` | `models()` HasMany → CarModel, `prices()` HasMany → ServicePrice   |
| `CarModel` | `brand()` BelongsTo → CarBrand                                     |
| `FuelType` | `prices()` HasMany → ServicePrice                                  |

Resources use these existing relationships — no model edits.

---

## 3. PART B — CarBrandResource

`/admin/car-brands` (navigationGroup "Vehicle Catalogue",
sort 60).

Form:
- `name` (required, max 200, slug auto-fill on create)
- `slug` (required, alphaDash, unique-ignoring-record)
- `is_active` toggle (default true)

Table:
- `name` searchable + sortable
- `slug` mono-font copyable
- `models_count` badge (eager via `->counts('models')`)
- `is_active` ToggleColumn
- `created_at` since-stamp (hidden by default)
- Filter: `is_active` TernaryFilter
- Delete blocked when `models()->count() > 0` (mirror of
  ServiceCategoryResource pattern from Phase 4.2)

`ListCarBrands` uses `HasMasterDataImportActions` trait with
`masterDataKind() = 'brands'`. Header bar gets:
**Create** | **Template** | **Export** | **Import**.

---

## 4. PART C — CarModelResource

`/admin/car-models` (navigationGroup "Vehicle Catalogue", sort 61).

Form:
- `brand_id` Select via `->relationship('brand', 'name')`
  (searchable, preload, live so slug uniqueness reactive)
- `name` (required, slug auto-fill on create)
- `slug` (required, alphaDash, **per-brand unique** —
  `Rule::unique('car_models', 'slug')->where('brand_id',
  $get('brand_id'))`, matches the
  `car_models_brand_id_slug_unique` compound index)
- `is_active` toggle

Table:
- `brand.name` badge (sortable; query uses
  `->modifyQueryUsing(fn ($q) => $q->with('brand'))` to dodge
  N+1)
- `name` searchable + sortable
- `slug` mono copyable
- `is_active` ToggleColumn
- Default sort: `brand.name` asc
- Filter: brand_id SelectFilter (searchable) + is_active

`ListCarModels` uses the trait with `masterDataKind() = 'models'`.

---

## 5. PART D — FuelTypeResource

`/admin/fuel-types` (navigationGroup "Vehicle Catalogue",
sort 62).

Form:
- `name` (required, unique-ignoring-record, slug auto-fill)
- `slug` (required, alphaDash, unique)
- `is_active` toggle

Table:
- `name` searchable + sortable
- `slug` mono copyable
- `prices_count` badge (eager count via `->counts('prices')`)
- `is_active` ToggleColumn
- Filter: `is_active` TernaryFilter
- Delete blocked when `prices()->count() > 0` (protects
  service_prices integrity)

`ListFuelTypes` uses the trait with `masterDataKind() = 'fuel_types'`.

---

## 6. PART E — Verification

### 6.1 Route registration

```
php artisan route:list --path=admin | grep -E "car-brand|car-model|fuel-type"

GET|HEAD  admin/car-brands              filament.admin.resources.car-brands.index
GET|HEAD  admin/car-brands/create       filament.admin.resources.car-brands.create
GET|HEAD  admin/car-brands/{record}/edit filament.admin.resources.car-brands.edit
GET|HEAD  admin/car-models              filament.admin.resources.car-models.index
GET|HEAD  admin/car-models/create       filament.admin.resources.car-models.create
GET|HEAD  admin/car-models/{record}/edit filament.admin.resources.car-models.edit
GET|HEAD  admin/fuel-types              filament.admin.resources.fuel-types.index
GET|HEAD  admin/fuel-types/create       filament.admin.resources.fuel-types.create
GET|HEAD  admin/fuel-types/{record}/edit filament.admin.resources.fuel-types.edit
```

All 9 routes registered after `php artisan filament:cache-components`.

### 6.2 Backend tests

```
Tests:    180 passed (752 assertions)
Duration: 72.75s
```

Phase 4.3 baseline (180) preserved. **Zero regressions.** No new
test files added — the Family A imports already had their test
suites in Phase 4.3 (`BrandsImportTest`, `ModelsImportTest`,
`FuelTypesImportTest`). The Filament resources scaffolded here
are pure UI surfaces over already-tested logic, so adding
duplicate Filament tests would be redundant.

### 6.3 Trait integration confirmed

`HasMasterDataImportActions::buildImporter(string $kind)` already
maps all 4 kinds to the right importer class:

```php
return match ($kind) {
    'brands'     => new BrandsImport(),
    'models'     => new ModelsImport(),
    'fuel_types' => new FuelTypesImport(),
    'services'   => new ServicesImport(),
};
```

So the new resources don't need any further import wiring —
just the `masterDataKind()` overrides in the List page subclass.

---

## 7. Deviations

**None.** Scope adhered exactly:

- No SEO retrofit (D-4.3.1-4)
- No image upload (D-4.3.1-5)
- No bulk operations beyond the trait's Template/Export/Import
- No statistics columns beyond the simple counts already on
  similar resources (services_count, models_count, prices_count)
- No frontend changes
- No package installs
- No modifications to ServiceResource (already wired in 4.3)
- No modifications to Phase 4.3 import logic

---

## 8. Confirmation — Phase 4.3 UI gap closed

Before Phase 4.3.1:

| Master kind  | Programmatic import  | Admin UI surface                  |
|--------------|----------------------|-----------------------------------|
| brands       | ✅ `BrandsImport`     | ❌ no resource                    |
| models       | ✅ `ModelsImport`     | ❌ no resource                    |
| fuel_types   | ✅ `FuelTypesImport`  | ❌ no resource                    |
| services     | ✅ `ServicesImport`   | ✅ ServiceResource (Phase 4.3 wired) |

After Phase 4.3.1:

| Master kind  | Programmatic import  | Admin UI surface                  |
|--------------|----------------------|-----------------------------------|
| brands       | ✅ `BrandsImport`     | ✅ CarBrandResource              |
| models       | ✅ `ModelsImport`     | ✅ CarModelResource              |
| fuel_types   | ✅ `FuelTypesImport`  | ✅ FuelTypeResource              |
| services     | ✅ `ServicesImport`   | ✅ ServiceResource               |

All 4 master data resources now have Template / Export / Import
buttons. Phase 4.3 deviation 1 resolved.

— Phase 4.3.1 complete · backend 180 / 180 · 3 resources + 9 pages added · zero code modifications
