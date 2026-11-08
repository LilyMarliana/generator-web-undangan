USE wedding_gen_db;

  ALTER TABLE users
      ADD COLUMN plan ENUM('free', 'pro') NOT NULL DEFAULT 'free' AFTER is_active,
      ADD COLUMN pro_expires_at DATETIME NULL AFTER plan;

  CREATE TABLE payments (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NOT NULL,
      midtrans_order_id VARCHAR(100) NOT NULL UNIQUE,
      midtrans_transaction_id VARCHAR(100) NULL,
      gross_amount INT UNSIGNED NOT NULL COMMENT 'Whole IDR; Pro is 18000',
      plan VARCHAR(32) NOT NULL,
      status VARCHAR(32) NOT NULL DEFAULT 'pending',
      raw_notification LONGTEXT NULL,
      paid_at DATETIME NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
          ON UPDATE CURRENT_TIMESTAMP,

      CONSTRAINT fk_payments_user
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

      INDEX idx_payments_user (user_id),
      INDEX idx_payments_status (status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
