-- RF Online Next — replace all packs with new listings (Jun 2026)
-- Run: mysql -u USER -p DATABASE < database/rf_online_next_packs_refresh.sql

SET NAMES utf8mb4;

UPDATE goods SET currency_name_ru = 'Алмазы' WHERE slug = 'rf-online-next';

DELETE FROM translations WHERE entity_type = 'pack' AND entity_id LIKE '528:%';

DELETE p FROM packs p
INNER JOIN goods g ON g.id = p.good_id
WHERE g.slug = 'rf-online-next';

INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock) VALUES
    (528, 52811, '10900 Алмазов 💎', 3150.00, 35.00, 1),
    (528, 52812, '3570 Алмазов 💎', 1350.00, 15.00, 1),
    (528, 52813, 'Monthly Summon Ticket Pack 🎫', 1350.00, 15.00, 1),
    (528, 52814, 'Weekly Summon Ticket Pack 🎫', 1350.00, 15.00, 1),
    (528, 52815, 'Monthly Artifact Package 📦', 1350.00, 15.00, 1);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value) VALUES
    ('good', '528', 'currency', 'en', 'Diamonds'),
    ('pack', '528:52811', 'name', 'en', '10900 Diamonds 💎'),
    ('pack', '528:52812', 'name', 'en', '3570 Diamonds 💎'),
    ('pack', '528:52813', 'name', 'en', 'Monthly Summon Ticket Pack 🎫'),
    ('pack', '528:52814', 'name', 'en', 'Weekly Summon Ticket Pack 🎫'),
    ('pack', '528:52815', 'name', 'en', 'Monthly Artifact Package 📦')
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);
