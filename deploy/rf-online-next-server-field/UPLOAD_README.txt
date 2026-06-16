RF Online Next — server selection by region
=============================================

Adds a Server dropdown on /g/rf-online-next that updates when Region changes.

  North America → Hecate1[NA] … Hecate4[NA]
  Europe        → Inanna1[EU] … Inanna4[EU]

STEP 1 — UPLOAD FILES
---------------------
  files/templates/good.php       → templates/good.php
  files/templates/helpers.php    → templates/helpers.php
  files/public/assets/good-buy.js → public/assets/good-buy.js
  files/lang/en.json             → lang/en.json
  files/lang/ru.json             → lang/ru.json

STEP 2 — IMPORT SQL
-------------------
  rf-online-next-server-field.sql

  phpMyAdmin → Import, or:
    mysql -u USER -p DATABASE < rf-online-next-server-field.sql

STEP 3 — VERIFY
---------------
  Open /g/rf-online-next → Buy → pick Region → Server list updates.
