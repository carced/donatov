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

INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock) VALUES
    (528, 52802, 'Crystals 360 💎', 466.20, 5.18, 1),
    (528, 52803, 'Crystals 1200 💎', 1498.50, 16.65, 1),
    (528, 52804, 'Crystals 2000 💎', 2485.80, 27.62, 1),
    (528, 52805, 'Crystals 4000 💎', 4410.00, 49.00, 1),
    (528, 52806, 'Crystals 8000 💎', 8010.00, 89.00, 1)
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
    'server',
    'Сервер',
    'select',
    'string',
    'Выберите сервер',
    1,
    '{"id": "rf_online_next_server", "name": "server", "type": "select", "model": "server", "dependsOn": "region", "optionsByParent": {"na": [{"id": "hecate1", "name": "Hecate1[NA]"}, {"id": "hecate2", "name": "Hecate2[NA]"}, {"id": "hecate3", "name": "Hecate3[NA]"}, {"id": "hecate4", "name": "Hecate4[NA]"}], "eu": [{"id": "inanna1", "name": "Inanna1[EU]"}, {"id": "inanna2", "name": "Inanna2[EU]"}, {"id": "inanna3", "name": "Inanna3[EU]"}, {"id": "inanna4", "name": "Inanna4[EU]"}]}, "required": true, "placeholder": "Выберите сервер", "selectOptions": {"hideNoneSelectedText": true}}',
    1
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
    2
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
    3
);

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value) VALUES
    ('good', '528', 'name', 'en', 'RF Online Next'),
    ('good', '528', 'currency', 'en', 'Crystals'),
    ('good_field', '528:region', 'label', 'en', 'Region'),
    ('good_field', '528:server', 'label', 'en', 'Server'),
    ('good_field', '528:nickname', 'label', 'en', 'Nickname'),
    ('good_field', '528:email', 'label', 'en', 'Email'),
    ('pack', '528:52802', 'name', 'en', 'Crystals 360 💎'),
    ('pack', '528:52803', 'name', 'en', 'Crystals 1200 💎'),
    ('pack', '528:52804', 'name', 'en', 'Crystals 2000 💎'),
    ('pack', '528:52805', 'name', 'en', 'Crystals 4000 💎'),
    ('pack', '528:52806', 'name', 'en', 'Crystals 8000 💎')
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);

UPDATE goods SET sort_order = sort_order + 1 WHERE id != 528 AND sort_order >= 0;
UPDATE goods SET sort_order = 0 WHERE id = 528;
