-- =============================================================================
-- GameWiwi.com — full update (2026-06-09)
-- Import in phpMyAdmin or: mysql -u USER -p DATABASE < gamewiwi-update-2026-06-09.sql
--
-- Includes:
--   1. game_guides table (guides system, multiple articles per game)
--   2. RF Online Next product, packs, checkout fields, EN translations
--   3. RF Online Next starter guide article
--   4. Homepage #1 sort order for RF Online Next
--
-- Safe to re-run: uses IF NOT EXISTS / ON DUPLICATE KEY UPDATE where possible.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. GAME GUIDES TABLE (create new, or upgrade old single-article version)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS game_guides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    good_slug VARCHAR(255) NOT NULL,
    article_slug VARCHAR(128) NOT NULL DEFAULT 'main',
    title_ru VARCHAR(512) DEFAULT NULL,
    title_en VARCHAR(512) DEFAULT NULL,
    content_ru MEDIUMTEXT DEFAULT NULL,
    content_en MEDIUMTEXT DEFAULT NULL,
    meta_description_ru TEXT DEFAULT NULL,
    meta_description_en TEXT DEFAULT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_guide_article (good_slug, article_slug),
    INDEX idx_guide_enabled (enabled),
    INDEX idx_guide_good (good_slug)
);

-- Upgrade v1 guides table (one row per game, no article_slug) if present
SET @has_article_slug = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_guides' AND COLUMN_NAME = 'article_slug'
);

SET @sql_add_cols = IF(
    @has_article_slug = 0,
    'ALTER TABLE game_guides
        ADD COLUMN article_slug VARCHAR(128) NOT NULL DEFAULT ''main'' AFTER good_slug,
        ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER enabled',
    'SELECT 1'
);
PREPARE stmt FROM @sql_add_cols;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE game_guides SET article_slug = 'how-to-buy-crystals'
WHERE article_slug = 'main' AND good_slug = 'rf-online-next';

UPDATE game_guides SET article_slug = CONCAT('article-', id)
WHERE article_slug = 'main';

SET @has_old_uk = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_guides' AND INDEX_NAME = 'uk_guide_slug'
);

SET @sql_drop_uk = IF(
    @has_old_uk > 0,
    'ALTER TABLE game_guides DROP INDEX uk_guide_slug',
    'SELECT 1'
);
PREPARE stmt FROM @sql_drop_uk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_new_uk = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_guides' AND INDEX_NAME = 'uk_guide_article'
);

SET @sql_add_uk = IF(
    @has_new_uk = 0,
    'ALTER TABLE game_guides ADD UNIQUE KEY uk_guide_article (good_slug, article_slug)',
    'SELECT 1'
);
PREPARE stmt FROM @sql_add_uk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_good_idx = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_guides' AND INDEX_NAME = 'idx_guide_good'
);

SET @sql_add_idx = IF(
    @has_good_idx = 0,
    'ALTER TABLE game_guides ADD INDEX idx_guide_good (good_slug)',
    'SELECT 1'
);
PREPARE stmt FROM @sql_add_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2. RF ONLINE NEXT — product listing
-- -----------------------------------------------------------------------------

INSERT INTO goods (
    id, slug, name_ru, category_id, type, cover_url, cover_path,
    currency_name_ru, instant, enabled, region, cashback, meta_json, sort_order
) VALUES (
    528,
    'rf-online-next',
    'RF Online Next',
    'games',
    'pack',
    'https://assets-prd.ignimgs.com/2023/11/16/rf-online-next-button-1700147130890.jpg',
    '/assets/covers/good-528-1780975039.webp',
    'Кристаллы',
    1,
    1,
    NULL,
    1.00,
    '{"source_url": "/rf-online-next"}',
    0
) ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    name_ru = VALUES(name_ru),
    cover_path = VALUES(cover_path),
    currency_name_ru = VALUES(currency_name_ru),
    enabled = VALUES(enabled),
    sort_order = VALUES(sort_order);

INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock)
SELECT g.id, v.source_pack_id, v.name_ru, v.price_rub_source, v.price_usd, 1
FROM goods g
CROSS JOIN (
    SELECT 52801 AS source_pack_id, 'Crystals 160 💎' AS name_ru, 201.60 AS price_rub_source, 2.24 AS price_usd
    UNION ALL SELECT 52802, 'Crystals 360 💎', 466.20, 5.18
    UNION ALL SELECT 52803, 'Crystals 1200 💎', 1498.50, 16.65
    UNION ALL SELECT 52804, 'Crystals 2000 💎', 2485.80, 27.62
) v
WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE
    name_ru = VALUES(name_ru),
    price_rub_source = VALUES(price_rub_source),
    price_usd = VALUES(price_usd),
    in_stock = VALUES(in_stock);

DELETE gf FROM good_fields gf
INNER JOIN goods g ON g.id = gf.good_id
WHERE g.slug = 'rf-online-next';

