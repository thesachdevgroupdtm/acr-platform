# IMAGE-URL-FIX — API returns full storage URLs, not relative paths

L1 API resources now return fully-qualified storage URLs for `hero_image_url`
(via a single centralized helper) instead of raw relative paths, so the frontend
loads images from the backend origin regardless of where it runs. Production-safe:
URLs derive from `APP_URL` — no hardcoded host/domain anywhere.

**Result: 260 passed (1071 assertions)** — 254 baseline + 6 new. Backend only; image
upload system / storage paths / frontend untouched.

---

## 1. Config verification (PART A)

- **`config/filesystems.php` `public` disk** — already env-driven, **no change needed**:
  ```php
  'public' => [
      'driver' => 'local',
      'root'   => storage_path('app/public'),
      'url'    => env('APP_URL').'/storage',   // ✅ derives from APP_URL
      'visibility' => 'public',
      'throw'  => false,
  ],
  ```
- **`.env` `APP_URL`** = `http://localhost:8000` (verified, **not changed** per
  constraint). Note: the operator's root-cause note assumed `http://127.0.0.1:8000`;
  the actual value is `localhost`. Both point to the same host and `<img>` loads are
  not CORS-restricted, so images display either way. If you prefer `127.0.0.1`, change
  the single `.env` line — the helper picks it up automatically.
- **Storage URL generation** (tinker):
  ```
  Storage::disk('public')->url('entity-images/brands/audi.webp')
  → http://localhost:8000/storage/entity-images/brands/audi.webp   ✅ full URL
  ```

## 2. ImageUrl helper (PART B)

New `app/Support/ImageUrl.php` — single source of truth (D-URL-1/2):
```php
ImageUrl::resolve(?string $path): ?string
  - null/empty            → null   (frontend renders its own fallback)
  - http(s):// …          → as-is  (idempotent — safe to double-resolve)
  - "entity-images/…"     → Storage::disk('public')->url($path)
```
No hardcoded host; the host comes from the `public` disk `url` config = `APP_URL`.

## 3. Resources updated (PART C)

| Resource | Change |
|---|---|
| `Api/V1/BrandResource` | `hero_image_url => ImageUrl::resolve($this->image)` |
| `Api/V1/ModelResource` | `hero_image_url => ImageUrl::resolve($this->image)` |
| `Api/V1/CategoryResource` | `hero_image_url => ImageUrl::resolve($this->image)` |
| `Api/V1/ServiceResource` | `hero_image_url => ImageUrl::resolve($this->image)` |
| `Api/V1/FuelResource` | **added** `hero_image_url => ImageUrl::resolve($this->image)` |

Each got `use App\Support\ImageUrl;`. (ServiceResource's nested `category` reuses
`CategoryResource`, so nested category images are normalized too.)

## 4. Before / after API response

`GET /api/v1/public/vehicles/brands`:
```diff
- "hero_image_url": "entity-images/brands/audi.webp"            ← relative (404 on frontend origin)
+ "hero_image_url": "http://localhost:8000/storage/entity-images/brands/audi.webp"   ← full URL
```
Null images stay `null` (unchanged) so the frontend fallback still triggers.

## 5. Test results (PART D)

`260 passed (1071 assertions)` — **6 new** in `tests/Feature/Images/ImageUrlTest.php`:
`resolve(null/'')→null`; relative→full `/storage` URL; absolute-URL passthrough +
idempotent double-resolve; `BrandResource.hero_image_url` is a full URL when set and
`null` when not; `FuelResource.hero_image_url` full-or-null. Assertions are
host-agnostic (`startsWith http` + `contains /storage/…`) so they hold for any
`APP_URL`. Existing L1 API tests (Brand/Category/Service/Fuel `assertJsonStructure`)
still pass — the keys are unchanged; only the values are now absolute. No test asserted
a relative-path value, so none needed updating.

Caches cleared: `php artisan config:clear` + `cache:clear`.

## 6. Production note (D-URL-4)

`Storage::disk('public')->url()` builds `APP_URL + /storage/…`, so on production you
**must** set:
```
APP_URL=https://acr-mechanics.in
```
Then `hero_image_url` becomes `https://acr-mechanics.in/storage/entity-images/…`
automatically — no code change. Ensure `php artisan storage:link` has run on prod and
`config:cache` is refreshed after changing `APP_URL`.

## 7. Manual verification

```
curl http://127.0.0.1:8000/api/v1/public/vehicles/brands
# expect: "hero_image_url":"http://localhost:8000/storage/entity-images/brands/<slug>.<ext>"
```
(Not executed here — needs the running server plus a brand row whose `image` is set in
the dev DB; the helper + resource tests + the tinker `Storage::url` check confirm the
full-URL output.) Operator: hard-refresh the frontend — brand/model/service/category
images should now load from the backend origin instead of 404-ing against
`localhost:3000`.

## 8. Deviations

- **APP_URL host** is `localhost` (not `127.0.0.1`) in `.env`; left unchanged per the
  "do not change .env" constraint — documented above.
- **FuelResource gained `hero_image_url`** (it had none). Justified: fuel types now have
  an `image` column (prior task), D-URL-3 lists FuelResource, the change is additive
  (frontend ignores unknown keys) and test-safe (`FuelApiTest` uses presence-only
  structure). Updated its stale "no image column" doc comment.
- **`config/filesystems.php` needed no edit** — the `public` disk `url` was already
  `env('APP_URL').'/storage'`.
