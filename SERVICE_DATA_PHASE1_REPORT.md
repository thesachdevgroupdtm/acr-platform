# SERVICE_DATA_PHASE1_REPORT — backend data + admin (no frontend, no layout)

Phase 1 of the service-pages redesign: added the two truly-missing data
structures (per-service **inclusions** "what's included" + **interval_info** km/interval
copy), made the remaining service/category content fields fillable in Filament, and
surfaced the new fields + full image URLs through the API so Phase 2 can render them.

**Additive only** — no drops/renames/slug changes. **No frontend touched.** No packages.
Guarded migrations. **298 tests pass** (288 prior + 10 new).

---

## 1. Audit — existing vs missing admin fields

**Most service content was already editable** — the audit's "make ALL fields fillable"
turned out to be mostly done; only two new structures were genuinely missing.

| Filament `ServiceResource` form | Status before |
|---|---|
| name, slug, category_id, is_active | ✅ editable |
| **image** (FileUpload, stable config → `entity-images/services`, slug naming) | ✅ editable |
| base_price, time_takes, time_unit (Select min/hours) | ✅ editable |
| description, warrenty_info, recommended_info, note (Textareas) | ✅ editable |
| SEO group | ✅ editable |
| **interval_info** | ❌ missing (no column yet) → **added** |
| **inclusions** ("what's included") | ❌ missing (no table) → **added (Repeater)** |

| Filament `ServiceCategoryResource` form | Status before |
|---|---|
| name, slug, description, position, is_active | ✅ editable |
| **image** (FileUpload → `entity-images/categories`) | ✅ editable |
| SEO group | ✅ editable |
| **icon_image** | ❌ column existed but no input → **added (FileUpload)** |

**List image columns (PART D-14):** both resources **already** had
`ImageColumn::make('image')` — nothing to add.

**API resources (main frontend, `app/Http/Resources/`):** `ServiceResource`,
`SubServiceResource`, `ServiceCategoryResource` emitted `image`/`icon_image` **raw**
(relative paths). The `Api/V1/*` (public L1) resources already used `ImageUrl::resolve`
as `hero_image_url` — so the helper + pattern existed; the main resources just hadn't
adopted it.

**Helpers confirmed:** `App\Support\ImageUrl::resolve(?string)` (null→null, http→as-is,
relative→`Storage::disk('public')->url()`, idempotent); `App\Models\Concerns\CleansOldImage`
(deletes old file on `image` path change); the stable FileUpload config
(`fetchFileInformation(false)`, `visibility('public')`, `disk('public')`, slug naming).

---

## 2. Migrations added (additive, guarded)

1. **`2026_05_24_120001_create_service_inclusions_table.php`** (D-P1-1) — guarded by
   `Schema::hasTable`. Columns: `id, service_id (FK → services, cascadeOnDelete),
   label (string), image (string nullable), position (int default 0), timestamps`;
   index `(service_id, position)`.
2. **`2026_05_24_120002_add_interval_info_to_services_table.php`** (D-P1-2) — guarded by
   `Schema::hasColumn`. Adds `services.interval_info` (string, nullable). `down()`
   drops it only if present.

`php artisan migrate` → both **Ran**; `migrate:status` confirms. Existing rows
unaffected (new column nullable; existing services keep working with nulls).

---

## 3. Models

- **`Service`** (`app/Models/Service.php`): added `interval_info` to `$fillable`; added
  `inclusions(): HasMany` → `ServiceInclusion`, **ordered by `position` then `id`**.
- **`ServiceInclusion`** (new, `app/Models/ServiceInclusion.php`): `$fillable =
  [service_id, label, image, position]`, `position` cast to int, `belongsTo Service`,
  uses **`CleansOldImage`** (D-P1-1/C-11 — consistent thumbnail overwrite cleanup).
- **`ServiceInclusionFactory`** (new) — for tests.

---

## 4. Filament forms made fillable

- **`ServiceResource`**: added `interval_info` TextInput (in the Content section, helper
  text `Every 5000 km or 3 months`) and a new **"What's Included"** Section with an
  **`inclusions` Repeater** bound via `->relationship()`: `label` (required) + optional
  thumbnail FileUpload (reuses the stable config → `entity-images/service-inclusions`,
  label-slug + random-suffix filename) + **`->orderColumn('position')`** so drag-reorder
  auto-writes `position`. Collapsible, `addActionLabel('Add inclusion')`.
- **`ServiceCategoryResource`**: added **`icon_image` FileUpload** (stable config →
  `entity-images/categories`, distinct `{slug}-icon.{ext}` filename so it never
  overwrites the hero image; accepts SVG too).
- **List image columns:** already present on both — unchanged.
- `php artisan filament:cache-components` builds cleanly ("All done!"); caches cleared
  for dev afterwards.

---

## 5. API resources updated (additive + ImageUrl on all images)

- **`ServiceResource`** (detail/category): `image` → `ImageUrl::resolve`; **added
  `interval_info`**; **added `inclusions[]`** (`id, label, image (ImageUrl), position`)
  via `whenLoaded('inclusions')` — only serialized when eager-loaded.
