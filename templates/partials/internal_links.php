<?php
/** @var list<array> $relatedGoods */
/** @var array|null $good */
$heading = $heading ?? t('seo_related_title');
?>
<aside class="internal-links" aria-labelledby="internal-links-heading">
    <h2 id="internal-links-heading" class="internal-links__title"><?= e($heading) ?></h2>
    <?php if (!empty($relatedGoods)): ?>
    <div class="catalog-grid catalog-grid--compact">
        <?php foreach ($relatedGoods as $g): ?>
        <a href="/g/<?= e($g['slug']) ?>" class="good-card" title="<?= e(t('seo_buy_title', ['product' => good_name($g)])) ?>">
            <img src="<?= e(cover_src($g)) ?>" alt="<?= e(good_name($g)) ?>" loading="lazy" decoding="async" width="180" height="180">
            <div class="good-card-body">
                <h3><?= e(good_name($g)) ?></h3>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p class="internal-links__more">
        <a href="/catalog"><?= e(t('seo_browse_catalog')) ?></a>
        <?php if (!empty($good['category_id'])): ?>
        · <a href="/catalog?category=<?= e(urlencode((string) $good['category_id'])) ?>"><?= e(t('seo_same_category')) ?></a>
        <?php endif; ?>
        · <a href="/referral"><?= e(t('nav_referral')) ?></a>
    </p>
</aside>
