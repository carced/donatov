<?php
/** @var array $g */
require_once dirname(__DIR__) . '/helpers.php';
$productName = good_name($g);
$buyUrl = '/g/' . $g['slug'];
$freeUrl = '/referral?from=free';
?>
<article class="good-card-wrap">
    <a href="<?= e($buyUrl) ?>" class="good-card" title="<?= e(t('seo_buy_title', ['product' => $productName])) ?>">
        <img src="<?= e(cover_src($g)) ?>" alt="<?= e(t('seo_product_image_alt', ['product' => $productName])) ?>" loading="lazy" decoding="async" width="180" height="180">
        <div class="good-card-body">
            <h3 class="good-card__title"><?= e($productName) ?></h3>
            <?php if (!empty($g['instant'])): ?><span class="badge"><?= e(t('instant')) ?></span><?php endif; ?>
            <?php if (!empty($g['currency_name_ru'])): ?>
                <small class="good-card__meta"><?= e(currency_label($g)) ?></small>
            <?php endif; ?>
            <p class="good-card__seo"><?= e(product_seo_excerpt($g, (float) ($g['min_price_usd'] ?? 0))) ?></p>
        </div>
    </a>
    <div class="good-card-actions">
        <a href="<?= e($buyUrl) ?>" class="btn btn-sm btn-buy-link"><?= e(t('buy')) ?></a>
        <a href="<?= e($freeUrl) ?>" class="btn btn-sm btn-free-link" data-referral-slide><?= e(t('btn_free')) ?></a>
    </div>
</article>
