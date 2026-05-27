-- Run once on existing databases: mysql donatov < database/referral_migration.sql

CREATE TABLE IF NOT EXISTS referral_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(16) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    balance_usd DECIMAL(12, 2) NOT NULL DEFAULT 0,
    total_clicks INT UNSIGNED NOT NULL DEFAULT 0,
    total_earned_usd DECIMAL(12, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_referral_code (code)
);

CREATE TABLE IF NOT EXISTS referral_clicks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    visitor_hash VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    amount_usd DECIMAL(12, 2) NOT NULL DEFAULT 0.10,
    click_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_referral_visitor_day (account_id, visitor_hash, click_date),
    FOREIGN KEY (account_id) REFERENCES referral_accounts(id) ON DELETE CASCADE,
    INDEX idx_clicks_account (account_id)
);

-- Add order columns if missing (MySQL 8.0 compatible)
SET @db = DATABASE();
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'referral_account_id') = 0,
    'ALTER TABLE orders ADD COLUMN referral_account_id INT UNSIGNED DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'referral_credit_usd') = 0,
    'ALTER TABLE orders ADD COLUMN referral_credit_usd DECIMAL(12, 2) NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
