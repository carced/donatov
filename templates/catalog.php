<?php require_once __DIR__ . '/helpers.php'; ?>
<header class="page-header">
    <h1><?= e(t('catalog_title')) ?></h1>
    <p class="page-intro text-muted"><?= e(t('seo_catalog_intro')) ?></p>
</header>
<?php if ($fx): ?>
<p class="fx-badge"><?= e(t('fx_note')) ?>: <?= e($fx['rate_date']) ?></p>
<?php endif; ?>
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
<div class="catalog-grid">
<?php foreach ($goods as $g): ?>
    <a href="/g/<?= e($g['slug']) ?>" class="good-card" title="<?= e(t('seo_buy_title', ['product' => good_name($g)])) ?>">
        <img src="<?= e(cover_src($g)) ?>" alt="<?= e(t('seo_product_image_alt', ['product' => good_name($g)])) ?>" loading="lazy" decoding="async" width="180" height="180">
        <div class="good-card-body">
            <h2 class="good-card__title"><?= e(good_name($g)) ?></h2>
            <?php if ($g['currency_name_ru']): ?>
                <small><?= e(trans_entity('good', (string)$g['id'], 'currency', $g['currency_name_ru'])) ?></small>
            <?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
</div>
