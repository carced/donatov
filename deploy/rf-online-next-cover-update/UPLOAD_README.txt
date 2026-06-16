RF Online Next — cover image only (game card size)
====================================================

Fixes the RF Online Next logo so it matches other games on the homepage cards
(400×400 WebP, same as the rest of the catalog).

STEP 1 — UPLOAD IMAGE
---------------------
Upload this file to your site, keeping the path:

  files/public/assets/covers/good-528-1780975039.webp
    → public/assets/covers/good-528-1780975039.webp

(Use public_html/assets/covers/ if that is your web root.)

STEP 2 — UPDATE DATABASE (optional but recommended)
---------------------------------------------------
Import rf-online-next-cover.sql so the site uses the new file path.

  phpMyAdmin → your database → Import → rf-online-next-cover.sql

  Or SSH:
    mysql -u YOUR_USER -p YOUR_DATABASE < rf-online-next-cover.sql

If you already ran the full gamewiwi-update SQL with cover_path
/assets/covers/good-528-1780975039.webp, you only need STEP 1.

STEP 3 — VERIFY
---------------
  • Homepage — RF Online Next card shows the logo at the same size as other games
  • Hard-refresh the page (Ctrl+F5) if you still see the old image
