# B4 + B6 — robots.txt hardening + carts:prune command

**Date:** 2026-05-29 · **Scope:** backend-only (per blocker spec) · **Tests:** 326 Pest passing (was 317; +9 new), zero regressions · **Live dry-run:** 1083/1469 guest carts eligible for prune.

---

## PART A — `backend/public/robots.txt` (D-B4-1)

### Before
```
User-agent: *
Disallow:
```

Default Laravel content. **Allowed everything including `/admin` (Filament panel) and `/api/v1/*` (JSON endpoints).** No `Sitemap:` directive.

### After
```
User-agent: *
Disallow: /admin
Disallow: /api/v1
Allow: /

Sitemap: https://acr-mechanics.in/sitemap.xml
```

5 directives. Customer-facing pages remain crawlable; `/admin` and `/api/v1` are now blocked; sitemap is advertised.

### Sitemap-URL assumption flag
- Local `.env`: `APP_URL=http://localhost:8000` (dev) — no production URL recorded.
- Used `https://acr-mechanics.in/sitemap.xml` per the task brief (locked decision D-B4-1).
- **Operator confirmation needed before deploy** if the production host differs. If it does, just edit the `Sitemap:` line — no other file depends on this string.
- The sitemap route itself is wired at the backend root (`routes/web.php` line 32 → `SitemapController@index`), so the path part `/sitemap.xml` is correct regardless of host.

---

## PART B — `carts:prune` command (D-B6-1)

### Files

| Path | Status | LoC |
|---|---|---|
| `backend/app/Console/Commands/PruneGuestCartsCommand.php` | New | 78 |
| `backend/app/Console/Kernel.php` | Modified — added `dailyAt('03:00')` schedule (7 lines + comment) | +9 / -0 |
| `backend/tests/Feature/Commands/CartsPruneTest.php` | New (directory `Commands/` created) | 142, 9 cases / 25 assertions |

### Command surface

```
$ php artisan carts:prune --help
Description:
  Delete stale guest carts (user_id NULL) older than N days with no recent items (default 14).

Usage:
  carts:prune [options]

Options:
      --days[=DAYS]  [default: 14]
      --dry-run      Report counts only, no deletes
```

### Algorithm (faithful to D-B6-1)

```php
$cutoff = now()->subDays($days);

$eligible = Cart::query()
    ->whereNull('user_id')                                              // guests only
    ->where('updated_at', '<', $cutoff)                                  // old cart
    ->whereDoesntHave('items', fn ($q) =>
        $q->where('updated_at', '>=', $cutoff)                           // AND no fresh items
    );
```

- `whereDoesntHave('items', fresh-only)` elegantly covers **both** "cart has 0 items" AND "all items older than cutoff" in one clause.
- Delete wrapped in `DB::transaction()`; `cart_items` rows cascade via the existing `cart_id` FK (`cascadeOnDelete` set in `2026_05_03_120004_create_cart_items_table.php` line 30).
- **User carts (`user_id IS NOT NULL`) are never touched** — explicit `whereNull('user_id')` filter. Verified by test "preserves user carts of any age".

### Schedule (registered in `Kernel.php`)

```php
$schedule->command('carts:prune')
    ->dailyAt('03:00')
    ->withoutOverlapping(60);
```

Hostinger cron stays the same (already documented in `Kernel.php`): one-line `* * * * * php artisan schedule:run` dispatches it once a day.

### Tests — `tests/Feature/Commands/CartsPruneTest.php`

```
✓ it dry-run reports eligible count but performs zero deletes                            2.11s
✓ it deletes guest carts older than the default 14-day threshold                         0.19s
✓ it preserves user carts of any age (user_id NOT NULL is sacred)                        0.27s
✓ it preserves guest carts updated within the threshold                                  0.15s
✓ it preserves an old empty cart whose owner came back yesterday (updated_at fresh)      0.25s
✓ it preserves an old guest cart whose items were touched recently (fresh item shield)   0.19s
✓ it deletes an old guest cart whose items are also old (full cascade)                   0.23s
✓ it respects --days option (3-day threshold deletes a 5-day-old cart that 14d kept)     0.14s
✓ it is idempotent: second run with nothing to prune is a clean no-op                    0.15s

Tests:    9 passed (25 assertions)
Duration: 4.23s
```

All 4 task-required cases covered (dry-run-no-deletes / deletes-only-old-guests / preserves-user-carts / preserves-recent-guests / --days respected) **plus 4 safety belts** (empty-but-recently-touched cart preserved; fresh-item shield; full cascade verification; idempotency).

---

## PART C — Verification

### Full Pest suite

```
Tests:    326 passed (1352 assertions)
Duration: 84.11s
```

- Was 317 passing pre-change.
- **+9 new tests = 326 total.** Zero regressions.

### Live dev-DB dry-run (`acr_v3`)

```
$ php artisan carts:prune --dry-run
Carts: 1469 guest · 18 user (total 1487)
Cutoff: 2026-05-15 09:11:41 (N=14 days)
Eligible for prune: 1083 guest carts
--dry-run set: no deletes performed.
```

**1,083 of 1,469 guest carts (73.7%) would be pruned at default 14-day threshold.** That tracks with the audit observation (1,487 carts vs 159 cart_items → most are empty / abandoned). Real run is the operator's call — recommend running `php artisan carts:prune` once on dev to validate the cascade behaviour against real data before the schedule kicks in on production.

### Frontend gates

**Not run** — backend-only change, no `.tsx`/`.ts`/`.css` modified. TSC + Vite untouched per task scope. Playwright suite unaffected.

---

## Files summary

**Modified (1):**
- `backend/app/Console/Kernel.php` (+9 / -0 — added `carts:prune` schedule alongside `orders:auto-confirm`)
- `backend/public/robots.txt` (rewritten — was 2 lines default, now 5 directives)

**New (2):**
- `backend/app/Console/Commands/PruneGuestCartsCommand.php` (78 lines)
- `backend/tests/Feature/Commands/CartsPruneTest.php` (142 lines, 9 cases)

**Directories created (1):**
- `backend/tests/Feature/Commands/` (new — first command-level test dir)

**No migrations · no API surface change · no frontend touch · no new dependencies.**

---

## Suggested commit

Per task git policy, no commits run. Operator can use:

```
feat(ops): robots.txt hardening + carts:prune scheduled command

B4 — robots.txt: disallow /admin + /api/v1, allow /, advertise sitemap
at https://acr-mechanics.in/sitemap.xml (update before deploy if the
prod host differs).

B6 — new carts:prune {--days=14} {--dry-run} artisan command:
- Deletes only guest carts (user_id IS NULL) untouched for N days
  whose items are also stale (cart_items cascade via existing FK).
- User carts are never touched (explicit whereNull filter).
- Transactional, idempotent, --dry-run flag.
- Scheduled dailyAt('03:00') via withoutOverlapping(60).
- 9 Pest tests covering dry-run / threshold / user-cart preservation /
  fresh-cart preservation / fresh-item shield / cascade / --days /
  idempotency.

Live dev-DB dry-run: 1083/1469 guest carts eligible at N=14d.
Full Pest suite: 326 passed (was 317; +9 new), zero regressions.
```

---

## Blockers closed

- **B4** ✅ robots.txt customized.
- **B6** ✅ carts:prune command + schedule + tests.

Remaining pre-launch blockers per `PROJECT_FULL_AUDIT.md` PART E: B3 (content authoring), B5 (locations/business-info/testimonials → backend), B7 (inclusion hand-corrections), B8 (Hostinger deploy), B9 (typography PEND-2/PEND-4), B10 (admin password rotation post-deploy).
