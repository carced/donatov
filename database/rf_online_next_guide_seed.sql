-- Optional starter content for RF Online Next guide (edit anytime in /admin)
INSERT INTO game_guides (
    good_slug, title_ru, title_en, content_ru, content_en,
    meta_description_ru, meta_description_en, enabled
) VALUES (
    'rf-online-next',
    'Гайд RF Online Next — как купить кристаллы',
    'RF Online Next Guide — How to Buy Crystals',
    '<h2>Что такое кристаллы RF Online Next</h2><p>Кристаллы — премиальная валюта RF Online Next. На GameWiwi вы можете купить паки от 160 до 2000 кристаллов с оплатой криптовалютой.</p><h2>Как оформить заказ</h2><p>Выберите пак на странице игры, укажите регион (Северная Америка или Европа), никнейм и email, затем оплатите удобной монетой.</p><h2>Регионы и аккаунт</h2><p>Убедитесь, что выбран правильный регион сервера и никнейм совпадает с персонажем в игре — так доставка проходит быстрее.</p>',
    '<h2>What are RF Online Next crystals</h2><p>Crystals are the premium currency in RF Online Next. On GameWiwi you can buy packs from 160 to 2000 crystals with cryptocurrency checkout.</p><h2>How to order</h2><p>Pick a pack on the store page, enter your region (North America or Europe), nickname, and email, then pay with your preferred coin.</p><h2>Regions and account</h2><p>Double-check your server region and in-game nickname so delivery is fast and accurate.</p>',
    'Гайд по покупке кристаллов RF Online Next: паки, регионы, никнейм и оплата криптой на GameWiwi.',
    'RF Online Next crystals guide: packs, regions, nickname, and crypto checkout on GameWiwi.',
    1
) ON DUPLICATE KEY UPDATE
    title_ru = VALUES(title_ru),
    title_en = VALUES(title_en),
    content_ru = VALUES(content_ru),
    content_en = VALUES(content_en),
    meta_description_ru = VALUES(meta_description_ru),
    meta_description_en = VALUES(meta_description_en),
    enabled = VALUES(enabled);
