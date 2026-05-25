# SERVICE_IMPORT_DIAGNOSTIC — old acr2025 → current acr_v3 (read-only)

**Diagnostic only. ZERO changes.** The two old dumps were parsed as text; acr_v3 was
read with `SELECT`s only (no writes, no migrations, no execution of the dumps). The
parser was a throwaway script, run once and deleted — nothing was added to the repo
except this report.

**Bottom line:** the match is **excellent** — **90 of 91** old packages map to a
current service (73 exact + 17 near), so **~90 of 92** current services can be
populated. Inclusions are the cleanest, highest-value payload (549 rows, all attach).
**Do NOT import** old `image`, `price`, or `note`. The old `warrenty_info`/
`recommended_info` columns are **inconsistent** (some rows hold symptom text, not
warranty/recommendation) and need a review/pattern filter before import.

Files parsed: `backend/storage/app/imports/{sceduled_packages,package_specification}.sql`
(confirmed CREATE TABLE + INSERT only — no DROP/DELETE/TRUNCATE; the lone "Drop" hit was
the label "Voltage Drop Test").

---

## 1. Old data parsed (counts confirmed)

| Dump | Rows | Notes |
|---|---|---|
| `sceduled_packages` | **91** | 91 **distinct** slugs (1:1, no duplicate slugs); all `status = 1` (active). 26 columns incl. `brand_id/model_id/fuel_type_id` (a sample vehicle per row — irrelevant to slug matching). |
| `package_specification` | **549** | grouped by `sp_id` → **91 distinct sp_id** (every package has inclusions; avg ~6 each). |

Old `time_takes_option`: **Hour ×74, Day ×17**.

---

## 2. Slug match results (exact / near / unmatched)

Automated pass (exact slug, then normalized-equal, then fuzzy `similar_text`):
**EXACT 73 · NEAR 18 · 0 with no candidate.** But the naive fuzzy scorer **mis-ranked
~5 candidates** (it rewards shared suffixes like `-replacement`), so the near list is
corrected by hand below. After correction: **73 exact + 17 near-with-real-target = 90
mapped; 1 old package has no current target.**

