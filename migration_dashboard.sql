-- Migrasi dashboard & CRUD (jalankan SEKALI pada database yang sudah ada)
-- Contoh: mysql -u root -p NAMA_DATABASE_KAMU < migration_dashboard.sql
-- Catatan: untuk instalasi baru, tidak perlu dijalankan karena schema.sql sudah memuat kolom ini.

-- Moderasi ucapan tamu: ucapan yang disembunyikan tidak tampil di website undangan
ALTER TABLE wishes ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER message;