GameWiwi.com — update package (2026-06-09)
==========================================

CONTENTS
--------
  gamewiwi-update-2026-06-09.sql   → import into your existing MySQL database
  files/                           → upload over your live site (merge folders)

WHAT'S INCLUDED
---------------
  • RF Online Next game (crystals packs, Region/Nickname/Email checkout fields)
  • RF Online Next pinned to #1 on homepage
  • Game Guides system (WYSIWYG admin editor, multiple articles per game)
  • Starter RF Online Next guide article
  • Select dropdown support on product checkout fields
  • Guides nav link, SEO, sitemap entries

STEP 1 — BACKUP
---------------
  1. Download a full backup of your site files and MySQL database before continuing.

STEP 2 — UPLOAD FILES
---------------------
  Upload everything inside the "files/" folder to your site root, keeping paths:

    files/config/          → config/
    files/src/             → src/
    files/templates/       → templates/
    files/lang/            → lang/
    files/public/          → public/   (or public_html/ if that is your docroot)

  If your host uses public_html as the web root, upload public/* into public_html/
  and upload src/, templates/, lang/, config/ one level above (same as now).

  New files you must upload (not only overwrite):
    - src/GameGuide.php
    - src/GuideRepository.php
    - public/assets/admin-guide-editor.js
    - public/assets/admin-guide-editor.css
    - templates/guide.php
    - templates/guide_hub.php
    - templates/guides_index.php
    - templates/partials/game_guides_section.php
    - templates/partials/guide_cta.php

STEP 3 — IMPORT SQL
-------------------
  In phpMyAdmin: select your database → Import → choose gamewiwi-update-2026-06-09.sql

  Or SSH:
    mysql -u YOUR_USER -p YOUR_DATABASE < gamewiwi-update-2026-06-09.sql

  Note: If goods id 528 is already used by another product, open the SQL file and
  change id 528 to a free id before importing.

STEP 4 — WRITABLE FOLDERS
-------------------------
  Ensure these exist and are writable by PHP (chmod 775 or 777):
    data/
    storage/

STEP 5 — VERIFY
---------------
  • Homepage — RF Online Next appears first
  • https://YOUR-DOMAIN/g/rf-online-next — product page with crystal packs
  • https://YOUR-DOMAIN/guide/rf-online-next — guide hub
  • https://YOUR-DOMAIN/guide/rf-online-next/how-to-buy-crystals — article
  • https://YOUR-DOMAIN/guides — all guides
  • https://YOUR-DOMAIN/admin — Game guides section with visual editor (password in .env)

  Admin guides: pick a game → + New article → write in RU/EN editors → Save.

TROUBLESHOOTING
---------------
  • 500 error after upload: check public/index.php includes GameGuide.php and
    GuideRepository.php (require_once lines near Router.php).
  • Guides not saving: game_guides table missing — re-import SQL.
  • RF Online cover missing: ensure public/assets/placeholder.png exists on server.
