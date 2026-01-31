# LinkHill

**v0.1** — Multi-user, MySQL-backed link-in-bio app for shared LAMP hosting (e.g. IONOS). Public profile URLs use `/@username`. Admins manage users; users manage their own links with drag-to-reorder, color picker, and local SVG icon presets.

- **Project:** linkhill  
- **License:** MIT  
- **Copyright (c) 2026 Hillwork, LLC**

---

## Features

- Multi-user with roles: **admin**, **user**
- **Auth:** password + optional TOTP (two-step verification) and **passkeys** (WebAuthn)
- **Security** page: password change, TOTP, passkeys (register / rename / remove)
- **Password reset** via email (SMTP or dev log)
- Public pages at **`/@username`**
- Links: title, URL, optional description, color, icon preset, drag-to-reorder
- Minimal click analytics via redirect `index.php?go=<id>`
- Local SVG icon set (Substack, Bluesky, GitHub, etc.)
- Shared-hosting friendly: PDO MySQL, file-based sessions and rate limiting, `.htaccess`

---

## Requirements

- **PHP 8.1+** (8.2+ preferred; Composer deps require 8.1)
- **MySQL** (create DB and user in your control panel)
- **Writable dirs:** `storage/sessions`, `storage/rate_limit`
- **HTTPS** recommended (required for passkeys in production)

---

## Installation (new install)

1. **Create a MySQL database** and user (host, dbname, user, pass) in your hosting control panel.

2. **Copy config**
   - Copy `config/config.example` to `config/config.php`.
   - Edit `config/config.php`: set **db** (host, dbname, user, pass).  
   - Keep `cookie_secure => true` on HTTPS. Optionally set **smtp** and **webauthn** (see [Configuration](#configuration)).

3. **Import the schema**
   - In phpMyAdmin or your DB manager, import **`sql/schema.sql`** into your database.  
   - This creates all tables (users, links, link_clicks, password_resets, webauthn_credentials, email_verifications).

4. **Composer (for passkeys and email)**
   - On your **local machine** (with PHP and Composer), run:  
     `composer install`  
   - Upload the **entire project** to the server via SFTP/FTP, **including the `vendor/` folder**.  
   - If you don’t upload `vendor/`, the app still runs (password + TOTP, links, profiles); passkeys and SMTP-based password reset will show a message that Composer dependencies are required.

5. **Upload the app**
   - Upload all files to your web root (or the folder that will be the document root for your domain).  
   - Ensure **`storage/sessions`** and **`storage/rate_limit`** are writable (e.g. `chmod 755`).

6. **Create the first admin**
   - In a browser, open **`/admin_seed.php`** once.  
   - It creates an initial admin user (see the on-screen message for credentials).  
   - **Delete `admin_seed.php`** from the server immediately after.

7. **Sign in**
   - Visit **`/admin/login.php`** (or `/login` if you use a front controller).  
   - Sign in with the admin account. Use **Security** for password change, TOTP, and passkeys.  
   - As admin, go to **`/admin/users.php`** to create more users.  
   - Public profile: **`/@username`** (or `index.php?u=username` if rewrites aren’t available).

---

## Configuration

In `config/config.php` (from `config.example`):

| Key | Purpose |
|-----|--------|
| **db** | host, dbname, user, pass, charset |
| **session_name**, **cookie_secure**, **cookie_samesite**, **password_cost**, **timezone** | Session and app behavior |
| **dev_mode** | If `true`, password reset links are written to the PHP error log when SMTP isn’t configured (useful for local/dev). |
| **smtp** | host, port, secure (tls/ssl), user, pass, from, from_name. If not set, password reset emails aren’t sent (in dev_mode the link is logged). |
| **webauthn** | **rp_id** (e.g. `example.com`), **rp_name**, **origin** (e.g. `https://example.com`). Required for passkeys. |

- **Passkeys:** Need HTTPS. Set **webauthn** `origin` and `rp_id` to match your domain.  
- **Base URL:** Set **base_url** if the app lives in a subdirectory or you need a fixed URL.

---

## Deploying on 1&1 / IONOS

- Set **PHP 8.1+** for the domain in the control panel.
- Create MySQL DB and user; put credentials in `config/config.php`.
- Point the domain’s **document root** to the folder that contains `index.php` and `.htaccess`.
- Make **storage/sessions** and **storage/rate_limit** writable (File Manager or SFTP).
- To get passkeys and SMTP working without SSH: run `composer install` locally, then upload the project **including `vendor/`**.
- **HTTPS** is required for passkeys; IONOS usually offers free SSL.

---

## Troubleshooting

### 500 when visiting `/admin/login.php`

- Ensure **storage/sessions** exists and is writable.  
- Enable `display_errors` or check the PHP error log; set **session.save_path** in `.user.ini` if the default path isn’t writable.

### `/@username` returns 404 or wrong page

- Use **`index.php?u=username`** as a fallback (works without rewrites).  
- Set the domain’s document root to the folder that contains `.htaccess`.  
- If the app is in a subdirectory, set **RewriteBase** in `.htaccess` (e.g. `RewriteBase /link`).

### “Call to undefined function” / “Class not found”

- Confirm **PHP 8.1+** in the control panel.  
- If passkeys or email are used, ensure **vendor/** was uploaded after `composer install`.

---

## Notes

- **Security:** CSRF required on all POST/AJAX; sessions use SameSite, Secure, HttpOnly.  
- **Rate limiting:** Login, password reset, and WebAuthn endpoints are rate-limited (file-based in `storage/rate_limit/`).  
- **Icons:** SVGs in `assets/icons/` can be replaced.  
- **Backups:** Back up the database regularly.
