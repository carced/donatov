<?php
require_once __DIR__ . '/helpers.php';

$guideSlug = (string) $goodSlug;
$articleSlug = (string) ($guideRow['article_slug'] ?? '');
$hubUrl = \App\GameGuide::guideHubUrl($guideSlug);
$storeUrl = \App\GameGuide::storeUrl($guideSlug);
?>
<article class="game-guide-page" itemscope itemtype="https://schema.org/Article">
    <meta itemprop="headline" content="<?= e($resolved['title']) ?>">
    <link itemprop="url" href="<?= e($seo['canonical'] ?? '') ?>">

    <header class="game-guide-page__header">
        <p class="game-guide-page__eyebrow">
            <a href="<?= e($hubUrl) ?>"><?= e(t('guide_page_eyebrow', ['product' => $productName])) ?></a>
        </p>
        <h1 itemprop="name"><?= e($resolved['title']) ?></h1>
        <p class="game-guide-page__lead"><?= e(t('guide_page_lead', ['product' => $productName])) ?></p>
        <?php if ($minPriceUsd > 0): ?>
        <div class="game-guide-page__meta">
            <span class="game-guide-page__price"><?= e(t('seo_from_price', ['price' => price_usd((float) $minPriceUsd)])) ?></span>
        </div>
        <?php endif; ?>
    </header>

    <?php
    $guideCtaVariant = 'hero';
    include __DIR__ . '/partials/guide_cta.php';
    ?>

    <div class="guide-content" itemprop="articleBody">
        <?= $guideContentHtml ?>
    </div>

    <?php
    $guideCtaVariant = 'hero';
    include __DIR__ . '/partials/guide_cta.php';
    ?>

    <?php if (!empty($siblingArticles) && count($siblingArticles) > 1): ?>
    <nav class="guide-siblings card-panel" aria-label="<?= e(t('guide_more_articles')) ?>">
        <h2><?= e(t('guide_more_articles')) ?></h2>
        <ul>
            <?php foreach ($siblingArticles as $sib): ?>
            <?php if ((string) ($sib['guide']['article_slug'] ?? '') === $articleSlug) continue; ?>
            <li><a href="<?= e($sib['articleUrl']) ?>"><?= e($sib['resolved']['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <nav class="game-guide-page__footer-nav" aria-label="<?= e(t('guide_related_nav')) ?>">
        <a href="<?= e($hubUrl) ?>" class="btn btn-secondary"><?= e(t('guide_all_for_game')) ?></a>
        <a href="/guides" class="btn btn-secondary"><?= e(t('guide_all_guides')) ?></a>
    </nav>
</article>
