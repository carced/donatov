<?php require_once __DIR__ . '/helpers.php'; ?>
<header class="page-header">
    <h1><?= e(t('catalog_title')) ?></h1>
    <p class="page-intro text-muted"><?= e(t('seo_catalog_intro')) ?></p>
    <p class="referral-catalog-hint">
        <a href="/referral" class="btn btn-sm btn-free-link" data-referral-slide><?= e(t('btn_free')) ?></a>
        <?= e(t('referral_free_banner')) ?>
    </p>
</header>
<nav class="category-tabs" aria-label="<?= e(t('seo_category_nav')) ?>">
    <a href="/catalog" class="<?= !$categoryId ? 'active' : '' ?>"><?= e(t('all_categories')) ?></a>
    <?php foreach ($categories as $cat): ?>
        <a href="/catalog?category=<?= e($cat['id']) ?>"
           class="<?= ($categoryId ?? '') === $cat['id'] ? 'active' : '' ?>"
           title="<?= e(t('seo_link_category_title', ['category' => trans_entity('category', $cat['id'], 'name', $cat['name_ru'])])) ?>">
            <?= e(trans_entity('category', $cat['id'], 'name', $cat['name_ru'])) ?>
        </a>
    <?php endforeach; ?>
</nav>
<?php include __DIR__ . '/partials/catalog_search.php'; ?>
<div class="catalog-grid catalog-grid--cards" data-catalog-grid>
<?php foreach ($goods as $g): ?>
    <?php include __DIR__ . '/partials/good_card.php'; ?>
<?php endforeach; ?>
</div>
