# LinkHub (Shared‑Hosting Friendly Link‑in‑Bio)

Multi‑user, MySQL‑backed, MFA‑enabled Linktree‑style app for shared LAMP hosting (e.g., IONOS).  **No Composer, No Node, No cron.** Public profile URLs use `/@username`. Admins manage users; users manage their own links with drag‑to‑reorder, color picker, and local SVG icon presets.

## Features

- Multi‑user with roles: `admin`, `user`
- Authentication with password + optional TOTP MFA (QR provisioning)
- Public pages at `/@username`
- Links: title, URL, color, icon preset, drag‑to‑reorder
- Minimal click analytics via redirect `/index.php?go=<id>`
- Local SVG icon set (customizable)
- Shared‑hosting safe: PDO MySQL, sessions, `.htaccess`

## Quickstart (IONOS or similar)

1. **Create MySQL DB** (host, dbname, user, pass) in your control panel.
2. **Set PHP 8.1+** for the site.
3. Edit `config/config.php` with your DB credentials (keep `cookie_secure=true` on HTTPS).
4. **Import schema**: upload `sql/schema.sql` via phpMyAdmin or DB Manager.
5. **Upload files** to your web root (or a subfolder) via SFTP.
6. Open `/admin_seed.php` once — it creates an initial admin (`admin@example.com / ChangeMeNow!123`). **Delete this file immediately.**
7. Visit `/admin/login.php`, sign in, (optionally) **enable MFA** under `/admin/mfa.php`.
8. As admin, go to `/admin/users.php` to create more users.
9. As a user, set profile at `/admin/profile.php`, manage links at `/admin/links.php`.
10. Public page is at `/@username`.

## Troubleshooting

### 500 error when visiting /admin/login.php

A 500 usually means the server couldn’t start sessions or hit a PHP error. The app uses a **project-local session directory** (`storage/sessions/`) so the default system path doesn’t have to be writable.

1. **Make `storage/sessions` writable on the server**  
   After uploading, set permissions so the web server can write there, e.g. `chmod 755 storage` and `chmod 755 storage/sessions` (or 700 if your host allows). On IONOS you can do this in the File Manager or via SFTP.

2. **See the real PHP error**  
   In your **project root**, add or edit `.user.ini` and set:
   ```ini
   display_errors = 1
   log_errors = 1
   ```
   Reload `/admin/login.php`; the page may now show the error. Or check the **PHP error log** in your IONOS control panel (e.g. “Error log” or “Log files”) for the exact message.

3. **Force a custom session path (if needed)**  
   If the app can’t use `storage/sessions`, set it yourself. In `.user.ini` in the project root:
   ```ini
   session.save_path = "/path/to/your/writable/sessions"
   ```
   Use the full server path to a folder that exists and is writable by the web server.

### /@username returns 404 or “another page” (e.g. on 1&1/IONOS)

Pretty URLs like `https://link.hillwork.net/@spao` rely on Apache rewriting `/@spao` to `index.php?u=spao`. If you get a 404 or a different page:

1. **Use the fallback URL**  
   `https://yoursite.example.com/index.php?u=spao` works without rewrites. If that loads the profile, the app is fine and the problem is only rewrite config.

2. **Point the domain at the app folder**  
   For `link.example.com`, set the domain’s **document root** in the 1&1/IONOS control panel to the folder that contains `index.php` and `.htaccess` (e.g. `…/htdocs/link/`). If the docroot is a parent folder that doesn’t contain `.htaccess`, `/@spao` will never be rewritten.

3. **If the app is in a subdirectory**  
   If the app lives at `yoursite.example.com/link/`, open `.htaccess` and set:
   ```apache
   RewriteBase /link
   ```
   (no trailing slash). Then use `yoursite.example.com/link/@spao` or `yoursite.example.com/link/index.php?u=spao`.

4. **Allow .htaccess to run**  
   Rewrites only work if the server allows it. In 1&1/IONOS, “AllowOverride” for the site must allow `.htaccess` (often “All” or at least “FileInfo”). If you can’t change this, use `index.php?u=username` as the profile URL.

### Other issues

- **“Call to undefined function” or “Class not found”** — Confirm the site runs on **PHP 8.1 or higher** in the IONOS control panel.
- **Blank page or “headers already sent”** — Ensure no PHP file has output (spaces/BOM) before `<?php`, and that no file sends output before `header()` / redirects.

## Notes

- **Security headers** are set in `.htaccess`. If your host blocks headers, add them via PHP.
- **CSRF** is required on all POST and AJAX requests.
- **Sessions**: If session issues, add `.user.ini` with a writable `session.save_path`.
- **Icons**: Replace SVGs in `/assets/icons/` with your preferred set.
- **CDN JS**: We use CDN for SortableJS and QRCode.js in admin pages. You can download them into `/assets/js/` and update script tags.
- **Backups**: Back up DB regularly; consider a daily dump.
