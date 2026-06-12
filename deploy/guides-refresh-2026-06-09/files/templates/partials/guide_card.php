<?php
/** @var string $articleUrl */
/** @var string $title */
/** @var string $excerpt */
/** @var string $searchHaystack */
/** @var string|null $productName */
/** @var bool $showGameBadge */
$showGameBadge = $showGameBadge ?? ($productName ?? '') !== '';
$excerpt = $excerpt ?? '';
?>
<article class="guide-card"
         data-guide-search="<?= e($searchHaystack) ?>"
         data-guide-title="<?= e($title) ?>">
    <?php if ($showGameBadge && ($productName ?? '') !== ''): ?>
    <span class="guide-card__badge"><?= e($productName) ?></span>
    <?php endif; ?>
    <h2 class="guide-card__title">
        <a href="<?= e($articleUrl) ?>"><?= e($title) ?></a>
    </h2>
    <?php if ($excerpt !== ''): ?>
    <p class="guide-card__excerpt"><?= e($excerpt) ?></p>
    <?php endif; ?>
    <a href="<?= e($articleUrl) ?>" class="guide-card__read">
        <span><?= e(t('guide_read_full')) ?></span>
        <span class="guide-card__arrow" aria-hidden="true">→</span>
    </a>
</article>
