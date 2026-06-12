-- RF Online Next — update crystal packs (remove 160, add 4000 & 8000)
-- Run: mysql -u USER -p DATABASE < database/rf_online_next_packs_update.sql

SET NAMES utf8mb4;

DELETE p FROM packs p
INNER JOIN goods g ON g.id = p.good_id
WHERE g.slug = 'rf-online-next' AND p.source_pack_id = 52801;

DELETE t FROM translations t
INNER JOIN goods g ON g.id = CAST(SUBSTRING_INDEX(t.entity_id, ':', 1) AS UNSIGNED)
WHERE g.slug = 'rf-online-next'
  AND t.entity_type = 'pack'
  AND t.entity_id LIKE CONCAT(g.id, ':52801');

INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock) VALUES
    (528, 52805, 'Crystals 4000 💎', 4410.00, 49.00, 1),
    (528, 52806, 'Crystals 8000 💎', 8010.00, 89.00, 1)
ON DUPLICATE KEY UPDATE
    name_ru = VALUES(name_ru),
    price_rub_source = VALUES(price_rub_source),
    price_usd = VALUES(price_usd),
    in_stock = VALUES(in_stock);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value) VALUES
    ('pack', '528:52805', 'name', 'en', 'Crystals 4000 💎'),
    ('pack', '528:52806', 'name', 'en', 'Crystals 8000 💎')
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);
