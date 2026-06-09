<?php
require_once __DIR__ . '/helpers.php';

$guideSlug = (string) $good['slug'];
$guideUrl = \App\GameGuide::guideUrl($guideSlug);
$storeUrl = \App\GameGuide::storeUrl($guideSlug);
$contentWithCtas = guide_html_with_ctas($guideContentHtml, $productName, $guideSlug);
?>
<article class="game-guide-page" itemscope itemtype="https://schema.org/Article">
    <meta itemprop="headline" content="<?= e($resolved['title']) ?>">
    <link itemprop="url" href="<?= e($seo['canonical'] ?? $guideUrl) ?>">

    <header class="game-guide-page__header">
        <p class="game-guide-page__eyebrow"><?= e(t('guide_page_eyebrow', ['product' => $productName])) ?></p>
        <h1 itemprop="name"><?= e($resolved['title']) ?></h1>
        <p class="game-guide-page__lead"><?= e(t('guide_page_lead', ['product' => $productName])) ?></p>
        <div class="game-guide-page__meta">
            <a href="<?= e($storeUrl) ?>" class="game-guide-page__store-link"><?= e(t('guide_store_link', ['product' => $productName])) ?></a>
            <?php if ($minPriceUsd > 0): ?>
            <span class="game-guide-page__price"><?= e(t('seo_from_price', ['price' => price_usd((float) $minPriceUsd)])) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <?php
    $guideCtaVariant = 'hero';
    include __DIR__ . '/partials/guide_cta.php';
    ?>

    <div class="guide-content" itemprop="articleBody">
        <?= $contentWithCtas ?>
    </div>

    <?php
    $guideCtaVariant = 'hero';
    include __DIR__ . '/partials/guide_cta.php';
    ?>

    <nav class="game-guide-page__footer-nav" aria-label="<?= e(t('guide_related_nav')) ?>">
        <a href="<?= e($storeUrl) ?>" class="btn btn-primary"><?= e(t('guide_cta_buy', ['product' => $productName])) ?></a>
        <a href="/guides" class="btn btn-secondary"><?= e(t('guide_all_guides')) ?></a>
    </nav>
</article>
