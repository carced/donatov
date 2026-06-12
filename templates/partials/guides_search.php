<?php require_once dirname(__DIR__) . '/helpers.php'; ?>
<div class="guides-search">
    <div class="guides-search__wrap">
        <label for="guide-search" class="visually-hidden"><?= e(t('search_guides_label')) ?></label>
        <input type="search"
               id="guide-search"
               class="guides-search__input form-control"
               placeholder="<?= e(t('search_guides_placeholder')) ?>"
               autocomplete="off"
               spellcheck="false"
               enterkeyhint="search">
    </div>
    <p id="guide-search-empty" class="guides-search__empty" hidden><?= e(t('search_guides_no_results')) ?></p>
</div>
