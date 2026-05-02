# Phase 2.1 — Auth + OTP infrastructure (report)

Single-commit landing per the brief. Implements `/PHASE2_CONTRACT.md`
sections §2.1, §2.2, §3 (User + OtpVerification), §4.1, §5.1, §6.5(d)
finishVerification, §7 (driver interface + DevModeOtpDriver +
production guard + DEV_BYPASS), §8 (auth middleware + throttle), §9
(TS types). Cart-merge step from §6.5 is intentionally deferred to
commit 2.4 — bookmarked in `VerifyOtpController` with a comment.

## Files created

### Backend
| File | Lines |
|---|---|
| `backend/database/migrations/2026_05_02_120001_extend_users_for_auth.php` | 48 |
| `backend/database/migrations/2026_05_02_120002_create_otp_verifications_table.php` | 44 |
| `backend/app/Models/OtpVerification.php` | 52 |
| `backend/app/Http/Resources/V1/UserResource.php` | 34 |
| `backend/app/Services/Otp/OtpDriverInterface.php` | 22 |
| `backend/app/Services/Otp/DevModeOtpDriver.php` | 24 |
| `backend/app/Services/Otp/SmtpEmailOtpDriver.php` | 29 |
| `backend/app/Mail/OtpMail.php` | 39 |
| `backend/resources/views/emails/otp.blade.php` | 16 |
| `backend/app/Http/Controllers/Api/V1/Auth/LeadCaptureController.php` | 116 |
| `backend/app/Http/Controllers/Api/V1/Auth/SendOtpController.php` | 76 |
| `backend/app/Http/Controllers/Api/V1/Auth/VerifyOtpController.php` | 138 |
| `backend/app/Http/Controllers/Api/V1/Auth/LoginController.php` | 71 |
| `backend/app/Http/Controllers/Api/V1/Auth/LogoutController.php` | 25 |
| `backend/app/Http/Controllers/Api/V1/User/ProfileController.php` | 61 |

### Frontend
| File | Lines |
|---|---|
| `src/types/api.ts` (NEW) | 96 |

## Files modified

### Backend
| File | Change |
|---|---|
| `backend/app/Models/User.php` | Extended fillable/casts/hidden for OTP auth; `otps()` relation; HasApiTokens preserved |
| `backend/app/Providers/AppServiceProvider.php` | Bound `OtpDriverInterface` via match() in `register()`; production safety guard in `boot()` |
| `backend/app/Providers/RouteServiceProvider.php` | Added 6 named rate limiters (`auth-public`, `auth-verify`, `cart-write`, `user-read`, `user-write`, `public-read`) per contract §8 |
| `backend/app/Http/Kernel.php` | Uncommented `EnsureFrontendRequestsAreStateful` in the `api` middleware group |
| `backend/routes/api.php` | Appended 7 new routes with throttle middleware (auth-public/verify/user-* per the contract); 23 routes total |
| `backend/.env` | Added `OTP_DRIVER=dev`, `OTP_DEV_BYPASS=true` |
| `backend/.env.example` | Added `OTP_DRIVER`, `OTP_DEV_BYPASS=false`, `IMPORT_API_TOKEN` documentation |

### Frontend
| File | Change |
|---|---|
| `src/lib/api.ts` | Added 7 typed fetchers: `postLeadCapture`, `postSendOtp`, `postVerifyOtp`, `postLogin`, `postLogout`, `fetchProfile`, `putProfile` |
| `src/hooks/useAuth.ts` | Rewrote for OTP flow: `signUp`, `logIn`, `verifyOtp`, `updateProfile`, `logOut/logout`, `setDefaults`, `addAddress` (gated 2.2), `addBooking` (gated 2.5). Preserves `AcrUser`, `BookingRecord`, `validateEmail`, `NAME_REGEX`, `PHONE_REGEX` for existing consumers. Removed password-based `signup`/`login` bodies. Removed `checkPasswordStrength` (no passwords per Assumption 15). Removed `findExisting` (legacy stub). |
| `src/components/AuthModal.tsx` | Rewrote to a 3-stage flow: form → otp → done. Login form is phone-only; signup form is name+phone+email?. OTP step has dev-mode hint + `dev_code` echo when present. Resend link. |
| `src/config/features.ts` | Flipped `FEATURES.auth` from `false` → `true`. `cartSync` and `offlineCheckout` remain `false` (re-enabled in 2.3/2.4 and 2.5 respectively). |

`src/components/Header.tsx` was NOT modified — its existing
`{!FEATURES.auth ? null : !isAuthenticated ? (...) : (...)}` branch
auto-uncovers Login/Signup buttons once the flag flips. Confirmed
during static review.

## Migrations (output)

```
$ php artisan migrate --force
INFO  Running migrations.
  2026_05_02_120001_extend_users_for_auth ........... 952ms DONE
  2026_05_02_120002_create_otp_verifications_table .. 689ms DONE
```

