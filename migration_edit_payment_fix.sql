-- Perubahan skema untuk fitur edit lengkap dan perbaikan pembayaran (aman dijalankan berulang, MariaDB 10.2+)

-- Menyimpan isi form wizard supaya undangan bisa diedit penuh
ALTER TABLE prompts ADD COLUMN IF NOT EXISTS form_data MEDIUMTEXT NULL;

-- Status langganan pengguna
ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_status VARCHAR(20) NOT NULL DEFAULT 'inactive';

-- Kolom yang dipakai checkout.php dan webhook Midtrans
ALTER TABLE payments ADD COLUMN IF NOT EXISTS amount INT NOT NULL DEFAULT 0;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS payload LONGTEXT NULL;
