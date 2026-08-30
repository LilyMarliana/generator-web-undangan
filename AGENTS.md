# Agent Guide: Wedding Website Prompt Generator

PHP 8 native web app for generating AI prompts for wedding invitations.

## 🛠 Tech Stack
- **Backend:** PHP 8 (Native, PDO)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JS
- **Auth:** Session-based (user/admin)

## 📂 Key Locations
- `config.php`: DB connection and `BASE_URL`.
- `lib/auth.php`: Login/Role guards (`require_login()`, `require_admin()`).
- `api.php`: Public RSVP & Wishes API (requires `api_key`).
- `pages/user/`: Dashboard, generator, and prompt management.
- `assets/`: Stylesheet and uploaded files (`.gitignore` excludes uploads).

## 🚀 Dev Commands & Shortcuts
- **Reset DB:** `mysql -u <user> -p < schema.sql; mysql -u <user> -p <name> < migration_api.sql`
- **Demo Accounts (Seeded on `pages/login.php` access):**
  - Admin: `admin@example.com` / `admin123`
  - User: `user@example.com` / `user123`

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
