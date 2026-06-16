<?php
/** @var array $good */
/** @var list<array> $gameGuideArticles */
/** @var string $productName */
$guideSlug = (string) $good['slug'];
$hubUrl = \App\GameGuide::guideHubUrl($guideSlug);
?>
<section class="game-guides-section card-panel" aria-labelledby="game-guides-section-title">
    <div class="game-guide-teaser__header">
        <h2 id="game-guides-section-title"><?= e(t('guide_teaser_title', ['product' => $productName])) ?></h2>
        <a href="<?= e($hubUrl) ?>" class="game-guide-teaser__link"><?= e(t('guide_all_for_game')) ?></a>
    </div>

    <ul class="game-guides-section__list">
        <?php foreach ($gameGuideArticles as $item): ?>
        <li class="game-guides-section__item">
            <a href="<?= e($item['articleUrl']) ?>" class="game-guides-section__link">
                <span class="game-guides-section__item-title"><?= e($item['resolved']['title']) ?></span>
                <?php if ($item['excerpt'] !== ''): ?>
                <span class="game-guides-section__item-excerpt"><?= e($item['excerpt']) ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