### EXACT MATCHES — 73 (auto-importable, 1:1)
All 73 old slugs equal a current `services.slug` exactly. Representative span (full set
verified): `battery-charging→#142`, `battery-replacement→#143`, `battery-jump-start→#191`,
`full-ac-service→#106`, `periodic-ac-service→#105`, `comprehensive/standard/primary-service
→#104/#103/#102`, all `*-paint` door/fender/body slugs (#121–#132), all detailing/coating
(#133–#141), `alternator-*`, `radiator-*`, bumper/engine/starter repairs (#144–#162),
inspection set (#163–#168), lights/glass (#169–#175), clutch (#176–#180), suspension
(#181–#188), `windshield-replacement→#189`. (Category cross-check: every exact match lands
in a sensible current category.)

### NEAR / AMBIGUOUS — 18 raw candidates → **corrected mapping** (operator confirms)
The current DB has **typo/rename variants** of many old slugs. The tool's raw guess is
shown; ✓ = tool correct, ✗ = tool wrong (use the corrected target).

| Old slug | **Correct current target** | Tool's raw guess (score) | Verdict |
|---|---|---|---|
| flat-bed-towing | flat-bed-towing-upto-10km **#193** | #193 (75) | ✓ rename (+`-upto-10km`) |
| wheel-lift-towing-10-kms | wheel-lift-towing-upto-10km **#192** | #192 (86) | ✓ rename |
| rear-brake-shoes-replacement | rear-brake-shoes **#110** | #110 (73) | ✓ dropped `-replacement` |
| boot-paint | boot-point **#120** | #120 (90) | ✓ typo (point↔paint) |
| left-quarter-panel-paint | left-quarter-pannel-paint **#127** | #127 (98) | ✓ typo (pannel) |
| right-quarter-panel-paint | right-quarter-pannel-paint **#128** | #128 (98) | ✓ typo |
| rat-pest | ratpest **#146** | #146 (93) | ✓ spacing |
| front-windshield-replacement | front-windshiled-replacement **#172** | #172 (96) | ✓ typo (windshiled) |
| rear-windshield-replacement | rearwindshiled-replacement **#173** | #173 (94) | ✓ typo |
| clutch-overhaul | clutch-overall **#179** | #179 (90) | ✓ typo (overall↔overhaul) |
| accidental-claim | accidential-claim **#190** | #190 (97) | ✓ typo (accidential) |
| pre-owned-car-inspection | second-hand-car-inspection **#165** | #165 (76) | ✓ synonym — **confirm** |
| front-brake-disc-replacement | front-brake-disc **#108** | front-bumper-replacement #157 (77) | ✗ **tool wrong → #108** |
| front-brake-pad-replacement | front-brake-pad **#109** | front-bumper-replacement #157 (78) | ✗ **tool wrong → #109** |
| front-bumper-paint | front-bumper **#117** | front-bumper-replacement #157 (81) | ✗ **tool wrong → #117** |
| rear-bumper-paint | rear-bumper **#118** | rear-bumper-replcament #158 (82) | ✗ **tool wrong → #118** |
| bonnet-paint | bonnet **#119** | boot-point #120 (73) | ✗ **tool wrong → #119** |
| rear-shock-absorber-replacement | **(no current target)** | front-shock-absorber-replacement #181 (89) | ✗ **UNMATCHED** (see §3) |

### UNMATCHED old packages — 1
- **`rear-shock-absorber-replacement`** — current DB has `front-shock-absorber-replacement`
  (#181, exact) and `suspension-overhaul` (#188) but **no rear-shock service**. The fuzzy
  tool mis-pointed it at the front variant (already taken). → **skip**, or operator creates
  a `rear-shock-absorber-replacement` service first, then re-run.

---

## 3. Current services that stay EMPTY (no old match) — **2**

After the corrected mapping, only **2 of 92** services have no old source and will stay
empty (operator fills via admin):
- **`ac-gas-refill` #107** (car-ac-service-repair)
- **`dry-ice-engine-clean` #137** (car-care-detailing)

> The other 17 services that the automated pass listed as "no old match" were a
> false positive — they are exactly the **near-match targets** above (typo/renamed
> current slugs). They WILL be populated once the corrected near mapping is used.

---

## 4. Category mapping (old `sc_id` → current category)

Inferred from the categories of matched services (matching is by service slug; category
is a sanity cross-check only). High confidence throughout:

| old sc_id | #pkgs | → current category | confidence |
|---|---|---|---|
| 1 | 2 | car-battery | 100% |
| 2 | 3 | car-emergency-services | 100% |
| 3 | 2 | car-insurance-claim | 100% |
| 5 | 25 | car-repairs-inspection (19) **+ car-inspection (6)** | 76% — old "sc_id 5" was split into two current categories (repairs vs inspection) |
| 6 | 9 | car-suspension-work | 100% |
| 7 | 5 | car-clutch-work | 100% |
| 8 | 7 | car-lights-and-glass-work | 100% |
| 9 | 8 | car-care-detailing | 100% |
| 10 | 16 | car-denting-painting (14) + repairs (2)* | 88% (*the 2 are the mis-scored bumper-paint rows → really denting-painting) |
| 11 | 9 | car-brake-wheel-maintenance (7) + repairs (2)* | 78% (*the 2 are the mis-scored brake rows → really brake-wheel) |
| 12 | 2 | car-ac-service-repair | 100% |
| 13 | 3 | regular-car-service | 100% |

The <100% rows are artifacts of the fuzzy mis-matches in §2 — once corrected, sc_id 5
spans repairs+inspection (a real 1→2 split), and 10/11 are 100% within their category.
**Category is not needed for the import** (slug carries it); this table is just validation.

---

## 5. Images verdict — **unusable, do NOT import**

- 91 distinct old image filenames (e.g. `BatteryCharging-1001706529847.jpg`,
  `FlatBedTowing-1001706527479.jpg`).
- **0 of 91** exist anywhere under `storage/app/public/**`.
- Importing these strings would write dead references; `ImageUrl::resolve` would then
  hand the frontend `/storage/…/BatteryCharging-100….jpg` → **404s**.
- **Verdict:** leave `services.image` (and inclusion `image`) NULL. Operators upload real
  images via the **Phase 1 admin FileUpload** (`entity-images/services|categories|
  service-inclusions`). Do **not** fabricate paths.

---

## 6. Field fill plan (old field → current column)

| Old field | → Current column | Import? | Notes |
|---|---|---|---|
| `package_specification.specification` | **`service_inclusions.label`** | ✅ **YES** | Cleanest payload. `position` = row order within sp_id; `image` = NULL. 549 rows, all attach. |
| `time_takes` | **`services.time_takes`** | ✅ YES | ~70 matched have it. |
| `time_takes_option` | **`services.time_unit`** | ✅ YES (map) | `Hour`→`hours`, `Day`→`days` (current Select uses `hours`/`minutes`; add/allow `days`). |
| `warrenty_info` | `services.warrenty_info` | ⚠️ **REVIEW** | **Inconsistent** — see §6.1. Import only rows matching a warranty pattern, or after operator review. |
| `recommended_info` | `services.recommended_info` | ⚠️ REVIEW | Same inconsistency; **also overlaps `interval_info`** — see §7. |
| `note` | `services.note` | ❌ skip | Only **2** of 91 populated (one is literally "Recommended"). No value. |
| `price` | — | ❌ **skip** | See §8 (garbage values; pricing lives in `service_prices`). |
| `image` | — | ❌ skip | See §5 (files don't exist). |

### 6.1 Data-quality flag — warranty/recommended columns are mislabeled in places
The old columns are **not** clean. Two patterns coexist:
- **Real content** (good): `warrenty_info = "Warranty 1000 kms or 1 month"`,
  `recommended_info = "After every 10,000 kms or 1 year (Recommended)"` (AC/service rows).
- **Symptom text in the wrong column** (bad): the battery rows have
  `warrenty_info = "Car Does Not Starts"`, `recommended_info = "Electrical System Does Not
  Work"` — these are symptoms, not warranty/recommendation.

→ **Do not blind-import** these two columns. Either (a) operator reviews the 90 values,
or (b) import only rows where `warrenty_info` matches `/warranty|km|month/i` and
`recommended_info` matches `/after every|recommended|km|month/i`; leave the rest NULL for
manual entry.

---

## 7. `interval_info` vs `recommended_info` overlap

The new Phase 1 `interval_info` ("Every 5000 km or 3 months") is **exactly** what many old
`recommended_info` values contain: `"After every 5,000 kms or 3 Months (Recommended)"`,
`"After every 20,000 kms or 12 Months (Recommended)"`. Recommendation:
- Where `recommended_info` matches `/every\s+[\d,]+\s*kms?/i`, **seed `interval_info`** from
  it (optionally normalized to "Every 5000 km or 3 months").
- Keep `recommended_info` too (or move the interval out and leave a cleaner recommendation).
- Decide per-row at import; flag overlaps for operator. This is the one place the two
  columns should be reconciled rather than both carrying the same km/month string.

---

## 8. Price recommendation — **do NOT import old price**

Old `price` is unreliable: `min 0, max 560000`; samples `1500, 450000, 800, 0, 560000, 66,
0`. e.g. **battery-replacement = ₹450000**, **battery-jump-start = ₹560000**, **full-ac-
service = ₹66**, many `0`. Meanwhile acr_v3 pricing is authoritative in **`service_prices`
(52,521 rows, all 92 services priced per brand/model/fuel)**. Importing old `price` into
`services.base_price` would inject garbage and risk confusing the real pricing path.
→ **Skip `price` entirely.** (Constraint also: don't touch pricing logic.)

---

## 9. Coverage estimate (after a corrected import of the 90 matched services)

| Current column | Services populated (of 92) | Source |
|---|---|---|
| **inclusions** (`service_inclusions`) | **~90** | all matched packages have specs (549 rows total) |
| `time_takes` + `time_unit` | **~70** | old `time_takes` present |
| `recommended_info` | **~71** (subject to §6.1 review) | old `recommended_info` |
| `interval_info` | subset of the ~71 (rows with "every N km") | derived from `recommended_info` |
| `warrenty_info` | **~85 raw / fewer after review** | old `warrenty_info` (§6.1) |
| `note` | ~2 | skip recommended |
| `image` | 0 from import | operator uploads (admin) |
| `base_price` | 0 from import | `service_prices` is source of truth |

Net: from **0/92 populated today** → **~90/92 with inclusions + most with time and a
reviewed warranty/recommendation**, leaving only `ac-gas-refill` and `dry-ice-engine-clean`
plus all images for manual entry. Big win.

---

## 10. Recommended import strategy (for the next step — not executed here)

**Match key = `services.slug`.** Build an explicit map: 73 exact (auto) + the 17 corrected
near rows from §2 (the 5 ✗ rows + the synonym row get a quick operator OK; the typo/rename
rows are safe). Skip `rear-shock-absorber-replacement` (no target).

1. **Inclusions first** (highest value, cleanest): for each matched package, insert its
   `package_specification` rows into `service_inclusions` as `{service_id, label =
   specification, position = order, image = null}`. **Idempotency:** only insert when the
   service currently has **zero** inclusions (so re-runs don't duplicate).
2. **`time_takes` + `time_unit`**: copy `time_takes`; map `Hour→hours`, `Day→days`. Only
   when the current column is **NULL** (never overwrite operator edits).
3. **`warrenty_info` / `recommended_info`**: import **only** pattern-valid values (§6.1),
   only into NULL columns, flag the rest for manual review. Don't import the symptom-text rows.
4. **`interval_info`**: seed from `recommended_info` where it matches the "every N km/month"
   pattern (§7).
5. **Skip** `price`, `image`, `note`.
6. **Safety rails for the script:** additive `INSERT`/NULL-only `UPDATE`s; wrap in a
   transaction; `--dry-run` mode that prints the diff first; match strictly by slug; never
   touch `service_prices`; re-runnable (NULL-guard + inclusion-empty guard). Operator
   confirms the 6 flagged near rows before the run.

**Suggested phasing:** (a) auto-import inclusions + time for the 73 exact; (b) operator
confirms the 17 near rows, then import those; (c) operator reviews warranty/recommended
(or run the pattern filter); (d) operator uploads images via admin. All read-back testable
against `GET /api/v1/services/{cat}/{svc}` (Phase 1 already serializes inclusions +
interval_info + full image URLs).

---

### Constraints honoured
Read-only throughout · dumps parsed as text + acr_v3 read via `SELECT` only · no INSERT/
UPDATE/DELETE, no migrations, no dump execution · no packages installed · throwaway parser
deleted (no committed code change) · **report only**. The actual import script is the next
step, built on this map.
