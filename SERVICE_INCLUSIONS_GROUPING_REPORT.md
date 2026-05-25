# SERVICE_INCLUSIONS_GROUPING_REPORT — Phase 1.5 (additive backend only)

Adds GoMechanic-style grouping to service inclusions: a nullable
`service_inclusions.group_name` ("Essential" / "Performance" /
"Additional"), exposed in Filament + the detail API, plus a re-runnable
`inclusions:autogroup` keyword classifier that buckets ungrouped rows
(NULL-only — never overwrites an operator's choice).

**Additive only · no frontend · no new packages · no slug/price changes.**
**Full suite: 305 passed (was 298 + 7 new), zero regressions.**

---

## PART A — Schema audit (before code)

```
service_inclusions exists : YES
columns (pre-migration)    : id, service_id, label, image, position, created_at, updated_at
group_name already exists  : NO
total rows (this sandbox)  : 0
rows with group_name NULL  : 0  (table empty here)
```

**Note on data location:** the old-data import the task describes ran in the
operator's environment — **`service_inclusions` is empty in this local sandbox**
(0 rows, 0 services). The grouping engineering does not depend on populated data, so
it's fully built + tested below. For the dry-run accuracy preview (which needs real
labels), I classified the **549 `specification` values straight from the import source
dump** (`backend/storage/app/imports/package_specification.sql`) through the *same*
`AutogroupInclusions::classify()` method — so the projection matches exactly what
`inclusions:autogroup --dry-run` will print once run on the populated DB.

15 sample labels could not be drawn locally (empty); the full real label set is in the
dry-run preview below.

---

## PART B — Migration

`backend/database/migrations/2026_05_24_130000_add_group_name_to_service_inclusions_table.php`
— guarded (`Schema::hasColumn`), additive:
```php
$table->string('group_name')->nullable()->after('label');  // AFTER ignored on SQLite, orders on MySQL
```
`php artisan migrate` → **Ran**; `migrate:status` confirms. Existing rows keep
`group_name = NULL` (untouched). `down()` drops only if present.

> Laravel 10.50.2: the SQLite grammar has no `modifyAfter`, so `->after()` is silently
> ignored on the test DB (no error) and orders the column on prod MySQL.

---

## PART C — Model

`app/Models/ServiceInclusion.php`: added `'group_name'` to `$fillable` (plain string,
no cast). `CleansOldImage` + `position` behaviour unchanged.

---

## PART D — Filament (ServiceResource "What's Included" Repeater)

`app/Filament/Resources/ServiceResource.php` — added a `group_name` Select **before**
`label` inside the relationship Repeater (still `orderColumn('position')`):
```php
Forms\Components\Select::make('group_name')
    ->label('Group')
    ->options(['Essential' => 'Essential', 'Performance' => 'Performance', 'Additional' => 'Additional'])
    ->placeholder('Ungrouped')   // nullable, default null
    ->native(false)
    ->columnSpan(1),
```
Item-label polish: each Repeater row now reads `Label  ·  Group` (group omitted when
unset) so the operator can scan buckets fast. `filament:cache-components` builds clean
("All done!"); caches cleared for dev.

---

## PART E — API (additive)

`app/Http/Resources/ServiceResource.php` — detail `inclusions[]` entries now carry
`group_name` (raw string or `null`) alongside `id/label/image/position`. Still gated by
`whenLoaded('inclusions')` (detail-only; list endpoints unchanged). Verified shape:
```json
"inclusions": [
  { "id": 1, "label": "Spark Plug",     "group_name": "Performance", "image": null, "position": 1 },
  { "id": 2, "label": "Ungrouped Item", "group_name": null,          "image": null, "position": 2 }
]
```

---

## PART F — `inclusions:autogroup` (re-runnable, NULL-only)

