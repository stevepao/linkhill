# LinkHill

**v0.1** — A free, open link-in-bio app for shared LAMP hosting (e.g. IONOS). Create a single page that houses all your links—social profiles, store, newsletter—with one URL. Multi-user, MySQL-backed; public profile URLs use `/@username`. No paywalls; you own your data.

- **Project:** linkhill  
- **License:** MIT  
- **Copyright (c) 2026 Hillwork, LLC**

---

## What LinkHill does

LinkHill is a **Linktree alternative** that you host yourself. It gives you:

- **One URL** (e.g. `yoursite.com/@yourname`) that shows your name, bio, avatar, and a list of links.
- **Links** that can be buttons (title + URL), or cards with a short description. You can add **section headings** to group links (e.g. “Social”, “Shop”).
- **Customization**: button color, icon (GitHub, Instagram, etc.), light/dark theme, optional custom footer on your page.
- **Multiple users**: an admin creates accounts; each user has their own profile and links. Admins can manage users (create, reset password, delete).

Visitors click a link on your page and are redirected through your site (optional minimal click analytics). No vendor lock-in; data stays in your database.

---

## Features

- **Multi-user** with roles: **admin**, **user**
- **Auth:** password + optional TOTP (authenticator app) and **passkeys** (WebAuthn)
- **Security** page: change password, enable/disable TOTP, register/rename/remove passkeys
- **Password reset** via email (SMTP), or with **`DEV_MODE`** enabled (in your Connect item) via link in the error log when SMTP is not configured
- **Public pages** at **`/@username`** (e.g. `/@jane`)
- **Links:** title, URL, optional description (card blurb), color, icon, drag-to-reorder
- **Section headings** on your link page (e.g. “Social links”, “My shop”)
- **Profile:** display name, username, bio, avatar (JPG/PNG), theme (light/dark), custom footer
- **Minimal click analytics** via redirect `?go=<id>` (hashed IP/UA for aggregates)
- **Sitemap** at `/sitemap.xml` (static pages + profile pages that have links); updated when links or users change
- **SEO:** meta and Open Graph on homepage, About/Contact/Privacy/Terms, and profile pages
- **Local SVG icons** (GitHub, LinkedIn, Instagram, Bluesky, Substack, etc.) in `assets/icons/`
- **Shared-hosting friendly:** PDO MySQL, file-based sessions and rate limiting, `.htaccess` rewrites

---

## Requirements

- **PHP 8.1+** (8.2+ preferred; Composer dependencies require 8.1)
- **MySQL** (create database and user in your control panel)
- **Writable directories:** `storage/sessions`, `storage/rate_limit`, `storage/` (for generated `sites.xml`)
- **HTTPS** recommended (required for passkeys in production)
- **1Password Connect** reachable from the server: configure your bridge URL and vault/item in **`../op_config/linkhill_connect.json`**, with a token file in **`../op_config/`**. Keep that directory **outside the web root** and restrict permissions on the token file (see step 2 under **Installation** below).
- **Node.js 18+** (only on your dev machine) if you change HTML/PHP classes and need to rebuild the Tailwind bundle

---

## Frontend (Tailwind CSS)

The site UI is built with **Tailwind CSS**. The production stylesheet is **`public/assets/app.css`** (committed to the repo). PHP templates link to it as **`/public/assets/app.css`** — your web server document root should be the **project root** (the folder that contains `public/`, `admin/`, `index.php`, etc.) so that URL resolves. If your host serves only a subdirectory as the docroot, adjust the link in `inc/partials/head.php` or map `/public` accordingly.

- **`assets/tailwind.css`** — source: `@tailwind` layers + shared component classes (`.card`, `.btn-primary`, …).
- **`tailwind.config.js`** — `content` globs scan PHP and JS for class names (purge unused CSS on build).
- **`postcss.config.js`** — Tailwind, Autoprefixer, and **cssnano** when `NODE_ENV=production`.

Commands (run from the project root):

```bash
npm install          # install devDependencies (one-time, or after pulling package.json changes)
npm run build        # compile minified public/assets/app.css (run before commit if you changed templates or tailwind.css)
npm run dev          # watch tailwind.css and rebuild public/assets/app.css on save
```

