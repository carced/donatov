-- RF Online Next — manual catalog entry (crystals + checkout fields)
-- Run: docker compose exec -T mysql mysql -u donatov -pdonatov_secret donatov < database/rf_online_next.sql

SET NAMES utf8mb4;

INSERT INTO goods (
    id, slug, name_ru, category_id, type, cover_url, cover_path,
    currency_name_ru, instant, enabled, region, cashback, meta_json, sort_order
) VALUES (
    528,
    'rf-online-next',
    'RF Online Next',
    'games',
    'pack',
    NULL,
    '/assets/placeholder.png',
    'Кристаллы',
    1,
    1,
    NULL,
    1.00,
    '{"source_url": "/rf-online-next"}',
    2
) ON DUPLICATE KEY UPDATE
    slug = VALUES(slug),
    name_ru = VALUES(name_ru),
    cover_path = VALUES(cover_path),
    currency_name_ru = VALUES(currency_name_ru),
    enabled = VALUES(enabled),
    sort_order = VALUES(sort_order);

INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock) VALUES
    (528, 52801, 'Crystals 160 💎', 201.60, 2.24, 1),
    (528, 52802, 'Crystals 360 💎', 466.20, 5.18, 1),
    (528, 52803, 'Crystals 1200 💎', 1498.50, 16.65, 1),
    (528, 52804, 'Crystals 2000 💎', 2485.80, 27.62, 1)
ON DUPLICATE KEY UPDATE
    name_ru = VALUES(name_ru),
    price_rub_source = VALUES(price_rub_source),
    price_usd = VALUES(price_usd),
    in_stock = VALUES(in_stock);

DELETE FROM good_fields WHERE good_id = 528;

INSERT INTO good_fields (
    good_id, field_key, label_ru, field_type, input_type, placeholder, required, validation_json, sort_order
) VALUES
(
    528,
    'region',
    'Регион',
    'select',
    'string',
    'Выберите регион',
    1,
    '{"id": "rf_online_next_region", "name": "region", "type": "select", "model": "region", "values": [{"id": "na", "name": "North America"}, {"id": "eu", "name": "Europe"}], "required": true, "placeholder": "Выберите регион", "selectOptions": {"hideNoneSelectedText": true}}',
    0
),
(
    528,
    'nickname',
    'Никнейм',
    'input',
    'string',
    'Ваш никнейм',
    1,
    '{"id": "rf_online_next_nickname", "name": "nickname", "type": "input", "model": "nickname", "required": true, "inputType": "string", "placeholder": "Ваш никнейм", "autocomplete": "off"}',
    1
),
(
    528,
    'email',
    'Email',
    'input',
    'email',
    'example@gmail.com',
    1,
    '{"id": "rf_online_next_email", "name": "email", "type": "input", "model": "email", "required": true, "inputType": "email", "placeholder": "example@gmail.com", "autocomplete": "email"}',
    2
);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value) VALUES
    ('good', '528', 'name', 'en', 'RF Online Next'),
    ('good', '528', 'currency', 'en', 'Crystals'),
    ('good_field', '528:region', 'label', 'en', 'Region'),
    ('good_field', '528:nickname', 'label', 'en', 'Nickname'),
    ('good_field', '528:email', 'label', 'en', 'Email'),
    ('pack', '528:52801', 'name', 'en', 'Crystals 160 💎'),
    ('pack', '528:52802', 'name', 'en', 'Crystals 360 💎'),
    ('pack', '528:52803', 'name', 'en', 'Crystals 1200 💎'),
    ('pack', '528:52804', 'name', 'en', 'Crystals 2000 💎')
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);
