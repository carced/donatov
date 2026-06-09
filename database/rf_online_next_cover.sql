-- RF Online Next cover image (run on existing installs)
UPDATE goods SET
    cover_url = 'https://assets-prd.ignimgs.com/2023/11/16/rf-online-next-button-1700147130890.jpg',
    cover_path = '/assets/covers/rf-online-next.jpg'
WHERE slug = 'rf-online-next';
