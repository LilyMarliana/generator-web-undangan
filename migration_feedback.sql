-- Fitur Masukan Penguji (jalankan sekali di database wedding_gen_db)
USE wedding_gen_db;

CREATE TABLE IF NOT EXISTS feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    category ENUM('bug','saran','tampilan','lainnya') NOT NULL DEFAULT 'saran',
    message TEXT NOT NULL,
    status ENUM('baru','dibaca','ditindaklanjuti') NOT NULL DEFAULT 'baru',
    admin_note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_feedback_status (status),
    INDEX idx_feedback_user (user_id)
);