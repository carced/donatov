-- Multiple articles per game + article slugs
-- Run: docker compose exec -T mysql mysql -u donatov -pdonatov_secret donatov < database/game_guides_multi_article.sql

SET NAMES utf8mb4;

ALTER TABLE game_guides
    ADD COLUMN article_slug VARCHAR(128) NOT NULL DEFAULT 'main' AFTER good_slug,
    ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER enabled;

UPDATE game_guides SET article_slug = 'how-to-buy-crystals' WHERE article_slug = 'main' AND good_slug = 'rf-online-next';
UPDATE game_guides SET article_slug = CONCAT('article-', id) WHERE article_slug = 'main';

ALTER TABLE game_guides DROP INDEX uk_guide_slug;
ALTER TABLE game_guides ADD UNIQUE KEY uk_guide_article (good_slug, article_slug);
ALTER TABLE game_guides ADD INDEX idx_guide_good (good_slug);
