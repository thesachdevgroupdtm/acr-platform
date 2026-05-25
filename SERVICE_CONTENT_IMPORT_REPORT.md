# SERVICE_CONTENT_IMPORT_REPORT — Phase: old→acr_v3 content import (DRY-RUN)

A safe, re-runnable artisan command `service-content:import` that maps the two old
acr2025 dumps → acr_v3 **by slug**, additive + NULL-only, with a pattern filter on the
dirty warranty/recommended columns. **This report covers PART A (audit) + PART B
(dry-run) + PART D (tests). No real write to acr_v3 has been performed — awaiting your
go-ahead for PART C.**

**Status:** dry-run clean · full suite **313 passed** (305 prior + 8 new) · 0 regressions.

---

## PART A — Audit (read-only)

- **Dev connection confirmed:** `\DB::connection()->getDatabaseName()` → **`acr_v3`** ✓
- **Dumps parsed as text** (not executed): `sceduled_packages.sql` → **91 packages**,
  `package_specification.sql` → **549 inclusion rows**.
- **Slug resolution** (explicit map, no runtime fuzzy — D-IMP-1/2/3):
  - **73 exact** (old.slug === service.slug) + **17 near** (hard-coded corrected map) =
    **90 packages → 90 distinct current services**, all resolved with a live service.
  - **1 skipped:** `rear-shock-absorber-replacement` (no current target, D-IMP-3) — logged, not created.
  - **0 unexpected misses** → the safety gate (STOP-if-unmatched) passed; the command
    would proceed.

---

## DRY-RUN plan (against acr_v3 — nothing written)

```
Parsed 91 packages, 549 inclusion rows.

Inclusions:        90 services → 543 rows        (549 − 6 for skipped rear-shock)
time_takes/unit:   73 services
warrenty_info:     40 services set, 50 rejected by pattern
recommended_info:  19 services set, 55 rejected by pattern
interval_info:      5 services seeded
skipped packages:  rear-shock-absorber-replacement
```

### interval_info seeded (all 5 — from km-cadence recommended copy, D-IMP-7)
```
[full-ac-service]       "After every 10,000 kms or 1 year (Recommended)"
[periodic-ac-service]   "After every 5,000 kms or 3 Months (Recommended)"
[comprehensive-service] "After every 20,000 kms or 12 Months (Recommended)"
[standard-service]      "After every 10,000 kms or 3 Months (Recommended)"
[primary-service]       "After every 5,000 kms or 3 Months (Recommended)"
```

