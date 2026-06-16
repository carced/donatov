-- RF Online Next — region-dependent server dropdown
-- Run: mysql -u USER -p DATABASE < database/rf_online_next_server_field.sql

SET NAMES utf8mb4;

DELETE FROM good_fields WHERE good_id = 528 AND field_key = 'server';

INSERT INTO good_fields (
    good_id, field_key, label_ru, field_type, input_type, placeholder, required, validation_json, sort_order
) VALUES (
    528,
    'server',
    'Сервер',
    'select',
    'string',
    'Выберите сервер',
    1,
    '{"id": "rf_online_next_server", "name": "server", "type": "select", "model": "server", "dependsOn": "region", "optionsByParent": {"na": [{"id": "hecate1", "name": "Hecate1[NA]"}, {"id": "hecate2", "name": "Hecate2[NA]"}, {"id": "hecate3", "name": "Hecate3[NA]"}, {"id": "hecate4", "name": "Hecate4[NA]"}], "eu": [{"id": "inanna1", "name": "Inanna1[EU]"}, {"id": "inanna2", "name": "Inanna2[EU]"}, {"id": "inanna3", "name": "Inanna3[EU]"}, {"id": "inanna4", "name": "Inanna4[EU]"}]}, "required": true, "placeholder": "Выберите сервер", "selectOptions": {"hideNoneSelectedText": true}}',
    1
);

UPDATE good_fields SET sort_order = 2 WHERE good_id = 528 AND field_key = 'nickname';
UPDATE good_fields SET sort_order = 3 WHERE good_id = 528 AND field_key = 'email';

INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value) VALUES
    ('good_field', '528:server', 'label', 'en', 'Server')
ON DUPLICATE KEY UPDATE text_value = VALUES(text_value);
