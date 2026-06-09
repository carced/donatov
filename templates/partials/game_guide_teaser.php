<?php
/** @var array $good */
/** @var array $gameGuideResolved */
/** @var string $productName */
$guideSlug = (string) $good['slug'];
$guideUrl = \App\GameGuide::guideUrl($guideSlug);
$excerpt = \App\GameGuide::excerpt($gameGuideResolved['content'], 320);
?>
<section class="game-guide-teaser card-panel" aria-labelledby="game-guide-teaser-title">
    <div class="game-guide-teaser__header">
        <h2 id="game-guide-teaser-title"><?= e(t('guide_teaser_title', ['product' => $productName])) ?></h2>
        <a href="<?= e($guideUrl) ?>" class="game-guide-teaser__link"><?= e(t('guide_read_full')) ?></a>
    </div>
    <?php if ($excerpt !== ''): ?>
    <p class="game-guide-teaser__excerpt"><?= e($excerpt) ?></p>
    <?php endif; ?>
    <?php
    $guideCtaVariant = 'compact';
    include __DIR__ . '/guide_cta.php';
    ?>
</section>
