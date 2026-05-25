# Hostinger deployment — ACR (Laravel + React)

Target host: `acr-mechanics.in` · Hostinger shared (hPanel)

## 1 · Final folder layout on the server

```
/public_html/
├── index.php                ← from deploy/index.php (entry point)
├── .htaccess                ← from deploy/.htaccess (root rewrite rules)
│
├── backend/                 ← FULL Laravel project (NEVER served directly — blocked by .htaccess)
│   ├── app/
│   ├── bootstrap/           ← MUST exist (bootstrap/app.php)
│   ├── config/
│   ├── database/            ← migrations, seeds, factories
│   ├── public/              ← (kept for completeness; nothing in here is served)
│   ├── resources/
│   ├── routes/
│   │   ├── api.php          ← /api/v1/*
│   │   └── web.php          ← legacy Blade routes
│   ├── storage/             ← writable; chmod 775
│   │   ├── app/
│   │   ├── framework/
│   │   └── logs/
│   ├── vendor/
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   └── .env                 ← from deploy/.env.production.template (FILL IN VALUES)
│
├── app/                     ← React build output (Vite dist/)
│   ├── index.html
│   ├── assets/
│   │   ├── index-XXXX.js
│   │   └── index-XXXX.css
│   ├── .htaccess            ← from deploy/app.htaccess (SPA fallback)
│   └── …
│
└── uploads/                 ← (optional — legacy site uses /public/uploads/...; mirror what the
                                live app expects — we don't change this here)
```

---

## 2 · Files to upload (per source)

| Source (this repo) | Destination on server | Notes |
|---|---|---|
| `deploy/index.php` | `/public_html/index.php` | Custom entry — boots Laravel from `/backend/` |
| `deploy/.htaccess` | `/public_html/.htaccess` | Routes `/api/*` → Laravel, `/app/*` → React, blocks `/backend/*` |
| `deploy/app.htaccess` | `/public_html/app/.htaccess` | SPA fallback inside `/app/` |
| `deploy/.env.production.template` → fill in | `/public_html/backend/.env` | Production secrets — never commit |
| `backend/` (this repo) **+ missing skeleton** | `/public_html/backend/` | See note below |
| `dist/*` (after `npm run build`) | `/public_html/app/` | React build artefacts |

> **Backend skeleton note.** This repo's `backend/` is missing `bootstrap/`, `public/`, `artisan`, `composer.json`, `composer.lock`, and `database/`. Pull those from your live source (or `_public_html.zip`) and include them under `/public_html/backend/`. They are unmodified between this repo and the live site.

---

## 3 · Step-by-step

### A · Build the React app (locally)

```bash
# At project root (next to package.json):
cp deploy/frontend.env.production .env.production
npm install
npm run build
```

This produces `./dist/` with paths prefixed by `/app/` (because `vite.config.ts` now sets `base: '/app/'` when `mode=production`).

Sanity-check `dist/index.html` — every script/style tag should look like:
```html
<script type="module" crossorigin src="/app/assets/index-XXXX.js"></script>
<link rel="stylesheet" crossorigin href="/app/assets/index-XXXX.css">
```
If they say `/assets/...` instead of `/app/assets/...`, the `base` didn't kick in — re-run `npm run build` after confirming `mode=production`.

### B · Prepare the backend bundle (locally)

```bash
# Combine this repo's backend/ with the missing skeleton from the live source
mkdir -p deploy-staging/backend
cp -r backend/* deploy-staging/backend/
# … then add bootstrap/, public/, artisan, composer.json, composer.lock, database/
# from the live site or the zip
cp deploy/.env.production.template deploy-staging/backend/.env
# Edit deploy-staging/backend/.env and fill in real DB / mail / API key values.
```

> If you have shell access on Hostinger and Composer is available there, you can skip uploading `vendor/` and run `composer install --no-dev --optimize-autoloader` on the server instead.

### C · Upload via FTP / Hostinger File Manager

```
deploy/index.php             → public_html/index.php
deploy/.htaccess             → public_html/.htaccess
deploy-staging/backend/      → public_html/backend/        (whole directory)
dist/                        → public_html/app/            (whole directory contents)
deploy/app.htaccess          → public_html/app/.htaccess
```

### D · Server-side post-upload (via Hostinger SSH or hPanel terminal)

```bash
cd public_html/backend

# Permissions — Laravel needs storage and bootstrap/cache writable
chmod -R 775 storage bootstrap/cache
# (On Hostinger, your user already owns the files; chmod is enough.)

# Generate APP_KEY if you didn't paste one into .env
php artisan key:generate --force

# Run pending migrations (creates personal_access_tokens table for Sanctum if missing)
php artisan migrate --force

# Cache config / routes / views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optional: clear any prior cache that referenced old paths
php artisan optimize:clear
```

