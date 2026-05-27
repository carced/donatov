<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('catalog_title')) ?></h1>
<?php if ($fx): ?>
<p class="fx-badge"><?= e(t('fx_note')) ?>: <?= e($fx['rate_date']) ?></p>
<?php endif; ?>
<div class="category-tabs">
    <a href="/catalog" class="<?= !$categoryId ? 'active' : '' ?>"><?= e(t('all_categories')) ?></a>
    <?php foreach ($categories as $cat): ?>
        <a href="/catalog?category=<?= e($cat['id']) ?>"
           class="<?= ($categoryId ?? '') === $cat['id'] ? 'active' : '' ?>">
            <?= e(trans_entity('category', $cat['id'], 'name', $cat['name_ru'])) ?>
        </a>
    <?php endforeach; ?>
</div>
<div class="catalog-grid">
<?php foreach ($goods as $g): ?>
    <a href="/g/<?= e($g['slug']) ?>" class="good-card">
        <img src="<?= e(cover_src($g)) ?>" alt="<?= e(good_name($g)) ?>" loading="lazy">
        <div class="good-card-body">
            <h3><?= e(good_name($g)) ?></h3>
            <?php if ($g['currency_name_ru']): ?>
                <small><?= e(trans_entity('good', (string)$g['id'], 'currency', $g['currency_name_ru'])) ?></small>
            <?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
</div>