- **`ServiceController@detail`**: now eager-loads `->with('inclusions')` so the
  per-service detail endpoint emits them. (`@show` / `@index` do **not** load them →
  list stays lean per D-P1-5.)
- **`SubServiceResource`** (list): `image` → `ImageUrl::resolve`; **added
  `interval_info`**. Inclusions intentionally omitted (lean list).
- **`ServiceCategoryResource`**: `image`, `image_1`, `icon_image` → `ImageUrl::resolve`.

**Safety:** images are 0% populated today, so `resolve(null)=null` — every existing
response is byte-identical. No existing test asserted raw image paths on these
endpoints. **Frontend untouched** (extra JSON keys are ignored; TS types get
`interval_info`/`inclusions` in Phase 2).

---

## 6. Sample API response with new fields + full URLs

Real output of `ServiceResource` for a sample service with image + interval + 3
inclusions (generated in a **rolled-back transaction** — nothing persisted):

```json
{
  "id": 194, "sc_id": 18, "category_id": 18,
  "slug": "battery-replacement-demo",
  "title": "Battery Replacement", "name": "Battery Replacement",
  "description": "Replace your car battery.",
  "image": "http://localhost:8000/storage/entity-images/services/battery-replacement.webp",
  "price": "2500.00", "base_price": "2500.00",
  "vehicle_price": null, "effective_price": "2500.00",
  "time_takes": "1", "time_unit": "hours",
  "warrenty_info": "1 year warranty",
  "recommended_info": "When battery is 3+ years old",
  "interval_info": "Every 3-4 years",
  "note": "Old battery taken for recycling.",
  "inclusions": [
    { "id": 1, "label": "New Battery (Amaron/Exide)", "image": "http://localhost:8000/storage/entity-images/service-inclusions/battery.webp", "position": 1 },
    { "id": 2, "label": "Terminal Cleaning",        "image": null, "position": 2 },
    { "id": 3, "label": "Charging System Check",    "image": null, "position": 3 }
  ]
}
```
`image` + inclusion images are full `/storage/...` URLs; inclusions are ordered by
`position`; missing thumbnails stay `null`. (In the live HTTP response the category —
with its own resolved `image`/`icon_image` URLs — is returned at the top-level
`category` key; `category_detail` is omitted unless the relation is eager-loaded.)

---

## 7. Test results

New file **`tests/Feature/ServiceDataPhase1Test.php`** — 10 tests, all green:
- `service_inclusions` table + columns exist; `services.interval_info` exists.
- `Service hasMany inclusions` returns them **ordered by position**.
- Deleting a service **cascade-deletes** its inclusions.
- Detail API returns `interval_info` + ordered `inclusions[]` with **full image URLs**
  (and `null` for inclusions without a thumbnail).
- Empty service (no image/inclusions) still works → `image:null`, `interval_info:null`,
  `inclusions:[]`.
- List endpoint returns `interval_info` + full image URL, **no `inclusions` key** (lean).
- Category `image` + `icon_image` resolved to **full URLs**.
- Filament: admin **fills `interval_info` + 3 inclusions on the Service form → create →
  persisted in order**; `icon_image` field present on the Category form.

```
Full suite:  ./vendor/bin/pest  →  298 passed (1221 assertions)
(288 prior + 10 new; 0 regressions)
php artisan migrate        → clean, both new migrations Ran
filament:cache-components  → builds; caches cleared for dev
```

Manual checks (PART F-22) covered by the above: admin fill+save+reorder (test 9),
persistence/order (test 9), detail API new fields + full URLs + inclusions (test 5),
category image/icon save (form test 10 + API test 8), existing empty services still
work (test 6).

---

## 8. Deviations / notes

1. **Less work than scoped.** Most service fields (image, description, time_takes/unit,
   warrenty_info, recommended_info, note, base_price) and the category `image` +
   `description` were **already** editable; list image columns already present. Only
   `interval_info`, the `inclusions` Repeater, and category `icon_image` were actually
   missing — those are what got added. No redundant re-adds.
2. **ImageUrl now applied to the main service/category resources.** This changes
   `image`/`icon_image` from raw relative paths to full URLs — but only for **populated**
   images (null→null), so it's behavior-preserving for all current data and no test
   relied on the raw value. Brings these in line with the L1 `hero_image_url` pattern.
3. **Inclusions are detail-only in the API** (`whenLoaded`, eager-loaded only in
   `@detail`) to keep `/services` and category lists lean (D-P1-5). Documented; Phase 2
   reads them from the detail endpoint.
4. **`CleansOldImage` watches `image` only.** `ServiceInclusion.image` is covered;
   category `icon_image` is **not** auto-cleaned on overwrite (the trait is image-only
   by design, and the `{slug}-icon` filename makes overwrite-in-place the norm anyway).
   Consistent with prior behavior — flagged, not a blocker.
5. **No frontend changes** (per constraint). The new JSON keys are additive; the TS
   types (`SubService.inclusions`, `interval_info`) and any rendering land in Phase 2.
6. **Git left to the operator** (D-P1-7).

**Phase 2 (layout/rendering) NOT started.** Operator can now populate sample data via
the admin (Service → image/description/duration/warranty/recommended/interval/note +
inclusions; Category → image/icon/description) to test Phase 2 against.
