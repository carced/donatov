<?php require_once dirname(__DIR__) . '/helpers.php'; ?>
<div class="catalog-search">
    <div class="catalog-search__wrap">
        <label for="game-search" class="visually-hidden"><?= e(t('search_games_label')) ?></label>
        <input type="search"
               id="game-search"
               class="catalog-search__input form-control"
               placeholder="<?= e(t('search_games_placeholder')) ?>"
               autocomplete="off"
               spellcheck="false"
               enterkeyhint="search">
    </div>
    <p id="game-search-empty" class="catalog-search__empty" hidden><?= e(t('search_no_results')) ?></p>
</div>
