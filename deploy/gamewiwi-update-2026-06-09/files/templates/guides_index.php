<?php require_once __DIR__ . '/helpers.php'; ?>
<section class="guides-index">
    <header class="guides-index__header">
        <h1><?= e(t('guides_index_heading')) ?></h1>
        <p class="guides-index__lead"><?= e(t('guides_index_lead')) ?></p>
    </header>

    <?php if (empty($items)): ?>
    <p class="text-muted"><?= e(t('guides_index_empty')) ?></p>
    <?php else: ?>
    <div class="guides-index__grid">
        <?php foreach ($items as $item): ?>
        <article class="guide-card card-panel">
            <h2 class="guide-card__title">
                <a href="<?= e($item['articleUrl']) ?>"><?= e($item['resolved']['title']) ?></a>
            </h2>
            <p class="guide-card__game text-muted"><?= e($item['productName']) ?></p>
            <?php if ($item['excerpt'] !== ''): ?>
            <p class="guide-card__excerpt"><?= e($item['excerpt']) ?></p>
            <?php endif; ?>
            <div class="guide-card__actions">
                <a href="<?= e($item['articleUrl']) ?>" class="btn btn-secondary"><?= e(t('guide_read_full')) ?></a>
                <a href="<?= e($item['storeUrl']) ?>" class="btn btn-primary"><?= e(t('guide_cta_buy_short', ['product' => $item['productName']])) ?></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
