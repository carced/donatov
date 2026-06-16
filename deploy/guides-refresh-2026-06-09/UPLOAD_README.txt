GameWiwi — guides refresh + RF Online pack update
=================================================

CONTENTS (same folder as this file after unzip)
-----------------------------------------------
  guides-refresh-2026-06-09.sql  ← IMPORT THIS in phpMyAdmin / MySQL
  files/                         ← upload over your live site
  UPLOAD_README.txt              ← this file

WHAT'S INCLUDED
---------------
  • Guide quick search (type to filter/sort)
  • Modern guide cards on /guides, hub, and product pages
  • Guide SEO copy focused on tips/walkthroughs (not sales)
  • RF Online Next: remove Crystals 160, add 4000 ($49) and 8000 ($89)

STEP 1 — BACKUP
---------------
  Backup site files and database first.

STEP 2 — UPLOAD FILES
---------------------
  Merge files/ into your site root (same paths as in the zip).

STEP 3 — IMPORT SQL
-------------------
  The SQL file is in this folder (NOT inside files/):

    guides-refresh-2026-06-09.sql

  phpMyAdmin → your database → Import → choose that file

  Or SSH (from the folder where you extracted the zip):
    mysql -u USER -p DATABASE < guides-refresh-2026-06-09.sql

STEP 4 — VERIFY
---------------
  • /guides — search box filters cards as you type
  • /g/rf-online-next — no 160 pack; 4000 and 8000 packs visible
  • Guide pages — no price banner in header; informational SEO titles
