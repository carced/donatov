-- RF Online Next cover image (run on existing installs)
UPDATE goods SET
    cover_url = 'https://assets-prd.ignimgs.com/2023/11/16/rf-online-next-button-1700147130890.jpg',
    cover_path = '/assets/covers/good-528-1780975039.webp'
WHERE slug = 'rf-online-next';
