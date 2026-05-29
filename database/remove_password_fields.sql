-- Remove password fields from all listings (optional; app hides them even without this).
DELETE FROM translations
WHERE entity_type = 'good_field'
  AND (entity_id LIKE '%:password' OR entity_id LIKE '%:pass');

DELETE FROM good_fields
WHERE field_key = 'password'
   OR LOWER(label_ru) LIKE '%парол%'
   OR LOWER(label_ru) LIKE '%password%';