### warrenty_info REJECTED by pattern — 50 (left NULL for operator)
The old `warrenty_info` column is dirty — most values are **symptoms/conditions**, not
warranties. All correctly left NULL:
```
battery-charging "Car Does Not Starts" · battery-replacement "Car Does Not Starts" ·
flat-bed-towing-upto-10km "Door-Step Service Available" · wheel-lift-towing-upto-10km /
battery-jump-start "Doorstep Service Available" · disc-turning "Vibrations on Steering
Wheel When Stopping" · brake-drums-turning "Screeching Noise From Brakes" · car-wash
"Dust and Dirt Accumulate…" · teflon/ceramic/nano-coating "UV Rays Damage" · alternator-*
"Frequent Battery Discharging" · *-replacement "Broken or Cracked …" · second-hand-car-
inspection "Buying a Second-Hand Car" · …(50 total)
```
**⚠️ Eyeshot flag:** a handful of rejected warranty values are actually **interval data
mislabeled into the warranty column**, using "miles" (so they don't match the km pattern):
```
front-brake-pad   "Every 25,000 to 60,000 miles"
rear-brake-shoes  "Every 25,000 to 65,000 miles"
tyre-rotation     "Every 3,000 to 5,000 miles"
wheel-balancing   "Every 5,000 to 6,000 miles"
complete-wheel-care "Every 5,000 to 6,000 miles"
```
These are left NULL (spec-correct). You may want to hand-enter them as `interval_info`
in admin after the import.

### recommended_info REJECTED by pattern — 55 (left NULL for operator)
Mostly marketing ("Free Pick-Up & Drop" ×~20) and symptoms ("Reduced Braking
Efficiency", "Swirl Marks", "Leakage", "Dead Battery", …). All correctly left NULL:
```
battery-* "Electrical System Does Not Work" · tyre-rotation/car-wash/alternator-*/many
"Free Pick-Up & Drop" · interior-dry-cleaning "Stains and Dust on Seats" · *-coating
"Swirl Marks" · wheel-alignment "Car Pulling to One Side" · radiator-replacement
"Blockage in Radiator" · accidential-claim "Insurance claim Advice" · …(55 total)
```

(Full lists are printed verbatim by the command; the above are condensed for the report.)

**Read of the plan:** conservative and correct — only **40 warranty** + **19
recommended** values that genuinely look like warranty/recommendation copy pass; the
~105 dirty/symptom/marketing values are left NULL for you. **543 inclusions** attach to
**90 services** (the cleanest, highest-value payload), **73** get duration. `group_name`
stays NULL on every new inclusion (so `inclusions:autogroup` runs cleanly afterward).
Price / image / note are not written (D-IMP-8). `service_prices`, slugs, categories are
untouched (D-IMP-9).

---

## PART C — Real run (EXECUTED + APPROVED)

`php artisan service-content:import` ran inside one DB transaction and **committed
successfully**. Verification counts (step 9) — exactly matching the dry-run plan:

| Metric | Count |
|---|---|
| services | **92** |
| inclusions total | **543** |
| services with ≥1 inclusion | **90** |
| with time_takes | **73** |
| with warrenty_info | **40** |
| with recommended_info | **19** |
| with interval_info | **5** |
| new inclusions with group_name = NULL | **543** ✓ (autogroup runs next, separately) |

### Idempotency — second run was a clean no-op
Running `service-content:import` a second time:
```
Inclusions: 0 services → 0 rows  (empty-guard skipped 90 already-populated services)
time_takes/time_unit: 0 services (73 already set)
warrenty_info: 0 set · recommended_info: 0 set · interval_info: 0 seeded
```
Post-commit counts **unchanged** (services 92 · inclusions 543 · with-inclusions 90 ·
time 73 · warranty 40 · recommended 19 · interval 5). **0 inserts, 0 updates** — the
empty-guard (inclusions) + NULL-only (columns) guarantees hold against re-runs. ✓

---

## PART D — Test results

New file `tests/Feature/ImportServiceContentTest.php` — **8 tests, all pass**:
- slug resolve: exact / near (`boot-paint`→`boot-point`, `front-brake-disc-replacement`→`front-brake-disc`) / rear-shock → null skip.
- warranty pattern: real warranty passes; `"Car Does Not Starts"`, `"Doorstep Service Available"`, null → rejected.
- recommended pattern: `"After every 10,000 kms or 1 year"` passes; symptom rejected.
- interval seed: fires on `"After every 5,000 kms…"`, not on plain text / "Recommended".
- time-unit map: Hour→hours, Day→days, null→null.
- `parseInsertValues`: NULL + escaped quote (`O\'Brien`) parse correctly.
- **end-to-end against the real dumps**: seeds all resolved targets, runs the command →
  inclusions == (549 − rear-shock), services-with-inclusions == 90, `battery-charging`
  has 6 ordered inclusions (pos 1 = "Available at Doorstep"), its symptom warranty stays
  NULL, time set to `24 hours`; `full-ac-service` gets warranty + recommended + interval;
  `group_name` NULL on all; rear-shock never created. **Idempotent: 2nd run inserts 0,
  columns unchanged.**
- `--dry-run` writes nothing (inclusions/time/warranty all stay 0/NULL).

```
Full suite:  ./vendor/bin/pest  →  313 passed (1310 assertions)
             (305 prior + 8 new; 0 regressions)
Dry-run (acr_v3): clean, nothing written
```

---

## Deviations / notes

1. **Inclusion count is 543, not 549** — the 6 inclusions belonging to the skipped
   `rear-shock-absorber-replacement` are intentionally not imported (D-IMP-3).
2. **Mislabeled "Every N miles" warranty values** (5 brake/wheel rows) are left NULL by
   the km-pattern (spec-correct) — flagged above for optional manual `interval_info` entry.
3. **Pattern is conservative by design** (D-IMP-6): 40/90 warranty + 19/90 recommended
   pass. The rest are dirty (symptoms / "Free Pick-Up & Drop") and left NULL for admin —
   this is the intended safety, not data loss.
4. **Connection guard:** the acr_v3 check is done by me in PART A (read-only tinker), not
   baked into the command — so the command stays connection-agnostic and the Pest suite
   (SQLite) can exercise it end-to-end. The command's own safety rails (additive,
   NULL-only, empty-guard, transaction, STOP-on-unmatched) protect the real run.
5. **No frontend, no migrations, no schema changes** — pure import command + tests.
6. `inclusions:autogroup` is **not** run here; it's the next step after the real import
   (new inclusions land with `group_name = NULL`).

---

## Files (git left to operator — not run)

**New:**
- `backend/app/Console/Commands/ImportServiceContent.php`
- `backend/tests/Feature/ImportServiceContentTest.php`
- `SERVICE_CONTENT_IMPORT_REPORT.md` (this file)

**Modified:** none. **Migrations:** none.

---

**DONE — import committed + idempotency verified.** Next step (separate, on request):
`php artisan inclusions:autogroup --dry-run` then the real autogroup run to bucket the
543 new inclusions into Essential/Performance/Additional (they all currently have
`group_name = NULL`). Not run here, per instruction.
