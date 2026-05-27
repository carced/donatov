<?php
/** @var list<array> $relatedGoods */
/** @var array|null $good */
require_once dirname(__DIR__) . '/helpers.php';
$heading = $heading ?? t('seo_related_title');
?>
<aside class="internal-links" aria-labelledby="internal-links-heading">
    <h2 id="internal-links-heading" class="internal-links__title"><?= e($heading) ?></h2>
    <?php if (!empty($relatedGoods)): ?>
    <div class="catalog-grid catalog-grid--compact catalog-grid--cards">
        <?php foreach ($relatedGoods as $g): ?>
            <?php include __DIR__ . '/good_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p class="internal-links__more">
        <a href="/catalog"><?= e(t('seo_browse_catalog')) ?></a>
        <?php if (!empty($good['category_id'])): ?>
        · <a href="/catalog?category=<?= e(urlencode((string) $good['category_id'])) ?>"><?= e(t('seo_same_category')) ?></a>
        <?php endif; ?>
        · <a href="/referral" data-referral-slide><?= e(t('btn_free')) ?> — <?= e(t('nav_referral')) ?></a>
    </p>
</aside>