After a contract-glitch fix on `otp_code` column size (`8` → `64`,
see deviations below), the second migration was rolled back and
re-applied:

```
$ php artisan migrate:rollback --step=1 ; php artisan migrate --force
INFO  Rolling back migrations.
  2026_05_02_120002_create_otp_verifications_table ... 68ms DONE
INFO  Running migrations.
  2026_05_02_120002_create_otp_verifications_table .. 200ms DONE
```

## Schema verification (live MySQL)

```
mysql> SHOW COLUMNS FROM users;
+--------------------+--------------------------+------+-----+
| Field              | Type                     | Null | Key |
+--------------------+--------------------------+------+-----+
| id                 | bigint unsigned          | NO   | PRI |
| name               | varchar(255)             | NO   |     |
| email              | varchar(255)             | NO   | UNI |
| phone              | varchar(15)              | YES  | UNI |
| is_verified_phone  | tinyint(1)               | NO   |     |
| is_verified_email  | tinyint(1)               | NO   |     |
| email_verified_at  | timestamp                | YES  |     |
| password           | varchar(255)             | NO   |     |
| remember_token     | varchar(100)             | YES  |     |
| last_login_at      | timestamp                | YES  |     |
| role               | enum('customer','admin') | NO   | MUL |
| created_at         | timestamp                | YES  |     |
| updated_at         | timestamp                | YES  |     |
+--------------------+--------------------------+------+-----+

mysql> SHOW COLUMNS FROM otp_verifications;
+-------------+----------------------+------+-----+
| Field       | Type                 | Null | Key |
+-------------+----------------------+------+-----+
| id          | bigint unsigned      | NO   | PRI |
| user_id     | bigint unsigned      | YES  | MUL |
| channel     | enum('phone','email')| NO   | MUL |
| destination | varchar(191)         | NO   |     |
| otp_code    | varchar(64)          | NO   |     |
| expires_at  | timestamp            | NO   |     |
| verified_at | timestamp            | YES  |     |
| attempts    | tinyint unsigned     | NO   |     |
| ip          | varchar(45)          | YES  |     |
| created_at  | timestamp            | YES  |     |
| updated_at  | timestamp            | YES  |     |
+-------------+----------------------+------+-----+
```

## Route list (auth subset)

```
POST     api/v1/auth/lead-capture     Api\V1\Auth\LeadCaptureController
POST     api/v1/auth/login            Api\V1\Auth\LoginController
POST     api/v1/auth/logout           Api\V1\Auth\LogoutController
POST     api/v1/auth/send-otp         Api\V1\Auth\SendOtpController
POST     api/v1/auth/verify-otp       Api\V1\Auth\VerifyOtpController
GET|HEAD api/v1/user/profile          Api\V1\User\ProfileController@show
PUT      api/v1/user/profile          Api\V1\User\ProfileController@update
```

`php artisan route:list --path=api --json | jq length` → **23**
(16 existing routes preserved + 7 new auth routes = 23 ✓).

## Curl smoke tests (full chain)

```
$ curl -X POST /api/v1/auth/lead-capture \
       -d '{"name":"Smoke Test","phone":"8888888888","email":"smoke@example.com"}'
HTTP 200
{
    "success": true,
    "pending_user_id": 2,
    "otp_sent_to": "phone",
    "dev_code": "953847"
}

$ curl -X POST /api/v1/auth/verify-otp \
       -d '{"channel":"phone","destination":"8888888888","code":"1234"}'
HTTP 200
{
    "success": true,
    "token": "2|bfAIvkx44GTJY84Ir3ylFt8XJdNs0nvEXhA9VG1Ce3054ebc",
    "user": {
        "id": 2, "name": "Smoke Test", "phone": "8888888888",
        "email": "smoke@example.com",
        "is_verified_phone": true, "is_verified_email": false,
        "role": "customer", "default_address": null,
        "created_at": "2026-05-02T06:48:53.000000Z",
        "last_login_at": "2026-05-02T06:48:54.000000Z"
    }
}

$ curl /api/v1/user/profile -H "Authorization: Bearer 2|..."
HTTP 200
{ "user": <UserResource as above> }

$ curl -X POST /api/v1/auth/logout -H "Authorization: Bearer 2|..."
HTTP 200

$ curl /api/v1/user/profile -H "Authorization: Bearer 2|..."  # token revoked
HTTP 401
```

The `dev_code` field in the lead-capture response is gated by
`config('app.debug')` — it disappears when `APP_DEBUG=false`. The
bypass code `1234` was accepted because `OTP_DEV_BYPASS=true` and
`APP_ENV=local`; the audit row was persisted with `otp_code='BYPASS'`
to distinguish it from real OTP verifications.

## Production guard test

```
$ sed -i 's/^APP_ENV=local$/APP_ENV=production/' .env
$ php artisan config:clear ; php artisan list

In AppServiceProvider.php line 47:

  Refusing to boot: DevModeOtpDriver bound in production. Set
  OTP_DRIVER in production .env to a real driver (see /PHASE2_CONTRACT.md §7.4).

$ # Reverted APP_ENV=local; framework boots normally again.
```

