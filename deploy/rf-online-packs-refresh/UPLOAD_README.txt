RF Online Next — new pack listings + reviews (Jun 2026)
=========================================================

Replaces ALL RF Online Next packs with:

  • 10900 Diamonds 💎 — $35
  • 3570 Diamonds 💎 — $15
  • Monthly Summon Ticket Pack — $15
  • Weekly Summon Ticket Pack — $15
  • Monthly Artifact Package — $15

Also updates product page reviews: 19 reviews dated 2026-06-17 … 2026-06-23.

STEP 1 — UPLOAD
---------------
  files/src/ProductReviews.php → src/ProductReviews.php

STEP 2 — IMPORT SQL
-------------------
  rf-online-next-packs-refresh.sql

  phpMyAdmin → Import, or:
    mysql -u USER -p DATABASE < rf-online-next-packs-refresh.sql

STEP 3 — VERIFY
---------------
  • /g/rf-online-next — only 5 packs listed at new prices
  • Reviews section shows 19 reviews with June 17–23 dates
