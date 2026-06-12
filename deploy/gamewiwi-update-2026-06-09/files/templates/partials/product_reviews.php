<?php
/** @var array $productReviews */
/** @var array{average: float, count: int} $reviewStats */
require_once dirname(__DIR__) . '/helpers.php';
$productReviews = $productReviews ?? [];
$reviewStats = $reviewStats ?? ['average' => 0, 'count' => 0];
if ($productReviews === []) {
    return;
}
?>
<section class="product-reviews card-panel" aria-labelledby="reviews-heading">
    <header class="product-reviews__head">
        <div>
            <h2 id="reviews-heading"><?= e(t('reviews_title')) ?></h2>
            <p class="product-reviews__summary">
                <span class="product-reviews__stars" aria-hidden="true"><?= str_repeat('★', (int) round($reviewStats['average'])) ?><?= str_repeat('☆', 5 - (int) round($reviewStats['average'])) ?></span>
                <strong><?= e(number_format((float) $reviewStats['average'], 1)) ?></strong>
                <span class="text-muted">· <?= e(t('reviews_count', ['count' => (string) $reviewStats['count']])) ?></span>
            </p>
        </div>
    </header>
    <ul class="product-reviews__list">
        <?php foreach ($productReviews as $review): ?>
        <li class="product-review" itemprop="review" itemscope itemtype="https://schema.org/Review">
            <div class="product-review__top">
                <span class="product-review__author" itemprop="author"><?= e($review['author']) ?></span>
                <span class="product-review__meta">
                    <span class="product-review__rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                        <meta itemprop="ratingValue" content="<?= (int) $review['rating'] ?>">
                        <meta itemprop="bestRating" content="5">
                        <?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?>
                    </span>
                    <time datetime="<?= e($review['date']) ?>" itemprop="datePublished"><?= e($review['date']) ?></time>
                </span>
            </div>
            <p class="product-review__text" itemprop="reviewBody"><?= e($review['text']) ?></p>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
