# B7 — 10 (actually 13) hand-corrections applied via `corrections:apply-b7`

**Scope:** SP-PEND-1 (fluid top-ups → Essential) + SP-PEND-2 (inspections → Essential) + SP-PEND-3 (5 services' interval_info seeded). Backend-only — no frontend or schema changes.

**Final state:** ✅ B7 closed. Full Pest suite **337 passing** (was 331; +6 new), zero regressions.

---

## Command

`backend/app/Console/Commands/ApplyHandCorrectionsB7.php` — new artisan command:

```
$ php artisan corrections:apply-b7 --help
Description:
  Apply 10 operator-locked B7 hand-corrections (group_name moves + interval_info seeds). Idempotent.

Usage:
  corrections:apply-b7 [options]

Options:
      --dry-run    Report counts + planned changes; perform zero writes.
```

Auto-discovered by `Console\Kernel::commands()` (no manual registration needed).

Three sub-steps, all wrapped in a single `DB::transaction` on the real run:
- **SP-PEND-1** — UPDATE `service_inclusions` SET `group_name='Essential'` WHERE `group_name='Performance'` AND `LOWER(label) LIKE` one of 3 fluid patterns.
- **SP-PEND-2** — UPDATE `service_inclusions` SET `group_name='Essential'` WHERE `group_name='Additional'` AND `LOWER(TRIM(label))` IN one of 2 exact inspection strings.
- **SP-PEND-3** — UPDATE `services` SET `interval_info` WHERE `slug` IN 5-row map AND `interval_info IS NULL` (idempotent — never overwrites operator-edited values).

Per D-B7-6, zero-match sub-steps print a WARN line and continue rather than failing. Per D-B7-7, the command always prints the resulting group-count distribution + the 5 target services' `interval_info` for verification.

---

## 🚩 Mismatch surfaced + resolution

`D-B7-7` expected post-state was **Essential 467 · Performance 20 · Additional 56**, predicated on SP-PEND-1 moving exactly 3 rows. The dry-run revealed **6 rows match the fluid patterns**, not 3 — because the same labels are catalog inclusions that appear on multiple services:

| Label | Services it appears on | Row count |
|---|---|---|
| `Brake Fluid Top Up upto (100 ml)` | service_id 104 | 1 |
| `Wiper Fluid Top Up` | service_id 102, 103, 104 | 3 |
| `Battery Water Top Up` | service_id 102, 103 | 2 |
| **Total** | | **6** |

Stopped at the dry-run checkpoint, surfaced to operator. Operator chose **"Apply all 6 fluid rows (intent-first)"** — the locked decision ("these 3 labels should be Essential") was about labels, not row counts. Applied uniformly across the catalog so the same label is never classified two different ways.

**Revised post-state:** Essential **470** · Performance **17** · Additional **56** · NULL **0** (was Essential 462 · Performance 23 · Additional 58 · NULL 0).

---

## Dry-run output (verbatim)

```
=== B7 DRY-RUN — no writes will be performed ===

SP-PEND-1 — Move 3 fluid top-ups from Performance → Essential
+----+------------+----------------------------------+--------------------+-------------+
| id | service_id | label                            | from (Performance) | to          |
+----+------------+----------------------------------+--------------------+-------------+
| 52 | 104        | Brake Fluid Top Up upto (100 ml) | Performance        | → Essential |
| 53 | 104        | Wiper Fluid Top Up               | Performance        | → Essential |
| 65 | 103        | Wiper Fluid Top Up               | Performance        | → Essential |
| 67 | 103        | Battery Water Top Up             | Performance        | → Essential |
| 80 | 102        | Wiper Fluid Top Up               | Performance        | → Essential |
| 82 | 102        | Battery Water Top Up             | Performance        | → Essential |
+----+------------+----------------------------------+--------------------+-------------+

SP-PEND-2 — Move 2 inspections from Additional → Essential
+-----+------------+----------------------------------+-------------------+-------------+
| id  | service_id | label                            | from (Additional) | to          |
+-----+------------+----------------------------------+-------------------+-------------+
| 377 | 163        | Exterior Inspection              | Additional        | → Essential |
| 391 | 165        | Exterior and Interior Inspection | Additional        | → Essential |
+-----+------------+----------------------------------+-------------------+-------------+

SP-PEND-3 — Seed interval_info on 5 services (NULL-only)
+---------------------+-----------------------+--------------------------------------+--------------+
| slug                | current interval_info | proposed                             | will-update? |
+---------------------+-----------------------+--------------------------------------+--------------+
| front-brake-pad     | NULL                  | After every 40,000 kms (Recommended) | WOULD-UPDATE |
| rear-brake-shoes    | NULL                  | After every 40,000 kms (Recommended) | WOULD-UPDATE |
| tyre-rotation       | NULL                  | After every 5,000 kms (Recommended)  | WOULD-UPDATE |
| wheel-balancing     | NULL                  | After every 10,000 kms (Recommended) | WOULD-UPDATE |
| complete-wheel-care | NULL                  | After every 10,000 kms (Recommended) | WOULD-UPDATE |
+---------------------+-----------------------+--------------------------------------+--------------+

=== POST-RUN AUDIT TRAIL ===
service_inclusions group_name distribution:
+-------------+-------+
| group_name  | count |
+-------------+-------+
| Additional  | 58    |
| Essential   | 462   |
| Performance | 23    |
+-------------+-------+

B7 dry-run complete. Updated 0 inclusion groups + 0 interval_info rows.
```

---

## Real-run output (verbatim)

```
=== B7 REAL RUN ===
[same 3 tables — all 13 rows in the planned state]

=== POST-RUN AUDIT TRAIL ===
service_inclusions group_name distribution:
+-------------+-------+
| group_name  | count |
+-------------+-------+
| Additional  | 56    |  ← was 58
| Essential   | 470   |  ← was 462
| Performance | 17    |  ← was 23
+-------------+-------+
(NULL: 0 — unchanged)

5 target services — interval_info:
+---------------------+--------------------------------------+
| slug                | interval_info                        |
+---------------------+--------------------------------------+
| front-brake-pad     | After every 40,000 kms (Recommended) |
| rear-brake-shoes    | After every 40,000 kms (Recommended) |
| tyre-rotation       | After every 5,000 kms (Recommended)  |
| wheel-balancing     | After every 10,000 kms (Recommended) |
| complete-wheel-care | After every 10,000 kms (Recommended) |
+---------------------+--------------------------------------+

B7 closed. Updated 8 inclusion groups + 5 interval_info rows.
```

Note: 8, not 10 — SP-PEND-1's 6 rows + SP-PEND-2's 2 rows = 8 group moves. SP-PEND-3 contributed 5 interval seeds. **Total 13 corrections** (operator pre-locked "10" was a count derived from 3+2+5 labels; the actual row count is 6+2+5 because the labels are catalog inclusions duplicated across services).

---

## Pre / Post comparison

| Metric | Pre | Post | Δ |
|---|---|---|---|
| `service_inclusions` rows with `group_name='Essential'` | 462 | **470** | +8 |
| `service_inclusions` rows with `group_name='Performance'` | 23 | **17** | −6 |
| `service_inclusions` rows with `group_name='Additional'` | 58 | **56** | −2 |
| `service_inclusions` rows with `group_name IS NULL` | 0 | **0** | 0 |
| `service_inclusions` total | 543 | **543** | 0 (preserved) |
| `services` rows with `interval_info` populated | 5 | **10** | +5 |
| Total updates | — | **13** | — |

Counts add up cleanly: 470 + 17 + 56 = 543 ✅ (no rows lost or created).

---

## Tests

`backend/tests/Feature/Commands/ApplyHandCorrectionsB7Test.php` (new, **6 cases / 32 assertions**):

```
✓ it dry-run reports counts but performs zero writes                                                          15.44s
✓ it moves all matching fluid top-ups from Performance to Essential                                            0.20s
✓ it moves only Exterior Inspection + Exterior and Interior Inspection from Additional to Essential            0.14s
✓ it seeds interval_info on all 5 target services with the locked values                                       0.14s
✓ it NEVER overwrites a service that already has a non-NULL interval_info                                      0.15s
✓ it is fully idempotent: second run with nothing to change exits 0 with WARN lines                            0.16s

Tests:    6 passed (32 assertions)
Duration: 26.87s
```

**Negative controls** explicitly covered:
- SP-PEND-1 test: seeds a Performance `Spark Plug Cleaning` row that must NOT move (other Performance items stay).
- SP-PEND-2 test: seeds an Additional `Pre-Service Visual Exterior Inspection (extended)` row (substring of "exterior inspection") that must NOT move — the SQL uses `LOWER(TRIM(label)) IN (...)` exact match, not LIKE.
- SP-PEND-3 test: seeds `tyre-rotation` with a custom operator-edited `interval_info` and asserts the command leaves it untouched.

**Full backend Pest suite:**
```
Tests:    337 passed (1487 assertions)
(was 331 → +6 new; zero regressions)
```

---

## Files

**New (2):**
- `backend/app/Console/Commands/ApplyHandCorrectionsB7.php` (~140 lines)
- `backend/tests/Feature/Commands/ApplyHandCorrectionsB7Test.php` (~115 lines, 6 cases)

**Modified:** none.

**No migrations · no schema change · no API surface change · no frontend touch · no new dependencies. TSC + Vite untouched.**

---

## Verification status

| ID | Status |
|---|---|
| **SP-PEND-1** — fluid top-ups → Essential | ✅ Done (6 rows moved; uniformly applied across catalog) |
| **SP-PEND-2** — inspections → Essential | ✅ Done (2 rows moved) |
| **SP-PEND-3** — 5 services' interval_info | ✅ Done (5 services populated; 5 → 10 total with interval_info) |
| **B7 blocker overall** | ✅ Closed |

---

## Suggested commit

```
feat(data): B7 — apply 10 hand-corrections via idempotent artisan command

corrections:apply-b7 — new artisan command applying the three
operator-locked SP-PEND-{1,2,3} corrections in one transactional pass:

  SP-PEND-1: 6 inclusion rows moved Performance → Essential
             (3 fluid-top-up labels × multiple services each)
  SP-PEND-2: 2 inclusion rows moved Additional → Essential
             (Exterior Inspection + Exterior and Interior Inspection)
  SP-PEND-3: 5 services' interval_info seeded (NULL-only — never
             overwrites operator-edited values)

  Total: 13 corrections (operator pre-locked "10" was label-count;
  actual row-count is 6+2+5 because labels are catalog inclusions
  duplicated across services. Surfaced at dry-run checkpoint;
  operator approved intent-first apply).

- --dry-run flag prints planned changes + counts; performs zero writes.
- Zero-match sub-steps print WARN and continue (idempotent re-runs).
- Post-run audit trail: group_name distribution + interval_info verify.

Pre  → Post:
  service_inclusions Essential   462 → 470
  service_inclusions Performance  23 →  17
  service_inclusions Additional   58 →  56
  service_inclusions NULL          0 →   0
  services with interval_info      5 →  10

6 Pest tests including negative controls + idempotency.
Full suite: 337 passed (was 331; +6 new), zero regressions.
```

---

## Blocker progress

`PROJECT_FULL_AUDIT.md` PART E:
- ✅ B1 (GitHub remote)
- ✅ B2 (working-tree commit)
- ✅ B4 (robots.txt)
- ✅ B6 (carts:prune)
- ✅ B5 partial (LOCATIONS migrated; TESTIMONIALS + BUSINESS_INFO deferred per D-B5-6)
- ✅ **B7** (this task) — 6 of 10 cleared
- ❌ B3 (content authoring — service descriptions + images)
- ❌ B8 (Hostinger deploy)
- ❌ B9 (typography PEND-2/PEND-4 verification)
- ❌ B10 (admin password rotation post-deploy)