INSERT INTO good_fields (
    good_id, field_key, label_ru, field_type, input_type, placeholder, required, validation_json, sort_order
)
SELECT g.id, 'region', 'Регион', 'select', 'string', 'Выберите регион', 1,
    '{"id": "rf_online_next_region", "name": "region", "type": "select", "model": "region", "values": [{"id": "na", "name": "North America"}, {"id": "eu", "name": "Europe"}], "required": true, "placeholder": "Выберите регион", "selectOptions": {"hideNoneSelectedText": true}}',
    0
FROM goods g WHERE g.slug = 'rf-online-next';

INSERT INTO good_fields (
    good_id, field_key, label_ru, field_type, input_type, placeholder, required, validation_json, sort_order
)
SELECT g.id, 'nickname', 'Никнейм', 'input', 'string', 'Ваш никнейм', 1,
    '{"id": "rf_online_next_nickname", "name": "nickname", "type": "input", "model": "nickname", "required": true, "inputType": "string", "placeholder": "Ваш никнейм", "autocomplete": "off"}',
    1
FROM goods g WHERE g.slug = 'rf-online-next';

INSERT INTO good_fields (
    good_id, field_key, label_ru, field_type, input_type, placeholder, required, validation_json, sort_order
)
SELECT g.id, 'email', 'Email', 'input', 'email', 'example@gmail.com', 1,
    '{"id": "rf_online_next_email", "name": "email", "type": "input", "model": "email", "required": true, "inputType": "email", "placeholder": "example@gmail.com", "autocomplete": "email"}',
    2
FROM goods g WHERE g.slug = 'rf-online-next';

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'good', CAST(g.id AS CHAR), 'name', 'en', 'RF Online Next'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'good', CAST(g.id AS CHAR), 'currency', 'en', 'Crystals'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'good_field', CONCAT(g.id, ':region'), 'label', 'en', 'Region'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'good_field', CONCAT(g.id, ':nickname'), 'label', 'en', 'Nickname'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'good_field', CONCAT(g.id, ':email'), 'label', 'en', 'Email'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'pack', CONCAT(g.id, ':52801'), 'name', 'en', 'Crystals 160 💎'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'pack', CONCAT(g.id, ':52802'), 'name', 'en', 'Crystals 360 💎'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'pack', CONCAT(g.id, ':52803'), 'name', 'en', 'Crystals 1200 💎'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value)
SELECT 'pack', CONCAT(g.id, ':52804'), 'name', 'en', 'Crystals 2000 💎'
FROM goods g WHERE g.slug = 'rf-online-next'
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

-- Pin RF Online Next to #1 on homepage (sort_order)
UPDATE goods SET sort_order = sort_order + 1
WHERE slug != 'rf-online-next' AND sort_order >= 0;

UPDATE goods SET sort_order = 0 WHERE slug = 'rf-online-next';

-- -----------------------------------------------------------------------------
-- 3. RF ONLINE NEXT — starter guide (edit in /admin after upload)
-- -----------------------------------------------------------------------------

INSERT INTO game_guides (
    good_slug, article_slug, title_ru, title_en, content_ru, content_en,
    meta_description_ru, meta_description_en, enabled, sort_order
) VALUES (
    'rf-online-next',
    'how-to-buy-crystals',
    'Гайд RF Online Next — как купить кристаллы',
    'RF Online Next Guide — How to Buy Crystals',
    '<h2>Что такое кристаллы RF Online Next</h2><p>Кристаллы — премиальная валюта RF Online Next. На GameWiwi вы можете купить паки от 160 до 2000 кристаллов с оплатой криптовалютой.</p><h2>Как оформить заказ</h2><p>Выберите пак на странице игры, укажите регион (Северная Америка или Европа), никнейм и email, затем оплатите удобной монетой.</p><h2>Регионы и аккаунт</h2><p>Убедитесь, что выбран правильный регион сервера и никнейм совпадает с персонажем в игре — так доставка проходит быстрее.</p>',
    '<h2>What are RF Online Next crystals</h2><p>Crystals are the premium currency in RF Online Next. On GameWiwi you can buy packs from 160 to 2000 crystals with cryptocurrency checkout.</p><h2>How to order</h2><p>Pick a pack on the store page, enter your region (North America or Europe), nickname, and email, then pay with your preferred coin.</p><h2>Regions and account</h2><p>Double-check your server region and in-game nickname so delivery is fast and accurate.</p>',
    'Гайд по покупке кристаллов RF Online Next: паки, регионы, никнейм и оплата криптой на GameWiwi.',
    'RF Online Next crystals guide: packs, regions, nickname, and crypto checkout on GameWiwi.',
    1,
    0
) ON DUPLICATE KEY UPDATE
    title_ru = VALUES(title_ru),
    title_en = VALUES(title_en),
    content_ru = VALUES(content_ru),
    content_en = VALUES(content_en),
    meta_description_ru = VALUES(meta_description_ru),
    meta_description_en = VALUES(meta_description_en),
    enabled = VALUES(enabled),
    sort_order = VALUES(sort_order);

SET FOREIGN_KEY_CHECKS = 1;