`app/Console/Commands/AutogroupInclusions.php`:
- `inclusions:autogroup` → updates only `WHERE group_name IS NULL`; prints per-group counts.
- `--dry-run` → prints the `{label → proposed group}` table + summary, **writes nothing**.
- Keyword map per **D-1.5-4**, checked **Performance → Additional → Essential** (case-insensitive substring), in a shared pure `public static function classify(string $label): string` (one source of truth for command + tests + this report's preview).
- Idempotent: a second run finds no NULL rows → no-op. Operator-set groups are never touched.

---

## Auto-classifier DRY-RUN preview (over the 549 import-source labels)

**Per-group totals (all 549 rows): Essential = 468 · Performance = 23 · Additional = 58.**
Distinct labels under each bucket (×N = duplicate occurrences across packages). This is
the operator's accuracy eyeshot **before** the real run.

### → Performance (20 distinct / 23 rows)
```
AC Gas Top Up (400gms) · AC Gas Top Up (600gms) · Battery Water Top Up (x2) ·
Brake and Clutch Fluid Top Up upto (50 ml) · Brake Fluid Top Up upto (100 ml) ·
Coolant Check · Coolant Drainage · Coolant Draining · Coolant Leakage Inspection ·
Coolant Refilling · Coolant Replacement - Labor · Coolant Top Up upto (2 litre) ·
Coolant Top Up upto (200 ml) · Coolant Top Up upto (500 ml) ·
Electronic Throttle Control (ETC) Testing · Spark Plug Clean & Check ·
Spark Plug Cleaning · Spark Plug Cleaning & Check · System Performance Test ·
Wiper Fluid Top Up (x3)
```

### → Additional (22 distinct / 58 rows)
```
Alloy Polishing (x2) · Ceramic Coating · Clean Interior · Cleaning and Detailing ·
Complimentary full car wash · Dashboard Polishing (x2) · Exterior and Interior Inspection ·
Exterior Body Wash · Exterior Body Wash & Interior Body Vacuuming with dashboard Polishing (x2) ·
Exterior Inspection · Exterior Pressure Wash · Interior Body Vacuuming with Polishing ·
Interior Vacumming (x2) · Interior Vacuuming and dashboard polishing ·
Polishing With Compound (x5) · Pressure Wash (x5) · Rubbing and Polishing (x16) ·
Stains and Marks on Interior Surfaces · Teflon Coating · Tyre Cleaning and Dressing (x6) ·
Upto 50% off on detailing service · Wax Polishing (x5)
```

### → Essential (351 distinct / 468 rows) — the default bucket
Everything else. A representative slice (full list available by running
`inclusions:autogroup --dry-run` on the populated DB):
```
20/30/50 Points Check · AC Filter Cleaning/Replacement · AC Inspection · Air Filter
Cleaning/Inspection/Replacement · Alignment Check/Adjustment/Verification · Alternator
Inspection/Repair/Replacement - Labor · Battery Inspection/Testing/Assessment · Brake
Fluid Check · Brake Inspection · Brake Pad Service · Caliper Inspection · Clutch Bearing/
Plate/System Testing · Coolant System Inspection · Diagnostic/Electrical Scanning · Engine
Oil Check/Replacement · Final/Initial/Visual Inspection · Fuel Filter Check · *-Labor fit
lines (Shock Absorber, Steering Rack, Windshield, Bumper, Headlight, …) · Road/Test Drive ·
Tyre Rotation · Wheel Alignment/Balancing · Voltage Drop/Output Test · …(351 total)
```

**Operator-review flags (spec-correct, but you may reclassify by hand afterward):**
- The `top up` / `top-up` Performance keyword pulls **all** "Top Up" lines into
  Performance — including `Brake Fluid Top Up`, `Wiper Fluid Top Up`, `Battery Water Top
  Up`. Spec-correct (D-1.5-4), but you may move the fluid top-ups to Essential.
- `Exterior Inspection` / `Exterior and Interior Inspection` land in **Additional**
  (matched `exterior`/`interior`) though they're inspections — reclassify to Essential if
  you prefer.
- All `Coolant *` (incl. checks/inspection) → Performance via the `coolant` keyword.

These are exactly the rows the post-run manual correction in admin is for.

---

## REAL run result

On **this sandbox** the table is empty, so the real run is a safe no-op:
```
$ php artisan inclusions:autogroup
No ungrouped inclusions (group_name IS NULL). Nothing to do.
```
**On the operator's populated DB**, the run will set the ~549 NULL rows to the
distribution previewed above (**Essential 468 · Performance 23 · Additional 58**), then
re-runs are no-ops. (Recommended: `--dry-run` first to confirm against live data, then
the real run, then hand-correct the flagged rows in admin.)

---

## PART G — Test results

New file `tests/Feature/InclusionGroupingPhase15Test.php` — **7 tests, all pass**:
- `group_name` column exists + nullable (null persists).
- `ServiceInclusion` mass-assigns `group_name`.
- `classify()` keyword map: Performance / Additional / Essential samples all bucket correctly.
- Detail API emits `group_name` in `inclusions[]` (string **and** null).
- `--dry-run` writes nothing (rows stay NULL).
- Real run sets NULL rows only, **preserves an operator override** (a "Spark Plug" left in
  Essential is not flipped to Performance), and is **idempotent** (2nd run changes 0 rows).
- Filament: create a service with grouped + ungrouped inclusions → `group_name` persists
  (incl. NULL for the ungrouped one).

```
Full suite:  ./vendor/bin/pest  →  305 passed (1268 assertions)
             (298 prior + 7 new; 0 regressions)
Migration:   php artisan migrate  →  group_name migration Ran
Filament:    filament:cache-components builds clean; caches cleared
Command:     inclusions:autogroup registered (artisan list)
```

---

## Deviations / notes

1. **Local `service_inclusions` is empty** (import ran elsewhere). All code is built +
   unit/feature-tested; the dry-run accuracy preview was computed over the 549 dump
   labels (the import source) using the identical `classify()` — so it equals what the
   command will print on the populated DB. No data was seeded/written here.
2. **`--dry-run` projection ≈ live**, with one caveat: the projection bucketed labels as
   they appear in the dump; if the import normalized/edited any label text, re-run
   `--dry-run` on the live DB for the exact table. Logic is identical either way.
3. Spec-driven edge cases flagged above (fluid top-ups → Performance; exterior/interior
   inspections → Additional) — intentional per D-1.5-4, left for operator hand-correction.
4. **No frontend changes** (Phase 2 will bucket `group_name` and render the three named
   groups; NULL → Essential mapping happens there, not here).

---

## Files changed (git left to operator — not run here)

**New:**
- `backend/database/migrations/2026_05_24_130000_add_group_name_to_service_inclusions_table.php`
- `backend/app/Console/Commands/AutogroupInclusions.php`
- `backend/tests/Feature/InclusionGroupingPhase15Test.php`
- `SERVICE_INCLUSIONS_GROUPING_REPORT.md` (this file)

**Modified:**
- `backend/app/Models/ServiceInclusion.php` (group_name fillable)
- `backend/app/Filament/Resources/ServiceResource.php` (group_name Select + item label)
- `backend/app/Http/Resources/ServiceResource.php` (group_name in detail inclusions[])

(Two throwaway analysis scripts — `_diag_tmp.php`, `_preview_tmp.php` — were created,
run, and deleted; neither remains in the tree.)
