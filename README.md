# Wedding Website Prompt Generator

Sistem web untuk membuat *prompt* undangan pernikahan digital yang siap digunakan oleh AI developer. Dilengkapi dengan sistem autentikasi (user & admin), dashboard terpisah, kuota harian, dan manajemen file upload.

---

## 🚀 Demo

- **Admin login**: `admin@example.com` / `admin123`
- **User login**: `user@example.com` / `user123`

---

## ✨ Fitur Utama

- **🔐 Sistem Autentikasi** — Registrasi & login terpisah untuk user dan admin
- **📊 Dashboard User** — Generate prompt, simpan, edit, hapus prompt sendiri
- **⚙️ Dashboard Admin** — Lihat statistik, semua user, dan semua prompt yang dibuat
- **📊 Kuota Harian** — 5 prompt per user per hari (reset otomatis)
- **📁 Manajemen File** — Upload foto (2MB) & musik (5MB), otomatis hapus saat prompt dihapus
- **🎨 Generator Prompt** — Form lengkap (nama, tanggal, lokasi, tema, visual style, elemen pilihan)
- **🔌 API RSVP & Ucapan** — Endpoint publik, API key unik per prompt, untuk website HTML yang di-generate AI

---

## 🚀 Cara Menjalankan & Menggunakan

1. Buka halaman utama
2. Daftar akun baru atau login dengan akun demo
3. Masuk ke **Dashboard User** → klik **Generator Baru**
4. Isi data pasangan dan pilih elemen yang ingin ditampilkan
5. Klik **Generate Prompt**, salin hasilnya, atau simpan ke dashboard
6. Kelola prompt (edit/hapus) dari **Prompt Saya**

> Admin masuk ke dashboard terpisah untuk melihat semua data pengguna dan penggunaan prompt.

---

## 🛠️ Teknologi yang Digunakan

- **PHP 8** (native, tanpa framework) + PDO
- **MySQL / MariaDB**
- HTML5, CSS3, JavaScript (Vanilla JS)

---

## 📦 Cara Instalasi

### 1. Prasyarat

- PHP **8.0+**
- MySQL **8.0+** atau MariaDB
- Web server (Apache / Nginx / XAMPP / Laragon)

### 2. Setup database

Buat database lalu impor skema. Lewat CLI MySQL:

```bash
mysql -u root -p < schema.sql
```

Atau buat manual di phpMyAdmin: buat database dengan ekspor impor file `schema.sql`. Schema otomatis membuat tabel `users`, `prompts`, dan `quota_logs`.

### 3. Konfigurasi

Edit file `config.php` sesuai koneksi DB dan domain kamu:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wedding_gen_db');
define('BASE_URL', 'http://localhost/nama-folder-project');
```

### 4. Deploy

Letakkan seluruh folder project ke web root server (contoh Laragon: `C:\laragon\www\`).

### 5. Akun awal

Halaman login **otomatis membuat** akun demo bila belum ada:
- **Admin**: `admin@example.com` / `admin123`
- **User**: `user@example.com` / `user123`

Buka URL project di browser untuk memulai.

---

## ⚠️ Batasan Upload

| Media  | Format                | Ukuran Maks |
|--------|-----------------------|-------------|
| Gambar | jpg, jpeg, png        | 2 MB        |
| Musik  | mp3                   | 5 MB        |

---

## 🔌 API RSVP & Ucapan Tamu

Setiap prompt yang disimpan otomatis mendapat **API key unik**. Website undangan (yang dibuat dari prompt) bisa terhubung ke API ini untuk menyimpan & menampilkan RSVP serta ucapan tamu secara real-time.

### Base URL

```
{BASE_URL}/api.php
```

### Autentikasi

Seluruh request memerlukan `api_key` di query parameter. API key didapat dari dashboard user setelah prompt disimpan.

### Endpoints

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `api.php?action=info&api_key=KEY` | Ambil nama pengantin |
| GET | `api.php?action=rsvp&api_key=KEY` | Lihat semua RSVP |
| POST | `api.php?action=rsvp&api_key=KEY` | Simpan RSVP |
| GET | `api.php?action=wish&api_key=KEY` | Lihat semua ucapan |
| POST | `api.php?action=wish&api_key=KEY` | Simpan ucapan |

### Contoh POST RSVP

```
POST /api.php?action=rsvp&api_key=KEY
Content-Type: application/json

{ "name": "Budi", "attending": "hadir", "guests": 2, "message": "Selamat!" }
```

Nilai `attending`: `hadir` | `tidak_hadir` | `belum_pasti`

### Contoh POST Ucapan

```
POST /api.php?action=wish&api_key=KEY
Content-Type: application/json

{ "name": "Sari", "message": "Semoga langgeng!" }
```

### Format Response

Semua endpoint return JSON:

```json
{ "success": true, "data": [...] }
```

Error:

```json
{ "success": false, "error": "pesan error" }
```

### Contoh javascript

```js
const KEY = 'API_KEY_ANDA';
const BASE = 'https://domain-anda/api.php';

// Simpan RSVP (WAJIB cek response.ok && data.success)
async function submitRSVP(payload) {
    const res = await fetch(`${BASE}?action=rsvp&api_key=${KEY}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
        // tampilkan error
        return;
    }
    // sukses
}

// Lihat daftar ucapan
const res = await fetch(`${BASE}?action=wish&api_key=${KEY}`);
const data = await res.json();
if (res.ok && data.success && Array.isArray(data.data)) {
    renderWishes(data.data);
}
```

### Catatan

- Props **read**: GET publik tanpa zona (siapapun dengan api_key bisa baca RSVP/ucapan — kekhasan undangan tamu melihat ucapan).
- CORS `*` diaktifkan agar bisa dipanggil dari HTML statis di domain lain.
- Data RSVP/ucapan otomatis terhapus bila prompt dihapus (FK CASCADE).

---

## 📁 Struktur Proyek

```text
generator-web-uandangan/
├── config.php              # Konfigurasi DB + BASE_URL + helpers
├── schema.sql              # Skema database (users, prompts, quota_logs, rsvps, wishes)
├── migration_api.sql       # Migrasi tambahan (API key + RSVP/Wishes)
├── api.php                 # Public API RSVP & Ucapan Tamu
├── index.php               # Halaman utama (landing)
├── lib/
│   └── auth.php            # Session, login, logout, guard role
├── pages/
│   ├── login.php           # Login + seeder akun demo
│   ├── register.php        # Registrasi user baru
│   ├── user/               # Dashboard & generator untuk user
│   │   ├── dashboard.php
│   │   ├── generator.php
│   │   ├── save.php
│   │   ├── edit.php
│   │   ├── delete.php
│   │   └── upload.php
│   └── admin/              # Dashboard & manajemen untuk admin
│       ├── dashboard.php
│       ├── users.php
│       └── prompts.php
└── assets/
    ├── style.css           # Stylesheet (ikut tercommit)
    └── (*.jpg, *.png, *.mp3)  # File upload (tidak masuk git)

