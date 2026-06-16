<?php require_once __DIR__ . '/helpers.php'; ?>
<section class="guides-index">
    <header class="guides-index__header">
        <p class="guides-index__eyebrow"><?= e(t('guides_index_eyebrow')) ?></p>
        <h1><?= e(t('guides_index_heading')) ?></h1>
        <p class="guides-index__lead"><?= e(t('guides_index_lead')) ?></p>
    </header>

    <?php if (empty($items)): ?>
    <p class="text-muted"><?= e(t('guides_index_empty')) ?></p>
    <?php else: ?>
    <?php include __DIR__ . '/partials/guides_search.php'; ?>
    <div class="guides-index__grid" data-guides-grid>
        <?php foreach ($items as $item): ?>
        <?php
        $articleUrl = $item['articleUrl'];
        $title = $item['resolved']['title'];
        $excerpt = $item['excerpt'];
        $productName = $item['productName'];
        $searchHaystack = guide_search_haystack($title, $productName, $excerpt, (string) ($item['guide']['good_slug'] ?? ''));
        include __DIR__ . '/partials/guide_card.php';
        ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
