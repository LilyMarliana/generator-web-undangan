# Agent Guide: Wedding Website Prompt Generator

PHP 8 native web app for generating AI prompts for wedding invitations.

## 🛠 Tech Stack
- **Backend:** PHP 8 (Native, PDO)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JS
- **Auth:** Session-based + Supabase Auth (email/password, Google OAuth)

## 📂 Key Locations
- `config.php`: DB connection, `BASE_URL`, dan kredensial Supabase (gitignored).
- `lib/auth.php`: Login/Role guards (`require_login()`, `require_admin()`).
- `lib/supabase.php`: Client Supabase Auth (sign in/up, OAuth PKCE).
- `pages/auth.php`: Handler redirect & callback Google OAuth.
- `api.php`: Public RSVP & Wishes API (requires `api_key`).
- `pages/user/`: Dashboard, generator, and prompt management.
- `assets/`: Stylesheet and uploaded files (`.gitignore` excludes uploads).

## 🚀 Dev Commands & Shortcuts
- **Reset DB:** `mysql -u <user> -p < schema.sql; mysql -u <user> -p <name> < migration_api.sql`
- **Akun:** tidak ada akun demo otomatis. Daftar lewat `pages/register.php` atau tombol Google OAuth. Untuk admin, ubah `users.role` jadi `admin`.

## 💡 Quirks & Constraints
- **Quota:** Users limited to **5 prompts per day** (tracked in `quota_logs`).
- **File Uploads:** 
  - Images (JPG/PNG) max 2MB.
  - Music (MP3) max 5MB.
  - Uploaded via `pages/user/upload.php` before saving prompt.
- **CSRF:** Every POST form requires `<input type="hidden" name="csrf" value="<?= csrf_token() ?>">`.
- **API Key:** Generated per prompt. Injected into `{API_KEY}` placeholder in generated text or appended if missing.
- **CORS:** Enabled (`*`) in `api.php`.

## 🧪 Testing
Manual testing via browser is primary. Verify API via `curl` or Postman:
`GET /api.php?action=info&api_key=<KEY>`
