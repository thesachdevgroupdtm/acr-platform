# ACR Admin Panel Setup

The Filament admin panel landed in **Phase 4.1** as a foundation
(no resources yet — Phase 4.2 adds Orders / Users / Services /
Coupons resources). This doc covers first-time login and operator
account management.

## First-time setup

1. Install dependencies (one-time, after pulling Phase 4.1 commit):
   ```bash
   composer install
   ```
2. Run the new migration:
   ```bash
   php artisan migrate
   ```
3. Seed the first admin user:
   ```bash
   php artisan db:seed --class=AdminUserSeeder
   ```
4. Visit the panel at `/admin` and log in.

## Default admin credentials

| Field    | Value |
|----------|---|
| URL      | `/admin` |
| Email    | `admin@acr-mechanics.in` |
| Password | `change-me-on-first-login` |
| Phone    | `9560321371` |

> ⚠ **CRITICAL** — change the password immediately after first
> login. The default is documented here precisely so it is
> obvious that anyone with shell access could log in until it is
> rotated.

## Adding a new admin user

### Option A — promote an existing user via Tinker

```bash
php artisan tinker
>>> App\Models\User::where('email', 'someone@example.com')->update(['is_admin' => true]);
```

The user does NOT need a separate Filament account — the admin
panel reads the same `users` table the customer-facing app uses.
Their existing OTP-login flow continues to work; promoting to
admin only adds the option to also sign in at `/admin` with their
email + password.

### Option B — within the Filament panel (after Phase 4.2)

Phase 4.2 will add a `UserResource` with an "Admin" toggle on
edit. Until then, use Option A.

## Removing admin access

```bash
php artisan tinker
>>> App\Models\User::where('email', 'someone@example.com')->update(['is_admin' => false]);
```

Their customer account is unaffected — they can still log in via
the customer site OTP flow. Only the `/admin` panel is gated.

## Changing the admin password

After logging in at `/admin`, Filament's profile page (top-right
avatar → "Edit profile") offers a password field. Or via Tinker:

```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'admin@acr-mechanics.in')->first();
>>> $u->password = bcrypt('your-new-password');
>>> $u->save();
```

## How the auth model works

- The `users` table has both `role` (enum customer|admin from
  Phase 2.1) and `is_admin` (boolean from Phase 4.1). Filament
  reads `is_admin` only — see `App\Models\User::canAccessPanel`.
  Future cleanup may consolidate these two columns; for now both
  are truthful as long as `is_admin=true` for whoever should
  access the panel.
- Customer-facing OTP login flow (Sanctum bearer tokens) is
  **completely unchanged** by Phase 4.1. The two auth paths
  coexist: bearer-token API access for the React frontend,
  email+password session auth for `/admin`.
