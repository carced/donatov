Game guides — reduce buy CTAs (advertising fix)
================================================

Shows one buy CTA at the top and one at the bottom of each guide article.
Removes repeated inline "buy crystals" blocks and extra buttons elsewhere.

STEP 1 — UPLOAD FILES
---------------------
Upload these files over your live site, keeping paths:

  files/templates/guide.php
    → templates/guide.php

  files/templates/helpers.php
    → templates/helpers.php

  files/templates/guide_hub.php
    → templates/guide_hub.php

  files/templates/partials/game_guides_section.php
    → templates/partials/game_guides_section.php

No database changes required.

STEP 2 — VERIFY
---------------
  • Open any guide article (e.g. /guide/rf-online-next/how-to-buy-crystals)
  • You should see one buy block above the article and one below
  • No buy blocks between sections inside the article
  • Product page guide teaser has no extra buy banner
