# sucky.life — Plug & Play (First‑Run Password)

This build includes a default password and forces you to update it on first login.

## Default Admin Password
- **Username:** (not required)
- **Password:** `sucky-life`

On first login, you’ll be redirected to set a new password. No code edits needed.

## Quick Start
1. Upload everything to your PHP host.
2. Ensure `assets/uploads/` and `eggs/data/` are writable by PHP.
3. Go to `/admin` → login with `sucky-life` → set your new password.
4. Replace `assets/friend.jpg` and `assets/screech.mp3` with your real files.

## Notes
- Password is stored in `/admin/password.json`. Changing it via the UI updates this file.
- WebP conversions require GD with WebP support; otherwise we keep the original format.
- You can deep link to eggs via `/?egg=slug`.
