# Phase 2.6c — Smoke Test Harness (Pest + Playwright)

**Date:** 2026-05-06
**Scope:** stand up a minimum-viable automated test harness so future
phases can run a "did anything obvious break" check in <30 s.
**Status:** ✅ All tests passing. 13/13 backend (Pest), 3/3 frontend (Playwright).

---

## What was added

### Backend — Pest 2.x bootstrap
| File | Purpose |
|---|---|
| `backend/tests/Pest.php` | Wires `RefreshDatabase` + `TestCase` into the `Feature/` suite. Manually authored because `pest:install` is a 3.x-only Artisan command and this project pins Pest 2.36 (PHP 8.2.12 / XAMPP). |
| `backend/phpunit.xml` | Uncommented `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:`; added `APP_DEBUG=true`, `OTP_DRIVER=dev`, `OTP_DEV_BYPASS=true`, `IMPORT_API_TOKEN=test-import-token`. |

### Backend — factories (6 new)
| File | Notes |
|---|---|
| `database/factories/ServiceCategoryFactory.php` | unique slug + name |
| `database/factories/ServiceFactory.php` | depends on `ServiceCategory::factory()`, sets `base_price` so cart pricing falls back when no `service_prices` row exists |
| `database/factories/CarBrandFactory.php` | unique slug |
| `database/factories/CarModelFactory.php` | depends on `CarBrand::factory()` |
| `database/factories/FuelTypeFactory.php` | unique slug from a small enum-ish list |
| `database/factories/ServicePriceFactory.php` | full 4-tuple (`service`, `brand`, `model`, `fuel_type`) for the pricing endpoint |

`UserFactory` already existed — tests just override `phone` inline.

### Backend — Pest smoke tests (6 files, 11 tests)
All under `backend/tests/Feature/Smoke/`:

| File | Tests | What it locks down |
|---|---|---|
| `AuthOtpTest.php` | 3 | `/auth/send-otp` 200 for known phone, 404 for unknown, `/auth/verify-otp` returns Sanctum token via dev-bypass |
| `CartTest.php` | 2 | Guest cart add (`X-Cart-Session` UUID), server-computed totals, 400 when no identifier |
| `CouponTest.php` | 2 | Seeded `FIRST10` applies a 10 % discount; same coupon rejected (422) below `min_order_value` |
| `CheckoutTest.php` | 1 | `/checkout/quote` returns `{subtotal, discount, tax, total}` for an authenticated user |
| `OrdersTest.php` | 2 | `/user/orders` returns paginated empty list for fresh user; 401 unauthenticated |
| `PricingTest.php` | 1 | `/pricing` returns the configured per-vehicle price for a service tuple |

### Frontend — Playwright config + tests
| File | Tests | What it locks down |
|---|---|---|
| `playwright.config.ts` | — | `baseURL=http://localhost:3000`, chromium-only, `workers:1`, 30 s test timeout, `screenshot: 'only-on-failure'` |
| `tests/e2e/smoke.spec.ts` | 3 | (a) home renders + zero React console errors, (b) Login button opens auth modal ("Welcome back" + phone field), (c) `/payment` lands on NotFound (regression guard for the Phase 2.6a payment-page deletion) |

### Tooling
`package.json` scripts:
```json
"test:backend": "cd backend && vendor/bin/pest"
"test:e2e":     "playwright test"
"test:e2e:ui":  "playwright test --ui"
"test":         "npm run test:backend && npm run test:e2e"
```

---

## Real execution results

### Backend (`vendor/bin/pest`)
```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\Smoke\AuthOtpTest        (3 tests)
PASS  Tests\Feature\Smoke\CartTest           (2 tests)
PASS  Tests\Feature\Smoke\CheckoutTest       (1 test)
PASS  Tests\Feature\Smoke\CouponTest         (2 tests)
PASS  Tests\Feature\Smoke\OrdersTest         (2 tests)
PASS  Tests\Feature\Smoke\PricingTest        (1 test)

Tests:    13 passed (59 assertions)
Duration: 3.88 s   (smoke-only: 1.33 s)
```

### Frontend (`npx playwright test`)
```
Running 3 tests using 1 worker

✓ home page renders without console errors        (10.6 s)
✓ clicking the Login button opens the auth modal  ( 1.7 s)
✓ /payment routes to NotFound                     ( 1.1 s)

3 passed (21.3 s)
```

Total wall-clock: **3.88 s + 21.3 s ≈ 25 s** for the full suite (excluding
the Vite + Laravel boot cost).

---

## File counts

| Category | Count |
|---|---|
| Pest test files | 6 (under `Feature/Smoke/`) |
| Pest test cases | 11 |
| Pest assertions | 57 (smoke) / 59 (full suite) |
| Factories created | 6 |
| Playwright configs | 1 (`playwright.config.ts`) |
| Playwright spec files | 1 (3 tests) |
| `package.json` scripts added | 4 |
| Bootstrap files | 1 (`tests/Pest.php`) |
| `phpunit.xml` env keys added | 6 (sqlite, debug, otp_driver, otp_dev_bypass, import token) |

---

## KNOWN_BUGS

**None.** All 13 backend tests and all 3 frontend tests pass on first
correct run. No regressions discovered.

Two implementation notes worth recording (NOT bugs, just gotchas for
future phases adding more tests):

1. **SQLite returns ints for whole-number JSON floats.** Two cart/coupon
   tests initially failed because `assertJsonPath('cart.totals.subtotal', 2400.0)`
   does a strict (`===`) comparison and SQLite serialised `2400.00` as
   the integer `2400`. Fix: cast both sides via `expect((float) $resp->json(...))->toBe(2400.0)`.
   Pattern documented in `CartTest.php:22-26` and `CouponTest.php:30-37`.

2. **`pest:install` does not exist in Pest 2.x.** Phase 2.6a's
   `composer require` landed Pest 2.36 because PHP 8.2.12 (XAMPP) blocks
   3.x. The `tests/Pest.php` bootstrap was therefore authored manually
   (4 lines: `uses(TestCase::class, RefreshDatabase::class)->in('Feature')`).
   When this project upgrades to PHP 8.3+ and Pest 3+, the artisan
   command can replace this file but the wiring is identical.

---

## How to run

```bash
# Backend only (no servers needed — uses :memory: SQLite)
npm run test:backend

# Frontend only (requires Vite dev server on :3000 and Laravel on :8000)
npm run dev          # in one shell
php artisan serve    # in another, from backend/
npm run test:e2e

# Everything
npm test
```

---

## Out of scope (deferred to a later phase)

- Place-order tests (the fake-booking guard, payment row creation, and
  cart-conversion side-effects would each merit dedicated tests).
- Cart merge tests (`POST /cart/merge` + the `X-Cart-Session` header on
  `/auth/verify-otp`).
- CMS page rendering (`/api/v1/pages/{slug}`).
- Address CRUD.
- Mobile viewports / accessibility audits in Playwright.
- CI hookup. The `npm test` script is local-only; a GitHub Actions
  workflow that runs both suites on push is the natural next step.
