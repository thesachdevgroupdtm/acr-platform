# IMAGE-SYSTEM-FIXES — model/fuel images · smart matcher · list-view uploads

Three fixes: (1) frontend model + fuel selectors render the uploaded image
(like brands), (2) a smart "messy filename" matcher (strip trailing timestamp +
split glued BRAND+MODEL), (3) Filament list-view image column + inline upload on
all 5 resources.

**Backend: 270 passed (1095 assertions)** — 260 baseline + 10 new.
**Frontend: tsc clean (2 pre-existing only) · build clean · smoke 3/3.**
No new packages; storage paths, L1 API resources, pricing/cart/booking untouched.

---

## 1. Frontend model/fuel image rendering (PART A)

The selector grids previously always drew a hardcoded icon. Now they render the
uploaded image when present, with the icon as fallback — the same pattern brands use.

- `src/components/vehicle-selector/ModelGrid.tsx` — `model.image ? <img …> : <Car/>`.
- `src/components/vehicle-selector/FuelGrid.tsx` — `fuel.image ? <img …> : <fuel icon>`.
- `src/lib/api.ts` — added `image?: string | null` to the `FuelType` type.
- `src/hooks/useVehicle.ts` — `toFuel` adapter now maps L1 `hero_image_url → image`
  (brands/models already mapped it). Fuels already come from L1
  `/api/v1/public/vehicles/fuels`, which includes `hero_image_url`.

The image value is the full L1 URL, used verbatim (no manipulation). Both the homepage
hero and the service-page sidebar mount the same `VehicleSelector`, so both benefit.

## 2. Smart filename matcher (PART B)

New public methods on `BulkImageMatcher` (additive — exact match still runs first):
- `stripTimestamp($name)` — `preg_replace('/\d{10}$/', '', …)`. `VOLVOXC601698310597`
  → `VOLVOXC60`; `PORSCHE9111698311285` → `PORSCHE911`; clean names unchanged.
- `normalizeString($s)` — lowercase + strip non-alphanumeric (glue-safe).
- `splitBrandModel($cleaned)` — **longest known-brand prefix** wins:
  `VOLVOXC60` → `{ brand: Volvo, model_norm: "xc60" }`; `MercedesGLE43` → Mercedes (8)
  beats Mer (3) → `gle43`.
- `matchModelSmart($filename)` — strip timestamp → split → exact-normalized model match
  within that brand → **fuzzy fallback** (existing `FuzzyMatcher`, ≥0.85). e.g.
  `AudiA81698311289.png` → Audi A8; `PorscheCarera…` → Carrera (fuzzy).

**Wiring (D-FIX2-3):** in `processForType`'s per-image path (`ingestImage`), the
timestamp is stripped on **every tab** before matching (so `VOLVO1698….png` → `volvo`
→ matches the brand), and the **Models** tab additionally falls back to
`matchModelSmart`. Unparseable model files are reported as **skipped** with the hint
"couldn't parse brand+model from filename '…'". The folder-ZIP `analyze/commit` path is
unchanged (legacy; its 12 tests still pass).

## 3. Filament list image column + inline upload (PART C)

On all 5 resource list tables (`CarBrand`, `CarModel`, `FuelType`, `Service`,
`ServiceCategory`):
- **`Tables\Columns\ImageColumn::make('image')->disk('public')->height(40)`** — 40px
  thumbnail (resolves the relative path to the full storage URL); blank when null.
- **`Tables\Actions\Action::make('uploadImage')`** (photo icon) — opens a modal with a
  `FileUpload` (`->image()->disk('public')->directory('entity-images/{type}')->maxSize(5120)`)
  that names the file by **slug** via `getUploadedFileNameForStorageUsing` and saves to
  `entity.image` — so the operator uploads from the list without opening the edit page.
  `{type}` = brands / models / fuel-types / services / categories. `fillForm` pre-loads
  the current image. Same `entity-images/{type}/{slug}.{ext}` path + overwrite as bulk
  and edit-form uploads (D-FIX2-5).

## 4. Test results

- Backend `pest`: **270 passed (1095 assertions)**.
  - New `tests/Feature/Images/SmartMatcherTest.php` (9): stripTimestamp; splitBrandModel
    + longest-prefix; matchModelSmart exact / null / **fuzzy**; processForType models
    (messy → matched + stored), brands (timestamp strip), models unparseable → skipped+hint.
  - New `tests/Feature/Admin/Resources/ListImageColumnTest.php` (1): all 5 resource list
    pages render with the image column + upload action.
  - The L2/earlier image tests (BulkImageMatcher folder path, ProcessForType, ImageUrl)
    still pass — smart matcher is additive.
- Frontend: `tsc --noEmit` → only the 2 pre-existing `brand-typography.spec.ts` errors;
  `vite build` clean; `playwright --project=smoke` → **3/3**.
- `php artisan filament:cache-components` → "All done!" (5 modified resources register
  cleanly); cleared afterward so dev stays dynamic.

## 5. Manual verification (operator)

a) Frontend model selector shows uploaded image (not the car icon) when set.
b) Frontend fuel selector shows uploaded image when set.
c) Bulk **Models** tab: drop `VOLVOXC601698310597.png` → matches **Volvo XC60**
   (timestamp stripped, brand prefix split). Unparseable → "couldn't parse…".
d) Filament model/brand/fuel/service/category **list**: image column shows thumbnails.
e) List **"Image"** row action → modal → upload → saves inline; thumbnail updates.
f) Uploaded image appears in the list immediately.

## 6. Deviations

- **Frontend uses `model.image` / `fuel.image`, not `hero_image_url` directly.** The
  selector hooks (SELECTOR-CONVERGENCE) already adapt L1's `hero_image_url` into the
  `image` field (so brands work); models/fuels follow the same field for consistency —
  same full URL, no component-side URL handling. (Functionally identical to D-FIX2-1.)
- **Smart matcher is wired into the per-tab `processForType` path** (what the redesigned
  bulk page uses), not the legacy folder-ZIP `analyze/commit` path (unused by the UI;
  left intact so its tests pass).
- **List "upload image" action storage** reuses Filament's `FileUpload` + slug-naming
  (the same mechanism proven by the edit-form upload test); covered here by a list-render
  test rather than a separate file-upload action test (the FileUpload store path is
  already verified). The action names by `$record->slug` (always present on a row).
- **ImageColumn uses `->height(40)`** (square thumbnail), not `circular()` — entity
  images aren't avatars.
