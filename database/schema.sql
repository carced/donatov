SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(64) PRIMARY KEY,
    `value` TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (`key`, `value`) VALUES
    ('discount_factor', '0.6'),
    ('default_lang', 'ru'),
    ('site_name', 'GameWiwi.com')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(32) PRIMARY KEY,
    name_ru VARCHAR(255) NOT NULL,
    icon VARCHAR(64) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tags (
    id VARCHAR(64) NOT NULL,
    category_id VARCHAR(32) NOT NULL,
    name_ru VARCHAR(255) NOT NULL,
    icon VARCHAR(64) DEFAULT NULL,
    PRIMARY KEY (category_id, id),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS fx_rates (
    rate_date DATE PRIMARY KEY,
    usd_rub DECIMAL(12, 6) NOT NULL,
    source VARCHAR(32) NOT NULL DEFAULT 'cbr',
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS goods (
    id INT UNSIGNED PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    name_ru VARCHAR(512) NOT NULL,
    category_id VARCHAR(32) NOT NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'pack',
    cover_url TEXT,
    cover_path VARCHAR(512) DEFAULT NULL,
    currency_name_ru VARCHAR(128) DEFAULT NULL,
    instant TINYINT(1) NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    region VARCHAR(64) DEFAULT NULL,
    cashback DECIMAL(8, 2) DEFAULT NULL,
    meta_json JSON DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS good_tags (
    good_id INT UNSIGNED NOT NULL,
    tag_id VARCHAR(64) NOT NULL,
    category_id VARCHAR(32) NOT NULL,
    PRIMARY KEY (good_id, category_id, tag_id),
    FOREIGN KEY (good_id) REFERENCES goods(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id, tag_id) REFERENCES tags(category_id, id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pack_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    good_id INT UNSIGNED NOT NULL,
    source_group_id INT NOT NULL,
    name_ru VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uk_good_group (good_id, source_group_id),
    FOREIGN KEY (good_id) REFERENCES goods(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS packs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    good_id INT UNSIGNED NOT NULL,
    source_pack_id INT UNSIGNED NOT NULL,
    name_ru VARCHAR(512) NOT NULL,
    group_id INT UNSIGNED DEFAULT NULL,
    price_rub_source DECIMAL(12, 2) NOT NULL,
    price_old_rub DECIMAL(12, 2) DEFAULT NULL,
    price_usd DECIMAL(12, 2) NOT NULL DEFAULT 0,
    cover_url TEXT,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    expired_at DATETIME DEFAULT NULL,
    UNIQUE KEY uk_good_pack (good_id, source_pack_id),
    FOREIGN KEY (good_id) REFERENCES goods(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES pack_groups(id) ON DELETE SET NULL,
    INDEX idx_packs_good (good_id),
    INDEX idx_packs_stock (in_stock)
);

CREATE TABLE IF NOT EXISTS good_content (
    good_id INT UNSIGNED PRIMARY KEY,
    short_description TEXT,
    description_html MEDIUMTEXT,
    instruction_html MEDIUMTEXT,
    warning_text TEXT,
    promo_text TEXT,
    uid_help TEXT,
    meta_title VARCHAR(512) DEFAULT NULL,
    meta_description TEXT,
    advantages_json JSON DEFAULT NULL,
    FOREIGN KEY (good_id) REFERENCES goods(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS good_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    good_id INT UNSIGNED NOT NULL,
    field_key VARCHAR(64) NOT NULL,
    label_ru VARCHAR(255) NOT NULL,
    field_type VARCHAR(32) NOT NULL DEFAULT 'input',
    input_type VARCHAR(32) DEFAULT 'string',
    placeholder VARCHAR(255) DEFAULT NULL,
    required TINYINT(1) NOT NULL DEFAULT 1,
    validation_json JSON DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uk_good_field (good_id, field_key),
    FOREIGN KEY (good_id) REFERENCES goods(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(32) NOT NULL,
    entity_id VARCHAR(64) NOT NULL,
    field_name VARCHAR(64) NOT NULL,
    lang CHAR(2) NOT NULL,
    text_value MEDIUMTEXT NOT NULL,
    source_hash CHAR(64) DEFAULT NULL,
    UNIQUE KEY uk_translation (entity_type, entity_id, field_name, lang),
    INDEX idx_entity (entity_type, entity_id)
);

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT UNSIGNED PRIMARY KEY,
    name_ru VARCHAR(255) NOT NULL,
    cover_data MEDIUMTEXT,
    fee DECIMAL(8, 2) DEFAULT NULL,
    meta_json JSON DEFAULT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(32) NOT NULL UNIQUE,
    status ENUM('pending', 'paid', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    lang CHAR(2) NOT NULL DEFAULT 'ru',
    total_usd DECIMAL(12, 2) NOT NULL,
    payment_method_id INT UNSIGNED DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    referral_account_id INT UNSIGNED DEFAULT NULL,
    referral_credit_usd DECIMAL(12, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
    INDEX idx_orders_status (status),
    INDEX idx_orders_created (created_at)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    good_id INT UNSIGNED NOT NULL,
    pack_id INT UNSIGNED NOT NULL,
    pack_name_ru VARCHAR(512) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price_usd DECIMAL(12, 2) NOT NULL,
    line_total_usd DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (good_id) REFERENCES goods(id),
    FOREIGN KEY (pack_id) REFERENCES packs(id)
);

CREATE TABLE IF NOT EXISTS order_field_values (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    good_id INT UNSIGNED NOT NULL,
    field_key VARCHAR(64) NOT NULL,
    field_value TEXT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (good_id) REFERENCES goods(id)
);



CREATE TABLE IF NOT EXISTS referral_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(16) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
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

SET FOREIGN_KEY_CHECKS = 1;
