# Phase 2.1.1 — fix-up: relax users.email + users.password (report)

Closes /PHASE2_2_REPORT.md Deviation #2. Phase 2.1.1 is a contract-
alignment patch, not a feature: the OTP-only auth flow (Decision
D-C / Assumption 15) requires `email` and `password` to be optional
on `users`. The Laravel skeleton inherited NOT NULL on both, which
hid until phone-only lead-capture surfaced it during 2.2 testing.

## Files modified

| File | Change |
|---|---|
| `backend/database/migrations/2026_05_03_120002_make_users_email_password_nullable.php` (new) | `Schema::table('users', …)->change()` flipping email + password to nullable. Down() backfills NULLs to `noemail-{id}@local` / `SHA2(RAND(),256)` before re-tightening so rollback is safe. |
| `backend/app/Http/Controllers/Api/V1/Auth/LeadCaptureController.php` | Removed `Str::random(60)` placeholder for `password`. firstOrCreate writes `name`, `email` (may be null), `role` only. Dropped `use Illuminate\Support\Str;` import along with it. |
| `backend/composer.json` + `composer.lock` | Added `doctrine/dbal ^3.10` to `require-dev` (needed for `->change()`). Confirmed in `require-dev` block, NOT `require`. |

No frontend code change. `LeadCaptureRequest.email` was already
typed `string \| undefined` in `src/types/api.ts` per contract §9 —
verified.

## Migration output

```
$ php artisan migrate --force
INFO  Running migrations.
  2026_05_03_120002_make_users_email_password_nullable .. 302ms DONE
```

## Schema verification (live MySQL)

```
mysql> SHOW COLUMNS FROM users;
+--------------------+--------------------------+------+-----+
| Field              | Type                     | Null | Key |
+--------------------+--------------------------+------+-----+
| id                 | bigint unsigned          | NO   | PRI |
| name               | varchar(255)             | NO   |     |
| email              | varchar(255)             | YES  | UNI |   ← was NO; UNIQUE preserved
| phone              | varchar(15)              | YES  | UNI |
| is_verified_phone  | tinyint(1)               | NO   |     |
| is_verified_email  | tinyint(1)               | NO   |     |
| email_verified_at  | timestamp                | YES  |     |
| password           | varchar(255)             | YES  |     |   ← was NO
| remember_token     | varchar(100)             | YES  |     |
| last_login_at      | timestamp                | YES  |     |
| role               | enum('customer','admin') | NO   | MUL |
+--------------------+--------------------------+------+-----+
```

## Curl regression (4 cases per brief)

```
$ POST /auth/lead-capture {name:"Phone Only", phone:"7777777777"}   ← NO email
HTTP 200
{"success":true,"pending_user_id":6,"otp_sent_to":"phone","dev_code":"146389"}
       ↑ previously crashed with "Column 'email' cannot be null" 500 ✓

$ POST /auth/verify-otp {channel:"phone", destination:"7777777777", code:"1234"}
HTTP 200
{"success":true,"token":"7|CijFAq…",
 "user":{"id":6,"name":"Phone Only","phone":"7777777777","email":null, …}}
       ↑ user.email = null in response ✓

$ POST /auth/lead-capture {name:"With Email", phone:"7777777778", email:"a@b.com"}
HTTP 200  (regression: email path still works) ✓

$ GET /user/profile  Authorization: Bearer <phone-only token>
HTTP 200
{"user":{"id":6, …, "email":null, "is_verified_phone":true, …}}    ✓
```

## Frontend

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ built in 32.12s — 729KB JS / 105KB CSS
```

## doctrine/dbal placement

```
$ grep -A3 '"require' backend/composer.json
"require": {
    "php": "^8.1",
    "guzzlehttp/guzzle": "^7.2",
    "laravel/framework": "^10.10",
--
"require-dev": {
    "doctrine/dbal": "^3.10",
    "fakerphp/faker": "^1.9.1",
```

Confirmed scoped to `require-dev` only — production install
(`composer install --no-dev`) will not pull it. Used here only by
the migration's `->change()` calls; future `->change()` migrations
can rely on it without re-installing.

## Single commit

`e4965012521fb39c0894ef143a52d7445ec13281` — 5 files staged
(2 modified, 3 new). Commit body notes that `backend/composer.json`
and `backend/composer.lock` were untracked baseline files; this
commit captures their full state for the first time. doctrine/dbal
is the only deliberate package addition in 2.1.1; laravel/sanctum
content in `composer.lock` arrived during Phase 2.1 but was never
previously committed.
