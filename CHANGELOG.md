# Changelog

## [0.1.0] — 2026

Initial release.

- Multi-user link-in-bio with admin and user roles
- Password + TOTP + passkeys (WebAuthn)
- Password reset via email (SMTP or dev log)
- Public profiles at `/@username`
- Links: title, URL, description, color, icon, drag-to-reorder
- Local SVG icons (Substack, Bluesky, GitHub, etc.)
- Shared-hosting friendly (PDO MySQL, file-based sessions and rate limiting)
- Install via `sql/schema.sql` only (no migration step for new installs)
