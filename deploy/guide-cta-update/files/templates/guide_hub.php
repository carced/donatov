<?php require_once __DIR__ . '/helpers.php'; ?>
<section class="guide-hub">
    <header class="guide-hub__header">
        <h1><?= e(t('guide_hub_title', ['product' => $productName])) ?></h1>
        <p class="guide-hub__lead"><?= e(t('guide_hub_lead', ['product' => $productName])) ?></p>
    </header>

    <div class="guides-index__grid">
        <?php foreach ($articles as $item): ?>
        <article class="guide-card card-panel">
            <h2 class="guide-card__title">
                <a href="<?= e($item['articleUrl']) ?>"><?= e($item['resolved']['title']) ?></a>
            </h2>
            <?php if ($item['excerpt'] !== ''): ?>
            <p class="guide-card__excerpt"><?= e($item['excerpt']) ?></p>
            <?php endif; ?>
            <div class="guide-card__actions">
                <a href="<?= e($item['articleUrl']) ?>" class="btn btn-secondary"><?= e(t('guide_read_full')) ?></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
