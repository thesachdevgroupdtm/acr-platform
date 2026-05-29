# Phase 4.5c — Pre-flight Audit (PART A)

> Findings from reading current source **before** any 4.5c edits.
> Each section below documents what exists today and how 4.5c will
> intercept it. No code changed yet — this doc is the contract for
> the rest of the phase.

---

## 1. `SeoFieldGroup::make()` — current signature

`backend/app/Filament/Forms/Components/SeoFieldGroup.php` line 37:

```php
public static function make(): array
```

- Takes **zero parameters** today.
- Returns one Section wrapping 5 Tabs (Basic / OG / Twitter / Schema
  / Advanced).
- `schema_type` Select hardcoded `->default('None')` (line 156).

**4.5c plan (PART C).** Add an optional first arg:

```php
public static function make(string $defaultSchemaType = 'None'): array
```

The Schema-tab Select passes `->default($defaultSchemaType)` so each
resource that calls `SeoFieldGroup::make('Service')` etc. gets a
sensible per-record-type default. Existing `SeoPageResource` call
(no-arg) keeps `'None'` — backwards compatible.

---

## 2. Phase 4.5b inline SEO persistence in SeoPageResource

`backend/app/Filament/Resources/SeoPageResource.php` line 233 owns
the 20-field list as a **public static method**:

```php
public static function seoFieldNames(): array {
    return ['meta_title', …, 'changefreq'];   // 20 names
}
```

`CreateSeoPage.php` and `EditSeoPage.php` each duplicate the SEO
save logic inline:

- **Create** — `afterCreate()` calls a private `saveSeoFromForm()`
  that slices form state by `SeoPageResource::seoFieldNames()`,
  filters null / empty, then `$this->record->setSeoData($seoData)`.
- **Edit** — `mutateFormDataBeforeFill()` merges
  `$this->record->seoMetadata->{name}` into `$data` for every name;
  `afterSave()` runs the same `saveSeoFromForm()` as Create.

**4.5c plan (PART B).** Lift the field list + save / load logic into
a trait at `backend/app/Filament/Concerns/HandlesSeoFormPersistence.php`
with three methods (`seoFieldNames`, `saveSeoFromForm`,
`loadSeoIntoForm`). Refactor the 2 SeoPage* pages to use the trait
**without changing observable behaviour**, then drop in on the 6
new Create/Edit page classes for Service / ServiceCategory /
ServiceCenter.

The static `SeoPageResource::seoFieldNames()` will keep delegating
for backwards compatibility (existing call sites stay valid).

---

## 3. API controller response shapes (current)

### 3.1 `HomeController@index`

Already emits a `seo` key — but in the **legacy nested shape**:

```php
'seo' => [
    'title'       => '…',
    'description' => '…',
    'keywords'    => '…',
    'canonical'   => null,
    'og'          => ['title' => '…', 'description' => '…', 'type' => 'website', 'site_name' => '…'],
    'twitter'     => ['card' => 'summary_large_image'],
],
```

**4.5c plan.** Replace `seoDefault()` with the flat `SeoFlatData`
shape (matching `HasSeoMetadata::getSeoData()` / Phase 4.5b
`/api/v1/seo-pages/{slug}` response). Pull dynamic values from
`SiteSeoSettings::current()` so admin overrides flow through.

### 3.2 `ServiceController@index|show|detail`

Three methods, each emits a 2-field nested `seo`:

```php
'seo' => [ 'title' => '…', 'description' => '…' ],
```

No eager loading of `seoMetadata` — list endpoint would trigger
N+1 if we attached per-item seo as-is.

**4.5c plan.** Eager-load `seoMetadata` on the category list. Switch
each method's `seo` value to `$model->getSeoData()` (flat shape).
For the list endpoint, attach per-item `seo` to each ServiceResource
output. Top-level `seo` on category page = category's data;
top-level `seo` on detail page = service's data.

### 3.3 `ServiceCentersController@index` (V1\Public\)

Currently 27 lines, list-only, no SEO at all:

```php
return response()->json([
    'service_centers' => ServiceCenterResource::collection($centers),
]);
```

No detail (`show`) method. **`routes/api.php` line 110** only registers
`GET /api/v1/service-centers` (list).

**4.5c plan.**
- Add `show($slug)` method on the controller — flat seo for the
  named center + the existing fields.
- Register `Route::get('service-centers/{slug}', [..., 'show'])`
  in `routes/api.php` adjacent to the existing list route.
- Update list to eager-load `with('seoMetadata')` and attach per-item
  `seo` field.

---

## 4. `routes/api.php` service-centers coverage

```
Line 110: Route::get('service-centers', [ServiceCentersController::class, 'index'])
            ->middleware('throttle:public-read');
```

**Detail route missing.** Add one in PART G; throttle middleware
follows the list pattern.

---

## 5. ServiceCenter model — confirmation

`backend/app/Models/ServiceCenter.php` already:
- `use HasFactory, HasSeoMetadata;` (line 21) — so `getSeoData()`
  / `setSeoData()` already available.
- 12 `$fillable` columns matching the verified schema.
- `decimal:7` casts on lat/lng.

**Missing:** `orders()` relationship. The delete-block in PART F
needs it. Will add one HasMany method (`return $this->hasMany(Order::class);`)
— smallest possible model change. Not a schema change.

---

## 6. Frontend `SeoHead` component — confirmation

`src/components/SeoHead.tsx` exists (Phase 4.5b). Consumes the
**flat** `SeoFlatData` shape exported from `src/lib/api.ts` line 710.
Currently only `SeoPageView.tsx` imports it.

`src/main.tsx` line 5 already mounts `HelmetProvider` — no provider
plumbing needed.

`src/lib/SeoHead.tsx` (legacy, Phase 1.6) exists as orphan. **Will
not be touched** per D-4.5c-7.

---

## 7. Existing TypeScript `seo` field on responses

`src/lib/api.ts` already declares `seo?: SeoPayload` on
`HomeResponse`, `ServicesResponse`, `CategoryDetailResponse`,
`ServiceDetailResponse`, and `PageResponse`. `SeoPayload` is the
**legacy nested shape** (line 314), distinct from `SeoFlatData`
(line 710).

**No current consumer reads `seo.og.title` etc.** Confirmed: zero
files import a SeoHead that consumes `SeoPayload`. The legacy field
is dead in practice.

**4.5c plan (PART H).** Replace `seo?: SeoPayload` →
`seo?: SeoFlatData` on the 5 response interfaces touched this phase.
Add a new `ServiceCentersResponse` for completeness. The
`SeoPayload` interface itself stays for now (other endpoints may
still emit it; we're not deleting unused types this phase).

---

## 8. Backend test baseline

```
Tests: 118 passed (534 assertions)
Time:  26.88s
```

Confirmed via prior session handoff. Will re-run after each PART to
catch regressions immediately.

---

## 9. Files this audit changes

**None.** Audit is read-only. The 4.5c diff begins at PART B.

— Audit complete · ready to start PART B
