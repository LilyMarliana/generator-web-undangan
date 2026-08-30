-- migration untuk menambahkan API dan RSVP/Wishes
-- Jalankan setelah install awal: mysql -u root wedding_gen_db < migration_api.sql

USE wedding_gen_db;

ALTER TABLE prompts 
ADD COLUMN api_key VARCHAR(64) NOT NULL UNIQUE AFTER id,
ADD COLUMN groom_name VARCHAR(100) DEFAULT NULL AFTER api_key,
ADD COLUMN bride_name VARCHAR(100) DEFAULT NULL AFTER groom_name;

CREATE TABLE IF NOT EXISTS rsvps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prompt_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    attending ENUM('hadir','tidak_hadir','belum_pasti') DEFAULT 'belum_pasti',
    guests INT UNSIGNED DEFAULT 1,
    message TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prompt_id) REFERENCES prompts(id) ON DELETE CASCADE,
    INDEX idx_rsvps_prompt (prompt_id)
);

CREATE TABLE IF NOT EXISTS wishes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prompt_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prompt_id) REFERENCES prompts(id) ON DELETE CASCADE,
    INDEX idx_wishes_prompt (prompt_id)
);

-- Update existing prompts dengan api_key (64 hex chars dari MD5 ganda)
UPDATE prompts SET api_key = CONCAT(MD5(CONCAT(id, RAND(), NOW())), MD5(CONCAT(RAND(), id, NOW()))) WHERE api_key = '';