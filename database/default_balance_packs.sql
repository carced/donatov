-- Default balance packs for goods with no listings (run once if needed).
-- Application also auto-creates these on first product page view.

INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock)
SELECT g.id, 90001, 'Баланс 25$ за 15$', 15.00, 15.00, 1
FROM goods g
WHERE g.enabled = 1
  AND NOT EXISTS (SELECT 1 FROM packs p WHERE p.good_id = g.id)
ON DUPLICATE KEY UPDATE name_ru = VALUES(name_ru);

-- Note: uk_good_pack (good_id, source_pack_id) — run per-tier via app or separate inserts.