---

## Installation (new install, v0.1)

This is for a **fresh install** with no existing users. You only need the schema; no migration scripts.

1. **Create a MySQL database** and user (host, dbname, user, pass) in your hosting control panel.

2. **1Password Connect secrets** (paths are relative to the **project root**—the folder that contains `bootstrap.php` and `index.php`; `../op_config` is **one level above** that folder and should not be web-accessible.)
   - Create **`../op_config/linkhill_connect.json`** beside your token file with Connect wiring, for example:
     ```json
     {
       "connect_base_url": "https://your-connect-bridge.example.com",
       "vault_id": "YOUR_VAULT_UUID",
       "item_title": "linkhill_env",
       "token_filename": "OP_CONNECT_TOKEN",
       "http_timeout_seconds": 25
     }
     ```
     Required keys: **`connect_base_url`**, **`vault_id`**, **`item_title`**. Optional: **`token_filename`** (default `OP_CONNECT_TOKEN`), **`http_timeout_seconds`** (default `25`, allowed range 1–120).
   - Put your Connect access token in **`../op_config/`** using the configured filename (default **`OP_CONNECT_TOKEN`**). The app resolves paths with `realpath` at runtime.
   - In the vault given by **`vault_id`**, keep an item whose **title** equals **`item_title`**. Each **custom field label** must match the environment variable name the app expects (for example `DB_HOST`, `SMTP_PASS`, `WEBAUTHN_ORIGIN`). Values are injected into `$_ENV` and `putenv()` before `config()` runs (see [Configuration](#configuration)).

3. **Import the schema**
   - In phpMyAdmin or your DB manager, import **`sql/schema.sql`** into your database.
   - This creates all tables (users, links, link_clicks, password_resets, webauthn_credentials, email_verifications). No other SQL scripts are needed for a new install.

4. **Composer**
   - On your machine (with PHP and Composer), run: `composer install`
   - Upload the **entire project** to the server (e.g. via SFTP), **including the `vendor/` folder**. LinkHill needs Composer packages for **1Password Connect loading (Guzzle)**, **passkeys**, and **SMTP**—without `vendor/autoload.php`, requests will fail when bootstrap runs.

5. **Upload the app**
   - Upload all files to your web root (or the folder that will be the document root for your domain).
   - Ensure **`storage/sessions`**, **`storage/rate_limit`**, and **`storage/`** are writable (e.g. `chmod 755`).

6. **Create the first admin**
   - In a browser, open **`/admin_seed.php`** once.
   - It creates an initial admin user and shows the temporary credentials on the page.
   - Sign in at **`/login`** (or `/admin/login.php`), then go to **Security** and change the password.
   - **Delete `admin_seed.php`** from the server after use.

7. **Use the app**
   - **Login:** `/login` or `/admin/login.php`
   - **Sign up:** `/signup` (optional; admins can also create users from **Users**)
   - **Profile:** set display name, bio, avatar, theme, custom footer
   - **Links:** add links and headings, reorder by drag, set color and icon
   - **Public page:** `/@username` (e.g. `yoursite.com/@jane`)
   - **Sitemap:** `yoursite.com/sitemap.xml`

---

## Configuration

Runtime configuration is built from **environment variables** that **`inc/secrets_loader.php`** fills **once per request**: it reads **`../op_config/linkhill_connect.json`**, loads your Connect token from **`../op_config/`**, then calls the [Connect REST API](https://developer.1password.com/docs/connect/api-reference/) (via **Guzzle**) and maps each **field label** on the configured item to `$_ENV` / `putenv()`. **`config/config.php`** reads those variables into the array returned by `config()`—there is no `.env` file.

### Why 1Password Connect?

The app uses a **1Password Connect** server so that **application secrets** (database, SMTP, WebAuthn settings, and similar) are **not stored in the repository**, a committed config file, or a `.env` on the server. They live in **1Password** and are served to PHP only at runtime, which supports:

- **Smaller attack surface on disk** — No long-lived copy of database passwords or API secrets beside the app tree; nothing sensitive needs to be edited in SFTP or echoed into hosting panels for routine deploys. *(You still place one **Connect access token** in `../op_config/` so PHP can authenticate to Connect—that single token should be tightly permissioned; rotate it in 1Password if it may be exposed.)*
- **Easier auditing** — Access and use of secrets can be tied to **1Password** and **Connect** logging and policies rather than grepping server files.
- **Smoother credential rotation** — Update passwords or keys **in 1Password**; the next request picks up new values without redeploying a flat config or rebuilding `.env` files for every secret change.
- **Centralized policy** — Who may view or edit secrets follows your **1Password** vault permissions and workflows instead of ad hoc file permissions across hosts.

Field labels on the item whose title matches **`item_title`** in JSON should mirror these keys (values may contain spaces; unlike `.env`, no quoting rules):

- `APP_NAME`, `APP_BASE_URL`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
- `SESSION_NAME`, `COOKIE_SECURE`, `COOKIE_SAMESITE`, `PASSWORD_COST`, `APP_TIMEZONE`
- `DEV_MODE`, `MIGRATION_KEY`
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`
- `WEBAUTHN_RP_ID`, `WEBAUTHN_RP_NAME`, `WEBAUTHN_ORIGIN`

- **Passkeys:** Need HTTPS. Set `WEBAUTHN_ORIGIN` and `WEBAUTHN_RP_ID` to your domain.
- **Base URL:** Set `APP_BASE_URL` if the app lives in a subdirectory or you need a fixed URL for emails and sitemaps.

---

## Deploying on 1&1 / IONOS

- Set **PHP 8.1+** for the domain in the control panel.
- Create a MySQL database and user; store DB credentials as fields on the Connect item whose title matches **`item_title`** in **`linkhill_connect.json`** (same as step 2 under **Installation**).
- Point the domain’s **document root** to the folder that contains `index.php` and `.htaccess`.
- Make **storage/sessions**, **storage/rate_limit**, and **storage** writable (File Manager or SFTP).
- To use passkeys and SMTP without SSH: run `composer install` locally, then upload the project **including `vendor/`**.
- **HTTPS** is required for passkeys; IONOS typically offers free SSL.

---

## Troubleshooting

### 500 when visiting `/admin/login.php` or `/login`

- Ensure **storage/sessions** exists and is writable.
- Enable `display_errors` or check the PHP error log; set **session.save_path** in `.user.ini` if the default path isn’t writable.

### 500 right after deploy / “Connect” or “op_config” errors

- Confirm **`../op_config/linkhill_connect.json`** exists next to your token file and contains valid JSON with **`connect_base_url`**, **`vault_id`**, and **`item_title`**.
- Confirm the token file exists (default **`../op_config/OP_CONNECT_TOKEN`**) and is readable by PHP.
- From the server, the URL in **`connect_base_url`** must be reachable (TLS, firewall, DNS).
- Confirm **`vendor/`** was uploaded (`composer install` includes Guzzle used by `secrets_loader.php`).

### `/@username` returns 404 or wrong page

- Try **`index.php?u=username`** (works without rewrites).
- Set the domain’s document root to the folder that contains `.htaccess`.
- If the app is in a subdirectory, set **RewriteBase** in `.htaccess` (e.g. `RewriteBase /link`).

### “Call to undefined function” / “Class not found”

- Confirm **PHP 8.1+** in the control panel.
- Ensure **`vendor/`** was uploaded after **`composer install`** (required for Connect bootstrap, passkeys, and SMTP).

### Sitemap not updating

- Ensure **storage/** is writable so `storage/sites.xml` can be created/updated when links or users change.

---

## Notes

- **Security:** CSRF is required on all POST and AJAX requests; sessions use SameSite, Secure, HttpOnly.
- **Rate limiting:** Login, password reset, signup, and WebAuthn endpoints are rate-limited (file-based in `storage/rate_limit/`).
- **Icons:** SVGs in `assets/icons/` can be replaced with your own.
- **Backups:** Back up the database regularly.
- **v0.1:** For a new install you only need `sql/schema.sql`. Other files in `sql/` are for upgrading from older development builds and are not required for new installs.
