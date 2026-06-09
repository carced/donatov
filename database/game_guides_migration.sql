-- Game guides (bilingual articles linked to goods.slug)
-- Run: docker compose exec -T mysql mysql -u donatov -pdonatov_secret donatov < database/game_guides_migration.sql

SET NAMES utf8mb4;

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
