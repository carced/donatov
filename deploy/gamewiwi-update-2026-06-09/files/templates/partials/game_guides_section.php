<?php
/** @var array $good */
/** @var list<array> $gameGuideArticles */
/** @var string $productName */
$guideSlug = (string) $good['slug'];
$hubUrl = \App\GameGuide::guideHubUrl($guideSlug);
$articleCount = count($gameGuideArticles);
?>
<section class="game-guides-section" aria-labelledby="game-guides-section-title">
    <div class="game-guides-section__header">
        <div class="game-guides-section__headline">
            <p class="game-guides-section__eyebrow"><?= e(t('guides_index_eyebrow')) ?></p>
            <h2 id="game-guides-section-title"><?= e(t('guide_teaser_title', ['product' => $productName])) ?></h2>
        </div>
        <a href="<?= e($hubUrl) ?>" class="game-guides-section__hub-link"><?= e(t('guide_all_for_game')) ?></a>
    </div>

    <?php if ($articleCount > 1): ?>
    <?php include __DIR__ . '/guides_search.php'; ?>
    <?php endif; ?>

    <div class="game-guides-section__grid<?= $articleCount > 1 ? ' game-guides-section__grid--searchable' : '' ?>"
         <?= $articleCount > 1 ? 'data-guides-grid' : '' ?>>
        <?php foreach ($gameGuideArticles as $item): ?>
        <?php
        $articleUrl = $item['articleUrl'];
        $title = $item['resolved']['title'];
        $excerpt = $item['excerpt'];
        $searchHaystack = guide_search_haystack($title, $productName, $excerpt, $guideSlug);
        $showGameBadge = false;
        include __DIR__ . '/guide_card.php';
        ?>
        <?php endforeach; ?>
    </div>
</section>
