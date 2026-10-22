-- schema untuk wedding_gen_db (MySQL 8+)
-- Jalankan sekali: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS wedding_gen_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wedding_gen_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS prompts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    groom_name VARCHAR(100) DEFAULT NULL,
    bride_name VARCHAR(100) DEFAULT NULL,
    prompt TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_prompts_user (user_id)
);

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
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prompt_id) REFERENCES prompts(id) ON DELETE CASCADE,
    INDEX idx_wishes_prompt (prompt_id)
);

CREATE TABLE IF NOT EXISTS quota_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    count INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, date)
);