The boot guard fires before any HTTP request can reach a controller.
Mis-deploy is structurally impossible.

## Frontend type-check + build

```
$ npx tsc --noEmit
exit 0

$ npm run build
✓ built in 25.88s
dist/index.html                 0.42 kB │ gzip:   0.28 kB
dist/assets/index-BlUgo5JH.css  104.77 kB │ gzip:  17.20 kB
dist/assets/index-CQC_vk32.js   729.25 kB │ gzip: 192.58 kB
exit 0
```

## Frontend smoke-test status

**Not driven from this session.** I started the dev server (port
3000) and the Laravel API (port 8000) and confirmed both boot
cleanly, but I cannot drive a browser to inspect DevTools. The brief
asks for these checks; they remain on the operator:

1. Header → "Login" / "Sign Up" buttons appear (FEATURES.auth=true).
2. Click Sign Up → modal opens with name/phone/email form.
3. Submit valid input → modal advances to OTP step. ONE call to
   `/api/v1/auth/lead-capture` → 200; response carries `dev_code`.
4. Enter any 4-digit code (e.g. `1234`) → click Verify. ONE call
   to `/api/v1/auth/verify-otp` → 200, token in response.
5. Modal closes, Header shows logged-in state.
6. Logout → state clears, Login button reappears.
7. Repeat with login flow (phone-only).
8. Confirm: ZERO requests to `/auth/register` (legacy endpoint —
   gone from `useAuth.ts` per the brief).

Static evidence supporting these expectations:
- `src/hooks/useAuth.ts` no longer references `/auth/register`
  (grepped: 0 matches outside historical comments).
- `src/components/AuthModal.tsx` calls only `auth.signUp`,
  `auth.logIn`, and `auth.verifyOtp` — all routed to the new
  endpoints via `src/lib/api.ts` typed fetchers.
- `src/components/Header.tsx` references `FEATURES.auth` as
  `{!FEATURES.auth ? null : !isAuthenticated ? <login> : <menu>}`;
  the flag is now `true`.

## Single commit hash

(Captured below the report after the commit lands.)

## Deviations from the contract / brief

1. **`otp_code` column size: contract §2.2 said `string(8)`; sha256
   hex is 64 chars.** Implemented as `string(64)` — the only size
   that holds a sha256 hash AND comfortably holds the `'BYPASS'`
   sentinel for the dev-bypass audit row (Decision D-C). Documented
   inline in the migration. Contract glitch; the implementation
   choice is forced by the data shape. No functional impact.

2. **`users.password` is NOT NULL in the skeleton, but OTP-only
   auth doesn't use passwords.** I did NOT add a migration to make
   it nullable (would require `doctrine/dbal` and is bigger than
   needed). Instead, `LeadCaptureController` writes
   `Str::random(60)` into the password column on user creation —
   the User model's `'password' => 'hashed'` cast bcrypts it,
   yielding an unusable password. Documented inline.

3. **Brief item 6.4 — LoginController duplicates the OTP-gen
   logic** rather than calling SendOtpController internally. The
   brief said either approach is fine ("calls SendOtpController
   internally OR duplicates"). I chose duplication for clarity:
   the controller doesn't depend on another HTTP route's
   internals, just uses the same primitives.

4. **Cart merge step at `VerifyOtpController::finishVerification`
   is omitted in 2.1.** The contract §6.5(d) explicitly
   documents this as "Cart merge — Phase 2.4". Inline comment in
   the controller marks the bookmark.

5. **`User` model's `addresses()` / `carts()` / `orders()` /
   `defaultAddress()` relationships are NOT declared in 2.1**, per
   the brief's recommendation ("omit relationship method entirely
   in 2.1, add in 2.2 — avoid forward-declared dead relations").
   Only `otps()` is declared. `UserResource.default_address`
   intentionally returns `null` until 2.2 lands.

6. **`Http\Kernel::EnsureFrontendRequestsAreStateful`** uncommented
   per brief item 10. The current frontend uses Bearer-token auth
   exclusively (no cookie/CSRF), so `EnsureFrontendRequestsAreStateful`
   is effectively a no-op for these calls — but the contract §12.1
   said to enable it, and Phase 2.4's cookie-based session work
   will need it ready. Confirmed `SANCTUM_STATEFUL_DOMAINS` is set
   in `.env` (audit confirmed: `localhost:3000,127.0.0.1:3000`).

7. **`OtpMail` Mailable is wired but un-tested at runtime** —
   `SmtpEmailOtpDriver` is not the bound default; switching to it
   requires `OTP_DRIVER=smtp-email` in `.env` plus working SMTP
   credentials. Phase 2.1 ships the class, view template, and
   binding; runtime SMTP testing is a Phase 6 concern when a real
   provider is procured.

## Stop point

Per the brief, this report is the last step before the single
commit. Phase 2.2 (addresses) is NOT started.
