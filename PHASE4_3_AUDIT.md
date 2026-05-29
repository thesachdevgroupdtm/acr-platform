# Phase 4.3 — Pre-flight Audit (PART A)

## 1. Schema state (verified via `php artisan db:table`)

### `car_brands` — 7 columns

```
id (bigint pk), name (string), slug (string, unique),
image (string nullable), is_active (boolean default 1),
created_at, updated_at
```

### `car_models` — 8 columns

```
id (bigint pk), brand_id (FK car_brands cascade), name (string),
slug (string), image (nullable), is_active (default 1),
created_at, updated_at
Unique: (brand_id, slug)
```

### `fuel_types` — 6 columns

```
id (bigint pk), name (string), slug (string, unique),
is_active (default 1), created_at, updated_at
```

**Note:** spec D-4.3-9 mentions a `sort_order` field. The current
table doesn't have one. We'll work around in the import (use
provided order from Excel; otherwise insertion order).

### `services` — 15 columns

```
id (bigint pk), category_id (FK), name (string), slug (string),
description (text nullable), image (nullable), base_price (decimal nullable),
time_takes (string nullable), time_unit (string nullable),
warrenty_info (text nullable), recommended_info (text nullable),
note (text nullable), is_active (default 1, indexed),
created_at, updated_at
```

### `service_categories` — 10 columns

```
id (bigint pk), name (string), slug (string, unique),
description (text nullable), image, icon_image,
position (smallint default 0), is_active (default 1),
created_at, updated_at
```

### `service_prices` — 8 columns

```
id (bigint pk),
service_id (FK), brand_id (FK), model_id (FK), fuel_type_id (FK),
price (decimal),
created_at, updated_at

Indexes:
  svcprice_full_unique (service_id, brand_id, model_id, fuel_type_id) — UNIQUE
  svcprice_vehicle_idx (brand_id, model_id, fuel_type_id)
```

The unique composite key is **exactly** what we need for the
matrix-to-relational upsert. No new indexes required.

## 2. NULL slug counts (D-4.3-15)

```
brands:   0   ← clean
models:   0   ← clean
services: 0   ← clean
fuels:    0   ← clean
```

**Zero NULL slugs anywhere.** Phase 4.3 step "fill NULL slugs via
cleanup migration" is a no-op — skipping the migration entirely.

## 3. Current data counts

| Table              | Rows |
|--------------------|------|
| car_brands         | 14   |
| car_models         | 81   |
| fuel_types         | 4    |
| service_categories | 12   |
| services           | 40   |
| service_prices     | 1,296 |

## 4. Full services list (40 rows, sorted by name)

Becomes the lookup target for matrix-column → service_id mapping:

```
 1 | Battery Charging                 | battery-charging
 2 | Battery Replacement              | battery-replacement
 3 | Flat Bed Towing                  | flat-bed-towing
 4 | Wheel lift towing ( 10 Kms )     | wheel-lift-towing-10-kms
 5 | Battery jump start               | battery-jump-start
 6 | Full AC service                  | full-ac-service
 7 | Periodic AC Service              | periodic-ac-service
 8 | Comprehensive Service            | comprehensive-service
 9 | Standard Service                 | standard-service
10 | Primary Service                  | primary-service
11 | Front Brake Disc Replacement     | front-brake-disc-replacement
12 | Front Brake Pad Replacement      | front-brake-pad-replacement
13 | Rear Brake Shoes Replacement     | rear-brake-shoes-replacement
14 | Disc Turning                     | disc-turning
15 | Brake Drums Turning              | brake-drums-turning
16 | Tyre Rotation                    | tyre-rotation
17 | Wheel Alignment                  | wheel-alignment
18 | Wheel Balancing                  | wheel-balancing
19 | Complete Wheel Care              | complete-wheel-care
20 | Front Bumper Paint               | front-bumper-paint
21 | Rear Bumper Paint                | rear-bumper-paint
22 | Bonnet Paint                     | bonnet-paint
23 | Full Body Paint                  | full-body-paint
24 | Car Wash                         | car-wash
25 | Interior Dry Cleaning            | interior-dry-cleaning
26 | Exterior Rubbing & Polishing     | exterior-rubbing-polishing
27 | Complete Car Detailing           | complete-car-detailing
28 | Teflon Coating                   | teflon-coating
29 | Ceramic Coating                  | ceramic-coating
30 | Alternator New                   | alternator-new
31 | Cooling Coil Replacement         | cooling-coil-replacement
32 | Car Inspection                   | car-inspection
33 | Front Headlight Replacement      | front-headlight-replacement
34 | Front Windshield Replacement     | front-windshield-replacement
35 | Clutch Assembly                  | clutch-assembly
36 | Clutch Overhaul                  | clutch-overhaul
37 | Front Shock Absorber Replacement | front-shock-absorber-replacement
38 | Suspension Overhaul              | suspension-overhaul
39 | Windshield Replacement Claim     | windshield-replacement-claim
40 | Accidental Claim                 | accidental-claim
```

## 5. Excel header → service mapping seed plan

Operator's actual headers (sample from spec):
`Car_id, Make, Model, Fuel_Type, Segment, primary_service,
 standard_service, comprehensive_service, periodic_ac_service,
 Full_ac_service, AC gas refill, Front Brake Disc, …`

Mapping strategy (Layer 1 exact + Layer 2 alias + Layer 3 fuzzy
in seeder):

| Excel column                | service_id | Confidence  |
|-----------------------------|-----------:|-------------|
| primary_service             | 10         | exact (case-insens, underscore-normalised) |
| standard_service            | 9          | exact       |
| comprehensive_service       | 8          | exact       |
| periodic_ac_service         | 7          | exact       |
| Full_ac_service             | 6          | exact       |
| Front Brake Disc            | 11         | alias       |
| Battery Charging            | 1          | exact       |
| Battery Replacement         | 2          | exact       |
| (etc — see `ServiceColumnMappingSeeder` for full list) |

The seeder will pre-populate ~30-40 high-confidence mappings to
save operator manual mapping on first import. Anything ambiguous
(e.g. `AC gas refill` — no exact service match) is left unmapped
and surfaces in the preview UI.

## 6. Maatwebsite/Excel install

```
composer require maatwebsite/excel --ignore-platform-req=ext-gd
→ Installed maatwebsite/excel v3.1.69 (Apr 2026)
```

`--ignore-platform-req=ext-gd` used because PHP CLI on this
Windows box doesn't have GD enabled. **GD is only needed for
in-cell image rendering** — we're doing data-only import/export,
so this is safe. Production env will likely have GD; the platform
ignore is composer-only and doesn't affect runtime.

No config publish needed in v3.1.69 — auto-discovers via service
provider.

## 7. Backend test baseline

```
Tests: 155 passed (679 assertions)
Duration: 108.10s
```

Phase 4.3 target: 155 + ~28 = ~183 passing. CRITICAL: zero
regressions on the 155.

## 8. Files this audit changes

None — audit is read-only. Composer ran (package installed). The
4.3 diff begins at PART B.

— Audit complete · proceed to PART B