Set the **PHP version** in hPanel → Advanced → PHP Configuration → 8.1 or 8.2 (whichever the live site uses). PHP 7.x will fail because Laravel 10+ requires 8.1+.

---

## 4 · Verification (in this exact order)

### 4.1 Backend reachable

```
GET  https://acr-mechanics.in/api/v1/home
```
**Expected:** `200 OK`, `Content-Type: application/json`, body starts with `{"success":true,...`.

| What you see | Diagnosis |
|---|---|
| `404 Not Found` | `routes/api.php` not loaded → run `php artisan route:clear && php artisan config:clear` on server |
| HTML home page | `.htaccess` not active → confirm hPanel → Apache Modules has `mod_rewrite` enabled |
| `500 Internal Server Error` | Check `public_html/backend/storage/logs/laravel.log` — usually permissions or DB credentials |
| `403 Forbidden` on `/backend/*` URL | **This is correct** — direct access blocked by `RewriteRule ^backend(/.*)?$ - [F,L]` |

### 4.2 Legacy Blade site still works

```
GET  https://acr-mechanics.in/                         → legacy home (Blade)
GET  https://acr-mechanics.in/our-services             → legacy listing (Blade)
GET  https://acr-mechanics.in/backend/login            → admin login (Blade)
```
All should render exactly as on the previous host. The API additions are non-breaking.

### 4.3 React app reachable

```
GET  https://acr-mechanics.in/app/
```
**Expected:** React SPA loads. DevTools → Network shows:
- `/app/index.html` → 200, `text/html`
- `/app/assets/index-XXXX.js` → 200, `application/javascript`
- `/app/assets/index-XXXX.css` → 200, `text/css`
- (then) `[API →] GET https://acr-mechanics.in/api/v1/user/profile (auth)` only if you have a stored token

| What you see | Diagnosis |
|---|---|
| Blank white page, console: `Failed to load module script … MIME type "text/html"` | Vite `base` not `/app/` — rebuild after confirming `mode=production`, or hardcode `VITE_BASE_PATH=/app/` |
| `404` on `/app/` | Missing `app/index.html` or root `.htaccess` not picking up the rewrite |
| `404` on `/app/assets/index-XXXX.js` | The `dist/` upload was incomplete; re-upload the entire `assets/` folder |

### 4.4 End-to-end auth flow

In a fresh incognito window at `https://acr-mechanics.in/app/`:

1. Click **Login** → submit valid credentials.
2. Network tab shows:
   ```
   POST https://acr-mechanics.in/api/v1/auth/login   → 200, body has token
   GET  https://acr-mechanics.in/api/v1/user/profile (auth) → 200
   ```
3. Application tab → Local Storage → `acr_api_token_v1` set.
4. Refresh page → still logged in.

### 4.5 CORS

Same-origin (`acr-mechanics.in/app` → `acr-mechanics.in/api/v1`), so **no CORS preflight should occur**. If you see `OPTIONS` requests in the Network tab, double-check `api.ts` is calling the production URL (not a different origin).

---

## 5 · What to do if you'd rather build on the server

If your local machine can't run `npm run build` (e.g., locked down):

```bash
# On Hostinger, after cloning the repo somewhere (NOT under public_html):
cd ~/acr-build
npm install
cp deploy/frontend.env.production .env.production
npm run build
cp -r dist/* ~/public_html/app/
cp deploy/app.htaccess ~/public_html/app/.htaccess
```

Hostinger Premium and above support Node.js via SSH. Business / Cloud have it directly.

---

## 6 · Things this guide deliberately does not change

- The **legacy Blade `routes/web.php`** continues to serve `/`, `/our-services`, `/backend/login`, etc. We add API routes alongside it, never replace.
- The **DB schema** is unchanged. Sanctum's `personal_access_tokens` table is created by `php artisan migrate` and is the only required addition.
- The **legacy admin panel** at `https://acr-mechanics.in/backend/login` is the LEGACY ROUTE — it's served by Laravel via `routes/web.php`, **not** by directly accessing the `backend/` folder. The `.htaccess` rule that blocks `/backend/*` only blocks attempts to read filesystem files; Laravel's `Route::group(['prefix' => 'backend'])` registrations still work because requests go through `index.php` first.

---

## 7 · Rollback

The only file that overrides the existing site behaviour is `/public_html/index.php`. Keep a backup of the previous `index.php` before uploading; restoring it brings the legacy site back to exactly its prior state. Nothing in `/public_html/backend/` or `/public_html/app/` interferes with the legacy site if `index.php` is reverted.
