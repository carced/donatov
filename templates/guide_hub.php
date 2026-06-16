<?php require_once __DIR__ . '/helpers.php'; ?>
<section class="guide-hub">
    <header class="guide-hub__header">
        <p class="guide-hub__eyebrow">
            <a href="/guides"><?= e(t('nav_guides')) ?></a>
        </p>
        <h1><?= e(t('guide_hub_title', ['product' => $productName])) ?></h1>
        <p class="guide-hub__lead"><?= e(t('guide_hub_lead', ['product' => $productName])) ?></p>
    </header>

    <?php if (count($articles) > 1): ?>
    <?php include __DIR__ . '/partials/guides_search.php'; ?>
    <?php endif; ?>

    <div class="guides-index__grid" data-guides-grid>
        <?php foreach ($articles as $item): ?>
        <?php
        $articleUrl = $item['articleUrl'];
        $title = $item['resolved']['title'];
        $excerpt = $item['excerpt'];
        $searchHaystack = guide_search_haystack($title, $productName, $excerpt, $goodSlug);
        $showGameBadge = false;
        include __DIR__ . '/partials/guide_card.php';
        ?>
        <?php endforeach; ?>
    </div>
</section>
